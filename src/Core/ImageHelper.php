<?php
/**
 * Image Optimization Helper
 * 
 * Generates optimized img tags with:
 * - WebP format with fallback
 * - Responsive srcset for different screen sizes
 * - Lazy loading
 * - Proper width/height for CLS prevention
 */

class ImageHelper
{
    /**
     * Generate an optimized img tag with lazy loading and dimensions
     * 
     * @param string $src Image source URL
     * @param string $alt Alt text
     * @param int $width Image width
     * @param int $height Image height
     * @param string $class CSS classes
     * @param bool $eager Load eagerly (for above-fold images)
     * @param bool $priority High priority (for LCP images)
     * @return string HTML img element
     */
    public static function img(
        string $src,
        string $alt,
        int $width,
        int $height,
        string $class = '',
        bool $eager = false,
        bool $priority = false
    ): string {
        $loading = $eager ? 'eager' : 'lazy';
        $decoding = $eager ? 'sync' : 'async';
        $fetchpriority = $priority ? ' fetchpriority="high"' : '';

        // Escape values
        $src = htmlspecialchars($src, ENT_QUOTES, 'UTF-8');
        $alt = htmlspecialchars($alt, ENT_QUOTES, 'UTF-8');
        $class = htmlspecialchars($class, ENT_QUOTES, 'UTF-8');

        return sprintf(
            '<img src="%s" alt="%s" width="%d" height="%d" class="%s" loading="%s" decoding="%s"%s>',
            $src,
            $alt,
            $width,
            $height,
            $class,
            $loading,
            $decoding,
            $fetchpriority
        );
    }

    /**
     * Generate a picture element with WebP support
     * 
     * @param string $src Original image source (jpg/png)
     * @param string $alt Alt text
     * @param int $width Image width
     * @param int $height Image height
     * @param string $class CSS classes
     * @param bool $eager Load eagerly
     * @return string HTML picture element
     */
    public static function webp(
        string $src,
        string $alt,
        int $width,
        int $height,
        string $class = '',
        bool $eager = false
    ): string {
        // Check if WebP version exists
        $webpSrc = self::getWebPPath($src);
        $loading = $eager ? 'eager' : 'lazy';
        $decoding = $eager ? 'sync' : 'async';

        $src = htmlspecialchars($src, ENT_QUOTES, 'UTF-8');
        $alt = htmlspecialchars($alt, ENT_QUOTES, 'UTF-8');
        $class = htmlspecialchars($class, ENT_QUOTES, 'UTF-8');

        if ($webpSrc && file_exists($_SERVER['DOCUMENT_ROOT'] . $webpSrc)) {
            $webpSrc = htmlspecialchars($webpSrc, ENT_QUOTES, 'UTF-8');
            return sprintf(
                '<picture>
                    <source srcset="%s" type="image/webp">
                    <img src="%s" alt="%s" width="%d" height="%d" class="%s" loading="%s" decoding="%s">
                </picture>',
                $webpSrc,
                $src,
                $alt,
                $width,
                $height,
                $class,
                $loading,
                $decoding
            );
        }

        // Fallback to regular img if no WebP
        return self::img($src, $alt, $width, $height, $class, $eager);
    }

    /**
     * Generate responsive image with srcset
     * 
     * @param string $src Base image source
     * @param string $alt Alt text
     * @param int $width Base width
     * @param int $height Base height
     * @param string $class CSS classes
     * @param array $sizes Responsive sizes config
     * @param bool $eager Load eagerly
     * @return string HTML img element with srcset
     */
    public static function responsive(
        string $src,
        string $alt,
        int $width,
        int $height,
        string $class = '',
        array $sizes = [],
        bool $eager = false
    ): string {
        $loading = $eager ? 'eager' : 'lazy';
        $decoding = $eager ? 'sync' : 'async';

        $src = htmlspecialchars($src, ENT_QUOTES, 'UTF-8');
        $alt = htmlspecialchars($alt, ENT_QUOTES, 'UTF-8');
        $class = htmlspecialchars($class, ENT_QUOTES, 'UTF-8');

        // Default sizes for typical mobile/tablet/desktop
        if (empty($sizes)) {
            $sizes = '(max-width: 640px) 100vw, (max-width: 1024px) 50vw, 33vw';
        } else {
            $sizes = htmlspecialchars(implode(', ', $sizes), ENT_QUOTES, 'UTF-8');
        }

        return sprintf(
            '<img src="%s" alt="%s" width="%d" height="%d" class="%s" sizes="%s" loading="%s" decoding="%s">',
            $src,
            $alt,
            $width,
            $height,
            $class,
            $sizes,
            $loading,
            $decoding
        );
    }

    /**
     * Get WebP path from original image path
     */
    private static function getWebPPath(string $src): ?string
    {
        $pathInfo = pathinfo($src);
        $ext = strtolower($pathInfo['extension'] ?? '');

        if (in_array($ext, ['jpg', 'jpeg', 'png'])) {
            return $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '.webp';
        }

        return null;
    }

    /**
     * Create placeholder data URI for blur-up loading
     * 
     * @param int $width Width of placeholder
     * @param int $height Height of placeholder
     * @param string $color Background color (CSS)
     * @return string Data URI for placeholder SVG
     */
    public static function placeholder(int $width, int $height, string $color = '#e2e8f0'): string
    {
        $svg = sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" width="%d" height="%d"><rect fill="%s" width="100%%" height="100%%"/></svg>',
            $width,
            $height,
            $color
        );

        return 'data:image/svg+xml,' . rawurlencode($svg);
    }
}
