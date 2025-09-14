(function($) {
    let editor, builder;

    function copyToClipboard(text) {
        if (navigator.clipboard && window.isSecureContext) {
            return navigator.clipboard.writeText(text);
        } else {
            // Méthode de repli pour les contextes non sécurisés (HTTP)
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

    function parseTokens(css) {
        const tokenRegex = /--([\w-]+)\s*:\s*([^;]+);/g;
        const tokens = [];
        let match;
        while ((match = tokenRegex.exec(css)) !== null) {
            tokens.push({ name: `--${match[1].trim()}`, value: match[2].trim() });
        }
        return tokens;
    }

    function generateCSS(tokens) {
        if (tokens.length === 0) {
            return ':root {}';
        }
        const css = tokens.map(t => `  ${t.name}: ${t.value};`).join('\n');
        return `:root {\n${css}\n}`;
    }

    function renderBuilder() {
        const css = editor.val();
        const tokens = parseTokens(css);
        builder.empty();

        tokens.forEach((token, index) => {
            const isColor = token.value.startsWith('#') || token.value.startsWith('rgb') || token.value.startsWith('hsl');
            const row = $(`
                <div class="ssc-kv-builder" style="margin-bottom:8px; display:flex; gap:8px; align-items-center;">
                    <input type="text" class="token-name regular-text" value="${token.name}" placeholder="--nom-du-token">
                    <input type="${isColor ? 'color' : 'text'}" class="token-value" value="${token.value}" style="${isColor ? 'height:36px;padding:2px;min-width:40px;' : ''}">
                    <button class="button button-link-delete" data-index="${index}">Supprimer</button>
                </div>
            `);
            builder.append(row);
        });
    }
    
    function updateEditorFromBuilder() {
        const tokens = [];
        builder.find('.ssc-kv-builder').each(function() {
            const name = $(this).find('.token-name').val();
            const value = $(this).find('.token-value').val();
            if (name && value) {
                tokens.push({ name, value });
            }
        });
        const newCSS = generateCSS(tokens);
        editor.val(newCSS);
        applyPreview();
    }

    function applyPreview() {
        const raw = editor.val();
        $('#ssc-tokens-preview-style').text(raw);
    }

    $(document).ready(function() {
        if (!$('#ssc-tokens').length) return;

        editor = $('#ssc-tokens');
        builder = $('#ssc-token-builder');

        $('#ssc-token-add').on('click', () => {
            const css = editor.val();
            const tokens = parseTokens(css);
            tokens.push({ name: '--nouveau-token', value: '#ffffff' });
            editor.val(generateCSS(tokens));
            renderBuilder();
            applyPreview();
        });

        builder.on('input', '.token-name, .token-value', updateEditorFromBuilder);
        builder.on('click', 'button', function() {
            $(this).closest('.ssc-kv-builder').remove();
            updateEditorFromBuilder();
        });
        
        editor.on('input', () => {
            renderBuilder();
            applyPreview();
        });

        $('#ssc-tokens-apply').on('click', () => {
            const css = editor.val();
            $.ajax({
                url: SSC.rest.root + 'save-css',
                method: 'POST',
                data: { css: css, option_name: 'ssc_tokens_css', append: false },
                beforeSend: x => x.setRequestHeader('X-WP-Nonce', SSC.rest.nonce)
            }).done(() => window.sscToast('Tokens appliqués'));
        });

        $('#ssc-tokens-copy').on('click', () => {
            copyToClipboard(editor.val());
            window.sscToast('Tokens copiés');
        });

        renderBuilder();
        applyPreview();
    });
})(jQuery);