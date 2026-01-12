<?php
/**
 * Homepage Configuration
 * 
 * Categories are now loaded from the database `categories` table.
 * This file provides fallback values and hero section configuration.
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
// CATEGORIES - Loaded from Database
// =====================================================
// Try to load categories from database, fallback to hardcoded if unavailable
$homepageCategories = [];
$navCategories = [];

try {
    // Include database if not already included
    if (!class_exists('Database')) {
        require_once __DIR__ . '/database.php';
    }

    // Get main categories (parent_id = NULL) and select miscellaneous subcategories for homepage
    $dbCategories = Database::fetchAll(
        "SELECT id, name, slug, icon, color, image_url, parent_id 
         FROM categories 
         WHERE is_active = 1 
         AND (parent_id IS NULL OR parent_id = (SELECT id FROM categories WHERE slug = 'miscellaneous'))
         ORDER BY 
            CASE WHEN parent_id IS NULL THEN 0 ELSE 1 END,
            display_order"
    ) ?? [];

    foreach ($dbCategories as $cat) {
        // Skip "miscellaneous" as a display category (its children are shown instead)
        if ($cat['slug'] === 'miscellaneous')
            continue;

        // Always use hardcoded webp path (ignore database image_url)
        $image = '/assets/images/categories/' . $cat['slug'] . '.webp';

        // Build homepage categories array
        $homepageCategories[] = [
            'slug' => $cat['slug'],
            'name' => $cat['name'],
            'image' => $image,
        ];

        // Build nav categories (keyed by slug)
        $navCategories[$cat['slug']] = [
            'name' => $cat['name'],
            'image' => $image,
        ];
    }
} catch (Exception $e) {
    // Database not available, use fallback
}

// Fallback if database failed or returned empty
if (empty($homepageCategories)) {
    $homepageCategories = [
        ['slug' => 'wedding', 'name' => 'Wedding', 'image' => '/assets/images/categories/wedding.webp'],
        ['slug' => 'birthday', 'name' => 'Birthday', 'image' => '/assets/images/categories/birthday.webp'],
        ['slug' => 'party', 'name' => 'Party', 'image' => '/assets/images/categories/parties.webp'],
        ['slug' => 'pooja-rituals', 'name' => 'Pooja & Rituals', 'image' => '/assets/images/categories/religious.webp'],
        ['slug' => 'festivals', 'name' => 'Festivals', 'image' => '/assets/images/categories/holidays.webp'],
        ['slug' => 'corporate', 'name' => 'Corporate', 'image' => '/assets/images/categories/corporate.webp'],
        ['slug' => 'anniversary', 'name' => 'Anniversary', 'image' => '/assets/images/categories/anniversary.webp'],
        ['slug' => 'save-the-date', 'name' => 'Save Date', 'image' => '/assets/images/categories/save_the_date.webp'],
        ['slug' => 'farewell', 'name' => 'Farewell', 'image' => '/assets/images/categories/farewell.webp'],
    ];

    $navCategories = [
        'wedding' => ['name' => 'Wedding', 'image' => '/assets/images/categories/wedding.webp'],
        'birthday' => ['name' => 'Birthday', 'image' => '/assets/images/categories/birthday.webp'],
        'party' => ['name' => 'Party', 'image' => '/assets/images/categories/parties.webp'],
        'pooja-rituals' => ['name' => 'Pooja & Rituals', 'image' => '/assets/images/categories/religious.webp'],
        'festivals' => ['name' => 'Festivals', 'image' => '/assets/images/categories/holidays.webp'],
        'corporate' => ['name' => 'Corporate', 'image' => '/assets/images/categories/corporate.webp'],
        'anniversary' => ['name' => 'Anniversary', 'image' => '/assets/images/categories/anniversary.webp'],
    ];
}

// =====================================================
// HOMEPAGE SECTIONS
// Template showcase sections displayed below categories
// =====================================================
$homepageSections = [];
