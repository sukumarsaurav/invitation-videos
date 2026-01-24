/**
 * Fade In Component
 * 
 * Simple fade-in animation wrapper
 */

import { interpolate, useCurrentFrame } from "remotion";
import React from "react";

interface FadeInProps {
    children: React.ReactNode;
    duration?: number;
    delay?: number;
}

export const FadeIn: React.FC<FadeInProps> = ({
    children,
    duration = 30,
    delay = 0,
}) => {
    const frame = useCurrentFrame();

    const opacity = interpolate(
        frame - delay,
        [0, duration],
        [0, 1],
        { extrapolateLeft: "clamp", extrapolateRight: "clamp" }
    );

    return (
        <div style={{ opacity }}>
            {children}
        </div>
    );
};
