-- Seed: FirstTemplate JSON Definition
-- This populates an example template with the new single-composition architecture

UPDATE templates 
SET 
    remotion_composition_id = 'GenericTemplate',
    template_definition = '{
  "version": "1.0",
  "fps": 30,
  "width": 1080,
  "height": 1920,
  "slides": [
    {
      "id": "intro",
      "name": "Introduction",
      "startFrame": 0,
      "durationFrames": 90,
      "background": {
        "type": "video",
        "src": "{{backgroundVideoUrl}}",
        "fallback": "https://invitation-video-assets-permanent.s3.us-east-1.amazonaws.com/backgrounds/first-template.mp4"
      },
      "layers": [
        {
          "id": "title",
          "type": "text",
          "fieldKey": "title",
          "defaultValue": "You''re Invited",
          "position": { "x": 540, "y": 400, "anchor": "center" },
          "style": {
            "fontSize": 72,
            "fontFamily": "Inter",
            "color": "{{primaryColor}}",
            "fontWeight": "bold",
            "textShadow": "0 4px 20px rgba(0,0,0,0.5)"
          },
          "animation": {
            "enter": { "type": "fade-in", "durationFrames": 30 }
          }
        },
        {
          "id": "subtitle",
          "type": "text",
          "fieldKey": "subtitle",
          "defaultValue": "Please join us for",
          "position": { "x": 540, "y": 500, "anchor": "center" },
          "style": {
            "fontSize": 36,
            "fontFamily": "Inter",
            "color": "{{secondaryColor}}",
            "fontWeight": "normal"
          },
          "animation": {
            "enter": { "type": "slide-up", "durationFrames": 30, "delay": 15 }
          }
        }
      ]
    },
    {
      "id": "event-details",
      "name": "Event Details",
      "startFrame": 90,
      "durationFrames": 120,
      "background": {
        "type": "video",
        "src": "{{backgroundVideoUrl}}",
        "fallback": "https://invitation-video-assets-permanent.s3.us-east-1.amazonaws.com/backgrounds/first-template.mp4"
      },
      "layers": [
        {
          "id": "eventName",
          "type": "text",
          "fieldKey": "eventName",
          "defaultValue": "Our Special Celebration",
          "position": { "x": 540, "y": 350, "anchor": "center" },
          "style": {
            "fontSize": 56,
            "fontFamily": "Inter",
            "color": "{{primaryColor}}",
            "fontWeight": "bold"
          },
          "animation": {
            "enter": { "type": "zoom-in", "durationFrames": 30 }
          }
        },
        {
          "id": "eventDate",
          "type": "text",
          "fieldKey": "eventDate",
          "defaultValue": "February 14, 2026",
          "position": { "x": 540, "y": 500, "anchor": "center" },
          "style": {
            "fontSize": 48,
            "fontFamily": "Inter",
            "color": "{{secondaryColor}}"
          },
          "animation": {
            "enter": { "type": "fade-in", "durationFrames": 30, "delay": 15 }
          }
        },
        {
          "id": "eventTime",
          "type": "text",
          "fieldKey": "eventTime",
          "defaultValue": "6:00 PM Onwards",
          "position": { "x": 540, "y": 580, "anchor": "center" },
          "style": {
            "fontSize": 36,
            "fontFamily": "Inter",
            "color": "{{secondaryColor}}"
          },
          "animation": {
            "enter": { "type": "fade-in", "durationFrames": 30, "delay": 30 }
          }
        }
      ]
    },
    {
      "id": "venue",
      "name": "Venue",
      "startFrame": 210,
      "durationFrames": 90,
      "background": {
        "type": "video",
        "src": "{{backgroundVideoUrl}}",
        "fallback": "https://invitation-video-assets-permanent.s3.us-east-1.amazonaws.com/backgrounds/first-template.mp4"
      },
      "layers": [
        {
          "id": "venueLabel",
          "type": "text",
          "fieldKey": "",
          "defaultValue": "Join us at",
          "position": { "x": 540, "y": 400, "anchor": "center" },
          "style": {
            "fontSize": 32,
            "fontFamily": "Inter",
            "color": "{{secondaryColor}}"
          },
          "animation": {
            "enter": { "type": "fade-in", "durationFrames": 20 }
          }
        },
        {
          "id": "eventVenue",
          "type": "text",
          "fieldKey": "eventVenue",
          "defaultValue": "Grand Ballroom, Royal Palace Hotel",
          "position": { "x": 540, "y": 500, "anchor": "center" },
          "style": {
            "fontSize": 42,
            "fontFamily": "Inter",
            "color": "{{primaryColor}}",
            "fontWeight": "bold",
            "maxWidth": 900,
            "textAlign": "center"
          },
          "animation": {
            "enter": { "type": "slide-up", "durationFrames": 30, "delay": 10 }
          }
        }
      ]
    }
  ],
  "music": {
    "fieldKey": "musicUrl",
    "fallback": null
  }
}'
WHERE slug = 'first-template'
   OR remotion_composition_id = 'FirstTemplate';
