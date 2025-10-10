(function($) {
    const selectors = {
        cols: '#ssc-grid-cols',
        gap: '#ssc-grid-gap',
        colsValue: '#ssc-grid-cols-val',
        gapValue: '#ssc-grid-gap-val',
        cssOutput: '#ssc-grid-css',
        preview: '#ssc-grid-preview',
        templateButtons: '.ssc-grid-preview-template',
        surfaceButtons: '.ssc-grid-preview-surface',
        templateName: '#ssc-grid-preview-template-name',
        templateDescription: '#ssc-grid-preview-template-description',
        liveRegion: '#ssc-grid-preview-live',
        colsMeta: '#ssc-grid-preview-cols-meta',
        gapMeta: '#ssc-grid-preview-gap-meta'
    };

    const parseNumber = (value, fallback) => {
        const parsed = parseInt(value, 10);
        return Number.isNaN(parsed) ? fallback : parsed;
    };

    $(document).ready(function() {
        const $colsInput = $(selectors.cols);
        if (!$colsInput.length) {
            return;
        }

        const $gapInput = $(selectors.gap);
        const $colsValue = $(selectors.colsValue);
        const $gapValue = $(selectors.gapValue);
        const $cssOutput = $(selectors.cssOutput);
        const $preview = $(selectors.preview);
        const $templateButtons = $(selectors.templateButtons);
        const $surfaceButtons = $(selectors.surfaceButtons);
        const $templateName = $(selectors.templateName);
        const $templateDescription = $(selectors.templateDescription);
        const $liveRegion = $(selectors.liveRegion);
        const $colsMeta = $(selectors.colsMeta);
        const $gapMeta = $(selectors.gapMeta);
        const announceTemplate = $preview.data('announce-template') || '';

        let currentTemplate = null;

        const renderPreviewContent = cols => {
            const templateId = currentTemplate ? `#ssc-grid-template-${currentTemplate}` : '';
            $preview.empty();

            if (templateId) {
                const template = document.querySelector(templateId);
                if (template && template.content) {
                    $preview.append(template.content.cloneNode(true));
                    return;
                }
            }

            const fallbackCount = Math.min(12, cols * 2);
            for (let i = 1; i <= fallbackCount; i++) {
                $preview.append(`<div class="ssc-grid-preview-cell">${i}</div>`);
            }
        };

        const updatePreviewDimensions = (cols, gap) => {
            const previewElement = $preview.get(0);
            if (!previewElement) {
                return;
            }

            previewElement.style.setProperty('--ssc-grid-preview-columns', cols);
            previewElement.style.setProperty('--ssc-grid-preview-gap', `${gap}px`);
        };

        const announceGrid = (cols, gap, silent) => {
            if (silent || !announceTemplate || !$liveRegion.length) {
                return;
            }

            const message = announceTemplate
                .replace('%1$s', cols)
                .replace('%2$s', gap);

            $liveRegion.text(message);
        };

        const generateGrid = ({ silent = false } = {}) => {
            const cols = parseNumber($colsInput.val(), 1);
            const gap = parseNumber($gapInput.val(), 0);

            $colsValue.text(cols);
            $gapValue.text(`${gap}px`);

            const css = `
.ssc-grid-container {
  display: grid;
  grid-template-columns: repeat(${cols}, 1fr);
  gap: ${gap}px;
}`;
            $cssOutput.text(css.trim());

            updatePreviewDimensions(cols, gap);
            renderPreviewContent(cols);

            if ($colsMeta.length) {
                $colsMeta.text(cols);
            }

            if ($gapMeta.length) {
                $gapMeta.text(`${gap}px`);
            }

            announceGrid(cols, gap, silent);
        };

        const setTemplate = ($button, { silent = false } = {}) => {
            if (!$button || !$button.length) {
                return;
            }

            currentTemplate = $button.data('template') || null;

            $templateButtons.removeClass('is-active').attr('aria-pressed', 'false');
            $button.addClass('is-active').attr('aria-pressed', 'true');

            if ($templateName.length) {
                $templateName.text($button.data('template-label') || '');
            }

            if ($templateDescription.length) {
                $templateDescription.text($button.data('template-description') || '');
            }

            if (currentTemplate) {
                $preview.attr('data-template', currentTemplate);
            } else {
                $preview.removeAttr('data-template');
            }

            const recommendedCols = parseNumber($button.data('template-cols'), NaN);
            const recommendedGap = parseNumber($button.data('template-gap'), NaN);

            if (!Number.isNaN(recommendedCols)) {
                $colsInput.val(recommendedCols);
            }

            if (!Number.isNaN(recommendedGap)) {
                $gapInput.val(recommendedGap);
            }

            generateGrid({ silent: true });

            if (!silent) {
                const announce = $button.data('template-announce');
                if (announce && $liveRegion.length) {
                    $liveRegion.text(announce);
                }

                const toast = $button.data('template-toast');
                if (toast && window.sscToast) {
                    window.sscToast(toast);
                }
            }
        };

        const setSurface = ($button, { silent = false } = {}) => {
            if (!$button || !$button.length) {
                return;
            }

            const surface = $button.data('surface');

            $surfaceButtons.removeClass('is-active').attr('aria-pressed', 'false');
            $button.addClass('is-active').attr('aria-pressed', 'true');

            $preview.removeClass('ssc-grid-preview--surface-light ssc-grid-preview--surface-dark');
            if (surface === 'dark') {
                $preview.addClass('ssc-grid-preview--surface-dark');
            } else {
                $preview.addClass('ssc-grid-preview--surface-light');
            }

            if (!silent) {
                const announce = $button.data('surface-announce');
                if (announce && $liveRegion.length) {
                    $liveRegion.text(announce);
                }
            }
        };

        $colsInput.on('input', () => generateGrid());
        $gapInput.on('input', () => generateGrid());

        $templateButtons.on('click', function() {
            setTemplate($(this));
        });

        $surfaceButtons.on('click', function() {
            setSurface($(this));
        });

        const $initialTemplate = $templateButtons.filter('.is-active').first().length
            ? $templateButtons.filter('.is-active').first()
            : $templateButtons.first();
        if ($initialTemplate.length) {
            setTemplate($initialTemplate, { silent: true });
        }

        const $initialSurface = $surfaceButtons.filter('.is-active').first().length
            ? $surfaceButtons.filter('.is-active').first()
            : $surfaceButtons.first();
        if ($initialSurface.length) {
            setSurface($initialSurface, { silent: true });
        }

        generateGrid({ silent: true });
    });
})(jQuery);
