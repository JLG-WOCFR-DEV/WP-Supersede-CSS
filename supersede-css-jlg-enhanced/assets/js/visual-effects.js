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
    const { __, sprintf } = hasI18n ? window.wp.i18n : fallbackI18n;

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

    $(document).ready(function() {
        if (!$('.ssc-ve-tabs').length) return;

        const $tabList = $('.ssc-ve-tabs');
        const $tabs = $tabList.find('.ssc-ve-tab');
        const $panels = $('.ssc-ve-panel');

        function setActiveTab($newTab, focus = false) {
            if (!$newTab.length) return;
            const panelId = $newTab.attr('aria-controls');
            const $panel = panelId ? $(`#${panelId}`) : $();

            $tabs.each(function() {
                $(this).removeClass('active').attr({
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

        initCRTEffect();
        initBackgrounds();
        initECG();
    });

    // --- Module 1: Effet CRT (Scanline) ---
    function initCRTEffect() {
        const canvas = document.getElementById('ssc-crt-canvas');
        if (!canvas) return;
        const ctx = canvas.getContext('2d');
        let time = 0;
        let animationFrameId = null;

        const settings = {
            scanlineColor: '#00ff00', scanlineOpacity: 0.4, scanlineSpeed: 0.5,
            noiseIntensity: 0.1, chromaticAberration: 1
        };

        function hexToRgb(hex) {
            let r = 0, g = 0, b = 0;
            if (hex.length == 4) {
                r = "0x" + hex[1] + hex[1];
                g = "0x" + hex[2] + hex[2];
                b = "0x" + hex[3] + hex[3];
            } else if (hex.length == 7) {
                r = "0x" + hex[1] + hex[2];
                g = "0x" + hex[3] + hex[4];
                b = "0x" + hex[5] + hex[6];
            }
            return {r: +r, g: +g, b: +b};
        }

        function ensureCanvasSize() {
            const width = canvas.offsetWidth;
            const height = canvas.offsetHeight;
            if (width === 0 || height === 0) {
                return false;
            }
            if (canvas.width !== width) {
                canvas.width = width;
            }
            if (canvas.height !== height) {
                canvas.height = height;
            }
            return true;
        }

        function renderFrame(frameTime) {
            if (!ensureCanvasSize()) {
                return false;
            }
            ctx.clearRect(0, 0, canvas.width, canvas.height);

            const imageData = ctx.createImageData(canvas.width, canvas.height);
            const data = imageData.data;
            for (let i = 0; i < data.length; i += 4) {
                const noise = Math.random() * settings.noiseIntensity * 255;
                data[i] = data[i + 1] = data[i + 2] = noise;
                data[i + 3] = 255;
            }
            ctx.putImageData(imageData, 0, 0);

            const baseColor = hexToRgb(settings.scanlineColor);
            const offsets = [
                { color: `rgba(255, 0, 0, ${settings.scanlineOpacity / 2})`, offset: settings.chromaticAberration },
                { color: `rgba(${baseColor.r}, ${baseColor.g}, ${baseColor.b}, ${settings.scanlineOpacity})`, offset: 0 },
                { color: `rgba(0, 0, 255, ${settings.scanlineOpacity / 2})`, offset: -settings.chromaticAberration }
            ];

            const scanlineOffset = (frameTime * settings.scanlineSpeed * 10) % 4;
            offsets.forEach(({ color, offset }) => {
                ctx.fillStyle = color;
                for (let y = scanlineOffset; y < canvas.height; y += 4) {
                    ctx.fillRect(offset, y, canvas.width, 2);
                }
            });

            return true;
        }

        function stopAnimation() {
            if (animationFrameId !== null) {
                window.cancelAnimationFrame(animationFrameId);
                animationFrameId = null;
            }
        }

        function drawStaticFrame() {
            stopAnimation();
            if (!renderFrame(0)) {
                window.setTimeout(() => {
                    if (shouldReduceMotion()) {
                        drawStaticFrame();
                    }
                }, 100);
            }
        }

        function step() {
            if (renderFrame(time)) {
                time += 0.016;
            }
        }

        function loop() {
            if (shouldReduceMotion()) {
                drawStaticFrame();
                return;
            }
            step();
            animationFrameId = window.requestAnimationFrame(loop);
        }

        function restartAnimation() {
            stopAnimation();
            time = 0;
            if (shouldReduceMotion()) {
                drawStaticFrame();
                return;
            }
            step();
            animationFrameId = window.requestAnimationFrame(loop);
        }

        $('.ssc-crt-control').on('input', function() {
            const prop = this.id;
            const value = $(this).is('input[type="color"]') ? $(this).val() : parseFloat($(this).val());
            settings[prop] = value;
            if (shouldReduceMotion()) {
                drawStaticFrame();
            }
        });

        restartAnimation();
        onMotionPreferenceChange(() => {
            restartAnimation();
        });
    }


    // --- Module 2: Fonds Animés (Corrigé) ---
    function initBackgrounds() {
        if(!$('#ssc-bg-type').length) return;

        const gradientDefaults = {
            angle: 135,
            speed: 10,
            stops: [
                { color: '#ee7752', position: 0 },
                { color: '#e73c7e', position: 33 },
                { color: '#23a6d5', position: 66 },
                { color: '#23d5ab', position: 100 },
            ],
        };

        let gradientStopIdCounter = 0;
        const gradientState = {
            angle: gradientDefaults.angle,
            stops: [],
        };
        let latestGradientResult = null;

        const $gradientStopsList = $('#ssc-gradient-stops-list');
        const $gradientErrors = $('#ssc-gradient-errors');
        const $applyButton = $('#ssc-bg-apply');
        const applyingLabel = __('Application…', 'supersede-css-jlg');

        const $presetNameInput = $('#ssc-bg-preset-name');
        const $savePresetButton = $('#ssc-bg-save-preset');
        const $presetsList = $('#ssc-bg-presets-list');
        const $presetsEmpty = $('#ssc-bg-presets-empty');
        const $copyCssButton = $('#ssc-bg-copy-css');
        const defaultEmptyMessage = $presetsEmpty.length ? $presetsEmpty.text() : '';

        const restInfo = typeof window !== 'undefined' && window.SSC && window.SSC.rest ? window.SSC.rest : null;
        const presetsEndpoint = restInfo ? `${restInfo.root}visual-effects-presets` : null;
        const restNonce = restInfo ? restInfo.nonce : null;

        const presetsState = {
            items: [],
            loading: false,
            activePresetId: null,
            activePresetName: '',
        };

        let applyLockedByValidation = false;
        let applyBusy = false;

        if (!$('style#ssc-stars-anim-style').length) {
            $('<style id="ssc-stars-anim-style">@keyframes ssc-stars-anim { from { transform: translateY(0px); } to { transform: translateY(-2000px); } }</style>').appendTo('head');
        }

        function clamp(value, min, max) {
            if (Number.isNaN(value)) return min;
            return Math.min(Math.max(value, min), max);
        }

        function clampPosition(value) {
            return Math.round(clamp(value, 0, 100));
        }

        function clampAngle(value) {
            return Math.round(clamp(value, 0, 360));
        }

        function showToast(message, options = {}) {
            if (!message) {
                return;
            }
            if (typeof window.sscToast === 'function') {
                window.sscToast(message, options);
            }
        }

        function updateApplyButtonState() {
            if (!$applyButton.length) {
                return;
            }
            const shouldDisable = applyLockedByValidation || applyBusy;
            $applyButton.prop('disabled', shouldDisable);
            if (shouldDisable) {
                $applyButton.attr('aria-disabled', 'true');
            } else {
                $applyButton.removeAttr('aria-disabled');
            }
        }

        function setValidationState(isValid) {
            applyLockedByValidation = !isValid;
            updateApplyButtonState();
        }

        function renderGradientStops() {
            if (!$gradientStopsList.length) return;

            const sortedStops = [...gradientState.stops].sort((a, b) => a.position - b.position);
            gradientState.stops = sortedStops;
            $gradientStopsList.empty();

            const colorLabel = __('Couleur', 'supersede-css-jlg');
            const positionLabel = __('Position', 'supersede-css-jlg');
            const removeLabel = __('Retirer', 'supersede-css-jlg');
            const removeAriaLabel = __('Supprimer cet arrêt', 'supersede-css-jlg');

            sortedStops.forEach((stop) => {
                const $item = $('<div>', {
                    class: 'ssc-gradient-stop',
                    role: 'listitem',
                    'data-stop-id': stop.id,
                });

                const $colorLabel = $('<label>').text(`${colorLabel} `);
                const $colorInput = $('<input>', {
                    type: 'color',
                    class: 'ssc-gradient-stop-color',
                    value: stop.color,
                });
                $colorLabel.append($colorInput);

                const $positionLabel = $('<label>').text(`${positionLabel} `);
                const $positionInput = $('<input>', {
                    type: 'number',
                    class: 'small-text ssc-gradient-stop-position',
                    min: 0,
                    max: 100,
                    step: 1,
                    value: stop.position,
                });
                $positionLabel.append($positionInput).append(document.createTextNode('%'));

                const $removeButton = $('<button>', {
                    type: 'button',
                    class: 'button-link-delete ssc-remove-gradient-stop',
                    text: removeLabel,
                    'aria-label': removeAriaLabel,
                });

                $item.append($colorLabel, $positionLabel, $removeButton);
                $gradientStopsList.append($item);
            });

            const disableRemoval = gradientState.stops.length <= 2;
            $gradientStopsList.find('.ssc-remove-gradient-stop').each(function() {
                $(this).prop('disabled', disableRemoval);
                if (disableRemoval) {
                    $(this).attr('aria-disabled', 'true');
                } else {
                    $(this).removeAttr('aria-disabled');
                }
            });
        }

        function addGradientStop(stop, options = {}) {
            const config = stop || {};
            gradientState.stops.push({
                id: ++gradientStopIdCounter,
                color: config.color || '#ffffff',
                position: clampPosition(typeof config.position === 'number' ? config.position : 50),
            });
            renderGradientStops();
            if (options.triggerUpdate !== false) {
                generateBackgroundCSS();
            }
        }

        function removeGradientStop(stopId) {
            if (gradientState.stops.length <= 2) {
                return;
            }
            gradientState.stops = gradientState.stops.filter((stop) => stop.id !== stopId);
            renderGradientStops();
            generateBackgroundCSS();
            markPresetAsDirty();
        }

        function updateGradientStop(stopId, updates) {
            const stop = gradientState.stops.find((item) => item.id === stopId);
            if (!stop) return;
            if (typeof updates.color === 'string') {
                stop.color = updates.color;
            }
            if (typeof updates.position === 'number' && !Number.isNaN(updates.position)) {
                stop.position = clampPosition(updates.position);
            }
        }

        function validateGradient() {
            const errors = [];
            if (gradientState.stops.length < 2) {
                errors.push(__('Ajoutez au moins deux arrêts de couleur pour créer un dégradé.', 'supersede-css-jlg'));
            }
            gradientState.stops.forEach((stop) => {
                if (stop.position < 0 || stop.position > 100) {
                    errors.push(__('Les positions doivent être comprises entre 0% et 100%.', 'supersede-css-jlg'));
                }
            });
            return errors;
        }

        function computeGradientCss() {
            const errors = validateGradient();
            if (errors.length) {
                return { errors, css: '', gradientString: '', stops: [], angle: gradientState.angle, speed: gradientDefaults.speed, keyframes: '' };
            }

            const sortedStops = [...gradientState.stops].sort((a, b) => a.position - b.position);
            const stopList = sortedStops.map((stop) => `${stop.color} ${stop.position}%`).join(', ');
            const angle = clampAngle(gradientState.angle);
            const speedValue = parseInt($('#gradientSpeed').val(), 10);
            const speed = Number.isNaN(speedValue) ? gradientDefaults.speed : speedValue;
            const gradientString = `linear-gradient(${angle}deg, ${stopList})`;
            const keyframes = `@keyframes ssc-gradient-anim { 0% { background-position: 0% 50%; } 50% { background-position: 100% 50%; } 100% { background-position: 0% 50%; } }`;
            const css = `${keyframes}
.ssc-bg-gradient {
  background: ${gradientString};
  background-size: 400% 400%;
  animation: ssc-gradient-anim ${speed}s ease infinite;
}
@media (prefers-reduced-motion: reduce) {
  .ssc-bg-gradient {
    animation: none;
  }
}`;

            return {
                errors,
                css,
                gradientString,
                stops: sortedStops,
                angle,
                speed,
                keyframes,
            };
        }

        function setDefaultGradientPreset() {
            gradientStopIdCounter = 0;
            gradientState.angle = gradientDefaults.angle;
            $('#gradientAngle').val(gradientDefaults.angle);
            $('#gradientSpeed').val(gradientDefaults.speed);
            gradientState.stops = [];
            gradientDefaults.stops.forEach((stop) => addGradientStop(stop, { triggerUpdate: false }));
            renderGradientStops();
        }

        function generateBackgroundCSS() {
            const type = $('#ssc-bg-type').val();
            $('#ssc-bg-controls-stars').toggle(type === 'stars');
            $('#ssc-bg-controls-gradient').toggle(type === 'gradient');
            let css = '';
            const preview = $('#ssc-bg-preview');
            preview.empty().removeAttr('style').css('animation', '').removeClass('ssc-bg-stars ssc-bg-gradient');
            const reduceMotion = shouldReduceMotion();

            if (type === 'stars') {
                setValidationState(true);
                latestGradientResult = null;
                if ($gradientErrors.length) {
                    $gradientErrors.hide().empty();
                }

                const color = $('#starColor').val();
                const count = parseInt($('#starCount').val(), 10);
                const keyframes = `@keyframes ssc-stars-anim { from { transform: translateY(0); } to { transform: translateY(-2000px); } }`;
                const animationDuration = 50;
                const boxShadows = [];
                for (let i = 0; i < count; i++) {
                    boxShadows.push(`${Math.random() * 2000}px ${Math.random() * 2000}px ${color}`);
                }
                const reduceMotionBlock = `@media (prefers-reduced-motion: reduce) {
  .ssc-bg-stars::after {
    animation: none;
    transform: none;
  }
}`;
                css = `${keyframes}
.ssc-bg-stars {
  background: #000000;
  position: relative;
  overflow: hidden;
}
.ssc-bg-stars::after {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  width: 1px;
  height: 1px;
  background: transparent;
  box-shadow: ${boxShadows.join(', ')};
  animation: ssc-stars-anim ${animationDuration}s linear infinite;
}
${reduceMotionBlock}`;

                $('style#ssc-stars-preview-style').remove();
                $(`<style id="ssc-stars-preview-style">${css}</style>`).appendTo('head');

                preview.addClass('ssc-bg-stars');

            } else if (type === 'gradient') {
                $('style#ssc-stars-preview-style').remove();
                const gradientResult = computeGradientCss();

                if (gradientResult.errors.length) {
                    latestGradientResult = null;
                    if ($gradientErrors.length) {
                        $gradientErrors.html(gradientResult.errors.map((error) => `<p>${error}</p>`).join('')).show();
                    }
                    setValidationState(false);
                    $('style#ssc-gradient-anim-style').remove();
                    $('#ssc-bg-css').text('');
                    return;
                }

                latestGradientResult = gradientResult;
                if ($gradientErrors.length) {
                    $gradientErrors.hide().empty();
                }
                setValidationState(true);

                $('style#ssc-gradient-anim-style').remove();
                $(`<style id="ssc-gradient-anim-style">${gradientResult.keyframes}</style>`).appendTo('head');

                css = gradientResult.css;
                preview.addClass('ssc-bg-gradient').css({
                    background: gradientResult.gradientString,
                    backgroundSize: '400% 400%',
                    animation: reduceMotion ? 'none' : `ssc-gradient-anim ${gradientResult.speed}s ease infinite`,
                });
            }

            $('#ssc-bg-css').text(css.trim());
        }

        function markPresetAsDirty() {
            if (presetsState.activePresetId !== null) {
                presetsState.activePresetId = null;
                presetsState.activePresetName = '';
                renderPresetsList();
            }
        }

        function normalizePreset(item) {
            if (!item || typeof item !== 'object') {
                return null;
            }
            const id = typeof item.id === 'string' ? item.id : '';
            if (!id) {
                return null;
            }
            const name = typeof item.name === 'string' ? item.name : '';
            const type = typeof item.type === 'string' ? item.type : 'stars';
            const settings = item.settings && typeof item.settings === 'object' ? item.settings : {};
            return { id, name, type, settings };
        }

        function getTypeLabel(type) {
            const $option = $('#ssc-bg-type option[value="' + type + '"]');
            return $option.length ? $option.text() : type;
        }

        function renderPresetsList() {
            if (!$presetsList.length) {
                return;
            }

            $presetsList.empty();

            if (presetsState.loading) {
                if ($presetsEmpty.length) {
                    $presetsEmpty.hide();
                }
                const $row = $('<tr>').append($('<td>', {
                    colspan: 3,
                    text: __('Chargement des presets…', 'supersede-css-jlg'),
                }));
                $presetsList.append($row);
                return;
            }

            if (!presetsState.items.length) {
                if ($presetsEmpty.length) {
                    $presetsEmpty.text(defaultEmptyMessage).show();
                }
                return;
            }

            if ($presetsEmpty.length) {
                $presetsEmpty.text(defaultEmptyMessage).hide();
            }

            presetsState.items.forEach((preset) => {
                const classes = ['ssc-ve-preset-row'];
                if (preset.id === presetsState.activePresetId) {
                    classes.push('is-active');
                }
                const $row = $('<tr>', {
                    'data-preset-id': preset.id,
                    class: classes.join(' '),
                });

                if (preset.id === presetsState.activePresetId) {
                    $row.css('background-color', '#f0f6ff');
                }

                const name = preset.name && preset.name.trim() ? preset.name : __('Preset sans nom', 'supersede-css-jlg');
                const $nameCell = $('<td>').text(name);
                const $typeCell = $('<td>').text(getTypeLabel(preset.type));

                const $actions = $('<div>', {
                    class: 'ssc-ve-preset-actions',
                    style: 'display:flex; gap:6px; flex-wrap:wrap;',
                });

                const $applyBtn = $('<button>', {
                    type: 'button',
                    class: 'button button-small button-primary',
                    text: __('Appliquer', 'supersede-css-jlg'),
                    'data-action': 'apply',
                });
                const $copyBtn = $('<button>', {
                    type: 'button',
                    class: 'button button-small',
                    text: __('Copier', 'supersede-css-jlg'),
                    'data-action': 'copy',
                });
                const $deleteBtn = $('<button>', {
                    type: 'button',
                    class: 'button button-small button-link-delete',
                    text: __('Supprimer', 'supersede-css-jlg'),
                    'data-action': 'delete',
                });

                $actions.append($applyBtn, $copyBtn, $deleteBtn);

                const $actionsCell = $('<td>').append($actions);

                $row.append($nameCell, $typeCell, $actionsCell);
                $presetsList.append($row);
            });
        }

        function setPresetsLoading(isLoading) {
            presetsState.loading = !!isLoading;
            if (isLoading && $presetsEmpty.length) {
                $presetsEmpty.text(__('Chargement des presets…', 'supersede-css-jlg')).show();
            }
            renderPresetsList();
        }

        function setPresets(items) {
            const normalized = [];
            if (Array.isArray(items)) {
                items.forEach((item) => {
                    const preset = normalizePreset(item);
                    if (preset) {
                        normalized.push(preset);
                    }
                });
            }

            presetsState.items = normalized;
            renderPresetsList();
        }

        function setActivePresetMetadata(preset) {
            presetsState.activePresetId = preset && preset.id ? preset.id : null;
            presetsState.activePresetName = preset && preset.name ? preset.name.trim() : '';
            if ($presetNameInput.length) {
                $presetNameInput.val(presetsState.activePresetName);
            }
            renderPresetsList();
        }

        function findPresetById(id) {
            return presetsState.items.find((preset) => preset.id === id);
        }

        function generateUniquePresetName(baseName) {
            const sanitizedBase = baseName && baseName.trim() ? baseName.trim() : __('Preset', 'supersede-css-jlg');
            const existingNames = new Set(presetsState.items.map((preset) => (preset.name || '').toLowerCase()));
            if (!existingNames.has(sanitizedBase.toLowerCase())) {
                return sanitizedBase;
            }

            let index = 2;
            let candidate = `${sanitizedBase} (${index})`;
            while (existingNames.has(candidate.toLowerCase())) {
                index += 1;
                candidate = `${sanitizedBase} (${index})`;
            }
            return candidate;
        }

        function serializeCurrentBackgroundPreset() {
            const type = $('#ssc-bg-type').val();

            if (type === 'stars') {
                const color = $('#starColor').val();
                const rawCount = parseInt($('#starCount').val(), 10);
                const min = parseInt($('#starCount').attr('min'), 10) || 10;
                const max = parseInt($('#starCount').attr('max'), 10) || 500;
                const count = clamp(Number.isNaN(rawCount) ? 200 : rawCount, min, max);

                return {
                    type,
                    settings: {
                        color: typeof color === 'string' ? color : '#ffffff',
                        count,
                    },
                };
            }

            if (type === 'gradient') {
                const gradientResult = computeGradientCss();
                if (gradientResult.errors.length) {
                    showToast(__('Corrigez les erreurs du dégradé avant d\'enregistrer un preset.', 'supersede-css-jlg'), { politeness: 'assertive' });
                    return null;
                }

                return {
                    type,
                    settings: {
                        angle: gradientResult.angle,
                        speed: gradientResult.speed,
                        stops: gradientResult.stops.map((stop) => ({
                            color: stop.color,
                            position: stop.position,
                        })),
                    },
                };
            }

            showToast(__('Sélectionnez un type de fond pris en charge.', 'supersede-css-jlg'), { politeness: 'assertive' });
            return null;
        }

        function requestSavePreset(payload) {
            if (!presetsEndpoint) {
                return $.Deferred().reject().promise();
            }
            return $.ajax({
                url: presetsEndpoint,
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify(payload),
                beforeSend: (xhr) => {
                    if (restNonce) {
                        xhr.setRequestHeader('X-WP-Nonce', restNonce);
                    }
                },
            });
        }

        function requestDeletePreset(id) {
            if (!presetsEndpoint) {
                return $.Deferred().reject().promise();
            }
            return $.ajax({
                url: `${presetsEndpoint}/${encodeURIComponent(id)}`,
                method: 'DELETE',
                beforeSend: (xhr) => {
                    if (restNonce) {
                        xhr.setRequestHeader('X-WP-Nonce', restNonce);
                    }
                },
            });
        }

        function applyPreset(preset) {
            if (!preset) {
                return;
            }

            const type = preset.type === 'gradient' ? 'gradient' : 'stars';
            $('#ssc-bg-type').val(type);

            if (type === 'stars') {
                const settings = preset.settings || {};
                if (typeof settings.color === 'string') {
                    $('#starColor').val(settings.color);
                }
                if (typeof settings.count === 'number') {
                    $('#starCount').val(settings.count);
                }
            } else {
                const settings = preset.settings || {};
                const stops = Array.isArray(settings.stops) && settings.stops.length >= 2 ? settings.stops : gradientDefaults.stops;
                gradientStopIdCounter = 0;
                gradientState.stops = [];
                $gradientStopsList.empty();
                stops.forEach((stop) => addGradientStop(stop, { triggerUpdate: false }));
                renderGradientStops();

                const angle = typeof settings.angle === 'number' ? settings.angle : gradientDefaults.angle;
                gradientState.angle = clampAngle(angle);
                $('#gradientAngle').val(gradientState.angle);

                const rawSpeed = typeof settings.speed === 'number' ? settings.speed : gradientDefaults.speed;
                const minSpeed = parseInt($('#gradientSpeed').attr('min'), 10) || 2;
                const maxSpeed = parseInt($('#gradientSpeed').attr('max'), 10) || 20;
                const speed = clamp(rawSpeed, minSpeed, maxSpeed);
                $('#gradientSpeed').val(speed);
            }

            setActivePresetMetadata(preset);
            generateBackgroundCSS();
            showToast(__('Preset appliqué !', 'supersede-css-jlg'));
        }

        function withButtonBusy($button, busyText, callback) {
            if (!$button || !$button.length) {
                return callback();
            }
            const originalText = $button.text();
            $button.prop('disabled', true).attr('aria-disabled', 'true').text(busyText);
            let request;
            try {
                request = callback();
            } catch (error) {
                $button.prop('disabled', false).removeAttr('aria-disabled').text(originalText);
                throw error;
            }

            const finalize = () => {
                $button.prop('disabled', false).removeAttr('aria-disabled').text(originalText);
            };

            if (request && typeof request.always === 'function') {
                request.always(finalize);
            } else {
                finalize();
            }

            return request;
        }

        function handleSavePreset() {
            if (!presetsEndpoint) {
                return;
            }

            const name = ($presetNameInput.val() || '').trim();
            if (name === '') {
                showToast(__('Veuillez indiquer un nom de preset.', 'supersede-css-jlg'), { politeness: 'assertive' });
                $presetNameInput.trigger('focus');
                return;
            }

            const serialized = serializeCurrentBackgroundPreset();
            if (!serialized) {
                return;
            }

            const payload = {
                name,
                type: serialized.type,
                settings: serialized.settings,
            };

            if (presetsState.activePresetId && name === presetsState.activePresetName) {
                payload.id = presetsState.activePresetId;
            }

            const savingLabel = __('Enregistrement…', 'supersede-css-jlg');

            withButtonBusy($savePresetButton, savingLabel, () => requestSavePreset(payload)
                .done((response) => {
                    const responsePresets = response && Array.isArray(response.presets) ? response.presets : null;
                    if (responsePresets) {
                        presetsState.loading = false;
                        setPresets(responsePresets);
                    } else {
                        fetchPresets();
                    }

                    const responsePreset = response && response.preset ? normalizePreset(response.preset) : null;
                    const savedPreset = responsePreset || (payload.id ? findPresetById(payload.id) : null);

                    if (savedPreset) {
                        setActivePresetMetadata(savedPreset);
                    } else {
                        presetsState.activePresetId = null;
                        presetsState.activePresetName = name;
                        if ($presetNameInput.length) {
                            $presetNameInput.val(name);
                        }
                        renderPresetsList();
                    }

                    const successMessage = payload.id ? __('Preset mis à jour !', 'supersede-css-jlg') : __('Preset enregistré !', 'supersede-css-jlg');
                    showToast(successMessage);
                })
                .fail((jqXHR) => {
                    if (window.console && typeof window.console.error === 'function') {
                        window.console.error('Failed to save visual effect preset', jqXHR);
                    }
                    showToast(__('Impossible d\'enregistrer le preset.', 'supersede-css-jlg'), { politeness: 'assertive' });
                })
            );
        }

        function handleCopyPreset(id, $button) {
            const preset = findPresetById(id);
            if (!preset) {
                return;
            }

            const baseName = preset.name && preset.name.trim() ? preset.name.trim() : __('Preset sans nom', 'supersede-css-jlg');
            const copyBase = sprintf(__('%s (copie)', 'supersede-css-jlg'), baseName);
            const uniqueName = generateUniquePresetName(copyBase);

            const payload = {
                name: uniqueName,
                type: preset.type,
                settings: $.extend(true, {}, preset.settings || {}),
            };

            withButtonBusy($button, __('Copie…', 'supersede-css-jlg'), () => requestSavePreset(payload)
                .done((response) => {
                    const responsePresets = response && Array.isArray(response.presets) ? response.presets : null;
                    if (responsePresets) {
                        presetsState.loading = false;
                        setPresets(responsePresets);
                    } else {
                        fetchPresets();
                    }

                    const responsePreset = response && response.preset ? normalizePreset(response.preset) : null;
                    if (responsePreset) {
                        setActivePresetMetadata(responsePreset);
                    } else {
                        presetsState.activePresetId = null;
                        presetsState.activePresetName = uniqueName;
                        if ($presetNameInput.length) {
                            $presetNameInput.val(uniqueName);
                        }
                        renderPresetsList();
                    }

                    showToast(__('Preset copié !', 'supersede-css-jlg'));
                })
                .fail(() => {
                    showToast(__('Impossible de copier le preset.', 'supersede-css-jlg'), { politeness: 'assertive' });
                })
            );
        }

        function handleDeletePreset(id, $button) {
            const preset = findPresetById(id);
            if (!preset) {
                return;
            }

            const confirmationMessage = __('Voulez-vous vraiment supprimer ce preset ?', 'supersede-css-jlg');
            if (typeof window.confirm === 'function' && !window.confirm(confirmationMessage)) {
                return;
            }

            withButtonBusy($button, __('Suppression…', 'supersede-css-jlg'), () => requestDeletePreset(id)
                .done((response) => {
                    const responsePresets = response && Array.isArray(response.presets) ? response.presets : null;
                    if (responsePresets) {
                        presetsState.loading = false;
                        setPresets(responsePresets);
                    } else {
                        fetchPresets();
                    }

                    if (presetsState.activePresetId === id) {
                        presetsState.activePresetId = null;
                        presetsState.activePresetName = '';
                        if ($presetNameInput.length) {
                            const currentValue = ($presetNameInput.val() || '').trim();
                            if (currentValue === preset.name) {
                                $presetNameInput.val('');
                            }
                        }
                        renderPresetsList();
                    }

                    showToast(__('Preset supprimé.', 'supersede-css-jlg'));
                })
                .fail(() => {
                    showToast(__('Impossible de supprimer le preset.', 'supersede-css-jlg'), { politeness: 'assertive' });
                })
            );
        }

        function fetchPresets() {
            if (!presetsEndpoint) {
                setPresets([]);
                return;
            }

            setPresetsLoading(true);

            $.ajax({
                url: presetsEndpoint,
                method: 'GET',
                beforeSend: (xhr) => {
                    if (restNonce) {
                        xhr.setRequestHeader('X-WP-Nonce', restNonce);
                    }
                },
            })
            .done((response) => {
                presetsState.loading = false;
                if ($presetsEmpty.length) {
                    $presetsEmpty.text(defaultEmptyMessage);
                }
                const responsePresets = response && Array.isArray(response.presets) ? response.presets : (Array.isArray(response) ? response : []);
                setPresets(responsePresets);
            })
            .fail(() => {
                presetsState.loading = false;
                if ($presetsEmpty.length) {
                    $presetsEmpty.text(defaultEmptyMessage);
                }
                showToast(__('Impossible de charger les presets.', 'supersede-css-jlg'), { politeness: 'assertive' });
                setPresets([]);
            });
        }

        $('#ssc-bg-type, #starColor, #starCount, #gradientSpeed').on('input change', generateBackgroundCSS);
        $('#ssc-bg-type, #starColor, #starCount, #gradientSpeed').on('input change', markPresetAsDirty);

        $('#ssc-add-gradient-stop').on('click', () => {
            addGradientStop({ color: '#ffffff', position: 50 });
            markPresetAsDirty();
        });

        $gradientStopsList.on('input change', '.ssc-gradient-stop-color', function() {
            const stopId = parseInt($(this).closest('.ssc-gradient-stop').data('stop-id'), 10);
            updateGradientStop(stopId, { color: $(this).val() });
            generateBackgroundCSS();
            markPresetAsDirty();
        });

        $gradientStopsList.on('input change', '.ssc-gradient-stop-position', function() {
            const stopId = parseInt($(this).closest('.ssc-gradient-stop').data('stop-id'), 10);
            const rawValue = parseFloat($(this).val());
            const position = clampPosition(rawValue);
            $(this).val(position);
            updateGradientStop(stopId, { position });
            generateBackgroundCSS();
            markPresetAsDirty();
        });

        $gradientStopsList.on('click', '.ssc-remove-gradient-stop', function() {
            const stopId = parseInt($(this).closest('.ssc-gradient-stop').data('stop-id'), 10);
            removeGradientStop(stopId);
        });

        $('#gradientAngle').on('input change', function() {
            const rawValue = parseFloat($(this).val());
            const angle = clampAngle(Number.isNaN(rawValue) ? gradientDefaults.angle : rawValue);
            $(this).val(angle);
            gradientState.angle = angle;
            generateBackgroundCSS();
            markPresetAsDirty();
        });

        if ($presetNameInput.length) {
            $presetNameInput.on('input', () => {
                const currentValue = ($presetNameInput.val() || '').trim();
                if (currentValue !== presetsState.activePresetName) {
                    presetsState.activePresetId = null;
                    renderPresetsList();
                }
            });
        }

        if ($savePresetButton.length) {
            $savePresetButton.on('click', handleSavePreset);
        }

        if ($presetsList.length) {
            $presetsList.on('click', 'button[data-action="apply"]', function(event) {
                event.preventDefault();
                const presetId = $(this).closest('tr').data('preset-id');
                applyPreset(findPresetById(presetId));
            });

            $presetsList.on('click', 'button[data-action="copy"]', function(event) {
                event.preventDefault();
                const presetId = $(this).closest('tr').data('preset-id');
                handleCopyPreset(presetId, $(this));
            });

            $presetsList.on('click', 'button[data-action="delete"]', function(event) {
                event.preventDefault();
                const presetId = $(this).closest('tr').data('preset-id');
                handleDeletePreset(presetId, $(this));
            });
        }

        if ($copyCssButton.length) {
            $copyCssButton.on('click', () => {
                generateBackgroundCSS();
                const css = $('#ssc-bg-css').text().trim();
                if (!css) {
                    showToast(__('Aucun CSS à copier pour le moment.', 'supersede-css-jlg'), { politeness: 'assertive' });
                    return;
                }

                if (typeof window.sscCopyToClipboard === 'function') {
                    const promise = window.sscCopyToClipboard(css, {
                        successMessage: __('CSS du fond copié !', 'supersede-css-jlg'),
                        errorMessage: __('Impossible de copier le CSS du fond.', 'supersede-css-jlg'),
                    });
                    if (promise && typeof promise.catch === 'function') {
                        promise.catch(() => {});
                    }
                } else if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(css)
                        .then(() => {
                            showToast(__('CSS du fond copié !', 'supersede-css-jlg'));
                        })
                        .catch(() => {
                            showToast(__('Impossible de copier le CSS du fond.', 'supersede-css-jlg'), { politeness: 'assertive' });
                        });
                } else {
                    showToast(__('Impossible de copier le CSS du fond.', 'supersede-css-jlg'), { politeness: 'assertive' });
                }
            });
        }

        $('#ssc-bg-type').on('change', markPresetAsDirty);

        $applyButton.on('click', () => {
            generateBackgroundCSS();

            const type = $('#ssc-bg-type').val();
            const storedCss = $('#ssc-bg-css').text().trim();
            let cssToApply = storedCss;

            if (type === 'gradient') {
                if (!latestGradientResult) {
                    const errorToast = __("Corrigez les erreurs du dégradé avant d'appliquer.", 'supersede-css-jlg');
                    window.sscToast(errorToast, { politeness: 'assertive' });
                    return;
                }

                cssToApply = latestGradientResult.css.trim();
            }

            if (!cssToApply) {
                return;
            }

            const errorMessage = __("Échec de l'enregistrement du fond animé.", 'supersede-css-jlg');
            const originalText = $applyButton.text();

            applyBusy = true;
            updateApplyButtonState();

            $applyButton.text(applyingLabel);

            const requestData = { css: cssToApply, append: true, _wpnonce: SSC.rest.nonce };
            if (type === 'gradient' && latestGradientResult) {
                requestData.gradient_settings = JSON.stringify({
                    angle: latestGradientResult.angle,
                    stops: latestGradientResult.stops.map((stop) => ({ color: stop.color, position: stop.position })),
                });
            }

            $.ajax({
                url: SSC.rest.root + 'save-css',
                method: 'POST',
                data: requestData,
                beforeSend: (xhr) => xhr.setRequestHeader('X-WP-Nonce', SSC.rest.nonce),
            }).done(() => window.sscToast('Fond animé appliqué !'))
             .fail((jqXHR, textStatus, errorThrown) => {
                 console.error(errorMessage, { jqXHR, textStatus, errorThrown });
                 window.sscToast(
                     errorMessage,
                     { politeness: 'assertive' }
                 );
             })
             .always(() => {
                 applyBusy = false;
                 updateApplyButtonState();
                 $applyButton.text(originalText);
             });
        });

        setDefaultGradientPreset();
        setValidationState(true);

        generateBackgroundCSS();
        onMotionPreferenceChange(generateBackgroundCSS);

        if (presetsEndpoint) {
            fetchPresets();
        } else {
            setPresets([]);
        }
    }

    // --- Module 3: ECG ---
    function initECG() {
        if(!$('#ssc-ecg-preset').length) return;
        const paths = { stable: "M0,30 L100,30 L110,18 L120,42 L130,26 L140,30 L240,30 L250,20 L260,40 L270,28 L280,30 L400,30", fast: "M0,30 L60,30 L70,8 L80,52 L90,18 L100,30 L160,30 L170,12 L180,48 L190,22 L200,30 L400,30", critical: "M0,30 L40,30 L50,5 L60,55 L70,15 L80,30 L120,30 L130,2 L140,58 L150,12 L160,30 L400,30" };
        
        if (typeof wp !== 'undefined' && wp.media) {
            let frame;
            $('#ssc-ecg-upload-btn').on('click', function(e) {
                e.preventDefault();
                if (frame) { frame.open(); return; }
                frame = wp.media({ title: 'Choisir un logo', multiple: false });
                frame.on('select', function() {
                    $('#ssc-ecg-logo-preview').attr('src', frame.state().get('selection').first().toJSON().url).show();
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

            $('#ssc-ecg-top-val').text(top + '%');
            $('#ssc-ecg-z-index-val').text(zIndex);
            $('#ssc-ecg-logo-size-val').text(logoSize + 'px');
            $('#ssc-ecg-logo-preview').css({ 'max-width': logoSize + 'px', 'max-height': logoSize + 'px' });

            const previewPath = $('#ssc-ecg-preview-path');
            const previewSvg = $('#ssc-ecg-preview-svg');

            if (!$('style#ssc-ecg-anim').length) $('<style id="ssc-ecg-anim">@keyframes ssc-ecg-line{to{stroke-dashoffset:0}}</style>').appendTo('head');

            previewSvg.css({ 'top': `${top}%`, 'transform': 'translateY(-50%)', 'z-index': zIndex });
            const dashOffset = reduceMotion ? 0 : 1000;
            const animationValue = reduceMotion ? 'none' : `ssc-ecg-line ${speed} linear infinite`;
            previewPath.attr('d', paths[preset]).css({ 'stroke': color, 'stroke-dasharray': 1000, 'stroke-dashoffset': dashOffset, 'animation': animationValue });

            const css = `@keyframes ssc-ecg-line{to{stroke-dashoffset:0}}
.ssc-ecg-container { position: relative; } /* Conteneur parent */
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
        const $ecgApplyButton = $('#ssc-ecg-apply');
        const ecgApplyingLabel = __('Application…', 'supersede-css-jlg');
        $ecgApplyButton.on('click', () => {
             const css = $('#ssc-ecg-css').text();
             const errorMessage = __('Impossible d\'appliquer l\'effet ECG.', 'supersede-css-jlg');
             const originalText = $ecgApplyButton.text();

             $ecgApplyButton
                 .prop('disabled', true)
                 .attr('aria-disabled', 'true')
                 .text(ecgApplyingLabel);

            $.ajax({
                url: SSC.rest.root + 'save-css',
                method: 'POST',
                data: { css, append: true, _wpnonce: SSC.rest.nonce },
                beforeSend: (xhr) => xhr.setRequestHeader('X-WP-Nonce', SSC.rest.nonce),
            })
             .done(() => window.sscToast('Effet ECG appliqué !'))
             .fail((jqXHR, textStatus, errorThrown) => {
                 console.error(errorMessage, { jqXHR, textStatus, errorThrown });
                 window.sscToast(errorMessage, { politeness: 'assertive' });
             })
             .always(() => {
                 $ecgApplyButton
                     .prop('disabled', false)
                     .removeAttr('aria-disabled')
                     .text(originalText);
             });
        });
        generateECGCSS();
        onMotionPreferenceChange(generateECGCSS);
    }
})(jQuery);
