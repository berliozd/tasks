import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import typography from '@tailwindcss/typography';
import daisyui from "daisyui";

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
            colors: {
                brand: {
                    navy: '#011342',
                    'navy-light': '#0d2461',
                    surface: '#F2F6FF',
                    accent: '#158749',
                    'accent-light': '#1CA85C',
                    'accent-dark': '#0F6B39',
                },
            },
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            boxShadow: {
                soft: '0 1px 2px 0 rgb(2 8 23 / 0.04), 0 1px 3px 0 rgb(2 8 23 / 0.06)',
                card: '0 1px 2px 0 rgb(2 8 23 / 0.04), 0 1px 3px 1px rgb(2 8 23 / 0.05)',
                'card-hover': '0 4px 12px -2px rgb(2 8 23 / 0.10), 0 2px 6px -2px rgb(2 8 23 / 0.06)',
            },
        },
    },

    daisyui: {
        themes: [
            {
                tasks: {
                    'primary': '#158749',
                    'primary-content': '#ffffff',
                    'secondary': '#011342',
                    'secondary-content': '#ffffff',
                    'accent': '#158749',
                    'accent-content': '#ffffff',
                    'neutral': '#1f2937',
                    'neutral-content': '#ffffff',
                    'base-100': '#ffffff',
                    'base-200': '#F5F7FB',
                    'base-300': '#E7EBF3',
                    'base-content': '#1f2937',
                    'info': '#2563eb',
                    'success': '#158749',
                    'warning': '#d97706',
                    'error': '#dc2626',
                    '--rounded-box': '0.75rem',
                    '--rounded-btn': '0.5rem',
                    '--rounded-badge': '9999px',
                },
            },
        ],
        base: true,
        styled: true,
        logs: false,
    },

    plugins: [forms, typography, daisyui],
};
