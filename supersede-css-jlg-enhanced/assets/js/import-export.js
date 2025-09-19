(function($) {
    // Fonction pour déclencher le téléchargement d'un fichier
    function downloadFile(filename, content, mimeType) {
        const blob = new Blob([content], { type: mimeType });
        const a = document.createElement('a');
        a.href = URL.createObjectURL(blob);
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
    }

    $(document).ready(function() {
        if (!$('#ssc-export-config').length) return;

        // --- EXPORTATION ---

        // Exporter la configuration complète (.json)
        $('#ssc-export-config').on('click', function() {
            const btn = $(this);
            btn.text('Exportation...').prop('disabled', true);

            $.ajax({
                url: SSC.rest.root + 'export-config',
                method: 'GET',
                data: { _wpnonce: SSC.rest.nonce },
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
        const importBtn = $('#ssc-import-btn');
        const importInput = $('#ssc-import-file');
        const importMsg = $('#ssc-import-msg');

        const showImportMessage = (type, text) => {
            if (!importMsg.length) {
                if (typeof window.sscToast === 'function') {
                    window.sscToast(text);
                }
                return;
            }

            importMsg
                .removeClass('ssc-muted ssc-notice-success ssc-notice-error')
                .text(text);

            if (type === 'success') {
                importMsg.addClass('ssc-notice-success');
                if (typeof window.sscToast === 'function') {
                    window.sscToast(text);
                }
            } else if (type === 'error') {
                importMsg.addClass('ssc-notice-error');
                if (typeof window.sscToast === 'function') {
                    window.sscToast(text);
                }
            } else {
                importMsg.addClass('ssc-muted');
            }
        };

        importBtn.on('click', function() {
            const btn = $(this);

            if (!importInput.length) {
                showImportMessage('error', 'Champ de fichier introuvable.');
                return;
            }

            const files = importInput[0].files;

            if (!files || !files.length) {
                showImportMessage('error', 'Sélectionnez un fichier JSON exporté depuis Supersede CSS.');
                return;
            }

            const file = files[0];
            const reader = new FileReader();

            btn.text('Importation...').prop('disabled', true);
            showImportMessage('info', 'Lecture du fichier...');

            reader.onerror = () => {
                btn.text('Importer').prop('disabled', false);
                showImportMessage('error', "Impossible de lire le fichier sélectionné.");
            };

            reader.onload = event => {
                let parsed;
                try {
                    parsed = JSON.parse(event.target.result);
                } catch (error) {
                    btn.text('Importer').prop('disabled', false);
                    showImportMessage('error', 'Le fichier ne contient pas un JSON valide.');
                    return;
                }

                if (parsed === null || typeof parsed !== 'object' || Array.isArray(parsed)) {
                    btn.text('Importer').prop('disabled', false);
                    showImportMessage('error', "Le JSON doit représenter un objet d'options Supersede CSS.");
                    return;
                }

                $.ajax({
                    url: SSC.rest.root + 'import-config',
                    method: 'POST',
                    data: {
                        payload: JSON.stringify(parsed),
                        _wpnonce: SSC.rest.nonce
                    },
                    beforeSend: x => x.setRequestHeader('X-WP-Nonce', SSC.rest.nonce)
                }).done(response => {
                    if (response && response.ok) {
                        const updated = Array.isArray(response.updated) ? response.updated.length : 0;
                        const message = updated > 0
                            ? `Configuration importée (${updated} option${updated > 1 ? 's' : ''} mise${updated > 1 ? 's' : ''} à jour).`
                            : 'Configuration importée.';
                        showImportMessage('success', message);
                        importInput.val('');
                    } else {
                        const message = response && response.message
                            ? response.message
                            : "Une erreur est survenue lors de l'import.";
                        showImportMessage('error', message);
                    }
                }).fail(xhr => {
                    const response = xhr.responseJSON;
                    const message = response && response.message
                        ? response.message
                        : "Une erreur est survenue lors de l'import.";
                    showImportMessage('error', message);
                }).always(() => {
                    btn.text('Importer').prop('disabled', false);
                });
            };

            reader.readAsText(file);
        });
    });
})(jQuery);