/** @type {import('tailwindcss').Config} */
module.exports = {
    content: [
        "./templates/**/*.php",
        "./admin/**/*.php",
        "./index.php"
    ],
    // Dark mode enabled for admin panel (uses .dark class)
    darkMode: "class",
    theme: {
        extend: {
            colors: {
                // Primary Color - Used for buttons, links, and accents
                "primary": "#970747",
                // Text Colors
                "text-primary": "#b69b5b",      // Main headings and important text
                "text-secondary": "#404040",    // Body text and descriptions
                // Background Colors
                "background-light": "#fdf7f3",  // Light mode background
                "background-dark": "#191022",   // Dark mode background (admin)
                // Header Colors
                "header-bg": "#2c0914",         // Header background
                "header-text": "#b69b5b",       // Header links and text
                "header-hover": "#fdf7f3",      // Header link hover state
                // Footer Colors
                "footer-bg": "#2c0914",         // Footer background
                "footer-text": "#b69b5b",       // Footer links and text
                "footer-hover": "#fdf7f3",      // Footer link hover state
                // Surface colors
                "surface-light": "#ffffff",
                "surface-dark": "#251b30",      // Dark mode surface (admin)
            },
            fontFamily: {
                "display": ["Plus Jakarta Sans", "sans-serif"],
            },
            screens: {
                'xs': '480px',    // 480-767: small mobile to large mobile
                'sm': '768px',    // 768-1023: tablet (industry standard)
                'md': '1024px',   // 1024-1279: small desktop
                'lg': '1280px',   // 1280-1535: desktop
                'xl': '1536px',   // 1536+: large screens
            },
        },
    },
    plugins: [],
}
