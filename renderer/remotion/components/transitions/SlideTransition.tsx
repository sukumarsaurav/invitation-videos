/**
 * Slide Transition Component
 * 
 * Wraps content with entrance/exit animations
 */

import { AbsoluteFill, interpolate, useCurrentFrame, useVideoConfig } from "remotion";
import React from "react";

interface SlideTransitionProps {
    children: React.ReactNode;
    type?: "fade" | "slideUp" | "slideDown" | "slideLeft" | "slideRight" | "zoom";
    duration?: number;
}

export const SlideTransition: React.FC<SlideTransitionProps> = ({
    children,
    type = "fade",
    duration = 30,
}) => {
    const frame = useCurrentFrame();
    const { durationInFrames } = useVideoConfig();

    // Entrance animation (first `duration` frames)
    const entranceProgress = interpolate(
        frame,
        [0, duration],
        [0, 1],
        { extrapolateRight: "clamp" }
    );

    // Exit animation (last `duration` frames)
    const exitProgress = interpolate(
        frame,
        [durationInFrames - duration, durationInFrames],
        [1, 0],
        { extrapolateLeft: "clamp" }
    );

    const progress = Math.min(entranceProgress, exitProgress);

    let transform = "";
    let opacity = progress;

    switch (type) {
        case "slideUp":
            transform = `translateY(${interpolate(progress, [0, 1], [50, 0])}px)`;
            break;
        case "slideDown":
            transform = `translateY(${interpolate(progress, [0, 1], [-50, 0])}px)`;
            break;
        case "slideLeft":
            transform = `translateX(${interpolate(progress, [0, 1], [50, 0])}px)`;
            break;
        case "slideRight":
            transform = `translateX(${interpolate(progress, [0, 1], [-50, 0])}px)`;
            break;
        case "zoom":
            transform = `scale(${interpolate(progress, [0, 1], [0.9, 1])})`;
            break;
        default:
            // Just fade
            break;
    }

    return (
        <AbsoluteFill
            style={{
                opacity,
                transform,
            }}
        >
            {children}
        </AbsoluteFill>
    );
};
