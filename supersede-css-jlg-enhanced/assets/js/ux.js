(function($) {
    // --- Toast Notifications ---
    window.sscToast = function(message) {
        const toast = $('<div class="ssc-toast"></div>').text(message);
        const container = $('#ssc-toasts').length ? $('#ssc-toasts') : $('<div id="ssc-toasts"></div>').appendTo('body');
        container.append(toast);
        setTimeout(() => toast.remove(), 3000);
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
            $('body').addClass('ssc-no-scroll');
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
            $('body').removeClass('ssc-no-scroll');
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

        $(window).on('resize', function() {
            if (!isMobileViewport()) {
                closeMobileMenu({ restoreFocus: false });
            }
            updateSidebarAria();
        });

        // --- Command Palette (⌘K) ---
        const cmdkButton = $('#ssc-cmdk');
        const cmdkPanelHtml = `
            <div id="ssc-cmdp">
                <div class="panel">
                    <input type="text" id="ssc-cmdp-search" placeholder="Naviguer ou lancer une action..." style="width: 100%; padding: 12px; border: none; border-bottom: 1px solid var(--ssc-border); font-size: 16px;">
                    <ul id="ssc-cmdp-results"></ul>
                </div>
            </div>`;
        $('body').append(cmdkPanelHtml);

        const cmdp = $('#ssc-cmdp');
        const searchInput = $('#ssc-cmdp-search');
        const resultsList = $('#ssc-cmdp-results');
        let commands = [];

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
                    if (c.type === 'link') {
                        window.location.href = c.handler;
                    } else {
                        c.handler();
                    }
                    cmdp.removeClass('active');
                });
                resultsList.append($('<li></li>').append(link));
            });
        }

        cmdkButton.on('click', () => {
            cmdp.addClass('active');
            renderResults();
            searchInput.val('').focus();
        });

        cmdp.on('click', function(e) {
            if ($(e.target).is(cmdp)) {
                cmdp.removeClass('active');
            }
        });
        
        searchInput.on('input', () => renderResults(searchInput.val()));

        $(document).on('keydown', function(e) {
            if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
                e.preventDefault();
                cmdkButton.click();
            }

            if (e.key === 'Escape') {
                if (cmdp.hasClass('active')) {
                    cmdp.removeClass('active');
                }
                if (shell.hasClass('ssc-shell--menu-open')) {
                    closeMobileMenu();
                }
            }

            if (shell.hasClass('ssc-shell--menu-open') && e.key === 'Tab') {
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
            if (!shell.hasClass('ssc-shell--menu-open') || !isMobileViewport()) {
                return;
            }

            if ($(e.target).closest('#ssc-sidebar, #ssc-mobile-menu').length === 0) {
                focusSidebar();
            }
        });
    });
})(jQuery);
