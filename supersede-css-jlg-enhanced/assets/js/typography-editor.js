(function($) {
    function notify(message) {
        if (window && typeof window.sscToast === 'function') {
            window.sscToast(message);
        }
    }

    function generateClamp() {
        let minFS = parseFloat($('#ssc-typo-min-fs').val());
        let maxFS = parseFloat($('#ssc-typo-max-fs').val());
        let minVP = parseFloat($('#ssc-typo-min-vp').val());
        let maxVP = parseFloat($('#ssc-typo-max-vp').val());

        if ([minFS, maxFS, minVP, maxVP].some((value) => !Number.isFinite(value))) {
            notify('Merci de renseigner des valeurs numériques valides.');
            return;
        }

        if (minVP > maxVP) {
            notify('Le viewport minimum doit être inférieur au viewport maximum.');
            return;
        }

        if (minVP === maxVP) {
            const epsilon = 0.01;
            maxVP = minVP + epsilon;
            $('#ssc-typo-max-vp').val(maxVP);
            notify('Le viewport maximum a été ajusté pour éviter une division par zéro.');
        }

        const vpRange = maxVP - minVP;
        if (vpRange <= 0) {
            notify('Les valeurs de viewport ne permettent pas le calcul de la taille fluide.');
            return;
        }

        const slope = (maxFS - minFS) / vpRange;
        const yAxisIntersection = -minVP * slope + minFS;

        if (!Number.isFinite(slope) || !Number.isFinite(yAxisIntersection)) {
            notify('Impossible de générer la règle CSS avec les valeurs actuelles.');
            return;
        }

        const preferredValue = `${yAxisIntersection.toFixed(4)}rem + ${(slope * 100).toFixed(4)}vw`;

        const clampValue = `clamp(${minFS / 16}rem, ${preferredValue}, ${maxFS / 16}rem)`;

        const css = `.votre-texte-fluide {\n  font-size: ${clampValue};\n}`;
        $('#ssc-typo-css').text(css);

        updatePreview();
    }

    function updatePreview() {
        const vpWidth = parseFloat($('#ssc-typo-vp-slider').val());
        if (!Number.isFinite(vpWidth)) {
            return;
        }

        $('#ssc-typo-vp-value').text(vpWidth + 'px');

        const minFS = parseFloat($('#ssc-typo-min-fs').val());
        const maxFS = parseFloat($('#ssc-typo-max-fs').val());
        const minVP = parseFloat($('#ssc-typo-min-vp').val());
        const maxVP = parseFloat($('#ssc-typo-max-vp').val());

        if ([minFS, maxFS, minVP, maxVP].some((value) => !Number.isFinite(value))) {
            return;
        }

        if (minVP >= maxVP) {
            $('#ssc-typo-preview').css('font-size', minFS + 'px');
            return;
        }

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
            window.sscCopyToClipboard($('#ssc-typo-css').text(), {
                successMessage: 'CSS de la typographie copié !',
                errorMessage: 'Impossible de copier le CSS de la typographie.'
            }).catch(() => {});
        });

        generateClamp();
    });
})(jQuery);