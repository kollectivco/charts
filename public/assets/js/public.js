/**
 * Kontentainment Charts — Frontend Interactions
 */

document.addEventListener('DOMContentLoaded', function() {
    
    // 1. EXPANDABLE ROWS (Archive & Ranking Lists)
    const initExpandables = () => {
        document.addEventListener('click', function(e) {
            const rowTrigger = e.target.closest('.kc-rank-row, .kc-row-header');
            if (!rowTrigger) return;

            // Don't expand if clicking a link or inside a link
            if (e.target.tagName === 'A' || e.target.closest('a')) return;

            const target = rowTrigger.closest('.kc-rank-row') || rowTrigger.closest('.kc-row-item');
            if (!target) return;

            const isAlreadyExpanded = target.classList.contains('is-expanded');

            // Close others (exclusive mode)
            document.querySelectorAll('.is-expanded').forEach(el => el.classList.remove('is-expanded'));

            // Toggle current if it wasn't already expanded
            if (!isAlreadyExpanded) {
                target.classList.add('is-expanded');
            }
        });
    };

    // 2. MOTION CAROUSEL ENGINE
    const initMotionCarousel = () => {
        const wraps = document.querySelectorAll('.kc-slider-system');
        
        wraps.forEach(wrap => {
            const container = wrap.querySelector('.kc-motion-carousel');
            const slides = wrap.querySelectorAll('.kc-motion-slide');
            const nextBtn = wrap.querySelector('.kc-motion-next');
            const prevBtn = wrap.querySelector('.kc-motion-prev');
            const dots = wrap.querySelectorAll('.kc-motion-dot');
            if (!slides.length) return;

            const opts = JSON.parse(wrap.getAttribute('data-config') || '{}');
            const style = opts.style || 'coverflow';

            // Configure
            const config = {
                speed: parseInt(opts.speed) || 600,
                easing: opts.easing || 'cubic-bezier(0.25, 1, 0.5, 1)',
                rotation: parseFloat(opts.rotation) || 45,
                depth: parseFloat(opts.depth) || 150,
                spacing: parseFloat(opts.spacing) || 50, 
                autoplay: opts.autoplay || false,
                delay: parseInt(opts.delay) || 3000,
                loop: opts.loop || false,
                opacity: parseFloat(opts.opacity) || 0.6,
                scale: parseFloat(opts.scale) || 0.8,
            };

            let currentIdx = 0;
            let total = slides.length;
            let isDragging = false;
            let startX = 0;
            let currentX = 0;
            let containerWidth = container.offsetWidth || window.innerWidth;

            const update = (idx, offsetPx = 0, instantaneous = false) => {
                if (idx !== null) {
                    // bounds check
                    if (!config.loop) {
                        if (idx < 0) idx = 0;
                        if (idx >= total) idx = total - 1;
                    } else {
                        if (idx < 0) idx = total - 1;
                        if (idx >= total) idx = 0;
                    }
                    currentIdx = idx;
                }

                slides.forEach((slide, i) => {
                    let offset = i - currentIdx;
                    
                    if (config.loop) {
                        if (offset < -Math.floor(total/2)) offset += total;
                        if (offset > Math.floor(total/2)) offset -= total;
                    }

                    // Drag progression logic
                    let dragPercent = (offsetPx / containerWidth) * 100;
                    // adjust ratio so dragging feels 1:1
                    let simulatedOffset = offset - (dragPercent / (config.spacing || 50)); 
                    
                    const absOffset = Math.abs(simulatedOffset);
                    const isCenter = Math.abs(simulatedOffset) < 0.1;

                    let transform = '';
                    let opacity = config.opacity;
                    
                    if (instantaneous) {
                        slide.style.transition = 'none';
                    } else {
                        slide.style.transition = `transform ${config.speed}ms ${config.easing}, opacity ${config.speed}ms ${config.easing}`;
                    }

                    if (style === 'coverflow') {
                        let rotate = Math.max(-config.rotation, Math.min(config.rotation, simulatedOffset * config.rotation));
                        let translateZ = -Math.abs(simulatedOffset) * config.depth;
                        let translateX = simulatedOffset * config.spacing;
                        transform = `translateX(${translateX}%) translateZ(${translateZ}px) rotateY(${-rotate}deg)`;
                        opacity = 1 - (absOffset * (1 - config.opacity));
                    } else if (style === 'stacked') {
                        let translateX = simulatedOffset * config.spacing;
                        let rotate = simulatedOffset * (config.rotation / Math.max(1, total));
                        let translateZ = -Math.abs(simulatedOffset) * config.depth;
                        let scale = Math.pow(config.scale, absOffset);
                        transform = `translateX(${translateX}%) translateZ(${translateZ}px) scale(${scale}) rotateZ(${rotate}deg)`;
                        opacity = 1 - (absOffset * (1 - config.opacity));
                    } else if (style === 'minimal') {
                        let translateX = simulatedOffset * config.spacing;
                        let scale = 1 - (absOffset * (1 - config.scale));
                        transform = `translateX(${translateX}%) scale(${scale})`;
                        opacity = 1 - (absOffset * (1 - config.opacity));
                    }

                    if (absOffset > 2 && !config.loop) opacity = 0;

                    slide.style.transform = transform;
                    slide.style.opacity = Math.max(0, Math.min(1, opacity));
                    slide.style.zIndex = Math.round(100 - absOffset * 10);
                    
                    if(!instantaneous && offsetPx === 0) {
                        slide.classList.toggle('is-center', i === currentIdx);
                    }
                });

                if (dots.length && offsetPx === 0 && idx !== null && !instantaneous) {
                    dots.forEach((dot, i) => dot.classList.toggle('is-active', i === currentIdx));
                }
            };

            update(0);

            const goNext = () => update(currentIdx + 1);
            const goPrev = () => update(currentIdx - 1);

            if (nextBtn) nextBtn.addEventListener('click', goNext);
            if (prevBtn) prevBtn.addEventListener('click', goPrev);
            dots.forEach((dot, i) => dot.addEventListener('click', () => update(i)));

            // Interaction handlers (Drag & Touch)
            const dragStart = (e) => {
                if (e.target.closest('a') && e.type !== 'touchstart') return; // let links work
                isDragging = true;
                startX = e.type.includes('touch') ? e.touches[0].clientX : e.clientX;
                currentX = startX;
                containerWidth = container.offsetWidth || window.innerWidth;
                container.style.cursor = 'grabbing';
            };

            const dragMove = (e) => {
                if (!isDragging) return;
                currentX = e.type.includes('touch') ? e.touches[0].clientX : e.clientX;
                const diff = currentX - startX;
                update(null, diff, true);
            };

            const dragEnd = (e) => {
                if (!isDragging) return;
                isDragging = false;
                container.style.cursor = '';
                
                const diff = currentX - startX;
                
                // If dragged more than 10% of container width, swipe
                if (Math.abs(diff) > containerWidth * 0.10) {
                    if (diff > 0) goPrev();
                    else goNext();
                } else {
                    update(currentIdx); // Snap back to center
                }
                startX = 0;
                currentX = 0;
            };

            container.addEventListener('mousedown', dragStart);
            window.addEventListener('mousemove', dragMove);
            window.addEventListener('mouseup', dragEnd);
            // Prevent image ghost dragging
            container.addEventListener('dragstart', e => e.preventDefault());

            container.addEventListener('touchstart', dragStart, {passive:true});
            window.addEventListener('touchmove', dragMove, {passive:false}); // passive false to allow preventDefault if needed later, but not strictly required
            window.addEventListener('touchend', dragEnd);

            // Optional keyboard access
            container.setAttribute('tabindex', '0');
            container.addEventListener('keydown', (e) => {
                if (e.key === 'ArrowLeft') goPrev();
                if (e.key === 'ArrowRight') goNext();
            });

            if (config.autoplay) {
                let interval = setInterval(goNext, config.delay);
                wrap.addEventListener('mouseenter', () => clearInterval(interval));
                wrap.addEventListener('mouseleave', () => interval = setInterval(goNext, config.delay));
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

    // 4. WIDGET CAROUSEL ENGINE (Standard Swiper-like fallback)
    const initWidgetCarousels = () => {
        const carousels = document.querySelectorAll('.kc-widget-carousel-wrap');
        
        carousels.forEach(wrap => {
            if (wrap.dataset.initialized) return;
            wrap.dataset.initialized = "1";

            const container = wrap.querySelector('.swiper-container');
            const wrapper = wrap.querySelector('.swiper-wrapper');
            const slides = wrap.querySelectorAll('.swiper-slide');
            const nextBtn = wrap.querySelector('.kc-next');
            const prevBtn = wrap.querySelector('.kc-prev');
            if (!slides.length) return;

            const config = JSON.parse(wrap.getAttribute('data-carousel-config') || '{}');
            let currentIdx = 0;
            const total = slides.length;
            let timer = null;
            
            const getSlidesPerView = () => {
                const w = window.innerWidth;
                if (w < 768) return config.slidesPerView?.mobile || 1;
                if (w < 1024) return config.slidesPerView?.tablet || 2;
                return config.slidesPerView?.desktop || 3;
            };

            const update = (idx) => {
                const spv = getSlidesPerView();
                const maxIdx = config.loop ? total - 1 : Math.max(0, total - spv);
                
                if (idx < 0) {
                    idx = config.loop ? maxIdx : 0;
                } else if (idx > maxIdx) {
                    idx = config.loop ? 0 : maxIdx;
                }
                
                currentIdx = idx;
                const offset = (currentIdx * (100 / spv));
                
                wrapper.style.transition = 'transform 0.8s cubic-bezier(0.16, 1, 0.3, 1)';
                wrapper.style.transform = `translateX(-${offset}%)`;
            };

            if (nextBtn) nextBtn.addEventListener('click', () => {
                update(currentIdx + 1);
                resetTimer();
            });
            if (prevBtn) prevBtn.addEventListener('click', () => {
                update(currentIdx - 1);
                resetTimer();
            });

            const startTimer = () => {
                if (config.autoplay && !timer) {
                    timer = setInterval(() => update(currentIdx + 1), 5000);
                }
            };
            const stopTimer = () => {
                if (timer) {
                    clearInterval(timer);
                    timer = null;
                }
            };
            const resetTimer = () => {
                stopTimer();
                startTimer();
            };

            wrap.addEventListener('mouseenter', stopTimer);
            wrap.addEventListener('mouseleave', startTimer);

            // Initial alignment
            update(0);
            startTimer();
            
            window.addEventListener('resize', () => update(currentIdx));
        });
    };

    initExpandables();
    initMotionCarousel();
    initMobileMenu();
    initWidgetCarousels();

    // Elementor Preview Init
    if (window.elementorFrontend) {
        elementorFrontend.hooks.addAction('frontend/element_ready/charts_carousel.default', initWidgetCarousels);
        elementorFrontend.hooks.addAction('frontend/element_ready/hero_slider.default', initMotionCarousel);
    }
});

/**
 * Premium Billboard Slider Engine
 */
class BillboardEngine {
    constructor(el, config) {
        this.el = el;
        this.slides = el.querySelectorAll('.kc-billboard-slide');
        this.dots = el.querySelectorAll('.kc-bb-dot');
        this.config = config;
        this.current = 0;
        this.total = this.slides.length;
        this.timer = null;
        this.isInteracting = false;

        this.init();
    }

    init() {
        if (this.total <= 1) return;
        
        if (this.config.autoplay) {
            this.startTimer();
            if (this.config.pause) {
                this.el.addEventListener('mouseenter', () => this.stopTimer());
                this.el.addEventListener('mouseleave', () => this.startTimer());
            }
        }
    }

    goTo(index) {
        if (index === this.current) return;
        
        // Handle loop
        if (index < 0) {
            if (this.config.loop) index = this.total - 1;
            else index = 0;
        } else if (index >= this.total) {
            if (this.config.loop) index = 0;
            else index = this.total - 1;
        }

        this.slides[this.current].classList.remove('is-active');
        this.dots[this.current].classList.remove('is-active');
        
        this.current = index;
        
        this.slides[this.current].classList.add('is-active');
        this.dots[this.current].classList.add('is-active');

        if (this.config.autoplay && !this.isInteracting) {
            this.stopTimer();
            this.startTimer();
        }
    }

    next() { this.goTo(this.current + 1); }
    prev() { this.goTo(this.current - 1); }

    startTimer() {
        this.stopTimer();
        this.timer = setInterval(() => this.next(), this.config.delay);
    }

    stopTimer() {
        if (this.timer) clearInterval(this.timer);
    }
}
window.BillboardEngine = BillboardEngine;

/**
 * Premium Slider Engine
 */
class PremiumSliderEngine {
    constructor(el, config) {
        this.el = el;
        this.slides = el.querySelectorAll('.kc-ps-slide');
        this.dots = el.querySelectorAll('.kc-ps-dot');
        this.config = config;
        this.current = 0;
        this.total = this.slides.length;
        this.timer = null;
        this.isInteracting = false;

        this.init();
    }

    init() {
        if (this.total <= 1) return;
        
        if (this.config.autoplay) {
            this.startTimer();
            this.el.addEventListener('mouseenter', () => this.stopTimer());
            this.el.addEventListener('mouseleave', () => this.startTimer());
        }

        // Swipe support
        let startX = 0;
        this.el.addEventListener('touchstart', (e) => startX = e.touches[0].clientX, {passive: true});
        this.el.addEventListener('touchend', (e) => {
            let endX = e.changedTouches[0].clientX;
            if (startX - endX > 50) this.next();
            if (endX - startX > 50) this.prev();
        }, {passive: true});
    }

    goTo(index) {
        if (index === this.current) return;
        
        if (index < 0) index = this.config.loop ? this.total - 1 : 0;
        else if (index >= this.total) index = this.config.loop ? 0 : this.total - 1;

        this.slides[this.current].classList.remove('is-active');
        if(this.dots[this.current]) this.dots[this.current].classList.remove('is-active');
        
        this.current = index;
        
        this.slides[this.current].classList.add('is-active');
        if(this.dots[this.current]) this.dots[this.current].classList.add('is-active');

        if (this.config.autoplay) {
            this.stopTimer();
            // Add a small delay before restarting to prevent rapid race conditions
            setTimeout(() => this.startTimer(), 150);
        }
    }

    next() { this.goTo(this.current + 1); }
    prev() { this.goTo(this.current - 1); }

    startTimer() {
        this.stopTimer();
        this.timer = setInterval(() => this.next(), this.config.delay);
    }

    stopTimer() {
        if (this.timer) clearInterval(this.timer);
    }
}
window.PremiumSliderEngine = PremiumSliderEngine;
