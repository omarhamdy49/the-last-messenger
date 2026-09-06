import defaultTheme from 'tailwindcss/defaultTheme';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',
    ],

    darkMode: 'class', // Enable dark mode via class

    theme: {
        extend: {
            fontFamily: {
                sans: ['Readex Pro', 'Cairo', ...defaultTheme.fontFamily.sans],
                arabic: ['Amiri', 'serif'],
            },

            fontWeight: {
                'light': '300',
                'normal': '400',
                'semibold': '600',
                'bold': '700',
                'black': '900',
            },

            colors: {
                // Custom blue-green gradient color palette
                primary: {
                    DEFAULT: '#0056b3',
                    50: '#e6f0ff',
                    100: '#b3d1ff',
                    200: '#80b3ff',
                    300: '#4d94ff',
                    400: '#1a75ff',
                    500: '#0056b3',
                    600: '#004080',
                    700: '#002d5c',
                    800: '#001a38',
                    900: '#000714',
                },
                secondary: {
                    DEFAULT: '#218838',
                    50: '#e8f5e9',
                    100: '#c8e6c9',
                    200: '#a5d6a7',
                    300: '#81c784',
                    400: '#66bb6a',
                    500: '#218838',
                    600: '#1b5e20',
                    700: '#145214',
                    800: '#0d3d0d',
                    900: '#062906',
                },
                // ponytail: `brand` was only ever defined in the inline cdn.tailwindcss.com
                // config that layouts/admin.blade.php dropped. Same values as `primary`;
                // aliased rather than rewriting ~90 brand-* usages across the views.
                brand: {
                    DEFAULT: '#0056b3',
                    50: '#e6f0ff',
                    100: '#cce0ff',
                    200: '#99c2ff',
                    300: '#66a3ff',
                    400: '#3385ff',
                    500: '#0056b3',
                    600: '#004799',
                    700: '#003880',
                    800: '#002966',
                    900: '#001a4d',
                },
                accent: {
                    DEFAULT: '#64CCC5',
                    50: '#f0fffe',
                    100: '#ccfffd',
                    200: '#99fffa',
                    300: '#64CCC5',
                    400: '#47b5ae',
                    500: '#2e9d97',
                    600: '#1f7a75',
                    700: '#155c58',
                    800: '#0d3d3b',
                    900: '#061f1e',
                },
            },

            backgroundImage: {
                'gradient-primary': 'linear-gradient(135deg, #0056b3 0%, #218838 100%)',
                'gradient-soft': 'linear-gradient(135deg, rgba(0, 86, 179, 0.95) 0%, rgba(33, 136, 56, 0.9) 100%)',
                'gradient-hero': 'linear-gradient(135deg, rgba(0, 86, 179, 0.95) 0%, rgba(33, 136, 56, 0.9) 100%)',
                'mesh-bg': `radial-gradient(circle at 20% 30%, rgba(0, 86, 179, 0.15) 0%, transparent 50%),
                            radial-gradient(circle at 80% 70%, rgba(33, 136, 56, 0.15) 0%, transparent 50%),
                            radial-gradient(circle at 50% 50%, rgba(100, 204, 197, 0.1) 0%, transparent 50%)`,
            },

            boxShadow: {
                'soft': '0 20px 40px rgba(0, 86, 179, 0.2)',
                'glow': '0 0 20px rgba(0, 86, 179, 0.3)',
                'glow-lg': '0 0 40px rgba(0, 86, 179, 0.6)',
            },

            animation: {
                'fade-in-up': 'fadeInUp 0.6s ease-out forwards',
                'fade-in': 'fadeIn 0.8s ease-out forwards',
                'slide-in-right': 'slideInRight 0.5s ease-out forwards',
                'float': 'float 3s ease-in-out infinite',
                'shimmer': 'shimmer 2s linear infinite',
                'pulse-glow': 'pulseGlow 2s ease-in-out infinite',
                'ken-burns': 'kenBurns 30s ease-in-out infinite',
                'mesh-move': 'meshMove 20s ease-in-out infinite',
                'particle-float': 'particleFloat linear infinite',
                'scroll-bounce': 'scrollBounce 2s ease-in-out infinite',
                // camelCase aliases: the views were written against the old inline config.
                'fadeInUp': 'fadeInUp 0.6s ease-out forwards',
                'fadeIn': 'fadeIn 0.8s ease-out forwards',
                'slideInRight': 'slideInRight 0.5s ease-out forwards',
            },

            keyframes: {
                fadeInUp: {
                    'from': { opacity: '0', transform: 'translateY(30px)' },
                    'to': { opacity: '1', transform: 'translateY(0)' }
                },
                fadeIn: {
                    'from': { opacity: '0' },
                    'to': { opacity: '1' }
                },
                slideInRight: {
                    'from': { opacity: '0', transform: 'translateX(-50px)' },
                    'to': { opacity: '1', transform: 'translateX(0)' }
                },
                float: {
                    '0%, 100%': { transform: 'translateY(0px)' },
                    '50%': { transform: 'translateY(-10px)' }
                },
                shimmer: {
                    '0%': { backgroundPosition: '-1000px 0' },
                    '100%': { backgroundPosition: '1000px 0' }
                },
                pulseGlow: {
                    '0%, 100%': { boxShadow: '0 0 20px rgba(0, 86, 179, 0.3)' },
                    '50%': { boxShadow: '0 0 40px rgba(0, 86, 179, 0.6)' }
                },
                kenBurns: {
                    '0%, 100%': { transform: 'scale(1) translateX(0) translateY(0)' },
                    '50%': { transform: 'scale(1.15) translateX(-20px) translateY(-10px)' }
                },
                meshMove: {
                    '0%, 100%': { backgroundPosition: '0% 50%' },
                    '50%': { backgroundPosition: '100% 50%' }
                },
                particleFloat: {
                    '0%': { opacity: '0', transform: 'translateY(0) translateX(0) scale(1)' },
                    '10%': { opacity: '0.6' },
                    '100%': { opacity: '0', transform: 'translateY(-100vh) translateX(50px) scale(0.3)' }
                },
                scrollBounce: {
                    '0%, 100%': { transform: 'translateY(0)' },
                    '50%': { transform: 'translateY(10px)' }
                }
            },

            transitionTimingFunction: {
                'bounce-in': 'cubic-bezier(0.68, -0.55, 0.265, 1.55)',
                'smooth': 'cubic-bezier(0.4, 0, 0.2, 1)',
            },

            transitionDuration: {
                'fast': '300ms',
                'medium': '500ms',
                'slow': '800ms',
            },

            spacing: {
                '18': '4.5rem',
                '88': '22rem',
                '100': '25rem',
                '112': '28rem',
                '128': '32rem',
            },

            zIndex: {
                '60': '60',
                '70': '70',
                '80': '80',
                '90': '90',
                '100': '100',
            },

            backdropBlur: {
                xs: '2px',
            },
        },
    },

    plugins: [
        // RTL support plugin
        function({ addUtilities }) {
            addUtilities({
                '.rtl': {
                    direction: 'rtl',
                },
                '.ltr': {
                    direction: 'ltr',
                },
            })
        },
    ],
};
