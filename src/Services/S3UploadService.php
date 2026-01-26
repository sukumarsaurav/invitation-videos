<?php
/**
 * S3 Upload Service
 * 
 * Handles uploading order files to S3 after payment for fast Lambda access.
 * Files are stored in order-specific folders with automatic cleanup after expiry.
 */

namespace InvitationVideos\Services;

use Aws\S3\S3Client;
use Aws\Exception\AwsException;

class S3UploadService
{
    private static ?S3Client $client = null;

    private const BUCKET = S3_USER_UPLOADS_BUCKET;
    private const REGION = AWS_DEFAULT_REGION;

    /**
     * Get S3 client (singleton)
     */
    private static function getClient(): S3Client
    {
        if (self::$client === null) {
            self::$client = new S3Client([
                'version' => 'latest',
                'region' => self::REGION,
                'credentials' => [
                    'key' => AWS_ACCESS_KEY_ID,
                    'secret' => AWS_SECRET_ACCESS_KEY,
                ],
            ]);
        }

        return self::$client;
    }

    /**
     * Upload a local file to S3
     * 
     * @param string $localPath Full path to local file
     * @param string $orderNumber Order number for folder naming
     * @param string $fieldName Field name (e.g., 'bride_photo', 'music')
     * @return array ['success' => bool, 'url' => string|null, 'error' => string|null]
     */
    public static function uploadOrderFile(
        string $localPath,
        string $orderNumber,
        string $fieldName
    ): array {
        if (!file_exists($localPath)) {
            return ['success' => false, 'url' => null, 'error' => 'File not found: ' . $localPath];
        }

        // Generate S3 key: orders/{order_number}/{field_name}_{timestamp}.{ext}
        $extension = pathinfo($localPath, PATHINFO_EXTENSION);
        $timestamp = time();
        $s3Key = "orders/{$orderNumber}/{$fieldName}_{$timestamp}.{$extension}";

        try {
            $client = self::getClient();

            // Determine content type
            $contentType = mime_content_type($localPath) ?: 'application/octet-stream';

            // Upload file
            $result = $client->putObject([
                'Bucket' => self::BUCKET,
                'Key' => $s3Key,
                'SourceFile' => $localPath,
                'ContentType' => $contentType,
                'ACL' => 'private', // Private, use pre-signed URLs for access
            ]);

            $s3Url = $result['ObjectURL'];

            error_log("S3 Upload: {$localPath} -> {$s3Key}");

            return [
                'success' => true,
                'url' => $s3Url,
                's3_key' => $s3Key,
                'bucket' => self::BUCKET,
                'error' => null
            ];

        } catch (AwsException $e) {
            error_log("S3 Upload Error: " . $e->getMessage());
            return [
                'success' => false,
                'url' => null,
                'error' => $e->getAwsErrorMessage() ?: $e->getMessage()
            ];
        }
    }

    /**
     * Generate a pre-signed URL for temporary access (renderer can use this)
     * 
     * @param string $s3Key The S3 object key
     * @param int $expiryMinutes URL expiry time in minutes (default: 60)
     * @return string|null Pre-signed URL or null on error
     */
    public static function getPresignedUrl(string $s3Key, int $expiryMinutes = 60): ?string
    {
        try {
            $client = self::getClient();

            $cmd = $client->getCommand('GetObject', [
                'Bucket' => self::BUCKET,
                'Key' => $s3Key,
            ]);

            $request = $client->createPresignedRequest($cmd, "+{$expiryMinutes} minutes");

            return (string) $request->getUri();

        } catch (AwsException $e) {
            error_log("S3 Presigned URL Error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Upload all order uploads to S3
     * Called after payment confirmation
     * 
     * @param int $orderId The order ID
     * @return array ['success' => bool, 'uploaded' => int, 'failed' => int]
     */
    public static function syncOrderUploadsToS3(int $orderId): array
    {
        require_once __DIR__ . '/../../config/database.php';

        // Get order info
        $order = \Database::fetchOne(
            "SELECT order_number FROM orders WHERE id = ?",
            [$orderId]
        );

        if (!$order) {
            return ['success' => false, 'uploaded' => 0, 'failed' => 0, 'error' => 'Order not found'];
        }

        $orderNumber = $order['order_number'];

        // Get all uploads for this order
        $uploads = \Database::fetchAll(
            "SELECT * FROM order_uploads WHERE order_id = ?",
            [$orderId]
        );

        $uploaded = 0;
        $failed = 0;

        foreach ($uploads as $upload) {
            // Skip if already has S3 URL
            if (!empty($upload['s3_url'])) {
                continue;
            }

            // Get full local path
            $localPath = __DIR__ . '/../../' . ltrim($upload['file_path'], '/');

            if (!file_exists($localPath)) {
                error_log("S3 Sync: Local file not found for upload #{$upload['id']}: {$localPath}");
                $failed++;
                continue;
            }

            // Upload to S3
            $result = self::uploadOrderFile(
                $localPath,
                $orderNumber,
                $upload['field_name']
            );

            if ($result['success']) {
                // Update database with S3 URL
                \Database::query(
                    "UPDATE order_uploads SET s3_url = ?, s3_bucket = ? WHERE id = ?",
                    [$result['url'], $result['bucket'], $upload['id']]
                );
                $uploaded++;
            } else {
                error_log("S3 Sync: Failed to upload #{$upload['id']}: " . $result['error']);
                $failed++;
            }
        }

        error_log("S3 Sync: Order #{$orderId} - Uploaded: {$uploaded}, Failed: {$failed}");

        return [
            'success' => $failed === 0,
            'uploaded' => $uploaded,
            'failed' => $failed
        ];
    }

    /**
     * Delete all S3 files for an order (for cleanup after expiry)
     * 
     * @param string $orderNumber Order number
     * @return bool Success
     */
    public static function deleteOrderFolder(string $orderNumber): bool
    {
        try {
            $client = self::getClient();
            $prefix = "orders/{$orderNumber}/";

            // List all objects with this prefix
            $objects = $client->listObjects([
                'Bucket' => self::BUCKET,
                'Prefix' => $prefix,
            ]);

            $keys = [];
            foreach (($objects['Contents'] ?? []) as $object) {
                $keys[] = ['Key' => $object['Key']];
            }

            if (empty($keys)) {
                return true; // Nothing to delete
            }

            // Delete all objects
            $client->deleteObjects([
                'Bucket' => self::BUCKET,
                'Delete' => ['Objects' => $keys],
            ]);

            error_log("S3 Cleanup: Deleted " . count($keys) . " files for order {$orderNumber}");

            return true;

        } catch (AwsException $e) {
            error_log("S3 Cleanup Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if S3 is configured and accessible
     * 
     * @return bool
     */
    public static function isConfigured(): bool
    {
        return !empty(AWS_ACCESS_KEY_ID) &&
            !empty(AWS_SECRET_ACCESS_KEY) &&
            !empty(S3_USER_UPLOADS_BUCKET);
    }
}
