<?php
/**
 * Draft Order Service
 * 
 * Manages draft orders (unpaid carts) before they become real orders.
 * Handles creation, retrieval, conversion to orders, and cleanup.
 */

namespace InvitationVideos\Services;

require_once __DIR__ . '/../../config/database.php';

class DraftOrderService
{
    /** Draft expiry in days */
    private const EXPIRY_DAYS = 7;

    /**
     * Create a new draft order
     * 
     * @param int $templateId Template ID
     * @param float $amount Order amount
     * @param string $currency 'USD' or 'INR'
     * @param array $customizationData Form field values
     * @param int|null $userId User ID (null for guests)
     * @return array Draft order data with token
     */
    public function createDraft(
        int $templateId,
        float $amount,
        string $currency,
        array $customizationData,
        ?int $userId = null
    ): array {
        $draftToken = $this->generateToken();
        $expiresAt = date('Y-m-d H:i:s', strtotime('+' . self::EXPIRY_DAYS . ' days'));

        \Database::query(
            "INSERT INTO draft_orders (draft_token, user_id, template_id, amount, currency, customization_data, expires_at)
             VALUES (?, ?, ?, ?, ?, ?, ?)",
            [
                $draftToken,
                $userId,
                $templateId,
                $amount,
                $currency,
                json_encode($customizationData),
                $expiresAt
            ]
        );

        $draftId = \Database::lastInsertId();

        return [
            'id' => $draftId,
            'draft_token' => $draftToken,
            'expires_at' => $expiresAt
        ];
    }

    /**
     * Get draft order by token
     * 
     * @param string $token Draft token
     * @return array|null Draft order data or null if not found/expired
     */
    public function getDraftByToken(string $token): ?array
    {
        $draft = \Database::fetchOne(
            "SELECT d.*, t.title as template_title, t.thumbnail_url, t.slug as template_slug
             FROM draft_orders d
             JOIN templates t ON d.template_id = t.id
             WHERE d.draft_token = ? AND d.expires_at > NOW()",
            [$token]
        );

        if ($draft && isset($draft['customization_data'])) {
            $draft['customization_data'] = json_decode($draft['customization_data'], true);
        }

        return $draft;
    }

    /**
     * Get draft order by Razorpay order ID
     * 
     * @param string $razorpayOrderId Razorpay order ID
     * @return array|null Draft order data
     */
    public function getDraftByRazorpayOrderId(string $razorpayOrderId): ?array
    {
        $draft = \Database::fetchOne(
            "SELECT * FROM draft_orders WHERE razorpay_order_id = ?",
            [$razorpayOrderId]
        );

        if ($draft && isset($draft['customization_data'])) {
            $draft['customization_data'] = json_decode($draft['customization_data'], true);
        }

        return $draft;
    }

    /**
     * Get draft order by Stripe payment intent
     * 
     * @param string $paymentIntentId Stripe PaymentIntent ID
     * @return array|null Draft order data
     */
    public function getDraftByStripePaymentIntent(string $paymentIntentId): ?array
    {
        $draft = \Database::fetchOne(
            "SELECT * FROM draft_orders WHERE stripe_payment_intent = ?",
            [$paymentIntentId]
        );

        if ($draft && isset($draft['customization_data'])) {
            $draft['customization_data'] = json_decode($draft['customization_data'], true);
        }

        return $draft;
    }

    /**
     * Update draft with payment gateway reference
     * 
     * @param int $draftId Draft order ID
     * @param string $gateway 'razorpay' or 'stripe'
     * @param string $gatewayOrderId Gateway-specific order/intent ID
     */
    public function updatePaymentReference(int $draftId, string $gateway, string $gatewayOrderId): void
    {
        if ($gateway === 'razorpay') {
            \Database::query(
                "UPDATE draft_orders SET razorpay_order_id = ? WHERE id = ?",
                [$gatewayOrderId, $draftId]
            );
        } else {
            \Database::query(
                "UPDATE draft_orders SET stripe_payment_intent = ? WHERE id = ?",
                [$gatewayOrderId, $draftId]
            );
        }
    }

    /**
     * Apply promo code to draft
     * 
     * @param int $draftId Draft order ID
     * @param int $promoCodeId Promo code ID
     * @param float $discountAmount Calculated discount
     * @param float $newAmount New total after discount
     */
    public function applyPromoCode(int $draftId, int $promoCodeId, float $discountAmount, float $newAmount): void
    {
        \Database::query(
            "UPDATE draft_orders SET promo_code_id = ?, discount_amount = ?, amount = ? WHERE id = ?",
            [$promoCodeId, $discountAmount, $newAmount, $draftId]
        );
    }

    /**
     * Convert draft order to real order after successful payment
     * 
     * @param array $draft Draft order data
     * @param string $paymentId Payment ID from gateway
     * @param string $gateway 'razorpay' or 'stripe'
     * @return int New order ID
     */
    public function convertToOrder(array $draft, string $paymentId, string $gateway): int
    {
        // Generate order number
        $orderNumber = 'ORD-' . strtoupper(substr(uniqid(), -8));

        // Create organized order directory
        $orderFilesDir = 'orders/' . $orderNumber;
        $fullOrderDir = UPLOAD_PATH . $orderFilesDir . '/';
        if (!is_dir($fullOrderDir)) {
            mkdir($fullOrderDir, 0755, true);
        }
        error_log("convertToOrder: Created order directory: {$fullOrderDir}");

        // Insert into orders table with files_directory
        \Database::query(
            "INSERT INTO orders (
                order_number, user_id, template_id, amount, currency, 
                customization_data, status, payment_status, order_status,
                payment_gateway, payment_id, razorpay_order_id,
                promo_code_id, discount_amount, files_directory, paid_at
            ) VALUES (?, ?, ?, ?, ?, ?, 'paid', 'paid', 'queued', ?, ?, ?, ?, ?, ?, NOW())",
            [
                $orderNumber,
                $draft['user_id'],
                $draft['template_id'],
                $draft['amount'],
                $draft['currency'],
                is_array($draft['customization_data'])
                ? json_encode($draft['customization_data'])
                : $draft['customization_data'],
                $gateway,
                $paymentId,
                $draft['razorpay_order_id'],
                $draft['promo_code_id'],
                $draft['discount_amount'] ?? 0,
                $orderFilesDir
            ]
        );

        $orderId = (int) \Database::lastInsertId();

        // Move uploaded files from draft directory to order directory
        $this->moveUploads($draft['id'], $orderId, $draft['files_directory'] ?? null, $fullOrderDir);

        // Delete the draft
        $this->deleteDraft($draft['id']);

        // Increment template purchase count
        \Database::query(
            "UPDATE templates SET purchase_count = purchase_count + 1 WHERE id = ?",
            [$draft['template_id']]
        );

        return $orderId;
    }

    /**
     * Move uploads from draft to order
     * 
     * @param int $draftId Draft order ID
     * @param int $orderId New order ID
     * @param string|null $draftFilesDir Draft files directory (relative path)
     * @param string|null $orderDir Order files directory (full path)
     */
    private function moveUploads(int $draftId, int $orderId, ?string $draftFilesDir = null, ?string $orderDir = null): void
    {
        error_log("moveUploads: Starting to move uploads from draft #{$draftId} to order #{$orderId}");

        // Get all draft uploads
        $uploads = \Database::fetchAll(
            "SELECT * FROM draft_order_uploads WHERE draft_id = ?",
            [$draftId]
        );

        error_log("moveUploads: Found " . count($uploads) . " uploads to move");

        foreach ($uploads as $upload) {
            error_log("moveUploads: Processing file '{$upload['stored_filename']}' (type: {$upload['file_type']})");

            $newFilePath = $upload['file_path']; // Default: keep same path

            // If we have both source and destination directories, move the file physically
            if ($draftFilesDir && $orderDir) {
                $oldPath = $upload['file_path'];

                if (file_exists($oldPath)) {
                    $newFilePath = $orderDir . $upload['stored_filename'];

                    if (rename($oldPath, $newFilePath)) {
                        error_log("moveUploads: Moved file from {$oldPath} to {$newFilePath}");
                    } else {
                        error_log("moveUploads: Failed to move file, keeping at original location");
                        $newFilePath = $oldPath;
                    }
                } else {
                    error_log("moveUploads: Source file not found: {$oldPath}");
                }
            }

            try {
                \Database::query(
                    "INSERT INTO order_uploads (order_id, field_name, file_type, original_filename, stored_filename, file_path, mime_type, file_size)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                    [
                        $orderId,
                        $upload['field_name'],
                        $upload['file_type'],
                        $upload['original_filename'],
                        $upload['stored_filename'],
                        $newFilePath,
                        $upload['mime_type'],
                        $upload['file_size']
                    ]
                );
                error_log("moveUploads: Successfully inserted upload for field '{$upload['field_name']}' with path {$newFilePath}");
            } catch (\Exception $e) {
                error_log("moveUploads: ERROR inserting upload - " . $e->getMessage());
            }
        }

        // Delete draft uploads from database
        \Database::query("DELETE FROM draft_order_uploads WHERE draft_id = ?", [$draftId]);
        error_log("moveUploads: Deleted draft uploads for draft #{$draftId}");

        // Clean up empty draft directory
        if ($draftFilesDir) {
            $fullDraftDir = UPLOAD_PATH . $draftFilesDir;
            if (is_dir($fullDraftDir)) {
                $remainingFiles = glob($fullDraftDir . '/*');
                if (empty($remainingFiles)) {
                    rmdir($fullDraftDir);
                    error_log("moveUploads: Removed empty draft directory: {$fullDraftDir}");
                }
            }
        }
    }

    /**
     * Add file upload to draft
     * 
     * @param int $draftId Draft order ID
     * @param string $fieldName Form field name
     * @param array $fileInfo File upload info
     * @param string $storedPath Stored file path
     */
    public function addUpload(int $draftId, string $fieldName, array $fileInfo, string $storedPath): void
    {
        $storedFilename = basename($storedPath);
        $fileType = strpos($fileInfo['type'], 'audio') !== false ? 'music' : 'image';

        \Database::query(
            "INSERT INTO draft_order_uploads (draft_id, field_name, file_type, original_filename, stored_filename, file_path, mime_type, file_size)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $draftId,
                $fieldName,
                $fileType,
                $fileInfo['name'],
                $storedFilename,
                $storedPath,
                $fileInfo['type'],
                $fileInfo['size']
            ]
        );
    }

    /**
     * Delete a draft order
     * 
     * @param int $draftId Draft order ID
     */
    public function deleteDraft(int $draftId): void
    {
        // Cascade delete will remove uploads
        \Database::query("DELETE FROM draft_orders WHERE id = ?", [$draftId]);
    }

    /**
     * Clean up expired drafts and their files
     * 
     * @return int Number of drafts cleaned up
     */
    public function cleanupExpired(): int
    {
        // Get expired drafts with their uploads
        $expiredDrafts = \Database::fetchAll(
            "SELECT d.id, u.file_path
             FROM draft_orders d
             LEFT JOIN draft_order_uploads u ON d.id = u.draft_id
             WHERE d.expires_at < NOW()"
        );

        // Delete files
        $deletedFiles = [];
        foreach ($expiredDrafts as $row) {
            if (!empty($row['file_path']) && !in_array($row['file_path'], $deletedFiles)) {
                if (file_exists($row['file_path'])) {
                    @unlink($row['file_path']);
                }
                $deletedFiles[] = $row['file_path'];
            }
        }

        // Delete expired drafts (cascade deletes uploads)
        $result = \Database::query(
            "DELETE FROM draft_orders WHERE expires_at < NOW()"
        );

        return $result->rowCount();
    }

    /**
     * Generate a cryptographically secure token
     * 
     * @return string 32-character hex token
     */
    private function generateToken(): string
    {
        return bin2hex(random_bytes(16));
    }

    /**
     * Check if a draft token is valid (exists and not expired)
     * 
     * @param string $token Draft token
     * @return bool
     */
    public function isValidToken(string $token): bool
    {
        $count = \Database::fetchOne(
            "SELECT COUNT(*) as cnt FROM draft_orders WHERE draft_token = ? AND expires_at > NOW()",
            [$token]
        );

        return ($count['cnt'] ?? 0) > 0;
    }
}
