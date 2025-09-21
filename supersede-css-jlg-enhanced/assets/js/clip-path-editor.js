(function($) {
    function applyClipPath() {
        const presetValue = $('#ssc-clip-preset').val();
        $('#ssc-clip-preview').css('clip-path', presetValue);
        
        const css = `.votre-element {\n  clip-path: ${presetValue};\n}`;
        $('#ssc-clip-css').text(css);
    }

    function updatePreviewSize() {
        const size = $('#ssc-clip-preview-size').val();
        $('#ssc-clip-preview').css({
            'width': size + 'px',
            'height': size + 'px'
        });
        $('#ssc-clip-size-val').text(size + 'px');
    }

    $(document).ready(function() {
        if(!$('#ssc-clip-preset').length) return;

        $('#ssc-clip-preset').on('change', applyClipPath);
        $('#ssc-clip-preview-size').on('input', updatePreviewSize);

        $('#ssc-clip-copy').on('click', () => {
            window.sscCopyToClipboard($('#ssc-clip-css').text(), {
                successMessage: 'CSS du clip-path copié !',
                errorMessage: 'Impossible de copier le CSS du clip-path.'
            }).catch(() => {});
        });

        applyClipPath();
        updatePreviewSize();
    });
})(jQuery);