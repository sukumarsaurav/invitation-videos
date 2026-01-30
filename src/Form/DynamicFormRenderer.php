<?php
/**
 * Invitation Videos - Dynamic Form Renderer
 * 
 * Renders template-specific customization forms based on database field definitions
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../Core/Security.php';

class DynamicFormRenderer
{

    /**
     * Get all fields for a template, grouped by step_number (mapped to field_group)
     * Uses the new template_field_presets table that links to field_presets
     */
    public function getFields(int $templateId): array
    {
        $sql = "SELECT 
                    tfp.id,
                    tfp.template_id,
                    tfp.preset_id,
                    tfp.is_required,
                    tfp.display_order,
                    tfp.step_number,
                    fp.name as field_label,
                    fp.field_name,
                    fp.field_type,
                    fp.placeholder,
                    fp.help_text,
                    fp.sample_value,
                    fp.validation_rules,
                    fp.category,
                    CASE 
                        WHEN tfp.step_number = 1 THEN 'event_details'
                        WHEN tfp.step_number = 2 THEN 'photos'
                        WHEN tfp.step_number = 3 THEN 'audio'
                        ELSE 'general'
                    END as field_group
                FROM template_field_presets tfp
                JOIN field_presets fp ON tfp.preset_id = fp.id
                WHERE tfp.template_id = ? AND fp.is_active = 1
                ORDER BY tfp.step_number, tfp.display_order";

        $fields = Database::fetchAll($sql, [$templateId]);

        // Group fields by field_group (derived from step_number)
        $grouped = [];
        foreach ($fields as $field) {
            $group = $field['field_group'] ?? 'general';
            $grouped[$group][] = $field;
        }

        return $grouped;
    }

    /**
     * Get field options for select/dropdown fields
     */
    public function getFieldOptions(int $fieldId): array
    {
        $sql = "SELECT option_value, option_label FROM field_options 
                WHERE field_id = ? ORDER BY display_order";
        return Database::fetchAll($sql, [$fieldId]);
    }

    /**
     * Get music tracks from music_library table
     */
    public function getMusicLibrary(): array
    {
        try {
            $sql = "SELECT id, name, slug, category, s3_url, duration_seconds, file_size_kb 
                    FROM music_library 
                    WHERE is_active = 1 
                    ORDER BY category, name";
            return Database::fetchAll($sql);
        } catch (\PDOException $e) {
            // Table might not exist yet
            error_log("Music library query failed: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Render HTML form for template
     */
    public function render(int $templateId, array $values = []): string
    {
        $groupedFields = $this->getFields($templateId);
        $html = '';

        foreach ($groupedFields as $groupName => $fields) {
            $html .= $this->renderGroup($groupName, $fields, $values);
        }

        return $html;
    }

    /**
     * Render HTML form for specific field groups only
     * Used for multi-step forms
     */
    public function renderByGroups(int $templateId, array $allowedGroups, array $values = []): string
    {
        $groupedFields = $this->getFields($templateId);
        $html = '';

        foreach ($groupedFields as $groupName => $fields) {
            if (in_array($groupName, $allowedGroups)) {
                $html .= $this->renderGroup($groupName, $fields, $values);
            }
        }

        return $html;
    }

    /**
     * Check if template has fields in specific groups
     */
    public function hasFieldsInGroups(int $templateId, array $groups): bool
    {
        $groupedFields = $this->getFields($templateId);
        foreach ($groups as $group) {
            if (!empty($groupedFields[$group])) {
                return true;
            }
        }
        return false;
    }

    /**
     * Render a field group section
     */
    private function renderGroup(string $groupName, array $fields, array $values): string
    {
        $groupLabels = [
            'couple_details' => '💑 Couple Details',
            'family_details' => '👨‍👩‍👧 Family Details',
            'event_details' => '📅 Event Details',
            'photos' => '📷 Photos',
            'audio' => '🎵 Background Music',
            'general' => '📝 Details'
        ];

        $label = $groupLabels[$groupName] ?? ucfirst(str_replace('_', ' ', $groupName));

        $html = '<section class="form-section bg-white rounded-xl p-6 shadow-sm border border-slate-200 mb-6">';
        $html .= '<h3 class="text-lg font-bold mb-4 flex items-center gap-2">' . Security::escape($label) . '</h3>';
        $html .= '<div class="grid grid-cols-1 md:grid-cols-2 gap-4">';

        foreach ($fields as $field) {
            $html .= $this->renderField($field, $values[$field['field_name']] ?? null);
        }

        $html .= '</div></section>';

        return $html;
    }

    /**
     * Render individual field
     */
    private function renderField(array $field, $value = null): string
    {
        $name = Security::escape($field['field_name']);
        $label = Security::escape($field['field_label']);
        $placeholder = Security::escape($field['placeholder'] ?? '');
        $required = $field['is_required'] ? 'required' : '';
        $requiredBadge = $field['is_required']
            ? '<span class="text-xs bg-primary/10 text-primary px-2 py-0.5 rounded">Required</span>'
            : '';

        $html = '<div class="form-field';

        // Full width for textarea, image, and music
        if (in_array($field['field_type'], ['textarea', 'image', 'music'])) {
            $html .= ' md:col-span-2';
        }

        $html .= '">';
        $html .= '<label class="flex items-center justify-between mb-2">';
        $html .= '<span class="text-sm font-medium text-slate-700">' . $label . '</span>';
        $html .= $requiredBadge;
        $html .= '</label>';

        switch ($field['field_type']) {
            case 'text':
            case 'number':
                $type = $field['field_type'] === 'number' ? 'number' : 'text';
                $html .= '<input type="' . $type . '" name="' . $name . '" 
                    class="w-full h-11 px-4 rounded-lg border border-slate-200 bg-slate-50 focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all"
                    placeholder="' . $placeholder . '" 
                    value="' . Security::escape($value ?? '') . '" ' . $required . '>';
                break;

            case 'textarea':
                $html .= '<textarea name="' . $name . '" 
                    class="w-full min-h-[100px] p-4 rounded-lg border border-slate-200 bg-slate-50 focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all resize-y"
                    placeholder="' . $placeholder . '" ' . $required . '>' . Security::escape($value ?? '') . '</textarea>';
                break;

            case 'date':
                $html .= '<input type="date" name="' . $name . '" 
                    class="w-full h-11 px-4 rounded-lg border border-slate-200 bg-slate-50 focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all"
                    value="' . Security::escape($value ?? '') . '" ' . $required . '>';
                break;

            case 'time':
                $html .= '<input type="time" name="' . $name . '" 
                    class="w-full h-11 px-4 rounded-lg border border-slate-200 bg-slate-50 focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all"
                    value="' . Security::escape($value ?? '') . '" ' . $required . '>';
                break;

            case 'datetime':
                $html .= '<input type="datetime-local" name="' . $name . '" 
                    class="w-full h-11 px-4 rounded-lg border border-slate-200 bg-slate-50 focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all"
                    value="' . Security::escape($value ?? '') . '" ' . $required . '>';
                break;

            case 'color':
                $html .= '<input type="color" name="' . $name . '" 
                    class="w-full h-11 rounded-lg border border-slate-200 cursor-pointer"
                    value="' . Security::escape($value ?? '#7f13ec') . '">';
                break;

            case 'select':
                $html .= '<select name="' . $name . '" 
                    class="w-full h-11 px-4 rounded-lg border border-slate-200 bg-slate-50 focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all" ' . $required . '>';
                $html .= '<option value="">Select...</option>';

                $options = $this->getFieldOptions($field['id']);
                foreach ($options as $option) {
                    $selected = ($value === $option['option_value']) ? 'selected' : '';
                    $html .= '<option value="' . Security::escape($option['option_value']) . '" ' . $selected . '>'
                        . Security::escape($option['option_label']) . '</option>';
                }
                $html .= '</select>';
                break;

            case 'image':
                $html .= $this->renderImageUpload($name, $field, $value);
                break;

            case 'music':
                $html .= $this->renderMusicSelector($name, $field, $value);
                break;
        }

        // Help text
        if (!empty($field['help_text'])) {
            $html .= '<p class="text-xs text-slate-500 mt-1">' . Security::escape($field['help_text']) . '</p>';
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Render image upload field
     */
    private function renderImageUpload(string $name, array $field, $value): string
    {
        $html = '<div class="image-upload-wrapper">';
        $html .= '<div class="border-2 border-dashed border-slate-200 hover:border-primary rounded-xl p-6 text-center cursor-pointer transition-all" 
            onclick="document.getElementById(\'' . $name . '_input\').click()">';
        $html .= '<div class="flex flex-col items-center gap-2">';
        $html .= '<span class="material-symbols-outlined text-4xl text-slate-400">cloud_upload</span>';
        $html .= '<p class="text-sm font-medium">Click to upload</p>';
        $html .= '<p class="text-xs text-slate-500">JPG, PNG (Max 10MB)</p>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '<input type="file" id="' . $name . '_input" name="' . $name . '" 
            accept="image/jpeg,image/png,image/webp" class="hidden" 
            onchange="previewImage(this, \'' . $name . '_preview\')">';
        $html .= '<div id="' . $name . '_preview" class="mt-2 hidden">';
        $html .= '<img src="" alt="Preview" class="max-h-40 rounded-lg mx-auto">';
        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    /**
     * Render music selector field with tracks from music_library
     */
    private function renderMusicSelector(string $name, array $field, $value): string
    {
        $tracks = $this->getMusicLibrary();

        // Category labels for display
        $categoryLabels = [
            'wedding' => '💒 Wedding',
            'traditional' => '🎭 Traditional',
            'romantic' => '💕 Romantic',
            'modern' => '🎧 Modern',
            'upbeat' => '🎉 Upbeat',
            'party' => '🎊 Party',
            'festival' => '🪔 Festival',
            'puja' => '🙏 Puja'
        ];

        $html = '<div class="music-selector">';

        if (empty($tracks)) {
            // No tracks in library - show upload option only
            $html .= '<div class="text-center py-6 text-slate-500">';
            $html .= '<span class="material-symbols-outlined text-4xl text-slate-300 mb-2">music_off</span>';
            $html .= '<p class="text-sm">No music tracks available. Upload your own track below.</p>';
            $html .= '</div>';
        } else {
            // Group tracks by category
            $groupedTracks = [];
            foreach ($tracks as $track) {
                $category = $track['category'] ?? 'other';
                $groupedTracks[$category][] = $track;
            }

            // Option to use no music / default
            $noMusicChecked = (empty($value) || $value === 'none') ? 'checked' : '';
            $html .= '<label class="music-option flex items-center gap-4 p-4 rounded-xl border-2 cursor-pointer transition-all mb-3 ' . ($noMusicChecked ? 'border-primary bg-primary/5' : 'border-slate-200 hover:border-primary/50') . '">';
            $html .= '<input type="radio" name="' . $name . '" value="none" class="hidden music-radio" ' . $noMusicChecked . '>';
            $html .= '<div class="size-12 rounded-full bg-slate-100 flex items-center justify-center shrink-0">';
            $html .= '<span class="material-symbols-outlined text-slate-400">music_off</span>';
            $html .= '</div>';
            $html .= '<div class="flex-1">';
            $html .= '<p class="font-semibold text-slate-800">Use Template Default</p>';
            $html .= '<p class="text-xs text-slate-500">Use the default music for this template</p>';
            $html .= '</div>';
            $html .= '<span class="material-symbols-outlined text-primary opacity-0 check-icon">check_circle</span>';
            $html .= '</label>';

            // Render each category
            foreach ($groupedTracks as $category => $categoryTracks) {
                $categoryLabel = $categoryLabels[$category] ?? ucfirst($category);

                $html .= '<div class="mb-4">';
                $html .= '<h4 class="text-sm font-bold text-slate-600 mb-2 flex items-center gap-2">' . $categoryLabel . '</h4>';
                $html .= '<div class="space-y-2">';

                foreach ($categoryTracks as $track) {
                    $trackValue = Security::escape($track['s3_url']);
                    $checked = ($value === $track['s3_url']) ? 'checked' : '';
                    $duration = $track['duration_seconds'] ? gmdate('i:s', $track['duration_seconds']) : '';

                    $html .= '<label class="music-option flex items-center gap-4 p-3 rounded-xl border-2 cursor-pointer transition-all ' . ($checked ? 'border-primary bg-primary/5' : 'border-slate-200 hover:border-primary/50') . '">';
                    $html .= '<input type="radio" name="' . $name . '" value="' . $trackValue . '" class="hidden music-radio" ' . $checked . '>';

                    // Play button
                    $html .= '<button type="button" class="music-play-btn size-10 rounded-full bg-primary/10 hover:bg-primary/20 flex items-center justify-center shrink-0 transition-colors" data-url="' . $trackValue . '">';
                    $html .= '<span class="material-symbols-outlined text-primary play-icon">play_arrow</span>';
                    $html .= '<span class="material-symbols-outlined text-primary pause-icon hidden">pause</span>';
                    $html .= '</button>';

                    // Track info
                    $html .= '<div class="flex-1 min-w-0">';
                    $html .= '<p class="font-medium text-slate-800 truncate">' . Security::escape($track['name']) . '</p>';
                    if ($duration) {
                        $html .= '<p class="text-xs text-slate-500">' . $duration . '</p>';
                    }
                    $html .= '</div>';

                    // Check icon
                    $html .= '<span class="material-symbols-outlined text-primary ' . ($checked ? 'opacity-100' : 'opacity-0') . ' check-icon">check_circle</span>';
                    $html .= '</label>';
                }

                $html .= '</div>';
                $html .= '</div>';
            }
        }

        // Custom upload option
        $uploadId = $name . '_custom';
        $html .= '<div class="mt-4 pt-4 border-t border-slate-200">';
        $html .= '<p class="text-sm font-bold text-slate-600 mb-3 flex items-center gap-2">';
        $html .= '<span class="material-symbols-outlined text-lg">upload</span> Or upload your own track';
        $html .= '</p>';

        // Dropzone
        $html .= '<div class="border-2 border-dashed border-slate-200 hover:border-primary rounded-xl p-4 text-center cursor-pointer transition-all" 
            id="' . $uploadId . '_dropzone" 
            onclick="document.getElementById(\'' . $uploadId . '\').click()">';

        // Placeholder state
        $html .= '<div id="' . $uploadId . '_placeholder" class="flex flex-col items-center gap-2">';
        $html .= '<span class="material-symbols-outlined text-3xl text-slate-400">music_note</span>';
        $html .= '<p class="text-sm font-medium">Click to upload MP3</p>';
        $html .= '<p class="text-xs text-slate-500">Max 20MB • MP3 format only</p>';
        $html .= '</div>';

        // Selected file state
        $html .= '<div id="' . $uploadId . '_selected" class="hidden">';
        $html .= '<div class="flex items-center justify-center gap-3">';
        $html .= '<span class="material-symbols-outlined text-2xl text-primary">audio_file</span>';
        $html .= '<div class="text-left">';
        $html .= '<p id="' . $uploadId . '_filename" class="text-sm font-medium text-slate-800 truncate max-w-[200px]"></p>';
        $html .= '<p id="' . $uploadId . '_size" class="text-xs text-slate-500"></p>';
        $html .= '</div>';
        $html .= '<button type="button" class="p-1.5 rounded-full hover:bg-red-100 text-red-500 transition-colors" onclick="event.stopPropagation(); clearMusicFile(\'' . $uploadId . '\')">';
        $html .= '<span class="material-symbols-outlined text-lg">close</span>';
        $html .= '</button>';
        $html .= '</div>';

        // Progress bar
        $html .= '<div id="' . $uploadId . '_progress" class="hidden mt-3">';
        $html .= '<div class="flex items-center justify-between text-xs text-slate-600 mb-1">';
        $html .= '<span>Uploading...</span>';
        $html .= '<span id="' . $uploadId . '_percent">0%</span>';
        $html .= '</div>';
        $html .= '<div class="h-2 bg-slate-200 rounded-full overflow-hidden">';
        $html .= '<div id="' . $uploadId . '_bar" class="h-full bg-primary rounded-full transition-all duration-300" style="width: 0%"></div>';
        $html .= '</div>';
        $html .= '</div>';

        $html .= '</div>'; // End selected state
        $html .= '</div>'; // End dropzone

        $html .= '<input type="file" id="' . $uploadId . '" name="' . $name . '" accept="audio/mpeg,audio/mp3" class="hidden" onchange="handleMusicSelect(this, \'' . $uploadId . '\')">';
        $html .= '</div>';

        // Hidden audio element for previews
        $html .= '<audio id="music-preview-player" class="hidden"></audio>';

        // JavaScript for music selection and preview
        $html .= '<script>
            (function() {
                let currentlyPlaying = null;
                const audioPlayer = document.getElementById("music-preview-player");
                
                // Handle radio button selection styling
                document.querySelectorAll(".music-radio").forEach(function(radio) {
                    radio.addEventListener("change", function() {
                        // Remove active state from all options
                        document.querySelectorAll(".music-option").forEach(function(opt) {
                            opt.classList.remove("border-primary", "bg-primary/5");
                            opt.classList.add("border-slate-200");
                            const checkIcon = opt.querySelector(".check-icon");
                            if (checkIcon) checkIcon.classList.add("opacity-0");
                        });
                        
                        // Add active state to selected option
                        const label = this.closest(".music-option");
                        if (label) {
                            label.classList.remove("border-slate-200");
                            label.classList.add("border-primary", "bg-primary/5");
                            const checkIcon = label.querySelector(".check-icon");
                            if (checkIcon) checkIcon.classList.remove("opacity-0");
                        }
                        
                        // Clear custom upload if selecting a preset
                        const uploadId = "' . $uploadId . '";
                        if (this.value && this.value !== "none" && this.value.startsWith("http")) {
                            clearMusicFile(uploadId);
                        }
                    });
                });
                
                // Handle play/pause buttons
                document.querySelectorAll(".music-play-btn").forEach(function(btn) {
                    btn.addEventListener("click", function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        
                        const url = this.dataset.url;
                        const playIcon = this.querySelector(".play-icon");
                        const pauseIcon = this.querySelector(".pause-icon");
                        
                        if (currentlyPlaying === this) {
                            // Pause current track
                            audioPlayer.pause();
                            playIcon.classList.remove("hidden");
                            pauseIcon.classList.add("hidden");
                            currentlyPlaying = null;
                        } else {
                            // Stop any currently playing track
                            if (currentlyPlaying) {
                                const prevPlayIcon = currentlyPlaying.querySelector(".play-icon");
                                const prevPauseIcon = currentlyPlaying.querySelector(".pause-icon");
                                prevPlayIcon.classList.remove("hidden");
                                prevPauseIcon.classList.add("hidden");
                            }
                            
                            // Play new track
                            audioPlayer.src = url;
                            audioPlayer.play();
                            playIcon.classList.add("hidden");
                            pauseIcon.classList.remove("hidden");
                            currentlyPlaying = this;
                        }
                    });
                });
                
                // Handle audio ended
                audioPlayer.addEventListener("ended", function() {
                    if (currentlyPlaying) {
                        const playIcon = currentlyPlaying.querySelector(".play-icon");
                        const pauseIcon = currentlyPlaying.querySelector(".pause-icon");
                        playIcon.classList.remove("hidden");
                        pauseIcon.classList.add("hidden");
                        currentlyPlaying = null;
                    }
                });
            })();
            
            function handleMusicSelect(input, uploadId) {
                const file = input.files[0];
                if (!file) return;
                
                // Validate file size (20MB)
                if (file.size > 20 * 1024 * 1024) {
                    alert("File size must be less than 20MB");
                    input.value = "";
                    return;
                }
                
                // Deselect any preset music options
                document.querySelectorAll(".music-radio").forEach(function(radio) {
                    radio.checked = false;
                });
                document.querySelectorAll(".music-option").forEach(function(opt) {
                    opt.classList.remove("border-primary", "bg-primary/5");
                    opt.classList.add("border-slate-200");
                    const checkIcon = opt.querySelector(".check-icon");
                    if (checkIcon) checkIcon.classList.add("opacity-0");
                });
                
                // Show selected file info
                document.getElementById(uploadId + "_placeholder").classList.add("hidden");
                document.getElementById(uploadId + "_selected").classList.remove("hidden");
                document.getElementById(uploadId + "_filename").textContent = file.name;
                document.getElementById(uploadId + "_size").textContent = (file.size / (1024 * 1024)).toFixed(2) + " MB";
                document.getElementById(uploadId + "_dropzone").classList.add("border-primary", "bg-primary/5");
            }
            
            function clearMusicFile(uploadId) {
                const input = document.getElementById(uploadId);
                if (input) input.value = "";
                const placeholder = document.getElementById(uploadId + "_placeholder");
                const selected = document.getElementById(uploadId + "_selected");
                const progress = document.getElementById(uploadId + "_progress");
                const dropzone = document.getElementById(uploadId + "_dropzone");
                
                if (placeholder) placeholder.classList.remove("hidden");
                if (selected) selected.classList.add("hidden");
                if (progress) progress.classList.add("hidden");
                if (dropzone) {
                    dropzone.classList.remove("border-primary", "bg-primary/5");
                }
            }
            
            // Handle form submission with progress
            document.addEventListener("DOMContentLoaded", function() {
                const form = document.getElementById("customize-form");
                if (!form) return;
                
                form.addEventListener("submit", function(e) {
                    const fileInputs = form.querySelectorAll("input[type=file]");
                    let hasFiles = false;
                    let totalSize = 0;
                    
                    fileInputs.forEach(function(input) {
                        if (input.files && input.files.length > 0) {
                            hasFiles = true;
                            totalSize += input.files[0].size;
                        }
                    });
                    
                    if (!hasFiles || totalSize < 1024 * 1024) return;
                    
                    e.preventDefault();
                    
                    const musicProgressDiv = document.querySelector("[id$=_custom_progress]");
                    if (musicProgressDiv) {
                        musicProgressDiv.classList.remove("hidden");
                    }
                    
                    const formData = new FormData(form);
                    const xhr = new XMLHttpRequest();
                    
                    xhr.upload.addEventListener("progress", function(evt) {
                        if (evt.lengthComputable) {
                            const percent = Math.round((evt.loaded / evt.total) * 100);
                            document.querySelectorAll("[id$=_custom_bar]").forEach(function(bar) {
                                bar.style.width = percent + "%";
                            });
                            document.querySelectorAll("[id$=_custom_percent]").forEach(function(pct) {
                                pct.textContent = percent + "%";
                            });
                        }
                    });
                    
                    xhr.addEventListener("load", function() {
                        if (xhr.status >= 200 && xhr.status < 400) {
                            try {
                                var response = JSON.parse(xhr.responseText);
                                if (response.success && response.redirect) {
                                    window.location.href = response.redirect;
                                    return;
                                }
                            } catch (e) {}
                            window.location.reload();
                        } else {
                            alert("Upload failed. Please try again.");
                            window.location.reload();
                        }
                    });
                    
                    xhr.addEventListener("error", function() {
                        alert("Upload failed. Please check your connection.");
                        window.location.reload();
                    });
                    
                    xhr.open("POST", form.action || window.location.href);
                    xhr.setRequestHeader("X-Requested-With", "XMLHttpRequest");
                    xhr.send(formData);
                });
            });
        </script>';

        $html .= '</div>';

        return $html;
    }

    /**
     * Validate form submission
     */
    public function validate(int $templateId, array $data, array $files): array
    {
        $errors = [];
        $fields = Database::fetchAll(
            "SELECT tfp.is_required, fp.field_name, fp.name as field_label, fp.field_type, fp.validation_rules
             FROM template_field_presets tfp
             JOIN field_presets fp ON tfp.preset_id = fp.id
             WHERE tfp.template_id = ?",
            [$templateId]
        );

        foreach ($fields as $field) {
            $name = $field['field_name'];
            $label = $field['field_label'] ?? $field['field_name'];
            $value = $data[$name] ?? null;

            // Check required fields
            if ($field['is_required']) {
                if ($field['field_type'] === 'image') {
                    if (empty($files[$name]['tmp_name'])) {
                        $errors[$name] = $label . ' is required';
                    }
                } elseif (empty($value)) {
                    $errors[$name] = $label . ' is required';
                }
            }

            // Validate based on rules
            if (!empty($field['validation_rules'])) {
                $rules = json_decode($field['validation_rules'], true);

                if (!empty($rules['max_length']) && strlen($value) > $rules['max_length']) {
                    $errors[$name] = $label . ' exceeds maximum length';
                }
            }

            // Validate file uploads
            if ($field['field_type'] === 'image' && !empty($files[$name]['tmp_name'])) {
                $uploadErrors = Security::validateUpload($files[$name], ALLOWED_IMAGE_TYPES);
                if (!empty($uploadErrors)) {
                    $errors[$name] = implode(', ', $uploadErrors);
                }
            }
        }

        return $errors;
    }
}
