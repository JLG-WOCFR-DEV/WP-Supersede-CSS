(function($) {
    // --- Toast Notifications ---
    const TOAST_DEFAULT_TIMEOUT = 3000;
    const TOAST_DEFAULT_POLITENESS = 'polite';

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

        return container;
    };

    window.sscToast = function(message, {
        politeness = TOAST_DEFAULT_POLITENESS,
        role,
        timeout = TOAST_DEFAULT_TIMEOUT
    } = {}) {
        const container = getToastContainer(politeness);
        const toastRole = role || (politeness === 'assertive' ? 'alert' : 'status');
        const toast = $('<div class="ssc-toast"></div>')
            .attr('role', toastRole)
            .text(message);

        container.append(toast);

        setTimeout(() => {
            toast.remove();

            if (!container.children().length) {
                container.attr('aria-live', TOAST_DEFAULT_POLITENESS);
            }
        }, timeout);
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
    window.sscCopyToClipboard = function(text, {
        successMessage = 'Texte copié !',
        errorMessage = 'Impossible de copier le texte.'
    } = {}) {
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

    $(document).ready(function() {
        const localizedData = (typeof window !== 'undefined' && typeof window.SSC !== 'undefined' && window.SSC)
            ? window.SSC
            : {};
        const i18n = (typeof localizedData.i18n === 'object' && localizedData.i18n !== null)
            ? localizedData.i18n
            : {};
        const commandPaletteTitle = i18n.commandPaletteTitle || 'Supersede CSS command palette';
        const commandPaletteSearchPlaceholder = i18n.commandPaletteSearchPlaceholder || 'Navigate or run an action…';
        const commandPaletteSearchLabel = i18n.commandPaletteSearchLabel || 'Command palette search';
        const getCommandPaletteResultsAnnouncement = (count) => {
            const template = i18n.commandPaletteResultsAnnouncement || '%d result(s) available.';

            if (typeof template === 'function') {
                return template(count);
            }

            if (typeof template === 'string') {
                if (template.includes('%d')) {
                    return template.replace(/%d/g, count);
                }

                return `${count} ${template}`;
            }

            return `${count} result(s) available.`;
        };

        // --- Dark/Light Theme Toggle ---
        const themeToggle = $('#ssc-theme');
        const body = $('body');

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
                'aria-label': 'Masquer le menu'
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
                'aria-label': 'Afficher le menu'
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
        const cmdkPanelHtml = `
            <div id="ssc-cmdp" role="dialog" aria-modal="true" aria-hidden="true" aria-label="${commandPaletteTitle}" tabindex="-1">
                <div class="panel" role="document">
                    <label for="ssc-cmdp-search" class="screen-reader-text">${commandPaletteSearchLabel}</label>
                    <input type="text" id="ssc-cmdp-search" placeholder="${commandPaletteSearchPlaceholder}" style="width: 100%; padding: 12px; border: none; border-bottom: 1px solid var(--ssc-border); font-size: 16px;">
                    <ul id="ssc-cmdp-results" role="listbox" aria-live="polite"></ul>
                </div>
            </div>`;
        $('body').append(cmdkPanelHtml);

        const cmdp = $('#ssc-cmdp');
        const searchInput = $('#ssc-cmdp-search');
        const resultsList = $('#ssc-cmdp-results');
        const backgroundElementsSelector = 'body > *:not(#ssc-cmdp)';
        let commands = [];
        let previouslyFocusedCommandElement = null;
        let paletteFocusableElements = $();
        let isCommandPaletteOpen = false;
        let optionElements = [];
        let activeOptionIndex = -1;
        let optionIdCounter = 0;

        const updatePaletteFocusableElements = () => {
            const focusable = cmdp.find(focusableSelectors).filter(':visible');
            paletteFocusableElements = focusable.length ? focusable : cmdp;
        };

        const setBackgroundTreeState = (hidden) => {
            $(backgroundElementsSelector).each(function() {
                const $element = $(this);

                if (hidden) {
                    if (typeof $element.data('sscCmdpOriginalAriaHidden') === 'undefined') {
                        const originalAriaHidden = $element.attr('aria-hidden');
                        $element.data('sscCmdpOriginalAriaHidden', typeof originalAriaHidden === 'undefined' ? null : originalAriaHidden);
                    }

                    if (typeof $element.data('sscCmdpOriginalInert') === 'undefined') {
                        $element.data('sscCmdpOriginalInert', this.hasAttribute('inert'));
                    }

                    $element.attr('aria-hidden', 'true');
                    this.setAttribute('inert', '');
                } else {
                    if (typeof $element.data('sscCmdpOriginalAriaHidden') !== 'undefined') {
                        const originalAriaHidden = $element.data('sscCmdpOriginalAriaHidden');
                        if (originalAriaHidden === null) {
                            $element.removeAttr('aria-hidden');
                        } else {
                            $element.attr('aria-hidden', originalAriaHidden);
                        }
                        $element.removeData('sscCmdpOriginalAriaHidden');
                    }

                    if (typeof $element.data('sscCmdpOriginalInert') !== 'undefined') {
                        if ($element.data('sscCmdpOriginalInert')) {
                            this.setAttribute('inert', '');
                        } else {
                            this.removeAttribute('inert');
                        }
                        $element.removeData('sscCmdpOriginalInert');
                    }
                }
            });
        };

        const openCommandPalette = () => {
            if (isCommandPaletteOpen) {
                searchInput.trigger('focus');
                return;
            }

            previouslyFocusedCommandElement = document.activeElement instanceof HTMLElement ? document.activeElement : null;
            isCommandPaletteOpen = true;
            cmdp.addClass('active');
            cmdp.attr('aria-hidden', 'false');
            cmdkButton.attr('aria-expanded', 'true');
            setBackgroundTreeState(true);
            renderResults();
            searchInput.val('').trigger('focus');
            updatePaletteFocusableElements();
        };

        const closeCommandPalette = () => {
            if (!isCommandPaletteOpen) {
                return;
            }

            isCommandPaletteOpen = false;
            cmdp.removeClass('active');
            cmdp.attr('aria-hidden', 'true');
            cmdkButton.attr('aria-expanded', 'false');
            setBackgroundTreeState(false);
            paletteFocusableElements = $();
            if (cmdkButton.length) {
                cmdkButton.trigger('focus');
            } else if (previouslyFocusedCommandElement) {
                $(previouslyFocusedCommandElement).trigger('focus');
            }
            previouslyFocusedCommandElement = null;
        };

        cmdkButton.attr({
            'aria-haspopup': 'dialog',
            'aria-expanded': 'false'
        });

        // Collect navigation links
        $('.ssc-sidebar a').each(function() {
            commands.push({
                name: "Nav: " + $(this).text().trim(),
                type: 'link',
                handler: $(this).attr('href')
            });
        });

        // Add actions
        commands.push(
            { name: 'Action: Basculer le thème (Clair/Sombre)', type: 'action', handler: () => $('#ssc-theme').click() },
            { name: 'Action: Vider le journal d\'activité', type: 'action', handler: () => {
                if ($('#ssc-clear-log').length) {
                    $('#ssc-clear-log').click();
                } else {
                    window.sscToast('Action non disponible sur cette page.');
                }
            }}
        );

        const setActiveOption = (index, { scrollIntoView = false } = {}) => {
            if (!optionElements.length || index < 0) {
                activeOptionIndex = -1;
                optionElements.forEach(option => {
                    option.attr('aria-selected', 'false');
                    option.removeClass('is-active');
                });
                resultsList.removeAttr('aria-activedescendant');
                return;
            }

            const boundedIndex = Math.max(0, Math.min(index, optionElements.length - 1));
            activeOptionIndex = boundedIndex;

            optionElements.forEach((option, idx) => {
                const isActive = idx === activeOptionIndex;
                option.attr('aria-selected', isActive ? 'true' : 'false');
                option.toggleClass('is-active', isActive);
            });

            const activeOption = optionElements[activeOptionIndex];
            if (activeOption && activeOption.length) {
                resultsList.attr('aria-activedescendant', activeOption.attr('id'));
                if (scrollIntoView && typeof activeOption[0].scrollIntoView === 'function') {
                    activeOption[0].scrollIntoView({ block: 'nearest' });
                }
            }
        };

        const activateOption = (index) => {
            if (index < 0 || index >= optionElements.length) {
                return;
            }
            optionElements[index].trigger('click');
        };

        function renderResults(query = '') {
            resultsList.empty();
            optionElements = [];
            const filtered = query
                ? commands.filter(c => c.name.toLowerCase().includes(query.toLowerCase()))
                : commands;

            filtered.forEach(c => {
                const optionId = `ssc-cmdp-option-${++optionIdCounter}`;
                const link = $(`<a href="#">${c.name}</a>`).attr({
                    role: 'option',
                    id: optionId,
                    'aria-selected': 'false'
                });
                link.on('click', (e) => {
                    e.preventDefault();
                    closeCommandPalette();
                    if (c.type === 'link') {
                        window.location.href = c.handler;
                    } else {
                        c.handler();
                    }
                });
                optionElements.push(link);
                resultsList.append($('<li></li>').append(link));
            });
            const resultCount = filtered.length;
            setActiveOption(resultCount ? 0 : -1);
            if (typeof window !== 'undefined' && window.wp && wp.a11y && typeof wp.a11y.speak === 'function') {
                const announcement = getCommandPaletteResultsAnnouncement(resultCount);
                if (announcement) {
                    wp.a11y.speak(announcement, 'polite');
                }
            }
            updatePaletteFocusableElements();
        }

        cmdkButton.on('click', () => {
            openCommandPalette();
        });

        cmdp.on('click', function(e) {
            if ($(e.target).is(cmdp)) {
                closeCommandPalette();
            }
        });

        searchInput.on('input', () => renderResults(searchInput.val()));

        searchInput.on('keydown', function(e) {
            const key = e.key;
            if (!['ArrowDown', 'ArrowUp', 'Home', 'End', 'Enter'].includes(key)) {
                return;
            }

            if (key === 'Enter') {
                if (activeOptionIndex >= 0) {
                    e.preventDefault();
                    activateOption(activeOptionIndex);
                }
                return;
            }

            if (!optionElements.length) {
                return;
            }

            e.preventDefault();

            if (key === 'ArrowDown') {
                const nextIndex = activeOptionIndex < 0 ? 0 : activeOptionIndex + 1;
                setActiveOption(nextIndex, { scrollIntoView: true });
            } else if (key === 'ArrowUp') {
                const previousIndex = activeOptionIndex <= 0 ? 0 : activeOptionIndex - 1;
                setActiveOption(previousIndex, { scrollIntoView: true });
            } else if (key === 'Home') {
                setActiveOption(0, { scrollIntoView: true });
            } else if (key === 'End') {
                setActiveOption(optionElements.length - 1, { scrollIntoView: true });
            }
        });

        $(document).on('keydown', function(e) {
            const key = typeof e.key === 'string' ? e.key.toLowerCase() : e.key;

            if ((e.metaKey || e.ctrlKey) && key === 'k') {
                e.preventDefault();
                openCommandPalette();
            }

            if (key === 'escape') {
                if (isCommandPaletteOpen) {
                    e.preventDefault();
                    closeCommandPalette();
                }
                if (shell.hasClass('ssc-shell--menu-open')) {
                    closeMobileMenu();
                }
            }

            if (isCommandPaletteOpen && key === 'tab') {
                const focusable = paletteFocusableElements;
                const elements = focusable.toArray();

                if (!elements.length) {
                    e.preventDefault();
                    cmdp.trigger('focus');
                    return;
                }

                const first = elements[0];
                const last = elements[elements.length - 1];
                const activeElement = document.activeElement;

                if (!e.shiftKey && activeElement === last) {
                    e.preventDefault();
                    $(first).trigger('focus');
                } else if (e.shiftKey && activeElement === first) {
                    e.preventDefault();
                    $(last).trigger('focus');
                }
                return;
            }

            if (shell.hasClass('ssc-shell--menu-open') && key === 'tab') {
                const focusable = sidebar.find(focusableSelectors).filter(':visible');
                if (!focusable.length) {
                    return;
                }

                const first = focusable.get(0);
                const last = focusable.get(focusable.length - 1);
                const activeElement = document.activeElement;

                if (!e.shiftKey && activeElement === last) {
                    e.preventDefault();
                    $(first).trigger('focus');
                } else if (e.shiftKey && activeElement === first) {
                    e.preventDefault();
                    $(last).trigger('focus');
                }
            }
        });

        $(document).on('focusin', function(e) {
            if (isCommandPaletteOpen && $(e.target).closest('#ssc-cmdp').length === 0) {
                if (paletteFocusableElements.length) {
                    $(paletteFocusableElements.get(0)).trigger('focus');
                } else {
                    cmdp.trigger('focus');
                }
                return;
            }

            if (!shell.hasClass('ssc-shell--menu-open') || !isMobileViewport()) {
                return;
            }

            if ($(e.target).closest('#ssc-sidebar, #ssc-mobile-menu').length === 0) {
                focusSidebar();
            }
        });
    });
})(jQuery);
