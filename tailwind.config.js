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
  variants: {
    extend: {
      display: ["group-hover"],
    }
  },
  plugins: [
    '@tailwindcss/forms'
  ],
  important: true,
  darkMode: 'selector',
}
