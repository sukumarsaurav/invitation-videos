/**
 * Animated Text Component
 * 
 * Reveals text with various animation effects
 */

import { interpolate, useCurrentFrame, useVideoConfig } from "remotion";
import React from "react";

interface AnimatedTextProps {
    text: string;
    style?: React.CSSProperties;
    delay?: number;
    duration?: number;
    animationType?: "fadeUp" | "fadeIn" | "typewriter" | "scaleIn";
}

export const AnimatedText: React.FC<AnimatedTextProps> = ({
    text,
    style = {},
    delay = 0,
    duration = 30,
    animationType = "fadeUp",
}) => {
    const frame = useCurrentFrame();
    const { fps } = useVideoConfig();

    const progress = interpolate(
        frame - delay,
        [0, duration],
        [0, 1],
        { extrapolateLeft: "clamp", extrapolateRight: "clamp" }
    );

    let animatedStyle: React.CSSProperties = {};

    switch (animationType) {
        case "fadeUp":
            animatedStyle = {
                opacity: progress,
                transform: `translateY(${interpolate(progress, [0, 1], [30, 0])}px)`,
            };
            break;
        case "fadeIn":
            animatedStyle = {
                opacity: progress,
            };
            break;
        case "scaleIn":
            animatedStyle = {
                opacity: progress,
                transform: `scale(${interpolate(progress, [0, 1], [0.8, 1])})`,
            };
            break;
        case "typewriter":
            const visibleChars = Math.floor(progress * text.length);
            return (
                <span style={{ ...style, ...animatedStyle }}>
                    {text.slice(0, visibleChars)}
                    <span style={{ opacity: 0.3 }}>{text.slice(visibleChars)}</span>
                </span>
            );
    }

    return (
        <div style={{ ...style, ...animatedStyle }}>
            {text}
        </div>
    );
};
