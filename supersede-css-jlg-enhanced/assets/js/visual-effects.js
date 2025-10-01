(function($) {
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

        function draw() {
            if (canvas.offsetWidth === 0) { requestAnimationFrame(draw); return; }
            canvas.width = canvas.offsetWidth;
            canvas.height = canvas.offsetHeight;
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

            offsets.forEach(({ color, offset }) => {
                ctx.fillStyle = color;
                const scanlineOffset = (time * settings.scanlineSpeed * 10) % 4;
                for (let y = scanlineOffset; y < canvas.height; y += 4) {
                    ctx.fillRect(offset, y, canvas.width, 2);
                }
            });
            
            time += 0.016;
            requestAnimationFrame(draw);
        }

        $('.ssc-crt-control').on('input', function() { 
            const prop = this.id;
            const value = $(this).is('input[type="color"]') ? $(this).val() : parseFloat($(this).val());
            settings[prop] = value;
        });
        draw();
    }


    // --- Module 2: Fonds Animés (Corrigé) ---
    function initBackgrounds() {
        if(!$('#ssc-bg-type').length) return;
        
        // S'assurer que les keyframes des étoiles sont prêtes
        if (!$('style#ssc-stars-anim-style').length) {
            $('<style id="ssc-stars-anim-style">@keyframes ssc-stars-anim { from { transform: translateY(0px); } to { transform: translateY(-2000px); } }</style>').appendTo('head');
        }

        // Attacher les écouteurs d'événements
        $('#ssc-bg-type, #starColor, #starCount, #gradientSpeed').on('input change', generateBackgroundCSS);
        $('#ssc-bg-apply').on('click', () => {
             const css = $('#ssc-bg-css').text();
             $.ajax({ url: SSC.rest.root + 'save-css', method: 'POST', data: { css, append: true, _wpnonce: SSC.rest.nonce }, beforeSend: x => x.setRequestHeader('X-WP-Nonce', SSC.rest.nonce)
             }).done(() => window.sscToast('Fond animé appliqué !'));
        });
        
        // Appel initial pour afficher l'aperçu par défaut
        generateBackgroundCSS();
    }

    function generateBackgroundCSS() {
        const type = $('#ssc-bg-type').val();
        $('#ssc-bg-controls-stars').toggle(type === 'stars');
        $('#ssc-bg-controls-gradient').toggle(type === 'gradient');
        let css = '', preview = $('#ssc-bg-preview');
        preview.empty().removeAttr('style').css('animation', '').removeClass('ssc-bg-stars ssc-bg-gradient'); // Réinitialiser l'animation

        if (type === 'stars') {
            const color = $('#starColor').val();
            const count = parseInt($('#starCount').val(), 10);
            const keyframes = `@keyframes ssc-stars-anim { from { transform: translateY(0); } to { transform: translateY(-2000px); } }`;
            const animationDuration = 50;
            let boxShadows = [];
            for (let i = 0; i < count; i++) {
                boxShadows.push(`${Math.random() * 2000}px ${Math.random() * 2000}px ${color}`);
            }
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
`;

            // Injecter le CSS généré pour l'aperçu
            $('style#ssc-stars-preview-style').remove();
            $(`<style id="ssc-stars-preview-style">${css}</style>`).appendTo('head');

            preview.addClass('ssc-bg-stars');

        } else if (type === 'gradient') {
            $('style#ssc-stars-preview-style').remove();
            const speed = $('#gradientSpeed').val();
            const keyframes = `@keyframes ssc-gradient-anim { 0% { background-position: 0% 50%; } 50% { background-position: 100% 50%; } 100% { background-position: 0% 50%; } }`;

            // Injecter les keyframes pour que l'aperçu fonctionne
            $('style#ssc-gradient-anim-style').remove();
            $(`<style id="ssc-gradient-anim-style">${keyframes}</style>`).appendTo('head');

            css = `${keyframes}
.ssc-bg-gradient {
  background: linear-gradient(-45deg, #ee7752, #e73c7e, #23a6d5, #23d5ab);
  background-size: 400% 400%;
  animation: ssc-gradient-anim ${speed}s ease infinite;
}`;
            preview.css({
                background: 'linear-gradient(-45deg, #ee7752, #e73c7e, #23a6d5, #23d5ab)',
                backgroundSize: '400% 400%',
                animation: `ssc-gradient-anim ${speed}s ease infinite`
            });
        }
        $('#ssc-bg-css').text(css.trim());
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

            $('#ssc-ecg-top-val').text(top + '%');
            $('#ssc-ecg-z-index-val').text(zIndex);
            $('#ssc-ecg-logo-size-val').text(logoSize + 'px');
            $('#ssc-ecg-logo-preview').css({ 'max-width': logoSize + 'px', 'max-height': logoSize + 'px' });

            const previewPath = $('#ssc-ecg-preview-path');
            const previewSvg = $('#ssc-ecg-preview-svg');

            if (!$('style#ssc-ecg-anim').length) $('<style id="ssc-ecg-anim">@keyframes ssc-ecg-line{to{stroke-dashoffset:0}}</style>').appendTo('head');

            previewSvg.css({ 'top': `${top}%`, 'transform': 'translateY(-50%)', 'z-index': zIndex });
            previewPath.attr('d', paths[preset]).css({ 'stroke': color, 'stroke-dasharray': 1000, 'stroke-dashoffset': 1000, 'animation': `ssc-ecg-line ${speed} linear infinite` });

            const css = `@keyframes ssc-ecg-line{to{stroke-dashoffset:0}}
.ssc-ecg-container { position: relative; } /* Conteneur parent */
.ssc-ecg-line-svg { position: absolute; top: ${top}%; left: 0; width: 100%; height: auto; transform: translateY(-50%); z-index: ${zIndex}; }
.ssc-ecg-path-animated { fill:none; stroke:${color}; stroke-width:2; stroke-dasharray:1000; stroke-dashoffset:1000; animation:ssc-ecg-line ${speed} linear infinite; filter:drop-shadow(0 0 5px ${color}) }`;
            $('#ssc-ecg-css').text(css.trim());
        }

        $('#ssc-ecg-preset, #ssc-ecg-color, #ssc-ecg-top, #ssc-ecg-logo-size, #ssc-ecg-z-index').on('input', generateECGCSS);
        $('#ssc-ecg-apply').on('click', () => {
             const css = $('#ssc-ecg-css').text();
             $.ajax({ url: SSC.rest.root + 'save-css', method: 'POST', data: { css, append: true, _wpnonce: SSC.rest.nonce }, beforeSend: x => x.setRequestHeader('X-WP-Nonce', SSC.rest.nonce)
             }).done(() => window.sscToast('Effet ECG appliqué !'));
        });
        generateECGCSS();
    }
})(jQuery);