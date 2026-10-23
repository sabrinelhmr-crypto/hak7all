/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    './templates/**/*.twig',
    './src/**/*.php',
    './assets/**/*.js',
    './public/**/*.js'
  ],
  theme: {
    extend: {}
  },
  plugins: []
};
