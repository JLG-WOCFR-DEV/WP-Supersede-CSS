(function($) {
    let nextStopId = 0;
    const createStop = (color, position) => ({ id: nextStopId++, color, position });

    let colorStops = [
        createStop('#ee7752', 0),
        createStop('#e73c7e', 50),
        createStop('#23a6d5', 100)
    ];
    let gradientType = 'linear-gradient';
    let gradientAngle = 90;

    function renderUI() {
        const uiContainer = $('#ssc-grad-stops-ui');
        uiContainer.empty();
        const removeStopLabel = window.SSC && window.SSC.i18n && window.SSC.i18n.removeStop
            ? window.SSC.i18n.removeStop
            : 'Supprimer ce point de couleur';

        colorStops.forEach((stop) => {
            const stopUI = $(`
                <div class="ssc-grad-stop" data-stop-id="${stop.id}">
                    <input type="color" class="ssc-grad-prop" data-prop="color" value="${stop.color}">
                    <input type="range" class="ssc-grad-prop ssc-grad-stop__range" data-prop="position" min="0" max="100" value="${stop.position}">
                    <span class="ssc-grad-stop__value">${stop.position}%</span>
                    <button type="button" class="button button-link-delete ssc-grad-remove-stop" aria-label="${removeStopLabel}">×</button>
                </div>
            `);
            uiContainer.append(stopUI);
        });

        $('<button type="button" class="button ssc-grad-add-stop" id="ssc-grad-add-stop">+ Ajouter une couleur</button>').appendTo(uiContainer);

        generateGradientCSS();
    }

    function generateGradientCSS() {
        const sortedStops = [...colorStops].sort((a, b) => a.position - b.position);

        const stopsValue = sortedStops.map(s => `${s.color} ${s.position}%`).join(', ');
        const angleValue = `${gradientAngle}deg`;
        
        let gradientValue = '';
        if(gradientType === 'linear-gradient') {
            gradientValue = `linear-gradient(${angleValue}, ${stopsValue})`;
        } else {
            gradientValue = `${gradientType}(${stopsValue})`;
        }
        
        const css = `.mon-degrade {\n  background: ${gradientValue};\n}`;
        
        $('#ssc-grad-css').text(css);
        $('#ssc-grad-preview').css('background', gradientValue);
        $('#ssc-grad-stops-preview').css('background', `linear-gradient(to right, ${stopsValue})`);
    }

    $(document).ready(function() {
        if (!$('#ssc-grad-type').length) return;

        // Event Delegation for changes
        $('#ssc-grad-stops-ui').on('input', '.ssc-grad-prop', function() {
            const $input = $(this);
            const $stop = $input.closest('.ssc-grad-stop');
            const stopId = $stop.data('stop-id');
            const prop = $input.data('prop');
            const value = $input.val();

            const stop = colorStops.find(s => s.id === stopId);
            if (!stop) {
                return;
            }

            stop[prop] = value;

            if (prop === 'position') {
                $stop.find('.ssc-grad-stop__value').text(`${value}%`);
            }

            generateGradientCSS();
        });

        $('#ssc-grad-stops-ui').on('click', '.ssc-grad-remove-stop', function() {
            if (colorStops.length <= 2) {
                window.sscToast('Un dégradé doit avoir au moins 2 couleurs.');
                return;
            }
            const stopId = $(this).closest('.ssc-grad-stop').data('stop-id');
            const index = colorStops.findIndex(stop => stop.id === stopId);
            if (index === -1) {
                return;
            }
            colorStops.splice(index, 1);
            renderUI();
        });

        $('#ssc-grad-stops-ui').on('click', '#ssc-grad-add-stop', () => {
            colorStops.push(createStop('#ffffff', 100));
            renderUI();
        });

        // General controls
        $('#ssc-grad-type').on('change', function() {
            gradientType = $(this).val();
            $('#ssc-grad-angle-control').toggle(gradientType === 'linear-gradient');
            generateGradientCSS();
        });

        $('#ssc-grad-angle, #ssc-grad-angle-num').on('input', function() {
            gradientAngle = $(this).val();
            $('#ssc-grad-angle').val(gradientAngle);
            $('#ssc-grad-angle-num').val(gradientAngle);
            generateGradientCSS();
        });

        // Action buttons
        $('#ssc-grad-copy').on('click', () => {
            window.sscCopyToClipboard($('#ssc-grad-css').text(), {
                successMessage: 'CSS copié !',
                errorMessage: 'Impossible de copier le CSS du dégradé.'
            }).catch(() => {});
        });
        $('#ssc-grad-apply').on('click', () => {
             const css = $('#ssc-grad-css').text();
             $.ajax({ url: SSC.rest.root + 'save-css', method: 'POST', data: { css, append: true, _wpnonce: SSC.rest.nonce }, beforeSend: x => x.setRequestHeader('X-WP-Nonce', SSC.rest.nonce)
             }).done(() => window.sscToast('Dégradé appliqué !'));
        });

        renderUI();
    });
})(jQuery);
