/**
 * Kontentainment Charts — Dashboard Interactivity
 */

document.addEventListener('DOMContentLoaded', function() {
    
    // 1. THEME SWITCHER
    const themeToggleBtn = document.getElementById('kc-theme-toggle');
    const root = document.documentElement;
    const currentTheme = localStorage.getItem('kc-dashboard-theme') || 'light';

    // Initial state
    root.setAttribute('data-theme', currentTheme);
    updateThemeLabel(currentTheme);

    if (themeToggleBtn) {
        themeToggleBtn.addEventListener('click', function() {
            const nextTheme = root.getAttribute('data-theme') === 'light' ? 'dark' : 'light';
            root.setAttribute('data-theme', nextTheme);
            localStorage.setItem('kc-dashboard-theme', nextTheme);
            updateThemeLabel(nextTheme);
        });
    }

    function updateThemeLabel(theme) {
        const span = themeToggleBtn ? themeToggleBtn.querySelector('span') : null;
        if (span) {
            span.textContent = theme === 'light' ? 'Switch to Dark Mode' : 'Switch to Light Mode';
        }
    }

    // 2. TABS / MODULE NAVIGATION
    // Built for future expansion of the dashboard router/controller

});
