<?php
/**
 * Dress Selection Component
 * 
 * Renders the dress/color selection UI for AI caricature templates.
 * Include this in customize.php for templates with ai_caricature_enabled = 1
 * 
 * Required variables:
 * - $templateId: int
 * - $dressDesigns: array (from DressDesignService->getDesignsForTemplate)
 * - $selectedDress: int|null (from session)
 * - $selectedColor: int|null (from session)
 */

if (empty($dressDesigns)) {
    return;
}

$selectedDressId = $_SESSION['customize_dress_id'] ?? null;
$selectedColorId = $_SESSION['customize_color_id'] ?? null;
?>

<div class="space-y-6" id="dress-selection">
    <!-- Header -->
    <div class="text-center">
        <h2 class="text-xl font-bold text-slate-900">Choose Your Outfit Style</h2>
        <p class="text-slate-500 mt-1">Select a dress design for your AI-generated caricature</p>
    </div>

    <!-- Dress Design Grid -->
    <div class="grid grid-cols-2 md:grid-cols-3 gap-4" id="dress-grid">
        <?php foreach ($dressDesigns as $dress): ?>
            <label class="dress-option cursor-pointer group">
                <input type="radio" name="dress_id" value="<?= $dress['id'] ?>" class="sr-only peer"
                    <?= $selectedDressId == $dress['id'] ? 'checked' : '' ?> data-dress-id="
            <?= $dress['id'] ?>" required>
                <div class="relative overflow-hidden rounded-xl border-2 transition-all
                    peer-checked:border-primary peer-checked:ring-2 peer-checked:ring-primary/20
                    border-slate-200 hover:border-primary/50
                    bg-white">

                    <!-- Dress Thumbnail -->
                    <?php if (!empty($dress['thumbnail_url'])): ?>
                        <div class="aspect-[3/4] bg-slate-100">
                            <img src="<?= Security::escape($dress['thumbnail_url']) ?>"
                                alt="<?= Security::escape($dress['name']) ?>" class="w-full h-full object-cover">
                        </div>
                    <?php else: ?>
                        <div
                            class="aspect-[3/4] bg-gradient-to-br from-slate-100 to-slate-200 flex items-center justify-center">
                            <span class="material-symbols-outlined text-4xl text-slate-400">checkroom</span>
                        </div>
                    <?php endif; ?>

                    <!-- Dress Name -->
                    <div class="p-3 text-center">
                        <p class="font-medium text-slate-900 text-sm">
                            <?= Security::escape($dress['name']) ?>
                        </p>
                        <?php if (!empty($dress['description'])): ?>
                            <p class="text-xs text-slate-500 mt-1 line-clamp-2">
                                <?= Security::escape($dress['description']) ?>
                            </p>
                        <?php endif; ?>
                    </div>

                    <!-- Selected Check -->
                    <div class="absolute top-2 right-2 size-6 rounded-full bg-primary text-white 
                        flex items-center justify-center opacity-0 peer-checked:opacity-100 transition-opacity">
                        <span class="material-symbols-outlined text-sm">check</span>
                    </div>
                </div>
            </label>
        <?php endforeach; ?>
    </div>

    <!-- Color Selection (dynamic based on dress) -->
    <div id="color-section" class="<?= $selectedDressId ? '' : 'hidden' ?>">
        <h3 class="font-bold text-slate-900 mb-3">Select Color</h3>

        <div id="color-loading" class="hidden animate-pulse">
            <div class="flex gap-3">
                <?php for ($i = 0; $i < 4; $i++): ?>
                    <div class="w-12 h-12 rounded-full bg-slate-200"></div>
                <?php endfor; ?>
            </div>
        </div>

        <div id="color-grid" class="flex flex-wrap gap-3">
            <!-- Colors will be loaded dynamically via JavaScript -->
            <?php if ($selectedDressId): ?>
                <?php
                require_once __DIR__ . '/../../src/Services/DressDesignService.php';
                $dressService = new \InvitationVideos\Services\DressDesignService();
                $colors = $dressService->getColorsForDress($selectedDressId, true);
                ?>
                <?php foreach ($colors as $color): ?>
                    <label class="color-option cursor-pointer group">
                        <input type="radio" name="color_id" value="<?= $color['id'] ?>" class="sr-only peer"
                            <?= $selectedColorId == $color['id'] ? 'checked' : '' ?>>
                        <div class="relative">
                            <div class="size-12 rounded-full border-4 transition-all
                                peer-checked:ring-2 peer-checked:ring-primary peer-checked:ring-offset-2
                                border-white shadow-md hover:scale-110"
                                style="background-color: <?= Security::escape($color['hex_code']) ?>"
                                title="<?= Security::escape($color['name']) ?>">
                            </div>
                            <div class="absolute -bottom-6 left-1/2 -translate-x-1/2 whitespace-nowrap 
                                text-xs text-slate-600 opacity-0 group-hover:opacity-100 transition-opacity">
                                <?= Security::escape($color['name']) ?>
                            </div>
                        </div>
                    </label>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div id="no-colors" class="hidden text-center py-4 text-slate-500">
            <p>No color options available for this design</p>
        </div>
    </div>

    <!-- AI Generation Info -->
    <div class="bg-gradient-to-r from-primary/5 to-purple-500/5 border border-primary/20 rounded-xl p-4">
        <div class="flex items-start gap-3">
            <div class="size-10 rounded-full bg-primary/10 flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-primary">auto_awesome</span>
            </div>
            <div>
                <p class="font-bold text-slate-900">AI-Generated Caricature</p>
                <p class="text-sm text-slate-600 mt-1">
                    After payment, our AI will create a beautiful cartoon caricature of you and your partner
                    wearing the selected outfit. This personalized illustration will be featured in your video!
                </p>
            </div>
        </div>
    </div>
</div>

<style>
    .dress-option input:checked+div {
        border-color: var(--color-primary, #970747);
        box-shadow: 0 0 0 3px rgba(127, 19, 236, 0.15);
    }

    .dress-option input:checked+div .check-icon {
        opacity: 1;
    }

    .color-option input:checked+div>div {
        transform: scale(1.1);
    }

    .line-clamp-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const dressInputs = document.querySelectorAll('input[name="dress_id"]');
        const colorSection = document.getElementById('color-section');
        const colorGrid = document.getElementById('color-grid');
        const colorLoading = document.getElementById('color-loading');
        const noColors = document.getElementById('no-colors');

        // Handle dress selection change
        dressInputs.forEach(input => {
            input.addEventListener('change', function () {
                const dressId = this.value;
                loadColorsForDress(dressId);
            });
        });

        // Load colors for a dress
        function loadColorsForDress(dressId) {
            colorSection.classList.remove('hidden');
            colorLoading.classList.remove('hidden');
            colorGrid.innerHTML = '';
            noColors.classList.add('hidden');

            fetch(`/api/dress-colors.php?dress_id=${dressId}`)
                .then(response => response.json())
                .then(data => {
                    colorLoading.classList.add('hidden');

                    if (data.success && data.colors.length > 0) {
                        renderColors(data.colors);
                    } else {
                        noColors.classList.remove('hidden');
                    }
                })
                .catch(error => {
                    colorLoading.classList.add('hidden');
                    noColors.classList.remove('hidden');
                    console.error('Failed to load colors:', error);
                });
        }

        // Render color options
        function renderColors(colors) {
            colorGrid.innerHTML = colors.map(color => `
            <label class="color-option cursor-pointer group">
                <input type="radio" name="color_id" value="${color.id}" class="sr-only peer">
                <div class="relative">
                    <div class="size-12 rounded-full border-4 transition-all
                        peer-checked:ring-2 peer-checked:ring-primary peer-checked:ring-offset-2
                        border-white shadow-md hover:scale-110"
                        style="background-color: ${color.hex_code}"
                        title="${color.name}">
                    </div>
                    <div class="absolute -bottom-6 left-1/2 -translate-x-1/2 whitespace-nowrap 
                        text-xs text-slate-600 opacity-0 group-hover:opacity-100 transition-opacity">
                        ${color.name}
                    </div>
                </div>
            </label>
        `).join('');

            // Auto-select first color
            const firstColor = colorGrid.querySelector('input[name="color_id"]');
            if (firstColor) {
                firstColor.checked = true;
            }
        }
    });
</script>