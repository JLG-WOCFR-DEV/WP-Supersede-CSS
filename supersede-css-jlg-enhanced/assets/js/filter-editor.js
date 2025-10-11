(function($) {
    function updateFilters() {
        let filterValue = '';
        let filters = {};

        // Collecte des valeurs de filtres
        $('.ssc-filter-prop').each(function() {
            const prop = $(this).data('prop');
            const value = $(this).val();
            let unit = 'px';
            if (['brightness', 'contrast', 'grayscale', 'saturate'].includes(prop)) unit = '%';
            if (prop === 'hue-rotate') unit = 'deg';

            $('#val-' + prop).text(value + unit);

            if ((prop === 'blur' && value > 0) || (prop !== 'blur' && value != 100 && value != 0)) {
                if (prop === 'hue-rotate') {
                    filters[prop] = `${prop}(${value}deg)`;
                } else if (unit === '%') {
                    filters[prop] = `${prop}(${value}%)`;
                } else {
                    filters[prop] = `${prop}(${value}px)`;
                }
            }
        });

        filterValue = Object.values(filters).join(' ');

        // Gestion du Glassmorphism
        const glassEnabled = $('#ssc-glass-enable').is(':checked');
        const previewBox = $('#ssc-filter-preview-box');
        let finalCss = '';

        if (filterValue) {
            finalCss += `filter: ${filterValue};\n`;
        }
        
        previewBox.css('filter', filterValue || 'none');
        
        if (glassEnabled) {
            previewBox.addClass('ssc-glassmorphism-preview');
            finalCss += `background: rgba(255, 255, 255, 0.18);\n`;
            finalCss += `backdrop-filter: blur(12px)${filterValue ? ` ${filterValue}` : ''};\n`;
            finalCss += `-webkit-backdrop-filter: blur(12px)${filterValue ? ` ${filterValue}` : ''};\n`;
            finalCss += `border: 1px solid rgba(255, 255, 255, 0.35);`;
        } else {
            previewBox.removeClass('ssc-glassmorphism-preview');
        }

        $('#ssc-filter-css').text(finalCss.trim() ? `.votre-element {\n${finalCss.trim()}\n}` : '');
    }

    $(document).ready(function() {
        if (!$('.ssc-filter-prop').length) return;

        const previewBg = $('#ssc-filter-preview-bg');
        const previewBackground = previewBg.data('previewBg');
        if (previewBackground) {
            previewBg.css('--ssc-filter-preview-image', `url('${previewBackground}')`);
        }

        $('.ssc-filter-prop, #ssc-glass-enable').on('input change', updateFilters);

        $('#ssc-filter-copy').on('click', () => {
            window.sscCopyToClipboard($('#ssc-filter-css').text(), {
                successMessage: 'CSS copié !',
                errorMessage: 'Impossible de copier le CSS du filtre.'
            }).catch(() => {});
        });

        updateFilters();
    });

})(jQuery);
