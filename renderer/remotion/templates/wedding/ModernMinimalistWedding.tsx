/**
 * Modern Minimalist Wedding Template
 * 
 * Clean, contemporary design with:
 * - Subtle animations
 * - Customizable accent color
 * - Elegant typography
 */

import { AbsoluteFill, Img, interpolate, Sequence, useCurrentFrame, useVideoConfig, Audio } from "remotion";
import { z } from "zod";
import { AnimatedText } from "../../components/AnimatedText";
import { FadeIn } from "../../components/transitions/FadeIn";

export const modernMinimalistWeddingSchema = z.object({
    partner1_name: z.string(),
    partner2_name: z.string(),
    event_date: z.string(),
    event_time: z.string().optional(),
    location: z.string(),
    cover_image: z.string().url(),
    accent_color: z.string().default("#c9a227"),
    music_url: z.string().url().nullable().optional(),
});

type Props = z.infer<typeof modernMinimalistWeddingSchema>;

export const ModernMinimalistWedding: React.FC<Props> = ({
    partner1_name,
    partner2_name,
    event_date,
    event_time,
    location,
    cover_image,
    accent_color,
    music_url,
}) => {
    const frame = useCurrentFrame();
    const { fps, width, height, durationInFrames } = useVideoConfig();

    const formattedDate = new Date(event_date).toLocaleDateString('en-US', {
        month: 'long',
        day: 'numeric',
        year: 'numeric',
    });

    // Animation progress
    const lineProgress = interpolate(frame, [30, 60], [0, 1], { extrapolateRight: "clamp" });
    const imageScale = interpolate(frame, [0, 45], [1.1, 1], { extrapolateRight: "clamp" });
    const imageOpacity = interpolate(frame, [0, 30], [0, 0.4], { extrapolateRight: "clamp" });

    return (
        <AbsoluteFill style={{ backgroundColor: "#fafafa" }}>
            {/* Background Image with overlay */}
            <div style={{
                position: "absolute",
                width: "100%",
                height: "100%",
                overflow: "hidden",
            }}>
                <Img
                    src={cover_image}
                    style={{
                        width: "100%",
                        height: "100%",
                        objectFit: "cover",
                        transform: `scale(${imageScale})`,
                        opacity: imageOpacity,
                        filter: "grayscale(30%)",
                    }}
                />
            </div>

            {/* Content Overlay */}
            <AbsoluteFill style={{
                display: "flex",
                flexDirection: "column",
                alignItems: "center",
                justifyContent: "center",
                padding: 80,
            }}>
                {/* Top decorative line */}
                <div style={{
                    width: `${lineProgress * 200}px`,
                    height: 2,
                    backgroundColor: accent_color,
                    marginBottom: 60,
                }} />

                {/* Names */}
                <Sequence from={20}>
                    <FadeIn duration={30}>
                        <div style={{
                            fontFamily: "'Cormorant Garamond', serif",
                            fontSize: 84,
                            fontWeight: 300,
                            color: "#2d2d2d",
                            textAlign: "center",
                            lineHeight: 1.2,
                        }}>
                            {partner1_name}
                        </div>
                    </FadeIn>
                </Sequence>

                <Sequence from={35}>
                    <FadeIn duration={30}>
                        <div style={{
                            fontFamily: "'Cormorant Garamond', serif",
                            fontSize: 36,
                            color: accent_color,
                            margin: "20px 0",
                            letterSpacing: 16,
                        }}>
                            AND
                        </div>
                    </FadeIn>
                </Sequence>

                <Sequence from={50}>
                    <FadeIn duration={30}>
                        <div style={{
                            fontFamily: "'Cormorant Garamond', serif",
                            fontSize: 84,
                            fontWeight: 300,
                            color: "#2d2d2d",
                            textAlign: "center",
                            lineHeight: 1.2,
                        }}>
                            {partner2_name}
                        </div>
                    </FadeIn>
                </Sequence>

                {/* Divider */}
                <Sequence from={80}>
                    <FadeIn duration={20}>
                        <div style={{
                            width: 60,
                            height: 60,
                            border: `1px solid ${accent_color}`,
                            transform: "rotate(45deg)",
                            margin: "50px 0",
                        }} />
                    </FadeIn>
                </Sequence>

                {/* Date & Time */}
                <Sequence from={100}>
                    <FadeIn duration={30}>
                        <div style={{
                            fontFamily: "'Montserrat', sans-serif",
                            fontSize: 32,
                            fontWeight: 300,
                            color: "#4a4a4a",
                            letterSpacing: 6,
                            textTransform: "uppercase",
                        }}>
                            {formattedDate}
                        </div>
                    </FadeIn>
                </Sequence>

                {event_time && (
                    <Sequence from={120}>
                        <FadeIn duration={30}>
                            <div style={{
                                fontFamily: "'Montserrat', sans-serif",
                                fontSize: 24,
                                fontWeight: 300,
                                color: "#6a6a6a",
                                marginTop: 10,
                                letterSpacing: 4,
                            }}>
                                {event_time}
                            </div>
                        </FadeIn>
                    </Sequence>
                )}

                {/* Location */}
                <Sequence from={140}>
                    <FadeIn duration={30}>
                        <div style={{
                            fontFamily: "'Cormorant Garamond', serif",
                            fontSize: 36,
                            fontStyle: "italic",
                            color: "#3a3a3a",
                            marginTop: 40,
                            textAlign: "center",
                        }}>
                            {location}
                        </div>
                    </FadeIn>
                </Sequence>

                {/* Bottom decorative line */}
                <div style={{
                    width: `${lineProgress * 200}px`,
                    height: 2,
                    backgroundColor: accent_color,
                    marginTop: 60,
                }} />
            </AbsoluteFill>

            {/* Audio */}
            {music_url && <Audio src={music_url} volume={0.4} />}
        </AbsoluteFill>
    );
};
