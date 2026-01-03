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

    /**
     * Compress and optimize an uploaded image
     * 
     * @param string $sourcePath Path to the original image
     * @param string $destPath Path to save the compressed image
     * @param int $maxWidth Maximum width (default 800px for thumbnails)
     * @param int $maxHeight Maximum height (default 1200px for 9:16 aspect ratio)
     * @param int $quality JPEG/WebP quality (0-100, default 85)
     * @param bool $convertToWebP Whether to convert to WebP format
     * @return array ['success' => bool, 'path' => string, 'size_before' => int, 'size_after' => int, 'error' => string]
     */
    public static function compressImage(
        string $sourcePath,
        string $destPath,
        int $maxWidth = 800,
        int $maxHeight = 1200,
        int $quality = 85,
        bool $convertToWebP = true
    ): array {
        $result = [
            'success' => false,
            'path' => $destPath,
            'size_before' => 0,
            'size_after' => 0,
            'error' => ''
        ];

        // Check if source file exists
        if (!file_exists($sourcePath)) {
            $result['error'] = 'Source file not found';
            return $result;
        }

        $result['size_before'] = filesize($sourcePath);

        // Get image info
        $imageInfo = getimagesize($sourcePath);
        if ($imageInfo === false) {
            $result['error'] = 'Invalid image file';
            return $result;
        }

        $originalWidth = $imageInfo[0];
        $originalHeight = $imageInfo[1];
        $mimeType = $imageInfo['mime'];

        // Create image resource based on type
        $sourceImage = null;
        switch ($mimeType) {
            case 'image/jpeg':
                $sourceImage = imagecreatefromjpeg($sourcePath);
                break;
            case 'image/png':
                $sourceImage = imagecreatefrompng($sourcePath);
                break;
            case 'image/gif':
                $sourceImage = imagecreatefromgif($sourcePath);
                break;
            case 'image/webp':
                if (function_exists('imagecreatefromwebp')) {
                    $sourceImage = imagecreatefromwebp($sourcePath);
                }
                break;
            default:
                $result['error'] = 'Unsupported image format: ' . $mimeType;
                return $result;
        }

        if (!$sourceImage) {
            $result['error'] = 'Failed to create image resource';
            return $result;
        }

        // Calculate new dimensions while maintaining aspect ratio
        $ratio = min($maxWidth / $originalWidth, $maxHeight / $originalHeight);

        // Only resize if image is larger than max dimensions
        if ($ratio < 1) {
            $newWidth = (int) round($originalWidth * $ratio);
            $newHeight = (int) round($originalHeight * $ratio);
        } else {
            $newWidth = $originalWidth;
            $newHeight = $originalHeight;
        }

        // Create new image with proper dimensions
        $destImage = imagecreatetruecolor($newWidth, $newHeight);

        // Preserve transparency for PNG/WebP
        if ($mimeType === 'image/png' || $mimeType === 'image/webp') {
            imagealphablending($destImage, false);
            imagesavealpha($destImage, true);
            $transparent = imagecolorallocatealpha($destImage, 0, 0, 0, 127);
            imagefilledrectangle($destImage, 0, 0, $newWidth, $newHeight, $transparent);
        }

        // Resize image with high quality resampling
        imagecopyresampled(
            $destImage,
            $sourceImage,
            0,
            0,
            0,
            0,
            $newWidth,
            $newHeight,
            $originalWidth,
            $originalHeight
        );

        // Determine output format and save
        $outputPath = $destPath;
        $saved = false;

        if ($convertToWebP && function_exists('imagewebp')) {
            // Change extension to .webp
            $pathInfo = pathinfo($destPath);
            $outputPath = $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '.webp';
            $saved = imagewebp($destImage, $outputPath, $quality);
        }

        // Fallback to JPEG if WebP failed or not requested
        if (!$saved) {
            $pathInfo = pathinfo($destPath);
            $ext = strtolower($pathInfo['extension'] ?? 'jpg');

            if ($ext === 'png' && ($mimeType === 'image/png')) {
                // For PNG, use PNG compression (0-9, where 9 is max compression)
                $pngQuality = (int) round((100 - $quality) / 11.1);
                $saved = imagepng($destImage, $destPath, $pngQuality);
                $outputPath = $destPath;
            } else {
                // Default to JPEG
                $outputPath = $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '.jpg';
                $saved = imagejpeg($destImage, $outputPath, $quality);
            }
        }

        // Clean up
        imagedestroy($sourceImage);
        imagedestroy($destImage);

        if ($saved && file_exists($outputPath)) {
            $result['success'] = true;
            $result['path'] = $outputPath;
            $result['size_after'] = filesize($outputPath);
        } else {
            $result['error'] = 'Failed to save compressed image';
        }

        return $result;
    }

    /**
     * Process thumbnail upload with compression
     * 
     * @param array $file $_FILES['thumbnail'] array
     * @param string $uploadDir Directory to save the thumbnail
     * @param string $prefix Filename prefix (e.g., 'template_')
     * @param int $maxWidth Maximum width
     * @param int $maxHeight Maximum height
     * @param int $quality Compression quality (0-100, default 85)
     * @return array ['success' => bool, 'url' => string, 'error' => string, 'compression_stats' => array]
     */
    public static function processThumbnailUpload(
        array $file,
        string $uploadDir,
        string $prefix = 'thumb_',
        int $maxWidth = 800,
        int $maxHeight = 1200,
        int $quality = 85
    ): array {
        $result = [
            'success' => false,
            'url' => '',
            'error' => '',
            'compression_stats' => []
        ];

        // Validate upload
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $maxSize = 10 * 1024 * 1024; // 10MB max for original (will be compressed)

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $uploadErrors = [
                UPLOAD_ERR_INI_SIZE => 'File exceeds server upload limit',
                UPLOAD_ERR_FORM_SIZE => 'File exceeds form limit',
                UPLOAD_ERR_PARTIAL => 'File partially uploaded',
                UPLOAD_ERR_NO_FILE => 'No file uploaded',
                UPLOAD_ERR_NO_TMP_DIR => 'Missing temp folder',
                UPLOAD_ERR_CANT_WRITE => 'Failed to write file',
                UPLOAD_ERR_EXTENSION => 'Upload blocked by extension',
            ];
            $result['error'] = $uploadErrors[$file['error']] ?? 'Unknown upload error';
            return $result;
        }

        if (!in_array($file['type'], $allowedTypes)) {
            $result['error'] = 'Invalid file type. Allowed: JPEG, PNG, GIF, WebP';
            return $result;
        }

        if ($file['size'] > $maxSize) {
            $result['error'] = 'File too large. Maximum 10MB';
            return $result;
        }

        // Create upload directory if needed
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Generate unique filename
        $filename = $prefix . time() . '_' . uniqid();
        $tempDest = $uploadDir . $filename . '_temp.' . pathinfo($file['name'], PATHINFO_EXTENSION);

        // Move uploaded file to temp location
        if (!move_uploaded_file($file['tmp_name'], $tempDest)) {
            $result['error'] = 'Failed to move uploaded file';
            return $result;
        }

        // Compress the image with specified quality
        $finalDest = $uploadDir . $filename . '.webp';
        $compression = self::compressImage($tempDest, $finalDest, $maxWidth, $maxHeight, $quality, true);

        // Delete temp file
        @unlink($tempDest);

        if (!$compression['success']) {
            $result['error'] = 'Compression failed: ' . $compression['error'];
            return $result;
        }

        // Calculate compression ratio
        $compressionRatio = $compression['size_before'] > 0
            ? round((1 - $compression['size_after'] / $compression['size_before']) * 100, 1)
            : 0;

        $result['success'] = true;
        $result['url'] = str_replace($uploadDir, '', $compression['path']);
        $result['compression_stats'] = [
            'original_size' => $compression['size_before'],
            'compressed_size' => $compression['size_after'],
            'compression_ratio' => $compressionRatio . '%',
            'format' => pathinfo($compression['path'], PATHINFO_EXTENSION)
        ];

        return $result;
    }
}
