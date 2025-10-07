(function($) {
    const presets = {
        bounce: {
            name: 'ssc-bounce',
            keyframes: `
  0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
  40% { transform: translateY(-30px); }
  60% { transform: translateY(-15px); }`
        },
        pulse: {
            name: 'ssc-pulse',
            keyframes: `
  0% { transform: scale(1); }
  50% { transform: scale(1.1); }
  100% { transform: scale(1); }`
        },
        'fade-in': {
            name: 'ssc-fade-in',
            keyframes: `
  from { opacity: 0; }
  to { opacity: 1; }`
        },
        'slide-in-left': {
            name: 'ssc-slide-in-left',
            keyframes: `
  from { transform: translateX(-100%); opacity: 0; }
  to { transform: translateX(0); opacity: 1; }`
        }
    };

    const previewTargets = {
        card: '#ssc-anim-preview-card',
        badge: '#ssc-anim-preview-badge',
        avatar: '#ssc-anim-preview-avatar',
        cta: '#ssc-anim-preview-cta'
    };
    const animationClasses = Object.values(presets).map(preset => preset.name);
    let currentTargetKey = 'card';

    function resetAnimationTargets() {
        $('.ssc-anim-target').each(function() {
            const $el = $(this);
            const preserved = ($el.attr('class') || '')
                .split(/\s+/)
                .filter(cls => cls && cls !== 'ssc-animated' && !animationClasses.includes(cls));
            $el.attr('class', preserved.join(' '));
        });
    }

    function applyPresetToTarget(selector, preset) {
        const $target = $(selector);
        if (!$target.length) {
            return;
        }

        resetAnimationTargets();

        const originalId = $target.attr('id') || '';
        const preservedClasses = ($target.attr('class') || '')
            .split(/\s+/)
            .filter(cls => cls && cls !== 'ssc-animated' && !animationClasses.includes(cls));

        const $clone = $target.clone(false);
        $clone.attr('id', originalId);
        $clone.attr('class', preservedClasses.join(' '));

        $target.replaceWith($clone);

        const $newTarget = $(selector);
        $newTarget.addClass(['ssc-animated', preset.name]);
    }

    function generateAnimationCSS() {
        const presetKey = $('#ssc-anim-preset').val();
        const duration = $('#ssc-anim-duration').val();
        const preset = presets[presetKey];
        if (!preset) {
            return;
        }

        $('#ssc-anim-duration-val').text(duration + 's');

        const css = `
@keyframes ${preset.name} {${preset.keyframes}
}

.ssc-animated.${preset.name} {
  animation-name: ${preset.name};
  animation-duration: ${duration}s;
  animation-fill-mode: both;
}`;
        
        $('#ssc-anim-css').text(css.trim());

        let styleTag = $('#ssc-anim-live-style');
        if (!styleTag.length) {
            styleTag = $('<style id="ssc-anim-live-style"></style>').appendTo('head');
        }
        styleTag.text(css);
        
        const selector = previewTargets[currentTargetKey] || previewTargets.card;
        applyPresetToTarget(selector, preset);
    }

    $(document).ready(function() {
        if (!$('#ssc-anim-preset').length) return; // Ne rien faire si on n'est pas sur la bonne page

        $('#ssc-anim-preset, #ssc-anim-duration').on('input', generateAnimationCSS);

        $('input[name="ssc-anim-target"]').on('change', function() {
            const selected = $(this).val();
            if (typeof selected === 'string' && previewTargets[selected]) {
                currentTargetKey = selected;
                generateAnimationCSS();
            }
        });

        const $stage = $('#ssc-anim-preview-stage');
        const $surfaceButtons = $('.ssc-preview-device-toggle');
        $surfaceButtons.on('click', function() {
            const $button = $(this);
            const surface = $button.data('surface');
            if (!surface) {
                return;
            }

            $surfaceButtons.attr('aria-pressed', 'false').removeClass('is-active');
            $button.attr('aria-pressed', 'true').addClass('is-active');
            $stage.attr('data-surface', surface);
        });

        $('.ssc-preview-bg-toggle').on('click', function() {
            const $button = $(this);
            const isDark = $stage.toggleClass('is-dark').hasClass('is-dark');
            $button.attr('aria-pressed', isDark ? 'true' : 'false');
        });

        $('#ssc-anim-copy').on('click', () => {
            window.sscCopyToClipboard($('#ssc-anim-css').text(), {
                successMessage: 'CSS de l\'animation copié !',
                errorMessage: 'Impossible de copier le CSS de l\'animation.'
            }).catch(() => {});
        });

        const $applyButton = $('#ssc-anim-apply');
        $applyButton.on('click', () => {
            const css = $('#ssc-anim-css').text();
            const originalText = $applyButton.text();

            $applyButton
                .prop('disabled', true)
                .attr('aria-disabled', 'true')
                .text('Application…');

            $.ajax({
                url: SSC.rest.root + 'save-css',
                method: 'POST',
                data: { css, append: true, _wpnonce: SSC.rest.nonce },
                beforeSend: x => x.setRequestHeader('X-WP-Nonce', SSC.rest.nonce)
            })
                .done(() => window.sscToast('Animation appliquée !'))
                .fail((jqXHR, textStatus, errorThrown) => {
                    const errorMessage = 'Impossible d\'appliquer l\'animation.';
                    console.error(errorMessage, { jqXHR, textStatus, errorThrown });
                    window.sscToast(errorMessage, { politeness: 'assertive' });
                })
                .always(() => {
                    $applyButton
                        .prop('disabled', false)
                        .removeAttr('aria-disabled')
                        .text(originalText);
                });
        });

        generateAnimationCSS();
    });
})(jQuery);