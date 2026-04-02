/**
 * Kontentainment Charts — Frontend Interactions
 */

document.addEventListener('DOMContentLoaded', function() {
    
    // 1. EXPANDABLE ROWS (Archive & Ranking Lists)
    const initExpandables = () => {
        const rows = document.querySelectorAll('.kc-rank-row, .kc-row-header');
        
        rows.forEach(row => {
            row.addEventListener('click', function(e) {
                // Ignore clicks on direct links within the row
                if (e.target.tagName === 'A') return;
                
                const currentOpen = document.querySelector('.is-expanded');
                const targetRow = this.closest('.kc-rank-row') || this.closest('.kc-row-item');
                
                // If clicking a row that's already open, just collapse it
                if (targetRow.classList.contains('is-expanded')) {
                    targetRow.classList.remove('is-expanded');
                    return;
                }

                // Close other open rows
                if (currentOpen) {
                    currentOpen.classList.remove('is-expanded');
                }

                // Open this row
                targetRow.classList.add('is-expanded');
            });
        });
    };

    // 2. HERO SLIDER SYSTEM
    const initSliders = () => {
        const wrappers = document.querySelectorAll('.kc-hero-slider-wrap');
        
        wrappers.forEach(wrap => {
            const container = wrap.querySelector('.kc-hero-slider');
            if (!container) return;
            
            const slides = wrap.querySelectorAll('.kc-slide');
            const nextBtn = wrap.querySelector('.kc-next');
            const prevBtn = wrap.querySelector('.kc-prev');
            const progressBar = wrap.querySelector('.kc-progress-bar');
            if (!slides.length) return;

            let currentIdx = 0;

            const update = (idx) => {
                slides.forEach((s, i) => s.classList.toggle('is-active', i === idx));
                
                // Update progress bar
                if (progressBar) {
                    const progress = ((idx + 1) / slides.length) * 100;
                    progressBar.style.width = `${progress}%`;
                }

                currentIdx = idx;
            };

            // Initialize progress
            update(0);

            if (nextBtn) {
                nextBtn.addEventListener('click', () => {
                    let next = (currentIdx + 1) % slides.length;
                    update(next);
                });
            }

            if (prevBtn) {
                prevBtn.addEventListener('click', () => {
                    let prev = (currentIdx - 1 + slides.length) % slides.length;
                    update(prev);
                });
            }
        });
    };

    // 3. MOBILE MENU TOGGLE
    const initMobileMenu = () => {
        const trigger = document.querySelector('.kc-mobile-trigger');
        const root = document.querySelector('.kc-root');
        
        if (trigger && root) {
            trigger.addEventListener('click', function(e) {
                e.stopPropagation();
                root.classList.toggle('is-nav-open');
            });
            
            // Close when clicking outside or on a link
            document.addEventListener('click', function(e) {
                if (root.classList.contains('is-nav-open') && !e.target.closest('.charts-nav')) {
                    root.classList.remove('is-nav-open');
                }
            });
        }
    };

    initExpandables();
    initSliders();
    initMobileMenu();
});
