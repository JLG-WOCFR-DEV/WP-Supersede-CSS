(function($) {
    $(document).ready(function() {
        const healthRunButton = $('#ssc-health-run');
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

        const translate = (key, fallback = null) => {
            const defaultValue = fallback !== null ? fallback : key;
            const localized = Object.prototype.hasOwnProperty.call(strings, key) ? strings[key] : defaultValue;
            return hasI18n ? wpI18n.__(localized, domain) : localized;
        };

        const resultPane = $('#ssc-health-json-raw');
        const healthPanel = $('#ssc-health-panel');
        const summaryList = $('#ssc-health-summary-list');
        const summaryMeta = $('#ssc-health-summary-meta');
        const emptyState = $('#ssc-health-empty-state');
        const detailsPanel = $('#ssc-health-details');
        const errorNotice = $('#ssc-health-error');
        const copyButton = $('#ssc-health-copy');

        let lastHealthPayload = '';

        const severityColors = {
            success: '#15803d',
            warning: '#b45309',
            error: '#dc2626',
            info: '#2563eb'
        };

        const severityLabels = {
            success: translate('healthSeveritySuccess', 'Succès'),
            warning: translate('healthSeverityWarning', 'Avertissement'),
            error: translate('healthSeverityError', 'Erreur'),
            info: translate('healthSeverityInfo', 'Info')
        };

        const labelMap = {
            plugin_version: translate('healthLabelPluginVersion', 'Version du plugin'),
            wordpress_version: translate('healthLabelWpVersion', 'Version WordPress'),
            php_version: translate('healthLabelPhpVersion', 'Version PHP'),
            rest_api_status: translate('healthLabelRestStatus', 'Statut de l’API REST'),
            asset_files_exist: translate('healthLabelAssets', 'Fichiers d’assets'),
            plugin_integrity: translate('healthLabelIntegrity', 'Intégrité du plugin')
        };

        const updateCopyButton = (payload) => {
            if (!copyButton.length) {
                return;
            }

            copyButton.data('payload', payload);
            copyButton.prop('disabled', !payload);
        };

        const resetPanels = () => {
            if (errorNotice.length) {
                errorNotice.hide().text('');
            }

            if (summaryMeta.length) {
                summaryMeta.text('').hide();
            }

            if (summaryList.length) {
                summaryList.empty().hide();
            }

            if (emptyState.length) {
                emptyState.text(translate('healthCheckIdleMessage', 'Aucun diagnostic lancé pour le moment.')).show();
            }

            if (detailsPanel.length) {
                detailsPanel.attr('hidden', true).prop('open', false);
            }

            if (resultPane.length) {
                resultPane.text('');
            }

            updateCopyButton('');
            lastHealthPayload = '';
        };

        const setLoadingState = () => {
            if (!healthPanel.length) {
                return;
            }

            if (errorNotice.length) {
                errorNotice.hide().text('');
            }

            if (emptyState.length) {
                emptyState.text(translate('healthCheckRunningMessage', 'Analyse du système…')).show();
            }

            if (summaryMeta.length) {
                summaryMeta.text('').hide();
            }

            if (summaryList.length) {
                summaryList.empty().show();

                for (let i = 0; i < 3; i += 1) {
                    const skeleton = $('<li class="ssc-health-placeholder"></li>')
                        .css({
                            marginBottom: '8px',
                            borderRadius: '6px',
                            background: 'linear-gradient(90deg, #f1f5f9 0%, #e2e8f0 50%, #f1f5f9 100%)',
                            height: '18px'
                        });
                    summaryList.append(skeleton);
                }
            }

            if (detailsPanel.length) {
                detailsPanel.attr('hidden', true).prop('open', false);
            }

            if (resultPane.length) {
                resultPane.text('');
            }

            updateCopyButton('');
            lastHealthPayload = '';
        };

        const detectSeverity = (value, sourcePath) => {
            if (typeof value === 'boolean') {
                return value ? 'success' : 'error';
            }

            if (value === null || typeof value === 'undefined') {
                return 'warning';
            }

            const normalized = String(value).trim().toLowerCase();

            if (!normalized.length) {
                return 'warning';
            }

            const successIndicators = ['ok', 'chargé', 'charge', 'loaded', 'active', 'actif'];
            const errorIndicators = ['manquant', 'missing', 'non trouvé', 'erreur', 'error', 'fail', 'échec', 'ko', 'absent'];
            const warningIndicators = ['warning', 'lent', 'slow', 'n/a', 'non disponible', 'indisponible'];

            if (successIndicators.includes(normalized)) {
                return 'success';
            }

            if (errorIndicators.some(indicator => normalized.includes(indicator))) {
                return 'error';
            }

            if (warningIndicators.some(indicator => normalized.includes(indicator))) {
                return 'warning';
            }

            if (sourcePath.includes('version')) {
                return normalized === 'n/a' ? 'warning' : 'info';
            }

            return 'info';
        };

        const formatLabel = (key) => {
            if (Object.prototype.hasOwnProperty.call(labelMap, key)) {
                return labelMap[key];
            }

            if (typeof key !== 'string') {
                return translate('healthLabelFallback', 'Élément');
            }

            const normalized = key.replace(/[_-]+/g, ' ').trim();

            if (!normalized.length) {
                return translate('healthLabelFallback', 'Élément');
            }

            return normalized.charAt(0).toUpperCase() + normalized.slice(1);
        };

        const messageForValue = (value, sourcePath) => {
            if (typeof value === 'boolean') {
                return value
                    ? translate('healthMessageBooleanTrue', 'Statut confirmé.')
                    : translate('healthMessageBooleanFalse', 'Statut non confirmé.');
            }

            if (value === null || typeof value === 'undefined') {
                return translate('healthMessageUnavailable', 'Valeur indisponible.');
            }

            const asString = String(value);

            if (sourcePath.includes('version')) {
                return sprintf(translate('healthMessageVersion', 'Version détectée : %s'), asString);
            }

            if (sourcePath.includes('asset_files_exist')) {
                return sprintf(translate('healthMessageAssetStatus', 'Statut : %s'), asString);
            }

            if (sourcePath.includes('plugin_integrity')) {
                return sprintf(translate('healthMessageIntegrityStatus', 'Intégrité : %s'), asString);
            }

            if (sourcePath.includes('rest_api_status')) {
                return sprintf(translate('healthMessageRestStatus', 'Statut API REST : %s'), asString);
            }

            return sprintf(translate('healthMessageValue', 'Valeur : %s'), asString);
        };

        const suggestionForItem = (sourcePath, severity) => {
            if (severity === 'success') {
                return '';
            }

            if (sourcePath.includes('asset_files_exist')) {
                return translate('healthActionAsset', 'Vérifiez que les fichiers d’assets du plugin sont présents (réinstallation ou permissions).');
            }

            if (sourcePath.includes('plugin_integrity')) {
                return translate('healthActionIntegrity', 'Vérifiez le chargement des classes et fonctions critiques du plugin.');
            }

            if (sourcePath.includes('rest_api_status')) {
                return translate('healthActionRest', 'Contrôlez la disponibilité de l’API REST (extensions de sécurité, pare-feu, .htaccess).');
            }

            if (sourcePath.includes('plugin_version')) {
                return translate('healthActionPluginVersion', 'Vérifiez que Supersede CSS est à jour dans le gestionnaire d’extensions.');
            }

            if (sourcePath.includes('php_version')) {
                return translate('healthActionPhp', 'Confirmez la compatibilité PHP auprès de votre hébergeur.');
            }

            if (sourcePath.includes('wordpress_version')) {
                return translate('healthActionWordPress', 'Assurez-vous que WordPress est à jour pour bénéficier des dernières corrections.');
            }

            if (severity === 'warning') {
                return translate('healthActionWarningDefault', 'Inspectez ce point dans Santé du site pour confirmer la configuration.');
            }

            return translate('healthActionErrorDefault', 'Consultez l’outil Santé du site pour investiguer et corriger ce point.');
        };

        const flattenResponse = (data, parentLabel = '', parentPath = '') => {
            if (!data || typeof data !== 'object') {
                return [];
            }

            const items = [];

            Object.entries(data).forEach(([key, value]) => {
                const currentPath = parentPath ? `${parentPath}.${key}` : key;
                const label = formatLabel(key);
                const fullLabel = parentLabel ? `${parentLabel} › ${label}` : label;

                if (value && typeof value === 'object' && !Array.isArray(value)) {
                    items.push(...flattenResponse(value, fullLabel, currentPath));
                    return;
                }

                if (Array.isArray(value)) {
                    value.forEach((entry, index) => {
                        const arrayLabel = `${fullLabel} #${index + 1}`;

                        if (entry && typeof entry === 'object') {
                            items.push(...flattenResponse(entry, arrayLabel, `${currentPath}[${index}]`));
                            return;
                        }

                        const severity = detectSeverity(entry, currentPath);
                        items.push({
                            label: arrayLabel,
                            severity,
                            message: messageForValue(entry, currentPath),
                            action: suggestionForItem(currentPath, severity)
                        });
                    });
                    return;
                }

                const severity = detectSeverity(value, currentPath);
                items.push({
                    label: fullLabel,
                    severity,
                    message: messageForValue(value, currentPath),
                    action: suggestionForItem(currentPath, severity)
                });
            });

            return items;
        };

        const renderSummary = (response) => {
            if (!summaryList.length) {
                return;
            }

            const items = flattenResponse(response);

            summaryList.empty();

            if (!items.length) {
                if (emptyState.length) {
                    emptyState.text(translate('healthCheckEmptyResult', 'Le diagnostic n’a retourné aucune donnée exploitable.')).show();
                }
                summaryList.hide();
                if (summaryMeta.length) {
                    summaryMeta.text('').hide();
                }
                return;
            }

            if (emptyState.length) {
                emptyState.hide();
            }

            const totals = {
                total: items.length,
                success: 0,
                warning: 0,
                error: 0,
                info: 0
            };

            items.forEach(item => {
                if (Object.prototype.hasOwnProperty.call(totals, item.severity)) {
                    totals[item.severity] += 1;
                }

                const listItem = $('<li class="ssc-health-item"></li>').css({
                    marginBottom: '12px',
                    padding: '12px',
                    borderRadius: '8px',
                    border: '1px solid #e2e8f0',
                    backgroundColor: '#ffffff',
                    boxShadow: '0 1px 1px rgba(15, 23, 42, 0.04)'
                });

                const header = $('<div class="ssc-health-item__header"></div>').css({
                    display: 'flex',
                    flexWrap: 'wrap',
                    alignItems: 'center',
                    gap: '8px'
                });

                const badge = $('<span class="ssc-health-badge"></span>').text(severityLabels[item.severity] || severityLabels.info).css({
                    display: 'inline-flex',
                    alignItems: 'center',
                    padding: '2px 10px',
                    borderRadius: '999px',
                    fontSize: '11px',
                    fontWeight: 600,
                    textTransform: 'uppercase',
                    letterSpacing: '0.04em',
                    backgroundColor: severityColors[item.severity] || severityColors.info,
                    color: '#ffffff'
                });

                const label = $('<span class="ssc-health-item__label"></span>').text(item.label).css({
                    fontWeight: 600,
                    fontSize: '14px'
                });

                header.append(badge).append(label);

                const message = $('<p class="ssc-health-item__message"></p>').text(item.message).css({
                    margin: '8px 0 0',
                    fontSize: '13px',
                    color: '#1f2937'
                });

                listItem.append(header).append(message);

                if (item.action) {
                    const action = $('<p class="ssc-health-item__action"></p>').text(item.action).css({
                        margin: '8px 0 0',
                        fontSize: '12px',
                        color: '#4b5563'
                    });
                    listItem.append(action);
                }

                summaryList.append(listItem);
            });

            summaryList.show();

            if (summaryMeta.length) {
                const parts = [
                    sprintf(translate('healthSummaryTotal', '%d vérifications'), totals.total)
                ];

                if (totals.error) {
                    parts.push(sprintf(translate('healthSummaryErrors', '%d erreur(s)'), totals.error));
                }

                if (totals.warning) {
                    parts.push(sprintf(translate('healthSummaryWarnings', '%d avertissement(s)'), totals.warning));
                }

                if (totals.success) {
                    parts.push(sprintf(translate('healthSummarySuccess', '%d réussite(s)'), totals.success));
                }

                if (totals.info) {
                    parts.push(sprintf(translate('healthSummaryInfo', '%d info(s)'), totals.info));
                }

                summaryMeta.text(parts.join(' • ')).show();
            }
        };

        const handleSuccess = (response) => {
            if (errorNotice.length) {
                errorNotice.hide().text('');
            }

            renderSummary(response);

            if (resultPane.length) {
                lastHealthPayload = JSON.stringify(response, null, 2);
                resultPane.text(lastHealthPayload);
            }

            if (detailsPanel.length && lastHealthPayload) {
                detailsPanel.removeAttr('hidden');
            }

            updateCopyButton(lastHealthPayload);

            window.sscToast && window.sscToast(translate('healthCheckSuccessMessage', 'Health check terminé.'));
        };

        const handleError = (message) => {
            if (errorNotice.length) {
                errorNotice.text(message).show();
            }

            if (summaryMeta.length) {
                summaryMeta.text('').hide();
            }

            if (summaryList.length) {
                summaryList.empty().hide();
            }

            if (emptyState.length) {
                emptyState.text(translate('healthCheckErrorPersistent', 'Impossible de récupérer les données du diagnostic. Réessayez ou consultez Santé du site.')).show();
            }

            if (detailsPanel.length) {
                detailsPanel.attr('hidden', true).prop('open', false);
            }

            if (resultPane.length) {
                resultPane.text('');
            }

            updateCopyButton('');
            lastHealthPayload = '';
        };

        if (copyButton.length) {
            copyButton.on('click', function() {
                const payload = $(this).data('payload');

                if (!payload) {
                    return;
                }

                if (typeof window.sscCopyToClipboard === 'function') {
                    window.sscCopyToClipboard(payload, {
                        successMessage: translate('healthCopySuccessMessage', 'JSON copié dans le presse-papiers.'),
                        errorMessage: translate('healthCopyErrorMessage', 'La copie du JSON a échoué.')
                    });
                    return;
                }

                try {
                    navigator.clipboard.writeText(payload).then(() => {
                        window.sscToast && window.sscToast(translate('healthCopySuccessMessage', 'JSON copié dans le presse-papiers.'));
                    });
                } catch (err) {
                    console.error('Clipboard API non disponible', err);
                }
            });
        }

        if (healthRunButton.length && healthPanel.length) {
            resetPanels();

            healthRunButton.on('click', function() {
                const btn = $(this);
                btn.text(translate('healthCheckCheckingLabel', 'Vérification en cours…')).prop('disabled', true);

                setLoadingState();

                $.ajax({
                    url: SSC.rest.root + 'health',
                    method: 'GET',
                    data: { _wpnonce: SSC.rest.nonce },
                    beforeSend: x => x.setRequestHeader('X-WP-Nonce', SSC.rest.nonce)
                }).done(response => {
                    handleSuccess(response);
                }).fail(err => {
                    console.error('Health Check Error:', err);
                    handleError(translate('healthCheckErrorMessage', 'Une erreur est survenue.'));
                }).always(() => {
                    btn.text(translate('healthCheckRunLabel', 'Lancer Health Check')).prop('disabled', false);
                });
            });
        }

        $('#ssc-clear-log').on('click', function() {
            if (!confirm(translate('confirmClearLog', 'Voulez-vous vraiment vider le journal ?'))) {
                return;
            }

            const btn = $(this);
            btn.prop('disabled', true);

            $.ajax({
                url: SSC.rest.root + 'clear-log',
                method: 'POST',
                data: { _wpnonce: SSC.rest.nonce },
                beforeSend: x => x.setRequestHeader('X-WP-Nonce', SSC.rest.nonce)
            }).done(() => {
                window.sscToast && window.sscToast(translate('clearLogSuccess', 'Journal vidé.'));
                setTimeout(() => location.reload(), 1000);
            }).fail(() => {
                window.sscToast && window.sscToast(translate('clearLogError', 'Impossible de vider le journal.'));
                btn.prop('disabled', false);
            });
        });

        $('#ssc-reset-all-css').on('click', function() {
            if (!confirm(translate('confirmResetAllCss', 'Réinitialiser tout le CSS personnalisé ?'))) {
                return;
            }

            const btn = $(this);
            btn.text(translate('resetAllCssWorking', 'Réinitialisation…')).prop('disabled', true);

            $.ajax({
                url: SSC.rest.root + 'reset-all-css',
                method: 'POST',
                data: { _wpnonce: SSC.rest.nonce },
                beforeSend: x => x.setRequestHeader('X-WP-Nonce', SSC.rest.nonce)
            }).done(() => {
                window.sscToast && window.sscToast(translate('resetAllCssSuccess', 'CSS réinitialisé.'));
                btn.text(translate('resetAllCssLabel', 'Réinitialiser tout le CSS')).prop('disabled', false);
            }).fail(() => {
                window.sscToast && window.sscToast(translate('resetAllCssError', 'La réinitialisation a échoué.'));
                btn.text(translate('resetAllCssLabel', 'Réinitialiser tout le CSS')).prop('disabled', false);
            });
        });

        $('.ssc-revision-restore').on('click', function() {
            if (typeof SSC === 'undefined' || !SSC.rest || !SSC.rest.root) {
                window.sscToast && window.sscToast(translate('restUnavailable', 'API REST indisponible.'));
                return;
            }

            const btn = $(this);
            const revisionId = btn.data('revision');
            const optionName = btn.data('option') || '';

            if (!revisionId) {
                window.sscToast && window.sscToast(translate('revisionNotFound', 'Révision introuvable.'));
                return;
            }

            const confirmationMessage = optionName
                ? sprintf(translate('confirmRestoreRevisionWithOption', 'Restaurer la révision pour %s ?'), optionName)
                : translate('confirmRestoreRevision', 'Restaurer cette révision ?');

            if (!confirm(confirmationMessage)) {
                return;
            }

            const originalText = btn.text();
            btn.prop('disabled', true).text(translate('restoreWorking', 'Restauration…'));

            $.ajax({
                url: SSC.rest.root + 'css-revisions/' + encodeURIComponent(revisionId) + '/restore',
                method: 'POST',
                data: { _wpnonce: SSC.rest.nonce },
                beforeSend: x => x.setRequestHeader('X-WP-Nonce', SSC.rest.nonce)
            }).done(response => {
                if (response && response.ok) {
                    window.sscToast && window.sscToast(translate('restoreSuccess', 'Révision restaurée.'));
                    setTimeout(() => location.reload(), 800);
                    return;
                }

                window.sscToast && window.sscToast(translate('restoreError', 'Impossible de restaurer la révision.'));
                btn.prop('disabled', false).text(originalText);
            }).fail(err => {
                let message = translate('restoreError', 'Impossible de restaurer la révision.');
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

                window.sscToast && window.sscToast(message);
                btn.prop('disabled', false).text(originalText);
            });
        });

        const parseJsonFromScript = (selector) => {
            const node = document.querySelector(selector);
            if (!node) {
                return [];
            }

            try {
                return JSON.parse(node.textContent || '[]');
            } catch (err) {
                console.error('Unable to parse JSON for selector', selector, err);
                return [];
            }
        };

        const parseDateValue = (value, isEnd = false) => {
            if (!value) {
                return null;
            }

            const isoCandidate = `${value}T${isEnd ? '23:59:59' : '00:00:00'}Z`;
            const parsed = new Date(isoCandidate);
            return Number.isNaN(parsed.getTime()) ? null : parsed;
        };

        const revisionsData = parseJsonFromScript('#ssc-revisions-data');
        const revisionsById = revisionsData.reduce((acc, item) => {
            if (item && typeof item.id !== 'undefined') {
                acc[String(item.id)] = item;
            }
            return acc;
        }, {});

        const revisionRows = $('#ssc-revisions-table tbody tr');
        const revisionEmptyState = $('#ssc-revision-empty');
        const revisionFilterBar = $('#ssc-revision-filters');

        const applyRevisionFilters = () => {
            if (!revisionRows.length) {
                return;
            }

            const startValue = $('#ssc-revision-date-start').val();
            const endValue = $('#ssc-revision-date-end').val();
            const userValue = ($('#ssc-revision-user').val() || '').toString().toLowerCase();

            const startDate = parseDateValue(startValue);
            const endDate = parseDateValue(endValue, true);

            let visibleCount = 0;

            revisionRows.each(function() {
                const row = $(this);
                const ts = row.attr('data-timestamp');
                const author = (row.attr('data-author') || '').toLowerCase();

                let isVisible = true;

                if (startDate || endDate) {
                    const rowDate = ts ? new Date(ts) : null;
                    if (!rowDate || Number.isNaN(rowDate.getTime())) {
                        isVisible = false;
                    } else {
                        if (startDate && rowDate < startDate) {
                            isVisible = false;
                        }
                        if (endDate && rowDate > endDate) {
                            isVisible = false;
                        }
                    }
                }

                if (isVisible && userValue) {
                    isVisible = author.indexOf(userValue) !== -1;
                }

                row.toggle(isVisible);

                if (isVisible) {
                    visibleCount += 1;
                }
            });

            const hasActiveFilter = Boolean(startValue || endValue || userValue);
            revisionFilterBar.toggleClass('is-active', hasActiveFilter);

            if (revisionEmptyState.length) {
                if (visibleCount === 0) {
                    revisionEmptyState.removeAttr('hidden');
                } else {
                    revisionEmptyState.attr('hidden', 'hidden');
                }
            }
        };

        if (revisionRows.length) {
            $('#ssc-revision-filters [data-filter]').on('change input', applyRevisionFilters);
            applyRevisionFilters();
        }

        const diffOutput = $('#ssc-diff-output');
        const diffPlaceholder = diffOutput.data('placeholder') || translate('diffPlaceholder', 'Sélectionnez deux révisions pour visualiser leurs différences.');
        const setDiffPlaceholder = () => {
            diffOutput.removeClass('has-diff').text(diffPlaceholder);
        };

        if (diffOutput.length) {
            setDiffPlaceholder();
        }

        const getRevisionFromSelect = (selector) => {
            const id = $(selector).val();
            return id ? revisionsById[String(id)] || null : null;
        };

        $('#ssc-diff-load').on('click', function() {
            const baseRevision = getRevisionFromSelect('#ssc-diff-base');
            const compareRevision = getRevisionFromSelect('#ssc-diff-compare');

            if (!baseRevision || !compareRevision) {
                setDiffPlaceholder();
                return;
            }

            const diffApi = window.wp && window.wp.diff ? window.wp.diff : null;
            const baseLabel = baseRevision.timestamp || translate('diffBaseLabel', 'Révision de base');
            const compareLabel = compareRevision.timestamp || translate('diffCompareLabel', 'Révision à comparer');

            if (diffApi) {
                const result = diffApi(baseLabel, compareLabel, baseRevision.css || '', compareRevision.css || '');
                if (result) {
                    diffOutput.addClass('has-diff').html(result);
                    return;
                }
            }

            if ((baseRevision.css || '') === (compareRevision.css || '')) {
                diffOutput.addClass('has-diff').text(translate('noDiffDetected', 'Aucune différence détectée.'));
                return;
            }

            const fallback = [
                `--- ${baseLabel}`,
                `+++ ${compareLabel}`,
                '',
                compareRevision.css || ''
            ].join('\n');

            diffOutput.addClass('has-diff').text(fallback);
        });

        $('#ssc-revisions-table tbody tr').on('click', function() {
            const revisionId = $(this).attr('data-revision-id');
            if (!revisionId) {
                return;
            }

            $('#ssc-revisions-table tbody tr').removeClass('is-selected');
            $(this).addClass('is-selected');

            const compareSelect = $('#ssc-diff-compare');
            if (!compareSelect.val()) {
                compareSelect.val(revisionId);
            }
        });

        const triggerDownload = (filename, content, mime = 'text/plain') => {
            const blob = new Blob([content], { type: mime });
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = filename;
            document.body.appendChild(link);
            link.click();
            setTimeout(() => {
                URL.revokeObjectURL(link.href);
                document.body.removeChild(link);
            }, 0);
        };

        $('#ssc-export-css').on('click', function() {
            const activeId = $('#ssc-diff-compare').val() || $('#ssc-diff-base').val();
            if (!activeId) {
                window.sscToast && window.sscToast(translate('revisionNotFound', 'Révision introuvable.'));
                return;
            }

            const revision = revisionsById[String(activeId)];
            if (!revision) {
                window.sscToast && window.sscToast(translate('revisionNotFound', 'Révision introuvable.'));
                return;
            }

            const label = (revision.timestamp || 'revision').replace(/[\s:]/g, '-');
            const fileName = `supersede-css-${label}.css`;
            triggerDownload(fileName, revision.css || '', 'text/css');
        });

        const logRows = $('#ssc-log-table tbody tr');
        const logEmptyState = $('#ssc-log-empty');
        const logFilterBar = $('#ssc-log-filters');
        const logData = parseJsonFromScript('#ssc-log-data');

        const getLogEntryByIndex = (index) => {
            if (typeof index === 'undefined') {
                return null;
            }
            const intIndex = parseInt(index, 10);
            if (Number.isNaN(intIndex) || intIndex < 0 || intIndex >= logData.length) {
                return null;
            }
            return logData[intIndex];
        };

        const applyLogFilters = () => {
            const startValue = $('#ssc-log-date-start').val();
            const endValue = $('#ssc-log-date-end').val();
            const userValue = ($('#ssc-log-user').val() || '').toString().toLowerCase();
            const actionValue = ($('#ssc-log-action').val() || '').toString().toLowerCase();

            const startDate = parseDateValue(startValue);
            const endDate = parseDateValue(endValue, true);

            let visibleCount = 0;

            logRows.each(function() {
                const row = $(this);
                const ts = row.attr('data-timestamp');
                const user = (row.attr('data-user') || '').toLowerCase();
                const action = (row.attr('data-action') || '').toLowerCase();

                let isVisible = true;

                if (startDate || endDate) {
                    const rowDate = ts ? new Date(ts) : null;
                    if (!rowDate || Number.isNaN(rowDate.getTime())) {
                        isVisible = false;
                    } else {
                        if (startDate && rowDate < startDate) {
                            isVisible = false;
                        }
                        if (endDate && rowDate > endDate) {
                            isVisible = false;
                        }
                    }
                }

                if (isVisible && userValue) {
                    isVisible = user.indexOf(userValue) !== -1;
                }

                if (isVisible && actionValue) {
                    isVisible = action.indexOf(actionValue) !== -1;
                }

                row.toggle(isVisible);

                if (isVisible) {
                    visibleCount += 1;
                }
            });

            const hasActiveFilter = Boolean(startValue || endValue || userValue || actionValue);
            logFilterBar.toggleClass('is-active', hasActiveFilter);

            if (logEmptyState.length) {
                if (visibleCount === 0) {
                    logEmptyState.removeAttr('hidden');
                } else {
                    logEmptyState.attr('hidden', 'hidden');
                }
            }
        };

        if (logRows.length) {
            $('#ssc-log-filters [data-filter]').on('change input', applyLogFilters);
            applyLogFilters();
        }

        const getVisibleLogEntries = () => {
            const visibleEntries = [];
            logRows.each(function() {
                const row = $(this);
                if (!row.is(':visible')) {
                    return;
                }
                const index = row.attr('data-index');
                const entry = getLogEntryByIndex(index);
                if (entry) {
                    visibleEntries.push(entry);
                }
            });
            return visibleEntries;
        };

        $('#ssc-export-log-json').on('click', function() {
            const entries = getVisibleLogEntries();
            if (!entries.length) {
                window.sscToast && window.sscToast(translate('emptySelectionMessage', 'Aucune donnée à exporter.'));
                return;
            }

            triggerDownload('supersede-css-log.json', JSON.stringify(entries, null, 2), 'application/json');
        });

        $('#ssc-export-log-csv').on('click', function() {
            const entries = getVisibleLogEntries();
            if (!entries.length) {
                window.sscToast && window.sscToast(translate('emptySelectionMessage', 'Aucune donnée à exporter.'));
                return;
            }

            const csvHeader = ['date', 'user', 'action', 'data'];
            const csvRows = entries.map(item => {
                const rowData = [item.t || '', item.user || '', item.action || '', JSON.stringify(item.data || {})];
                return rowData.map(value => {
                    const safeValue = String(value).replace(/"/g, '""');
                    return `"${safeValue}"`;
                }).join(',');
            });

            const csvContent = [csvHeader.join(','), ...csvRows].join('\n');
            triggerDownload('supersede-css-log.csv', csvContent, 'text/csv');
        });
    });
})(jQuery);
