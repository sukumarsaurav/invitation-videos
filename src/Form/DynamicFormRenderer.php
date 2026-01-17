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
     * Get music presets for music fields
     */
    public function getMusicPresets(): array
    {
        try {
            $sql = "SELECT * FROM music_presets WHERE is_active = 1 ORDER BY display_order";
            return Database::fetchAll($sql);
        } catch (\PDOException $e) {
            // Table might not exist yet
            error_log("Music presets query failed: " . $e->getMessage());
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

        $html = '<section class="form-section bg-white dark:bg-slate-900 rounded-xl p-6 shadow-sm border border-slate-200 dark:border-slate-800 mb-6">';
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
        $html .= '<span class="text-sm font-medium text-slate-700 dark:text-slate-300">' . $label . '</span>';
        $html .= $requiredBadge;
        $html .= '</label>';

        switch ($field['field_type']) {
            case 'text':
            case 'number':
                $type = $field['field_type'] === 'number' ? 'number' : 'text';
                $html .= '<input type="' . $type . '" name="' . $name . '" 
                    class="w-full h-11 px-4 rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all"
                    placeholder="' . $placeholder . '" 
                    value="' . Security::escape($value ?? '') . '" ' . $required . '>';
                break;

            case 'textarea':
                $html .= '<textarea name="' . $name . '" 
                    class="w-full min-h-[100px] p-4 rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all resize-y"
                    placeholder="' . $placeholder . '" ' . $required . '>' . Security::escape($value ?? '') . '</textarea>';
                break;

            case 'date':
                $html .= '<input type="date" name="' . $name . '" 
                    class="w-full h-11 px-4 rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all"
                    value="' . Security::escape($value ?? '') . '" ' . $required . '>';
                break;

            case 'time':
                $html .= '<input type="time" name="' . $name . '" 
                    class="w-full h-11 px-4 rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all"
                    value="' . Security::escape($value ?? '') . '" ' . $required . '>';
                break;

            case 'datetime':
                $html .= '<input type="datetime-local" name="' . $name . '" 
                    class="w-full h-11 px-4 rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all"
                    value="' . Security::escape($value ?? '') . '" ' . $required . '>';
                break;

            case 'color':
                $html .= '<input type="color" name="' . $name . '" 
                    class="w-full h-11 rounded-lg border border-slate-200 dark:border-slate-700 cursor-pointer"
                    value="' . Security::escape($value ?? '#7f13ec') . '">';
                break;

            case 'select':
                $html .= '<select name="' . $name . '" 
                    class="w-full h-11 px-4 rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all" ' . $required . '>';
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
        $html .= '<div class="border-2 border-dashed border-slate-200 dark:border-slate-700 hover:border-primary rounded-xl p-6 text-center cursor-pointer transition-all" 
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
     * Render music selector field
     */
    private function renderMusicSelector(string $name, array $field, $value): string
    {
        $presets = $this->getMusicPresets();

        $html = '<div class="music-selector space-y-3">';

        if (empty($presets)) {
            // No presets - show simple upload message
            $html .= '<div class="text-center py-4 text-slate-500">';
            $html .= '<p class="text-sm">Upload your own music track below</p>';
            $html .= '</div>';
        } else {
            foreach ($presets as $preset) {
                $checked = ($value === $preset['id']) ? 'checked' : '';
                $html .= '<label class="flex items-center p-4 rounded-xl border border-slate-200 dark:border-slate-700 hover:border-primary cursor-pointer transition-all ' . ($checked ? 'border-primary bg-primary/5' : '') . '">';
                $html .= '<input type="radio" name="' . $name . '" value="' . $preset['id'] . '" class="hidden peer" ' . $checked . '>';
                $html .= '<div class="flex-1">';
                $html .= '<div class="font-medium">' . Security::escape($preset['name']) . '</div>';
                $html .= '<div class="text-xs text-slate-500">' . Security::escape($preset['description'] ?? '') . '</div>';
                $html .= '</div>';
                $html .= '<button type="button" class="p-2 rounded-full bg-slate-100 dark:bg-slate-800 hover:bg-primary hover:text-white transition-colors" onclick="playPreview(\'' . Security::escape($preset['file_url']) . '\')">';
                $html .= '<span class="material-symbols-outlined text-xl">play_arrow</span>';
                $html .= '</button>';
                $html .= '</label>';
            }
        }

        // Custom upload option with progress indicator
        $uploadId = $name . '_custom';
        $html .= '<div class="mt-4 border-t border-slate-200 dark:border-slate-700 pt-4">';
        $html .= '<p class="text-sm font-medium text-slate-600 dark:text-slate-400 mb-2">Or upload your own track:</p>';
        $html .= '<div class="border-2 border-dashed border-slate-200 dark:border-slate-700 hover:border-primary rounded-xl p-4 text-center cursor-pointer transition-all" 
            id="' . $uploadId . '_dropzone" 
            onclick="document.getElementById(\'' . $uploadId . '\').click()">';
        $html .= '<div id="' . $uploadId . '_placeholder" class="flex flex-col items-center gap-2">';
        $html .= '<span class="material-symbols-outlined text-3xl text-slate-400">music_note</span>';
        $html .= '<p class="text-sm font-medium">Click to upload MP3</p>';
        $html .= '<p class="text-xs text-slate-500">Max 20MB</p>';
        $html .= '</div>';
        $html .= '<div id="' . $uploadId . '_selected" class="hidden items-center justify-between gap-3">';
        $html .= '<div class="flex items-center gap-3">';
        $html .= '<span class="material-symbols-outlined text-2xl text-primary">audio_file</span>';
        $html .= '<div class="text-left">';
        $html .= '<p id="' . $uploadId . '_filename" class="text-sm font-medium text-slate-800 dark:text-slate-200"></p>';
        $html .= '<p id="' . $uploadId . '_size" class="text-xs text-slate-500"></p>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '<button type="button" class="p-2 rounded-full hover:bg-red-100 text-red-500 transition-colors" onclick="event.stopPropagation(); clearMusicFile(\'' . $uploadId . '\')">';
        $html .= '<span class="material-symbols-outlined">close</span>';
        $html .= '</button>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '<input type="file" id="' . $uploadId . '" name="' . $name . '_custom" accept="audio/mpeg,audio/mp3" class="hidden" onchange="handleMusicSelect(this, \'' . $uploadId . '\')">';
        $html .= '</div>';

        // Add JavaScript for music file handling (inline for simplicity)
        $html .= '<script>
            function handleMusicSelect(input, uploadId) {
                const file = input.files[0];
                if (!file) return;
                
                // Validate file size (20MB)
                if (file.size > 20 * 1024 * 1024) {
                    alert("File size must be less than 20MB");
                    input.value = "";
                    return;
                }
                
                // Show selected file info
                document.getElementById(uploadId + "_placeholder").classList.add("hidden");
                document.getElementById(uploadId + "_selected").classList.remove("hidden");
                document.getElementById(uploadId + "_selected").classList.add("flex");
                document.getElementById(uploadId + "_filename").textContent = file.name;
                document.getElementById(uploadId + "_size").textContent = (file.size / (1024 * 1024)).toFixed(2) + " MB";
                document.getElementById(uploadId + "_dropzone").classList.add("border-primary", "bg-primary/5");
            }
            
            function clearMusicFile(uploadId) {
                document.getElementById(uploadId).value = "";
                document.getElementById(uploadId + "_placeholder").classList.remove("hidden");
                document.getElementById(uploadId + "_selected").classList.add("hidden");
                document.getElementById(uploadId + "_selected").classList.remove("flex");
                document.getElementById(uploadId + "_dropzone").classList.remove("border-primary", "bg-primary/5");
            }
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
