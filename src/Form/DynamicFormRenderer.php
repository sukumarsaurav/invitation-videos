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
     * Get all fields for a template, grouped by field_group
     */
    public function getFields(int $templateId): array
    {
        $sql = "SELECT * FROM template_fields 
                WHERE template_id = ? 
                ORDER BY field_group, display_order";

        $fields = Database::fetchAll($sql, [$templateId]);

        // Group fields
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
        $sql = "SELECT * FROM music_presets WHERE is_active = 1";
        return Database::fetchAll($sql);
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

        // Custom upload option
        $html .= '<div class="mt-2">';
        $html .= '<label class="flex items-center gap-2 text-primary font-medium cursor-pointer hover:underline">';
        $html .= '<span class="material-symbols-outlined text-lg">upload_file</span>';
        $html .= '<span>Upload your own track (MP3)</span>';
        $html .= '<input type="file" name="' . $name . '_custom" accept="audio/mpeg,audio/mp3" class="hidden">';
        $html .= '</label>';
        $html .= '</div>';

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
            "SELECT * FROM template_fields WHERE template_id = ?",
            [$templateId]
        );

        foreach ($fields as $field) {
            $name = $field['field_name'];
            $value = $data[$name] ?? null;

            // Check required fields
            if ($field['is_required']) {
                if ($field['field_type'] === 'image') {
                    if (empty($files[$name]['tmp_name'])) {
                        $errors[$name] = $field['field_label'] . ' is required';
                    }
                } elseif (empty($value)) {
                    $errors[$name] = $field['field_label'] . ' is required';
                }
            }

            // Validate based on rules
            if (!empty($field['validation_rules'])) {
                $rules = json_decode($field['validation_rules'], true);

                if (!empty($rules['max_length']) && strlen($value) > $rules['max_length']) {
                    $errors[$name] = $field['field_label'] . ' exceeds maximum length';
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
