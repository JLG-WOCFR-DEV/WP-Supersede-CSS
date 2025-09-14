(function($) {
    $(document).ready(function() {
        if (!$('#ssc-health-run').length) return;
        const resultPane = $('#ssc-health-json');

        // Lancer le Health Check
        $('#ssc-health-run').on('click', function() {
            const btn = $(this);
            btn.text('Vérification...').prop('disabled', true);
            resultPane.text('Vérification en cours...');
            
            $.ajax({
                url: SSC.rest.root + 'health',
                method: 'GET',
                beforeSend: x => x.setRequestHeader('X-WP-Nonce', SSC.rest.nonce)
            }).done(response => {
                resultPane.text(JSON.stringify(response, null, 2));
                window.sscToast('Health Check terminé.');
            }).fail(err => {
                resultPane.text('Erreur lors du Health Check. Vérifiez la console du navigateur pour plus de détails.');
                console.error('Health Check Error:', err);
            }).always(() => {
                btn.text('Lancer Health Check').prop('disabled', false);
            });
        });
        
        // Vider le journal d'activité
        $('#ssc-clear-log').on('click', function() {
            if (!confirm("Voulez-vous vraiment effacer tout le journal d'activité ? Cette action est irréversible.")) return;

            const btn = $(this);
            btn.prop('disabled', true);

            $.ajax({
                url: SSC.rest.root + 'clear-log',
                method: 'POST',
                beforeSend: x => x.setRequestHeader('X-WP-Nonce', SSC.rest.nonce)
            }).done(() => {
                window.sscToast('Journal effacé ! La page va se recharger.');
                setTimeout(() => location.reload(), 1000);
            }).fail(() => {
                window.sscToast('Erreur lors de la suppression du journal.');
                btn.prop('disabled', false);
            });
        });

        // Réinitialiser tout le CSS
        $('#ssc-reset-all-css').on('click', function() {
            if (!confirm("ATTENTION : Vous êtes sur le point de supprimer TOUT le CSS généré par Supersede. Cette action est irréversible.\n\nVoulez-vous vraiment continuer ?")) {
                return;
            }

            const btn = $(this);
            btn.text('Réinitialisation...').prop('disabled', true);

            $.ajax({
                url: SSC.rest.root + 'reset-all-css',
                method: 'POST',
                beforeSend: x => x.setRequestHeader('X-WP-Nonce', SSC.rest.nonce)
            }).done(() => {
                window.sscToast('Tout le CSS a été réinitialisé !');
                btn.text('Réinitialiser tout le CSS').prop('disabled', false);
                // Optionnellement, recharger la page pour voir les changements
                // location.reload(); 
            }).fail(() => {
                window.sscToast('Erreur lors de la réinitialisation.');
                btn.text('Réinitialiser tout le CSS').prop('disabled', false);
            });
        });
    });
})(jQuery);