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
            const dots = wrap.querySelectorAll('.kc-dot');
            const nextBtn = wrap.querySelector('.kc-next');
            const prevBtn = wrap.querySelector('.kc-prev');
            if (!slides.length) return;

            let currentIdx = 0;

            const update = (idx) => {
                slides.forEach((s, i) => s.classList.toggle('is-active', i === idx));
                dots.forEach((d, i) => d.classList.toggle('active', i === idx));
                currentIdx = idx;
            };

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

            dots.forEach((dot, i) => {
                dot.addEventListener('click', () => update(i));
            });
        });
    };

    initExpandables();
    initSliders();
});
