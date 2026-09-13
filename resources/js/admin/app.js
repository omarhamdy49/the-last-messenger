/**
 * Admin Dashboard Application Entry Point
 * Initializes Alpine.js, Lucide Icons, and Chart.js
 */

import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';
import { createIcons as renderLucideIcons, icons } from 'lucide';
const createIcons = (options = {}) => renderLucideIcons({ icons, ...options });
import Chart from 'chart.js/auto';

// Import admin store
import './store.js';

// Import language tabs helper
import './language-tabs.js';

// Make Alpine and Chart available globally
window.Alpine = Alpine;
Alpine.plugin(collapse);
window.Chart = Chart;

// Initialize Lucide icons
window.lucide = { createIcons };

// Initialize icons on page load
document.addEventListener('DOMContentLoaded', () => {
    createIcons();
});

// Reinitialize icons after Alpine updates
Alpine.magic('lucide', () => {
    return () => {
        createIcons();
    };
});

// Custom Alpine directive for auto-growing textareas
Alpine.directive('auto-grow', (el) => {
    el.style.overflow = 'hidden';
    const resize = () => {
        el.style.height = 'auto';
        el.style.height = el.scrollHeight + 'px';
    };
    el.addEventListener('input', resize);
    resize();
});

// Custom Alpine directive for click outside
Alpine.directive('click-outside', (el, { expression }, { evaluate }) => {
    const handler = (e) => {
        if (!el.contains(e.target)) {
            evaluate(expression);
        }
    };
    document.addEventListener('click', handler);
    // Cleanup when element is removed
    el._clickOutsideCleanup = () => {
        document.removeEventListener('click', handler);
    };
});

// Cleanup directive
Alpine.directive('cleanup', (el) => {
    return () => {
        if (el._clickOutsideCleanup) {
            el._clickOutsideCleanup();
        }
    };
});

// Start Alpine
Alpine.start();

// Log initialization
console.log('Admin dashboard initialized');
console.log('Alpine.js version:', Alpine.version);
console.log('Chart.js version:', Chart.version);
