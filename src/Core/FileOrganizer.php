<?php

/**
 * File Organization Helper
 * 
 * Manages organized directory structure for templates and orders.
 * - Templates: /uploads/templates/{slug}/
 * - Orders: /uploads/orders/{order_number}/
 * - Drafts: /uploads/drafts/{token_prefix}/
 */

namespace InvitationVideos\Core;

class FileOrganizer
{
    /**
     * Get or create template assets directory
     * 
     * @param string $slug Template slug
     * @return string Full path to template directory
     */
    public static function getTemplateDir(string $slug): string
    {
        $dir = UPLOAD_PATH . 'templates/' . $slug . '/';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        return $dir;
    }

    /**
     * Get template slides directory
     * 
     * @param string $slug Template slug
     * @return string Full path to slides directory
     */
    public static function getTemplateSlidesDir(string $slug): string
    {
        $dir = self::getTemplateDir($slug) . 'slides/';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        return $dir;
    }

    /**
     * Get or create order files directory
     * 
     * @param string $orderNumber Order number (e.g., ORD-185CD75A)
     * @return string Full path to order directory
     */
    public static function getOrderDir(string $orderNumber): string
    {
        $dir = UPLOAD_PATH . 'orders/' . $orderNumber . '/';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        return $dir;
    }

    /**
     * Get relative path for order directory (for database storage)
     * 
     * @param string $orderNumber Order number
     * @return string Relative path (e.g., 'orders/ORD-185CD75A')
     */
    public static function getOrderDirRelative(string $orderNumber): string
    {
        return 'orders/' . $orderNumber;
    }

    /**
     * Get or create draft files directory
     * 
     * @param string $draftToken Draft token (uses first 16 chars)
     * @return string Full path to draft directory
     */
    public static function getDraftDir(string $draftToken): string
    {
        $prefix = substr($draftToken, 0, 16);
        $dir = UPLOAD_PATH . 'drafts/' . $prefix . '/';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        return $dir;
    }

    /**
     * Get relative path for draft directory (for database storage)
     * 
     * @param string $draftToken Draft token
     * @return string Relative path (e.g., 'drafts/7fd30e2543826ce3')
     */
    public static function getDraftDirRelative(string $draftToken): string
    {
        return 'drafts/' . substr($draftToken, 0, 16);
    }

    /**
     * Move file to organized location
     * 
     * @param string $currentPath Current file path
     * @param string $targetDir Target directory (full path)
     * @param string $filename Target filename
     * @return string|false New file path or false on failure
     */
    public static function organizeFile(string $currentPath, string $targetDir, string $filename)
    {
        if (!file_exists($currentPath)) {
            error_log("FileOrganizer: Source file not found: {$currentPath}");
            return false;
        }

        // Ensure target directory exists
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        $targetPath = $targetDir . $filename;

        if (rename($currentPath, $targetPath)) {
            error_log("FileOrganizer: Moved {$currentPath} to {$targetPath}");
            return $targetPath;
        }

        error_log("FileOrganizer: Failed to move {$currentPath} to {$targetPath}");
        return false;
    }

    /**
     * Move all files from one directory to another
     * 
     * @param string $sourceDir Source directory
     * @param string $targetDir Target directory
     * @return int Number of files moved
     */
    public static function moveDirectoryContents(string $sourceDir, string $targetDir): int
    {
        if (!is_dir($sourceDir)) {
            return 0;
        }

        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        $movedCount = 0;
        $files = glob($sourceDir . '*');

        foreach ($files as $file) {
            if (is_file($file)) {
                $filename = basename($file);
                if (rename($file, $targetDir . $filename)) {
                    $movedCount++;
                }
            }
        }

        return $movedCount;
    }

    /**
     * Delete directory and all contents recursively
     * 
     * @param string $dir Directory path
     * @return bool Success
     */
    public static function deleteDirectory(string $dir): bool
    {
        if (!is_dir($dir)) {
            return true;
        }

        $files = array_diff(scandir($dir), ['.', '..']);

        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            if (is_dir($path)) {
                self::deleteDirectory($path);
            } else {
                unlink($path);
            }
        }

        return rmdir($dir);
    }

    /**
     * Get web-accessible URL from file path
     * 
     * @param string $filePath Full file path
     * @return string Web URL (e.g., /uploads/orders/ORD-123/photo.jpg)
     */
    public static function getPublicUrl(string $filePath): string
    {
        // Remove UPLOAD_PATH prefix to get relative path
        $relativePath = str_replace(UPLOAD_PATH, '', $filePath);
        $relativePath = ltrim($relativePath, '/');
        return '/uploads/' . $relativePath;
    }

    /**
     * Get full URL from file path
     * 
     * @param string $filePath Full file path
     * @param string $domain Domain name (default from SERVER)
     * @return string Full URL
     */
    public static function getFullUrl(string $filePath, string $domain = null): string
    {
        $domain = $domain ?? ($_SERVER['HTTP_HOST'] ?? 'invitationvideos.com');
        $publicUrl = self::getPublicUrl($filePath);
        return 'https://' . $domain . $publicUrl;
    }

    /**
     * Get directory size in bytes
     * 
     * @param string $dir Directory path
     * @return int Size in bytes
     */
    public static function getDirectorySize(string $dir): int
    {
        $size = 0;

        if (!is_dir($dir)) {
            return $size;
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($files as $file) {
            if ($file->isFile()) {
                $size += $file->getSize();
            }
        }

        return $size;
    }

    /**
     * Create base directory structure
     * Call this once during setup
     */
    public static function initializeDirectories(): void
    {
        $dirs = [
            UPLOAD_PATH . 'templates/',
            UPLOAD_PATH . 'orders/',
            UPLOAD_PATH . 'drafts/',
        ];

        foreach ($dirs as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
                error_log("FileOrganizer: Created directory {$dir}");
            }
        }
    }

    /**
     * Check if file exists in legacy location (for backwards compatibility)
     * 
     * @param string $filename Filename to check
     * @return string|null Full path if found, null otherwise
     */
    public static function findLegacyFile(string $filename): ?string
    {
        // Check direct upload path (legacy)
        $legacyPath = UPLOAD_PATH . $filename;
        if (file_exists($legacyPath)) {
            return $legacyPath;
        }

        // Check old videos directory structure
        $oldVideosPattern = UPLOAD_PATH . 'videos/*/' . $filename;
        $matches = glob($oldVideosPattern);
        if (!empty($matches)) {
            return $matches[0];
        }

        return null;
    }
}
