<?php
/**
 * Template Controller
 * 
 * Handles template-related API requests
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../Core/Security.php';
require_once __DIR__ . '/../Form/DynamicFormRenderer.php';

class TemplateController
{
    private DynamicFormRenderer $formRenderer;

    public function __construct()
    {
        $this->formRenderer = new DynamicFormRenderer();
    }

    /**
     * Get template fields as JSON
     */
    public function getFields(int $templateId): void
    {
        header('Content-Type: application/json');

        try {
            $fields = $this->formRenderer->getFields($templateId);

            // Add options for select/radio fields
            foreach ($fields as &$field) {
                if (in_array($field['field_type'], ['select', 'radio', 'checkbox'])) {
                    $field['options'] = $this->formRenderer->getFieldOptions($field['id']);
                }
            }

            echo json_encode([
                'success' => true,
                'fields' => $fields
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Handle form submission and create order
     */
    public function submitCustomization(int $templateId): void
    {
        header('Content-Type: application/json');

        // Validate CSRF
        if (!Security::validateCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Invalid security token']);
            return;
        }

        try {
            // Validate form data
            $errors = $this->formRenderer->validate($templateId, $_POST, $_FILES);

            if (!empty($errors)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'errors' => $errors]);
                return;
            }

            // Get template
            $template = Database::fetchOne("SELECT * FROM templates WHERE id = ?", [$templateId]);

            if (!$template) {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Template not found']);
                return;
            }

            // Handle file uploads
            $uploadedFiles = $this->handleFileUploads($_FILES);

            // Merge form data with uploaded files
            $customizationData = array_merge($_POST, $uploadedFiles);
            unset($customizationData[CSRF_TOKEN_NAME]);

            // Create order
            $orderNumber = 'ORD-' . strtoupper(bin2hex(random_bytes(4)));
            $userId = $_SESSION['user_id'] ?? null;

            Database::query(
                "INSERT INTO orders (user_id, template_id, order_number, amount, currency, customization_data, status) 
                 VALUES (?, ?, ?, ?, 'USD', ?, 'pending')",
                [
                    $userId,
                    $templateId,
                    $orderNumber,
                    $template['price_usd'],
                    json_encode($customizationData)
                ]
            );

            $orderId = Database::lastInsertId();

            echo json_encode([
                'success' => true,
                'order_id' => $orderId,
                'redirect' => '/checkout/' . $orderId
            ]);

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Handle file uploads
     */
    private function handleFileUploads(array $files): array
    {
        $uploadedFiles = [];

        foreach ($files as $fieldName => $file) {
            if ($file['error'] === UPLOAD_ERR_OK) {
                // Generate unique filename
                $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                $filename = uniqid() . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
                $uploadPath = UPLOAD_PATH . $filename;

                // Move uploaded file
                if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                    $uploadedFiles[$fieldName] = '/uploads/' . $filename;
                }
            }
        }

        return $uploadedFiles;
    }
}
