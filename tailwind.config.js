/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
      "./*.{html,php}", // Include php files
      "./includes/*.{html,php}",
      "./src/*.{js,css}", // Include files in src directory
      "./admin/*.{html,php}", // Include files in the admin directory
       "./admin/includes/*.{html,php}", // Include files in the admin/includes directory

  ],
  theme: {
      extend: {
          colors: {
              primary: '#546e7a',
              secondary: '#aed581',
              accent: '#b3e5fc',
              neutral: {
                  DEFAULT: '#fafafa',
                  dark: '#424242'
              },
              error: '#e57373',
          },
          fontFamily: {
              'primary': ['Raleway', 'sans-serif'],
              'secondary': ['Merriweather', 'serif'],
          },
      },
  },
  plugins: [],
}

