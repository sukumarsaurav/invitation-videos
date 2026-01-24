/**
 * Remotion Renderer - Express Server for Cloud Run
 * 
 * "Boomerang" Architecture:
 * 1. Receives render job from PHP (via Cloud Tasks or direct call)
 * 2. Updates status to 'processing' via PHP API
 * 3. Renders video using Remotion
 * 4. Uploads finished video back to PHP webhook (boomerang!)
 * 
 * Environment Variables:
 * - CALLBACK_URL: PHP webhook to receive finished video
 * - RENDERER_SECRET_KEY: Secret for authenticating with PHP
 * - STATUS_URL: PHP endpoint to update order status
 * - PORT: Server port (default 8080)
 */

import express, { Request, Response } from 'express';
import { renderVideo } from './render.js';
import { uploadToHostinger, updateOrderStatus } from './upload.js';
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const app = express();
app.use(express.json());

// Types
interface RenderRequest {
    order_id: number;
    order_number: string;
    template_id: string;
    input_props: Record<string, unknown>;
    callback_url?: string;
    status_url?: string;
    secret_key?: string;
}

// ============================================
// HEALTH CHECK
// ============================================

app.get('/health', (_req: Request, res: Response) => {
    res.json({
        status: 'healthy',
        timestamp: new Date().toISOString(),
        service: 'remotion-renderer',
        version: '1.0.0'
    });
});

app.get('/', (_req: Request, res: Response) => {
    res.json({
        name: 'Remotion Renderer - Boomerang Architecture',
        endpoints: {
            '/health': 'Health check',
            '/render': 'POST - Submit render job'
        }
    });
});

// ============================================
// MAIN RENDER ENDPOINT
// ============================================

app.post('/render', async (req: Request, res: Response) => {
    const startTime = Date.now();
    const job = req.body as RenderRequest;

    // Validate request
    if (!job.order_id || !job.template_id) {
        console.error('❌ Missing order_id or template_id');
        res.status(400).json({ error: 'order_id and template_id are required' });
        return;
    }

    console.log(`\n${'='.repeat(60)}`);
    console.log(`📦 Starting render job: Order #${job.order_number || job.order_id}`);
    console.log(`   Template: ${job.template_id}`);
    console.log(`${'='.repeat(60)}`);

    // Respond immediately to avoid Cloud Tasks timeout
    res.status(202).json({
        success: true,
        message: 'Render job accepted',
        order_id: job.order_id
    });

    // Process render asynchronously
    processRender(job, startTime).catch(err => {
        console.error('Render failed:', err);
    });
});

/**
 * Process the render job asynchronously
 */
async function processRender(job: RenderRequest, startTime: number): Promise<void> {
    const outputDir = path.join(__dirname, '../temp');
    const outputPath = path.join(outputDir, `order_${job.order_id}.mp4`);

    // Ensure temp directory exists
    if (!fs.existsSync(outputDir)) {
        fs.mkdirSync(outputDir, { recursive: true });
    }

    const callbackUrl = job.callback_url || process.env.CALLBACK_URL;
    const statusUrl = job.status_url || process.env.STATUS_URL;
    const secretKey = job.secret_key || process.env.RENDERER_SECRET_KEY || 'rmtn_render_secret_key';

    try {
        // Step 1: Update status to processing
        console.log('\n⚙️ Step 1: Updating status to processing...');
        if (statusUrl) {
            await updateOrderStatus(statusUrl, job.order_id, 'processing', secretKey);
        }

        // Step 2: Render video
        console.log('\n🎬 Step 2: Rendering video...');
        await renderVideo(job.template_id, job.input_props, outputPath);
        console.log(`   ✅ Rendered: ${outputPath}`);

        // Verify file exists
        if (!fs.existsSync(outputPath)) {
            throw new Error('Rendered video file not found');
        }

        const fileSize = fs.statSync(outputPath).size;
        console.log(`   📦 File size: ${(fileSize / 1024 / 1024).toFixed(2)} MB`);

        // Step 3: Upload to Hostinger (Boomerang!)
        console.log('\n📤 Step 3: Uploading video to Hostinger (boomerang)...');
        if (callbackUrl) {
            await uploadToHostinger(callbackUrl, outputPath, job.order_id, secretKey);
            console.log('   ✅ Video uploaded successfully');
        } else {
            console.warn('   ⚠️ No callback URL - skipping upload');
            // Update status to completed anyway (for testing)
            if (statusUrl) {
                await updateOrderStatus(statusUrl, job.order_id, 'completed', secretKey);
            }
        }

        // Step 4: Cleanup
        console.log('\n🧹 Step 4: Cleaning up...');
        if (fs.existsSync(outputPath)) {
            fs.unlinkSync(outputPath);
            console.log('   ✅ Temp file deleted');
        }

        // Done!
        const duration = ((Date.now() - startTime) / 1000).toFixed(1);
        console.log(`\n${'='.repeat(60)}`);
        console.log(`✅ Render completed successfully!`);
        console.log(`   Order: #${job.order_number || job.order_id}`);
        console.log(`   Duration: ${duration}s`);
        console.log(`${'='.repeat(60)}\n`);

    } catch (error) {
        const errorMessage = error instanceof Error ? error.message : 'Unknown error';
        console.error(`\n❌ Render failed: Order #${job.order_id}`);
        console.error(`   Error: ${errorMessage}`);

        // Update status to failed
        if (statusUrl) {
            try {
                await updateOrderStatus(statusUrl, job.order_id, 'failed', secretKey);
            } catch {
                console.error('   Failed to update error status');
            }
        }

        // Cleanup on failure
        if (fs.existsSync(outputPath)) {
            fs.unlinkSync(outputPath);
        }
    }
}

// ============================================
// START SERVER
// ============================================

const PORT = process.env.PORT || 8080;

app.listen(PORT, () => {
    console.log(`
╔═══════════════════════════════════════════════════════════════╗
║                                                               ║
║   🎬  REMOTION RENDERER - BOOMERANG ARCHITECTURE             ║
║                                                               ║
║   Server running on port ${PORT}                                ║
║   Callback URL: ${process.env.CALLBACK_URL || 'Not configured'}
║                                                               ║
╚═══════════════════════════════════════════════════════════════╝
`);
});

export default app;
