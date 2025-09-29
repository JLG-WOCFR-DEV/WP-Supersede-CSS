(function($) {
    const fallbackI18n = {
        __: (text) => text,
        sprintf: (format, ...args) => {
            let index = 0;
            return format.replace(/%([0-9]+\$)?[sd]/g, (match, position) => {
                if (position) {
                    const explicitIndex = parseInt(position, 10) - 1;
                    return typeof args[explicitIndex] !== 'undefined' ? args[explicitIndex] : '';
                }
                const value = typeof args[index] !== 'undefined' ? args[index] : '';
                index += 1;
                return value;
            });
        },
    };

    const hasI18n = typeof window !== 'undefined' && window.wp && window.wp.i18n;
    const { __, sprintf } = hasI18n ? window.wp.i18n : fallbackI18n;

    if (!hasI18n) {
        // eslint-disable-next-line no-console
        console.warn(__('wp.i18n is not available. Falling back to untranslated strings.', 'supersede-css-jlg'));
    }

    // Fonction pour déclencher le téléchargement d'un fichier
    function downloadFile(filename, content, mimeType) {
        const blob = new Blob([content], { type: mimeType });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = filename;
        document.body.appendChild(a);
        const revoke = () => URL.revokeObjectURL(url);

        try {
            a.click();
        } finally {
            if (a.parentNode) {
                a.parentNode.removeChild(a);
            }
            if (typeof requestAnimationFrame === 'function') {
                requestAnimationFrame(revoke);
            } else {
                setTimeout(revoke, 0);
            }
        }
    }

    $(document).ready(function() {
        if (!$('#ssc-export-config').length) return;

        // --- EXPORTATION ---
        const moduleCheckboxes = $('input[name="ssc-modules[]"]');

        function getSelectedModules() {
            return moduleCheckboxes
                .filter(':checked')
                .map((_, el) => el.value)
                .get()
                .filter(Boolean);
        }

        function ensureModulesSelected(context) {
            const modules = getSelectedModules();
            if (modules.length > 0) {
                return modules;
            }

            const message = context === 'import'
                ? __('Veuillez sélectionner au moins un ensemble à importer.', 'supersede-css-jlg')
                : __('Veuillez sélectionner au moins un ensemble à exporter.', 'supersede-css-jlg');

            if (typeof window.sscToast === 'function' && context !== 'import') {
                window.sscToast(message);
            }

            if (context === 'import') {
                handleImportError(message);
            }

            return [];
        }

        // Exporter la configuration complète (.json)
        $('#ssc-export-config').on('click', function() {
            const btn = $(this);
            const modules = ensureModulesSelected('export');
            if (!modules.length) {
                return;
            }

            btn.text(__('Exportation...', 'supersede-css-jlg')).prop('disabled', true);

            $.ajax({
                url: SSC.rest.root + 'export-config',
                method: 'GET',
                data: { _wpnonce: SSC.rest.nonce, modules },
                beforeSend: x => x.setRequestHeader('X-WP-Nonce', SSC.rest.nonce)
            }).done(response => {
                const jsonContent = JSON.stringify(response, null, 2);
                downloadFile('supersede-config-export.json', jsonContent, 'application/json');
                window.sscToast(__('Configuration exportée !', 'supersede-css-jlg'));
            }).fail(() => {
                window.sscToast(__("Erreur lors de l'exportation de la configuration.", 'supersede-css-jlg'));
            }).always(() => {
                btn.text(__('Exporter Config (.json)', 'supersede-css-jlg')).prop('disabled', false);
            });
        });

        // Exporter uniquement le CSS actif (.css)
        $('#ssc-export-css').on('click', function() {
            const btn = $(this);
            btn.text(__('Exportation...', 'supersede-css-jlg')).prop('disabled', true);

            $.ajax({
                url: SSC.rest.root + 'export-css',
                method: 'GET',
                data: { _wpnonce: SSC.rest.nonce },
                beforeSend: x => x.setRequestHeader('X-WP-Nonce', SSC.rest.nonce)
            }).done(response => {
                downloadFile('supersede-styles.css', response.css, 'text/css');
                window.sscToast(__('CSS exporté !', 'supersede-css-jlg'));
            }).fail(() => {
                window.sscToast(__("Erreur lors de l'exportation du CSS.", 'supersede-css-jlg'));
            }).always(() => {
                btn.text(__('Exporter CSS (.css)', 'supersede-css-jlg')).prop('disabled', false);
            });
        });

        // --- IMPORTATION ---
        const importInput = $('#ssc-import-file');
        const importBtn = $('#ssc-import-btn');
        const importMsg = $('#ssc-import-msg');

        function setImportMessage(type, message) {
            importMsg.removeClass('ssc-success ssc-error');
            if (!message) {
                importMsg.text('');
                return;
            }

            if (type === 'success') {
                importMsg.addClass('ssc-success');
            } else if (type === 'error') {
                importMsg.addClass('ssc-error');
            }

            importMsg.text(message);
        }

        function handleImportError(message) {
            setImportMessage('error', message);
            if (typeof window.sscToast === 'function') {
                window.sscToast(message);
            }
        }

        importBtn.on('click', function() {
            const modules = ensureModulesSelected('import');
            if (!modules.length) {
                return;
            }

            const file = importInput[0]?.files?.[0];
            if (!file) {
                handleImportError(__('Veuillez sélectionner un fichier JSON à importer.', 'supersede-css-jlg'));
                return;
            }

            const reader = new FileReader();
            importBtn.text(__('Importation...', 'supersede-css-jlg')).prop('disabled', true);
            setImportMessage('', '');

            reader.onerror = () => {
                handleImportError(__("Impossible de lire le fichier sélectionné.", 'supersede-css-jlg'));
                importBtn.text(__('Importer', 'supersede-css-jlg')).prop('disabled', false);
            };

            reader.onload = () => {
                let parsed;
                try {
                    parsed = JSON.parse(reader.result);
                } catch (error) {
                    handleImportError(__('Le fichier importé ne contient pas un JSON valide.', 'supersede-css-jlg'));
                    importBtn.text(__('Importer', 'supersede-css-jlg')).prop('disabled', false);
                    return;
                }

                $.ajax({
                    url: SSC.rest.root + 'import-config',
                    method: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify({ options: parsed, modules, _wpnonce: SSC.rest.nonce }),
                    beforeSend: x => x.setRequestHeader('X-WP-Nonce', SSC.rest.nonce)
                }).done(response => {
                    const applied = Array.isArray(response.applied) ? response.applied.length : 0;
                    const skipped = Array.isArray(response.skipped) ? response.skipped.length : 0;
                    const message = skipped > 0
                        ? sprintf(__('Import terminé : %1$d option(s) appliquée(s), %2$d ignorée(s).', 'supersede-css-jlg'), applied, skipped)
                        : sprintf(__('Import terminé : %1$d option(s) appliquée(s).', 'supersede-css-jlg'), applied);

                    setImportMessage('success', message);
                    if (typeof window.sscToast === 'function') {
                        window.sscToast(__('Configuration importée !', 'supersede-css-jlg'));
                    }
                    importInput.val('');
                }).fail(jqXHR => {
                    const errorMessage = jqXHR?.responseJSON?.message
                        || __("Erreur lors de l'importation de la configuration.", 'supersede-css-jlg');
                    handleImportError(errorMessage);
                }).always(() => {
                    importBtn.text(__('Importer', 'supersede-css-jlg')).prop('disabled', false);
                });
            };

            reader.readAsText(file);
        });
    });
})(jQuery);
