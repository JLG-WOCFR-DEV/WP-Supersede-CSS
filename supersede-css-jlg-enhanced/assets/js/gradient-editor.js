(function($) {
    let colorStops = [
        { color: '#ee7752', position: 0 },
        { color: '#e73c7e', position: 50 },
        { color: '#23a6d5', position: 100 }
    ];
    let gradientType = 'linear-gradient';
    let gradientAngle = 90;

    function renderUI() {
        const uiContainer = $('#ssc-grad-stops-ui');
        uiContainer.empty();

        colorStops.forEach((stop, index) => {
            const stopUI = $(`
                <div class="ssc-grad-stop" data-index="${index}" style="display: flex; gap: 10px; align-items: center; margin-bottom: 8px;">
                    <input type="color" class="ssc-grad-prop" data-prop="color" value="${stop.color}">
                    <input type="range" class="ssc-grad-prop" data-prop="position" min="0" max="100" value="${stop.position}" style="flex: 1;">
                    <span>${stop.position}%</span>
                    <button class="button button-link-delete ssc-grad-remove-stop">X</button>
                </div>
            `);
            uiContainer.append(stopUI);
        });
        
        $('<button class="button" id="ssc-grad-add-stop" style="margin-top: 10px;">+ Ajouter une couleur</button>').appendTo(uiContainer);

        generateGradientCSS();
    }

    function generateGradientCSS() {
        colorStops.sort((a, b) => a.position - b.position);

        const stopsValue = colorStops.map(s => `${s.color} ${s.position}%`).join(', ');
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
            const index = $(this).closest('.ssc-grad-stop').data('index');
            const prop = $(this).data('prop');
            colorStops[index][prop] = $(this).val();
            renderUI();
        });

        $('#ssc-grad-stops-ui').on('click', '.ssc-grad-remove-stop', function() {
            if (colorStops.length <= 2) {
                window.sscToast('Un dégradé doit avoir au moins 2 couleurs.');
                return;
            }
            const index = $(this).closest('.ssc-grad-stop').data('index');
            colorStops.splice(index, 1);
            renderUI();
        });

        $('#ssc-grad-stops-ui').on('click', '#ssc-grad-add-stop', () => {
            colorStops.push({ color: '#ffffff', position: 100 });
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
        $('#ssc-grad-copy').on('click', () => navigator.clipboard.writeText($('#ssc-grad-css').text()).then(() => window.sscToast('CSS copié !')));
        $('#ssc-grad-apply').on('click', () => {
             const css = $('#ssc-grad-css').text();
             $.ajax({ url: SSC.rest.root + 'save-css', method: 'POST', data: { css, append: true }, beforeSend: x => x.setRequestHeader('X-WP-Nonce', SSC.rest.nonce)
             }).done(() => window.sscToast('Dégradé appliqué !'));
        });

        renderUI();
    });
})(jQuery);