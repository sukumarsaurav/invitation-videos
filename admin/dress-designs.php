<?php
/**
 * Admin - Dress Designs Management
 * Manage dress designs, colors, and AI prompts for caricature generation
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/Core/Security.php';
require_once __DIR__ . '/../src/Services/DressDesignService.php';

use InvitationVideos\Services\DressDesignService;

$pageTitle = 'Dress Designs';
$pendingTickets = 0;

$dressService = new DressDesignService();

// Handle actions
$action = $_GET['action'] ?? 'list';
$dressId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$colorId = isset($_GET['color_id']) ? intval($_GET['color_id']) : 0;
$message = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token';
    } else {
        $postAction = $_POST['action'] ?? '';
        
        try {
            switch ($postAction) {
                case 'create_design':
                case 'update_design':
                    $data = [
                        'name' => Security::sanitizeString($_POST['name'] ?? ''),
                        'description' => Security::sanitizeString($_POST['description'] ?? ''),
                        'thumbnail_url' => Security::sanitizeString($_POST['thumbnail_url'] ?? ''),
                        'category' => $_POST['category'] ?? 'wedding',
                        'gender' => $_POST['gender'] ?? 'couple',
                        'display_order' => intval($_POST['display_order'] ?? 0),
                        'is_active' => isset($_POST['is_active']) ? 1 : 0
                    ];
                    
                    if (empty($data['name'])) {
                        $error = 'Name is required';
                    } else {
                        if ($postAction === 'create_design') {
                            $newId = $dressService->createDesign($data);
                            $message = 'Dress design created successfully';
                            header('Location: /admin/dress-designs.php?action=edit&id=' . $newId . '&message=' . urlencode($message));
                            exit;
                        } else {
                            $id = intval($_POST['id'] ?? 0);
                            $dressService->updateDesign($id, $data);
                            $message = 'Dress design updated successfully';
                        }
                    }
                    break;
                    
                case 'delete_design':
                    $id = intval($_POST['id'] ?? 0);
                    $dressService->deleteDesign($id);
                    header('Location: /admin/dress-designs.php?message=' . urlencode('Dress design deleted'));
                    exit;
                    
                case 'create_color':
                case 'update_color':
                    $colorData = [
                        'name' => Security::sanitizeString($_POST['color_name'] ?? ''),
                        'hex_code' => $_POST['hex_code'] ?? '#000000',
                        'thumbnail_url' => Security::sanitizeString($_POST['color_thumbnail'] ?? ''),
                        'display_order' => intval($_POST['color_order'] ?? 0),
                        'is_active' => isset($_POST['color_active']) ? 1 : 0
                    ];
                    
                    if (empty($colorData['name'])) {
                        $error = 'Color name is required';
                    } else {
                        if ($postAction === 'create_color') {
                            $dressService->addColor($dressId, $colorData);
                            $message = 'Color added successfully';
                        } else {
                            $cId = intval($_POST['color_id'] ?? 0);
                            $dressService->updateColor($cId, $colorData);
                            $message = 'Color updated successfully';
                        }
                    }
                    break;
                    
                case 'delete_color':
                    $cId = intval($_POST['color_id'] ?? 0);
                    $dressService->deleteColor($cId);
                    $message = 'Color deleted';
                    break;
                    
                case 'save_prompt':
                    $promptDressId = intval($_POST['dress_id'] ?? 0);
                    $promptColorId = !empty($_POST['prompt_color_id']) ? intval($_POST['prompt_color_id']) : null;
                    $promptText = Security::sanitizeString($_POST['prompt_text'] ?? '');
                    $negativePrompt = Security::sanitizeString($_POST['negative_prompt'] ?? '');
                    
                    if (empty($promptText)) {
                        $error = 'Prompt text is required';
                    } else {
                        $dressService->setPrompt($promptDressId, $promptColorId, $promptText, $negativePrompt);
                        $message = 'Prompt saved successfully';
                    }
                    break;
                    
                case 'delete_prompt':
                    $promptId = intval($_POST['prompt_id'] ?? 0);
                    $dressService->deletePrompt($promptId);
                    $message = 'Prompt deleted';
                    break;
            }
        } catch (Exception $e) {
            $error = 'Error: ' . $e->getMessage();
        }
    }
}

// Get message from redirect
if (isset($_GET['message'])) {
    $message = Security::escape($_GET['message']);
}

// Fetch data based on action
$designs = $dressService->getAllDesigns(false);
$design = null;
$colors = [];
$prompts = [];

if ($dressId && in_array($action, ['edit', 'colors', 'prompts'])) {
    $design = $dressService->getDesignById($dressId);
    $colors = $dressService->getColorsForDress($dressId, false);
    $prompts = $dressService->getPromptsForDress($dressId);
}

$categories = ['wedding', 'birthday', 'anniversary', 'engagement', 'corporate', 'general'];
$genderOptions = ['couple' => 'Couple', 'male' => 'Male Only', 'female' => 'Female Only'];
?>

<?php ob_start(); ?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h1 class="text-2xl font-bold">
                <?php if ($action === 'new'): ?>
                    Add Dress Design
                <?php elseif ($action === 'edit' && $design): ?>
                    Edit: <?= Security::escape($design['name']) ?>
                <?php else: ?>
                    Dress Designs
                <?php endif; ?>
            </h1>
            <p class="text-slate-500">Manage outfit styles for AI caricature generation</p>
        </div>
        <?php if ($action === 'list'): ?>
            <a href="/admin/dress-designs.php?action=new" class="btn-primary">
                <span class="material-symbols-outlined text-lg">add</span>
                Add Design
            </a>
        <?php else: ?>
            <a href="/admin/dress-designs.php" class="px-4 py-2 border rounded-lg hover:bg-slate-50 flex items-center gap-2">
                <span class="material-symbols-outlined">arrow_back</span>
                Back to List
            </a>
        <?php endif; ?>
    </div>

    <?php if ($message): ?>
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg flex items-center gap-2">
            <span class="material-symbols-outlined">check_circle</span>
            <?= $message ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg flex items-center gap-2">
            <span class="material-symbols-outlined">error</span>
            <?= $error ?>
        </div>
    <?php endif; ?>

    <?php if ($action === 'new' || ($action === 'edit' && $design)): ?>
        <!-- Tabs for Edit Mode -->
        <?php if ($action === 'edit'): ?>
            <div class="border-b border-slate-200 dark:border-slate-700">
                <nav class="flex gap-4">
                    <a href="/admin/dress-designs.php?action=edit&id=<?= $dressId ?>" 
                       class="py-3 px-1 border-b-2 <?= !isset($_GET['tab']) || $_GET['tab'] === 'details' ? 'border-primary text-primary font-medium' : 'border-transparent text-slate-500 hover:text-slate-700' ?>">
                        Details
                    </a>
                    <a href="/admin/dress-designs.php?action=edit&id=<?= $dressId ?>&tab=colors" 
                       class="py-3 px-1 border-b-2 <?= ($_GET['tab'] ?? '') === 'colors' ? 'border-primary text-primary font-medium' : 'border-transparent text-slate-500 hover:text-slate-700' ?>">
                        Colors (<?= count($colors) ?>)
                    </a>
                    <a href="/admin/dress-designs.php?action=edit&id=<?= $dressId ?>&tab=prompts" 
                       class="py-3 px-1 border-b-2 <?= ($_GET['tab'] ?? '') === 'prompts' ? 'border-primary text-primary font-medium' : 'border-transparent text-slate-500 hover:text-slate-700' ?>">
                        AI Prompts (<?= count($prompts) ?>)
                    </a>
                </nav>
            </div>
        <?php endif; ?>

        <?php $currentTab = $_GET['tab'] ?? 'details'; ?>

        <?php if ($currentTab === 'details' || $action === 'new'): ?>
            <!-- Design Details Form -->
            <div class="bg-white dark:bg-surface-dark rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
                <form method="POST" class="space-y-4">
                    <input type="hidden" name="csrf_token" value="<?= Security::generateCSRFToken() ?>">
                    <input type="hidden" name="action" value="<?= $design ? 'update_design' : 'create_design' ?>">
                    <?php if ($design): ?>
                        <input type="hidden" name="id" value="<?= $design['id'] ?>">
                    <?php endif; ?>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-1">Design Name *</label>
                            <input type="text" name="name" value="<?= Security::escape($design['name'] ?? '') ?>" 
                                class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-primary" required
                                placeholder="e.g., Traditional Sherwani & Lehenga">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Category</label>
                            <select name="category" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-primary">
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat ?>" <?= ($design['category'] ?? 'wedding') === $cat ? 'selected' : '' ?>>
                                        <?= ucfirst($cat) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Gender</label>
                            <select name="gender" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-primary">
                                <?php foreach ($genderOptions as $val => $label): ?>
                                    <option value="<?= $val ?>" <?= ($design['gender'] ?? 'couple') === $val ? 'selected' : '' ?>>
                                        <?= $label ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Display Order</label>
                            <input type="number" name="display_order" value="<?= $design['display_order'] ?? 0 ?>" 
                                class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-primary">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium mb-1">Thumbnail URL</label>
                            <input type="text" name="thumbnail_url" value="<?= Security::escape($design['thumbnail_url'] ?? '') ?>" 
                                class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-primary"
                                placeholder="https://...">
                            <?php if (!empty($design['thumbnail_url'])): ?>
                                <img src="<?= Security::escape($design['thumbnail_url']) ?>" alt="Preview" 
                                    class="mt-2 w-24 h-24 object-cover rounded-lg border">
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium mb-1">Description</label>
                        <textarea name="description" rows="2" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-primary"
                            placeholder="Brief description for admin reference"><?= Security::escape($design['description'] ?? '') ?></textarea>
                    </div>

                    <div class="flex items-center gap-2">
                        <input type="checkbox" name="is_active" id="is_active" <?= ($design['is_active'] ?? 1) ? 'checked' : '' ?>>
                        <label for="is_active" class="text-sm font-medium">Active</label>
                    </div>

                    <div class="flex gap-3 pt-4 border-t">
                        <button type="submit" class="btn-primary">
                            <?= $design ? 'Update Design' : 'Create Design' ?>
                        </button>
                        <a href="/admin/dress-designs.php" class="px-4 py-2 border rounded-lg hover:bg-slate-50">Cancel</a>
                    </div>
                </form>
            </div>

        <?php elseif ($currentTab === 'colors'): ?>
            <!-- Colors Management -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Color List -->
                <div class="bg-white dark:bg-surface-dark rounded-xl shadow-sm border border-slate-200 dark:border-slate-700">
                    <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                        <h2 class="font-bold">Colors for <?= Security::escape($design['name']) ?></h2>
                    </div>
                    <div class="divide-y divide-slate-200 dark:divide-slate-700">
                        <?php if (empty($colors)): ?>
                            <div class="p-6 text-center text-slate-500">
                                <span class="material-symbols-outlined text-3xl mb-2">palette</span>
                                <p>No colors added yet</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($colors as $color): ?>
                                <div class="flex items-center justify-between px-6 py-4 hover:bg-slate-50">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full border-2 border-slate-300" 
                                            style="background-color: <?= Security::escape($color['hex_code']) ?>"></div>
                                        <div>
                                            <p class="font-medium"><?= Security::escape($color['name']) ?></p>
                                            <p class="text-sm text-slate-500"><?= Security::escape($color['hex_code']) ?></p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <?php if (!$color['is_active']): ?>
                                            <span class="text-xs bg-slate-100 text-slate-500 px-2 py-1 rounded">Inactive</span>
                                        <?php endif; ?>
                                        <button type="button" onclick="editColor(<?= htmlspecialchars(json_encode($color)) ?>)" 
                                            class="p-2 hover:bg-slate-100 rounded-lg" title="Edit">
                                            <span class="material-symbols-outlined text-slate-500">edit</span>
                                        </button>
                                        <form method="POST" class="inline" onsubmit="return confirm('Delete this color?')">
                                            <input type="hidden" name="csrf_token" value="<?= Security::generateCSRFToken() ?>">
                                            <input type="hidden" name="action" value="delete_color">
                                            <input type="hidden" name="color_id" value="<?= $color['id'] ?>">
                                            <button type="submit" class="p-2 hover:bg-red-50 rounded-lg" title="Delete">
                                                <span class="material-symbols-outlined text-red-500">delete</span>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Add/Edit Color Form -->
                <div class="bg-white dark:bg-surface-dark rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
                    <h2 class="font-bold mb-4" id="color-form-title">Add New Color</h2>
                    <form method="POST" class="space-y-4" id="color-form">
                        <input type="hidden" name="csrf_token" value="<?= Security::generateCSRFToken() ?>">
                        <input type="hidden" name="action" value="create_color" id="color-form-action">
                        <input type="hidden" name="color_id" value="" id="color-form-id">

                        <div>
                            <label class="block text-sm font-medium mb-1">Color Name *</label>
                            <input type="text" name="color_name" id="color-name" 
                                class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-primary" required
                                placeholder="e.g., Royal Red">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Hex Code</label>
                            <div class="flex gap-2">
                                <input type="color" name="hex_code" id="color-hex" value="#B22222" 
                                    class="h-10 w-16 rounded border cursor-pointer">
                                <input type="text" id="color-hex-text" value="#B22222" 
                                    class="flex-1 px-3 py-2 border rounded-lg" pattern="^#[0-9A-Fa-f]{6}$"
                                    onchange="document.getElementById('color-hex').value = this.value">
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Thumbnail URL (optional)</label>
                            <input type="text" name="color_thumbnail" id="color-thumbnail" 
                                class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-primary"
                                placeholder="https://...">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Display Order</label>
                            <input type="number" name="color_order" id="color-order" value="0" 
                                class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-primary">
                        </div>
                        <div class="flex items-center gap-2">
                            <input type="checkbox" name="color_active" id="color-active" checked>
                            <label for="color-active" class="text-sm font-medium">Active</label>
                        </div>

                        <div class="flex gap-3">
                            <button type="submit" class="btn-primary">Save Color</button>
                            <button type="button" onclick="resetColorForm()" class="px-4 py-2 border rounded-lg hover:bg-slate-50">Reset</button>
                        </div>
                    </form>
                </div>
            </div>

            <script>
            function editColor(color) {
                document.getElementById('color-form-title').textContent = 'Edit Color: ' + color.name;
                document.getElementById('color-form-action').value = 'update_color';
                document.getElementById('color-form-id').value = color.id;
                document.getElementById('color-name').value = color.name;
                document.getElementById('color-hex').value = color.hex_code;
                document.getElementById('color-hex-text').value = color.hex_code;
                document.getElementById('color-thumbnail').value = color.thumbnail_url || '';
                document.getElementById('color-order').value = color.display_order;
                document.getElementById('color-active').checked = color.is_active == 1;
            }
            function resetColorForm() {
                document.getElementById('color-form-title').textContent = 'Add New Color';
                document.getElementById('color-form-action').value = 'create_color';
                document.getElementById('color-form-id').value = '';
                document.getElementById('color-form').reset();
                document.getElementById('color-hex').value = '#B22222';
                document.getElementById('color-hex-text').value = '#B22222';
            }
            document.getElementById('color-hex').addEventListener('change', function() {
                document.getElementById('color-hex-text').value = this.value;
            });
            </script>

        <?php elseif ($currentTab === 'prompts'): ?>
            <!-- AI Prompts Management -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Prompt List -->
                <div class="bg-white dark:bg-surface-dark rounded-xl shadow-sm border border-slate-200 dark:border-slate-700">
                    <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                        <h2 class="font-bold">AI Prompts</h2>
                        <p class="text-sm text-slate-500">Prompts sent to AI for image generation</p>
                    </div>
                    <div class="divide-y divide-slate-200 dark:divide-slate-700">
                        <?php if (empty($prompts)): ?>
                            <div class="p-6 text-center text-slate-500">
                                <span class="material-symbols-outlined text-3xl mb-2">auto_awesome</span>
                                <p>No prompts added yet</p>
                                <p class="text-sm">Add a default prompt for this design</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($prompts as $prompt): ?>
                                <div class="px-6 py-4 hover:bg-slate-50">
                                    <div class="flex items-start justify-between gap-4">
                                        <div class="flex-1">
                                            <div class="flex items-center gap-2 mb-1">
                                                <?php if ($prompt['color_id']): ?>
                                                    <div class="w-4 h-4 rounded-full border" 
                                                        style="background-color: <?= Security::escape($prompt['hex_code'] ?? '#000') ?>"></div>
                                                    <span class="font-medium"><?= Security::escape($prompt['color_name']) ?></span>
                                                <?php else: ?>
                                                    <span class="font-medium text-primary">Default Prompt</span>
                                                <?php endif; ?>
                                            </div>
                                            <p class="text-sm text-slate-600 line-clamp-2"><?= Security::escape($prompt['prompt_text']) ?></p>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <button type="button" onclick="editPrompt(<?= htmlspecialchars(json_encode($prompt)) ?>)" 
                                                class="p-2 hover:bg-slate-100 rounded-lg" title="Edit">
                                                <span class="material-symbols-outlined text-slate-500">edit</span>
                                            </button>
                                            <form method="POST" class="inline" onsubmit="return confirm('Delete this prompt?')">
                                                <input type="hidden" name="csrf_token" value="<?= Security::generateCSRFToken() ?>">
                                                <input type="hidden" name="action" value="delete_prompt">
                                                <input type="hidden" name="prompt_id" value="<?= $prompt['id'] ?>">
                                                <button type="submit" class="p-2 hover:bg-red-50 rounded-lg" title="Delete">
                                                    <span class="material-symbols-outlined text-red-500">delete</span>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Add/Edit Prompt Form -->
                <div class="bg-white dark:bg-surface-dark rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
                    <h2 class="font-bold mb-4" id="prompt-form-title">Add AI Prompt</h2>
                    <form method="POST" class="space-y-4" id="prompt-form">
                        <input type="hidden" name="csrf_token" value="<?= Security::generateCSRFToken() ?>">
                        <input type="hidden" name="action" value="save_prompt">
                        <input type="hidden" name="dress_id" value="<?= $dressId ?>">

                        <div>
                            <label class="block text-sm font-medium mb-1">For Color</label>
                            <select name="prompt_color_id" id="prompt-color" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-primary">
                                <option value="">Default (Any Color)</option>
                                <?php foreach ($colors as $color): ?>
                                    <option value="<?= $color['id'] ?>"><?= Security::escape($color['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <p class="text-xs text-slate-500 mt-1">Leave as "Default" to use this prompt for all colors without specific prompts</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Prompt Text *</label>
                            <textarea name="prompt_text" id="prompt-text" rows="5" required
                                class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-primary"
                                placeholder="Describe the outfit in detail. E.g., 'The groom wearing a royal red sherwani with gold embroidery and matching turban. The bride in a stunning red lehenga with gold zari work, wearing traditional jewelry including maang tikka and jhumkas.'"></textarea>
                            <p class="text-xs text-slate-500 mt-1">This will be combined with caricature style instructions</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Negative Prompt (optional)</label>
                            <textarea name="negative_prompt" id="prompt-negative" rows="2"
                                class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-primary"
                                placeholder="What to avoid: e.g., 'realistic, photorealistic, ugly, deformed'"></textarea>
                        </div>

                        <div class="flex gap-3">
                            <button type="submit" class="btn-primary">Save Prompt</button>
                            <button type="button" onclick="resetPromptForm()" class="px-4 py-2 border rounded-lg hover:bg-slate-50">Reset</button>
                        </div>
                    </form>

                    <div class="mt-6 p-4 bg-slate-50 rounded-lg">
                        <h3 class="font-medium text-sm mb-2">💡 Prompt Tips</h3>
                        <ul class="text-sm text-slate-600 space-y-1">
                            <li>• Describe clothing colors, patterns, and embroidery</li>
                            <li>• Mention jewelry and accessories</li>
                            <li>• Include cultural-specific details</li>
                            <li>• The system adds caricature style instructions automatically</li>
                        </ul>
                    </div>
                </div>
            </div>

            <script>
            function editPrompt(prompt) {
                document.getElementById('prompt-form-title').textContent = 'Edit Prompt';
                document.getElementById('prompt-color').value = prompt.color_id || '';
                document.getElementById('prompt-text').value = prompt.prompt_text;
                document.getElementById('prompt-negative').value = prompt.negative_prompt || '';
            }
            function resetPromptForm() {
                document.getElementById('prompt-form-title').textContent = 'Add AI Prompt';
                document.getElementById('prompt-form').reset();
            }
            </script>
        <?php endif; ?>

    <?php else: ?>
        <!-- Designs List -->
        <div class="bg-white dark:bg-surface-dark rounded-xl shadow-sm border border-slate-200 dark:border-slate-700">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-white/5">
                            <th class="text-left px-6 py-3 text-sm font-medium text-slate-500">Design</th>
                            <th class="text-left px-6 py-3 text-sm font-medium text-slate-500">Category</th>
                            <th class="text-center px-6 py-3 text-sm font-medium text-slate-500">Colors</th>
                            <th class="text-center px-6 py-3 text-sm font-medium text-slate-500">Templates</th>
                            <th class="text-center px-6 py-3 text-sm font-medium text-slate-500">Status</th>
                            <th class="text-right px-6 py-3 text-sm font-medium text-slate-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                        <?php if (empty($designs)): ?>
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-slate-500">
                                    <span class="material-symbols-outlined text-4xl mb-2">checkroom</span>
                                    <p>No dress designs yet. <a href="/admin/dress-designs.php?action=new" class="text-primary">Create one</a></p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($designs as $d): ?>
                                <tr class="hover:bg-slate-50 dark:hover:bg-white/5">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <?php if ($d['thumbnail_url']): ?>
                                                <img src="<?= Security::escape($d['thumbnail_url']) ?>" alt="" 
                                                    class="w-12 h-12 object-cover rounded-lg border">
                                            <?php else: ?>
                                                <div class="w-12 h-12 bg-slate-100 rounded-lg flex items-center justify-center">
                                                    <span class="material-symbols-outlined text-slate-400">checkroom</span>
                                                </div>
                                            <?php endif; ?>
                                            <div>
                                                <p class="font-medium"><?= Security::escape($d['name']) ?></p>
                                                <p class="text-sm text-slate-500"><?= ucfirst($d['gender']) ?></p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="px-2 py-1 bg-slate-100 text-slate-600 text-sm rounded">
                                            <?= ucfirst($d['category']) ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="font-medium"><?= $d['color_count'] ?? 0 ?></span>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="font-medium"><?= $d['template_count'] ?? 0 ?></span>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <?php if ($d['is_active']): ?>
                                            <span class="px-2 py-1 bg-green-100 text-green-700 text-sm rounded-full">Active</span>
                                        <?php else: ?>
                                            <span class="px-2 py-1 bg-slate-100 text-slate-500 text-sm rounded-full">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center justify-end gap-2">
                                            <a href="/admin/dress-designs.php?action=edit&id=<?= $d['id'] ?>" 
                                                class="p-2 hover:bg-slate-100 rounded-lg" title="Edit">
                                                <span class="material-symbols-outlined text-slate-500">edit</span>
                                            </a>
                                            <a href="/admin/dress-designs.php?action=edit&id=<?= $d['id'] ?>&tab=colors" 
                                                class="p-2 hover:bg-slate-100 rounded-lg" title="Colors">
                                                <span class="material-symbols-outlined text-slate-500">palette</span>
                                            </a>
                                            <a href="/admin/dress-designs.php?action=edit&id=<?= $d['id'] ?>&tab=prompts" 
                                                class="p-2 hover:bg-slate-100 rounded-lg" title="Prompts">
                                                <span class="material-symbols-outlined text-slate-500">auto_awesome</span>
                                            </a>
                                            <form method="POST" class="inline" onsubmit="return confirm('Delete this design? This will also delete all colors and prompts.')">
                                                <input type="hidden" name="csrf_token" value="<?= Security::generateCSRFToken() ?>">
                                                <input type="hidden" name="action" value="delete_design">
                                                <input type="hidden" name="id" value="<?= $d['id'] ?>">
                                                <button type="submit" class="p-2 hover:bg-red-50 rounded-lg" title="Delete">
                                                    <span class="material-symbols-outlined text-red-500">delete</span>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
.btn-primary {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.625rem 1.25rem;
    background: #7f13ec;
    color: white;
    font-weight: 600;
    border-radius: 0.5rem;
    transition: all 0.2s;
}
.btn-primary:hover { background: #6b0fcc; }
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>

<?php
$content = ob_get_clean();
include __DIR__ . '/layouts/admin.php';
?>
