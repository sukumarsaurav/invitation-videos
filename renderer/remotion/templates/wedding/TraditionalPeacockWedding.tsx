/**
 * Traditional Peacock Wedding Template
 * 
 * A beautiful traditional Indian wedding invitation featuring:
 * - Peacock & Lotus themed background with Ganesha
 * - Elegant mandala patterns
 * - Animated text reveals with traditional styling
 * - Photo frames with decorative elements
 * - Smooth slide transitions
 * 
 * Props match field_name values in field_presets table
 */

import { AbsoluteFill, Img, interpolate, Sequence, spring, useCurrentFrame, useVideoConfig, Audio, staticFile } from "remotion";
import { z } from "zod";
import { AnimatedText } from "../../components/AnimatedText";
import { PhotoFrame } from "../../components/PhotoFrame";
import { SlideTransition } from "../../components/transitions/SlideTransition";

// Schema defines what props this template accepts
export const traditionalPeacockWeddingSchema = z.object({
    groom_name: z.string(),
    bride_name: z.string(),
    wedding_date: z.string(),
    wedding_time: z.string().optional(),
    venue_name: z.string(),
    venue_address: z.string().optional(),
    couple_photo: z.string().url(),
    music_url: z.string().url().nullable().optional(),
    background_music_custom: z.string().url().nullable().optional(),
});

type TraditionalPeacockWeddingProps = z.infer<typeof traditionalPeacockWeddingSchema>;

export const TraditionalPeacockWedding: React.FC<TraditionalPeacockWeddingProps> = ({
    groom_name,
    bride_name,
    wedding_date,
    wedding_time,
    venue_name,
    venue_address,
    couple_photo,
    music_url,
    background_music_custom,
}) => {
    const frame = useCurrentFrame();
    const { fps, durationInFrames } = useVideoConfig();

    // Format the date nicely
    const formattedDate = new Date(wedding_date).toLocaleDateString('en-IN', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });

    // Animation timing (in frames)
    const SLIDE_1_START = 0;
    const SLIDE_1_END = 7 * fps;  // First 7 seconds - Title slide with Ganesha
    const SLIDE_2_START = SLIDE_1_END;
    const SLIDE_2_END = 17 * fps; // Next 10 seconds - Couple names + photo
    const SLIDE_3_START = SLIDE_2_END;
    const SLIDE_3_END = 25 * fps; // Next 8 seconds - Date reveal
    const SLIDE_4_START = SLIDE_3_END;
    const SLIDE_4_END = durationInFrames; // Rest - Venue details

    // Use custom uploaded music, fallback to music_url, then default
    const audioSrc = background_music_custom || music_url || staticFile("audio/wedding-default.mp3");

    // Spring animation for decorative elements
    const floatProgress = spring({
        frame: frame % 60,
        fps,
        config: { damping: 100, stiffness: 50 }
    });

    // Gentle pulsing glow effect
    const glowIntensity = interpolate(
        Math.sin(frame / 20),
        [-1, 1],
        [0.3, 0.6]
    );

    return (
        <AbsoluteFill>
            {/* Background Music */}
            {audioSrc && (
                <Audio src={audioSrc} volume={0.5} />
            )}

            {/* Background Image - Always visible */}
            <Img
                src={staticFile("backgrounds/peacock-lotus-wedding.jpg")}
                style={{
                    position: "absolute",
                    width: "100%",
                    height: "100%",
                    objectFit: "cover",
                }}
            />

            {/* Subtle animated overlay for depth */}
            <div style={{
                ...styles.overlay,
                opacity: interpolate(glowIntensity, [0.3, 0.6], [0.1, 0.2]),
            }} />

            {/* Slide 1: Opening with Ganesha Blessing */}
            <Sequence from={SLIDE_1_START} durationInFrames={SLIDE_1_END - SLIDE_1_START}>
                <SlideTransition type="fade">
                    <AbsoluteFill style={styles.slideContent}>
                        {/* Ganesha blessing text at top */}
                        <div style={styles.blessingContainer}>
                            <AnimatedText
                                text="॥ श्री गणेशाय नमः ॥"
                                style={styles.blessingText}
                                delay={10}
                            />
                        </div>

                        {/* Main title */}
                        <div style={styles.titleContainer}>
                            <AnimatedText
                                text="Shubh Vivah"
                                style={styles.titleText}
                                delay={30}
                            />

                            <div style={{
                                ...styles.subtitleDivider,
                                transform: `scaleX(${interpolate(frame, [45, 75], [0, 1], { extrapolateRight: "clamp" })})`,
                            }}>
                                ✦ ✦ ✦
                            </div>

                            <AnimatedText
                                text="Wedding Invitation"
                                style={styles.subtitleText}
                                delay={60}
                            />
                        </div>

                        <AnimatedText
                            text="Together with their families"
                            style={styles.inviteText}
                            delay={90}
                        />
                    </AbsoluteFill>
                </SlideTransition>
            </Sequence>

            {/* Slide 2: Couple Names & Photo */}
            <Sequence from={SLIDE_2_START} durationInFrames={SLIDE_2_END - SLIDE_2_START}>
                <SlideTransition type="slideUp">
                    <AbsoluteFill style={styles.slideContent}>
                        <div style={styles.coupleContainer}>
                            {/* Groom Name */}
                            <AnimatedText
                                text={groom_name}
                                style={styles.groomName}
                                delay={10}
                            />

                            {/* Decorative ampersand */}
                            <div style={styles.weddingSymbol}>
                                <span style={styles.ampersandDecor}>❧</span>
                                <span style={styles.ampersand}>&</span>
                                <span style={styles.ampersandDecor}>❧</span>
                            </div>

                            {/* Bride Name */}
                            <AnimatedText
                                text={bride_name}
                                style={styles.brideName}
                                delay={30}
                            />
                        </div>

                        {/* Couple Photo with elegant frame */}
                        <div style={styles.photoContainer}>
                            <PhotoFrame
                                src={couple_photo}
                                frameStyle="ornate-gold"
                                delay={50}
                            />
                        </div>

                        <AnimatedText
                            text="request the pleasure of your company"
                            style={styles.requestText}
                            delay={80}
                        />
                    </AbsoluteFill>
                </SlideTransition>
            </Sequence>

            {/* Slide 3: Save the Date */}
            <Sequence from={SLIDE_3_START} durationInFrames={SLIDE_3_END - SLIDE_3_START}>
                <SlideTransition type="slideLeft">
                    <AbsoluteFill style={styles.slideContent}>
                        <div style={styles.dateContainer}>
                            <div style={styles.saveTheDate}>Save the Date</div>

                            <div style={styles.dateDecorTop}>✦ ✦ ✦</div>

                            <AnimatedText
                                text={formattedDate}
                                style={styles.dateText}
                                delay={15}
                            />

                            {wedding_time && (
                                <AnimatedText
                                    text={`at ${wedding_time}`}
                                    style={styles.timeText}
                                    delay={40}
                                />
                            )}

                            <div style={styles.dateDecorBottom}>
                                ❦
                            </div>
                        </div>
                    </AbsoluteFill>
                </SlideTransition>
            </Sequence>

            {/* Slide 4: Venue Details */}
            <Sequence from={SLIDE_4_START} durationInFrames={SLIDE_4_END - SLIDE_4_START}>
                <SlideTransition type="fade">
                    <AbsoluteFill style={styles.slideContent}>
                        <div style={styles.venueContainer}>
                            <div style={styles.venueLabel}>Venue</div>

                            <div style={styles.venueDivider}>❧ ❧ ❧</div>

                            <AnimatedText
                                text={venue_name}
                                style={styles.venueText}
                                delay={15}
                            />

                            {venue_address && (
                                <AnimatedText
                                    text={venue_address}
                                    style={styles.addressText}
                                    delay={40}
                                />
                            )}
                        </div>

                        {/* Footer blessing */}
                        <div style={styles.footer}>
                            <div style={styles.footerDecor}>✦</div>
                            <AnimatedText
                                text="With Love & Blessings"
                                style={styles.footerText}
                                delay={70}
                            />
                            <AnimatedText
                                text="आपकी उपस्थिति हमारे लिए सौभाग्य की बात होगी"
                                style={styles.footerHindi}
                                delay={90}
                            />
                        </div>
                    </AbsoluteFill>
                </SlideTransition>
            </Sequence>
        </AbsoluteFill>
    );
};

const styles: Record<string, React.CSSProperties> = {
    overlay: {
        position: "absolute",
        top: 0,
        left: 0,
        right: 0,
        bottom: 0,
        background: "radial-gradient(ellipse at center, transparent 30%, rgba(0,0,0,0.3) 100%)",
    },
    slideContent: {
        display: "flex",
        flexDirection: "column",
        alignItems: "center",
        justifyContent: "center",
        padding: 50,
    },
    blessingContainer: {
        position: "absolute",
        top: 120,
        width: "100%",
        textAlign: "center",
    },
    blessingText: {
        fontFamily: "'Noto Sans Devanagari', serif",
        fontSize: 28,
        color: "#8B4513",
        textShadow: "0 2px 10px rgba(139, 69, 19, 0.3)",
        letterSpacing: 4,
    },
    titleContainer: {
        display: "flex",
        flexDirection: "column",
        alignItems: "center",
        marginTop: 60,
    },
    titleText: {
        fontFamily: "'Great Vibes', cursive",
        fontSize: 82,
        color: "#6B3A2F",
        textShadow: "0 4px 20px rgba(107, 58, 47, 0.4)",
    },
    subtitleDivider: {
        fontSize: 24,
        color: "#8B4513",
        margin: "15px 0",
        letterSpacing: 15,
    },
    subtitleText: {
        fontFamily: "'Playfair Display', serif",
        fontSize: 42,
        color: "#7D5A50",
        fontStyle: "italic",
        letterSpacing: 6,
    },
    inviteText: {
        fontFamily: "'Cormorant Garamond', serif",
        fontSize: 26,
        color: "#6B3A2F",
        marginTop: 50,
        fontStyle: "italic",
    },
    coupleContainer: {
        display: "flex",
        flexDirection: "column",
        alignItems: "center",
        marginTop: -80,
    },
    groomName: {
        fontFamily: "'Great Vibes', cursive",
        fontSize: 88,
        color: "#6B3A2F",
        textShadow: "0 4px 25px rgba(107, 58, 47, 0.5)",
    },
    weddingSymbol: {
        display: "flex",
        alignItems: "center",
        margin: "5px 0",
    },
    ampersandDecor: {
        fontSize: 28,
        color: "#8B4513",
        margin: "0 15px",
    },
    ampersand: {
        fontFamily: "'Playfair Display', serif",
        fontSize: 56,
        color: "#7D5A50",
    },
    brideName: {
        fontFamily: "'Great Vibes', cursive",
        fontSize: 88,
        color: "#6B3A2F",
        textShadow: "0 4px 25px rgba(107, 58, 47, 0.5)",
    },
    photoContainer: {
        marginTop: 30,
        marginBottom: 30,
    },
    requestText: {
        fontFamily: "'Cormorant Garamond', serif",
        fontSize: 24,
        color: "#7D5A50",
        fontStyle: "italic",
        letterSpacing: 2,
    },
    dateContainer: {
        display: "flex",
        flexDirection: "column",
        alignItems: "center",
        backgroundColor: "rgba(255, 253, 248, 0.85)",
        padding: "50px 70px",
        borderRadius: 20,
        boxShadow: "0 10px 40px rgba(107, 58, 47, 0.2)",
    },
    saveTheDate: {
        fontFamily: "'Cormorant Garamond', serif",
        fontSize: 28,
        color: "#8B4513",
        textTransform: "uppercase",
        letterSpacing: 10,
    },
    dateDecorTop: {
        fontSize: 18,
        color: "#8B4513",
        margin: "15px 0 20px",
        letterSpacing: 15,
    },
    dateText: {
        fontFamily: "'Playfair Display', serif",
        fontSize: 42,
        color: "#6B3A2F",
        textAlign: "center",
        lineHeight: 1.4,
    },
    timeText: {
        fontFamily: "'Cormorant Garamond', serif",
        fontSize: 32,
        color: "#7D5A50",
        marginTop: 15,
        fontStyle: "italic",
    },
    dateDecorBottom: {
        fontSize: 36,
        color: "#8B4513",
        marginTop: 20,
    },
    venueContainer: {
        display: "flex",
        flexDirection: "column",
        alignItems: "center",
        backgroundColor: "rgba(255, 253, 248, 0.85)",
        padding: "40px 60px",
        borderRadius: 20,
        boxShadow: "0 10px 40px rgba(107, 58, 47, 0.2)",
        marginTop: -50,
    },
    venueLabel: {
        fontFamily: "'Cormorant Garamond', serif",
        fontSize: 28,
        color: "#8B4513",
        textTransform: "uppercase",
        letterSpacing: 10,
    },
    venueDivider: {
        fontSize: 18,
        color: "#8B4513",
        margin: "15px 0",
        letterSpacing: 10,
    },
    venueText: {
        fontFamily: "'Playfair Display', serif",
        fontSize: 40,
        color: "#6B3A2F",
        textAlign: "center",
    },
    addressText: {
        fontFamily: "'Cormorant Garamond', serif",
        fontSize: 26,
        color: "#7D5A50",
        marginTop: 15,
        textAlign: "center",
        fontStyle: "italic",
        maxWidth: 400,
    },
    footer: {
        position: "absolute",
        bottom: 100,
        display: "flex",
        flexDirection: "column",
        alignItems: "center",
    },
    footerDecor: {
        fontSize: 24,
        color: "#8B4513",
        marginBottom: 10,
    },
    footerText: {
        fontFamily: "'Great Vibes', cursive",
        fontSize: 36,
        color: "#6B3A2F",
    },
    footerHindi: {
        fontFamily: "'Noto Sans Devanagari', serif",
        fontSize: 18,
        color: "#7D5A50",
        marginTop: 10,
    },
};
