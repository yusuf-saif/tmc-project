import defaultTheme from 'tailwindcss/defaultTheme';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './resources/**/*.vue',
        './app/Livewire/**/*.php',
        './app/Filament/**/*.php',
    ],
    theme: {
        extend: {
            colors: {
                teal: {
                    DEFAULT: '#1A6B72',
                    dk: '#0D3F44',
                    md: '#2A8A93',
                    lt: '#D6EDEF',
                },
                gold: {
                    DEFAULT: '#C8A84B',
                    lt: '#E8CB7A',
                    pale: '#FDF6E3',
                },
                ivory: '#FAF8F3',
                ink: {
                    DEFAULT: '#1C1A17',
                    md: '#3D3A35',
                    soft: '#6B6760',
                },
            },
            fontFamily: {
                sans: ['Nunito', ...defaultTheme.fontFamily.sans],
                display: ['Dancing Script', 'cursive'],
                arabic: ['Amiri', 'serif'],
            },
        },
    },
    plugins: [],
};
