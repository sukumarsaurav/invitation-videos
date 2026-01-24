/**
 * Confetti Effect Component
 * 
 * Animated confetti particles falling
 */

import { interpolate, random, useCurrentFrame, useVideoConfig } from "remotion";
import React, { useMemo } from "react";

interface ConfettiEffectProps {
    colors?: string[];
    particleCount?: number;
}

interface Particle {
    id: number;
    x: number;
    y: number;
    size: number;
    color: string;
    rotation: number;
    speed: number;
    wobble: number;
}

export const ConfettiEffect: React.FC<ConfettiEffectProps> = ({
    colors = ["#ff0000", "#00ff00", "#0000ff", "#ffff00", "#ff00ff"],
    particleCount = 50,
}) => {
    const frame = useCurrentFrame();
    const { width, height, durationInFrames } = useVideoConfig();

    // Generate particles once
    const particles = useMemo<Particle[]>(() => {
        return Array.from({ length: particleCount }, (_, i) => ({
            id: i,
            x: random(`x-${i}`) * width,
            y: random(`y-${i}`) * -200 - 50, // Start above screen
            size: random(`size-${i}`) * 15 + 5,
            color: colors[Math.floor(random(`color-${i}`) * colors.length)],
            rotation: random(`rot-${i}`) * 360,
            speed: random(`speed-${i}`) * 3 + 2,
            wobble: random(`wobble-${i}`) * 30,
        }));
    }, [particleCount, width, colors]);

    return (
        <div style={{ position: "absolute", inset: 0, overflow: "hidden", pointerEvents: "none" }}>
            {particles.map((particle) => {
                // Calculate current position
                const yOffset = frame * particle.speed;
                const currentY = particle.y + yOffset;
                const wobbleX = Math.sin(frame * 0.1 + particle.id) * particle.wobble;
                const currentRotation = particle.rotation + frame * 5;

                // Fade out at bottom
                const opacity = interpolate(
                    currentY,
                    [height - 200, height],
                    [1, 0],
                    { extrapolateLeft: "clamp", extrapolateRight: "clamp" }
                );

                // Only render if visible
                if (currentY > height + 50) return null;

                return (
                    <div
                        key={particle.id}
                        style={{
                            position: "absolute",
                            left: particle.x + wobbleX,
                            top: currentY,
                            width: particle.size,
                            height: particle.size * 0.6,
                            backgroundColor: particle.color,
                            transform: `rotate(${currentRotation}deg)`,
                            opacity,
                            borderRadius: 2,
                        }}
                    />
                );
            })}
        </div>
    );
};
