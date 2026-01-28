<?php
/**
 * Form Field Components
 * 
 * Reusable form field component for static forms (contact, profile, register, etc.)
 * For dynamic template customization forms, use src/Form/DynamicFormRenderer.php
 * 
 * @package InvitationVideos
 */

require_once __DIR__ . '/../../src/Core/Security.php';

/**
 * Render a text input field
 * 
 * @param string $name Field name attribute
 * @param string $label Field label
 * @param array $options Optional configuration:
 *   - type: string - Input type (text, email, tel, password, number) (default: 'text')
 *   - value: string - Current value (default: '')
 *   - placeholder: string - Placeholder text (default: '')
 *   - required: bool - Whether field is required (default: false)
 *   - disabled: bool - Whether field is disabled (default: false)
 *   - error: string - Error message to display (default: '')
 *   - helpText: string - Help text below field (default: '')
 *   - autocomplete: string - Autocomplete attribute (default: '')
 * @return string HTML output
 */
function renderFormInput(string $name, string $label, array $options = []): string
{
    $defaults = [
        'type' => 'text',
        'value' => '',
        'placeholder' => '',
        'required' => false,
        'disabled' => false,
        'error' => '',
        'helpText' => '',
        'autocomplete' => '',
    ];
    $opts = array_merge($defaults, $options);

    $inputId = Security::escape($name);
    $labelText = Security::escape($label);
    $value = Security::escape($opts['value']);
    $placeholder = Security::escape($opts['placeholder']);
    $autocomplete = $opts['autocomplete'] ? 'autocomplete="' . Security::escape($opts['autocomplete']) . '"' : '';
    $required = $opts['required'] ? 'required' : '';
    $disabled = $opts['disabled'] ? 'disabled' : '';

    $baseClass = 'w-full h-12 px-4 rounded-xl border bg-white focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all';
    $borderClass = $opts['error'] ? 'border-red-300' : 'border-slate-200';
    $disabledClass = $opts['disabled'] ? 'bg-slate-100 text-slate-500 cursor-not-allowed' : '';

    ob_start();
    ?>
    <div class="form-field">
        <label for="<?= $inputId ?>" class="block text-sm font-medium text-slate-700 mb-2">
            <?= $labelText ?>
            <?php if ($opts['required']): ?>
                <span class="text-red-500">*</span>
            <?php endif; ?>
        </label>
        <input type="<?= Security::escape($opts['type']) ?>" id="<?= $inputId ?>" name="<?= $inputId ?>"
            value="<?= $value ?>" placeholder="<?= $placeholder ?>"
            class="<?= $baseClass ?> <?= $borderClass ?> <?= $disabledClass ?>" <?= $autocomplete ?>
        <?= $required ?>
        <?= $disabled ?>
        >
        <?php if ($opts['error']): ?>
            <p class="text-xs text-red-500 mt-1">
                <?= Security::escape($opts['error']) ?>
            </p>
        <?php elseif ($opts['helpText']): ?>
            <p class="text-xs text-slate-500 mt-1">
                <?= Security::escape($opts['helpText']) ?>
            </p>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Render a textarea field
 * 
 * @param string $name Field name attribute
 * @param string $label Field label
 * @param array $options Optional configuration (same as renderFormInput, plus rows)
 * @return string HTML output
 */
function renderFormTextarea(string $name, string $label, array $options = []): string
{
    $defaults = [
        'value' => '',
        'placeholder' => '',
        'required' => false,
        'rows' => 4,
        'error' => '',
        'helpText' => '',
    ];
    $opts = array_merge($defaults, $options);

    $inputId = Security::escape($name);
    $labelText = Security::escape($label);
    $value = Security::escape($opts['value']);
    $placeholder = Security::escape($opts['placeholder']);
    $required = $opts['required'] ? 'required' : '';

    $baseClass = 'w-full p-4 rounded-xl border bg-white focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all resize-y';
    $borderClass = $opts['error'] ? 'border-red-300' : 'border-slate-200';

    ob_start();
    ?>
    <div class="form-field">
        <label for="<?= $inputId ?>" class="block text-sm font-medium text-slate-700 mb-2">
            <?= $labelText ?>
            <?php if ($opts['required']): ?>
                <span class="text-red-500">*</span>
            <?php endif; ?>
        </label>
        <textarea id="<?= $inputId ?>" name="<?= $inputId ?>" rows="<?= (int) $opts['rows'] ?>"
            placeholder="<?= $placeholder ?>" class="<?= $baseClass ?> <?= $borderClass ?>" <?= $required ?>
            ><?= $value ?></textarea>
        <?php if ($opts['error']): ?>
            <p class="text-xs text-red-500 mt-1">
                <?= Security::escape($opts['error']) ?>
            </p>
        <?php elseif ($opts['helpText']): ?>
            <p class="text-xs text-slate-500 mt-1">
                <?= Security::escape($opts['helpText']) ?>
            </p>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Render a select dropdown field
 * 
 * @param string $name Field name attribute
 * @param string $label Field label
 * @param array $options Field options as value => label pairs
 * @param array $config Optional configuration
 * @return string HTML output
 */
function renderFormSelect(string $name, string $label, array $selectOptions, array $config = []): string
{
    $defaults = [
        'value' => '',
        'required' => false,
        'placeholder' => 'Select...',
        'error' => '',
        'helpText' => '',
    ];
    $opts = array_merge($defaults, $config);

    $inputId = Security::escape($name);
    $labelText = Security::escape($label);
    $required = $opts['required'] ? 'required' : '';

    $baseClass = 'w-full h-12 px-4 rounded-xl border bg-white focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all';
    $borderClass = $opts['error'] ? 'border-red-300' : 'border-slate-200';

    ob_start();
    ?>
    <div class="form-field">
        <label for="<?= $inputId ?>" class="block text-sm font-medium text-slate-700 mb-2">
            <?= $labelText ?>
            <?php if ($opts['required']): ?>
                <span class="text-red-500">*</span>
            <?php endif; ?>
        </label>
        <select id="<?= $inputId ?>" name="<?= $inputId ?>" class="<?= $baseClass ?> <?= $borderClass ?>" <?= $required ?>
            >
            <option value="">
                <?= Security::escape($opts['placeholder']) ?>
            </option>
            <?php foreach ($selectOptions as $value => $optLabel): ?>
                <option value="<?= Security::escape($value) ?>" <?= $opts['value'] === $value ? 'selected' : '' ?>>
                    <?= Security::escape($optLabel) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php if ($opts['error']): ?>
            <p class="text-xs text-red-500 mt-1">
                <?= Security::escape($opts['error']) ?>
            </p>
        <?php elseif ($opts['helpText']): ?>
            <p class="text-xs text-slate-500 mt-1">
                <?= Security::escape($opts['helpText']) ?>
            </p>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Render a submit button
 * 
 * @param string $label Button label
 * @param array $options Optional configuration:
 *   - variant: string - 'primary' | 'secondary' (default: 'primary')
 *   - fullWidth: bool - Whether button should be full width (default: false)
 *   - disabled: bool - Whether button is disabled (default: false)
 *   - icon: string - Material Symbols icon name (default: '')
 * @return string HTML output
 */
function renderFormButton(string $label, array $options = []): string
{
    $defaults = [
        'variant' => 'primary',
        'fullWidth' => false,
        'disabled' => false,
        'icon' => '',
    ];
    $opts = array_merge($defaults, $options);

    $baseClass = 'px-6 py-3 rounded-xl font-bold transition-all';
    $widthClass = $opts['fullWidth'] ? 'w-full' : '';
    $disabledClass = $opts['disabled'] ? 'opacity-50 cursor-not-allowed' : '';

    $variantClasses = [
        'primary' => 'bg-primary text-white hover:bg-primary/90 shadow-lg shadow-primary/30',
        'secondary' => 'bg-white text-slate-700 border border-slate-200 hover:bg-slate-50',
    ];
    $variantClass = $variantClasses[$opts['variant']] ?? $variantClasses['primary'];

    ob_start();
    ?>
    <button type="submit"
        class="<?= $baseClass ?> <?= $variantClass ?> <?= $widthClass ?> <?= $disabledClass ?> flex items-center justify-center gap-2"
        <?= $opts['disabled'] ? 'disabled' : '' ?>
        >
        <?php if ($opts['icon']): ?>
            <span class="material-symbols-outlined">
                <?= Security::escape($opts['icon']) ?>
            </span>
        <?php endif; ?>
        <?= Security::escape($label) ?>
    </button>
    <?php
    return ob_get_clean();
}
