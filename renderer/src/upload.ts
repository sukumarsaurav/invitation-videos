/**
 * Boomerang Upload Logic
 * 
 * Uploads rendered videos back to the Hostinger PHP webhook.
 */

import fs from 'fs';
import path from 'path';
import FormData from 'form-data';

// Retry configuration
const MAX_RETRIES = 3;
const INITIAL_DELAY_MS = 1000;

/**
 * Sleep utility
 */
function sleep(ms: number): Promise<void> {
    return new Promise(resolve => setTimeout(resolve, ms));
}

/**
 * Upload the rendered video to the PHP webhook (boomerang) with retry logic
 */
export async function uploadToHostinger(
    callbackUrl: string,
    videoPath: string,
    orderId: number,
    secretKey: string
): Promise<void> {
    console.log(`   📤 Uploading to: ${callbackUrl}`);

    const fileSize = fs.statSync(videoPath).size;
    const fileSizeMB = (fileSize / 1024 / 1024).toFixed(2);
    console.log(`   📦 File size: ${fileSizeMB} MB`);

    let lastError: Error | null = null;

    for (let attempt = 1; attempt <= MAX_RETRIES; attempt++) {
        try {
            const formData = new FormData();
            formData.append('video', fs.createReadStream(videoPath), {
                filename: path.basename(videoPath),
                contentType: 'video/mp4',
            });
            formData.append('order_id', orderId.toString());
            formData.append('secret_key', secretKey);

            // Use AbortController for timeout
            const controller = new AbortController();
            const timeout = setTimeout(() => controller.abort(), 5 * 60 * 1000); // 5 min timeout

            const response = await fetch(callbackUrl, {
                method: 'POST',
                headers: {
                    'X-Renderer-Secret': secretKey,
                    ...formData.getHeaders(),
                },
                body: formData as unknown as BodyInit,
                signal: controller.signal,
            });

            clearTimeout(timeout);

            if (!response.ok) {
                const errorText = await response.text();
                throw new Error(`HTTP ${response.status}: ${errorText}`);
            }

            const result = await response.json();

            if (!result.success) {
                throw new Error(`Upload rejected: ${result.error || 'Unknown error'}`);
            }

            console.log(`   ✅ Upload complete: ${result.video_url || 'success'}`);
            return; // Success!

        } catch (error) {
            lastError = error instanceof Error ? error : new Error(String(error));
            console.warn(`   ⚠️ Attempt ${attempt}/${MAX_RETRIES} failed: ${lastError.message}`);

            if (attempt < MAX_RETRIES) {
                const delay = INITIAL_DELAY_MS * Math.pow(2, attempt - 1);
                console.log(`   ⏳ Retrying in ${delay}ms...`);
                await sleep(delay);
            }
        }
    }

    throw new Error(`Upload failed after ${MAX_RETRIES} attempts: ${lastError?.message}`);
}

/**
 * Update order status via PHP API
 */
export async function updateOrderStatus(
    statusUrl: string,
    orderId: number,
    status: 'processing' | 'completed' | 'failed',
    secretKey: string
): Promise<void> {
    try {
        const response = await fetch(statusUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `ApiKey ${secretKey}`,
            },
            body: JSON.stringify({
                order_id: orderId,
                status: status,
            }),
        });

        if (!response.ok) {
            console.warn(`   ⚠️ Status update failed: ${response.status}`);
        } else {
            console.log(`   ✅ Status updated to: ${status}`);
        }
    } catch (error) {
        console.warn(`   ⚠️ Failed to update status:`, error);
    }
}
