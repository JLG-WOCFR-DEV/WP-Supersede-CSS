(function($) {
    const fallbackI18n = {
        __: (text) => text,
        _x: (text) => text,
        _n: (single, plural, number) => (number === 1 ? single : plural),
        _nx: (single, plural, number) => (number === 1 ? single : plural),
        sprintf: (format, ...args) => {
            let argIndex = 0;
            return format.replace(/%(\d+\$)?[sd]/g, (match, position) => {
                if (position) {
                    const explicitIndex = parseInt(position, 10) - 1;
                    return typeof args[explicitIndex] !== 'undefined' ? args[explicitIndex] : '';
                }
                const value = args[argIndex];
                argIndex += 1;
                return typeof value !== 'undefined' ? value : '';
            });
        }
    };

    const hasI18n = typeof window !== 'undefined' && window.wp && window.wp.i18n;
    const { __, _x, _n, _nx, sprintf } = hasI18n ? window.wp.i18n : fallbackI18n;

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

        const showToast = (message) => {
            if (typeof window !== 'undefined' && typeof window.sscToast === 'function') {
                window.sscToast(message);
            } else if (typeof window !== 'undefined' && typeof window.alert === 'function') {
                window.alert(message);
            } else {
                console.log(message); // eslint-disable-line no-console
            }
        };

        const extractApiMessage = (payload, fallbackMessage) => {
            if (!payload) {
                return fallbackMessage;
            }

            if (typeof payload === 'string') {
                return payload;
            }

            if (payload.message && typeof payload.message === 'string') {
                return payload.message;
            }

            if (payload.data) {
                if (typeof payload.data === 'string') {
                    return payload.data;
                }

                if (payload.data.message && typeof payload.data.message === 'string') {
                    return payload.data.message;
                }
            }

            return fallbackMessage;
        };

        $('#ssc-save-css').on('click', function() {
            const $button = $(this);
            if ($button.prop('disabled')) {
                return;
            }

            const desktopCss = getEditorValue('desktop');
            const tabletCss = getEditorValue('tablet');
            const mobileCss = getEditorValue('mobile');
            const fullCss = getFullCss();
            const defaultSuccessMessage = __('CSS enregistré !', 'supersede-css-jlg');
            const defaultErrorMessage = __('Impossible d\'enregistrer le CSS. Vérifiez votre connexion et réessayez.', 'supersede-css-jlg');
            $button.prop('disabled', true);
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
                beforeSend: (xhr) => {
                    xhr.setRequestHeader('X-WP-Nonce', SSC.rest.nonce);
                }
            }).done((response) => {
                const message = extractApiMessage(response, defaultSuccessMessage);
                showToast(message);
            }).fail((jqXHR, textStatus, errorThrown) => {
                const responsePayload = jqXHR && jqXHR.responseJSON ? jqXHR.responseJSON : null;
                const networkMessage = textStatus === 'error' && !responsePayload && errorThrown ? errorThrown : null;
                const message = extractApiMessage(responsePayload || networkMessage, defaultErrorMessage);
                showToast(message);
            }).always(() => {
                $button.prop('disabled', false);
            });
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
            showToast(message);
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
        const responsiveButtons = $('.ssc-responsive-toggles button');
        const viewportLiveRegion = $('#ssc-viewport-status');
        const widthSlider = $('#ssc-viewport-width');
        const widthNumber = $('#ssc-viewport-width-number');
        const storageAvailable = (() => {
            try {
                if (typeof window === 'undefined' || !window.localStorage) {
                    return false;
                }
                const testKey = '__ssc_preview_test__';
                window.localStorage.setItem(testKey, '1');
                window.localStorage.removeItem(testKey);
                return true;
            } catch (error) {
                return false;
            }
        })();
        const widthStorageKey = 'sscPreviewCustomWidth';
        const presetStorageKey = 'sscPreviewViewport';
        let currentWidth = null;

        const parseWidthValue = (value) => {
            if (typeof value === 'number') {
                return value;
            }
            if (typeof value === 'string') {
                if (!value.trim()) {
                    return Number.NaN;
                }
                const parsed = parseInt(value, 10);
                return Number.isNaN(parsed) ? Number.NaN : parsed;
            }
            if (typeof value === 'undefined' || value === null) {
                return Number.NaN;
            }
            const coerced = Number(value);
            return Number.isNaN(coerced) ? Number.NaN : coerced;
        };

        const clampWidth = (value) => {
            if (Number.isNaN(value)) {
                return Number.NaN;
            }
            let min = 320;
            let max = 1920;
            if (widthSlider.length) {
                const sliderMin = parseInt(widthSlider.attr('min'), 10);
                const sliderMax = parseInt(widthSlider.attr('max'), 10);
                if (!Number.isNaN(sliderMin)) {
                    min = sliderMin;
                }
                if (!Number.isNaN(sliderMax)) {
                    max = sliderMax;
                }
            } else if (widthNumber.length) {
                const inputMin = parseInt(widthNumber.attr('min'), 10);
                const inputMax = parseInt(widthNumber.attr('max'), 10);
                if (!Number.isNaN(inputMin)) {
                    min = inputMin;
                }
                if (!Number.isNaN(inputMax)) {
                    max = inputMax;
                }
            }
            return Math.min(Math.max(value, min), max);
        };

        const announceCustomWidth = (width) => {
            if (!viewportLiveRegion.length) {
                return;
            }
            viewportLiveRegion.text(sprintf(__('Largeur personnalisée définie sur %d px.', 'supersede-css-jlg'), width));
        };

        const applyWidth = (rawWidth, options = {}) => {
            const { announce = false, persist = false } = options;
            if (!previewFrame.length) {
                return null;
            }
            const parsed = parseWidthValue(rawWidth);
            if (Number.isNaN(parsed)) {
                return null;
            }
            const clamped = clampWidth(parsed);
            currentWidth = clamped;
            previewFrame.css('max-width', `${clamped}px`);
            if (widthSlider.length && parseWidthValue(widthSlider.val()) !== clamped) {
                widthSlider.val(String(clamped));
            }
            if (widthNumber.length && parseWidthValue(widthNumber.val()) !== clamped) {
                widthNumber.val(String(clamped));
            }
            if (persist && storageAvailable) {
                try {
                    window.localStorage.setItem(widthStorageKey, String(clamped));
                } catch (error) {
                    // Ignore storage failures
                }
            }
            if (announce) {
                announceCustomWidth(clamped);
            }
            return clamped;
        };

        const setActiveViewport = ($button, announce = true) => {
            responsiveButtons.each(function() {
                const isActive = $button && $button.length && this === $button.get(0);
                $(this)
                    .toggleClass('button-primary', isActive)
                    .attr('aria-pressed', isActive ? 'true' : 'false');
            });

            if ($button && $button.length) {
                const preset = $button.data('vp');
                if (storageAvailable) {
                    try {
                        window.localStorage.setItem(presetStorageKey, preset);
                    } catch (error) {
                        // Ignore storage failures
                    }
                }
                if (announce && viewportLiveRegion.length) {
                    const label = $button.data('label') || $.trim($button.text());
                    const widthValue = clampWidth(parseWidthValue($button.data('width')));
                    if (!Number.isNaN(widthValue)) {
                        viewportLiveRegion.text(
                            sprintf(__('Vue %1$s sélectionnée (%2$d px).', 'supersede-css-jlg'), label, widthValue)
                        );
                    } else {
                        viewportLiveRegion.text(sprintf(__('Vue %s sélectionnée.', 'supersede-css-jlg'), label));
                    }
                }
            } else if (storageAvailable) {
                try {
                    window.localStorage.setItem(presetStorageKey, 'custom');
                } catch (error) {
                    // Ignore storage failures
                }
            }
        };

        const initializeViewport = () => {
            let storedPreset = null;
            let storedWidth = null;
            if (storageAvailable) {
                try {
                    storedPreset = window.localStorage.getItem(presetStorageKey);
                } catch (error) {
                    storedPreset = null;
                }
                try {
                    storedWidth = parseWidthValue(window.localStorage.getItem(widthStorageKey));
                } catch (error) {
                    storedWidth = null;
                }
            }

            let $initialButton = storedPreset ? responsiveButtons.filter(`[data-vp="${storedPreset}"]`).first() : $();
            let initialWidth = null;

            if ($initialButton.length) {
                const presetWidth = parseWidthValue($initialButton.data('width'));
                if (!Number.isNaN(presetWidth)) {
                    initialWidth = clampWidth(presetWidth);
                }
            }

            if (initialWidth === null || Number.isNaN(initialWidth)) {
                if (!Number.isNaN(storedWidth)) {
                    initialWidth = clampWidth(storedWidth);
                    $initialButton = $();
                }
            }

            if (initialWidth === null || Number.isNaN(initialWidth)) {
                const fallbackButton = responsiveButtons.filter('[data-vp="desktop"]').first();
                if (fallbackButton.length) {
                    const fallbackWidth = parseWidthValue(fallbackButton.data('width'));
                    if (!Number.isNaN(fallbackWidth)) {
                        initialWidth = clampWidth(fallbackWidth);
                        $initialButton = fallbackButton;
                    }
                }
            }

            if (initialWidth === null || Number.isNaN(initialWidth)) {
                const sliderDefault = widthSlider.length ? parseWidthValue(widthSlider.attr('value')) : Number.NaN;
                const numberDefault = widthNumber.length ? parseWidthValue(widthNumber.attr('value')) : Number.NaN;
                const fallbackWidth = !Number.isNaN(sliderDefault) ? sliderDefault : numberDefault;
                initialWidth = Number.isNaN(fallbackWidth) ? 1024 : clampWidth(fallbackWidth);
                $initialButton = $();
            }

            const appliedWidth = applyWidth(initialWidth, { announce: false, persist: false });

            if ($initialButton.length) {
                setActiveViewport($initialButton, false);
                if (viewportLiveRegion.length) {
                    const label = $initialButton.data('label') || $.trim($initialButton.text());
                    const widthForAnnouncement = appliedWidth !== null
                        ? appliedWidth
                        : clampWidth(parseWidthValue($initialButton.data('width')));
                    if (!Number.isNaN(widthForAnnouncement)) {
                        viewportLiveRegion.text(
                            sprintf(
                                __('Vue %1$s sélectionnée (%2$d px).', 'supersede-css-jlg'),
                                label,
                                widthForAnnouncement
                            )
                        );
                    } else {
                        viewportLiveRegion.text(sprintf(__('Vue %s sélectionnée.', 'supersede-css-jlg'), label));
                    }
                }
            } else {
                setActiveViewport(null, false);
                if (appliedWidth !== null && !Number.isNaN(appliedWidth) && viewportLiveRegion.length) {
                    announceCustomWidth(appliedWidth);
                }
            }
        };

        initializeViewport();

        responsiveButtons.on('click', function() {
            const $button = $(this);
            const applied = applyWidth($button.data('width'), { announce: false, persist: true });
            if (applied !== null) {
                setActiveViewport($button, true);
            }
        });

        if (widthSlider.length) {
            widthSlider.on('input', function() {
                const applied = applyWidth($(this).val(), { announce: false, persist: false });
                if (applied !== null) {
                    setActiveViewport(null, false);
                }
            });
            widthSlider.on('change', function() {
                const applied = applyWidth($(this).val(), { announce: true, persist: true });
                if (applied !== null) {
                    setActiveViewport(null, false);
                } else if (currentWidth !== null) {
                    widthSlider.val(String(currentWidth));
                }
            });
        }

        if (widthNumber.length) {
            widthNumber.on('input', function() {
                const value = $(this).val();
                if (typeof value !== 'string' || !value.trim()) {
                    return;
                }
                const applied = applyWidth(value, { announce: false, persist: false });
                if (applied !== null) {
                    setActiveViewport(null, false);
                }
            });

            const commitNumberChange = (value) => {
                const applied = applyWidth(value, { announce: true, persist: true });
                if (applied !== null) {
                    setActiveViewport(null, false);
                    return true;
                }
                if (currentWidth !== null) {
                    widthNumber.val(String(currentWidth));
                }
                return false;
            };

            widthNumber.on('change', function() {
                const value = $(this).val();
                if (typeof value !== 'string' || !value.trim()) {
                    if (currentWidth !== null) {
                        $(this).val(String(currentWidth));
                    }
                    return;
                }
                commitNumberChange(value);
            });

            widthNumber.on('blur', function() {
                const value = $(this).val();
                if (typeof value !== 'string' || value.trim()) {
                    return;
                }
                if (currentWidth !== null) {
                    $(this).val(String(currentWidth));
                }
            });
        }

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
