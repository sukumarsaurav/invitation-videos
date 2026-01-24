/**
 * Traditional Wedding Events Template
 * 
 * A 4-slide wedding invitation featuring:
 * - Haldi Ceremony
 * - Sangeet Night  
 * - Mehendi Ceremony
 * - Barat Procession
 * 
 * Style: Background image with elegant text overlay
 * Admin Panel: Set remotion_composition_id = "TraditionalWeddingEvents"
 */

import { AbsoluteFill, Img, Sequence, Audio, staticFile, useCurrentFrame, useVideoConfig, interpolate, spring } from "remotion";
import { z } from "zod";

// Schema for props - matches field_name values from database
export const traditionalWeddingEventsSchema = z.object({
    // Couple Info
    groom_name: z.string(),
    bride_name: z.string(),

    // AI Caricature (optional)
    caricature_image: z.string().url().optional().nullable(),
    couple_photo: z.string().url().optional().nullable(),

    // Haldi Details
    haldi_date: z.string(),
    haldi_time: z.string().optional().nullable(),
    haldi_venue: z.string().optional().nullable(),

    // Sangeet Details  
    sangeet_date: z.string(),
    sangeet_time: z.string().optional().nullable(),
    sangeet_venue: z.string().optional().nullable(),

    // Mehendi Details
    mehendi_date: z.string(),
    mehendi_time: z.string().optional().nullable(),
    mehendi_venue: z.string().optional().nullable(),

    // Barat Details
    baraat_date: z.string().optional().nullable(),
    baraat_time: z.string().optional().nullable(),
    baraat_venue: z.string().optional().nullable(),

    // Music
    background_music: z.string().url().optional().nullable(),
});

type TraditionalWeddingEventsProps = z.infer<typeof traditionalWeddingEventsSchema>;

const SLIDE_DURATION = 10; // seconds per slide

export const TraditionalWeddingEvents: React.FC<TraditionalWeddingEventsProps> = ({
    groom_name,
    bride_name,
    haldi_date,
    haldi_time,
    haldi_venue,
    sangeet_date,
    sangeet_time,
    sangeet_venue,
    mehendi_date,
    mehendi_time,
    mehendi_venue,
    baraat_date,
    baraat_time,
    baraat_venue,
    background_music,
}) => {
    const { fps } = useVideoConfig();
    const slideFrames = SLIDE_DURATION * fps;
    const crossfadeDuration = 15; // frames for crossfade (0.5 seconds)

    const audioSrc = background_music || staticFile("audio/wedding-default.mp3");

    return (
        <AbsoluteFill>
            {/* Persistent Background Image - Never fades */}
            <Img
                src={staticFile("assets/wedding-events/wedding_bg.jpg")}
                style={{
                    position: "absolute",
                    width: "100%",
                    height: "100%",
                    objectFit: "cover",
                }}
            />

            {/* Background Music */}
            {audioSrc && <Audio src={audioSrc} volume={0.4} />}

            {/* Slide 1: Haldi Ceremony */}
            <Sequence from={0} durationInFrames={slideFrames + crossfadeDuration}>
                <EventSlideContent
                    groomName={groom_name}
                    brideName={bride_name}
                    eventNameHindi="हल्दी"
                    eventNameEnglish="HALDI CEREMONY"
                    date={haldi_date}
                    time={haldi_time}
                    venue={haldi_venue}
                    fadeOutStart={slideFrames}
                    fadeOutDuration={crossfadeDuration}
                />
            </Sequence>

            {/* Slide 2: Sangeet Night - starts slightly before slide 1 ends */}
            <Sequence from={slideFrames - crossfadeDuration} durationInFrames={slideFrames + crossfadeDuration * 2}>
                <EventSlideContent
                    groomName={groom_name}
                    brideName={bride_name}
                    eventNameHindi="संगीत"
                    eventNameEnglish="SANGEET NIGHT"
                    date={sangeet_date}
                    time={sangeet_time}
                    venue={sangeet_venue}
                    fadeInDuration={crossfadeDuration}
                    fadeOutStart={slideFrames + crossfadeDuration}
                    fadeOutDuration={crossfadeDuration}
                />
            </Sequence>

            {/* Slide 3: Mehendi Ceremony */}
            <Sequence from={slideFrames * 2 - crossfadeDuration * 2} durationInFrames={slideFrames + crossfadeDuration * 2}>
                <EventSlideContent
                    groomName={groom_name}
                    brideName={bride_name}
                    eventNameHindi="मेहंदी"
                    eventNameEnglish="MEHENDI CEREMONY"
                    date={mehendi_date}
                    time={mehendi_time}
                    venue={mehendi_venue}
                    fadeInDuration={crossfadeDuration}
                    fadeOutStart={slideFrames + crossfadeDuration}
                    fadeOutDuration={crossfadeDuration}
                />
            </Sequence>

            {/* Slide 4: Barat Procession */}
            <Sequence from={slideFrames * 3 - crossfadeDuration * 3} durationInFrames={slideFrames + crossfadeDuration}>
                <EventSlideContent
                    groomName={groom_name}
                    brideName={bride_name}
                    eventNameHindi="बारात"
                    eventNameEnglish="BARAT PROCESSION"
                    date={baraat_date}
                    time={baraat_time}
                    venue={baraat_venue}
                    fadeInDuration={crossfadeDuration}
                />
            </Sequence>
        </AbsoluteFill>
    );
};

// ============================================
// EVENT SLIDE COMPONENT
// ============================================

interface EventSlideContentProps {
    groomName: string;
    brideName: string;
    eventNameHindi: string;
    eventNameEnglish: string;
    date?: string | null;
    time?: string | null;
    venue?: string | null;
    fadeInDuration?: number;
    fadeOutStart?: number;
    fadeOutDuration?: number;
}

const EventSlideContent: React.FC<EventSlideContentProps> = ({
    groomName,
    brideName,
    eventNameHindi,
    eventNameEnglish,
    date,
    time,
    venue,
    fadeInDuration = 0,
    fadeOutStart,
    fadeOutDuration = 15,
}) => {
    const frame = useCurrentFrame();
    const { fps } = useVideoConfig();

    // Fade in at start
    const fadeIn = fadeInDuration > 0
        ? interpolate(frame, [0, fadeInDuration], [0, 1], { extrapolateLeft: "clamp", extrapolateRight: "clamp" })
        : 1;

    // Fade out at end (if specified)
    const fadeOut = fadeOutStart !== undefined
        ? interpolate(frame, [fadeOutStart, fadeOutStart + fadeOutDuration], [1, 0], { extrapolateLeft: "clamp", extrapolateRight: "clamp" })
        : 1;

    // Combined opacity
    const slideOpacity = fadeIn * fadeOut;

    // Staggered text animations (only on fade in)
    const animationOffset = fadeInDuration;
    const titleOpacity = interpolate(frame, [animationOffset + 5, animationOffset + 20], [0, 1], { extrapolateLeft: "clamp", extrapolateRight: "clamp" });
    const titleY = interpolate(frame, [animationOffset + 5, animationOffset + 20], [30, 0], { extrapolateLeft: "clamp", extrapolateRight: "clamp" });

    const namesOpacity = interpolate(frame, [animationOffset + 20, animationOffset + 35], [0, 1], { extrapolateLeft: "clamp", extrapolateRight: "clamp" });
    const namesScale = spring({ frame: frame - animationOffset - 20, fps, config: { damping: 12, stiffness: 100 } });

    const eventOpacity = interpolate(frame, [animationOffset + 35, animationOffset + 50], [0, 1], { extrapolateLeft: "clamp", extrapolateRight: "clamp" });
    const eventY = interpolate(frame, [animationOffset + 35, animationOffset + 50], [20, 0], { extrapolateLeft: "clamp", extrapolateRight: "clamp" });

    const detailsOpacity = interpolate(frame, [animationOffset + 55, animationOffset + 75], [0, 1], { extrapolateLeft: "clamp", extrapolateRight: "clamp" });
    const detailsY = interpolate(frame, [animationOffset + 55, animationOffset + 75], [20, 0], { extrapolateLeft: "clamp", extrapolateRight: "clamp" });

    const formattedDate = date ? formatDateWithOrdinal(date) : "";

    return (
        <AbsoluteFill style={{ opacity: slideOpacity }}>
            {/* Text Content Overlay - No background, just text */}
            <div style={styles.contentOverlay}>

                {/* Hindi Event Title */}
                <div style={{
                    ...styles.hindiTitle,
                    opacity: titleOpacity,
                    transform: `translateY(${titleY}px)`,
                }}>
                    ॥ श्री गणेशाय नमः ॥
                </div>

                {/* Couple Names - Cursive Style */}
                <div style={{
                    ...styles.coupleNames,
                    opacity: namesOpacity,
                    transform: `scale(${namesScale})`,
                }}>
                    {groomName} & {brideName}
                </div>

                {/* Event Title */}
                <div style={{
                    ...styles.eventTitle,
                    opacity: eventOpacity,
                    transform: `translateY(${eventY}px)`,
                }}>
                    {eventNameEnglish}
                </div>

                {/* Subtext */}
                <div style={{
                    ...styles.subtext,
                    opacity: eventOpacity,
                }}>
                    Together with their families
                </div>

                {/* Event Details */}
                <div style={{
                    ...styles.detailsContainer,
                    opacity: detailsOpacity,
                    transform: `translateY(${detailsY}px)`,
                }}>
                    {/* Date */}
                    {formattedDate && (
                        <div style={styles.dateText}>
                            {formattedDate}
                        </div>
                    )}

                    {/* Time */}
                    {time && (
                        <div style={styles.timeText}>
                            {time}
                        </div>
                    )}

                    {/* "at" separator */}
                    {venue && (
                        <div style={styles.atText}>at</div>
                    )}

                    {/* Venue */}
                    {venue && (
                        <div style={styles.venueText}>
                            {venue.toUpperCase()}
                        </div>
                    )}
                </div>
            </div>
        </AbsoluteFill>
    );
};

// ============================================
// HELPERS
// ============================================

function formatDateWithOrdinal(dateStr: string): string {
    try {
        const date = new Date(dateStr);
        const day = date.getDate();
        const weekday = date.toLocaleDateString('en-US', { weekday: 'long' });
        const month = date.toLocaleDateString('en-US', { month: 'long' });
        const year = date.getFullYear();

        // Get ordinal suffix
        const ordinal = getOrdinalSuffix(day);

        return `${weekday}, ${day}${ordinal} ${month} ${year}`;
    } catch {
        return dateStr;
    }
}

function getOrdinalSuffix(day: number): string {
    if (day > 3 && day < 21) return 'th';
    switch (day % 10) {
        case 1: return 'st';
        case 2: return 'nd';
        case 3: return 'rd';
        default: return 'th';
    }
}

// ============================================
// STYLES - Matching Reference Image
// ============================================

const styles: Record<string, React.CSSProperties> = {
    backgroundImage: {
        position: "absolute",
        width: "100%",
        height: "100%",
        objectFit: "cover",
    },
    contentOverlay: {
        position: "absolute",
        top: 0,
        left: 0,
        right: 0,
        bottom: 0,
        display: "flex",
        flexDirection: "column",
        alignItems: "center",
        justifyContent: "center",
        paddingTop: 120,
        paddingBottom: 400, // Leave space for the couple illustration at bottom
        textAlign: "center",
    },
    hindiTitle: {
        fontFamily: "'Noto Sans Devanagari', 'Arial Unicode MS', serif",
        fontSize: 24,
        color: "#8B7355",
        letterSpacing: 2,
        marginBottom: 20,
    },
    coupleNames: {
        fontFamily: "'Great Vibes', 'Dancing Script', cursive",
        fontSize: 72,
        color: "#8B6914",
        textShadow: "0 2px 10px rgba(139, 105, 20, 0.15)",
        marginBottom: 15,
        lineHeight: 1.2,
    },
    eventTitle: {
        fontFamily: "'Cormorant Garamond', 'Playfair Display', serif",
        fontSize: 28,
        fontWeight: 600,
        color: "#A0522D",
        letterSpacing: 6,
        textTransform: "uppercase",
        marginBottom: 10,
    },
    subtext: {
        fontFamily: "'Cormorant Garamond', Georgia, serif",
        fontSize: 22,
        fontStyle: "italic",
        color: "#8B7355",
        marginBottom: 25,
    },
    detailsContainer: {
        display: "flex",
        flexDirection: "column",
        alignItems: "center",
        gap: 6,
    },
    dateText: {
        fontFamily: "'Cormorant Garamond', Georgia, serif",
        fontSize: 26,
        color: "#5D4E37",
        fontWeight: 500,
    },
    timeText: {
        fontFamily: "'Cormorant Garamond', Georgia, serif",
        fontSize: 36,
        fontWeight: 700,
        color: "#5D4E37",
        marginTop: 5,
    },
    atText: {
        fontFamily: "'Cormorant Garamond', Georgia, serif",
        fontSize: 18,
        fontStyle: "italic",
        color: "#8B7355",
        marginTop: 8,
    },
    venueText: {
        fontFamily: "'Cormorant Garamond', Georgia, serif",
        fontSize: 22,
        fontWeight: 600,
        color: "#A0522D",
        letterSpacing: 3,
        maxWidth: 500,
        lineHeight: 1.4,
    },
};
