(function($) {
    const DEFAULT_I18N = {
        commandPaletteTitle: 'Supersede CSS command palette',
        commandPaletteSearchPlaceholder: 'Navigate or run an action…',
        commandPaletteSearchLabel: 'Command palette search',
        commandPaletteEmptyState: 'Aucun résultat',
        commandPaletteResultsAnnouncement: '%d result(s) available.',
        mobileMenuShowLabel: 'Afficher le menu',
        mobileMenuHideLabel: 'Masquer le menu',
        mobileMenuToggleSrLabel: 'Menu',
        clipboardSuccess: 'Texte copié !',
        clipboardError: 'Impossible de copier le texte.',
        toastHistoryLabel: 'Supersede CSS notifications history',
        toastHistoryEntry: 'Notification recorded at %1$s: %2$s',
        toastDismissLabel: 'Dismiss notification',
    };

    const getSscI18n = (() => {
        let cache = null;

        const pickString = (value, fallback = '') => (
            typeof value === 'string' && value.trim() !== ''
                ? value
                : fallback
        );

        const resolveAnnouncement = (rawValue, fallback) => {
            const fallbackTemplate = pickString(fallback, DEFAULT_I18N.commandPaletteResultsAnnouncement);

            if (typeof rawValue === 'function') {
                return {
                    template: fallbackTemplate,
                    formatter: rawValue,
                };
            }

            if (rawValue && typeof rawValue === 'object') {
                const template = pickString(rawValue.template, fallbackTemplate);
                const customFormatter = typeof rawValue.formatter === 'function'
                    ? rawValue.formatter
                    : null;

                return {
                    template,
                    formatter: customFormatter || ((count) => {
                        if (template.includes('%d')) {
                            return template.replace(/%d/g, `${count}`);
                        }
                        return `${count} ${template}`;
                    }),
                };
            }

            const template = pickString(rawValue, fallbackTemplate);
            return {
                template,
                formatter: (count) => {
                    if (template.includes('%d')) {
                        return template.replace(/%d/g, `${count}`);
                    }
                    return `${count} ${template}`;
                },
            };
        };

        return () => {
            if (cache) {
                return cache;
            }

            const rawI18n = (typeof window !== 'undefined' && window?.SSC?.i18n && typeof window.SSC.i18n === 'object')
                ? window.SSC.i18n
                : {};

            const merged = { ...DEFAULT_I18N };

            Object.keys(DEFAULT_I18N).forEach((key) => {
                if (key === 'commandPaletteResultsAnnouncement') {
                    return;
                }
                const rawValue = rawI18n[key];
                if (typeof rawValue === 'string' && rawValue.trim() !== '') {
                    merged[key] = rawValue;
                }
            });

            const announcement = resolveAnnouncement(
                rawI18n.commandPaletteResultsAnnouncement,
                merged.commandPaletteResultsAnnouncement
            );

            merged.commandPaletteResultsAnnouncement = announcement.template;
            merged.getCommandPaletteResultsAnnouncement = announcement.formatter;

            cache = merged;
            return cache;
        };
    })();

    // --- Toast Notifications ---
    const TOAST_DEFAULT_TIMEOUT = 6000;
    const TOAST_DEFAULT_POLITENESS = 'polite';
    const TOAST_HISTORY_LIMIT = 10;

    const getToastContainer = (politeness = TOAST_DEFAULT_POLITENESS) => {
        let container = $('#ssc-toasts');

        if (!container.length) {
            container = $('<div id="ssc-toasts" role="status" aria-live="polite" aria-atomic="false"></div>');
            container.appendTo('body');
        }

        const normalizedPoliteness = politeness === 'assertive' ? 'assertive' : TOAST_DEFAULT_POLITENESS;
        if (container.attr('aria-live') !== normalizedPoliteness) {
            container.attr('aria-live', normalizedPoliteness);
        }

        const i18n = getSscI18n();
        const historyLabel = (typeof i18n.toastHistoryLabel === 'string' && i18n.toastHistoryLabel.trim() !== '')
            ? i18n.toastHistoryLabel
            : DEFAULT_I18N.toastHistoryLabel;

        let log = $('#ssc-toast-log');
        if (!log.length) {
            log = $('<div id="ssc-toast-log" class="screen-reader-text" role="log" aria-live="off"></div>')
                .attr('aria-label', historyLabel)
                .insertAfter(container);
        } else if (log.attr('aria-label') !== historyLabel) {
            log.attr('aria-label', historyLabel);
        }

        const describedby = (container.attr('aria-describedby') || '').split(/\s+/).filter(Boolean);
        if (!describedby.includes('ssc-toast-log')) {
            describedby.push('ssc-toast-log');
            container.attr('aria-describedby', describedby.join(' '));
        }

        return { container, log, i18n };
    };

    window.sscToast = function(message, {
        politeness = TOAST_DEFAULT_POLITENESS,
        role,
        timeout = TOAST_DEFAULT_TIMEOUT
    } = {}) {
        const { container, log, i18n } = getToastContainer(politeness);
        const toastRole = role || (politeness === 'assertive' ? 'alert' : 'status');
        const toast = $('<div class="ssc-toast"></div>')
            .attr('role', toastRole)
            .attr('tabindex', '0');

        const messageEl = $('<span class="ssc-toast__message"></span>').text(message);

        const dismissLabel = (typeof i18n.toastDismissLabel === 'string' && i18n.toastDismissLabel.trim() !== '')
            ? i18n.toastDismissLabel
            : DEFAULT_I18N.toastDismissLabel;

        const dismissButton = $('<button type="button" class="ssc-toast__dismiss"></button>')
            .attr('aria-label', dismissLabel)
            .append($('<span aria-hidden="true"></span>').text('×'));

        toast.append(messageEl, dismissButton);

        container.append(toast);

        const normalizedTimeout = (typeof timeout === 'number' && isFinite(timeout) && timeout >= 0)
            ? timeout
            : TOAST_DEFAULT_TIMEOUT;

        let removalTimeoutId = null;

        const clearRemoval = () => {
            if (removalTimeoutId) {
                window.clearTimeout(removalTimeoutId);
                removalTimeoutId = null;
            }
        };

        const removeToast = () => {
            clearRemoval();
            toast.remove();

            if (!container.children().length) {
                container.attr('aria-live', TOAST_DEFAULT_POLITENESS);
            }
        };

        dismissButton.on('click', (event) => {
            event.preventDefault();
            removeToast();
        });

        const scheduleRemoval = () => {
            if (normalizedTimeout <= 0) {
                return;
            }

            clearRemoval();

            removalTimeoutId = window.setTimeout(() => {
                if (toast.is(':focus') || toast.is(':hover')) {
                    scheduleRemoval();
                    return;
                }

                removeToast();
            }, normalizedTimeout);
        };

        const pauseRemoval = () => {
            if (normalizedTimeout > 0) {
                clearRemoval();
            }
        };

        const resumeRemoval = () => {
            if (normalizedTimeout > 0) {
                scheduleRemoval();
            }
        };

        toast.on('focusin mouseenter', pauseRemoval);
        toast.on('focusout mouseleave', resumeRemoval);
        toast.on('keydown', (event) => {
            if (event && (event.key === 'Escape' || event.key === 'Esc')) {
                event.preventDefault();
                removeToast();
            }
        });

        scheduleRemoval();

        const historyTemplate = (typeof i18n.toastHistoryEntry === 'string' && i18n.toastHistoryEntry.trim() !== '')
            ? i18n.toastHistoryEntry
            : DEFAULT_I18N.toastHistoryEntry;

        if (log && log.length) {
            const timestamp = new Date();
            let timestampText = '';

            try {
                timestampText = timestamp.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });
            } catch (e) {
                timestampText = timestamp.toISOString();
            }

            const historyLabel = historyTemplate
                .replace('%1$s', timestampText)
                .replace('%2$s', message);

            const entry = $('<p class="ssc-toast-log__entry"></p>')
                .attr('aria-label', historyLabel);

            entry.append($('<span class="ssc-toast-log__timestamp"></span>').text(timestampText));
            entry.append(' ');
            entry.append($('<span class="ssc-toast-log__message"></span>').text(message));

            log.append(entry);

            const entries = log.children('.ssc-toast-log__entry');
            if (entries.length > TOAST_HISTORY_LIMIT) {
                entries.slice(0, entries.length - TOAST_HISTORY_LIMIT).remove();
            }
        }
    };

    // --- Plugin Asset URL Helper ---
    window.sscPluginAssetUrl = function(relativePath) {
        if (typeof relativePath !== 'string' || relativePath.trim() === '') {
            return '';
        }

        const sanitizedPath = relativePath.replace(/^\/+/, '');

        if (typeof SSC === 'undefined' || !SSC || typeof SSC.pluginUrl !== 'string') {
            return sanitizedPath;
        }

        const baseUrl = SSC.pluginUrl.endsWith('/') ? SSC.pluginUrl : SSC.pluginUrl + '/';
        return baseUrl + sanitizedPath;
    };

    // --- Clipboard Helper ---
    window.sscCopyToClipboard = function(text, options = {}) {
        const i18n = getSscI18n();
        const successMessage = (typeof options.successMessage === 'string')
            ? options.successMessage
            : i18n.clipboardSuccess;
        const errorMessage = (typeof options.errorMessage === 'string')
            ? options.errorMessage
            : i18n.clipboardError;

        const showSuccess = () => window.sscToast(successMessage);
        const showError = (error) => {
            if (errorMessage) {
                window.sscToast(errorMessage);
            }
            throw error;
        };

        const fallbackCopy = () => new Promise((resolve, reject) => {
            const textarea = $('<textarea></textarea>')
                .css({
                    position: 'fixed',
                    top: '-1000px',
                    left: '-1000px',
                    opacity: 0
                })
                .val(text)
                .appendTo('body');

            textarea[0].select();
            textarea[0].setSelectionRange(0, text.length);

            let succeeded = false;
            try {
                succeeded = document.execCommand('copy');
            } catch (err) {
                textarea.remove();
                reject(err);
                return;
            }

            textarea.remove();
            if (succeeded) {
                resolve();
            } else {
                reject(new Error('execCommand copy failed'));
            }
        });

        const copyPromise = (navigator.clipboard && window.isSecureContext)
            ? navigator.clipboard.writeText(text).catch(() => fallbackCopy())
            : fallbackCopy();

        return copyPromise.then(() => {
            showSuccess();
        }).catch(error => {
            showError(error);
        });
    };

    (function attachGlobalAsyncGuards() {
        if (typeof window === 'undefined') {
            return;
        }

        if (window.__sscAsyncGuardsAttached) {
            return;
        }

        const suppressedMessages = [
            'a listener indicated an asynchronous response by returning true, but the message channel closed before a response was received'
        ];

        const extractMessage = (source) => {
            if (!source) {
                return '';
            }

            if (typeof source === 'string') {
                return source;
            }

            if (typeof source.message === 'string') {
                return source.message;
            }

            if (typeof source.reason === 'string') {
                return source.reason;
            }

            if (source.reason && typeof source.reason.message === 'string') {
                return source.reason.message;
            }

            if (typeof source.error === 'string') {
                return source.error;
            }

            if (source.error && typeof source.error.message === 'string') {
                return source.error.message;
            }

            return '';
        };

        const shouldSuppress = (message) => {
            if (typeof message !== 'string' || message.trim() === '') {
                return false;
            }

            const normalized = message.toLowerCase();
            return suppressedMessages.some((pattern) => normalized.indexOf(pattern) !== -1);
        };

        const logSuppressed = (message) => {
            const consoleObject = window.console;
            if (!consoleObject) {
                return;
            }

            const logger = consoleObject.debug || consoleObject.info || consoleObject.log;
            if (typeof logger === 'function') {
                logger.call(consoleObject, '[Supersede CSS] Ignored async message:', message);
            }
        };

        window.addEventListener('unhandledrejection', (event) => {
            const message = extractMessage(event && event.reason);
            if (!shouldSuppress(message)) {
                return;
            }

            if (typeof event.preventDefault === 'function') {
                event.preventDefault();
            }

            if (typeof event.stopPropagation === 'function') {
                event.stopPropagation();
            }

            logSuppressed(message);
        });

        window.addEventListener('error', (event) => {
            const message = extractMessage(event);
            if (!shouldSuppress(message)) {
                return;
            }

            if (typeof event.preventDefault === 'function') {
                event.preventDefault();
            }

            if (typeof event.stopPropagation === 'function') {
                event.stopPropagation();
            }

            logSuppressed(message);
        }, true);

        window.__sscAsyncGuardsAttached = true;
    })();

    const commandPalette = (() => {
        const localized = getSscI18n();
        const sources = new Map();
        let overlay = null;
        let searchInput = null;
        let resultsList = null;
        let emptyStateEl = null;
        let results = [];
        let activeIndex = -1;
        let isPaletteOpen = false;
        let lastFocusedElement = null;
        let config = {
            title: localized.commandPaletteTitle,
            searchPlaceholder: localized.commandPaletteSearchPlaceholder,
            searchLabel: localized.commandPaletteSearchLabel,
            emptyState: localized.commandPaletteEmptyState,
            announce: (count) => localized.getCommandPaletteResultsAnnouncement(count),
        };

        const speak = (message) => {
            if (!message) {
                return;
            }
            if (window.wp && window.wp.a11y && typeof window.wp.a11y.speak === 'function') {
                window.wp.a11y.speak(message, 'polite');
            }
        };

        const setActiveIndex = (index) => {
            activeIndex = index;
            if (!resultsList) {
                return;
            }
            resultsList.children().removeClass('is-active').attr('aria-selected', 'false');
            if (activeIndex >= 0) {
                const item = resultsList.children().eq(activeIndex);
                item.addClass('is-active').attr('aria-selected', 'true');
                if (searchInput && item.length) {
                    searchInput.attr('aria-activedescendant', item.attr('id'));
                }
            } else if (searchInput) {
                searchInput.removeAttr('aria-activedescendant');
            }
        };

        const executeCommand = (index) => {
            if (index < 0 || index >= results.length) {
                return;
            }
            const item = results[index];
            close();

            if (item.perform && typeof item.perform === 'function') {
                item.perform();
                return;
            }

            if (item.href) {
                window.location.href = item.href;
            }
        };

        const onInputKeyDown = (event) => {
            if (!isPaletteOpen) {
                return;
            }

            if (event.key === 'ArrowDown') {
                event.preventDefault();
                if (results.length) {
                    const nextIndex = activeIndex >= results.length - 1 ? 0 : activeIndex + 1;
                    setActiveIndex(nextIndex);
                }
            } else if (event.key === 'ArrowUp') {
                event.preventDefault();
                if (results.length) {
                    const nextIndex = activeIndex <= 0 ? results.length - 1 : activeIndex - 1;
                    setActiveIndex(nextIndex);
                }
            } else if (event.key === 'Enter') {
                event.preventDefault();
                if (results.length) {
                    executeCommand(activeIndex >= 0 ? activeIndex : 0);
                }
            } else if (event.key === 'Escape') {
                event.preventDefault();
                close();
            }
        };

        const collectResults = (query) => {
            const aggregated = [];
            sources.forEach((factory) => {
                try {
                    const items = factory();
                    if (!Array.isArray(items)) {
                        return;
                    }
                    items.forEach((item) => {
                        if (!item || typeof item !== 'object') {
                            return;
                        }
                        aggregated.push(item);
                    });
                } catch (error) {
                    // ignore faulty sources
                }
            });

            const normalized = (query || '').trim().toLowerCase();
            if (!normalized) {
                return aggregated;
            }

            return aggregated.filter((item) => {
                const haystack = [item.title, item.subtitle]
                    .concat(Array.isArray(item.keywords) ? item.keywords : [])
                    .filter(Boolean)
                    .join(' ')
                    .toLowerCase();
                return haystack.indexOf(normalized) !== -1;
            });
        };

        const renderResults = () => {
            if (!resultsList) {
                return;
            }

            resultsList.empty();

            if (!results.length) {
                if (emptyStateEl) {
                    emptyStateEl.removeAttr('hidden');
                }
                setActiveIndex(-1);
                return;
            }

            if (emptyStateEl) {
                emptyStateEl.attr('hidden', 'hidden');
            }

            results.forEach((item, index) => {
                const listItem = $('<li>', {
                    class: 'ssc-command-palette__item',
                    role: 'option',
                    id: `ssc-command-palette-item-${index}`,
                    'data-index': index,
                });

                listItem.append($('<span>', {
                    class: 'ssc-command-palette__item-title',
                    text: item.title || '',
                }));

                if (item.subtitle) {
                    listItem.append($('<span>', {
                        class: 'ssc-command-palette__item-subtitle',
                        text: item.subtitle,
                    }));
                }

                listItem.on('mousedown', (event) => {
                    event.preventDefault();
                    executeCommand(index);
                });

                listItem.on('mouseenter', () => {
                    setActiveIndex(index);
                });

                resultsList.append(listItem);
            });

            setActiveIndex(results.length ? Math.max(0, Math.min(activeIndex, results.length - 1)) : -1);
        };

        const updateResults = (query) => {
            results = collectResults(query);
            renderResults();
            if (typeof config.announce === 'function') {
                const announcement = config.announce(results.length);
                speak(announcement);
            }
        };

        const ensureOverlay = () => {
            if (overlay) {
                return;
            }

            overlay = $('<div>', {
                class: 'ssc-command-palette',
                id: 'ssc-cmdp',
                hidden: 'hidden',
            });
            const backdrop = $('<div>', {
                class: 'ssc-command-palette__backdrop',
                tabindex: '-1',
            });
            const dialog = $('<div>', {
                class: 'ssc-command-palette__dialog',
                role: 'dialog',
                'aria-modal': 'true',
                'aria-labelledby': 'ssc-command-palette-title',
            });
            const header = $('<div>', { class: 'ssc-command-palette__header' });
            const title = $('<h2>', {
                class: 'ssc-command-palette__title',
                id: 'ssc-command-palette-title',
                text: config.title,
            });
            header.append(title);
            searchInput = $('<input>', {
                type: 'search',
                class: 'ssc-command-palette__search',
                id: 'ssc-cmdp-search',
                placeholder: config.searchPlaceholder,
                'aria-label': config.searchLabel,
                autocomplete: 'off',
                'aria-controls': 'ssc-cmdp-results',
                'aria-autocomplete': 'list',
            });
            resultsList = $('<ul>', {
                class: 'ssc-command-palette__results',
                role: 'listbox',
                id: 'ssc-cmdp-results',
                'aria-live': 'polite',
            });
            emptyStateEl = $('<p>', {
                class: 'ssc-command-palette__empty',
                id: 'ssc-cmdp-empty',
                text: config.emptyState,
                hidden: 'hidden',
            });

            dialog.append(header, searchInput, resultsList, emptyStateEl);
            overlay.append(backdrop, dialog);
            $('body').append(overlay);

            overlay.on('click', (event) => {
                if ($(event.target).hasClass('ssc-command-palette__backdrop')) {
                    close();
                }
            });

            searchInput.on('input', function() {
                updateResults($(this).val() || '');
            });

            searchInput.on('keydown', onInputKeyDown);
        };

        const open = (initialQuery = '') => {
            ensureOverlay();

            if (isPaletteOpen) {
                updateResults(initialQuery);
                searchInput.val(initialQuery);
                setActiveIndex(results.length ? 0 : -1);
                return;
            }

            lastFocusedElement = document.activeElement;
            overlay.removeAttr('hidden');
            $('body').addClass('ssc-command-palette-open');
            isPaletteOpen = true;
            searchInput.val(initialQuery);
            updateResults(initialQuery);
            setActiveIndex(results.length ? 0 : -1);
            setTimeout(() => {
                searchInput.trigger('focus');
            }, 0);
        };

        const close = () => {
            if (!overlay || !isPaletteOpen) {
                return;
            }
            overlay.attr('hidden', 'hidden');
            $('body').removeClass('ssc-command-palette-open');
            isPaletteOpen = false;
            activeIndex = -1;
            if (searchInput) {
                searchInput.val('').removeAttr('aria-activedescendant');
            }
            if (lastFocusedElement && typeof lastFocusedElement.focus === 'function') {
                lastFocusedElement.focus();
            }
        };

        const configure = (options = {}) => {
            if (!options || typeof options !== 'object') {
                return;
            }
            config = {
                ...config,
                ...options,
            };

            if (overlay) {
                overlay.find('.ssc-command-palette__title').text(config.title);
                if (searchInput) {
                    searchInput.attr('placeholder', config.searchPlaceholder);
                    searchInput.attr('aria-label', config.searchLabel);
                }
                if (emptyStateEl) {
                    emptyStateEl.text(config.emptyState);
                }
            }
        };

        const registerSource = (id, factory) => {
            if (typeof id !== 'string' || !id.trim() || typeof factory !== 'function') {
                return;
            }
            sources.set(id, factory);
        };

        return {
            configure,
            registerSource,
            open,
            close,
            isOpen: () => isPaletteOpen,
        };
    })();

    window.sscCommandPalette = {
        configure: commandPalette.configure,
        registerSource: commandPalette.registerSource,
        open: commandPalette.open,
        close: commandPalette.close,
        isOpen: commandPalette.isOpen,
    };

    $(document).ready(function() {
        const i18n = getSscI18n();
        const commandPaletteTitle = i18n.commandPaletteTitle;
        const commandPaletteSearchPlaceholder = i18n.commandPaletteSearchPlaceholder;
        const commandPaletteSearchLabel = i18n.commandPaletteSearchLabel;
        const commandPaletteEmptyState = i18n.commandPaletteEmptyState;
        const mobileMenuShowLabel = i18n.mobileMenuShowLabel;
        const mobileMenuHideLabel = i18n.mobileMenuHideLabel;
        const mobileMenuToggleSrLabel = i18n.mobileMenuToggleSrLabel;
        const getCommandPaletteResultsAnnouncement = (count) => i18n.getCommandPaletteResultsAnnouncement(count);

        // --- Dark/Light Theme Toggle ---
        const themeToggle = $('#ssc-theme');
        const body = $('body');

        const visualDebug = (() => {
            const STORAGE_KEY = 'ssc-visual-debug-enabled';
            const subscribers = new Set();
            let currentState = false;

            const safeStorage = () => {
                try {
                    return window.localStorage || null;
                } catch (error) {
                    return null;
                }
            };

            const persist = (enabled) => {
                const storage = safeStorage();
                if (!storage) {
                    return;
                }

                try {
                    storage.setItem(STORAGE_KEY, enabled ? '1' : '0');
                } catch (error) {
                    // Ignore storage quota/security errors.
                }
            };

            const read = () => {
                const storage = safeStorage();
                if (!storage) {
                    return false;
                }

                try {
                    const value = storage.getItem(STORAGE_KEY);
                    return value === '1' || value === 'true';
                } catch (error) {
                    return false;
                }
            };

            const apply = (enabled) => {
                currentState = !!enabled;
                if (body && body.length) {
                    body.toggleClass('ssc-visual-debug-active', currentState);
                }
            };

            const notify = (meta = {}) => {
                const payload = { ...meta };
                subscribers.forEach((callback) => {
                    try {
                        callback(currentState, payload);
                    } catch (error) {
                        // Ignore subscriber errors to avoid breaking the UI.
                    }
                });
                $(document).trigger('ssc:visual-debug:change', [currentState, payload]);
            };

            return {
                STORAGE_KEY,
                init() {
                    apply(read());
                    notify({ initial: true, silent: true });
                },
                set(enabled, meta = {}) {
                    apply(enabled);
                    persist(currentState);
                    notify(meta);
                },
                isEnabled() {
                    return currentState;
                },
                read,
                onChange(callback) {
                    if (typeof callback === 'function') {
                        subscribers.add(callback);
                        try {
                            callback(currentState, { initial: true, silent: true });
                        } catch (error) {
                            // Ignore subscriber errors.
                        }
                    }

                    return () => {
                        subscribers.delete(callback);
                    };
                },
            };
        })();

        visualDebug.init();
        window.sscVisualDebug = visualDebug;

        $(document).on('ssc:visual-debug:update', (event, state, meta = {}) => {
            visualDebug.set(!!state, meta);
        });

        if (localStorage.getItem('ssc-theme') === 'dark') {
            body.addClass('ssc-dark');
        }

        const updateThemeToggleAria = () => {
            themeToggle.attr('aria-pressed', body.hasClass('ssc-dark') ? 'true' : 'false');
        };

        updateThemeToggleAria();

        themeToggle.on('click', function() {
            body.toggleClass('ssc-dark');
            localStorage.setItem('ssc-theme', body.hasClass('ssc-dark') ? 'dark' : 'light');
            updateThemeToggleAria();
        });

        // --- Mobile Sidebar Toggle ---
        const shell = $('.ssc-shell');
        const sidebar = $('#ssc-sidebar');
        const mobileMenuToggle = $('#ssc-mobile-menu');
        const overlay = $('.ssc-shell-overlay');
        const bodyEl = $('body');
        const focusableSelectors = 'a[href], area[href], input:not([disabled]), select:not([disabled]), textarea:not([disabled]), button:not([disabled]), [tabindex]:not([tabindex="-1"])';
        let lastFocusedElement = null;

        const isMobileViewport = () => window.matchMedia('(max-width: 960px)').matches;

        const updateSidebarAria = () => {
            if (isMobileViewport() && !shell.hasClass('ssc-shell--menu-open')) {
                sidebar.attr('aria-hidden', 'true');
            } else {
                sidebar.removeAttr('aria-hidden');
            }
        };

        const focusSidebar = () => {
            const focusable = sidebar.find(focusableSelectors).filter(':visible');
            if (focusable.length) {
                focusable.first().trigger('focus');
            } else {
                sidebar.attr('tabindex', '-1');
                sidebar.trigger('focus');
            }
        };

        if (mobileMenuToggle.length) {
            mobileMenuToggle.attr('aria-label', mobileMenuShowLabel);
            const srText = mobileMenuToggle.find('.screen-reader-text');
            if (srText.length) {
                srText.text(mobileMenuToggleSrLabel);
            }
        }

        const openMobileMenu = () => {
            if (!isMobileViewport() || shell.hasClass('ssc-shell--menu-open')) {
                return;
            }

            lastFocusedElement = document.activeElement instanceof HTMLElement ? document.activeElement : null;
            shell.addClass('ssc-shell--menu-open');
            bodyEl.addClass('ssc-no-scroll');
            overlay.removeAttr('hidden');
            mobileMenuToggle.attr({
                'aria-expanded': 'true',
                'aria-label': mobileMenuHideLabel
            });
            updateSidebarAria();
            focusSidebar();
        };

        const closeMobileMenu = ({ restoreFocus = true } = {}) => {
            if (!shell.hasClass('ssc-shell--menu-open')) {
                updateSidebarAria();
                return;
            }

            shell.removeClass('ssc-shell--menu-open');
            bodyEl.removeClass('ssc-no-scroll');
            overlay.attr('hidden', 'hidden');
            mobileMenuToggle.attr({
                'aria-expanded': 'false',
                'aria-label': mobileMenuShowLabel
            });
            sidebar.removeAttr('tabindex');
            updateSidebarAria();

            if (restoreFocus && lastFocusedElement) {
                $(lastFocusedElement).trigger('focus');
            }
            lastFocusedElement = null;
        };

        updateSidebarAria();

        mobileMenuToggle.on('click', function() {
            if (shell.hasClass('ssc-shell--menu-open')) {
                closeMobileMenu();
            } else {
                openMobileMenu();
            }
        });

        overlay.on('click', function() {
            closeMobileMenu();
        });

        shell.on('click', function(event) {
            if (!shell.hasClass('ssc-shell--menu-open') || !isMobileViewport()) {
                return;
            }

            if (!$(event.target).closest('aside, #ssc-mobile-menu').length) {
                closeMobileMenu();
            }
        });

        sidebar.on('click', 'a', function() {
            if (isMobileViewport()) {
                closeMobileMenu({ restoreFocus: false });
            }
        });

        $(window).on('resize', function() {
            if (!isMobileViewport()) {
                closeMobileMenu({ restoreFocus: false });
            }
            updateSidebarAria();
        });

        // --- Command Palette (⌘K) ---
        const cmdkButton = $('#ssc-cmdk');
        const paletteApi = (typeof window !== 'undefined' && typeof window.sscCommandPalette === 'object')
            ? window.sscCommandPalette
            : null;
        const hasPaletteModule = !!(
            paletteApi
            && typeof paletteApi.configure === 'function'
            && typeof paletteApi.registerSource === 'function'
            && typeof paletteApi.open === 'function'
            && typeof paletteApi.close === 'function'
            && typeof paletteApi.isOpen === 'function'
        );

        const shouldIgnorePaletteShortcut = (target) => {
            if (!target) {
                return false;
            }
            const element = target instanceof HTMLElement ? target : null;
            if (!element) {
                return false;
            }
            if (element.isContentEditable) {
                return true;
            }
            const tagName = element.tagName ? element.tagName.toLowerCase() : '';
            if (tagName === 'input') {
                const type = (element.getAttribute('type') || '').toLowerCase();
                const passthroughTypes = ['button', 'checkbox', 'color', 'file', 'hidden', 'image', 'radio', 'range', 'reset', 'submit'];
                if (passthroughTypes.indexOf(type) === -1) {
                    return true;
                }
            }
            if (tagName === 'textarea' || tagName === 'select') {
                return true;
            }
            return false;
        };

        if (hasPaletteModule) {
            paletteApi.configure({
                title: commandPaletteTitle,
                searchPlaceholder: commandPaletteSearchPlaceholder,
                searchLabel: commandPaletteSearchLabel,
                emptyState: commandPaletteEmptyState,
                announce: (count) => getCommandPaletteResultsAnnouncement(count),
            });

            paletteApi.registerSource('primary-navigation', () => {
                const items = [];
                $('.ssc-sidebar a').each(function() {
                    const link = $(this);
                    const text = (link.text() || '').trim();
                    if (!text) {
                        return;
                    }
                    const href = link.attr('href') || '';
                    const description = link.attr('aria-label') || link.attr('title') || '';
                    items.push({
                        title: text,
                        subtitle: description || href,
                        href,
                        keywords: [text, description, 'navigation'].filter(Boolean),
                    });
                });
                return items;
            });

            paletteApi.registerSource('global-actions', () => {
                const actions = [];
                if (themeToggle.length) {
                    actions.push({
                        title: 'Action: Basculer le thème (Clair/Sombre)',
                        keywords: ['thème', 'clair', 'sombre', 'mode'],
                        perform: () => themeToggle.trigger('click'),
                    });
                }
                const clearLogButton = $('#ssc-clear-log');
                if (clearLogButton.length) {
                    actions.push({
                        title: 'Action: Vider le journal d\'activité',
                        keywords: ['journal', 'activité', 'debug'],
                        perform: () => {
                            if (clearLogButton.length) {
                                clearLogButton.trigger('click');
                            } else if (typeof window.sscToast === 'function') {
                                window.sscToast('Action non disponible sur cette page.');
                            }
                        },
                    });
                }
                return actions;
            });

            if (cmdkButton.length) {
                cmdkButton.attr({
                    'aria-haspopup': 'dialog',
                    'aria-expanded': paletteApi.isOpen() ? 'true' : 'false',
                });
                cmdkButton.on('click', function(event) {
                    event.preventDefault();
                    paletteApi.open();
                });

                if (typeof MutationObserver === 'function') {
                    const observer = new MutationObserver(() => {
                        cmdkButton.attr('aria-expanded', paletteApi.isOpen() ? 'true' : 'false');
                    });
                    observer.observe(document.body, { attributes: true, attributeFilter: ['class'] });
                }
            }

            const getPaletteContainer = () => $('.ssc-command-palette').filter(function() {
                return !this.hasAttribute('hidden');
            }).first();

            const focusPalette = () => {
                const container = getPaletteContainer();
                if (!container.length) {
                    return;
                }
                const searchField = container.find('.ssc-command-palette__search');
                if (searchField.length) {
                    searchField.trigger('focus');
                    return;
                }
                container.trigger('focus');
            };

            const trapFocusInPalette = (event) => {
                const container = getPaletteContainer();
                if (!container.length) {
                    return;
                }
                const focusable = container.find(focusableSelectors).filter(':visible');
                if (!focusable.length) {
                    event.preventDefault();
                    focusPalette();
                    return;
                }
                const first = focusable.get(0);
                const last = focusable.get(focusable.length - 1);
                const activeElement = document.activeElement;
                if (!event.shiftKey && activeElement === last) {
                    event.preventDefault();
                    $(first).trigger('focus');
                } else if (event.shiftKey && activeElement === first) {
                    event.preventDefault();
                    $(last).trigger('focus');
                }
            };

            $(document).on('keydown.sscCommandPalette', function(event) {
                if (event.defaultPrevented) {
                    return;
                }
                const rawKey = event.key;
                const key = typeof rawKey === 'string' ? rawKey.toLowerCase() : rawKey;

                if ((event.metaKey || event.ctrlKey) && key === 'k') {
                    if (shouldIgnorePaletteShortcut(event.target)) {
                        return;
                    }
                    event.preventDefault();
                    paletteApi.open();
                    return;
                }

                if (key === 'escape') {
                    if (paletteApi.isOpen()) {
                        event.preventDefault();
                        paletteApi.close();
                        return;
                    }
                    if (shell.hasClass('ssc-shell--menu-open')) {
                        event.preventDefault();
                        closeMobileMenu();
                    }
                    return;
                }

                if (key === 'tab') {
                    if (paletteApi.isOpen()) {
                        trapFocusInPalette(event);
                        return;
                    }

                    if (shell.hasClass('ssc-shell--menu-open')) {
                        const focusable = sidebar.find(focusableSelectors).filter(':visible');
                        if (!focusable.length) {
                            return;
                        }
                        const first = focusable.get(0);
                        const last = focusable.get(focusable.length - 1);
                        const activeElement = document.activeElement;
                        if (!event.shiftKey && activeElement === last) {
                            event.preventDefault();
                            $(first).trigger('focus');
                        } else if (event.shiftKey && activeElement === first) {
                            event.preventDefault();
                            $(last).trigger('focus');
                        }
                    }
                }
            });

            $(document).on('focusin.sscCommandPalette', function(event) {
                if (paletteApi.isOpen()) {
                    const container = getPaletteContainer();
                    if (container.length && !$(event.target).closest('.ssc-command-palette__dialog').length) {
                        const focusable = container.find(focusableSelectors).filter(':visible');
                        const fallback = focusable.length
                            ? focusable.get(0)
                            : container.find('.ssc-command-palette__search').get(0);
                        if (fallback) {
                            $(fallback).trigger('focus');
                        }
                        return;
                    }
                }

                if (!shell.hasClass('ssc-shell--menu-open') || !isMobileViewport()) {
                    return;
                }

                if ($(event.target).closest('#ssc-sidebar, #ssc-mobile-menu').length === 0) {
                    focusSidebar();
                }
            });
        }

    });
})(jQuery);
