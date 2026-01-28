<?php
/**
 * Template Card Component
 * 
 * Reusable template card component for displaying template previews
 * Used in: home.php, gallery.php, service pages, wishlist.php
 * 
 * @package InvitationVideos
 */

require_once __DIR__ . '/../../src/Core/Security.php';
require_once __DIR__ . '/../../src/Core/ImageHelper.php';

/**
 * Render a template card component
 * 
 * @param array $template Template data from database
 * @param array $options Optional configuration:
 *   - badgeColor: string - Tailwind color classes for category badge (default: 'bg-slate-100 text-slate-700')
 *   - isAboveFold: bool - Whether to eager load image (default: false)
 *   - variant: string - 'desktop' | 'mobile' | 'service' (default: 'desktop')
 *   - showPrice: bool - Whether to show price (default: true)
 *   - showBadge: bool - Whether to show category badge (default: true)
 * @return string HTML output
 */
function renderTemplateCard(array $template, array $options = []): string
{
    // Default options
    $defaults = [
        'badgeColor' => 'bg-slate-100 text-slate-700',
        'isAboveFold' => false,
        'variant' => 'desktop',
        'showPrice' => true,
        'showBadge' => true,
    ];
    $opts = array_merge($defaults, $options);

    // Variant-specific styling
    $variants = [
        'desktop' => [
            'card' => 'group block bg-white rounded-2xl overflow-hidden shadow-sm hover:shadow-xl transition-all border border-slate-200 hover:border-primary/30',
            'imageWrapper' => 'relative aspect-[4/5] overflow-hidden bg-slate-100',
            'imageClass' => 'absolute inset-0 w-full h-full object-cover transition-transform duration-500 group-hover:scale-105',
            'badgeWrapper' => 'absolute top-3 left-3',
            'badgeClass' => 'px-3 py-1 rounded-full text-xs font-bold',
            'content' => 'p-4',
            'title' => 'font-bold text-slate-900 group-hover:text-primary transition-colors truncate',
            'price' => 'text-sm',
        ],
        'mobile' => [
            'card' => 'group flex-shrink-0 w-[45%] snap-start bg-white rounded-2xl overflow-hidden shadow-sm hover:shadow-xl transition-all border border-slate-200',
            'imageWrapper' => 'relative aspect-[4/5] overflow-hidden bg-slate-100',
            'imageClass' => 'absolute inset-0 w-full h-full object-cover',
            'badgeWrapper' => 'absolute top-2 left-2',
            'badgeClass' => 'px-2 py-0.5 rounded-full text-[10px] font-bold',
            'content' => 'p-3',
            'title' => 'font-bold text-sm text-slate-900 truncate',
            'price' => 'text-xs',
        ],
        'service' => [
            'card' => 'group block bg-white rounded-2xl overflow-hidden shadow-sm hover:shadow-xl transition-all border border-slate-200 hover:border-primary/30',
            'imageWrapper' => 'relative aspect-[4/5] overflow-hidden bg-slate-100',
            'imageClass' => 'absolute inset-0 w-full h-full object-cover transition-transform duration-500 group-hover:scale-105',
            'badgeWrapper' => 'absolute top-3 left-3',
            'badgeClass' => 'px-3 py-1 rounded-full text-xs font-bold',
            'content' => 'p-4',
            'title' => 'font-bold text-slate-900 group-hover:text-primary transition-colors truncate',
            'price' => 'text-sm',
        ],
        'gallery' => [
            'card' => 'group block bg-white rounded-2xl overflow-hidden shadow-sm hover:shadow-xl transition-all border border-slate-200 hover:border-primary/30',
            'imageWrapper' => 'relative aspect-[4/5] overflow-hidden bg-slate-100',
            'imageClass' => 'absolute inset-0 w-full h-full object-cover transition-transform duration-500 group-hover:scale-105',
            'badgeWrapper' => 'absolute top-3 left-3',
            'badgeClass' => 'px-3 py-1 rounded-full text-xs font-bold',
            'content' => 'p-4',
            'title' => 'font-bold text-slate-900 group-hover:text-primary transition-colors truncate',
            'price' => 'text-sm',
        ],
    ];

    $style = $variants[$opts['variant']] ?? $variants['desktop'];

    // Extract template data with defaults
    $slug = Security::escape($template['slug'] ?? '');
    $title = Security::escape($template['title'] ?? 'Untitled');
    $thumbnail = $template['thumbnail_url'] ?? '/assets/images/placeholder.jpg';
    $category = $template['category'] ?? '';
    $priceUsd = $template['price_usd'] ?? 0;
    $priceInr = $template['price_inr'] ?? 0;

    // Format category for display
    $categoryDisplay = ucfirst(str_replace('_', ' ', $category));

    // Price display
    $priceClass = $priceUsd > 0 ? 'text-primary font-bold' : 'text-green-600 font-bold';
    $priceText = $priceUsd > 0 ? '₹' . number_format($priceInr, 0) : 'Free';

    // Build the HTML
    ob_start();
    ?>
    <a href="/template/<?= $slug ?>" class="<?= $style['card'] ?>">
        <div class="<?= $style['imageWrapper'] ?>">
            <?= ImageHelper::responsiveThumbnail(
                $thumbnail,
                $title,
                $opts['isAboveFold'],
                $opts['isAboveFold'],
                $style['imageClass']
            ) ?>
            <?php if ($opts['showBadge'] && $category): ?>
                <div class="<?= $style['badgeWrapper'] ?>">
                    <span class="<?= $style['badgeClass'] ?> <?= $opts['badgeColor'] ?>">
                        <?= $categoryDisplay ?>
                    </span>
                </div>
            <?php endif; ?>
        </div>
        <div class="<?= $style['content'] ?>">
            <h3 class="<?= $style['title'] ?>">
                <?= $title ?>
            </h3>
            <?php if ($opts['showPrice']): ?>
                <p class="<?= $style['price'] ?> <?= $priceClass ?>">
                    <?= $priceText ?>
                </p>
            <?php endif; ?>
        </div>
    </a>
    <?php
    return ob_get_clean();
}

/**
 * Get category badge colors
 * 
 * @return array Mapping of category slugs to Tailwind color classes
 */
function getCategoryBadgeColors(): array
{
    return [
        'wedding' => 'bg-rose-100 text-rose-700',
        'birthday' => 'bg-amber-100 text-amber-700',
        'baby_shower' => 'bg-teal-100 text-teal-700',
        'corporate' => 'bg-slate-200 text-slate-700',
        'anniversary' => 'bg-pink-100 text-pink-700',
        'parties' => 'bg-orange-100 text-orange-700',
        'graduation' => 'bg-indigo-100 text-indigo-700',
        'religious' => 'bg-yellow-100 text-yellow-700',
        'roka' => 'bg-amber-100 text-amber-700',
        'save_the_date' => 'bg-rose-100 text-rose-700',
    ];
}
