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
            preview.append(`<div class="ssc-grid-preview-cell">${i}</div>`);
        }
    }

    $(document).ready(function() {
        if (!$('#ssc-grid-cols').length) return;

        $('#ssc-grid-cols, #ssc-grid-gap').on('input', generateGrid);
        
        $('#ssc-grid-copy').on('click', () => {
            window.sscCopyToClipboard($('#ssc-grid-css').text(), {
                successMessage: 'CSS de la grille copié !',
                errorMessage: 'Impossible de copier le CSS de la grille.'
            }).catch(() => {});
        });
        
        $('#ssc-grid-apply').on('click', () => {
            const css = $('#ssc-grid-css').text();
            $.ajax({
                url: SSC.rest.root + 'save-css',
                method: 'POST',
                data: { css, append: true, _wpnonce: SSC.rest.nonce },
                beforeSend: x => x.setRequestHeader('X-WP-Nonce', SSC.rest.nonce)
            }).done(() => window.sscToast('Grille appliquée !'));
        });

        generateGrid();
    });
})(jQuery);