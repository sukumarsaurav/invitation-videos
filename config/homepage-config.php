<?php
/**
 * Homepage Configuration
 * 
 * Edit this file to update homepage content without CMS.
 * Add/remove categories, update hero section, or modify sections here.
 * 
 * Example: To add Holi category for festival season:
 *   1. Add image to /assets/images/categories/holi.webp
 *   2. Add entry to $homepageCategories array below
 */

// =====================================================
// HERO SECTION
// =====================================================
$heroConfig = [
    'title' => 'Create Beautiful <span class="text-primary">Invitation Videos</span>',
    'subtitle' => 'Stunning video invitations for weddings, birthdays, and special events. Easy to customize, ready to share.',
    'button_text' => 'Browse Templates',
    'button_link' => '/templates',
    // Leave empty for gradient background, or set image paths
    'image_desktop' => '',
    'image_mobile' => '',
];

// =====================================================
// CATEGORIES
// All categories displayed on homepage
// Images are loaded from /assets/images/categories/{slug}.webp
// =====================================================
$homepageCategories = [
    [
        'slug' => 'wedding',
        'name' => 'Wedding',
        'image' => '/assets/images/categories/wedding.png',
    ],
    [
        'slug' => 'birthday',
        'name' => 'Birthday',
        'image' => '/assets/images/categories/birthday.png',
    ],
    [
        'slug' => 'baby_shower',
        'name' => 'Baby Shower',
        'image' => '/assets/images/categories/baby_shower.png',
    ],
    [
        'slug' => 'save_the_date',
        'name' => 'Save Date',
        'image' => '/assets/images/categories/save_the_date.png',
    ],
    [
        'slug' => 'parties',
        'name' => 'Parties',
        'image' => '/assets/images/categories/parties.png',
    ],
    [
        'slug' => 'corporate',
        'name' => 'Corporate',
        'image' => '/assets/images/categories/corporate.png',
    ],
    [
        'slug' => 'holidays',
        'name' => 'Holidays',
        'image' => '/assets/images/categories/holidays.png',
    ],
    [
        'slug' => 'anniversary',
        'name' => 'Anniversary',
        'image' => '/assets/images/categories/anniversary.png',
    ],
    [
        'slug' => 'graduation',
        'name' => 'Graduation',
        'image' => '/assets/images/categories/graduation.png',
    ],
    [
        'slug' => 'housewarming',
        'name' => 'Housewarming',
        'image' => '/assets/images/categories/housewarming.png',
    ],
    [
        'slug' => 'religious',
        'name' => 'Religious',
        'image' => '/assets/images/categories/religious.png',
    ],
    [
        'slug' => 'farewell',
        'name' => 'Farewell',
        'image' => '/assets/images/categories/farewell.png',
    ],
    // =====================================================
    // SEASONAL CATEGORIES (uncomment when needed)
    // =====================================================
    // [
    //     'slug' => 'holi',
    //     'name' => 'Holi',
    //     'image' => '/assets/images/categories/holi.png',
    // ],
    // [
    //     'slug' => 'diwali',
    //     'name' => 'Diwali',
    //     'image' => '/assets/images/categories/diwali.png',
    // ],
    // [
    //     'slug' => 'christmas',
    //     'name' => 'Christmas',
    //     'image' => '/assets/images/categories/christmas.png',
    // ],
];

// =====================================================
// HOMEPAGE SECTIONS
// Template showcase sections displayed below categories
// Each section pulls templates based on category/subcategory filters
// =====================================================
$homepageSections = [
    // Example section (uncomment and customize as needed):
    // [
    //     'title' => 'Wedding Collection',
    //     'category_slug' => 'wedding',
    //     'subcategory' => null,
    //     'template_count' => 4,
    //     'banner_bg_color' => '#a11045',
    //     'title_color' => '#d4a853',
    //     'grid_bg_color' => '#f5f0e8',
    // ],
];
