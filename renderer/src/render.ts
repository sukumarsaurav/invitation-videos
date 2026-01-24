/**
 * Remotion Rendering Logic
 * 
 * Handles video rendering using Remotion's @remotion/renderer package.
 */

import { bundle } from '@remotion/bundler';
import { renderMedia, selectComposition } from '@remotion/renderer';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

// Cache the bundle path
let bundlePath: string | null = null;

/**
 * Get or create the Remotion bundle
 */
async function getBundle(): Promise<string> {
    if (bundlePath) {
        return bundlePath;
    }

    console.log('   📦 Creating Remotion bundle...');

    const entryPoint = path.join(__dirname, '../remotion/index.ts');

    const result = await bundle({
        entryPoint,
        // Use a persistent location for caching
        outDir: path.join(__dirname, '../dist/bundle'),
    });

    bundlePath = result;

    console.log('   ✅ Bundle created');
    return result;
}

/**
 * Render a video using Remotion
 */
export async function renderVideo(
    compositionId: string,
    inputProps: Record<string, unknown>,
    outputPath: string,
    onProgress?: (progress: number) => void
): Promise<string> {
    console.log(`   🎯 Composition: ${compositionId}`);
    console.log(`   📋 Props:`, JSON.stringify(inputProps).substring(0, 200) + '...');

    // Get the bundle
    const bundleLocation = await getBundle();

    // Select the composition
    console.log('   🔍 Selecting composition...');
    const composition = await selectComposition({
        serveUrl: bundleLocation,
        id: compositionId,
        inputProps,
    });

    console.log(`   📐 Resolution: ${composition.width}x${composition.height}`);
    console.log(`   ⏱️ Duration: ${composition.durationInFrames} frames @ ${composition.fps}fps`);

    // Render the video
    console.log('   🎬 Rendering...');

    await renderMedia({
        composition,
        serveUrl: bundleLocation,
        codec: 'h264',
        outputLocation: outputPath,
        inputProps,
        onProgress: ({ progress }: { progress: number }) => {
            const percent = Math.round(progress * 100);
            if (percent % 10 === 0) {
                console.log(`   📊 Progress: ${percent}%`);
            }
            onProgress?.(percent);
        },
        // Optimized settings for Cloud Run (limited resources)
        concurrency: 1, // Single-threaded to avoid OOM
        chromiumOptions: {
            disableWebSecurity: true,
            // Use system Chrome/Chromium
            gl: 'swangle',
        },
    });

    return outputPath;
}
