(function($) {
    let shadowLayers = [{
        inset: false,
        offsetX: 5,
        offsetY: 10,
        blur: 15,
        spread: 0,
        color: 'rgba(0,0,0,0.2)'
    }];

    function renderLayers() {
        const container = $('#ssc-shadow-layers-container');
        container.empty();
        
        shadowLayers.forEach((layer, index) => {
            const layerUI = $(`
                <div class="ssc-shadow-layer" data-index="${index}">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                        <strong>Calque ${index + 1}</strong>
                        <button class="button button-link-delete ssc-shadow-remove-layer">Supprimer</button>
                    </div>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 10px;">
                        <div><label>X-Offset</label><input type="range" class="ssc-shadow-prop" data-prop="offsetX" min="-50" max="50" value="${layer.offsetX}"></div>
                        <div><label>Y-Offset</label><input type="range" class="ssc-shadow-prop" data-prop="offsetY" min="-50" max="50" value="${layer.offsetY}"></div>
                        <div><label>Flou (Blur)</label><input type="range" class="ssc-shadow-prop" data-prop="blur" min="0" max="100" value="${layer.blur}"></div>
                        <div><label>Étendue (Spread)</label><input type="range" class="ssc-shadow-prop" data-prop="spread" min="-50" max="50" value="${layer.spread}"></div>
                        <div><label>Couleur</label><input type="color" class="ssc-shadow-prop" data-prop="color" value="${layer.color}"></div>
                        <div><label>Inset</label><input type="checkbox" class="ssc-shadow-prop" data-prop="inset" ${layer.inset ? 'checked' : ''}></div>
                    </div>
                </div>
            `);
            container.append(layerUI);
        });
        
        generateShadowCSS();
    }

    function generateShadowCSS() {
        if (shadowLayers.length === 0) {
            $('#ssc-shadow-css').text('box-shadow: none;');
            $('#ssc-shadow-preview').css('box-shadow', 'none');
            return;
        }

        const shadowValue = shadowLayers.map(l => 
            `${l.inset ? 'inset ' : ''}${l.offsetX}px ${l.offsetY}px ${l.blur}px ${l.spread}px ${l.color}`
        ).join(', ');

        const css = `.ma-classe-ombre {\n  box-shadow: ${shadowValue};\n}`;
        $('#ssc-shadow-css').text(css);
        $('#ssc-shadow-preview').css('box-shadow', shadowValue);
    }

    $(document).ready(function() {
        if (!$('#ssc-shadow-layers-container').length) return;

        // Event Delegation for layer property changes
        $('#ssc-shadow-layers-container').on('input', '.ssc-shadow-prop', function() {
            const prop = $(this).data('prop');
            const index = $(this).closest('.ssc-shadow-layer').data('index');
            const value = $(this).is(':checkbox') ? $(this).is(':checked') : $(this).val();
            shadowLayers[index][prop] = value;
            generateShadowCSS();
        });

        // Remove a layer
        $('#ssc-shadow-layers-container').on('click', '.ssc-shadow-remove-layer', function() {
            const index = $(this).closest('.ssc-shadow-layer').data('index');
            shadowLayers.splice(index, 1);
            renderLayers();
        });

        // Add a new layer
        $('#ssc-shadow-add-layer').on('click', () => {
            shadowLayers.push({
                inset: false, offsetX: 0, offsetY: 5, blur: 10, spread: 0, color: 'rgba(0,0,0,0.1)'
            });
            renderLayers();
        });

        // Action buttons
        $('#ssc-shadow-copy').on('click', () => {
            navigator.clipboard.writeText($('#ssc-shadow-css').text());
            window.sscToast('CSS copié !');
        });
        $('#ssc-shadow-apply').on('click', () => {
            const css = $('#ssc-shadow-css').text();
             $.ajax({ url: SSC.rest.root + 'save-css', method: 'POST', data: { css, append: true, _wpnonce: SSC.rest.nonce }, beforeSend: x => x.setRequestHeader('X-WP-Nonce', SSC.rest.nonce)
             }).done(() => window.sscToast('Ombre appliquée !'));
        });

        renderLayers();
    });
})(jQuery);