/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./resources/**/*.blade.php",
    "./resources/**/*.js",
    "./resources/**/*.vue",
  ],
  theme: {
    fontFamily: {
      sans: ['quicksands', 'ui-sans-serif', 'system-ui'],
    },
    extend: {
      fontFamily: {
        main: ['quicksands'],
      },
      colors: {
        primary: {
          dark: '#136C40',
          DEFAULT: '#36B37E',
          light: '#DDF6E2',
        },
        secodary: {
          DEFAULT: '#FFAB00',
          dark: '#B76E00',
          light: '#FFF5CC',
        },
        grey: {
          100: '#F9FAFB',
          200: '#F4F6F8',
          300: '#DFE3E8',
          400: '#C4CDD5',
          500: '#919EAB',
          600: '#637381',
          700: '#454F5B',
          800: '#212B36',
        },
        error: {
          DEFAULT: '#FF5630',
        },
      },
    },
  },
  plugins: [],
}

