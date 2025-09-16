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
        
        // --- IMPORTATION (Logique de base) ---
        $('#ssc-import-btn').on('click', function() {
            alert("La fonctionnalité d'importation est en cours de développement.");
        });
    });
})(jQuery);