(function($) {
    const presets = {
        bounce: {
            name: 'ssc-bounce',
            keyframes: `
  0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
  40% { transform: translateY(-30px); }
  60% { transform: translateY(-15px); }`
        },
        pulse: {
            name: 'ssc-pulse',
            keyframes: `
  0% { transform: scale(1); }
  50% { transform: scale(1.1); }
  100% { transform: scale(1); }`
        },
        'fade-in': {
            name: 'ssc-fade-in',
            keyframes: `
  from { opacity: 0; }
  to { opacity: 1; }`
        },
        'slide-in-left': {
            name: 'ssc-slide-in-left',
            keyframes: `
  from { transform: translateX(-100%); opacity: 0; }
  to { transform: translateX(0); opacity: 1; }`
        }
    };

    function generateAnimationCSS() {
        const presetKey = $('#ssc-anim-preset').val();
        const duration = $('#ssc-anim-duration').val();
        const preset = presets[presetKey];

        $('#ssc-anim-duration-val').text(duration + 's');

        const css = `
@keyframes ${preset.name} {${preset.keyframes}
}

.ssc-animated.${preset.name} {
  animation-name: ${preset.name};
  animation-duration: ${duration}s;
  animation-fill-mode: both;
}`;
        
        $('#ssc-anim-css').text(css.trim());

        let styleTag = $('#ssc-anim-live-style');
        if (!styleTag.length) {
            styleTag = $('<style id="ssc-anim-live-style"></style>').appendTo('head');
        }
        styleTag.text(css);
        
        const previewBox = $('#ssc-anim-preview-box');
        // Relancer l'animation en clonant l'élément
        const newBox = previewBox.clone(true);
        previewBox.before(newBox);
        $("." + previewBox.attr("class") + ":last").remove();
        newBox.addClass('ssc-animated ' + preset.name);
    }

    $(document).ready(function() {
        if (!$('#ssc-anim-preset').length) return; // Ne rien faire si on n'est pas sur la bonne page

        $('#ssc-anim-preset, #ssc-anim-duration').on('input', generateAnimationCSS);

        $('#ssc-anim-copy').on('click', () => {
            navigator.clipboard.writeText($('#ssc-anim-css').text());
            window.sscToast('CSS de l\'animation copié !');
        });

        $('#ssc-anim-apply').on('click', () => {
            const css = $('#ssc-anim-css').text();
            $.ajax({
                url: SSC.rest.root + 'save-css',
                method: 'POST',
                data: { css, append: true },
                beforeSend: x => x.setRequestHeader('X-WP-Nonce', SSC.rest.nonce)
            }).done(() => window.sscToast('Animation appliquée !'));
        });

        generateAnimationCSS();
    });
})(jQuery);