/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./index.html",
    "./src/**/*.{vue,js,ts,jsx,tsx}",
  ],
  theme: {
    extend: {
      colors: {
        // Dashboard palette
        angaza: {
          // Primary tokens used across existing UI classes
          dark: '#024E5D',     // TEAL BLUE
          accent: '#21AEBE',   // JAVA

          // Extended palette tokens
          paleSky: '#6C7C86',  // PALE SKY
          geyser: '#D3DFE1',   // GEYSER
          blue: '#3480DC',     // requested blue accent
        },
      },
    },
  },
  plugins: [],
}
