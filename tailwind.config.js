import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Nunito Sans', ...defaultTheme.fontFamily.sans],
                display: ['Baloo 2', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                dex: {
                    bg: '#1e2419',
                    surface: '#232b1c',
                    card: '#3a4530',
                    dim: '#262e1f',
                    text: '#f4ede1',
                    label: '#dde2c9',
                    meta: '#b7c19c',
                    date: '#9aa384',
                    warm: '#f0d9a8',
                    red: '#e8543f',
                    'red-btn': '#f2634c',
                    'red-shadow': '#a5392b',
                    gold: '#e0c14c',
                    'gold-shadow': '#a8891f',
                    'gold-ink': '#2a2410',
                    'delete-bg': '#4a2b21',
                    'delete-text': '#ff8f7d',
                    'delete-shadow': '#241511',
                    silhouette: '#454f38',
                    'card-shadow': '#171d10',
                    'photo-shadow': '#10150a',
                },
            },
        },
    },

    plugins: [forms],
};
