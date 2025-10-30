import './bootstrap';
import Alpine from 'alpinejs';

// Alpine.js components
import { smartCarousel } from './components/smart-carousel';
import { videoPlayer } from './components/video-player';
import { enhancedDashboard } from './components/enhanced-dashboard';

// Register Alpine globally
window.Alpine = Alpine;

// Global Alpine data components
Alpine.data('smartCarousel', smartCarousel);
Alpine.data('videoPlayer', videoPlayer);
Alpine.data('enhancedDashboard', enhancedDashboard);

// Start Alpine
Alpine.start();

// Lucide Icons initialization
import { createIcons, icons } from 'lucide';

// Initialize icons on page load
document.addEventListener('DOMContentLoaded', () => {
    createIcons({ icons });
});

// Re-initialize icons after dynamic content loads
window.refreshIcons = () => {
    createIcons({ icons });
};

// Global helper for Alpine components
window.alpineHelpers = {
    // Format date helper
    formatDate(date, locale = 'ar') {
        return new Intl.DateTimeFormat(locale, {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        }).format(new Date(date));
    },

    // Format number helper
    formatNumber(number, locale = 'ar') {
        return new Intl.NumberFormat(locale).format(number);
    },

    // Truncate text helper
    truncate(text, length = 100) {
        if (text.length <= length) return text;
        return text.substring(0, length) + '...';
    },

    // Debounce helper
    debounce(func, wait = 300) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
};
