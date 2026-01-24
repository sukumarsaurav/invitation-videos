<?php
/**
 * Simple Logger Utility
 * 
 * Provides consistent logging with environment-aware debug output.
 * Debug logs only appear when APP_DEBUG is true.
 */

class Logger
{
    /**
     * Log debug message (only in development)
     */
    public static function debug(string $message, array $context = []): void
    {
        if (defined('APP_DEBUG') && APP_DEBUG) {
            self::log('DEBUG', $message, $context);
        }
    }

    /**
     * Log info message
     */
    public static function info(string $message, array $context = []): void
    {
        self::log('INFO', $message, $context);
    }

    /**
     * Log warning message
     */
    public static function warning(string $message, array $context = []): void
    {
        self::log('WARNING', $message, $context);
    }

    /**
     * Log error message (always logged)
     */
    public static function error(string $message, array $context = []): void
    {
        self::log('ERROR', $message, $context);
    }

    /**
     * Internal log method
     */
    private static function log(string $level, string $message, array $context): void
    {
        $contextStr = !empty($context) ? ' ' . json_encode($context) : '';
        error_log("[{$level}] {$message}{$contextStr}");
    }
}
