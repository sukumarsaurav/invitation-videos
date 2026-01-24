/**
 * Neon Text Component
 * 
 * Text with glowing neon effect
 */

import { interpolate, useCurrentFrame } from "remotion";
import React from "react";

interface NeonTextProps {
    text: string;
    color: string;
    fontSize?: number;
    delay?: number;
}

export const NeonText: React.FC<NeonTextProps> = ({
    text,
    color,
    fontSize = 48,
    delay = 0,
}) => {
    const frame = useCurrentFrame();

    const opacity = interpolate(
        frame - delay,
        [0, 20],
        [0, 1],
        { extrapolateLeft: "clamp", extrapolateRight: "clamp" }
    );

    // Flickering effect
    const flicker = Math.sin(frame * 0.5) > 0.9 ? 0.8 : 1;

    // Pulsing glow
    const glowIntensity = interpolate(
        Math.sin(frame * 0.08),
        [-1, 1],
        [15, 35]
    );

    return (
        <div
            style={{
                fontFamily: "'Bangers', 'Arial Black', sans-serif",
                fontSize,
                color: color,
                textShadow: `
          0 0 ${glowIntensity * 0.5}px ${color},
          0 0 ${glowIntensity}px ${color},
          0 0 ${glowIntensity * 1.5}px ${color},
          0 0 ${glowIntensity * 2}px ${color}
        `,
                opacity: opacity * flicker,
                textAlign: "center",
                letterSpacing: 4,
            }}
        >
            {text}
        </div>
    );
};
