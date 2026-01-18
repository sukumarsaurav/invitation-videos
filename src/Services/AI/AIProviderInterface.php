<?php
/**
 * AI Provider Interface
 * 
 * Abstract interface for AI image generation providers.
 * Allows swapping between OpenAI, Stability AI, Replicate, etc.
 */

namespace InvitationVideos\Services\AI;

interface AIProviderInterface
{
    /**
     * Generate an image from a prompt
     * 
     * @param string $prompt The generation prompt describing the desired output
     * @param string|null $referenceImageUrl URL of user's photo for reference (for providers that support it)
     * @param array $options Provider-specific options (size, quality, model, etc.)
     * @return array [
     *     'success' => bool,
     *     'image_url' => string|null,  // URL of generated image
     *     'job_id' => string|null,     // External job ID (for async providers)
     *     'error' => string|null       // Error message if failed
     * ]
     */
    public function generateImage(string $prompt, ?string $referenceImageUrl = null, array $options = []): array;

    /**
     * Check the status of an async generation job
     * 
     * @param string $jobId The job ID returned from generateImage
     * @return array [
     *     'status' => 'pending'|'processing'|'completed'|'failed',
     *     'image_url' => string|null,  // Available if completed
     *     'error' => string|null       // Available if failed
     * ]
     */
    public function checkJobStatus(string $jobId): array;

    /**
     * Get the provider name identifier
     * 
     * @return string e.g., 'openai', 'stability', 'replicate'
     */
    public function getProviderName(): string;

    /**
     * Check if this provider is properly configured (API keys set, etc.)
     * 
     * @return bool
     */
    public function isConfigured(): bool;

    /**
     * Get estimated cost per image in cents
     * 
     * @return int Cost in cents (e.g., 8 for $0.08)
     */
    public function getEstimatedCostCents(): int;
}
