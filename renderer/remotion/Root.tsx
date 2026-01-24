/**
 * Root entry point for Remotion Renderer
 * 
 * This file registers all video compositions (templates) that can be rendered.
 * Each composition maps to a template in the admin panel via `remotion_composition_id`
 * 
 * Copied from remotion-studio and adapted for Cloud Run deployment.
 */

import React from 'react';
import { Composition } from "remotion";

// Import all template compositions
import { RoyalWeddingGold, royalWeddingGoldSchema } from "./templates/wedding/RoyalWeddingGold";
import { ModernMinimalistWedding, modernMinimalistWeddingSchema } from "./templates/wedding/ModernMinimalistWedding";
import { TraditionalPeacockWedding, traditionalPeacockWeddingSchema } from "./templates/wedding/TraditionalPeacockWedding";
import { TraditionalWeddingEvents, traditionalWeddingEventsSchema } from "./templates/wedding/TraditionalWeddingEvents";
import { BirthdayPartyNeon, birthdayPartyNeonSchema } from "./templates/birthday/BirthdayPartyNeon";

// Video settings
const FPS = 30;
const PORTRAIT_WIDTH = 1080;
const PORTRAIT_HEIGHT = 1920;

export const RemotionRoot: React.FC = () => {
    return (
        <>
            {/* ============================================== */}
            {/*              WEDDING TEMPLATES                 */}
            {/* ============================================== */}

            <Composition
                id="RoyalWeddingGold"
                component={RoyalWeddingGold}
                durationInFrames={30 * FPS}
                fps={FPS}
                width={PORTRAIT_WIDTH}
                height={PORTRAIT_HEIGHT}
                schema={royalWeddingGoldSchema}
                defaultProps={{
                    groom_name: "Rahul",
                    bride_name: "Priya",
                    wedding_date: "2026-02-14",
                    venue_name: "Grand Palace Hotel",
                    venue_address: "123 Royal Street, Mumbai",
                    couple_photo: "https://images.unsplash.com/photo-1519741497674-611481863552?w=400",
                    music_url: null,
                }}
            />

            <Composition
                id="ModernMinimalistWedding"
                component={ModernMinimalistWedding}
                durationInFrames={25 * FPS}
                fps={FPS}
                width={PORTRAIT_WIDTH}
                height={PORTRAIT_HEIGHT}
                schema={modernMinimalistWeddingSchema}
                defaultProps={{
                    partner1_name: "Alex",
                    partner2_name: "Jordan",
                    event_date: "2026-03-20",
                    event_time: "16:00",
                    location: "Sunset Gardens",
                    cover_image: "https://images.unsplash.com/photo-1511285560929-80b456fea0bc?w=400",
                    accent_color: "#c9a227",
                }}
            />

            <Composition
                id="TraditionalPeacockWedding"
                component={TraditionalPeacockWedding}
                durationInFrames={35 * FPS}
                fps={FPS}
                width={PORTRAIT_WIDTH}
                height={PORTRAIT_HEIGHT}
                schema={traditionalPeacockWeddingSchema}
                defaultProps={{
                    groom_name: "Rahul",
                    bride_name: "Priya",
                    wedding_date: "2026-02-14",
                    wedding_time: "7:00 PM",
                    venue_name: "Royal Palace Banquet",
                    venue_address: "123 Heritage Road, Jaipur, Rajasthan",
                    couple_photo: "https://images.unsplash.com/photo-1519741497674-611481863552?w=400",
                    music_url: null,
                }}
            />

            <Composition
                id="TraditionalWeddingEvents"
                component={TraditionalWeddingEvents}
                durationInFrames={40 * FPS}
                fps={FPS}
                width={PORTRAIT_WIDTH}
                height={PORTRAIT_HEIGHT}
                schema={traditionalWeddingEventsSchema}
                defaultProps={{
                    groom_name: "Rahul",
                    bride_name: "Priya",
                    caricature_image: null,
                    couple_photo: "https://images.unsplash.com/photo-1519741497674-611481863552?w=400",
                    haldi_date: "2026-06-14",
                    haldi_time: "10:00 AM onwards",
                    haldi_venue: "Bride's Residence",
                    sangeet_date: "2026-06-14",
                    sangeet_time: "7:00 PM onwards",
                    sangeet_venue: "Grand Ballroom",
                    mehendi_date: "2026-06-14",
                    mehendi_time: "4:00 PM onwards",
                    mehendi_venue: "Garden Lawn",
                    baraat_date: "2026-06-15",
                    baraat_time: "7:00 PM",
                    baraat_venue: "Starting from Temple",
                    background_music: null,
                }}
            />

            {/* ============================================== */}
            {/*              BIRTHDAY TEMPLATES                */}
            {/* ============================================== */}

            <Composition
                id="BirthdayPartyNeon"
                component={BirthdayPartyNeon}
                durationInFrames={20 * FPS}
                fps={FPS}
                width={PORTRAIT_WIDTH}
                height={PORTRAIT_HEIGHT}
                schema={birthdayPartyNeonSchema}
                defaultProps={{
                    birthday_person: "Aarav",
                    age: "5",
                    party_date: "2026-01-25",
                    party_time: "17:00",
                    venue: "Fun Zone Play Area",
                    child_photo: "https://images.unsplash.com/photo-1503454537195-1dcabb73ffb9?w=400",
                    theme_color: "#ff00ff",
                }}
            />
        </>
    );
};
