import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import typography from '@tailwindcss/typography';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './vendor/laravel/jetstream/**/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],

    theme: {
        extend: {
            sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            colors: {
                brand: {
                    DEFAULT: '#1E40AF',
                    light: '#60A5FA',
                    dark: '#1E3A8A',
                },
            },
        },
    },

    plugins: [forms, typography],
};
