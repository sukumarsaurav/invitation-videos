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
        },
    },
    plugins: [],
}
