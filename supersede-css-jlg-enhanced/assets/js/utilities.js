(function($) {
    let editors = {};
    let pickerActive = false;

    function initCodeMirrors() {
        ['desktop', 'tablet', 'mobile'].forEach(view => {
            const textarea = document.getElementById(`ssc-css-editor-${view}`);
            if (textarea) {
                editors[view] = CodeMirror.fromTextArea(textarea, {
                    lineNumbers: true, mode: 'css', theme: 'material-darker', lineWrapping: true,
                });
            }
        });
    }

    function getEditorValue(view) {
        return editors[view] ? editors[view].getValue() : '';
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

        $('.ssc-editor-tab').on('click', function() {
            const tab = $(this).data('tab');
            $('.ssc-editor-tab, .ssc-editor-panel').removeClass('active');
            $(this).addClass('active');
            $(`#ssc-editor-panel-${tab}`).addClass('active');

            // Rafraîchir l'éditeur si c'est un onglet d'édition
            if (editors[tab]) {
                editors[tab].refresh();
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
            }).done(() => window.sscToast('CSS enregistré !'));
        });

        const pickerToggle = $('#ssc-element-picker-toggle');
        const previewFrame = $('#ssc-preview-frame');
        let lastHovered;

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
            } catch(e) { console.warn("Impossible d'accéder au contenu de l'iframe. Assurez-vous que l'URL chargée est sur le même domaine que votre page d'administration WordPress."); }
        });
        
        $('#ssc-preview-load').on('click', () => $('#ssc-preview-frame').attr('src', $('#ssc-preview-url').val())).trigger('click');
        $('.ssc-responsive-toggles button').on('click', function() {
             $(this).addClass('button-primary').siblings().removeClass('button-primary');
             const widths = { desktop: '100%', tablet: '768px', mobile: '375px' };
             $('#ssc-preview-frame').css('max-width', widths[$(this).data('vp')]);
        });
    });
})(jQuery);
