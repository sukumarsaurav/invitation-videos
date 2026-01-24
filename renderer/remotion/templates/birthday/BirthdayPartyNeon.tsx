/**
 * Birthday Party Neon Template
 * 
 * Vibrant, fun design for birthday parties:
 * - Neon glow effects
 * - Bouncy animations
 * - Colorful confetti
 */

import { AbsoluteFill, Img, interpolate, Sequence, spring, useCurrentFrame, useVideoConfig, Audio } from "remotion";
import { z } from "zod";
import { AnimatedText } from "../../components/AnimatedText";
import { ConfettiEffect } from "../../components/effects/ConfettiEffect";
import { NeonText } from "../../components/NeonText";

export const birthdayPartyNeonSchema = z.object({
    birthday_person: z.string(),
    age: z.string(),
    party_date: z.string(),
    party_time: z.string().optional(),
    venue: z.string(),
    child_photo: z.string().url().optional(),
    theme_color: z.string().default("#ff00ff"),
    music_url: z.string().url().nullable().optional(),
});

type Props = z.infer<typeof birthdayPartyNeonSchema>;

export const BirthdayPartyNeon: React.FC<Props> = ({
    birthday_person,
    age,
    party_date,
    party_time,
    venue,
    child_photo,
    theme_color,
    music_url,
}) => {
    const frame = useCurrentFrame();
    const { fps, durationInFrames } = useVideoConfig();

    const formattedDate = new Date(party_date).toLocaleDateString('en-US', {
        weekday: 'long',
        month: 'short',
        day: 'numeric',
    });

    // Bounce animation for the age
    const bounce = spring({
        frame: frame - 30,
        fps,
        config: {
            damping: 8,
            stiffness: 100,
        },
    });

    // Glow pulse animation
    const glowIntensity = interpolate(
        Math.sin(frame * 0.1),
        [-1, 1],
        [20, 40]
    );

    return (
        <AbsoluteFill style={{
            background: "linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f0f23 100%)",
        }}>
            {/* Confetti overlay */}
            <ConfettiEffect colors={[theme_color, "#ffff00", "#00ffff", "#ff6b6b"]} />

            {/* Main content */}
            <AbsoluteFill style={{
                display: "flex",
                flexDirection: "column",
                alignItems: "center",
                justifyContent: "center",
                padding: 60,
            }}>
                {/* "You're Invited" header */}
                <Sequence from={0} durationInFrames={durationInFrames}>
                    <NeonText
                        text="You're Invited!"
                        color="#00ffff"
                        fontSize={48}
                        delay={0}
                    />
                </Sequence>

                {/* Age Circle */}
                <Sequence from={30}>
                    <div style={{
                        width: 200,
                        height: 200,
                        borderRadius: "50%",
                        background: `linear-gradient(135deg, ${theme_color}, #ff6b6b)`,
                        display: "flex",
                        alignItems: "center",
                        justifyContent: "center",
                        marginTop: 40,
                        marginBottom: 30,
                        transform: `scale(${bounce})`,
                        boxShadow: `0 0 ${glowIntensity}px ${theme_color}, 0 0 ${glowIntensity * 2}px ${theme_color}`,
                    }}>
                        <span style={{
                            fontFamily: "'Bangers', cursive",
                            fontSize: 96,
                            color: "white",
                            textShadow: "3px 3px 0 rgba(0,0,0,0.3)",
                        }}>
                            {age}
                        </span>
                    </div>
                </Sequence>

                {/* Birthday Person Name */}
                <Sequence from={45}>
                    <NeonText
                        text={`${birthday_person}'s`}
                        color={theme_color}
                        fontSize={72}
                        delay={0}
                    />
                    <div style={{ height: 10 }} />
                    <NeonText
                        text="Birthday Party!"
                        color="#ffff00"
                        fontSize={56}
                        delay={15}
                    />
                </Sequence>

                {/* Photo (if provided) */}
                {child_photo && (
                    <Sequence from={75}>
                        <div style={{
                            marginTop: 30,
                            marginBottom: 30,
                            borderRadius: 20,
                            overflow: "hidden",
                            border: `4px solid ${theme_color}`,
                            boxShadow: `0 0 30px ${theme_color}`,
                            transform: `scale(${interpolate(frame - 75, [0, 20], [0.8, 1], { extrapolateRight: "clamp" })})`,
                            opacity: interpolate(frame - 75, [0, 20], [0, 1], { extrapolateRight: "clamp" }),
                        }}>
                            <Img
                                src={child_photo}
                                style={{
                                    width: 280,
                                    height: 280,
                                    objectFit: "cover",
                                }}
                            />
                        </div>
                    </Sequence>
                )}

                {/* Date & Time */}
                <Sequence from={100}>
                    <div style={{
                        marginTop: 20,
                        textAlign: "center",
                    }}>
                        <div style={{
                            fontFamily: "'Poppins', sans-serif",
                            fontSize: 32,
                            color: "#ffffff",
                            textShadow: `0 0 10px ${theme_color}`,
                        }}>
                            📅 {formattedDate}
                        </div>
                        {party_time && (
                            <div style={{
                                fontFamily: "'Poppins', sans-serif",
                                fontSize: 28,
                                color: "#cccccc",
                                marginTop: 8,
                            }}>
                                ⏰ {party_time}
                            </div>
                        )}
                    </div>
                </Sequence>

                {/* Venue */}
                <Sequence from={120}>
                    <div style={{
                        marginTop: 30,
                        padding: "15px 30px",
                        background: "rgba(255,255,255,0.1)",
                        borderRadius: 15,
                        border: `2px solid ${theme_color}`,
                    }}>
                        <div style={{
                            fontFamily: "'Poppins', sans-serif",
                            fontSize: 24,
                            color: "#ffffff",
                            textAlign: "center",
                        }}>
                            📍 {venue}
                        </div>
                    </div>
                </Sequence>

                {/* Bottom CTA */}
                <Sequence from={150}>
                    <div style={{
                        position: "absolute",
                        bottom: 80,
                        fontFamily: "'Bangers', cursive",
                        fontSize: 36,
                        color: "#00ffff",
                        textShadow: "0 0 20px #00ffff",
                        animation: "pulse 1s infinite",
                    }}>
                        🎉 Let's Party! 🎉
                    </div>
                </Sequence>
            </AbsoluteFill>

            {/* Audio */}
            {music_url && <Audio src={music_url} volume={0.5} />}
        </AbsoluteFill>
    );
};
