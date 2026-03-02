import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import colors from 'tailwindcss/colors'; // Change require to import

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            // Since you are in 'extend', you don't actually need 
            // to define 'red' manually unless you want to rename it. 
            // Tailwind includes it by default.
            colors: {
                danger: colors.red,
                success: colors.green,
                warning: colors.yellow,
            }
        }
    },

    plugins: [forms],
};
