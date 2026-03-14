/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./index.html",
    "./src/**/*.{vue,js,ts,jsx,tsx}",
  ],
  theme: {
    extend: {
      colors: {
        // Angaza Center brand (https://www.angazacenter.org/)
        angaza: {
          dark: '#0e5c5c',    // dark teal/blue-green (header, footer, primary)
          accent: '#14b8a6',  // bright teal-green (CTAs, links)
        },
      },
    },
  },
  plugins: [],
}
