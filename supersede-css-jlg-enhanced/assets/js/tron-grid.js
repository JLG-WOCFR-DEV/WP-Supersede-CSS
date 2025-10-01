(function($) {
    const reduceMotionQuery = window.matchMedia('(prefers-reduced-motion: reduce)');
    let forcePreviewMotion = false;

    function shouldReduceMotion() {
        return reduceMotionQuery.matches && !forcePreviewMotion;
    }

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
        const reduceMotion = shouldReduceMotion();

        $('#ssc-tron-size-val').text(size + 'px');
        $('#ssc-tron-thickness-val').text(thickness + 'px');
        $('#ssc-tron-speed-val').text(speed + 's');

        const keyframes = `@keyframes ssc-tron-scroll {
  from { background-position: 0 0; }
  to { background-position: 0 -${size}px; }
}`;

        const reducedMotionBlock = `@media (prefers-reduced-motion: reduce) {
  .ssc-tron-grid-bg {
    animation: none;
    background-position: 0 0, 0 0, 0 0;
  }
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
}
${reducedMotionBlock}`;

        $('#ssc-tron-css').text(css.trim());

        const preview = $('#ssc-tron-preview');

        $('style#ssc-tron-keyframes').remove();
        const previewStyle = reduceMotion ? `${keyframes}
.ssc-tron-grid-preview-motion { animation: none !important; }` : keyframes;
        $(`<style id="ssc-tron-keyframes">${previewStyle}</style>`).appendTo('head');

        preview.css({
            backgroundColor: bg1,
            backgroundImage: `
                linear-gradient(to bottom, ${lineColor} ${thickness}px, transparent ${thickness}px),
                linear-gradient(to right, ${lineColor} ${thickness}px, transparent ${thickness}px),
                linear-gradient(to bottom, ${bg1}, ${bg2})
            `,
            backgroundSize: `100% ${size}px, ${size}px 100%, 100% 100%`,
            animation: reduceMotion ? 'none' : `ssc-tron-scroll ${speed}s linear infinite`,
            backgroundPosition: reduceMotion ? '0 0, 0 0, 0 0' : ''
        });
        preview.toggleClass('ssc-tron-grid-preview-motion', reduceMotion);
    }

    $(document).ready(function() {
        if (!$('#ssc-tron-line-color').length) return;

        const $forceToggle = $('#ssc-tron-force-motion');
        if ($forceToggle.length) {
            forcePreviewMotion = $forceToggle.is(':checked');
            $forceToggle.on('change', function() {
                forcePreviewMotion = $(this).is(':checked');
                generateTronCSS();
            });
        }

        const handleMotionPreferenceChange = () => generateTronCSS();
        if (typeof reduceMotionQuery.addEventListener === 'function') {
            reduceMotionQuery.addEventListener('change', handleMotionPreferenceChange);
        } else if (typeof reduceMotionQuery.addListener === 'function') {
            reduceMotionQuery.addListener(handleMotionPreferenceChange);
        }

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
            }).done(() => window.sscToast('Grille animée appliquée !'));
        });

        generateTronCSS();
    });
})(jQuery);
