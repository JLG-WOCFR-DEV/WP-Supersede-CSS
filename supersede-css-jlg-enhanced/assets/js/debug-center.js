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

        const setVisibility = (element, isVisible) => {
            if (!element || !element.length) {
                return;
            }

            if (isVisible) {
                element.removeAttr('hidden');
            } else {
                element.attr('hidden', 'hidden');
            }
        };

        const resultPane = $('#ssc-health-json-raw');
        const healthPanel = $('#ssc-health-panel');
        const summaryList = $('#ssc-health-summary-list');
        const summaryMeta = $('#ssc-health-summary-meta');
        const summaryGenerated = $('#ssc-health-summary-generated');
        const emptyState = $('#ssc-health-empty-state');
        const detailsPanel = $('#ssc-health-details');
        const errorNotice = $('#ssc-health-error');
        const copyButton = $('#ssc-health-copy');
        const visualDebug = window.sscVisualDebug || null;
        const visualDebugToggle = $('#ssc-visual-debug-toggle');
        const visualDebugStatus = $('#ssc-visual-debug-status');
        const visualDebugNotice = $('#ssc-visual-debug-note');

        let lastHealthPayload = '';

        const locale = (document.documentElement && document.documentElement.lang)
            ? document.documentElement.lang
            : ((navigator.language || navigator.userLanguage || 'en-US'));
        const relativeTimeFormatter = (typeof Intl !== 'undefined' && typeof Intl.RelativeTimeFormat === 'function')
            ? new Intl.RelativeTimeFormat(locale, { numeric: 'auto' })
            : null;
        const dateTimeFormatter = (typeof Intl !== 'undefined' && typeof Intl.DateTimeFormat === 'function')
            ? new Intl.DateTimeFormat(locale, { dateStyle: 'medium', timeStyle: 'short' })
            : null;

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

        const visualDebugMessages = {
            onLabel: translate('visualDebugToggleOnLabel', 'Désactiver le débogage visuel'),
            offLabel: translate('visualDebugToggleOffLabel', 'Activer le débogage visuel'),
            enabled: translate('visualDebugEnabledMessage', 'Débogage visuel actif. Les surfaces sont annotées dans toute l’interface.'),
            disabled: translate('visualDebugDisabledMessage', 'Débogage visuel inactif.'),
            enabledToast: translate('visualDebugEnabledToast', 'Débogage visuel activé.'),
            disabledToast: translate('visualDebugDisabledToast', 'Débogage visuel désactivé.'),
            persisted: translate('visualDebugPersistedNotice', 'Préférence sauvegardée pour toutes les pages Supersede CSS.'),
        };

        const labelMap = {
            plugin_version: translate('healthLabelPluginVersion', 'Version du plugin'),
            wordpress_version: translate('healthLabelWpVersion', 'Version WordPress'),
            php_version: translate('healthLabelPhpVersion', 'Version PHP'),
            rest_api_status: translate('healthLabelRestStatus', 'Statut de l’API REST'),
            asset_files_exist: translate('healthLabelAssets', 'Fichiers d’assets'),
            plugin_integrity: translate('healthLabelIntegrity', 'Intégrité du plugin')
        };

        const formatDateTime = (isoString, fallbackIso) => {
            const candidate = (typeof isoString === 'string' && isoString.length)
                ? isoString
                : ((typeof fallbackIso === 'string' && fallbackIso.length) ? fallbackIso : null);

            if (!candidate) {
                return null;
            }

            const parsed = new Date(candidate);

            if (Number.isNaN(parsed.getTime())) {
                return null;
            }

            if (dateTimeFormatter) {
                try {
                    return dateTimeFormatter.format(parsed);
                } catch (err) {
                    // Fallback to native formatting below.
                }
            }

            try {
                return parsed.toLocaleString(locale);
            } catch (err) {
                return parsed.toISOString();
            }
        };

        const formatDuration = (seconds) => {
            if (typeof seconds !== 'number' || Number.isNaN(seconds) || seconds <= 0) {
                return translate('durationLessThanSecond', 'moins d’une seconde');
            }

            const units = [
                { limit: 60, divisor: 1, unit: 'second', fallback: 'durationSeconds' },
                { limit: 3600, divisor: 60, unit: 'minute', fallback: 'durationMinutes' },
                { limit: 86400, divisor: 3600, unit: 'hour', fallback: 'durationHours' },
                { limit: Infinity, divisor: 86400, unit: 'day', fallback: 'durationDays' },
            ];

            for (let i = 0; i < units.length; i += 1) {
                const { limit, divisor, unit, fallback } = units[i];

                if (seconds < limit) {
                    const value = Math.max(1, Math.round(seconds / divisor));

                    if (relativeTimeFormatter) {
                        try {
                            return relativeTimeFormatter.format(value, unit);
                        } catch (err) {
                            // Continue to fallback formatting.
                        }
                    }

                    return sprintf(translate(fallback, '%d'), value);
                }
            }

            return sprintf(translate('durationDays', '%d'), Math.round(seconds / 86400));
        };

        const updateCopyButton = (payload) => {
            if (!copyButton.length) {
                return;
            }

            copyButton.data('payload', payload);
            copyButton.prop('disabled', !payload);
        };

        const resetPanels = () => {
            if (healthRunButton.length) {
                healthRunButton.attr('aria-expanded', 'false');
            }

            if (healthPanel.length) {
                healthPanel.attr('aria-busy', 'false');
            }

            if (errorNotice.length) {
                errorNotice.text('');
                setVisibility(errorNotice, false);
            }

            if (summaryMeta.length) {
                summaryMeta.text('');
                setVisibility(summaryMeta, false);
            }

            if (summaryGenerated.length) {
                summaryGenerated.text('');
                setVisibility(summaryGenerated, false);
            }

            if (summaryList.length) {
                summaryList.empty();
                setVisibility(summaryList, false);
            }

            if (emptyState.length) {
                emptyState.text(translate('healthCheckIdleMessage', 'Aucun diagnostic lancé pour le moment.'));
                setVisibility(emptyState, true);
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

            healthPanel.attr('aria-busy', 'true');

            if (errorNotice.length) {
                errorNotice.text('');
                setVisibility(errorNotice, false);
            }

            if (emptyState.length) {
                emptyState.text(translate('healthCheckRunningMessage', 'Analyse du système…'));
                setVisibility(emptyState, true);
            }

            if (summaryMeta.length) {
                summaryMeta.text('');
                setVisibility(summaryMeta, false);
            }

            if (summaryGenerated.length) {
                summaryGenerated.text('');
                setVisibility(summaryGenerated, false);
            }

            if (summaryList.length) {
                summaryList.empty();
                setVisibility(summaryList, true);

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

            const safeResponse = (response && typeof response === 'object' && !Array.isArray(response))
                ? { ...response }
                : {};

            const meta = (safeResponse.meta && typeof safeResponse.meta === 'object')
                ? { ...safeResponse.meta }
                : null;

            if (Object.prototype.hasOwnProperty.call(safeResponse, 'meta')) {
                delete safeResponse.meta;
            }

            const items = flattenResponse(safeResponse);

            summaryList.empty();

            if (!items.length) {
                if (emptyState.length) {
                    emptyState.text(translate('healthCheckEmptyResult', 'Le diagnostic n’a retourné aucune donnée exploitable.'));
                    setVisibility(emptyState, true);
                }
                setVisibility(summaryList, false);
                if (summaryMeta.length) {
                    summaryMeta.text('');
                    setVisibility(summaryMeta, false);
                }
                return;
            }

            if (emptyState.length) {
                setVisibility(emptyState, false);
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

            setVisibility(summaryList, true);

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

                summaryMeta.text(parts.join(' • '));
                setVisibility(summaryMeta, true);
            }

            if (summaryGenerated.length) {
                if (meta) {
                    const messages = [];
                    const generatedLabel = formatDateTime(meta.generated_at, meta.generated_at_gmt);

                    if (generatedLabel) {
                        messages.push(sprintf(translate('healthSummaryGeneratedAt', 'Diagnostic généré le %s'), generatedLabel));
                    }

                    const ttlValue = Number(meta.cache_ttl);
                    const ttl = Number.isFinite(ttlValue) && ttlValue > 0 ? ttlValue : 0;
                    const secondsValue = Number(meta.seconds_until_expiration);
                    const secondsRemaining = Number.isFinite(secondsValue) && secondsValue > 0
                        ? secondsValue
                        : null;
                    const cacheHit = meta.cache_hit === true
                        || meta.cache_hit === 1
                        || meta.cache_hit === '1'
                        || meta.cache_hit === 'true';

                    if (ttl === 0) {
                        if (cacheHit) {
                            messages.push(translate('healthSummaryCacheHitNoExpiry', 'Réponse servie depuis le cache.'));
                        }

                        messages.push(translate('healthSummaryCacheDisabled', 'Cache désactivé pour ce diagnostic.'));

                        if (!cacheHit) {
                            messages.push(translate('healthSummaryCacheMiss', 'Réponse recalculée à la demande.'));
                        }
                    } else if (cacheHit) {
                        if (secondsRemaining !== null) {
                            messages.push(sprintf(
                                translate('healthSummaryCacheHit', 'Réponse servie depuis le cache (expire dans %s).'),
                                formatDuration(secondsRemaining)
                            ));
                        } else {
                            const expiresLabel = formatDateTime(meta.cache_expires_at, meta.cache_expires_at_gmt);

                            if (expiresLabel) {
                                messages.push(sprintf(
                                    translate('healthSummaryCacheHitExpiresAt', 'Réponse servie depuis le cache (expiration le %s).'),
                                    expiresLabel
                                ));
                            } else {
                                messages.push(translate('healthSummaryCacheHitNoExpiry', 'Réponse servie depuis le cache.'));
                            }
                        }
                    } else {
                        messages.push(translate('healthSummaryCacheMiss', 'Réponse recalculée à la demande.'));
                    }

                    const uniqueMessages = messages.filter((message, index, array) => (
                        typeof message === 'string'
                        && message.length
                        && array.indexOf(message) === index
                    ));

                    if (uniqueMessages.length) {
                        summaryGenerated.text(uniqueMessages.join(' • '));
                        setVisibility(summaryGenerated, true);
                    } else {
                        summaryGenerated.text('');
                        setVisibility(summaryGenerated, false);
                    }
                } else {
                    summaryGenerated.text('');
                    setVisibility(summaryGenerated, false);
                }
            }

            if (healthRunButton.length) {
                healthRunButton.attr('aria-expanded', items.length ? 'true' : 'false');
            }
        };

        const handleSuccess = (response) => {
            if (errorNotice.length) {
                errorNotice.text('');
                setVisibility(errorNotice, false);
            }

            if (healthPanel.length) {
                healthPanel.attr('aria-busy', 'false');
            }

            if (healthRunButton.length) {
                healthRunButton.attr('aria-busy', 'false');
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
                errorNotice.text(message);
                setVisibility(errorNotice, true);
            }

            if (healthPanel.length) {
                healthPanel.attr('aria-busy', 'false');
            }

            if (healthRunButton.length) {
                healthRunButton.attr('aria-busy', 'false');
            }

            if (summaryMeta.length) {
                summaryMeta.text('');
                setVisibility(summaryMeta, false);
            }

            if (summaryGenerated.length) {
                summaryGenerated.text('');
                setVisibility(summaryGenerated, false);
            }

            if (summaryList.length) {
                summaryList.empty();
                setVisibility(summaryList, false);
            }

            if (emptyState.length) {
                emptyState.text(translate('healthCheckErrorPersistent', 'Impossible de récupérer les données du diagnostic. Réessayez ou consultez Santé du site.'));
                setVisibility(emptyState, true);
            }

            if (detailsPanel.length) {
                detailsPanel.attr('hidden', true).prop('open', false);
            }

            if (resultPane.length) {
                resultPane.text('');
            }

            updateCopyButton('');
            lastHealthPayload = '';

            if (healthRunButton.length) {
                healthRunButton.attr('aria-expanded', 'false');
            }
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
                btn.text(translate('healthCheckCheckingLabel', 'Vérification en cours…')).prop('disabled', true).attr('aria-busy', 'true');

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
                    btn.text(translate('healthCheckRunLabel', 'Lancer Health Check')).prop('disabled', false).attr('aria-busy', 'false');
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
            const primaryMessage = translate('confirmResetAllCss', 'Réinitialiser tout le CSS personnalisé ?');
            if (!confirm(primaryMessage)) {
                return;
            }

            const secondaryMessage = translate('confirmResetAllCssSecondary', 'Dernier avertissement : cette action est irréversible. Confirmez la réinitialisation ?');
            if (!confirm(secondaryMessage)) {
                return;
            }

            const btn = $(this);
            btn.text(translate('resetAllCssWorking', 'Réinitialisation…')).prop('disabled', true).attr('aria-busy', 'true');

            $.ajax({
                url: SSC.rest.root + 'reset-all-css',
                method: 'POST',
                data: { _wpnonce: SSC.rest.nonce },
                beforeSend: x => x.setRequestHeader('X-WP-Nonce', SSC.rest.nonce)
            }).done(() => {
                window.sscToast && window.sscToast(translate('resetAllCssSuccess', 'CSS réinitialisé.'));
                btn.text(translate('resetAllCssLabel', 'Réinitialiser tout le CSS')).prop('disabled', false).attr('aria-busy', 'false');
            }).fail(() => {
                window.sscToast && window.sscToast(translate('resetAllCssError', 'La réinitialisation a échoué.'));
                btn.text(translate('resetAllCssLabel', 'Réinitialiser tout le CSS')).prop('disabled', false).attr('aria-busy', 'false');
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
            try {
                const blob = new Blob([content], { type: mime });
                const link = document.createElement('a');
                link.href = URL.createObjectURL(blob);
                link.download = filename;
                link.style.display = 'none';
                document.body.appendChild(link);
                link.click();
                setTimeout(() => {
                    URL.revokeObjectURL(link.href);
                    if (link.parentNode) {
                        link.parentNode.removeChild(link);
                    }
                }, 0);

                return true;
            } catch (error) {
                if (window.console && typeof window.console.error === 'function') {
                    console.error('Supersede CSS — download error', error);
                }

                return false;
            }
        };

        const exportPermissionsNode = document.querySelector('#ssc-exports-permissions');
        let exportPermissions = null;

        if (exportPermissionsNode) {
            try {
                exportPermissions = JSON.parse(exportPermissionsNode.textContent || '{}');
            } catch (error) {
                if (window.console && typeof window.console.error === 'function') {
                    console.error('Supersede CSS — unable to parse export permissions', error);
                }
            }
        }

        const exportFormatSelect = $('#ssc-token-export-format');
        const exportScopeSelect = $('#ssc-token-export-scope');
        const exportRunButton = $('#ssc-token-export-run');
        const exportStatusMessage = $('#ssc-token-export-status');

        const exportsState = {
            canExport: (() => {
                if (exportPermissions && typeof exportPermissions === 'object' && !Array.isArray(exportPermissions)) {
                    return !!exportPermissions.canExport;
                }

                if (exportRunButton.length) {
                    const dataAttr = exportRunButton.data('can-export');
                    return dataAttr === true || dataAttr === 1 || dataAttr === '1';
                }

                return false;
            })(),
            isRunning: false,
        };

        const setExportsRunning = (running) => {
            exportsState.isRunning = !!running;

            if (!exportRunButton.length) {
                return;
            }

            const shouldDisable = !!running || !exportsState.canExport;
            exportRunButton.prop('disabled', shouldDisable);
            exportRunButton.attr('aria-busy', running ? 'true' : 'false');
        };

        const updateExportStatus = (message, state = 'info') => {
            if (!exportStatusMessage.length) {
                return;
            }

            if (!message) {
                exportStatusMessage.text('');
                exportStatusMessage.attr('hidden', 'hidden');
                exportStatusMessage.removeAttr('data-state');
                return;
            }

            exportStatusMessage.text(message);
            exportStatusMessage.attr('data-state', state);
            exportStatusMessage.removeAttr('hidden');
        };

        const resolveExportFilename = (format, scope) => {
            const safeFormat = (format || 'style-dictionary').toString();
            const safeScope = (scope || 'ready').toString();
            const timestamp = new Date().toISOString().replace(/[:.]/g, '-');
            const extension = safeFormat === 'android' ? 'xml' : 'json';

            return `supersede-tokens-${safeFormat}-${safeScope}-${timestamp}.${extension}`;
        };

        const handleExportSuccess = (format, scope, payload, contentType) => {
            let data = payload;
            let mimeType = contentType || 'application/octet-stream';

            if (typeof data !== 'string' && (mimeType.includes('json') || format !== 'android')) {
                data = JSON.stringify(data, null, 2);
                mimeType = 'application/json';
            }

            if (typeof data !== 'string') {
                data = String(data);
            }

            const filename = resolveExportFilename(format, scope);
            const downloadOk = triggerDownload(filename, data, mimeType);

            if (!downloadOk) {
                updateExportStatus(translate('exportsDownloadError', 'Le fichier n’a pas pu être téléchargé. Réessayez.'), 'error');
                return;
            }

            updateExportStatus(translate('exportsSuccess', 'Export prêt. Le téléchargement va démarrer.'), 'success');

            if (typeof window.sscToast === 'function') {
                window.sscToast(translate('exportsSuccess', 'Export prêt. Le téléchargement va démarrer.'));
            }
        };

        const handleExportError = (code, error) => {
            if (code === 'forbidden') {
                exportsState.canExport = false;
                updateExportStatus(translate('exportsForbidden', 'Vous n’avez pas les droits nécessaires pour exporter.'), 'error');
                if (typeof window.sscToast === 'function') {
                    window.sscToast(translate('exportsForbidden', 'Vous n’avez pas les droits nécessaires pour exporter.'));
                }
                return;
            }

            updateExportStatus(translate('exportsError', 'Impossible de générer l’export.'), 'error');

            if (window.console && typeof window.console.error === 'function') {
                console.error('Supersede CSS — export error', error);
            }
        };

        const requestTokenExport = (format, scope) => {
            if (typeof SSC === 'undefined' || !SSC.rest || !SSC.rest.root || !SSC.rest.nonce) {
                updateExportStatus(translate('exportsUnavailable', 'Export indisponible : API REST injoignable.'), 'error');
                return;
            }

            setExportsRunning(true);
            updateExportStatus(translate('exportsPreparing', 'Préparation de l’export…'), 'info');

            const params = new URLSearchParams({
                format,
                scope,
            });

            fetch(`${SSC.rest.root}exports?${params.toString()}`, {
                method: 'GET',
                headers: {
                    'X-WP-Nonce': SSC.rest.nonce,
                },
                credentials: 'same-origin',
            })
                .then((response) => {
                    if (!response.ok) {
                        const error = new Error('http_error');
                        error.code = response.status === 403 ? 'forbidden' : 'http_error';
                        throw error;
                    }

                    const contentType = (response.headers.get('content-type') || '').toLowerCase();

                    if (contentType.indexOf('application/json') !== -1) {
                        return response.json().then((json) => ({ payload: json, contentType }));
                    }

                    return response.text().then((text) => ({ payload: text, contentType }));
                })
                .then(({ payload, contentType }) => {
                    handleExportSuccess(format, scope, payload, contentType);
                })
                .catch((error) => {
                    handleExportError(error && error.code ? error.code : 'http_error', error);
                })
                .finally(() => {
                    setExportsRunning(false);
                });
        };

        const handleTokenExport = () => {
            if (exportsState.isRunning) {
                return;
            }

            if (!exportsState.canExport) {
                if (typeof window.sscToast === 'function') {
                    window.sscToast(translate('exportsForbidden', 'Vous n’avez pas les droits nécessaires pour exporter.'));
                }
                return;
            }

            const format = (exportFormatSelect.val() || 'style-dictionary').toString();
            const scope = (exportScopeSelect.val() || 'ready').toString();

            requestTokenExport(format, scope);
        };

        if (exportRunButton.length) {
            if (!exportsState.canExport) {
                exportRunButton.prop('disabled', true);
            }

            exportRunButton.on('click', handleTokenExport);
        }

        const updateVisualDebugUi = (enabled, { silent = false } = {}) => {
            const isEnabled = !!enabled;

            if (visualDebugToggle.length) {
                visualDebugToggle.attr('aria-pressed', isEnabled ? 'true' : 'false');
                visualDebugToggle.text(isEnabled ? visualDebugMessages.onLabel : visualDebugMessages.offLabel);
            }

            if (visualDebugStatus.length) {
                visualDebugStatus.text(isEnabled ? visualDebugMessages.enabled : visualDebugMessages.disabled);
            }

            if (!silent && typeof window.sscToast === 'function') {
                window.sscToast(isEnabled ? visualDebugMessages.enabledToast : visualDebugMessages.disabledToast, {
                    politeness: 'polite',
                });
            }

            if (visualDebugNotice.length) {
                if (isEnabled) {
                    visualDebugNotice.removeAttr('hidden').text(visualDebugMessages.persisted).show();
                } else {
                    visualDebugNotice.text('').attr('hidden', 'hidden').hide();
                }
            }
        };

        const bindVisualDebug = () => {
            if (!visualDebugToggle.length) {
                return;
            }

            const handleChange = (state, meta = {}) => {
                const fromDebugCenter = meta && meta.source === 'debug-center';
                const shouldRemainSilent = meta && (meta.silent || meta.initial || !fromDebugCenter);
                updateVisualDebugUi(state, { silent: shouldRemainSilent });
            };

            if (visualDebug && typeof visualDebug.onChange === 'function') {
                visualDebug.onChange(handleChange);
            } else {
                $(document).on('ssc:visual-debug:change', (event, state, meta = {}) => {
                    handleChange(!!state, meta || {});
                });
                handleChange($('body').hasClass('ssc-visual-debug-active'), { silent: true, initial: true });
            }

            visualDebugToggle.on('click', function(event) {
                event.preventDefault();
                const nextState = visualDebugToggle.attr('aria-pressed') !== 'true';

                if (visualDebug && typeof visualDebug.set === 'function') {
                    visualDebug.set(nextState, { source: 'debug-center' });
                } else {
                    $('body').toggleClass('ssc-visual-debug-active', nextState);
                    try {
                        window.localStorage.setItem('ssc-visual-debug-enabled', nextState ? '1' : '0');
                    } catch (error) {
                        // Ignore storage errors.
                    }
                    $(document).trigger('ssc:visual-debug:change', [nextState, { source: 'debug-center' }]);
                }
            });

            if (visualDebugNotice.length) {
                visualDebugNotice.attr('aria-live', 'polite');
            }
        };

        bindVisualDebug();

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

        const approvalsTableBody = $('#ssc-approvals-table tbody');
        const approvalsEmptyState = $('#ssc-approvals-empty');
        const approvalsFilter = $('#ssc-approvals-filter');
        const approvalsRefreshButton = $('#ssc-approvals-refresh');
        const approvalsPermissions = parseJsonFromScript('#ssc-approvals-permissions');
        const approvalsInitialData = parseJsonFromScript('#ssc-approvals-data');
        const approvalsPriorityDefinitions = parseJsonFromScript('#ssc-approvals-priorities');
        const approvalsTokenMetaRaw = parseJsonFromScript('#ssc-approvals-token-meta');
        const approvalsTokenMeta = (approvalsTokenMetaRaw && !Array.isArray(approvalsTokenMetaRaw))
            ? approvalsTokenMetaRaw
            : {};
        const approvalsTokenLookup = approvalsTokenMeta.tokens && typeof approvalsTokenMeta.tokens === 'object'
            ? approvalsTokenMeta.tokens
            : {};
        const approvalsStatusMeta = approvalsTokenMeta.statuses && typeof approvalsTokenMeta.statuses === 'object'
            ? approvalsTokenMeta.statuses
            : {};
        const approvalsSlaRules = approvalsTokenMeta.sla && typeof approvalsTokenMeta.sla === 'object'
            ? approvalsTokenMeta.sla
            : {};

        const priorityMeta = buildPriorityMeta(approvalsPriorityDefinitions);

        const approvalsState = {
            entries: normalizeApprovals(approvalsInitialData),
            currentStatus: approvalsFilter.val() || 'pending',
            canReview: approvalsPermissions && approvalsPermissions.canReview,
        };
        const approvalsModalElement = $('#ssc-approval-review-modal');
        const approvalsModalBadges = $('#ssc-approval-review-badges');
        const approvalsModalDialog = approvalsModalElement.find('.ssc-modal__dialog');
        const approvalsModalMeta = $('#ssc-approval-review-meta');
        const approvalsModalValue = $('#ssc-approval-review-value');
        const approvalsModalCopy = $('#ssc-approval-review-copy');
        const approvalsModalChangelog = $('#ssc-approval-review-changelog');
        const approvalsModalComponents = $('#ssc-approval-review-components');
        const approvalsModalComments = $('#ssc-approval-review-comments');
        const approvalsModalTimeline = $('#ssc-approval-review-timeline');
        const approvalsModalTimelineEmpty = $('#ssc-approval-review-timeline-empty');
        const approvalsModalTimelineError = $('#ssc-approval-review-timeline-error');
        const approvalsModalLoading = $('#ssc-approval-review-loading');
        const approvalsModalCloseButtons = $('[data-ssc-approval-modal-close]');
        const approvalsModalTitle = $('#ssc-approval-review-title');
        const approvalsModalEyebrow = $('#ssc-approval-review-eyebrow');
        let approvalsModalState = {
            open: false,
            currentId: null,
            tokenKey: null,
            restoreFocus: null,
        };

        function createUserPlaceholder(id) {
            if (typeof id !== 'number' || Number.isNaN(id) || id <= 0) {
                return {
                    id: 0,
                    name: translate('approvalStatusUnknown', 'Statut inconnu'),
                    avatar: '',
                };
            }

            return {
                id,
                name: `#${id}`,
                avatar: '',
            };
        }

        function buildPriorityMeta(definitions) {
            const list = Array.isArray(definitions) ? definitions : [];
            const meta = {
                labels: Object.create(null),
                defaultValue: 'normal',
            };

            list.forEach((item) => {
                if (!item || typeof item !== 'object') {
                    return;
                }

                const value = typeof item.value === 'string' ? item.value.trim().toLowerCase() : '';
                if (!value) {
                    return;
                }

                const label = typeof item.label === 'string' && item.label.length ? item.label : value;
                meta.labels[value] = { value, label };

                if (item.default === true) {
                    meta.defaultValue = value;
                }
            });

            if (!meta.labels[meta.defaultValue]) {
                if (meta.labels.normal) {
                    meta.defaultValue = 'normal';
                } else {
                    const firstKey = Object.keys(meta.labels)[0];
                    if (firstKey) {
                        meta.defaultValue = firstKey;
                    }
                }
            }

            return meta;
        }

        function normalizePriorityValue(value) {
            const normalized = typeof value === 'string' ? value.trim().toLowerCase() : '';
            if (normalized && priorityMeta.labels[normalized]) {
                return normalized;
            }

            return priorityMeta.defaultValue;
        }

        function getPriorityLabel(value) {
            const normalized = normalizePriorityValue(value);
            const meta = priorityMeta.labels[normalized];

            if (meta && meta.label) {
                return meta.label;
            }

            if (normalized === 'low') {
                return translate('approvalPriorityLow', 'Faible');
            }
            if (normalized === 'normal') {
                return translate('approvalPriorityNormal', 'Normale');
            }
            if (normalized === 'high') {
                return translate('approvalPriorityHigh', 'Haute');
            }

            return translate('approvalPriorityUnknown', 'Priorité inconnue');
        }

        function buildTokenKey(name, context) {
            const safeName = typeof name === 'string' ? name.trim().toLowerCase() : '';
            const safeContext = typeof context === 'string' ? context.trim().toLowerCase() : '';
            return `${safeContext}|${safeName}`;
        }

        function getTokenMeta(entry) {
            if (!entry || !entry.token) {
                return { key: '', meta: null };
            }

            const tokenName = entry.token.name || '';
            const tokenContext = entry.token.context || '';
            const key = buildTokenKey(tokenName, tokenContext);

            if (key && approvalsTokenLookup[key]) {
                return { key, meta: approvalsTokenLookup[key] };
            }

            return { key, meta: null };
        }

        function getStatusMeta(value) {
            const normalized = typeof value === 'string' ? value.trim().toLowerCase() : '';
            if (normalized && approvalsStatusMeta[normalized]) {
                return approvalsStatusMeta[normalized];
            }

            return { label: value || '', description: '' };
        }

        function computeSlaMeta(entry) {
            if (!entry) {
                return null;
            }

            const requestedAtIso = entry.requested_at || '';
            if (!requestedAtIso) {
                return null;
            }

            const requestedAt = new Date(requestedAtIso);
            if (Number.isNaN(requestedAt.getTime())) {
                return null;
            }

            const priority = normalizePriorityValue(entry.priority);
            const rule = approvalsSlaRules[priority];
            if (!rule || typeof rule.hours !== 'number' || Number.isNaN(rule.hours) || rule.hours <= 0) {
                return null;
            }

            const status = (entry.status || 'pending').toLowerCase();
            const targetTime = requestedAt.getTime() + (rule.hours * 60 * 60 * 1000);
            const now = Date.now();

            let state = 'pending';
            let diffSeconds;
            let decisionAtTime = null;

            if (status === 'pending') {
                diffSeconds = Math.round((targetTime - now) / 1000);
                if (diffSeconds < 0) {
                    state = 'overdue';
                    diffSeconds = Math.abs(diffSeconds);
                }
            } else {
                const decisionAtIso = entry.decision && entry.decision.decided_at ? entry.decision.decided_at : '';
                const decisionAt = decisionAtIso ? new Date(decisionAtIso) : null;

                if (decisionAt && !Number.isNaN(decisionAt.getTime())) {
                    decisionAtTime = decisionAt.getTime();
                    const delta = decisionAtTime - targetTime;
                    diffSeconds = Math.round(Math.abs(delta) / 1000);
                    state = delta <= 0 ? 'fulfilled' : 'fulfilled_late';
                } else {
                    diffSeconds = Math.round(Math.abs(targetTime - now) / 1000);
                    state = targetTime < now ? 'overdue' : 'pending';
                }
            }

            return {
                state,
                priority,
                diffSeconds,
                targetTime,
                requestedAt: requestedAt.getTime(),
                decisionAt: decisionAtTime,
            };
        }

        function buildSlaDisplay(entry) {
            const meta = computeSlaMeta(entry);
            if (!meta) {
                return null;
            }

            const targetIso = new Date(meta.targetTime).toISOString();
            const targetLabel = formatDateTime(targetIso) || targetIso;

            let text = '';
            let cssClass = '';

            if (meta.state === 'pending') {
                const remaining = formatDuration(Math.max(1, meta.diffSeconds || 0));
                text = translate('approvalsReviewSlaRemaining', 'Temps restant : %s').replace('%s', remaining);
            } else if (meta.state === 'overdue') {
                const overdue = formatDuration(Math.max(1, meta.diffSeconds || 0));
                text = translate('approvalsReviewSlaOverdue', 'Retard de %s').replace('%s', overdue);
                cssClass = 'is-overdue';
            } else if (meta.state === 'fulfilled') {
                text = translate('approvalsReviewSlaMet', 'Revue clôturée dans les temps.');
                cssClass = 'is-success';
            } else if (meta.state === 'fulfilled_late') {
                const delay = formatDuration(Math.max(1, meta.diffSeconds || 0));
                text = translate('approvalsReviewSlaLate', 'Clôturée avec %s de retard.').replace('%s', delay);
                cssClass = 'is-overdue';
            }

            return {
                text,
                cssClass,
                meta,
                title: translate('approvalsReviewSlaTarget', 'Délai cible : %s').replace('%s', targetLabel),
            };
        }

        function applySlaToRow(row, entry) {
            if (!row || !row.length) {
                return;
            }

            const slaDisplay = buildSlaDisplay(entry);
            const slaElement = row.find('.ssc-approval-sla');

            if (!slaElement.length) {
                return;
            }

            if (!slaDisplay || !slaDisplay.text) {
                slaElement.attr('hidden', 'hidden').text('');
                row.removeClass('ssc-approvals-row--overdue');
                return;
            }

            slaElement.text(slaDisplay.text);
            slaElement.attr('title', slaDisplay.title);
            slaElement.removeAttr('hidden');
            slaElement.removeClass('is-overdue is-success');
            if (slaDisplay.cssClass) {
                slaElement.addClass(slaDisplay.cssClass);
            }

            if (slaDisplay.meta && (slaDisplay.meta.state === 'overdue' || slaDisplay.meta.state === 'fulfilled_late')) {
                row.addClass('ssc-approvals-row--overdue');
            } else {
                row.removeClass('ssc-approvals-row--overdue');
            }
        }

        const timelineEventLabels = {
            'token.created': translate('approvalsTimelineTokenCreated', 'Token créé'),
            'token.updated': translate('approvalsTimelineTokenUpdated', 'Token mis à jour'),
            'token.approved': translate('approvalsTimelineTokenApproved', 'Token approuvé'),
            'token.deprecated': translate('approvalsTimelineTokenDeprecated', 'Token déprécié'),
            'token.approval_requested': translate('approvalsTimelineApprovalRequested', 'Demande d’approbation'),
            'token.approval_changes_requested': translate('approvalsTimelineApprovalChangesRequested', 'Changements demandés'),
            'css.published': translate('approvalsTimelineCssPublished', 'CSS publié'),
            'preset.changed': translate('approvalsTimelinePresetChanged', 'Preset mis à jour'),
            'export.generated': translate('approvalsTimelineExportGenerated', 'Export généré'),
        };

        function getTimelineEventLabel(eventName) {
            if (timelineEventLabels[eventName]) {
                return timelineEventLabels[eventName];
            }

            return eventName || '';
        }

        function copyToClipboard(text) {
            if (navigator.clipboard && typeof navigator.clipboard.writeText === 'function') {
                return navigator.clipboard.writeText(text);
            }

            return new Promise((resolve, reject) => {
                const textarea = document.createElement('textarea');
                textarea.value = text;
                textarea.setAttribute('readonly', '');
                textarea.style.position = 'absolute';
                textarea.style.left = '-9999px';
                document.body.appendChild(textarea);

                textarea.select();

                try {
                    const successful = document.execCommand('copy');
                    document.body.removeChild(textarea);
                    if (successful) {
                        resolve();
                    } else {
                        reject(new Error('copy-failed'));
                    }
                } catch (error) {
                    document.body.removeChild(textarea);
                    reject(error);
                }
            });
        }

        function normalizeApprovals(raw) {
            const list = Array.isArray(raw) ? raw : [];

            return list.map((entry) => {
                const normalized = { ...entry };
                const requestedByUser = entry && entry.requested_by_user ? entry.requested_by_user : null;
                const requestedById = typeof entry.requested_by === 'number' ? entry.requested_by : (requestedByUser && requestedByUser.id) || 0;

                normalized.requested_by = requestedById;
                if (requestedByUser && typeof requestedByUser === 'object') {
                    normalized.requested_by_user = {
                        id: requestedByUser.id || requestedById,
                        name: requestedByUser.name || createUserPlaceholder(requestedById).name,
                        avatar: requestedByUser.avatar || '',
                    };
                } else {
                    normalized.requested_by_user = createUserPlaceholder(requestedById);
                }

                if (entry && entry.decision && typeof entry.decision === 'object') {
                    const decisionUser = entry.decision_user && typeof entry.decision_user === 'object'
                        ? entry.decision_user
                        : (entry.decision.user ? entry.decision.user : null);

                    normalized.decision = {
                        user_id: typeof entry.decision.user_id === 'number' ? entry.decision.user_id : (decisionUser && decisionUser.id) || 0,
                        comment: entry.decision.comment || '',
                        decided_at: entry.decision.decided_at || '',
                    };

                    if (decisionUser && typeof decisionUser === 'object') {
                        normalized.decision_user = {
                            id: decisionUser.id || normalized.decision.user_id,
                            name: decisionUser.name || createUserPlaceholder(normalized.decision.user_id).name,
                            avatar: decisionUser.avatar || '',
                        };
                    } else {
                        normalized.decision_user = normalized.decision.user_id
                            ? createUserPlaceholder(normalized.decision.user_id)
                            : null;
                    }
                } else {
                    normalized.decision = null;
                    normalized.decision_user = null;
                }

                normalized.status = (normalized.status || 'pending').toLowerCase();
                normalized.priority = normalizePriorityValue(entry && entry.priority);

                return normalized;
            });
        }

        function filterApprovalsByStatus(status) {
            const normalizedStatus = (status || '').toLowerCase();
            if (!normalizedStatus || normalizedStatus === 'all') {
                return approvalsState.entries;
            }

            return approvalsState.entries.filter((entry) => (entry.status || 'pending') === normalizedStatus);
        }

        function buildApprovalRow(entry) {
            const tokenName = entry.token && entry.token.name ? entry.token.name : '';
            const tokenContext = entry.token && entry.token.context ? entry.token.context : '';
            const status = (entry.status || 'pending').toLowerCase();
            const priorityValue = normalizePriorityValue(entry.priority);
            const priorityLabel = getPriorityLabel(priorityValue);
            const statusLabelMap = {
                pending: translate('approvalStatusPending', 'En attente'),
                approved: translate('approvalStatusApproved', 'Approuvé'),
                changes_requested: translate('approvalStatusChangesRequested', 'Changements demandés'),
            };
            const statusLabel = statusLabelMap[status] || translate('approvalStatusUnknown', 'Statut inconnu');
            const statusClass = `ssc-approval-badge--${status.replace(/[^a-z0-9_-]/g, '')}`;
            const requestedBy = entry.requested_by_user || { name: '', avatar: '' };
            const requestedAtIso = entry.requested_at || '';
            const requestedAt = formatDateTime(requestedAtIso);
            const comment = entry.comment || '';
            const decision = entry.decision || null;
            const decisionUser = entry.decision_user || null;
            const decisionComment = decision ? (decision.comment || '') : '';
            const decisionAt = decision ? formatDateTime(decision.decided_at || '') : '';
            const tokenMeta = getTokenMeta(entry);

            const row = $('<tr>')
                .attr('data-approval-id', entry.id || '')
                .attr('data-priority', priorityValue)
                .attr('data-requested-at', requestedAtIso)
                .attr('data-token-key', tokenMeta.key || '');

            const tokenCell = $('<td>');
            const tokenWrapper = $('<div>').addClass('ssc-approval-token');
            tokenWrapper.append($('<code>').text(tokenName));
            if (tokenContext) {
                tokenWrapper.append($('<span>').addClass('ssc-approval-context').text(tokenContext));
            }
            tokenCell.append(tokenWrapper);

            const priorityCell = $('<td>');
            const priorityWrapper = $('<div>').addClass('ssc-approval-priority-wrapper');
            priorityWrapper.append(
                $('<span>')
                    .addClass(`ssc-approval-priority ssc-approval-priority--${priorityValue.replace(/[^a-z0-9_-]/g, '')}`)
                    .text(priorityLabel)
            );
            priorityWrapper.append($('<p>').addClass('ssc-approval-sla').attr('hidden', 'hidden'));
            priorityCell.append(priorityWrapper);

            const statusCell = $('<td>').append(
                $('<span>').addClass(`ssc-approval-badge ${statusClass}`).text(statusLabel)
            );

            const requestedCell = $('<td>');
            const metaWrapper = $('<div>').addClass('ssc-approval-meta');
            if (requestedBy.avatar) {
                metaWrapper.append($('<img>', {
                    src: requestedBy.avatar,
                    alt: '',
                    class: 'ssc-approval-avatar',
                }));
            }
            const metaText = $('<div>');
            metaText.append($('<strong>').text(requestedBy.name || translate('approvalStatusUnknown', 'Statut inconnu')));
            if (requestedAt) {
                metaText.append($('<p>').addClass('description ssc-description--flush').text(
                    translate('approvalsRequestedAt', 'Envoyé le %s').replace('%s', requestedAt)
                ));
            }
            metaWrapper.append(metaText);
            requestedCell.append(metaWrapper);

            const commentCell = $('<td>');
            if (comment) {
                commentCell.append($('<p>').addClass('ssc-approval-comment').text(`“${comment}”`));
            } else {
                commentCell.append($('<p>').addClass('description ssc-description--flush').text(
                    translate('approvalsNoComment', 'Aucun commentaire fourni lors de la demande.')
                ));
            }

            if (decisionComment && decisionUser && decisionUser.name) {
                const decisionBase = translate('approvalsDecisionBy', 'Décision : %1$s le %2$s');
                const decisionLabel = decisionAt
                    ? sprintf(decisionBase, decisionUser.name, decisionAt)
                    : sprintf(decisionBase, decisionUser.name, '').replace(/\s+le\s*$/u, '').trim();
                const decisionMessage = decisionLabel ? `${decisionLabel} : “${decisionComment}”` : `“${decisionComment}”`;
                commentCell.append(
                    $('<p>').addClass('ssc-approval-decision').text(decisionMessage)
                );
            }

            const actionsCell = $('<td>');
            const actionsWrapper = $('<div>').addClass('ssc-approval-actions');
            actionsWrapper.append($('<button>', {
                type: 'button',
                class: 'button button-primary ssc-approval-open-modal',
                text: translate('approvalsOpenReview', 'Examiner'),
                'data-approval-id': entry.id || '',
            }));

            if (status === 'pending' && approvalsState.canReview) {
                const decisionsWrapper = $('<div>').addClass('ssc-approval-actions__decisions');
                decisionsWrapper.append($('<button>', {
                    type: 'button',
                    class: 'button button-secondary ssc-approval-approve',
                    text: translate('approvalsDecisionApprove', 'Approuver'),
                    'data-approval-id': entry.id || '',
                }));
                decisionsWrapper.append($('<button>', {
                    type: 'button',
                    class: 'button button-link-delete ssc-approval-request-changes',
                    text: translate('approvalsDecisionRequestChanges', 'Demander des changements'),
                    'data-approval-id': entry.id || '',
                }));
                actionsWrapper.append(decisionsWrapper);
            } else {
                actionsWrapper.append($('<p>').addClass('description ssc-description--flush ssc-approval-actions__info').text(
                    translate('approvalsNoActions', 'Aucune action disponible.')
                ));
            }

            actionsCell.append(actionsWrapper);

            row.append(tokenCell, priorityCell, statusCell, requestedCell, commentCell, actionsCell);
            return row;
        }

        function renderApprovals() {
            if (!approvalsTableBody.length) {
                return;
            }

            const entries = filterApprovalsByStatus(approvalsState.currentStatus);
            approvalsTableBody.empty();
            setVisibility(approvalsEmptyState, entries.length === 0);

            entries.forEach((entry) => {
                const row = buildApprovalRow(entry);
                approvalsTableBody.append(row);
                applySlaToRow(row, entry);
            });
        }

        function setApprovalsLoading(isLoading) {
            if (!approvalsRefreshButton.length) {
                return;
            }

            approvalsRefreshButton.prop('disabled', !!isLoading);
            approvalsRefreshButton.attr('aria-busy', isLoading ? 'true' : 'false');
        }

        function fetchApprovals(status) {
            if (!SSC || !SSC.rest || !SSC.rest.root) {
                renderApprovals();
                return;
            }

            const targetStatus = status || approvalsState.currentStatus || 'pending';
            approvalsState.currentStatus = targetStatus;
            setApprovalsLoading(true);

            $.ajax({
                url: SSC.rest.root + 'approvals',
                method: 'GET',
                data: { status: targetStatus },
                beforeSend: (xhr) => xhr.setRequestHeader('X-WP-Nonce', SSC.rest.nonce),
            }).done((response) => {
                if (response && Array.isArray(response.approvals)) {
                    approvalsState.entries = normalizeApprovals(response.approvals);
                }
                renderApprovals();
            }).fail(() => {
                window.sscToast && window.sscToast(translate('approvalsFetchError', 'Impossible de récupérer les demandes d’approbation.'));
            }).always(() => {
                setApprovalsLoading(false);
            });
        }

        function handleApprovalDecision(event, decision) {
            event.preventDefault();
            const button = $(event.currentTarget);
            const approvalId = button.attr('data-approval-id');

            if (!approvalId) {
                return;
            }

            if (!SSC || !SSC.rest || !SSC.rest.root) {
                window.sscToast && window.sscToast(translate('approvalsDecisionError', 'Impossible d’enregistrer la décision.'));
                return;
            }

            if (decision === 'approve') {
                const confirmed = window.confirm(translate('approvalsDecisionConfirmApprove', 'Confirmez-vous l’approbation de ce token ?'));
                if (!confirmed) {
                    return;
                }
            }

            let comment = '';
            if (decision === 'changes_requested') {
                const confirmed = window.confirm(translate('approvalsDecisionConfirmChanges', 'Confirmez-vous la demande de changements ? Un commentaire est requis.'));
                if (!confirmed) {
                    return;
                }
                comment = window.prompt(translate('approvalsDecisionPromptComment', 'Précisez un commentaire pour guider l’auteur :')) || '';
                if (!comment.trim()) {
                    window.sscToast && window.sscToast(translate('approvalsDecisionCommentRequired', 'Un commentaire est obligatoire pour demander des changements.'));
                    return;
                }
            }

            button.prop('disabled', true).attr('aria-busy', 'true');

            $.ajax({
                url: `${SSC.rest.root}approvals/${encodeURIComponent(approvalId)}`,
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({ decision, comment }),
                beforeSend: (xhr) => xhr.setRequestHeader('X-WP-Nonce', SSC.rest.nonce),
            }).done(() => {
                window.sscToast && window.sscToast(translate('approvalsDecisionSuccess', 'Décision enregistrée.'));
                fetchApprovals(approvalsState.currentStatus);
            }).fail(() => {
                window.sscToast && window.sscToast(translate('approvalsDecisionError', 'Impossible d’enregistrer la décision.'));
            }).always(() => {
                button.prop('disabled', false).attr('aria-busy', 'false');
            });
        }

        function resetApprovalModal() {
            approvalsModalBadges.empty();
            approvalsModalMeta.empty();
            approvalsModalValue.text('');
            approvalsModalCopy.prop('disabled', true);
            approvalsModalChangelog.text('');
            approvalsModalComponents.empty();
            approvalsModalComments.empty();
            approvalsModalTimeline.empty();
            approvalsModalTimelineEmpty.attr('hidden', 'hidden');
            approvalsModalTimelineError.attr('hidden', 'hidden');
            approvalsModalLoading.attr('hidden', 'hidden');
            approvalsModalEyebrow.text('');
            approvalsModalTitle.text('');
        }

        function appendMetaItem(label, value) {
            if (!value || !approvalsModalMeta.length) {
                return;
            }

            const item = $('<div>').addClass('ssc-approval-review__meta-item');
            item.append($('<dt>').text(label));
            item.append($('<dd>').text(value));
            approvalsModalMeta.append(item);
        }

        function renderModalComponents(components) {
            approvalsModalComponents.empty();

            const list = Array.isArray(components) ? components : [];

            if (!list.length) {
                approvalsModalComponents.append(
                    $('<li>').addClass('is-empty').text(
                        translate('approvalsReviewLinkedComponentsEmpty', 'Aucun composant référencé.')
                    )
                );
                return;
            }

            list.forEach((component) => {
                approvalsModalComponents.append(
                    $('<li>').text(component)
                );
            });
        }

        function renderModalComments(entry) {
            approvalsModalComments.empty();

            const requestedBy = entry.requested_by_user || { name: '', avatar: '' };
            const requestedAt = formatDateTime(entry.requested_at || '') || (entry.requested_at || '');
            const comment = entry.comment || '';

            const requesterBlock = $('<article>').addClass('ssc-approval-review__comment');
            requesterBlock.append($('<h4>').text(translate('approvalsReviewRequesterLabel', 'Demande initiale')));
            const requesterMetaParts = [];
            if (requestedBy.name) {
                requesterMetaParts.push(requestedBy.name);
            }
            if (requestedAt) {
                requesterMetaParts.push(requestedAt);
            }
            if (requesterMetaParts.length) {
                requesterBlock.append(
                    $('<p>').addClass('ssc-approval-review__comment-meta').text(requesterMetaParts.join(' · '))
                );
            }
            requesterBlock.append(
                $('<p>').addClass('ssc-approval-review__comment-body').text(
                    comment ? `“${comment}”` : translate('approvalsNoComment', 'Aucun commentaire fourni lors de la demande.')
                )
            );
            approvalsModalComments.append(requesterBlock);

            const decision = entry.decision || null;
            const decisionUser = entry.decision_user || null;
            const decisionComment = decision ? (decision.comment || '') : '';
            const decisionAt = decision ? (formatDateTime(decision.decided_at || '') || decision.decided_at || '') : '';

            const decisionBlock = $('<article>').addClass('ssc-approval-review__comment');
            decisionBlock.append($('<h4>').text(translate('approvalsReviewDecisionLabel', 'Dernière décision')));

            if (decisionUser && decisionUser.name) {
                const decisionMetaParts = [decisionUser.name];
                if (decisionAt) {
                    decisionMetaParts.push(decisionAt);
                }
                decisionBlock.append(
                    $('<p>').addClass('ssc-approval-review__comment-meta').text(decisionMetaParts.join(' · '))
                );
                decisionBlock.append(
                    $('<p>').addClass('ssc-approval-review__comment-body').text(
                        decisionComment
                            ? `“${decisionComment}”`
                            : translate('approvalsNoComment', 'Aucun commentaire fourni lors de la demande.')
                    )
                );
            } else {
                decisionBlock.append(
                    $('<p>').addClass('ssc-approval-review__comment-meta').text(
                        translate('approvalsReviewNoDecision', 'Aucune décision enregistrée pour le moment.')
                    )
                );
            }

            approvalsModalComments.append(decisionBlock);
        }

        function loadApprovalTimeline(tokenKey, approvalId) {
            approvalsModalTimeline.empty();
            approvalsModalTimelineEmpty.attr('hidden', 'hidden');
            approvalsModalTimelineError.attr('hidden', 'hidden');

            if (!tokenKey) {
                approvalsModalTimelineEmpty.text(
                    translate('approvalsReviewMissingToken', 'Le token associé est introuvable ou a été supprimé.')
                ).removeAttr('hidden');
                return;
            }

            if (!SSC || !SSC.rest || !SSC.rest.root) {
                approvalsModalTimelineError.removeAttr('hidden').text(
                    translate('approvalsReviewTimelineError', 'Impossible de charger l’historique.')
                );
                return;
            }

            approvalsModalLoading.removeAttr('hidden');

            $.ajax({
                url: SSC.rest.root + 'activity-log',
                method: 'GET',
                data: {
                    entity_type: 'token',
                    entity_id: tokenKey,
                    per_page: 15,
                },
                beforeSend: (xhr) => xhr.setRequestHeader('X-WP-Nonce', SSC.rest.nonce),
            }).done((response) => {
                if (!approvalsModalState.open || approvalsModalState.currentId !== approvalId) {
                    return;
                }

                const entries = response && Array.isArray(response.entries) ? response.entries : [];

                if (!entries.length) {
                    approvalsModalTimelineEmpty.removeAttr('hidden');
                    return;
                }

                entries.forEach((activityEntry) => {
                    const createdAt = formatDateTime(activityEntry.created_at || '') || (activityEntry.created_at || '');
                    const eventLabel = getTimelineEventLabel(activityEntry.event || '');
                    const author = activityEntry.created_by && activityEntry.created_by.name
                        ? activityEntry.created_by.name
                        : translate('activitySystemUser', 'Système');
                    const details = activityEntry.details && typeof activityEntry.details === 'object'
                        ? activityEntry.details
                        : {};
                    const detailParts = [];

                    if (details.comment) {
                        detailParts.push(`“${details.comment}”`);
                    }
                    if (details.priority) {
                        detailParts.push(`${translate('approvalPriorityColumn', 'Priorité')} : ${getPriorityLabel(details.priority)}`);
                    }
                    if (details.status) {
                        const statusMeta = getStatusMeta(details.status);
                        detailParts.push(`${translate('approvalsReviewStatusLabel', 'Statut')} : ${statusMeta.label || details.status}`);
                    }
                    if (details.version) {
                        detailParts.push(`${translate('approvalsReviewVersionLabel', 'Version')} : ${details.version}`);
                    }

                    const item = $('<li>').addClass('ssc-approval-timeline__item');
                    item.append($('<time>').addClass('ssc-approval-timeline__time').attr('datetime', activityEntry.created_at || '').text(createdAt));

                    const body = $('<div>').addClass('ssc-approval-timeline__body');
                    body.append($('<strong>').text(eventLabel));
                    body.append($('<p>').addClass('ssc-approval-timeline__meta').text(author));

                    if (detailParts.length) {
                        body.append($('<p>').addClass('ssc-approval-timeline__details').text(detailParts.join(' · ')));
                    }

                    item.append(body);
                    approvalsModalTimeline.append(item);
                });
            }).fail(() => {
                if (!approvalsModalState.open || approvalsModalState.currentId !== approvalId) {
                    return;
                }

                approvalsModalTimelineError.removeAttr('hidden').text(
                    translate('approvalsReviewTimelineError', 'Impossible de charger l’historique.')
                );
            }).always(() => {
                if (!approvalsModalState.open || approvalsModalState.currentId !== approvalId) {
                    return;
                }

                approvalsModalLoading.attr('hidden', 'hidden');
            });
        }

        function populateApprovalModal(entry) {
            resetApprovalModal();

            const tokenName = entry.token && entry.token.name ? entry.token.name : '';
            const tokenContext = entry.token && entry.token.context ? entry.token.context : '';
            const requestedAtIso = entry.requested_at || '';
            const requestedAt = formatDateTime(requestedAtIso) || requestedAtIso;
            const tokenMeta = getTokenMeta(entry);
            approvalsModalState.currentId = entry.id || '';
            approvalsModalState.tokenKey = tokenMeta.key || '';

            if (requestedAt) {
                approvalsModalEyebrow.text(
                    translate('approvalsReviewRequestedAt', 'Demande envoyée le %s').replace('%s', requestedAt)
                );
            } else {
                approvalsModalEyebrow.text(translate('approvalsReviewTitle', 'Revue de token'));
            }

            const titleParts = [];
            if (tokenName) {
                titleParts.push(tokenName);
            }
            if (tokenContext) {
                titleParts.push(tokenContext);
            }
            approvalsModalTitle.text(titleParts.length ? titleParts.join(' · ') : translate('approvalsReviewTitle', 'Revue de token'));

            const statusMeta = tokenMeta.meta && tokenMeta.meta.status ? tokenMeta.meta.status : null;
            if (tokenMeta.meta) {
                const statusLabel = statusMeta && statusMeta.label
                    ? statusMeta.label
                    : getStatusMeta(entry.status).label || translate('approvalStatusUnknown', 'Statut inconnu');
                approvalsModalBadges.append(
                    $('<span>').addClass('ssc-approval-review__badge ssc-approval-review__badge--status').text(statusLabel)
                );
            } else {
                approvalsModalBadges.append(
                    $('<span>').addClass('ssc-approval-review__notice').text(
                        translate('approvalsReviewMissingToken', 'Le token associé est introuvable ou a été supprimé.')
                    )
                );
            }

            const priorityValue = normalizePriorityValue(entry.priority);
            approvalsModalBadges.append(
                $('<span>')
                    .addClass(`ssc-approval-review__badge ssc-approval-review__badge--priority ssc-approval-priority--${priorityValue.replace(/[^a-z0-9_-]/g, '')}`)
                    .text(getPriorityLabel(priorityValue))
            );

            const owner = tokenMeta.meta && tokenMeta.meta.owner ? tokenMeta.meta.owner : null;
            if (owner && owner.name) {
                appendMetaItem(translate('approvalsReviewOwnerLabel', 'Référent'), owner.name);
            }

            if (tokenMeta.meta && tokenMeta.meta.version) {
                appendMetaItem(translate('approvalsReviewVersionLabel', 'Version'), tokenMeta.meta.version);
            }

            const contextDisplay = tokenContext || '—';
            appendMetaItem(translate('approvalsReviewContextLabel', 'Contexte'), contextDisplay);

            if (tokenMeta.meta && tokenMeta.meta.type) {
                appendMetaItem(translate('approvalsReviewTypeLabel', 'Type'), tokenMeta.meta.type);
            }

            const slaDisplay = buildSlaDisplay(entry);
            if (slaDisplay && slaDisplay.text) {
                appendMetaItem(translate('approvalsReviewSlaLabel', 'SLA'), slaDisplay.text);
            }

            const tokenValue = tokenMeta.meta && tokenMeta.meta.value ? tokenMeta.meta.value : '';
            if (tokenValue) {
                approvalsModalValue.text(tokenValue);
                approvalsModalCopy.prop('disabled', false);
            } else {
                approvalsModalValue.text(translate('approvalsReviewValueUnavailable', 'Valeur indisponible.'));
                approvalsModalCopy.prop('disabled', true);
            }

            const changelog = tokenMeta.meta && tokenMeta.meta.changelog ? tokenMeta.meta.changelog : '';
            approvalsModalChangelog.text(
                changelog ? changelog : translate('approvalsReviewChangelogEmpty', 'Aucune note pour ce token.')
            );

            renderModalComponents(tokenMeta.meta && tokenMeta.meta.linked_components ? tokenMeta.meta.linked_components : []);
            renderModalComments(entry);

            loadApprovalTimeline(tokenMeta.key || '', approvalsModalState.currentId);
        }

        function openApprovalModal(event) {
            let entry = event;
            let triggerElement = null;

            if (event && event.preventDefault) {
                event.preventDefault();
                const button = $(event.currentTarget);
                triggerElement = button.length ? button[0] : null;
                const approvalId = button.attr('data-approval-id');
                entry = approvalsState.entries.find((item) => String(item.id) === String(approvalId));
            }

            if (!entry) {
                window.sscToast && window.sscToast(translate('approvalsFetchError', 'Impossible de récupérer les demandes d’approbation.'));
                return;
            }

            approvalsModalState.restoreFocus = triggerElement || approvalsModalState.restoreFocus;
            approvalsModalState.open = true;
            approvalsModalElement.removeAttr('hidden').addClass('is-visible');
            $('body').addClass('ssc-modal-open');

            populateApprovalModal(entry);

            setTimeout(() => {
                if (!approvalsModalState.open) {
                    return;
                }

                if (approvalsModalDialog.length) {
                    approvalsModalDialog.attr('tabindex', '-1').trigger('focus');
                } else if (approvalsModalCloseButtons.length) {
                    approvalsModalCloseButtons.first().trigger('focus');
                }
            }, 20);
        }

        function closeApprovalModal() {
            if (!approvalsModalState.open) {
                return;
            }

            approvalsModalState.open = false;
            approvalsModalElement.attr('hidden', 'hidden').removeClass('is-visible');
            $('body').removeClass('ssc-modal-open');
            approvalsModalState.currentId = null;
            approvalsModalState.tokenKey = null;
            resetApprovalModal();

            if (approvalsModalState.restoreFocus && typeof approvalsModalState.restoreFocus.focus === 'function') {
                approvalsModalState.restoreFocus.focus();
            } else if (approvalsModalState.restoreFocus) {
                try {
                    approvalsModalState.restoreFocus.focus();
                } catch (error) {
                    // Ignore focus restoration errors.
                }
            }

            approvalsModalState.restoreFocus = null;
        }

        if (approvalsFilter.length) {
            approvalsFilter.on('change', function() {
                approvalsState.currentStatus = approvalsFilter.val() || 'pending';
                fetchApprovals(approvalsState.currentStatus);
            });
        }

        if (approvalsRefreshButton.length) {
            approvalsRefreshButton.on('click', () => {
                fetchApprovals(approvalsState.currentStatus);
            });
        }

        approvalsTableBody.on('click', '.ssc-approval-approve', (event) => handleApprovalDecision(event, 'approve'));
        approvalsTableBody.on('click', '.ssc-approval-request-changes', (event) => handleApprovalDecision(event, 'changes_requested'));
        approvalsTableBody.on('click', '.ssc-approval-open-modal', (event) => openApprovalModal(event));

        approvalsModalCloseButtons.on('click', (event) => {
            event.preventDefault();
            closeApprovalModal();
        });

        approvalsModalCopy.on('click', (event) => {
            event.preventDefault();
            const value = approvalsModalValue.text();
            if (!value || !value.trim()) {
                window.sscToast && window.sscToast(translate('approvalsReviewCopyError', 'Impossible de copier la valeur.'));
                return;
            }

            copyToClipboard(value).then(() => {
                window.sscToast && window.sscToast(translate('approvalsReviewCopySuccess', 'Valeur copiée dans le presse-papiers.'));
            }).catch(() => {
                window.sscToast && window.sscToast(translate('approvalsReviewCopyError', 'Impossible de copier la valeur.'));
            });
        });

        $(document).on('keydown', (event) => {
            if (event.key === 'Escape' && approvalsModalState.open) {
                event.preventDefault();
                closeApprovalModal();
            }
        });

        $(document).on('focusin', (event) => {
            if (!approvalsModalState.open || !approvalsModalElement.length) {
                return;
            }

            if (!approvalsModalElement[0].contains(event.target)) {
                if (approvalsModalDialog.length) {
                    approvalsModalDialog.attr('tabindex', '-1').trigger('focus');
                }
            }
        });

        renderApprovals();

        const activityTableBody = $('#ssc-activity-log-table tbody');
        const activityEmptyState = $('#ssc-activity-empty');
        const activitySummary = $('#ssc-activity-summary');
        const activityPrev = $('#ssc-activity-prev');
        const activityNext = $('#ssc-activity-next');
        const activityIndicator = $('#ssc-activity-page-indicator');
        const activityEventField = $('#ssc-activity-event');
        const activityEntityField = $('#ssc-activity-entity');
        const activityResourceField = $('#ssc-activity-resource');
        const activityWindowField = $('#ssc-activity-window');
        const activityPerPageField = $('#ssc-activity-per-page');
        const activityApplyButton = $('#ssc-activity-apply');
        const activityResetButton = $('#ssc-activity-reset');
        const activityExportJson = $('#ssc-activity-export-json');
        const activityExportCsv = $('#ssc-activity-export-csv');
        const activityInitialData = parseJsonFromScript('#ssc-activity-log-data');

        const activityLabels = {
            date: translate('activityColumnDate', 'Date'),
            event: translate('activityColumnEvent', 'Événement'),
            entity: translate('activityColumnEntity', 'Entité'),
            resource: translate('activityColumnResource', 'Ressource'),
            author: translate('activityColumnAuthor', 'Auteur'),
            details: translate('activityColumnDetails', 'Détails'),
            system: translate('activitySystemUser', 'Système'),
        };

        const activityState = {
            entries: Array.isArray(activityInitialData.entries) ? activityInitialData.entries : [],
            pagination: activityInitialData.pagination || { total: 0, total_pages: 1, page: 1 },
            filters: {
                event: activityInitialData.filters ? activityInitialData.filters.event || '' : '',
                entity_type: activityInitialData.filters ? activityInitialData.filters.entity_type || '' : '',
                entity_id: activityInitialData.filters ? activityInitialData.filters.entity_id || '' : '',
                window: activityInitialData.filters ? activityInitialData.filters.window || '' : '',
                per_page: activityInitialData.filters ? activityInitialData.filters.per_page || 20 : 20,
            },
        };

        function updateActivityFormInputs() {
            if (activityEventField.length) {
                activityEventField.val(activityState.filters.event || '');
            }
            if (activityEntityField.length) {
                activityEntityField.val(activityState.filters.entity_type || '');
            }
            if (activityResourceField.length) {
                activityResourceField.val(activityState.filters.entity_id || '');
            }
            if (activityWindowField.length) {
                activityWindowField.val(activityState.filters.window || '');
            }
            if (activityPerPageField.length) {
                activityPerPageField.val(String(activityState.filters.per_page || 20));
            }
        }

        function renderActivityRows() {
            if (!activityTableBody.length) {
                return;
            }

            activityTableBody.empty();

            activityState.entries.forEach((entry) => {
                const createdAt = entry.created_at || '';
                const formattedDate = formatDateTime(createdAt) || createdAt;
                const eventName = entry.event || '';
                const entityType = entry.entity_type || '';
                const entityId = entry.entity_id || '';
                const author = entry.created_by && entry.created_by.name ? entry.created_by.name : activityLabels.system;
                const details = entry.details || {};

                const row = $('<tr>').attr('data-entry-id', entry.id || '');
                row.append($('<td>').attr('data-label', activityLabels.date).append(
                    $('<time>').attr('datetime', createdAt).text(formattedDate)
                ));
                row.append($('<td>').attr('data-label', activityLabels.event).append(
                    $('<code>').text(eventName)
                ));

                const entityCell = $('<td>').attr('data-label', activityLabels.entity);
                entityCell.append($('<span>').addClass('ssc-activity-entity').text(entityType));
                if (entityId) {
                    const resourceRow = $('<p>').addClass('description ssc-description--flush');
                    resourceRow.append(
                        $('<span>').addClass('ssc-activity-resource-label').text(`${activityLabels.resource}: `)
                    );
                    resourceRow.append(
                        $('<span>').addClass('ssc-activity-resource').text(entityId)
                    );
                    entityCell.append(resourceRow);
                }
                row.append(entityCell);

                row.append($('<td>').attr('data-label', activityLabels.author).text(author));

                row.append(
                    $('<td>').attr('data-label', activityLabels.details).append(
                        $('<code>').text(JSON.stringify(details))
                    )
                );

                activityTableBody.append(row);
            });

            setVisibility(activityEmptyState, activityState.entries.length === 0);
        }

        function updateActivitySummary() {
            if (!activitySummary.length) {
                return;
            }

            const total = activityState.pagination.total || 0;
            const page = activityState.pagination.page || 1;
            const totalPages = activityState.pagination.total_pages || 1;
            activitySummary.text(
                sprintf(translate('activityPaginationSummary', 'Page %1$s sur %2$s — %3$s entrée(s)'), page, totalPages, total)
            );
        }

        function updateActivityPaginationControls() {
            if (!activityPrev.length || !activityNext.length || !activityIndicator.length) {
                return;
            }

            const page = activityState.pagination.page || 1;
            const totalPages = activityState.pagination.total_pages || 1;

            activityPrev.prop('disabled', page <= 1);
            activityNext.prop('disabled', page >= totalPages);
            activityIndicator.text(`${page} / ${totalPages}`);
        }

        function setActivityLoading(isLoading) {
            if (activityApplyButton.length) {
                activityApplyButton.prop('disabled', !!isLoading);
                activityApplyButton.attr('aria-busy', isLoading ? 'true' : 'false');
            }
            if (activityExportJson.length) {
                activityExportJson.prop('disabled', !!isLoading);
            }
            if (activityExportCsv.length) {
                activityExportCsv.prop('disabled', !!isLoading);
            }
            if (activityPrev.length) {
                activityPrev.prop('disabled', !!isLoading || (activityState.pagination.page || 1) <= 1);
            }
            if (activityNext.length) {
                const totalPages = activityState.pagination.total_pages || 1;
                activityNext.prop('disabled', !!isLoading || (activityState.pagination.page || 1) >= totalPages);
            }
            if (activitySummary.length) {
                if (isLoading) {
                    activitySummary.text(translate('activityLoading', 'Chargement du journal…'));
                }
            }
        }

        function fetchActivityLog(options = {}) {
            if (!SSC || !SSC.rest || !SSC.rest.root) {
                renderActivityRows();
                updateActivitySummary();
                updateActivityPaginationControls();
                return;
            }

            const nextFilters = {
                ...activityState.filters,
                ...(options.filters || {}),
            };

            const nextPage = options.page || activityState.pagination.page || 1;
            const perPage = parseInt(nextFilters.per_page, 10) || 20;

            setActivityLoading(true);

            $.ajax({
                url: SSC.rest.root + 'activity-log',
                method: 'GET',
                data: {
                    page: nextPage,
                    per_page: perPage,
                    event: nextFilters.event || undefined,
                    entity_type: nextFilters.entity_type || undefined,
                    entity_id: nextFilters.entity_id || undefined,
                    window: nextFilters.window || undefined,
                },
                beforeSend: (xhr) => xhr.setRequestHeader('X-WP-Nonce', SSC.rest.nonce),
            }).done((response) => {
                if (response) {
                    activityState.entries = Array.isArray(response.entries) ? response.entries : [];
                    activityState.pagination = response.pagination || { total: 0, total_pages: 1, page: nextPage };
                    activityState.filters = {
                        event: nextFilters.event || '',
                        entity_type: nextFilters.entity_type || '',
                        entity_id: nextFilters.entity_id || '',
                        window: nextFilters.window || '',
                        per_page: perPage,
                    };
                }

                renderActivityRows();
                updateActivitySummary();
                updateActivityPaginationControls();
            }).fail(() => {
                window.sscToast && window.sscToast(translate('activityFetchError', 'Impossible de charger le journal d’activité.'));
            }).always(() => {
                setActivityLoading(false);
            });
        }

        function collectFiltersFromForm() {
            return {
                event: activityEventField.length ? (activityEventField.val() || '').toString().trim() : '',
                entity_type: activityEntityField.length ? (activityEntityField.val() || '').toString().trim() : '',
                entity_id: activityResourceField.length ? (activityResourceField.val() || '').toString().trim() : '',
                window: activityWindowField.length ? (activityWindowField.val() || '').toString().trim() : '',
                per_page: activityPerPageField.length ? parseInt(activityPerPageField.val(), 10) || 20 : 20,
            };
        }

        if (activityApplyButton.length) {
            activityApplyButton.on('click', () => {
                fetchActivityLog({ page: 1, filters: collectFiltersFromForm() });
            });
        }

        if (activityResetButton.length) {
            activityResetButton.on('click', () => {
                activityEventField.val('');
                activityEntityField.val('');
                activityResourceField.val('');
                activityWindowField.val('');
                activityPerPageField.val('20');
                window.sscToast && window.sscToast(translate('activityFiltersCleared', 'Filtres réinitialisés.'));
                fetchActivityLog({ page: 1, filters: { event: '', entity_type: '', entity_id: '', window: '', per_page: 20 } });
            });
        }

        if (activityPrev.length) {
            activityPrev.on('click', () => {
                const currentPage = activityState.pagination.page || 1;
                if (currentPage > 1) {
                    fetchActivityLog({ page: currentPage - 1 });
                }
            });
        }

        if (activityNext.length) {
            activityNext.on('click', () => {
                const currentPage = activityState.pagination.page || 1;
                const totalPages = activityState.pagination.total_pages || 1;
                if (currentPage < totalPages) {
                    fetchActivityLog({ page: currentPage + 1 });
                }
            });
        }

        function exportActivityLog(format) {
            if (!SSC || !SSC.rest || !SSC.rest.root) {
                window.sscToast && window.sscToast(translate('activityExportError', 'Impossible d’exporter le journal.'));
                return;
            }

            const params = {
                format,
                event: activityState.filters.event || undefined,
                entity_type: activityState.filters.entity_type || undefined,
                window: activityState.filters.window || undefined,
            };

            setActivityLoading(true);
            window.sscToast && window.sscToast(translate('activityExportPreparing', 'Préparation de l’export…'));

            $.ajax({
                url: SSC.rest.root + 'activity-log/export',
                method: 'GET',
                data: params,
                dataType: format === 'csv' ? 'text' : 'json',
                beforeSend: (xhr) => xhr.setRequestHeader('X-WP-Nonce', SSC.rest.nonce),
            }).done((response, statusText, xhr) => {
                if (format === 'csv') {
                    const disposition = xhr.getResponseHeader('content-disposition') || '';
                    const fallbackName = 'ssc-activity-log.csv';
                    let downloadName = fallbackName;
                    const match = disposition.match(/filename="?([^";]+)"?/i);
                    if (match && match[1]) {
                        downloadName = match[1];
                    }
                    triggerDownload(downloadName, response, 'text/csv');
                } else {
                    const payload = typeof response === 'string' ? response : JSON.stringify(response, null, 2);
                    triggerDownload('ssc-activity-log.json', payload, 'application/json');
                }
                window.sscToast && window.sscToast(translate('activityExportReady', 'Export prêt.'));
            }).fail(() => {
                window.sscToast && window.sscToast(translate('activityExportError', 'Impossible d’exporter le journal.'));
            }).always(() => {
                setActivityLoading(false);
            });
        }

        if (activityExportJson.length) {
            activityExportJson.on('click', () => exportActivityLog('json'));
        }

        if (activityExportCsv.length) {
            activityExportCsv.on('click', () => exportActivityLog('csv'));
        }

        updateActivityFormInputs();
        renderActivityRows();
        updateActivitySummary();
        updateActivityPaginationControls();
    });
})(jQuery);
