<?php
/**
 * GeoLocation Service
 * 
 * Detects country from IP address using ip-api.com (free, no API key required)
 */

class GeoLocationService
{
    private const API_URL = 'http://ip-api.com/json/';

    /**
     * Get country information from IP address
     * 
     * @param string $ip IP address (use $_SERVER['REMOTE_ADDR'])
     * @return array ['country_code' => 'IN', 'country_name' => 'India']
     */
    public static function getCountryFromIP(string $ip): array
    {
        $default = [
            'country_code' => 'US',
            'country_name' => 'United States'
        ];

        // Skip for localhost/private IPs
        if ($ip === '127.0.0.1' || $ip === '::1' || self::isPrivateIP($ip)) {
            return $default;
        }

        try {
            $context = stream_context_create([
                'http' => [
                    'timeout' => 3 // 3 second timeout
                ]
            ]);

            $response = @file_get_contents(self::API_URL . $ip . '?fields=countryCode,country,status', false, $context);

            if ($response === false) {
                return $default;
            }

            $data = json_decode($response, true);

            if (!$data || ($data['status'] ?? '') !== 'success') {
                return $default;
            }

            return [
                'country_code' => $data['countryCode'] ?? 'US',
                'country_name' => $data['country'] ?? 'United States'
            ];
        } catch (Exception $e) {
            return $default;
        }
    }

    /**
     * Check if IP is a private/local IP
     */
    private static function isPrivateIP(string $ip): bool
    {
        return filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) === false;
    }

    /**
     * Get the client's real IP address
     * Handles proxies and load balancers
     */
    public static function getClientIP(): string
    {
        $headers = [
            'HTTP_CF_CONNECTING_IP',     // Cloudflare
            'HTTP_X_FORWARDED_FOR',      // Standard proxy header
            'HTTP_X_REAL_IP',            // Nginx proxy
            'HTTP_CLIENT_IP',            // Shared internet
            'REMOTE_ADDR'                // Fallback
        ];

        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                // X-Forwarded-For can contain multiple IPs
                $ips = explode(',', $_SERVER[$header]);
                $ip = trim($ips[0]);

                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return '127.0.0.1';
    }
}
