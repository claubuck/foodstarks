import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                primary: {
                    50: '#fef7ee',
                    100: '#fdedd6',
                    200: '#f9d7ad',
                    300: '#f4b978',
                    400: '#ee9242',
                    500: '#ea751e',
                    600: '#db5b14',
                    700: '#b54412',
                    800: '#903617',
                    900: '#742f16',
                    950: '#3f1509',
                },
            },
        },
    },

    plugins: [forms],
};
