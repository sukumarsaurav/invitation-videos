/** @type {import('tailwindcss').Config} */
module.exports = {
    content: [
        "./templates/**/*.php",
        "./admin/**/*.php",
        "./index.php"
    ],
    darkMode: "class",
    theme: {
        extend: {
            colors: {
                "primary": "#7f13ec",
                "background-light": "#f7f6f8",
                "background-dark": "#191022",
                "surface-light": "#ffffff",
                "surface-dark": "#251b30",
            },
            fontFamily: {
                "display": ["Plus Jakarta Sans", "sans-serif"],
            },
            screens: {
                'xs': '481px',    // 481-768: small mobile to tablet
                'sm': '769px',   // 769-1023: tablet
                'md': '1024px',  // 1024-1279: small desktop
                'lg': '1280px',  // 1280-1535: desktop
                'xl': '1536px',  // 1536+: large screens
            },
        },
    },
    plugins: [],
}
