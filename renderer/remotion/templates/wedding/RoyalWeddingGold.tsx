/**
 * Royal Wedding Gold Template
 * 
 * A traditional Indian wedding invitation with:
 * - Golden ornamental borders
 * - Animated text reveals
 * - Photo frames with decorative elements
 * - Smooth slide transitions
 * 
 * Props match field_name values in field_presets table
 */

import { AbsoluteFill, Img, interpolate, Sequence, spring, useCurrentFrame, useVideoConfig, Audio, staticFile } from "remotion";
import { z } from "zod";
import { AnimatedText } from "../../components/AnimatedText";
import { GoldenBorder } from "../../components/decorations/GoldenBorder";
import { PhotoFrame } from "../../components/PhotoFrame";
import { SlideTransition } from "../../components/transitions/SlideTransition";

// Schema defines what props this template accepts
// These MUST match the field_name values in your database
export const royalWeddingGoldSchema = z.object({
    groom_name: z.string(),
    bride_name: z.string(),
    wedding_date: z.string(),
    venue_name: z.string(),
    venue_address: z.string().optional(),
    couple_photo: z.string().url(),
    music_url: z.string().url().nullable().optional(),
    background_music_custom: z.string().url().nullable().optional(),
});

type RoyalWeddingGoldProps = z.infer<typeof royalWeddingGoldSchema>;

export const RoyalWeddingGold: React.FC<RoyalWeddingGoldProps> = ({
    groom_name,
    bride_name,
    wedding_date,
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
    const SLIDE_1_END = 8 * fps;  // First 8 seconds - Title slide
    const SLIDE_2_START = SLIDE_1_END;
    const SLIDE_2_END = 18 * fps; // Next 10 seconds - Couple names + photo
    const SLIDE_3_START = SLIDE_2_END;
    const SLIDE_3_END = durationInFrames; // Rest - Venue details

    // Use custom uploaded music, fallback to music_url, then default
    const audioSrc = background_music_custom || music_url || staticFile("audio/wedding-default.mp3");

    return (
        <AbsoluteFill style={{ backgroundColor: "#1a0a0a" }}>
            {/* Background Music */}
            {audioSrc && (
                <Audio src={audioSrc} volume={0.5} />
            )}

            {/* Slide 1: Opening Title */}
            <Sequence from={SLIDE_1_START} durationInFrames={SLIDE_1_END - SLIDE_1_START}>
                <SlideTransition type="fade">
                    <AbsoluteFill style={styles.slide}>
                        <GoldenBorder />

                        {/* Decorative element */}
                        <div style={styles.decorTop}>✦ शुभ विवाह ✦</div>

                        <AnimatedText
                            text="Wedding Invitation"
                            style={styles.titleText}
                            delay={15}
                        />

                        <AnimatedText
                            text="You are cordially invited"
                            style={styles.subtitleText}
                            delay={45}
                        />

                        <div style={styles.decorBottom}>❧ ❧ ❧</div>
                    </AbsoluteFill>
                </SlideTransition>
            </Sequence>

            {/* Slide 2: Couple Names & Photo */}
            <Sequence from={SLIDE_2_START} durationInFrames={SLIDE_2_END - SLIDE_2_START}>
                <SlideTransition type="slideUp">
                    <AbsoluteFill style={styles.slide}>
                        <GoldenBorder />

                        <div style={styles.namesContainer}>
                            <AnimatedText
                                text={groom_name}
                                style={styles.groomName}
                                delay={10}
                            />

                            <div style={styles.ampersand}>&</div>

                            <AnimatedText
                                text={bride_name}
                                style={styles.brideName}
                                delay={25}
                            />
                        </div>

                        <PhotoFrame
                            src={couple_photo}
                            frameStyle="ornate-gold"
                            delay={40}
                        />

                        <AnimatedText
                            text="Request the pleasure of your company"
                            style={styles.requestText}
                            delay={70}
                        />
                    </AbsoluteFill>
                </SlideTransition>
            </Sequence>

            {/* Slide 3: Date & Venue */}
            <Sequence from={SLIDE_3_START} durationInFrames={SLIDE_3_END - SLIDE_3_START}>
                <SlideTransition type="slideLeft">
                    <AbsoluteFill style={styles.slide}>
                        <GoldenBorder />

                        <div style={styles.detailsContainer}>
                            <div style={styles.dateLabel}>Save the Date</div>

                            <AnimatedText
                                text={formattedDate}
                                style={styles.dateText}
                                delay={10}
                            />

                            <div style={styles.divider}>⟡</div>

                            <div style={styles.venueLabel}>Venue</div>

                            <AnimatedText
                                text={venue_name}
                                style={styles.venueText}
                                delay={30}
                            />

                            {venue_address && (
                                <AnimatedText
                                    text={venue_address}
                                    style={styles.addressText}
                                    delay={50}
                                />
                            )}
                        </div>

                        <div style={styles.footer}>
                            With Love & Blessings
                        </div>
                    </AbsoluteFill>
                </SlideTransition>
            </Sequence>
        </AbsoluteFill>
    );
};

const styles: Record<string, React.CSSProperties> = {
    slide: {
        display: "flex",
        flexDirection: "column",
        alignItems: "center",
        justifyContent: "center",
        padding: 60,
        background: "linear-gradient(180deg, #2d1810 0%, #1a0a0a 50%, #2d1810 100%)",
    },
    decorTop: {
        fontFamily: "serif",
        fontSize: 32,
        color: "#d4a853",
        marginBottom: 40,
        letterSpacing: 8,
    },
    titleText: {
        fontFamily: "'Playfair Display', serif",
        fontSize: 72,
        color: "#d4a853",
        textAlign: "center",
        textShadow: "0 4px 20px rgba(212, 168, 83, 0.3)",
    },
    subtitleText: {
        fontFamily: "'Cormorant Garamond', serif",
        fontSize: 36,
        color: "#c9a227",
        marginTop: 30,
        fontStyle: "italic",
    },
    decorBottom: {
        fontSize: 48,
        color: "#d4a853",
        marginTop: 60,
        letterSpacing: 20,
    },
    namesContainer: {
        display: "flex",
        flexDirection: "column",
        alignItems: "center",
        marginBottom: 40,
    },
    groomName: {
        fontFamily: "'Great Vibes', cursive",
        fontSize: 96,
        color: "#d4a853",
        textShadow: "0 4px 30px rgba(212, 168, 83, 0.4)",
    },
    ampersand: {
        fontFamily: "'Playfair Display', serif",
        fontSize: 64,
        color: "#8b6914",
        margin: "10px 0",
    },
    brideName: {
        fontFamily: "'Great Vibes', cursive",
        fontSize: 96,
        color: "#d4a853",
        textShadow: "0 4px 30px rgba(212, 168, 83, 0.4)",
    },
    requestText: {
        fontFamily: "'Cormorant Garamond', serif",
        fontSize: 28,
        color: "#c9a227",
        marginTop: 40,
        fontStyle: "italic",
    },
    detailsContainer: {
        display: "flex",
        flexDirection: "column",
        alignItems: "center",
    },
    dateLabel: {
        fontFamily: "'Cormorant Garamond', serif",
        fontSize: 28,
        color: "#8b6914",
        textTransform: "uppercase",
        letterSpacing: 8,
        marginBottom: 10,
    },
    dateText: {
        fontFamily: "'Playfair Display', serif",
        fontSize: 48,
        color: "#d4a853",
        textAlign: "center",
    },
    divider: {
        fontSize: 48,
        color: "#d4a853",
        margin: "40px 0",
    },
    venueLabel: {
        fontFamily: "'Cormorant Garamond', serif",
        fontSize: 28,
        color: "#8b6914",
        textTransform: "uppercase",
        letterSpacing: 8,
        marginBottom: 10,
    },
    venueText: {
        fontFamily: "'Playfair Display', serif",
        fontSize: 42,
        color: "#d4a853",
        textAlign: "center",
    },
    addressText: {
        fontFamily: "'Cormorant Garamond', serif",
        fontSize: 28,
        color: "#c9a227",
        marginTop: 15,
        textAlign: "center",
    },
    footer: {
        position: "absolute",
        bottom: 80,
        fontFamily: "'Great Vibes', cursive",
        fontSize: 36,
        color: "#8b6914",
    },
};
