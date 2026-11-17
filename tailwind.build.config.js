import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** Slim content scan so local builds finish when C: is low on disk. */
export default {
    content: [
        './resources/views/layouts/**/*.blade.php',
        './resources/views/components/**/*.blade.php',
        './resources/views/phone_directory/**/*.blade.php',
        './resources/views/livewire/**/*.blade.php',
        './resources/js/**/*.js',
    ],
    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                dms: {
                    sidebar: '#0F172A',
                    active: '#4F46E5',
                },
            },
        },
    },
    plugins: [forms],
};
