(function($) {
    function buildCssRule() {
        const selectorsRaw = ($('#ssc-sel').val() || '').trim();
        const cssRaw = ($('#ssc-css').val() || '').trim();

        if (!selectorsRaw || !cssRaw) {
            return '';
        }

        const pseudo = ($('#ssc-pseudo').val() || '').trim();
        const selectors = selectorsRaw
            .split(',')
            .map(sel => sel.trim())
            .filter(Boolean)
            .map(sel => (pseudo ? sel + pseudo : sel));

        if (!selectors.length) {
            return '';
        }

        const cssLines = cssRaw
            .split(';')
            .map(line => line.trim())
            .filter(Boolean)
            .map(line => `    ${line}${line.endsWith(';') ? '' : ';'}`);

        if (!cssLines.length) {
            return '';
        }

        return `${selectors.join(', ')} {\n${cssLines.join('\n')}\n}`;
    }

    function updatePreview() {
        const rule = buildCssRule();
        const styleEl = document.getElementById('ssc-scope-preview-style');
        if (styleEl) {
            styleEl.textContent = rule;
        }
        return rule;
    }

    function copyToClipboard(text) {
        if (!text) {
            window.sscToast('Rien à copier.');
            return;
        }

        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text)
                .then(() => window.sscToast('CSS copié !'))
                .catch(() => fallbackCopy(text));
            return;
        }

        fallbackCopy(text);
    }

    function fallbackCopy(text) {
        const textarea = document.createElement('textarea');
        textarea.value = text;
        textarea.style.position = 'fixed';
        textarea.style.opacity = '0';
        document.body.appendChild(textarea);
        textarea.select();
        try {
            document.execCommand('copy');
            window.sscToast('CSS copié !');
        } catch (e) {
            window.sscToast('Impossible de copier le CSS.');
        }
        document.body.removeChild(textarea);
    }

    function debounce(fn, wait) {
        let timeout = null;
        return function() {
            const context = this;
            const args = arguments;
            window.clearTimeout(timeout);
            timeout = window.setTimeout(function() {
                fn.apply(context, args);
            }, wait);
        };
    }

    $(document).ready(function() {
        if (!$('#ssc-scope-preview-container').length) {
            return;
        }

        const debouncedUpdate = debounce(updatePreview, 150);

        $('#ssc-sel, #ssc-pseudo').on('input change', debouncedUpdate);
        $('#ssc-css').on('input', debouncedUpdate);

        $('#ssc-copy').on('click', function(e) {
            e.preventDefault();
            const cssRule = updatePreview();
            copyToClipboard(cssRule);
        });

        $('#ssc-apply').on('click', function(e) {
            e.preventDefault();
            const btn = $(this);
            const cssRule = updatePreview();

            if (!cssRule) {
                window.sscToast('Veuillez remplir le sélecteur et les propriétés CSS.');
                return;
            }

            btn.prop('disabled', true).text('Application...');

            $.ajax({
                url: SSC.rest.root + 'save-css',
                method: 'POST',
                data: {
                    css: cssRule,
                    append: true,
                    _wpnonce: SSC.rest.nonce
                },
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', SSC.rest.nonce);
                }
            }).done(function() {
                window.sscToast('CSS appliqué !');
            }).fail(function() {
                window.sscToast('Erreur lors de la sauvegarde du CSS.');
            }).always(function() {
                btn.prop('disabled', false).text('Appliquer');
            });
        });

        updatePreview();
    });
})(jQuery);
