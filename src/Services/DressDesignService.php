<?php
/**
 * Dress Design Service
 * 
 * CRUD operations for dress designs, colors, and AI prompts.
 * Used by admin panel and frontend for dress selection.
 */

namespace InvitationVideos\Services;

require_once __DIR__ . '/../../config/database.php';

class DressDesignService
{
    /**
     * Get all dress designs
     */
    public function getAllDesigns(bool $activeOnly = true): array
    {
        $sql = "SELECT d.*, 
                (SELECT COUNT(*) FROM dress_colors WHERE dress_id = d.id AND is_active = 1) as color_count,
                (SELECT COUNT(*) FROM template_dress_designs WHERE dress_id = d.id) as template_count
                FROM dress_designs d";

        if ($activeOnly) {
            $sql .= " WHERE d.is_active = 1";
        }

        $sql .= " ORDER BY d.display_order, d.name";

        return \Database::fetchAll($sql);
    }

    /**
     * Get a single dress design by ID
     */
    public function getDesignById(int $id): ?array
    {
        return \Database::fetchOne(
            "SELECT * FROM dress_designs WHERE id = ?",
            [$id]
        );
    }

    /**
     * Get dress designs available for a specific template
     */
    public function getDesignsForTemplate(int $templateId): array
    {
        return \Database::fetchAll(
            "SELECT d.*, tdd.display_order as template_order
             FROM dress_designs d
             JOIN template_dress_designs tdd ON d.id = tdd.dress_id
             WHERE tdd.template_id = ? AND d.is_active = 1
             ORDER BY tdd.display_order, d.name",
            [$templateId]
        );
    }

    /**
     * Get all colors for a dress design
     */
    public function getColorsForDress(int $dressId, bool $activeOnly = true): array
    {
        $sql = "SELECT * FROM dress_colors WHERE dress_id = ?";

        if ($activeOnly) {
            $sql .= " AND is_active = 1";
        }

        $sql .= " ORDER BY display_order, name";

        return \Database::fetchAll($sql, [$dressId]);
    }

    /**
     * Get a specific color by ID
     */
    public function getColorById(int $colorId): ?array
    {
        return \Database::fetchOne(
            "SELECT * FROM dress_colors WHERE id = ?",
            [$colorId]
        );
    }

    /**
     * Create a new dress design
     */
    public function createDesign(array $data): int
    {
        $slug = $this->generateSlug($data['name']);

        // Ensure unique slug
        $existingSlug = \Database::fetchOne(
            "SELECT id FROM dress_designs WHERE slug = ?",
            [$slug]
        );

        if ($existingSlug) {
            $slug .= '-' . time();
        }

        \Database::query(
            "INSERT INTO dress_designs (name, slug, description, thumbnail_url, category, gender, display_order, is_active)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $data['name'],
                $slug,
                $data['description'] ?? null,
                $data['thumbnail_url'] ?? null,
                $data['category'] ?? 'wedding',
                $data['gender'] ?? 'couple',
                $data['display_order'] ?? 0,
                $data['is_active'] ?? 1
            ]
        );

        return (int) \Database::lastInsertId();
    }

    /**
     * Update a dress design
     */
    public function updateDesign(int $id, array $data): bool
    {
        $fields = [];
        $values = [];

        $allowedFields = ['name', 'description', 'thumbnail_url', 'category', 'gender', 'display_order', 'is_active'];

        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "{$field} = ?";
                $values[] = $data[$field];
            }
        }

        if (empty($fields)) {
            return false;
        }

        $values[] = $id;

        \Database::query(
            "UPDATE dress_designs SET " . implode(', ', $fields) . " WHERE id = ?",
            $values
        );

        return true;
    }

    /**
     * Delete a dress design
     */
    public function deleteDesign(int $id): bool
    {
        \Database::query("DELETE FROM dress_designs WHERE id = ?", [$id]);
        return true;
    }

    /**
     * Add a color to a dress design
     */
    public function addColor(int $dressId, array $data): int
    {
        \Database::query(
            "INSERT INTO dress_colors (dress_id, name, hex_code, thumbnail_url, display_order, is_active)
             VALUES (?, ?, ?, ?, ?, ?)",
            [
                $dressId,
                $data['name'],
                $data['hex_code'] ?? '#000000',
                $data['thumbnail_url'] ?? null,
                $data['display_order'] ?? 0,
                $data['is_active'] ?? 1
            ]
        );

        return (int) \Database::lastInsertId();
    }

    /**
     * Update a color
     */
    public function updateColor(int $colorId, array $data): bool
    {
        $fields = [];
        $values = [];

        $allowedFields = ['name', 'hex_code', 'thumbnail_url', 'display_order', 'is_active'];

        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "{$field} = ?";
                $values[] = $data[$field];
            }
        }

        if (empty($fields)) {
            return false;
        }

        $values[] = $colorId;

        \Database::query(
            "UPDATE dress_colors SET " . implode(', ', $fields) . " WHERE id = ?",
            $values
        );

        return true;
    }

    /**
     * Delete a color
     */
    public function deleteColor(int $colorId): bool
    {
        \Database::query("DELETE FROM dress_colors WHERE id = ?", [$colorId]);
        return true;
    }

    /**
     * Get AI prompt for a dress/color combination
     */
    public function getPrompt(int $dressId, ?int $colorId = null): ?array
    {
        if ($colorId) {
            $prompt = \Database::fetchOne(
                "SELECT * FROM dress_ai_prompts WHERE dress_id = ? AND color_id = ?",
                [$dressId, $colorId]
            );

            if ($prompt) {
                return $prompt;
            }
        }

        // Fallback to dress default prompt
        return \Database::fetchOne(
            "SELECT * FROM dress_ai_prompts WHERE dress_id = ? AND color_id IS NULL",
            [$dressId]
        );
    }

    /**
     * Get all prompts for a dress (including all color variations)
     */
    public function getPromptsForDress(int $dressId): array
    {
        return \Database::fetchAll(
            "SELECT p.*, c.name as color_name, c.hex_code
             FROM dress_ai_prompts p
             LEFT JOIN dress_colors c ON p.color_id = c.id
             WHERE p.dress_id = ?
             ORDER BY p.color_id IS NULL DESC, c.display_order",
            [$dressId]
        );
    }

    /**
     * Set or update AI prompt for a dress/color combination
     */
    public function setPrompt(int $dressId, ?int $colorId, string $promptText, ?string $negativePrompt = null, ?string $stylePreset = null): int
    {
        // Check if prompt exists
        $existing = \Database::fetchOne(
            "SELECT id FROM dress_ai_prompts WHERE dress_id = ? AND color_id <=> ?",
            [$dressId, $colorId]
        );

        if ($existing) {
            // Update existing prompt
            \Database::query(
                "UPDATE dress_ai_prompts 
                 SET prompt_text = ?, negative_prompt = ?, style_preset = COALESCE(?, style_preset)
                 WHERE id = ?",
                [$promptText, $negativePrompt, $stylePreset, $existing['id']]
            );
            return (int) $existing['id'];
        }

        // Create new prompt
        \Database::query(
            "INSERT INTO dress_ai_prompts (dress_id, color_id, prompt_text, negative_prompt, style_preset)
             VALUES (?, ?, ?, ?, ?)",
            [$dressId, $colorId, $promptText, $negativePrompt, $stylePreset ?? 'caricature']
        );

        return (int) \Database::lastInsertId();
    }

    /**
     * Delete a prompt
     */
    public function deletePrompt(int $promptId): bool
    {
        \Database::query("DELETE FROM dress_ai_prompts WHERE id = ?", [$promptId]);
        return true;
    }

    /**
     * Assign a dress design to a template
     */
    public function assignToTemplate(int $templateId, int $dressId, int $displayOrder = 0): void
    {
        \Database::query(
            "INSERT INTO template_dress_designs (template_id, dress_id, display_order)
             VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE display_order = VALUES(display_order)",
            [$templateId, $dressId, $displayOrder]
        );
    }

    /**
     * Remove a dress design from a template
     */
    public function removeFromTemplate(int $templateId, int $dressId): void
    {
        \Database::query(
            "DELETE FROM template_dress_designs WHERE template_id = ? AND dress_id = ?",
            [$templateId, $dressId]
        );
    }

    /**
     * Get all templates that have a specific dress assigned
     */
    public function getTemplatesForDress(int $dressId): array
    {
        return \Database::fetchAll(
            "SELECT t.*, tdd.display_order
             FROM templates t
             JOIN template_dress_designs tdd ON t.id = tdd.template_id
             WHERE tdd.dress_id = ?
             ORDER BY t.title",
            [$dressId]
        );
    }

    /**
     * Sync dress assignments for a template (replace all)
     */
    public function syncTemplateDesigns(int $templateId, array $dressIds): void
    {
        // Remove all existing assignments
        \Database::query(
            "DELETE FROM template_dress_designs WHERE template_id = ?",
            [$templateId]
        );

        // Add new assignments
        foreach ($dressIds as $order => $dressId) {
            $this->assignToTemplate($templateId, (int) $dressId, $order);
        }
    }

    /**
     * Generate URL-safe slug from name
     */
    private function generateSlug(string $name): string
    {
        $slug = strtolower(trim($name));
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        $slug = trim($slug, '-');
        return $slug ?: 'dress-' . time();
    }

    /**
     * Check if a template has AI caricature enabled with dress designs assigned
     */
    public function isTemplateCaricatureReady(int $templateId): bool
    {
        $template = \Database::fetchOne(
            "SELECT ai_caricature_enabled FROM templates WHERE id = ?",
            [$templateId]
        );

        if (!$template || !$template['ai_caricature_enabled']) {
            return false;
        }

        // Check if any designs are assigned
        $count = \Database::fetchOne(
            "SELECT COUNT(*) as cnt FROM template_dress_designs WHERE template_id = ?",
            [$templateId]
        );

        return ($count['cnt'] ?? 0) > 0;
    }
}
