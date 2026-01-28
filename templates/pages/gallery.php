<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../src/Core/Security.php';
require_once __DIR__ . '/../../src/Core/ImageHelper.php';

// Flag to hide footer on mobile (for sticky bottom bar)
$hideFooterOnMobile = true;

// Disable global bottom tabs (gallery has Sort/Filter bar)
$showMobileBottomTabs = false;

// Flag for gallery page - enables special mobile header
$isGalleryPage = true;
$currentCategory = $_GET['category'] ?? null;
$galleryCategories = [];
$galleryTotalTemplates = 0;

// Get filters - simplified to category + tradition + sort
$category = $_GET['category'] ?? null;
$tradition = $_GET['tradition'] ?? null;
$sort = $_GET['sort'] ?? 'popular';

// Initial page load - fetch first batch
$limit = 12;
$params = [];
$conditions = ["t.is_active = 1"];

// Category filter - check both direct category match and subcategory matches
if ($category) {
    // First check if this is a main category (has children) or a specific subcategory
    $categoryCheck = Database::fetchOne(
        "SELECT id, parent_id FROM categories WHERE slug = ? AND is_active = 1",
        [$category]
    );

    if ($categoryCheck) {
        if ($categoryCheck['parent_id'] === null) {
            // Main category - get all subcategory slugs too
            $subcategorySlugs = Database::fetchAll(
                "SELECT slug FROM categories WHERE parent_id = ? AND is_active = 1",
                [$categoryCheck['id']]
            );

            if (!empty($subcategorySlugs)) {
                $slugs = array_column($subcategorySlugs, 'slug');
                $slugs[] = $category; // Include main category too
                $placeholders = implode(',', array_fill(0, count($slugs), '?'));
                $conditions[] = "t.category IN ($placeholders)";
                $params = array_merge($params, $slugs);
            } else {
                $conditions[] = "t.category = ?";
                $params[] = $category;
            }
        } else {
            // Subcategory - exact match
            $conditions[] = "t.category = ?";
            $params[] = $category;
        }
    } else {
        // Category not found in new structure, try direct match
        $conditions[] = "t.category = ?";
        $params[] = $category;
    }
}

if ($tradition) {
    $conditions[] = "t.cultural_tradition = ?";
    $params[] = $tradition;
}

// Build the SQL query
$whereClause = implode(" AND ", $conditions);

$sql = "SELECT DISTINCT t.* FROM templates t WHERE {$whereClause}";
$countSql = "SELECT COUNT(DISTINCT t.id) as total FROM templates t WHERE {$whereClause}";




// Sort
switch ($sort) {
    case 'newest':
        $sql .= " ORDER BY created_at DESC";
        break;
    case 'price_low':
        $sql .= " ORDER BY price_usd ASC";
        break;
    case 'price_high':
        $sql .= " ORDER BY price_usd DESC";
        break;
    default:
        $sql .= " ORDER BY purchase_count DESC";
}

$sql .= " LIMIT $limit";
$templates = Database::fetchAll($sql, $params);
$totalResult = Database::fetchOne($countSql, $params);
$totalTemplates = intval($totalResult['total'] ?? 0);
$hasMore = $totalTemplates > $limit;

// Use categories from homepage-config.php (local images only, no CMS dependency)
require_once __DIR__ . '/../../config/homepage-config.php';

// Build allCategories from config
$allCategories = [];
foreach ($navCategories as $slug => $cat) {
    $allCategories[$slug] = [
        'name' => $cat['name'],
        'image' => $cat['image'],
    ];
}

// Pass categories to layout for mobile header
$galleryCategories = $allCategories;
$galleryTotalTemplates = $totalTemplates;

// Cultural traditions - fetch distinct values from database
$traditionsResult = Database::fetchAll(
    "SELECT DISTINCT cultural_tradition FROM templates 
     WHERE cultural_tradition IS NOT NULL AND cultural_tradition != '' AND is_active = 1 
     ORDER BY cultural_tradition"
);
$traditions = array_column($traditionsResult ?? [], 'cultural_tradition');

// Fallback if no traditions found
if (empty($traditions)) {
    $traditions = ['Hindu', 'Muslim', 'Christian', 'Sikh', 'Jewish', 'Chinese', 'Western'];
}

// Get subcategories for current category (for filter options)
$subcategoryFilters = [];
if ($category) {
    $categoryCheck = Database::fetchOne(
        "SELECT id, parent_id FROM categories WHERE slug = ? AND is_active = 1",
        [$category]
    );
    if ($categoryCheck && $categoryCheck['parent_id'] === null) {
        // Main category - get its subcategories
        $subcategoryFilters = Database::fetchAll(
            "SELECT name, slug FROM categories WHERE parent_id = ? AND is_active = 1 ORDER BY display_order",
            [$categoryCheck['id']]
        ) ?? [];
    }
}

// Sort options
$sortOptions = [
    'popular' => 'Most Popular',
    'newest' => 'Newest First',
    'price_low' => 'Price: Low to High',
    'price_high' => 'Price: High to Low',
];

// SEO
$categoryTitles = [
    'wedding' => 'Wedding Video Invitation Templates',
    'birthday' => 'Birthday Video Invitation Templates',
    'corporate' => 'Corporate Event Video Templates',
    'baby_shower' => 'Baby Shower Video Invitation Templates',
    'anniversary' => 'Anniversary Video Invitation Templates',
];

if ($category && isset($categoryTitles[$category])) {
    $pageTitle = $categoryTitles[$category];
    $metaDescription = "Browse our beautiful collection of {$categoryTitles[$category]}. Easy customization, professional quality.";
} else {
    $pageTitle = 'Video Invitation Templates - All Categories';
    $metaDescription = 'Browse our stunning collection of video invitation templates for weddings, birthdays, anniversaries.';
}
?>

<?php ob_start(); ?>

<div class="flex flex-1 justify-center w-full">
    <div class="flex w-full max-w-[1600px] flex-col lg:flex-row">

        <!-- Desktop Sidebar Filters (hidden on mobile) -->
        <aside
            class="hidden lg:block w-72 xl:w-80 lg:shrink-0 border-r border-slate-200 bg-white overflow-y-auto lg:h-[calc(100vh-65px)] lg:sticky lg:top-[65px]">
            <div class="flex flex-col h-full p-6">
                <!-- Categories -->
                <div class="py-4">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-3 px-1">Categories</h3>
                    <div class="space-y-0.5">
                        <a href="/templates"
                            class="w-full flex items-center gap-2 px-3 py-2 text-sm font-medium <?= !$category ? 'text-primary bg-primary/5' : 'text-slate-600 hover:text-primary' ?> rounded-lg">
                            <span class="material-symbols-outlined text-lg">grid_view</span>
                            All Templates
                        </a>
                        <?php foreach ($allCategories as $key => $cat): ?>
                            <a href="/templates?category=<?= $key ?>"
                                class="w-full flex items-center gap-2 px-3 py-2 text-sm font-medium <?= $category === $key ? 'text-primary bg-primary/5 font-bold' : 'text-slate-600 hover:text-primary' ?> rounded-lg">
                                <img src="<?= htmlspecialchars($cat['image']) ?>"
                                    alt="<?= htmlspecialchars($cat['name']) ?>" class="w-6 h-6 rounded object-cover">
                                <?= $cat['name'] ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="h-px bg-slate-200 my-2"></div>

                <!-- Cultural Traditions -->
                <div class="py-4">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-3 px-1">Cultural Traditions
                    </h3>
                    <div class="flex flex-wrap gap-2">
                        <?php foreach ($traditions as $t): ?>
                            <a href="/templates?tradition=<?= strtolower($t) ?><?= $category ? '&category=' . $category : '' ?>"
                                class="inline-flex items-center gap-1 rounded-full border px-3 py-1.5 text-xs font-bold transition-all 
                               <?= $tradition === strtolower($t) ? 'border-primary bg-primary text-white' : 'border-slate-200 bg-white text-slate-600 hover:border-primary hover:text-primary' ?>">
                                <?= $t ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <?php if (!empty($subcategoryFilters)): ?>
                    <div class="h-px bg-slate-200 my-2"></div>

                    <!-- Event Types (Subcategories) -->
                    <div class="py-4">
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-3 px-1">Event Types</h3>
                        <div class="flex flex-wrap gap-2">
                            <?php foreach ($subcategoryFilters as $subcat): ?>
                                <a href="/templates?category=<?= $subcat['slug'] ?><?= $tradition ? '&tradition=' . $tradition : '' ?>"
                                    class="inline-flex items-center gap-1 rounded-full border px-3 py-1.5 text-xs font-bold transition-all 
                               <?= ($category === $subcat['slug']) ? 'border-primary bg-primary text-white' : 'border-slate-200 bg-white text-slate-600 hover:border-primary hover:text-primary' ?>">
                                    <?= htmlspecialchars($subcat['name']) ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="h-px bg-slate-200 my-2"></div>

                <!-- Sort (Desktop) -->
                <div class="py-4">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-3 px-1">Sort By</h3>
                    <div class="space-y-1">
                        <?php foreach ($sortOptions as $key => $label): ?>
                            <a href="?<?= http_build_query(array_merge($_GET, ['sort' => $key])) ?>"
                                class="block px-3 py-2 text-sm rounded-lg <?= $sort === $key ? 'text-primary bg-primary/5 font-bold' : 'text-slate-600 hover:text-primary hover:bg-slate-50' ?>">
                                <?= $label ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 p-4 sm:p-6 lg:p-10 pb-24 sm:pb-6">
            <!-- Header -->
            <div class="mb-6">
                <!-- Breadcrumb - hidden on mobile, visible on desktop -->
                <nav class="hidden sm:flex items-center gap-2 text-sm mb-4">
                    <a class="text-slate-500 hover:text-primary transition-colors" href="/">Home</a>
                    <span class="text-slate-400">/</span>
                    <span class="font-medium text-slate-900">Templates</span>
                </nav>

                <!-- Header - hidden on mobile, visible on desktop -->
                <div class="hidden sm:flex flex-col sm:flex-row sm:items-end justify-between gap-4">
                    <div>
                        <h1 class="heading-hero text-slate-900 tracking-tight">
                            <?= $category && isset($allCategories[$category]) ? $allCategories[$category]['name'] : 'All' ?>
                            Templates
                        </h1>
                        <p class="text-slate-500 mt-1">
                            <span id="template-count"><?= $totalTemplates ?></span> templates found
                        </p>
                    </div>

                    <!-- Desktop Sort Dropdown -->
                    <div class="hidden sm:flex items-center gap-2">
                        <span class="text-sm text-slate-500">Sort:</span>
                        <select onchange="window.location.href=this.value"
                            class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium">
                            <?php foreach ($sortOptions as $key => $label): ?>
                                <option value="?<?= http_build_query(array_merge($_GET, ['sort' => $key])) ?>"
                                    <?= $sort === $key ? 'selected' : '' ?>>
                                    <?= $label ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Template Grid -->
            <div id="templates-grid"
                class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4 sm:gap-6">
                <?php foreach ($templates as $index => $template):
                    $isAboveFold = $index < 4;
                    ?>
                    <a href="/template/<?= Security::escape($template['slug']) ?>" class="group block">
                        <!-- Image Card -->
                        <div
                            class="relative aspect-[4/5] overflow-hidden bg-slate-100 rounded-2xl shadow-sm hover:shadow-xl transition-all border border-slate-100 group-hover:border-primary/30">
                            <?= ImageHelper::responsiveThumbnail(
                                $template['thumbnail_url'] ?? '/assets/images/placeholder.jpg',
                                $template['title'],
                                $isAboveFold,
                                $isAboveFold,
                                'absolute inset-0 w-full h-full object-cover transition-transform duration-500 group-hover:scale-105'
                            ) ?>

                            <!-- Badges -->
                            <?php if ($template['is_premium']): ?>
                                <span
                                    class="absolute top-2 left-2 px-2 py-1 rounded-md bg-white/90 text-xs font-bold text-slate-900 backdrop-blur-sm">Premium</span>
                            <?php elseif ($template['price_usd'] == 0): ?>
                                <span
                                    class="absolute top-2 left-2 px-2 py-1 rounded-md bg-green-500/90 text-xs font-bold text-white backdrop-blur-sm">Free</span>
                            <?php endif; ?>

                            <!-- Wishlist Button -->
                            <button type="button"
                                class="wishlist-btn absolute top-2 right-2 size-8 rounded-full bg-white/90 backdrop-blur-sm flex items-center justify-center shadow-sm hover:bg-white transition-all z-10"
                                data-template-id="<?= $template['id'] ?>" data-in-wishlist="false"
                                onclick="event.preventDefault(); event.stopPropagation(); toggleWishlist(this);"
                                title="Add to wishlist">
                                <span class="material-symbols-outlined text-lg text-slate-400 wishlist-icon">favorite</span>
                            </button>
                        </div>

                        <!-- Title & Price (Outside Card) -->
                        <div class="pt-3 px-1">
                            <h3
                                class="font-bold text-sm text-slate-900 truncate group-hover:text-primary transition-colors">
                                <?= Security::escape($template['title']) ?>
                            </h3>
                            <p class="template-price text-sm font-semibold mt-0.5 <?= $template['price_usd'] == 0 ? 'text-green-600' : 'text-slate-700' ?>"
                                data-usd="<?= $template['price_usd'] ?>" data-inr="<?= $template['price_inr'] ?? 0 ?>">
                                <?= $template['price_usd'] == 0 ? 'Free' : '₹' . number_format($template['price_inr'] ?? 0, 0) ?>
                            </p>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>

            <!-- Load More Trigger with Skeleton Placeholders -->
            <?php if ($hasMore): ?>
                <div id="load-more-trigger" class="py-4">
                    <div id="loading-skeletons"
                        class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4 sm:gap-6">
                        <!-- Skeleton Cards -->
                        <?php for ($i = 0; $i < 4; $i++): ?>
                            <div class="animate-pulse">
                                <div class="aspect-[4/5] bg-slate-200 rounded-2xl"></div>
                                <div class="pt-3 px-1">
                                    <div class="h-4 bg-slate-200 rounded w-3/4 mb-2"></div>
                                    <div class="h-3 bg-slate-200 rounded w-1/4"></div>
                                </div>
                            </div>
                        <?php endfor; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- No Results -->
            <?php if (empty($templates)): ?>
                <div class="text-center py-12">
                    <span class="material-symbols-outlined text-6xl text-slate-300">movie</span>
                    <h3 class="mt-4 text-xl font-bold">No templates found</h3>
                    <p class="text-slate-500 mt-2">Try adjusting your filters</p>
                    <a href="/templates"
                        class="inline-flex items-center gap-2 mt-4 px-6 py-3 bg-primary text-white font-bold rounded-xl">
                        View All Templates
                    </a>
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<!-- Mobile Sticky Bottom Bar (Card Style) -->
<div id="mobile-bottom-bar" class="fixed bottom-0 left-0 right-0 z-40 sm:hidden"
    style="background-color: var(--footer-bg-color, #1e293b);">
    <div class="flex">
        <button onclick="openSortSheet()"
            class="flex-1 flex items-center justify-center gap-2 py-4 font-medium transition-colors active:opacity-80"
            style="color: var(--footer-text-color, #94a3b8); border-right: 1px solid var(--footer-text-color, #94a3b8); border-opacity: 0.3;">
            <span class="material-symbols-outlined text-lg">swap_vert</span>
            <span class="text-sm uppercase tracking-wide">Sort</span>
        </button>
        <button onclick="openFilterSheet()"
            class="flex-1 flex items-center justify-center gap-2 py-4 font-medium transition-colors active:opacity-80"
            style="color: var(--footer-text-color, #94a3b8);">
            <span class="material-symbols-outlined text-lg">tune</span>
            <span class="text-sm uppercase tracking-wide">Filter</span>
            <?php if ($category || $tradition): ?>
                <span class="w-2 h-2 rounded-full bg-primary"></span>
            <?php endif; ?>
        </button>
    </div>
</div>

<!-- Bottom Sheet Backdrop -->
<div id="sheet-backdrop" onclick="closeSheet()"
    class="fixed inset-0 bg-black/50 z-[100] opacity-0 pointer-events-none transition-opacity duration-300"></div>

<!-- Filter Bottom Sheet -->
<div id="filter-sheet"
    class="fixed bottom-0 left-0 right-0 z-[110] bg-white rounded-t-3xl transform translate-y-full transition-transform duration-300 ease-out max-h-[85vh] overflow-hidden flex flex-col">
    <!-- Handle -->
    <div class="flex justify-center pt-3 pb-2">
        <div class="w-10 h-1 rounded-full bg-slate-300"></div>
    </div>

    <!-- Header -->
    <div class="flex items-center justify-between px-5 pb-4 border-b border-slate-200">
        <h3 class="text-lg font-bold">Filter Templates</h3>
        <button onclick="closeSheet()" class="p-2 -mr-2 rounded-lg hover:bg-slate-100:bg-slate-800">
            <span class="material-symbols-outlined">close</span>
        </button>
    </div>

    <!-- Filter Content -->
    <div class="flex-1 overflow-y-auto p-5 space-y-6">
        <!-- Categories -->
        <div>
            <h4 class="text-sm font-bold text-slate-500 uppercase tracking-wide mb-3">Category</h4>
            <div class="flex flex-wrap gap-2" id="filter-categories">
                <button type="button" data-category=""
                    class="filter-cat-btn px-4 py-2 rounded-full text-sm font-medium border transition-all <?= !$category ? 'border-primary bg-primary text-white' : 'border-slate-200 text-slate-600' ?>">
                    All
                </button>
                <?php foreach ($allCategories as $key => $cat): ?>
                    <button type="button" data-category="<?= $key ?>"
                        class="filter-cat-btn px-4 py-2 rounded-full text-sm font-medium border transition-all <?= $category === $key ? 'border-primary bg-primary text-white' : 'border-slate-200 text-slate-600' ?>">
                        <?= $cat['name'] ?>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Subcategories (when viewing a main category) -->
        <?php if (!empty($subcategoryFilters)): ?>
            <div>
                <h4 class="text-sm font-bold text-slate-500 uppercase tracking-wide mb-3">Event Type</h4>
                <div class="flex flex-wrap gap-2" id="filter-subcategories">
                    <?php
                    $currentSubcat = $_GET['subcategory'] ?? null;
                    foreach ($subcategoryFilters as $subcat):
                        ?>
                        <button type="button" data-subcategory="<?= $subcat['slug'] ?>"
                            class="filter-subcat-btn px-4 py-2 rounded-full text-sm font-medium border transition-all <?= $currentSubcat === $subcat['slug'] ? 'border-primary bg-primary text-white' : 'border-slate-200 text-slate-600' ?>">
                            <?= htmlspecialchars($subcat['name']) ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Traditions -->
        <div>
            <h4 class="text-sm font-bold text-slate-500 uppercase tracking-wide mb-3">Cultural Tradition</h4>
            <div class="flex flex-wrap gap-2" id="filter-traditions">
                <button type="button" data-tradition=""
                    class="filter-trad-btn px-4 py-2 rounded-full text-sm font-medium border transition-all <?= !$tradition ? 'border-primary bg-primary text-white' : 'border-slate-200 text-slate-600' ?>">
                    All
                </button>
                <?php foreach ($traditions as $t): ?>
                    <button type="button" data-tradition="<?= strtolower($t) ?>"
                        class="filter-trad-btn px-4 py-2 rounded-full text-sm font-medium border transition-all <?= $tradition === strtolower($t) ? 'border-primary bg-primary text-white' : 'border-slate-200 text-slate-600' ?>">
                        <?= $t ?>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Apply Button -->
    <div class="p-5 border-t border-slate-200 bg-white">
        <button onclick="applyFilters()"
            class="w-full py-4 rounded-xl bg-primary text-white font-bold text-lg shadow-lg shadow-primary/30 active:scale-[0.98] transition-transform">
            Apply Filters
        </button>
    </div>
</div>

<!-- Sort Bottom Sheet (Clean Design) -->
<div id="sort-sheet"
    class="fixed bottom-0 left-0 right-0 z-[110] bg-white rounded-t-3xl transform translate-y-full transition-transform duration-300 ease-out">
    <!-- Header -->
    <div class="px-5 py-4 border-b border-slate-200">
        <h3 class="text-sm font-bold uppercase tracking-wide text-slate-900">Sort By</h3>
    </div>

    <!-- Sort Options (Clean List) -->
    <div class="py-2">
        <button onclick="applySort('popular')"
            class="w-full flex items-center gap-4 px-5 py-4 text-left transition-colors hover:bg-slate-50:bg-slate-800">
            <span class="material-symbols-outlined text-xl text-slate-500">local_fire_department</span>
            <span class="font-medium text-slate-700">Popularity</span>
        </button>
        <button onclick="applySort('newest')"
            class="w-full flex items-center gap-4 px-5 py-4 text-left transition-colors hover:bg-slate-50:bg-slate-800">
            <span class="material-symbols-outlined text-xl text-slate-500">schedule</span>
            <span class="font-medium text-slate-700">Latest</span>
        </button>
        <button onclick="applySort('price_high')"
            class="w-full flex items-center gap-4 px-5 py-4 text-left transition-colors hover:bg-slate-50:bg-slate-800">
            <span class="material-symbols-outlined text-xl text-slate-500">trending_down</span>
            <span class="font-medium text-slate-700">Price: High to Low</span>
        </button>
        <button onclick="applySort('price_low')"
            class="w-full flex items-center gap-4 px-5 py-4 text-left transition-colors hover:bg-slate-50:bg-slate-800">
            <span class="material-symbols-outlined text-xl text-slate-500">trending_up</span>
            <span class="font-medium text-slate-700">Price: Low to High</span>
        </button>
    </div>
</div>

<script>
    // State
    let currentPage = 1;
    let isLoading = false;
    let hasMore = <?= $hasMore ? 'true' : 'false' ?>;
    let selectedCategory = '<?= $category ?? '' ?>';
    let selectedSubcategory = '<?= $_GET['subcategory'] ?? '' ?>';
    let selectedTradition = '<?= $tradition ?? '' ?>';
    let currentSort = '<?= $sort ?>';

    // Currency detection (timezone-based) - INR is default
    const userTimezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
    const isIndianUser = userTimezone.includes('Kolkata') || userTimezone.includes('Calcutta') || userTimezone.includes('Asia/');
    const userCurrency = isIndianUser ? 'INR' : 'USD';

    // Update prices based on detected currency (only change if USD user)
    function updatePriceDisplay() {
        document.querySelectorAll('.template-price').forEach(el => {
            const usd = parseFloat(el.dataset.usd) || 0;
            const inr = parseFloat(el.dataset.inr) || 0;
            if (usd === 0) return; // Skip free items

            if (userCurrency === 'USD' && usd > 0) {
                el.textContent = '$' + Math.round(usd);
            }
            // INR is already shown by default in the HTML
        });
    }

    // Run on page load
    document.addEventListener('DOMContentLoaded', updatePriceDisplay);

    // Bottom Sheet Functions
    function openFilterSheet() {
        document.getElementById('sheet-backdrop').classList.remove('opacity-0', 'pointer-events-none');
        document.getElementById('filter-sheet').classList.remove('translate-y-full');
        document.body.style.overflow = 'hidden';
    }

    function openSortSheet() {
        document.getElementById('sheet-backdrop').classList.remove('opacity-0', 'pointer-events-none');
        document.getElementById('sort-sheet').classList.remove('translate-y-full');
        document.body.style.overflow = 'hidden';
    }

    function closeSheet() {
        document.getElementById('sheet-backdrop').classList.add('opacity-0', 'pointer-events-none');
        document.getElementById('filter-sheet').classList.add('translate-y-full');
        document.getElementById('sort-sheet').classList.add('translate-y-full');
        document.body.style.overflow = '';
    }

    // Filter Selection
    document.querySelectorAll('.filter-cat-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            document.querySelectorAll('.filter-cat-btn').forEach(b => {
                b.classList.remove('border-primary', 'bg-primary', 'text-white');
                b.classList.add('border-slate-200', 'text-slate-600');
            });
            this.classList.remove('border-slate-200', 'text-slate-600');
            this.classList.add('border-primary', 'bg-primary', 'text-white');
            selectedCategory = this.dataset.category;
        });
    });

    document.querySelectorAll('.filter-trad-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            document.querySelectorAll('.filter-trad-btn').forEach(b => {
                b.classList.remove('border-primary', 'bg-primary', 'text-white');
                b.classList.add('border-slate-200', 'text-slate-600');
            });
            this.classList.remove('border-slate-200', 'text-slate-600');
            this.classList.add('border-primary', 'bg-primary', 'text-white');
            selectedTradition = this.dataset.tradition;
        });
    });

    // Subcategory filter buttons
    document.querySelectorAll('.filter-subcat-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            // Toggle: click again to deselect
            if (this.classList.contains('bg-primary')) {
                this.classList.remove('border-primary', 'bg-primary', 'text-white');
                this.classList.add('border-slate-200', 'text-slate-600');
                selectedSubcategory = '';
            } else {
                document.querySelectorAll('.filter-subcat-btn').forEach(b => {
                    b.classList.remove('border-primary', 'bg-primary', 'text-white');
                    b.classList.add('border-slate-200', 'text-slate-600');
                });
                this.classList.remove('border-slate-200', 'text-slate-600');
                this.classList.add('border-primary', 'bg-primary', 'text-white');
                selectedSubcategory = this.dataset.subcategory;
            }
        });
    });

    function applyFilters() {
        const params = new URLSearchParams();
        // If subcategory is selected, use that as the category instead
        if (selectedSubcategory) {
            params.set('category', selectedSubcategory);
        } else if (selectedCategory) {
            params.set('category', selectedCategory);
        }
        if (selectedTradition) params.set('tradition', selectedTradition);
        if (currentSort !== 'popular') params.set('sort', currentSort);
        window.location.href = '/templates' + (params.toString() ? '?' + params.toString() : '');
    }

    function applySort(sort) {
        currentSort = sort;
        const params = new URLSearchParams(window.location.search);
        if (sort === 'popular') {
            params.delete('sort');
        } else {
            params.set('sort', sort);
        }
        window.location.href = '/templates' + (params.toString() ? '?' + params.toString() : '');
    }

    // Infinite Scroll
    const grid = document.getElementById('templates-grid');
    const loadTrigger = document.getElementById('load-more-trigger');
    const spinner = document.getElementById('loading-spinner');

    if (loadTrigger) {
        const observer = new IntersectionObserver((entries) => {
            if (entries[0].isIntersecting && !isLoading && hasMore) {
                loadMoreTemplates();
            }
        }, { rootMargin: '200px' });

        observer.observe(loadTrigger);
    }

    async function loadMoreTemplates() {
        if (isLoading || !hasMore) return;
        isLoading = true;
        currentPage++;

        const params = new URLSearchParams();
        params.set('page', currentPage);
        params.set('limit', 12);
        if (selectedCategory) params.set('category', selectedCategory);
        if (selectedTradition) params.set('tradition', selectedTradition);
        params.set('sort', currentSort);

        try {
            const response = await fetch('/api/templates.php?' + params.toString());
            const data = await response.json();

            if (data.success && data.templates.length > 0) {
                data.templates.forEach(template => {
                    grid.insertAdjacentHTML('beforeend', createTemplateCard(template));
                });
                hasMore = data.pagination.hasMore;
            }

            if (!hasMore && loadTrigger) {
                loadTrigger.style.display = 'none';
            }
        } catch (error) {
            console.error('Failed to load templates:', error);
        }

        isLoading = false;
    }

    function createTemplateCard(template) {
        const isFree = template.price_usd === 0;
        const priceClass = isFree ? 'text-green-600' : 'text-slate-700';
        const priceInr = Math.round(template.price_inr || 0);
        const priceUsd = Math.round(template.price_usd || 0);
        const priceText = isFree ? 'Free' : (userCurrency === 'USD' ? '$' + priceUsd : '₹' + priceInr.toLocaleString('en-IN'));

        let badge = '';
        if (template.is_premium) {
            badge = '<span class="absolute top-2 left-2 px-2 py-1 rounded-md bg-white/90 text-xs font-bold text-slate-900 backdrop-blur-sm">Premium</span>';
        } else if (isFree) {
            badge = '<span class="absolute top-2 left-2 px-2 py-1 rounded-md bg-green-500/90 text-xs font-bold text-white backdrop-blur-sm">Free</span>';
        }

        // Use 400w variant or fallback
        const imgSrc = template.srcset && template.srcset[400] ? template.srcset[400] : template.thumbnail_url;

        // Wishlist button
        const wishlistBtn = `
            <button type="button" 
                class="wishlist-btn absolute top-2 right-2 size-8 rounded-full bg-white/90 backdrop-blur-sm flex items-center justify-center shadow-sm hover:bg-white transition-all z-10"
                data-template-id="${template.id}"
                data-in-wishlist="false"
                onclick="event.preventDefault(); event.stopPropagation(); toggleWishlist(this);"
                title="Add to wishlist">
                <span class="material-symbols-outlined text-lg text-slate-400 wishlist-icon">favorite</span>
            </button>
        `;

        return `
        <a href="/template/${template.slug}" class="group block">
            <div class="relative aspect-[4/5] overflow-hidden bg-slate-100 rounded-2xl shadow-sm hover:shadow-xl transition-all border border-slate-100 group-hover:border-primary/30">
                <img src="${imgSrc}" alt="${template.title}" loading="lazy" decoding="async" 
                     class="absolute inset-0 w-full h-full object-cover transition-transform duration-500 group-hover:scale-105">
                ${badge}
                ${wishlistBtn}
            </div>
            <div class="pt-3 px-1">
                <h3 class="font-bold text-sm text-slate-900 truncate group-hover:text-primary transition-colors">${template.title}</h3>
                <p class="template-price text-sm font-semibold mt-0.5 ${priceClass}" data-usd="${template.price_usd}" data-inr="${template.price_inr || 0}">${priceText}</p>
            </div>
        </a>
    `;
    }

    // Prevent body scroll when sheets are open
    document.addEventListener('touchmove', function (e) {
        if (document.body.style.overflow === 'hidden') {
            const sheet = e.target.closest('#filter-sheet, #sort-sheet');
            if (!sheet) {
                e.preventDefault();
            }
        }
    }, { passive: false });

    // User login status for wishlist
    window.isUserLoggedIn = <?= !empty($_SESSION['user_id']) ? 'true' : 'false' ?>;
</script>

<!-- Wishlist JavaScript -->
<script src="/assets/js/wishlist.js" defer></script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
?>