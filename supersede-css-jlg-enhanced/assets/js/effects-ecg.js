(function($) {
    const fallbackI18n = {
        __: (text) => text,
        sprintf: (format, ...args) => {
            let index = 0;
            return format.replace(/%([0-9]+\$)?[sd]/g, (match, position) => {
                if (position) {
                    const explicitIndex = parseInt(position, 10) - 1;
                    return typeof args[explicitIndex] !== 'undefined' ? args[explicitIndex] : '';
                }
                const value = typeof args[index] !== 'undefined' ? args[index] : '';
                index += 1;
                return value;
            });
        },
    };

    const hasI18n = typeof window !== 'undefined' && window.wp && window.wp.i18n;
    const { __ } = hasI18n ? window.wp.i18n : fallbackI18n;

    const motionPreference = (() => {
        const listeners = new Set();
        const supportsMatchMedia = typeof window !== 'undefined' && typeof window.matchMedia === 'function';
        const mediaQuery = supportsMatchMedia ? window.matchMedia('(prefers-reduced-motion: reduce)') : null;
        let matches = mediaQuery ? mediaQuery.matches : false;

        function handleChange(event) {
            matches = event.matches;
            listeners.forEach((listener) => listener(matches));
        }

        if (mediaQuery) {
            if (typeof mediaQuery.addEventListener === 'function') {
                mediaQuery.addEventListener('change', handleChange);
            } else if (typeof mediaQuery.addListener === 'function') {
                mediaQuery.addListener(handleChange);
            }
        }

        return {
            matches: () => matches,
            subscribe(listener) {
                if (typeof listener !== 'function') {
                    return () => {};
                }
                listeners.add(listener);
                return () => listeners.delete(listener);
            },
        };
    })();

    function shouldReduceMotion() {
        return motionPreference.matches();
    }

    function onMotionPreferenceChange(listener) {
        return motionPreference.subscribe(listener);
    }

    function showToast(message, options = {}) {
        if (!message) {
            return;
        }
        if (typeof window.sscToast === 'function') {
            window.sscToast(message, options);
        }
    }

    $(document).ready(() => {
        if (!$('#ssc-ecg-app').length) {
            return;
        }

        const paths = {
            stable: 'M0,30 L100,30 L110,18 L120,42 L130,26 L140,30 L240,30 L250,20 L260,40 L270,28 L280,30 L400,30',
            fast: 'M0,30 L60,30 L70,8 L80,52 L90,18 L100,30 L160,30 L170,12 L180,48 L190,22 L200,30 L400,30',
            critical: 'M0,30 L40,30 L50,5 L60,55 L70,15 L80,30 L120,30 L130,2 L140,58 L150,12 L160,30 L400,30',
        };

        if (typeof wp !== 'undefined' && wp.media) {
            let frame;
            $('#ssc-ecg-upload-btn').on('click', function(event) {
                event.preventDefault();
                if (frame) {
                    frame.open();
                    return;
                }
                frame = wp.media({ title: __('Choisir une image', 'supersede-css-jlg'), multiple: false });
                frame.on('select', function() {
                    const selection = frame.state().get('selection').first();
                    if (!selection) {
                        return;
                    }
                    const attachment = selection.toJSON();
                    if (attachment && attachment.url) {
                        $('#ssc-ecg-logo-preview').attr('src', attachment.url).show();
                    }
                });
                frame.open();
            });
        }

        function generateECGCSS() {
            const preset = $('#ssc-ecg-preset').val();
            const color = $('#ssc-ecg-color').val();
            const top = $('#ssc-ecg-top').val();
            const zIndex = $('#ssc-ecg-z-index').val();
            const speed = preset === 'fast' ? '1.2s' : (preset === 'critical' ? '0.8s' : '2s');
            const logoSize = $('#ssc-ecg-logo-size').val();
            const reduceMotion = shouldReduceMotion();

            $('#ssc-ecg-top-val').text(`${top}%`);
            $('#ssc-ecg-z-index-val').text(zIndex);
            $('#ssc-ecg-logo-size-val').text(`${logoSize}px`);
            $('#ssc-ecg-logo-preview').css({ 'max-width': `${logoSize}px`, 'max-height': `${logoSize}px` });

            const $previewPath = $('#ssc-ecg-preview-path');
            const $previewSvg = $('#ssc-ecg-preview-svg');

            if (!$('style#ssc-ecg-anim').length) {
                $('<style id="ssc-ecg-anim">@keyframes ssc-ecg-line{to{stroke-dashoffset:0}}</style>').appendTo('head');
            }

            $previewSvg.css({ top: `${top}%`, transform: 'translateY(-50%)', 'z-index': zIndex });
            const dashOffset = reduceMotion ? 0 : 1000;
            const animationValue = reduceMotion ? 'none' : `ssc-ecg-line ${speed} linear infinite`;
            $previewPath.attr('d', paths[preset] || paths.stable).css({
                stroke: color,
                'stroke-dasharray': 1000,
                'stroke-dashoffset': dashOffset,
                animation: animationValue,
                filter: `drop-shadow(0 0 5px ${color})`,
            });

            const css = `@keyframes ssc-ecg-line{to{stroke-dashoffset:0}}
.ssc-ecg-container { position: relative; }
.ssc-ecg-line-svg { position: absolute; top: ${top}%; left: 0; width: 100%; height: auto; transform: translateY(-50%); z-index: ${zIndex}; }
.ssc-ecg-path-animated { fill:none; stroke:${color}; stroke-width:2; stroke-dasharray:1000; stroke-dashoffset:1000; animation:ssc-ecg-line ${speed} linear infinite; filter:drop-shadow(0 0 5px ${color}) }
@media (prefers-reduced-motion: reduce) {
  .ssc-ecg-path-animated {
    animation: none;
    stroke-dashoffset: 0;
  }
}`;
            $('#ssc-ecg-css').text(css.trim());
        }

        $('#ssc-ecg-preset, #ssc-ecg-color, #ssc-ecg-top, #ssc-ecg-logo-size, #ssc-ecg-z-index').on('input', generateECGCSS);

        const $applyButton = $('#ssc-ecg-apply');
        const applyingLabel = __('Application…', 'supersede-css-jlg');
        $applyButton.on('click', () => {
            const css = $('#ssc-ecg-css').text();
            if (!css) {
                return;
            }

            const errorMessage = __('Impossible d\'appliquer l\'effet ECG.', 'supersede-css-jlg');
            const originalText = $applyButton.text();

            $applyButton.prop('disabled', true).attr('aria-disabled', 'true').text(applyingLabel);

            $.ajax({
                url: SSC.rest.root + 'save-css',
                method: 'POST',
                data: { css, append: true, _wpnonce: SSC.rest.nonce },
                beforeSend: (xhr) => xhr.setRequestHeader('X-WP-Nonce', SSC.rest.nonce),
            })
                .done(() => showToast(__('Effet ECG appliqué !', 'supersede-css-jlg')))
                .fail((jqXHR, textStatus, errorThrown) => {
                    console.error(errorMessage, { jqXHR, textStatus, errorThrown });
                    showToast(errorMessage, { politeness: 'assertive' });
                })
                .always(() => {
                    $applyButton.prop('disabled', false).removeAttr('aria-disabled').text(originalText);
                });
        });

        generateECGCSS();
        onMotionPreferenceChange(generateECGCSS);
    });
})(jQuery);
