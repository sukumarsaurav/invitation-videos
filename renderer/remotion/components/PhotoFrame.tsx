/**
 * Photo Frame Component
 * 
 * Displays an image with decorative frame styles
 */

import { Img, interpolate, spring, useCurrentFrame, useVideoConfig } from "remotion";
import React from "react";

interface PhotoFrameProps {
    src: string;
    frameStyle?: "ornate-gold" | "simple" | "polaroid" | "circle";
    delay?: number;
    width?: number;
    height?: number;
}

export const PhotoFrame: React.FC<PhotoFrameProps> = ({
    src,
    frameStyle = "simple",
    delay = 0,
    width = 350,
    height = 400,
}) => {
    const frame = useCurrentFrame();
    const { fps } = useVideoConfig();

    const scale = spring({
        frame: frame - delay,
        fps,
        config: {
            damping: 15,
            stiffness: 80,
        },
    });

    const opacity = interpolate(
        frame - delay,
        [0, 20],
        [0, 1],
        { extrapolateLeft: "clamp", extrapolateRight: "clamp" }
    );

    const getFrameStyles = (): React.CSSProperties => {
        switch (frameStyle) {
            case "ornate-gold":
                return {
                    border: "12px solid #d4a853",
                    borderImage: "linear-gradient(135deg, #d4a853, #f4e4ba, #d4a853, #8b6914, #d4a853) 1",
                    boxShadow: "0 0 40px rgba(212, 168, 83, 0.4), inset 0 0 20px rgba(212, 168, 83, 0.2)",
                    padding: 8,
                    background: "#1a0a0a",
                };
            case "polaroid":
                return {
                    background: "#ffffff",
                    padding: "15px 15px 50px 15px",
                    boxShadow: "0 8px 30px rgba(0, 0, 0, 0.3)",
                };
            case "circle":
                return {
                    borderRadius: "50%",
                    border: "5px solid white",
                    boxShadow: "0 8px 30px rgba(0, 0, 0, 0.3)",
                    overflow: "hidden",
                };
            default:
                return {
                    border: "3px solid white",
                    boxShadow: "0 4px 20px rgba(0, 0, 0, 0.2)",
                };
        }
    };

    return (
        <div
            style={{
                transform: `scale(${scale})`,
                opacity,
                ...getFrameStyles(),
            }}
        >
            <Img
                src={src}
                style={{
                    width: frameStyle === "circle" ? width : width,
                    height: frameStyle === "circle" ? width : height, // Square for circle
                    objectFit: "cover",
                    display: "block",
                }}
            />
        </div>
    );
};
