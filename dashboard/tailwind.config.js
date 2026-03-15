/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./index.html",
    "./src/**/*.{vue,js,ts,jsx,tsx}",
  ],
  theme: {
    extend: {
      colors: {
        // Angaza Center brand (angazacenter.org) — dark teal + light turquoise
        angaza: {
          dark: '#004d40',    // dark teal (top bar, buttons, primary)
          accent: '#32afa9',  // light teal/turquoise (logo, links, highlights)
        },
      },
    },
  },
  plugins: [],
}
