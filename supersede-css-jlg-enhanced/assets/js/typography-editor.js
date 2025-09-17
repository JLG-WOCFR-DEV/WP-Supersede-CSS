(function($) {
    function generateClamp() {
        const minFS = parseFloat($('#ssc-typo-min-fs').val());
        const maxFS = parseFloat($('#ssc-typo-max-fs').val());
        const minVP = parseFloat($('#ssc-typo-min-vp').val());
        const maxVP = parseFloat($('#ssc-typo-max-vp').val());

        if ([minFS, maxFS, minVP, maxVP].some(isNaN)) return;

        const slope = (maxFS - minFS) / (maxVP - minVP);
        const yAxisIntersection = -minVP * slope + minFS;

        const preferredValue = `${yAxisIntersection.toFixed(4)}rem + ${(slope * 100).toFixed(4)}vw`;
        
        const clampValue = `clamp(${minFS / 16}rem, ${preferredValue}, ${maxFS / 16}rem)`;
        
        const css = `.votre-texte-fluide {\n  font-size: ${clampValue};\n}`;
        $('#ssc-typo-css').text(css);

        updatePreview();
    }

    function updatePreview() {
        const vpWidth = $('#ssc-typo-vp-slider').val();
        $('#ssc-typo-vp-value').text(vpWidth + 'px');

        const minFS = parseFloat($('#ssc-typo-min-fs').val());
        const maxFS = parseFloat($('#ssc-typo-max-fs').val());
        const minVP = parseFloat($('#ssc-typo-min-vp').val());
        const maxVP = parseFloat($('#ssc-typo-max-vp').val());
        
        let fontSize;
        if (vpWidth <= minVP) {
            fontSize = minFS;
        } else if (vpWidth >= maxVP) {
            fontSize = maxFS;
        } else {
            fontSize = minFS + (maxFS - minFS) * (vpWidth - minVP) / (maxVP - minVP);
        }

        $('#ssc-typo-preview').css('font-size', fontSize + 'px');
    }

    $(document).ready(function() {
        if(!$('#ssc-typo-min-fs').length) return;

        $('#ssc-typo-min-fs, #ssc-typo-max-fs, #ssc-typo-min-vp, #ssc-typo-max-vp').on('input', generateClamp);
        $('#ssc-typo-vp-slider').on('input', updatePreview);
        
        $('#ssc-typo-copy').on('click', () => {
            navigator.clipboard.writeText($('#ssc-typo-css').text());
            window.sscToast('CSS de la typographie copié !');
        });

        generateClamp();
    });
})(jQuery);