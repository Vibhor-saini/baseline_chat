/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./resources/**/*.blade.php", // Ye line ensures karti hai ki saare sub-folders (like layouts) scan honge
    "./resources/**/*.js",
    "./resources/**/*.vue",
  ],
  theme: {
    extend: {
      colors: {
        teams: {
          bg: '#0f1014',
          secondary: '#111318',
          hover: '#1a1c22',
          border: '#1c1d22',
          accent: '#5b5fc7',
          text: {
            primary: '#ffffff',
            secondary: '#8b8d97',
          }
        }
      }
    },
  },
  plugins: [],
}