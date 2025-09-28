(function($) {
    // Fonction pour déclencher le téléchargement d'un fichier
    function downloadFile(filename, content, mimeType) {
        const blob = new Blob([content], { type: mimeType });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        const revoke = () => URL.revokeObjectURL(url);
        if (typeof requestAnimationFrame === 'function') {
            requestAnimationFrame(revoke);
        } else {
            setTimeout(revoke, 0);
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
                ? 'Veuillez sélectionner au moins un ensemble à importer.'
                : 'Veuillez sélectionner au moins un ensemble à exporter.';

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

            btn.text('Exportation...').prop('disabled', true);

            $.ajax({
                url: SSC.rest.root + 'export-config',
                method: 'GET',
                data: { _wpnonce: SSC.rest.nonce, modules },
                beforeSend: x => x.setRequestHeader('X-WP-Nonce', SSC.rest.nonce)
            }).done(response => {
                const jsonContent = JSON.stringify(response, null, 2);
                downloadFile('supersede-config-export.json', jsonContent, 'application/json');
                window.sscToast('Configuration exportée !');
            }).fail(() => {
                window.sscToast("Erreur lors de l'exportation de la configuration.");
            }).always(() => {
                btn.text('Exporter Config (.json)').prop('disabled', false);
            });
        });

        // Exporter uniquement le CSS actif (.css)
        $('#ssc-export-css').on('click', function() {
            const btn = $(this);
            btn.text('Exportation...').prop('disabled', true);

            $.ajax({
                url: SSC.rest.root + 'export-css',
                method: 'GET',
                data: { _wpnonce: SSC.rest.nonce },
                beforeSend: x => x.setRequestHeader('X-WP-Nonce', SSC.rest.nonce)
            }).done(response => {
                downloadFile('supersede-styles.css', response.css, 'text/css');
                window.sscToast('CSS exporté !');
            }).fail(() => {
                window.sscToast("Erreur lors de l'exportation du CSS.");
            }).always(() => {
                btn.text('Exporter CSS (.css)').prop('disabled', false);
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
                handleImportError('Veuillez sélectionner un fichier JSON à importer.');
                return;
            }

            const reader = new FileReader();
            importBtn.text('Importation...').prop('disabled', true);
            setImportMessage('', '');

            reader.onerror = () => {
                handleImportError("Impossible de lire le fichier sélectionné.");
                importBtn.text('Importer').prop('disabled', false);
            };

            reader.onload = () => {
                let parsed;
                try {
                    parsed = JSON.parse(reader.result);
                } catch (error) {
                    handleImportError('Le fichier importé ne contient pas un JSON valide.');
                    importBtn.text('Importer').prop('disabled', false);
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
                        ? `Import terminé : ${applied} option(s) appliquée(s), ${skipped} ignorée(s).`
                        : `Import terminé : ${applied} option(s) appliquée(s).`;

                    setImportMessage('success', message);
                    if (typeof window.sscToast === 'function') {
                        window.sscToast('Configuration importée !');
                    }
                    importInput.val('');
                }).fail(jqXHR => {
                    const errorMessage = jqXHR?.responseJSON?.message
                        || "Erreur lors de l'importation de la configuration.";
                    handleImportError(errorMessage);
                }).always(() => {
                    importBtn.text('Importer').prop('disabled', false);
                });
            };

            reader.readAsText(file);
        });
    });
})(jQuery);
