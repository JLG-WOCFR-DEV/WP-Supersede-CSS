(function($) {
    $(document).ready(function() {
        const healthRunButton = $('#ssc-health-run');
        if (!healthRunButton.length) return;

        const l10n = window.sscDebugCenterL10n || {};
        const domain = typeof l10n.domain === 'string' && l10n.domain.length ? l10n.domain : 'supersede-css-jlg';
        const strings = l10n.strings && typeof l10n.strings === 'object' ? l10n.strings : {};
        const wpI18n = window.wp && window.wp.i18n ? window.wp.i18n : null;
        const hasI18n = !!(wpI18n && typeof wpI18n.__ === 'function');
        const sprintf = hasI18n && typeof wpI18n.sprintf === 'function'
            ? wpI18n.sprintf
            : (message, ...args) => {
                let index = 0;
                return String(message).replace(/%s/g, () => {
                    const replacement = args[index];
                    index += 1;
                    return typeof replacement === 'undefined' ? '' : String(replacement);
                });
            };

        const translate = (key) => {
            const fallback = Object.prototype.hasOwnProperty.call(strings, key) ? strings[key] : key;
            return hasI18n ? wpI18n.__(fallback, domain) : fallback;
        };

        const resultPane = $('#ssc-health-json');

        // Lancer le Health Check
        healthRunButton.on('click', function() {
            const btn = $(this);
            btn.text(translate('healthCheckCheckingLabel')).prop('disabled', true);
            resultPane.text(translate('healthCheckRunningMessage'));

            $.ajax({
                url: SSC.rest.root + 'health',
                method: 'GET',
                data: { _wpnonce: SSC.rest.nonce },
                beforeSend: x => x.setRequestHeader('X-WP-Nonce', SSC.rest.nonce)
            }).done(response => {
                resultPane.text(JSON.stringify(response, null, 2));
                window.sscToast(translate('healthCheckSuccessMessage'));
            }).fail(err => {
                resultPane.text(translate('healthCheckErrorMessage'));
                console.error('Health Check Error:', err);
            }).always(() => {
                btn.text(translate('healthCheckRunLabel')).prop('disabled', false);
            });
        });

        // Vider le journal d'activité
        $('#ssc-clear-log').on('click', function() {
            if (!confirm(translate('confirmClearLog'))) return;

            const btn = $(this);
            btn.prop('disabled', true);

            $.ajax({
                url: SSC.rest.root + 'clear-log',
                method: 'POST',
                data: { _wpnonce: SSC.rest.nonce },
                beforeSend: x => x.setRequestHeader('X-WP-Nonce', SSC.rest.nonce)
            }).done(() => {
                window.sscToast(translate('clearLogSuccess'));
                setTimeout(() => location.reload(), 1000);
            }).fail(() => {
                window.sscToast(translate('clearLogError'));
                btn.prop('disabled', false);
            });
        });

        // Réinitialiser tout le CSS
        $('#ssc-reset-all-css').on('click', function() {
            if (!confirm(translate('confirmResetAllCss'))) {
                return;
            }

            const btn = $(this);
            btn.text(translate('resetAllCssWorking')).prop('disabled', true);

            $.ajax({
                url: SSC.rest.root + 'reset-all-css',
                method: 'POST',
                data: { _wpnonce: SSC.rest.nonce },
                beforeSend: x => x.setRequestHeader('X-WP-Nonce', SSC.rest.nonce)
            }).done(() => {
                window.sscToast(translate('resetAllCssSuccess'));
                btn.text(translate('resetAllCssLabel')).prop('disabled', false);
                // Optionnellement, recharger la page pour voir les changements
                // location.reload();
            }).fail(() => {
                window.sscToast(translate('resetAllCssError'));
                btn.text(translate('resetAllCssLabel')).prop('disabled', false);
            });
        });

        $('.ssc-revision-restore').on('click', function() {
            if (typeof SSC === 'undefined' || !SSC.rest || !SSC.rest.root) {
                window.sscToast(translate('restUnavailable'));
                return;
            }

            const btn = $(this);
            const revisionId = btn.data('revision');
            const optionName = btn.data('option') || '';

            if (!revisionId) {
                window.sscToast(translate('revisionNotFound'));
                return;
            }

            const confirmationMessage = optionName
                ? sprintf(translate('confirmRestoreRevisionWithOption'), optionName)
                : translate('confirmRestoreRevision');

            if (!confirm(confirmationMessage)) {
                return;
            }

            const originalText = btn.text();
            btn.prop('disabled', true).text(translate('restoreWorking'));

            $.ajax({
                url: SSC.rest.root + 'css-revisions/' + encodeURIComponent(revisionId) + '/restore',
                method: 'POST',
                data: { _wpnonce: SSC.rest.nonce },
                beforeSend: x => x.setRequestHeader('X-WP-Nonce', SSC.rest.nonce)
            }).done(response => {
                if (response && response.ok) {
                    window.sscToast(translate('restoreSuccess'));
                    setTimeout(() => location.reload(), 800);
                    return;
                }

                window.sscToast(translate('restoreError'));
                btn.prop('disabled', false).text(originalText);
            }).fail(err => {
                let message = translate('restoreError');
                let duplicates = [];

                if (err && err.responseJSON) {
                    const payload = err.responseJSON;
                    if (typeof payload.message === 'string' && payload.message.length) {
                        message = payload.message;
                    }

                    if (payload.data && Array.isArray(payload.data.duplicates)) {
                        duplicates = payload.data.duplicates;
                    }
                }

                if (duplicates.length) {
                    console.error('Duplicate tokens detected while restoring a revision:', duplicates);
                } else {
                    console.error('Revision restore error:', err);
                }

                window.sscToast(message);
                btn.prop('disabled', false).text(originalText);
            });
        });
    });
})(jQuery);
