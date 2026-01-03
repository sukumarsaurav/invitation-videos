<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../src/Core/Security.php';
require_once __DIR__ . '/../../src/Core/ImageHelper.php';

// Get filters
$category = $_GET['category'] ?? null;
$tradition = $_GET['tradition'] ?? null;
$priceRange = $_GET['price'] ?? null;
$sort = $_GET['sort'] ?? 'popular';

// Build query
$sql = "SELECT * FROM templates WHERE is_active = 1";
$params = [];

if ($category) {
    $sql .= " AND category = ?";
    $params[] = $category;
}

if ($tradition) {
    $sql .= " AND cultural_tradition = ?";
    $params[] = $tradition;
}

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

$templates = Database::fetchAll($sql, $params);

// Categories for filter
$categories = [
    'wedding' => 'Wedding',
    'birthday' => 'Birthday',
    'corporate' => 'Corporate',
    'baby_shower' => 'Baby Shower',
    'anniversary' => 'Anniversary'
];

// Cultural traditions
$traditions = ['Hindu', 'Muslim', 'Christian', 'Sikh', 'Jewish', 'Chinese', 'Western'];

// SEO: Dynamic page titles and meta descriptions based on filters
$categoryTitles = [
    'wedding' => 'Wedding Video Invitation Templates',
    'birthday' => 'Birthday Video Invitation Templates',
    'corporate' => 'Corporate Event Video Templates',
    'baby_shower' => 'Baby Shower Video Invitation Templates',
    'anniversary' => 'Anniversary Video Invitation Templates',
    'holi' => 'Holi Festival Video Invitations',
    'diwali' => 'Diwali Festival Video Invitations',
    'graduation' => 'Graduation Video Invitation Templates',
    'farewell' => 'Farewell Party Video Invitations',
    'holidays' => 'Holiday Video Invitation Templates',
    'housewarming' => 'Housewarming Video Invitation Templates',
    'parties' => 'Party Video Invitation Templates',
    'religious' => 'Religious Event Video Invitations',
    'save_the_date' => 'Save the Date Video Templates',
];

$traditionTitles = [
    'hindu' => 'Hindu Wedding Video Invitations',
    'muslim' => 'Muslim Wedding Video Invitations',
    'christian' => 'Christian Wedding Video Invitations',
    'sikh' => 'Sikh Wedding Video Invitations',
    'jewish' => 'Jewish Wedding Video Invitations',
    'chinese' => 'Chinese Wedding Video Invitations',
    'western' => 'Western Wedding Video Invitations',
];

if ($category && isset($categoryTitles[$category])) {
    $pageTitle = $categoryTitles[$category];
    $metaDescription = "Browse our beautiful collection of {$categoryTitles[$category]}. Easy customization, professional quality, instant download. Create your perfect invitation today!";
} elseif ($tradition && isset($traditionTitles[strtolower($tradition)])) {
    $pageTitle = $traditionTitles[strtolower($tradition)];
    $metaDescription = "Beautiful {$traditionTitles[strtolower($tradition)]} templates. Culturally authentic designs with easy customization. Download and share your perfect invitation.";
} else {
    $pageTitle = 'Video Invitation Templates - All Categories';
    $metaDescription = 'Browse our stunning collection of video invitation templates for weddings, birthdays, anniversaries, and special events. Easy customization, instant download.';
}
?>

<?php ob_start(); ?>

<div class="flex flex-1 justify-center w-full">
    <div class="flex w-full max-w-[1600px] flex-col lg:flex-row">

        <!-- Mobile Filter Button -->
        <div
            class="lg:hidden sticky top-[65px] z-40 bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-700 px-4 py-3">
            <button onclick="toggleFilters()"
                class="w-full flex items-center justify-between px-4 py-2.5 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                <span class="flex items-center gap-2 text-sm font-medium">
                    <span class="material-symbols-outlined text-lg">tune</span>
                    Filters
                </span>
                <span id="filterArrow" class="material-symbols-outlined text-lg transition-transform">expand_more</span>
            </button>
        </div>

        <!-- Sidebar Filters -->
        <aside id="filterSidebar"
            class="hidden lg:block w-full lg:w-72 xl:w-80 lg:shrink-0 border-r border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 overflow-y-auto lg:h-[calc(100vh-65px)] lg:sticky lg:top-[65px]">
            <div class="flex flex-col h-full p-4 sm:p-6">

                <!-- Categories -->
                <div class="py-4">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-3 px-1">Categories</h3>
                    <div class="space-y-0.5">
                        <a href="/templates"
                            class="w-full flex items-center justify-between px-3 py-2 text-sm font-medium <?= !$category ? 'text-primary bg-primary/5' : 'text-slate-600 hover:text-primary' ?> rounded-lg">
                            All Events
                        </a>
                        <?php foreach ($categories as $key => $label): ?>
                            <a href="/templates?category=<?= $key ?>"
                                class="w-full flex items-center justify-between px-3 py-2 text-sm font-medium <?= $category === $key ? 'text-primary bg-primary/5 font-bold' : 'text-slate-600 hover:text-primary' ?> rounded-lg">
                                <?= $label ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="h-px bg-slate-200 dark:bg-slate-800 my-2"></div>

                <!-- Cultural Traditions -->
                <div class="py-4">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-3 px-1">Cultural Traditions
                    </h3>
                    <div class="flex flex-wrap gap-2">
                        <?php foreach ($traditions as $t): ?>
                            <a href="/templates?tradition=<?= strtolower($t) ?>"
                                class="inline-flex items-center gap-1 rounded-full border px-3 py-1.5 text-xs font-bold transition-all 
                           <?= $tradition === strtolower($t) ? 'border-primary bg-primary text-white' : 'border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-600 hover:border-primary hover:text-primary' ?>">
                                <?= $t ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="h-px bg-slate-200 dark:bg-slate-800 my-2"></div>

                <!-- Price Range -->
                <div class="py-4">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-3 px-1">Price Range</h3>
                    <div class="space-y-2">
                        <label class="flex items-center gap-2 text-sm text-slate-600 cursor-pointer hover:text-primary">
                            <input type="radio" name="price" value="" <?= !$priceRange ? 'checked' : '' ?>
                                class="text-primary focus:ring-primary"> Any Price
                        </label>
                        <label class="flex items-center gap-2 text-sm text-slate-600 cursor-pointer hover:text-primary">
                            <input type="radio" name="price" value="free" class="text-primary focus:ring-primary"> Free
                        </label>
                        <label class="flex items-center gap-2 text-sm text-slate-600 cursor-pointer hover:text-primary">
                            <input type="radio" name="price" value="premium" class="text-primary focus:ring-primary">
                            Premium
                        </label>
                    </div>
                </div>

            </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 p-4 sm:p-6 lg:p-10">

            <!-- Header -->
            <div class="mb-6 sm:mb-8">
                <nav class="flex items-center gap-2 text-sm mb-4">
                    <a class="text-slate-500 hover:text-primary transition-colors" href="/">Home</a>
                    <span class="text-slate-400">/</span>
                    <span class="font-medium text-slate-900 dark:text-white">Templates</span>
                </nav>

                <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4">
                    <div class="flex flex-col gap-2">
                        <h1 class="text-3xl md:text-4xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                            <?= $category ? $categories[$category] : 'All' ?> Templates
                        </h1>
                        <p class="text-slate-500 dark:text-slate-400">
                            <?= count($templates) ?> templates found
                        </p>
                    </div>

                    <!-- Sort -->
                    <div class="flex items-center gap-2">
                        <span class="text-sm text-slate-500">Sort by:</span>
                        <select
                            class="rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-4 py-2 text-sm font-medium"
                            onchange="window.location.href=this.value">
                            <option value="?sort=popular" <?= $sort === 'popular' ? 'selected' : '' ?>>Popular</option>
                            <option value="?sort=newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest</option>
                            <option value="?sort=price_low" <?= $sort === 'price_low' ? 'selected' : '' ?>>Price: Low
                            </option>
                            <option value="?sort=price_high" <?= $sort === 'price_high' ? 'selected' : '' ?>>Price: High
                            </option>
                        </select>
                    </div>
                </div>
            </div>

            <?php
            // SEO Content: Category and Tradition Descriptions
            $categoryDescriptions = [
                'wedding' => 'Create the perfect first impression with our stunning wedding invitation videos. From elegant save-the-date animations to grand reception announcements, our wedding video templates capture the romance and joy of your special day. Each template is professionally designed with beautiful typography, smooth transitions, and customizable elements for names, dates, and venue details.',
                'birthday' => 'Make birthday celebrations unforgettable with animated video invitations that bring excitement and joy. Our birthday invitation videos feature vibrant designs for all ages – from playful kids\' party themes to sophisticated adult celebration templates. Add photos, custom messages, and your choice of music to create an invitation that sets the perfect party mood.',
                'baby_shower' => 'Announce the arrival of your little one with adorable baby shower invitation videos. Our collection includes sweet animations for both boy and girl celebrations, gender reveal announcements, and sprinkle party invites. Each video invitation template features gentle colors, cute graphics, and space for all your event details.',
                'anniversary' => 'Celebrate years of love and togetherness with beautiful anniversary invitation videos. Whether it\'s a milestone 25th silver anniversary or a golden 50th celebration, our video templates help you invite guests in a memorable way. Share your journey with photo montages and heartfelt messages.',
                'corporate' => 'Elevate your corporate events with professional video invitations that make a lasting impression. Our business-ready templates are perfect for conferences, product launches, team celebrations, and networking events. Clean designs, customizable branding elements, and sophisticated animations reflect your company\'s professionalism.',
                'graduation' => 'Mark academic achievements with inspiring graduation invitation videos. From high school to university ceremonies, our templates celebrate this important milestone with pride. Include graduation photos, ceremony details, and party information in one beautiful animated invitation.',
                'housewarming' => 'Welcome guests to your new home with charming housewarming invitation videos. Our templates feature cozy home-themed animations that perfectly set the tone for your celebration. Share your excitement about your new space and invite loved ones to help you make it a home.',
                'parties' => 'Get the party started with dynamic video invitations that build excitement for any celebration. From cocktail parties to themed events, our party invitation videos feature energetic animations, bold designs, and space for all your event details. Make your guests eager to RSVP yes!',
                'religious' => 'Honor sacred traditions with respectful and beautiful religious event invitation videos. Our collection includes templates for christenings, bar/bat mitzvahs, first communions, and other spiritual celebrations. Each design incorporates appropriate symbols and elegant styling.',
                'farewell' => 'Say goodbye in style with heartfelt farewell invitation videos. Whether it\'s a retirement party, going-away celebration, or fond farewell, our templates help you gather loved ones for one last memorable gathering. Add photos and personal messages to make it special.',
                'holidays' => 'Spread festive cheer with holiday invitation videos for all seasonal celebrations. From Christmas parties to New Year\'s Eve bashes, our templates capture the spirit of each holiday with themed animations, colors, and music. Create invitations that put guests in a celebratory mood.',
                'save_the_date' => 'Give your guests advance notice with elegant save-the-date video invitations. Our animated templates create anticipation for your upcoming wedding or event. Include essential details like date, location, and a preview of what\'s to come in a beautifully designed video format.',
                'diwali' => 'Light up your Diwali celebration invitations with stunning video templates featuring diyas, rangoli, and festive fireworks. Our Diwali invitation videos capture the joy and brightness of the Festival of Lights, perfect for puja ceremonies, family gatherings, and Diwali parties.',
                'holi' => 'Celebrate the Festival of Colors with vibrant Holi invitation videos bursting with gulaal splashes and joyful animations. Our templates capture the playful spirit of Holi, perfect for inviting friends and family to join your colorful celebration.',
            ];

            $traditionDescriptions = [
                'hindu' => 'Embrace the richness of Hindu wedding traditions with our culturally authentic video invitations. Our Hindu wedding invitation videos feature beautiful elements like mandaps, kalash, paisley patterns, and traditional motifs. Perfect for Mehendi, Sangeet, Haldi, and main wedding ceremony invitations.',
                'muslim' => 'Honor Islamic traditions with elegant Muslim wedding invitation videos. Our templates incorporate beautiful Arabic calligraphy, crescent moon motifs, and sophisticated designs suitable for Nikah ceremonies and Walima celebrations. Each video invitation maintains cultural respect while celebrating your union.',
                'christian' => 'Celebrate your Christian wedding with graceful video invitations featuring crosses, church imagery, and elegant floral designs. Our templates are perfect for church weddings, rehearsal dinners, and reception celebrations. Share your faith and love through beautifully animated invitations.',
                'sikh' => 'Honor Sikh traditions with video invitations featuring Khanda symbols, Gurudwara imagery, and vibrant Punjabi designs. Our Sikh wedding invitation videos are perfect for Anand Karaj ceremonies and all pre-wedding celebrations. Capture the joy and spirituality of your special day.',
                'jewish' => 'Celebrate Jewish traditions with elegant video invitations featuring Star of David, Chuppah imagery, and traditional motifs. Our templates are perfect for Jewish weddings, Bar/Bat Mitzvahs, and holiday celebrations. Each design honors your heritage with beautiful animations.',
                'chinese' => 'Embrace Chinese traditions with auspicious video invitations featuring lucky symbols, red and gold themes, and traditional motifs. Our Chinese wedding invitation videos are perfect for tea ceremonies and banquet celebrations. Include Double Happiness symbols and elegant calligraphy.',
                'western' => 'Create timeless elegance with our contemporary Western wedding invitation videos. Featuring classic designs, romantic typography, and sophisticated animations, our templates are perfect for modern celebrations. From rustic charm to black-tie elegance, find your perfect style.',
            ];

            $pageDescription = '';
            if ($category && isset($categoryDescriptions[$category])) {
                $pageDescription = $categoryDescriptions[$category];
            } elseif ($tradition && isset($traditionDescriptions[strtolower($tradition)])) {
                $pageDescription = $traditionDescriptions[strtolower($tradition)];
            } else {
                $pageDescription = 'Browse our extensive collection of professionally designed video invitation templates. From elegant wedding announcements to vibrant birthday parties, our invitation videos help you create memorable first impressions. Each template is fully customizable with your event details, photos, and music – making it easy to create stunning video invitations that your guests will love.';
            }
            ?>

            <?php if ($pageDescription): ?>
                <div class="mb-8 max-w-3xl">
                    <p class="text-slate-600 dark:text-slate-400 leading-relaxed text-sm">
                        <?= htmlspecialchars($pageDescription) ?>
                    </p>
                </div>
            <?php endif; ?>

            <!-- Template Grid -->
            <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-3 2xl:grid-cols-4 gap-6 mb-12">
                <?php foreach ($templates as $index => $template):
                    // First 2 images are above the fold on mobile - load eagerly
                    $isAboveFold = $index < 2;
                    ?>
                    <div
                        class="group relative flex flex-col overflow-hidden rounded-xl bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800 shadow-sm transition-all hover:shadow-xl hover:-translate-y-1">
                        <div class="relative aspect-[4/5] w-full overflow-hidden bg-slate-100">
                            <?= ImageHelper::responsiveThumbnail(
                                $template['thumbnail_url'] ?? '/assets/images/placeholder.jpg',
                                $template['title'],
                                $isAboveFold,
                                $isAboveFold,
                                'absolute inset-0 w-full h-full object-cover transition-transform duration-700 group-hover:scale-105'
                            ) ?>

                            <!-- Play Button Overlay -->
                            <div
                                class="absolute inset-0 flex items-center justify-center bg-black/40 opacity-0 transition-opacity group-hover:opacity-100">
                                <button
                                    class="flex h-12 w-12 items-center justify-center rounded-full bg-white/20 text-white backdrop-blur-sm transition-transform hover:scale-110">
                                    <span class="material-symbols-outlined text-3xl">play_arrow</span>
                                </button>
                            </div>

                            <!-- Badges -->
                            <div class="absolute left-3 top-3 flex gap-2">
                                <?php if ($template['is_premium']): ?>
                                    <span
                                        class="rounded-md bg-white/90 px-2 py-1 text-xs font-bold text-slate-900 backdrop-blur-sm shadow-sm">Premium</span>
                                <?php elseif ($template['price_usd'] == 0): ?>
                                    <span
                                        class="rounded-md bg-green-500/90 px-2 py-1 text-xs font-bold text-white backdrop-blur-sm shadow-sm">Free</span>
                                <?php endif; ?>
                            </div>

                            <!-- Favorite Button -->
                            <div class="absolute right-3 top-3">
                                <button
                                    class="rounded-full bg-white/20 p-2 text-white hover:bg-white hover:text-red-500 backdrop-blur-sm transition-colors">
                                    <span class="material-symbols-outlined text-[20px]">favorite</span>
                                </button>
                            </div>
                        </div>

                        <div class="flex flex-1 flex-col p-4">
                            <div class="flex justify-between items-start mb-2">
                                <h3 class="text-base font-bold text-slate-900 dark:text-white leading-tight">
                                    <?= Security::escape($template['title']) ?>
                                </h3>
                                <span
                                    class="text-base font-bold <?= $template['price_usd'] == 0 ? 'text-green-600' : 'text-primary' ?>">
                                    <?= $template['price_usd'] == 0 ? 'Free' : '$' . number_format($template['price_usd'], 0) ?>
                                </span>
                            </div>

                            <div class="mb-4 flex flex-wrap items-center gap-3 text-xs text-slate-500 dark:text-slate-400">
                                <div class="flex items-center gap-1">
                                    <span class="material-symbols-outlined text-[14px]">schedule</span>
                                    <span><?= $template['duration_seconds'] ?>s</span>
                                </div>
                                <div class="flex items-center gap-1">
                                    <span class="material-symbols-outlined text-[14px]">aspect_ratio</span>
                                    <span><?= $template['aspect_ratio'] ?? '9:16' ?></span>
                                </div>
                            </div>

                            <a href="/template/<?= Security::escape($template['slug']) ?>"
                                class="mt-auto flex w-full items-center justify-center gap-2 rounded-lg bg-primary py-2 text-sm font-bold text-white transition-all hover:bg-primary/90 focus:ring-4 focus:ring-primary/20">
                                Select
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if (empty($templates)): ?>
                <div class="text-center py-12">
                    <span class="material-symbols-outlined text-6xl text-slate-300">movie</span>
                    <h3 class="mt-4 text-xl font-bold">No templates found</h3>
                    <p class="text-slate-500 mt-2">Try adjusting your filters</p>
                </div>
            <?php endif; ?>

        </main>
    </div>
</div>

<script>
    function toggleFilters() {
        const sidebar = document.getElementById('filterSidebar');
        const arrow = document.getElementById('filterArrow');

        sidebar.classList.toggle('hidden');
        arrow.style.transform = sidebar.classList.contains('hidden') ? '' : 'rotate(180deg)';
    }
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
?>