(function($) {
    let shadowLayers = [{
        inset: false,
        offsetX: 5,
        offsetY: 10,
        blur: 15,
        spread: 0,
        color: '#000000',
        alpha: 0.2
    }];

    function parseRgbaValue(color) {
        if (typeof color !== 'string') {
            return null;
        }

        const match = color.match(/rgba?\((\d+),\s*(\d+),\s*(\d+)(?:,\s*([0-9.]+))?\)/i);
        if (!match) {
            return null;
        }

        return {
            r: parseInt(match[1], 10),
            g: parseInt(match[2], 10),
            b: parseInt(match[3], 10),
            a: typeof match[4] !== 'undefined' ? parseFloat(match[4]) : 1
        };
    }

    function componentToHex(component) {
        const hex = component.toString(16);
        return hex.length === 1 ? `0${hex}` : hex;
    }

    function rgbToHex(r, g, b) {
        return `#${componentToHex(r)}${componentToHex(g)}${componentToHex(b)}`;
    }

    function expandShortHex(hex) {
        if (!/^#?[0-9a-f]{3}$/i.test(hex)) {
            return null;
        }

        const value = hex.replace('#', '');
        const expanded = value.split('').map(char => char + char).join('');
        return `#${expanded}`;
    }

    function ensureHexColor(color) {
        if (typeof color !== 'string') {
            return '#000000';
        }

        let normalized = color.trim().toLowerCase();

        if (/^#[0-9a-f]{6}$/.test(normalized)) {
            return normalized;
        }

        if (/^#[0-9a-f]{3}$/i.test(normalized) || /^[0-9a-f]{3}$/i.test(normalized)) {
            const expanded = expandShortHex(normalized);
            if (expanded) {
                return expanded.toLowerCase();
            }
        }

        const rgba = parseRgbaValue(normalized);
        if (rgba) {
            return rgbToHex(rgba.r, rgba.g, rgba.b);
        }

        return '#000000';
    }

    function normalizeLayer(layer) {
        if (!layer) {
            return layer;
        }

        const rgba = parseRgbaValue(layer.color);
        if (rgba) {
            layer.color = rgbToHex(rgba.r, rgba.g, rgba.b);
            if (typeof layer.alpha === 'undefined') {
                layer.alpha = rgba.a;
            }
        } else {
            layer.color = ensureHexColor(layer.color);
        }

        if (typeof layer.alpha === 'undefined' || Number.isNaN(parseFloat(layer.alpha))) {
            layer.alpha = 1;
        } else {
            layer.alpha = Math.min(1, Math.max(0, parseFloat(layer.alpha)));
        }

        return layer;
    }

    function hexToRgb(hex) {
        if (typeof hex !== 'string') {
            return { r: 0, g: 0, b: 0 };
        }

        let value = hex.replace('#', '').trim();
        if (value.length === 3) {
            value = value.split('').map(char => char + char).join('');
        }

        const intValue = parseInt(value, 16);
        if (Number.isNaN(intValue)) {
            return { r: 0, g: 0, b: 0 };
        }

        return {
            r: (intValue >> 16) & 255,
            g: (intValue >> 8) & 255,
            b: intValue & 255
        };
    }

    function sanitizeLayerValue(prop, value) {
        if (prop === 'color') {
            return ensureHexColor(value);
        }

        if (prop === 'alpha') {
            const parsed = parseFloat(value);
            if (Number.isNaN(parsed)) {
                return 1;
            }
            return Math.min(1, Math.max(0, parsed));
        }

        return value;
    }

    function renderLayers() {
        const container = $('#ssc-shadow-layers-container');
        container.empty();
        
        shadowLayers.forEach((layer, index) => {
            normalizeLayer(layer);
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
                        <div><label>Opacité</label><input type="range" class="ssc-shadow-prop" data-prop="alpha" min="0" max="1" step="0.01" value="${layer.alpha}"></div>
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

        const shadowValue = shadowLayers.map(layer => {
            const normalizedLayer = normalizeLayer(layer);
            const rgb = hexToRgb(normalizedLayer.color);
            const alpha = typeof normalizedLayer.alpha === 'number' ? normalizedLayer.alpha : parseFloat(normalizedLayer.alpha);
            const colorString = `rgba(${rgb.r}, ${rgb.g}, ${rgb.b}, ${Number.isNaN(alpha) ? 1 : alpha})`;

            return `${normalizedLayer.inset ? 'inset ' : ''}${normalizedLayer.offsetX}px ${normalizedLayer.offsetY}px ${normalizedLayer.blur}px ${normalizedLayer.spread}px ${colorString}`;
        }).join(', ');

        const css = `.ma-classe-ombre {\n  box-shadow: ${shadowValue};\n}`;
        $('#ssc-shadow-css').text(css);
        $('#ssc-shadow-preview').css('box-shadow', shadowValue);
    }

    $(document).ready(function() {
        if (!$('#ssc-shadow-layers-container').length) return;

        // Event Delegation for layer property changes
        $('#ssc-shadow-layers-container').on('input change', '.ssc-shadow-prop', function() {
            const prop = $(this).data('prop');
            const index = $(this).closest('.ssc-shadow-layer').data('index');
            const rawValue = $(this).is(':checkbox') ? $(this).is(':checked') : $(this).val();
            const value = sanitizeLayerValue(prop, rawValue);
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
                inset: false,
                offsetX: 0,
                offsetY: 5,
                blur: 10,
                spread: 0,
                color: '#000000',
                alpha: 0.1
            });
            renderLayers();
        });

        // Action buttons
        $('#ssc-shadow-copy').on('click', () => {
            window.sscCopyToClipboard($('#ssc-shadow-css').text(), {
                successMessage: 'CSS copié !',
                errorMessage: 'Impossible de copier le CSS de l\'ombre.'
            }).catch(() => {});
        });
        $('#ssc-shadow-apply').on('click', () => {
            const css = $('#ssc-shadow-css').text();
             $.ajax({ url: SSC.rest.root + 'save-css', method: 'POST', data: { css, append: true, _wpnonce: SSC.rest.nonce }, beforeSend: x => x.setRequestHeader('X-WP-Nonce', SSC.rest.nonce)
             }).done(() => window.sscToast('Ombre appliquée !'));
        });

        renderLayers();
    });
})(jQuery);