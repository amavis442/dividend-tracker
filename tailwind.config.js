/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./assets/**/*.js",
    "./templates/**/*.html.twig",
    "./src/Twig/Components/**/*.php",
  ],
  theme: {
    extend: {},
  },
  plugins: [
    '@tailwindcss/forms'
  ],
  important: true,
  darkMode: 'selector',
}
