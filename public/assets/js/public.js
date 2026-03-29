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

    initExpandables();
});
