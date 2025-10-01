(function($) {
    const fallbackI18n = {
        __: (text) => text,
    };

    const hasI18n = typeof window !== 'undefined' && window.wp && window.wp.i18n;
    const { __ } = hasI18n ? window.wp.i18n : fallbackI18n;

    function copyToClipboard(text) {
        if (navigator.clipboard && window.isSecureContext) {
            return navigator.clipboard.writeText(text);
        } else {
            let textArea = document.createElement("textarea");
            textArea.value = text;
            textArea.style.position = "fixed";
            textArea.style.left = "-999999px";
            document.body.appendChild(textArea);
            textArea.select();
            try {
                document.execCommand('copy');
            } catch (err) {
                console.error('Fallback copy failed', err);
            }
            document.body.removeChild(textArea);
        }
    }

    function generateTronCSS() {
        const lineColor = $('#ssc-tron-line-color').val();
        const bg1 = $('#ssc-tron-bg1').val();
        const bg2 = $('#ssc-tron-bg2').val();
        const size = $('#ssc-tron-size').val();
        const thickness = $('#ssc-tron-thickness').val();
        const speed = $('#ssc-tron-speed').val();

        $('#ssc-tron-size-val').text(size + 'px');
        $('#ssc-tron-thickness-val').text(thickness + 'px');
        $('#ssc-tron-speed-val').text(speed + 's');

        const keyframes = `@keyframes ssc-tron-scroll {
  from { background-position: 0 0; }
  to { background-position: 0 -${size}px; }
}`;

        const css = `${keyframes}
.ssc-tron-grid-bg {
  background-color: ${bg1};
  background-image:
    /* Lignes horizontales qui défilent */
    linear-gradient(to bottom, ${lineColor} ${thickness}px, transparent ${thickness}px),
    /* Lignes verticales qui défilent */
    linear-gradient(to right, ${lineColor} ${thickness}px, transparent ${thickness}px),
    /* Dégradé de fond statique */
    linear-gradient(to bottom, ${bg1}, ${bg2});
  background-size:
    100% ${size}px,
    ${size}px 100%,
    100% 100%;
  animation: ssc-tron-scroll ${speed}s linear infinite;
}`;
        
        $('#ssc-tron-css').text(css.trim());

        const preview = $('#ssc-tron-preview');
        
        $('style#ssc-tron-keyframes').remove();
        $(`<style id="ssc-tron-keyframes">${keyframes}</style>`).appendTo('head');
        
        preview.css({
            backgroundColor: bg1,
            backgroundImage: `
                linear-gradient(to bottom, ${lineColor} ${thickness}px, transparent ${thickness}px),
                linear-gradient(to right, ${lineColor} ${thickness}px, transparent ${thickness}px),
                linear-gradient(to bottom, ${bg1}, ${bg2})
            `,
            backgroundSize: `100% ${size}px, ${size}px 100%, 100% 100%`,
            animation: `ssc-tron-scroll ${speed}s linear infinite`
        });
    }

    $(document).ready(function() {
        if (!$('#ssc-tron-line-color').length) return;

        $('#ssc-tron-line-color, #ssc-tron-bg1, #ssc-tron-bg2, #ssc-tron-size, #ssc-tron-thickness, #ssc-tron-speed').on('input', generateTronCSS);
        
        $('#ssc-tron-copy').on('click', () => {
            copyToClipboard($('#ssc-tron-css').text());
            window.sscToast('CSS de la grille copié !');
        });
        
        $('#ssc-tron-apply').on('click', () => {
            const css = $('#ssc-tron-css').text();
            $.ajax({
                url: SSC.rest.root + 'save-css',
                method: 'POST',
                data: { css: css, append: true, _wpnonce: SSC.rest.nonce },
                beforeSend: x => x.setRequestHeader('X-WP-Nonce', SSC.rest.nonce)
            }).done(() => window.sscToast('Grille animée appliquée !'))
                .fail((jqXHR, textStatus, errorThrown) => {
                    console.error('Échec de l\'enregistrement de la grille animée.', errorThrown || jqXHR);
                    window.sscToast(
                        __('Échec de l\'enregistrement de la grille animée.', 'supersede-css-jlg'),
                        { politeness: 'assertive' }
                    );
                });
        });

        generateTronCSS();
    });
})(jQuery);