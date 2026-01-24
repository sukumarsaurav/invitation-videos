/**
 * Golden Border Decoration
 * 
 * Ornamental border for traditional Indian designs
 */

import { interpolate, useCurrentFrame } from "remotion";
import React from "react";

interface GoldenBorderProps {
    thickness?: number;
    animated?: boolean;
}

export const GoldenBorder: React.FC<GoldenBorderProps> = ({
    thickness = 20,
    animated = true,
}) => {
    const frame = useCurrentFrame();

    const drawProgress = animated
        ? interpolate(frame, [0, 60], [0, 1], { extrapolateRight: "clamp" })
        : 1;

    const shimmerPosition = interpolate(
        frame % 120,
        [0, 120],
        [0, 200]
    );

    const cornerSize = 80;

    return (
        <>
            {/* Top border */}
            <div
                style={{
                    position: "absolute",
                    top: 30,
                    left: 30,
                    right: 30,
                    height: thickness,
                    background: `linear-gradient(90deg, 
            transparent 0%, 
            #8b6914 ${shimmerPosition - 20}%, 
            #f4e4ba ${shimmerPosition}%, 
            #d4a853 ${shimmerPosition + 20}%, 
            transparent 100%
          )`,
                    transform: `scaleX(${drawProgress})`,
                    transformOrigin: "left",
                }}
            />

            {/* Bottom border */}
            <div
                style={{
                    position: "absolute",
                    bottom: 30,
                    left: 30,
                    right: 30,
                    height: thickness,
                    background: `linear-gradient(90deg, 
            transparent 0%, 
            #8b6914 ${shimmerPosition - 20}%, 
            #f4e4ba ${shimmerPosition}%, 
            #d4a853 ${shimmerPosition + 20}%, 
            transparent 100%
          )`,
                    transform: `scaleX(${drawProgress})`,
                    transformOrigin: "right",
                }}
            />

            {/* Left border */}
            <div
                style={{
                    position: "absolute",
                    top: 30,
                    bottom: 30,
                    left: 30,
                    width: thickness,
                    background: `linear-gradient(180deg, 
            #d4a853 0%, 
            #f4e4ba 50%, 
            #d4a853 100%
          )`,
                    transform: `scaleY(${drawProgress})`,
                    transformOrigin: "top",
                }}
            />

            {/* Right border */}
            <div
                style={{
                    position: "absolute",
                    top: 30,
                    bottom: 30,
                    right: 30,
                    width: thickness,
                    background: `linear-gradient(180deg, 
            #d4a853 0%, 
            #f4e4ba 50%, 
            #d4a853 100%
          )`,
                    transform: `scaleY(${drawProgress})`,
                    transformOrigin: "bottom",
                }}
            />

            {/* Corner decorations */}
            {[
                { top: 20, left: 20 },
                { top: 20, right: 20 },
                { bottom: 20, left: 20 },
                { bottom: 20, right: 20 },
            ].map((pos, i) => (
                <div
                    key={i}
                    style={{
                        position: "absolute",
                        ...pos,
                        width: cornerSize,
                        height: cornerSize,
                        opacity: drawProgress,
                        display: "flex",
                        alignItems: "center",
                        justifyContent: "center",
                        fontSize: 48,
                        color: "#d4a853",
                        textShadow: "0 0 10px rgba(212, 168, 83, 0.5)",
                        transform: `rotate(${i * 90}deg)`,
                    }}
                >
                    ❧
                </div>
            ))}
        </>
    );
};
