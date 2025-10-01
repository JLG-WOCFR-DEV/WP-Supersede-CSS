(function($) {
    const fallbackI18n = {
        __: (text) => text,
        _x: (text) => text,
        _n: (single, plural, number) => (number === 1 ? single : plural),
        _nx: (single, plural, number) => (number === 1 ? single : plural),
    };

    const hasI18n = typeof window !== 'undefined' && window.wp && window.wp.i18n;
    const { __, _x, _n, _nx } = hasI18n ? window.wp.i18n : fallbackI18n;

    if (!hasI18n) {
        // eslint-disable-next-line no-console
        console.warn(__('wp.i18n is not available. Falling back to untranslated strings.', 'supersede-css-jlg'));
    }

    let editors = {};
    let pickerActive = false;
    const editorViews = ['desktop', 'tablet', 'mobile'];
    const codeMirrorAvailable = typeof window !== 'undefined' && typeof window.CodeMirror !== 'undefined';
    let codeMirrorWarningShown = false;

    function notifyCodeMirrorUnavailable() {
        if (codeMirrorWarningShown) return;
        const message = __('Éditeur enrichi indisponible : CodeMirror n\'est pas chargé. Les champs texte classiques seront utilisés.', 'supersede-css-jlg');
        if (typeof window !== 'undefined' && typeof window.sscToast === 'function') {
            window.sscToast(message);
        } else if (typeof window !== 'undefined' && typeof window.alert === 'function') {
            window.alert(message);
        } else {
            console.warn(message);
        }
        codeMirrorWarningShown = true;
    }

    function initCodeMirrors() {
        if (!codeMirrorAvailable) {
            notifyCodeMirrorUnavailable();
            return;
        }

        editorViews.forEach(view => {
            const textarea = document.getElementById(`ssc-css-editor-${view}`);
            if (textarea) {
                editors[view] = CodeMirror.fromTextArea(textarea, {
                    lineNumbers: true, mode: 'css', theme: 'material-darker', lineWrapping: true,
                });
            }
        });
    }

    function getEditorValue(view) {
        if (editors[view]) {
            return editors[view].getValue();
        }

        const textarea = document.getElementById(`ssc-css-editor-${view}`);
        return textarea ? textarea.value : '';
    }

    function getFullCss() {
        const desktopCss = getEditorValue('desktop');
        const tabletCss = getEditorValue('tablet');
        const mobileCss = getEditorValue('mobile');

        const tabletWrapped = tabletCss.trim() ? `@media (max-width: 782px) {\n${tabletCss}\n}` : '';
        const mobileWrapped = mobileCss.trim() ? `@media (max-width: 480px) {\n${mobileCss}\n}` : '';

        return [desktopCss, tabletWrapped, mobileWrapped].filter(Boolean).join('\n\n');
    }

    function generateCssSelector(el) {
        if (!el || el.nodeType !== 1) return "";
        let path = [];
        while (el && el.nodeType === 1) {
            let selector = el.nodeName.toLowerCase();
            if (el.id) {
                selector = '#' + el.id.trim().replace(/([!"#$%&'()*+,./:;<=>?@[\\\]^`{|}~])/g, '\\$1');
                path.unshift(selector);
                break;
            } else {
                let classes = Array.from(el.classList).join('.');
                if (classes) {
                    selector += '.' + classes;
                }
                let sib = el, nth = 1;
                while (sib = sib.previousElementSibling) {
                    if (sib.nodeName.toLowerCase() == el.nodeName.toLowerCase()) nth++;
                }
                selector += `:nth-of-type(${nth})`;
            }
            path.unshift(selector);
            if (el.nodeName.toLowerCase() === 'body') break;
            el = el.parentNode;
        }
        return path.join(' > ');
    }

    $(document).ready(function() {
        if (!$('.ssc-utilities-wrap').length) return;

        initCodeMirrors();

        const $tabList = $('.ssc-editor-tabs');
        const $tabs = $tabList.find('.ssc-editor-tab');
        const $panels = $('.ssc-editor-panel');

        function setActiveTab($newTab, focus = false) {
            if (!$newTab.length) return;
            const tab = $newTab.data('tab');
            const panelId = $newTab.attr('aria-controls');
            const $panel = panelId ? $(`#${panelId}`) : $();

            $tabs.each(function() {
                const $tab = $(this);
                $tab.removeClass('active').attr({
                    'aria-selected': 'false',
                    tabindex: '-1'
                });
            });

            $panels.each(function() {
                $(this).removeClass('active').attr('hidden', true);
            });

            $newTab.addClass('active').attr({
                'aria-selected': 'true',
                tabindex: '0'
            });

            if (focus) {
                $newTab.trigger('focus');
            }

            if ($panel.length) {
                $panel.addClass('active').removeAttr('hidden');
            }

            if (editors[tab]) {
                editors[tab].refresh();
            }
        }

        $tabs.attr('tabindex', '-1');
        const $initialActive = $tabs.filter('.active').attr('tabindex', '0');
        if ($initialActive.length) {
            const initialPanelId = $initialActive.attr('aria-controls');
            if (initialPanelId) {
                $panels.not(`#${initialPanelId}`).attr('hidden', true);
            }
        } else if ($tabs.length) {
            setActiveTab($tabs.eq(0));
        }

        $tabs.on('click', function() {
            setActiveTab($(this));
        });

        $tabs.on('keydown', function(event) {
            const key = event.key;
            const currentIndex = $tabs.index(this);
            let newIndex = null;

            if (key === 'ArrowRight' || key === 'ArrowDown') {
                newIndex = (currentIndex + 1) % $tabs.length;
            } else if (key === 'ArrowLeft' || key === 'ArrowUp') {
                newIndex = (currentIndex - 1 + $tabs.length) % $tabs.length;
            } else if (key === 'Home') {
                newIndex = 0;
            } else if (key === 'End') {
                newIndex = $tabs.length - 1;
            }

            if (newIndex !== null) {
                event.preventDefault();
                const $targetTab = $tabs.eq(newIndex);
                setActiveTab($targetTab, true);
            }
        });

        $('#ssc-save-css').on('click', function() {
            const desktopCss = getEditorValue('desktop');
            const tabletCss = getEditorValue('tablet');
            const mobileCss = getEditorValue('mobile');
            const fullCss = getFullCss();
            $.ajax({
                url: SSC.rest.root + 'save-css', method: 'POST',
                data: {
                    css: fullCss,
                    css_desktop: desktopCss,
                    css_tablet: tabletCss,
                    css_mobile: mobileCss,
                    option_name: 'ssc_active_css',
                    _wpnonce: SSC.rest.nonce
                },
                beforeSend: x => x.setRequestHeader('X-WP-Nonce', SSC.rest.nonce)
            }).done(() => window.sscToast(__('CSS enregistré !', 'supersede-css-jlg')));
        });

        const pickerToggle = $('#ssc-element-picker-toggle');
        const previewFrame = $('#ssc-preview-frame');
        const urlField = $('#ssc-preview-url');
        const previewToggleButton = $('#ssc-preview-toggle');
        const previewColumn = $('#ssc-preview-column');
        const previewLayoutQuery = typeof window.matchMedia === 'function' ? window.matchMedia('(max-width: 1024px)') : null;
        let previewVisible = true;
        let lastHovered;
        let lastValidPreviewUrl = previewFrame.attr('src') || '';

        function notifyInvalidUrl(message) {
            if (typeof window.sscToast === 'function') {
                window.sscToast(message);
            } else {
                alert(message);
            }
        }

        pickerToggle.on('click', function() {
            pickerActive = !pickerActive;
            $(this).toggleClass('button-primary', pickerActive);
            const frameBody = previewFrame.contents().find('body');
            if (pickerActive) {
                frameBody.addClass('ssc-picker-active');
            } else {
                frameBody.removeClass('ssc-picker-active');
                if (lastHovered) lastHovered.removeClass('ssc-picker-highlight');
            }
        });

        previewFrame.on('load', function() {
            try {
                const frameBody = previewFrame.contents().find('body');
                const frameDoc = previewFrame.contents();
                
                // Inject highlight style into iframe
                $('<style>.ssc-picker-highlight { outline: 2px solid #f87171 !important; outline-offset: 2px; } .ssc-picker-active { cursor: crosshair; }</style>').appendTo(frameDoc.find('head'));

                frameBody.on('mousemove', function(e) {
                    if (!pickerActive) return;
                    const target = $(frameDoc[0].elementFromPoint(e.clientX, e.clientY));
                    if (lastHovered && !lastHovered.is(target)) {
                        lastHovered.removeClass('ssc-picker-highlight');
                    }
                    target.addClass('ssc-picker-highlight');
                    lastHovered = target;
                });
                
                frameBody.on('click', function(e) {
                    if (!pickerActive) return;
                    e.preventDefault();
                    e.stopPropagation();
                    if (lastHovered) {
                        const targetNode = lastHovered[0];
                        // Si la cible est un noeud de texte, prendre son parent.
                        const elementToSelect = targetNode.nodeType === 3 ? targetNode.parentNode : targetNode;
                        const selector = generateCssSelector(elementToSelect);
                        $('#ssc-picked-selector').val(selector);
                        lastHovered.removeClass('ssc-picker-highlight');
                    }
                    pickerToggle.click();
                });
            } catch(e) { console.warn(__('Impossible d\'accéder au contenu de l\'iframe. Assurez-vous que l\'URL chargée est sur le même domaine que votre page d\'administration WordPress.', 'supersede-css-jlg')); }
        });

        $('#ssc-preview-load').on('click', function(e) {
            e.preventDefault();
            const rawValue = (urlField.val() || '').trim();

            if (!rawValue) {
                if (!e.isTrigger) {
                    notifyInvalidUrl(__('Veuillez saisir une URL valide pour l\'aperçu.', 'supersede-css-jlg'));
                    if (lastValidPreviewUrl) {
                        urlField.val(lastValidPreviewUrl);
                    }
                }
                return;
            }

            let parsedUrl;
            try {
                parsedUrl = new URL(rawValue, window.location.origin);
            } catch (error) {
                if (!e.isTrigger) {
                    notifyInvalidUrl(__('URL invalide. Veuillez saisir une adresse commençant par http:// ou https://', 'supersede-css-jlg'));
                    if (lastValidPreviewUrl) {
                        urlField.val(lastValidPreviewUrl);
                    }
                }
                return;
            }

            if (!/^https?:$/i.test(parsedUrl.protocol)) {
                if (!e.isTrigger) {
                    notifyInvalidUrl(__('Seules les URL http et https sont autorisées.', 'supersede-css-jlg'));
                    if (lastValidPreviewUrl) {
                        urlField.val(lastValidPreviewUrl);
                    }
                }
                return;
            }

            const normalizedUrl = parsedUrl.href;
            previewFrame.attr('src', normalizedUrl);
            urlField.val(normalizedUrl);
            lastValidPreviewUrl = normalizedUrl;
        }).trigger('click');
        $('.ssc-responsive-toggles button').on('click', function() {
             $(this).addClass('button-primary').siblings().removeClass('button-primary');
             const widths = { desktop: '100%', tablet: '768px', mobile: '375px' };
             $('#ssc-preview-frame').css('max-width', widths[$(this).data('vp')]);
        });

        if (previewToggleButton.length && previewColumn.length && previewLayoutQuery) {
            const showLabel = previewToggleButton.data('show') || __('Afficher l\'aperçu', 'supersede-css-jlg');
            const hideLabel = previewToggleButton.data('hide') || __('Masquer l\'aperçu', 'supersede-css-jlg');

            const setPreviewVisibility = (visible) => {
                previewVisible = visible;
                previewColumn.toggleClass('is-hidden', !visible);
                previewToggleButton.text(visible ? hideLabel : showLabel);
                previewToggleButton.attr('aria-expanded', visible ? 'true' : 'false');
            };

            const handleLayoutChange = (event) => {
                if (event.matches) {
                    setPreviewVisibility(false);
                } else {
                    setPreviewVisibility(true);
                }
            };

            previewToggleButton.on('click', function() {
                if (!previewLayoutQuery.matches) return;
                setPreviewVisibility(!previewVisible);
            });

            handleLayoutChange(previewLayoutQuery);
            if (typeof previewLayoutQuery.addEventListener === 'function') {
                previewLayoutQuery.addEventListener('change', handleLayoutChange);
            } else if (typeof previewLayoutQuery.addListener === 'function') {
                previewLayoutQuery.addListener(handleLayoutChange);
            }
        }
    });
})(jQuery);
