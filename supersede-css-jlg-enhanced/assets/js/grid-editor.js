(function($) {
    function generateGrid() {
        const cols = $('#ssc-grid-cols').val();
        const gap = $('#ssc-grid-gap').val();

        $('#ssc-grid-cols-val').text(cols);
        $('#ssc-grid-gap-val').text(gap + 'px');

        const css = `
.ssc-grid-container {
  display: grid;
  grid-template-columns: repeat(${cols}, 1fr);
  gap: ${gap}px;
}`;
        $('#ssc-grid-css').text(css.trim());

        const preview = $('#ssc-grid-preview');
        preview.empty();
        preview.css({
            'grid-template-columns': `repeat(${cols}, 1fr)`,
            'gap': `${gap}px`
        });

        for (let i = 1; i <= Math.min(12, cols * 2); i++) {
            preview.append(`<div style="background:var(--ssc-bg); padding:12px; border-radius:8px; text-align:center;">${i}</div>`);
        }
    }

    $(document).ready(function() {
        if (!$('#ssc-grid-cols').length) return;

        $('#ssc-grid-cols, #ssc-grid-gap').on('input', generateGrid);
        
        $('#ssc-grid-copy').on('click', () => {
            navigator.clipboard.writeText($('#ssc-grid-css').text());
            window.sscToast('CSS de la grille copié !');
        });
        
        $('#ssc-grid-apply').on('click', () => {
            const css = $('#ssc-grid-css').text();
            $.ajax({
                url: SSC.rest.root + 'save-css',
                method: 'POST',
                data: { css, append: true },
                beforeSend: x => x.setRequestHeader('X-WP-Nonce', SSC.rest.nonce)
            }).done(() => window.sscToast('Grille appliquée !'));
        });

        generateGrid();
    });
})(jQuery);