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

        const resultPane = $('#ssc-health-json');
        if (healthRunButton.length && resultPane.length) {
            healthRunButton.on('click', function() {
                const btn = $(this);
                btn.text(translate('healthCheckCheckingLabel', 'Vérification en cours…')).prop('disabled', true);
                resultPane.text(translate('healthCheckRunningMessage', 'Analyse du système…'));

                $.ajax({
                    url: SSC.rest.root + 'health',
                    method: 'GET',
                    data: { _wpnonce: SSC.rest.nonce },
                    beforeSend: x => x.setRequestHeader('X-WP-Nonce', SSC.rest.nonce)
                }).done(response => {
                    resultPane.text(JSON.stringify(response, null, 2));
                    window.sscToast && window.sscToast(translate('healthCheckSuccessMessage', 'Health check terminé.'));
                }).fail(err => {
                    resultPane.text(translate('healthCheckErrorMessage', 'Une erreur est survenue.'));
                    console.error('Health Check Error:', err);
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
