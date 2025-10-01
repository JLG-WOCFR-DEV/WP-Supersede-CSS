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
        // --- Dark/Light Theme Toggle ---
        const themeToggle = $('#ssc-theme');
        const body = $('body');

        if (localStorage.getItem('ssc-theme') === 'dark') {
            body.addClass('ssc-dark');
        }
        themeToggle.on('click', function() {
            body.toggleClass('ssc-dark');
            localStorage.setItem('ssc-theme', body.hasClass('ssc-dark') ? 'dark' : 'light');
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
            <div id="ssc-cmdp" role="dialog" aria-modal="true" aria-hidden="true" aria-label="Palette de commandes Supersede CSS" tabindex="-1">
                <div class="panel" role="document">
                    <input type="text" id="ssc-cmdp-search" placeholder="Naviguer ou lancer une action..." style="width: 100%; padding: 12px; border: none; border-bottom: 1px solid var(--ssc-border); font-size: 16px;">
                    <ul id="ssc-cmdp-results"></ul>
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

        function renderResults(query = '') {
            resultsList.empty();
            const filtered = query 
                ? commands.filter(c => c.name.toLowerCase().includes(query.toLowerCase()))
                : commands;

            filtered.forEach(c => {
                const link = $(`<a href="#">${c.name}</a>`);
                link.on('click', (e) => {
                    e.preventDefault();
                    closeCommandPalette();
                    if (c.type === 'link') {
                        window.location.href = c.handler;
                    } else {
                        c.handler();
                    }
                });
                resultsList.append($('<li></li>').append(link));
            });
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
