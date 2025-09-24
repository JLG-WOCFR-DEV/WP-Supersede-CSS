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
            if (e.key === 'Escape' && cmdp.hasClass('active')) {
                cmdp.removeClass('active');
            }
        });
    });
})(jQuery);
