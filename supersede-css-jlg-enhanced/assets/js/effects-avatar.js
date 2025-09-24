(function($) {
    let presets = {};
    let activePresetId = null;
    const placeholderAssetPath = 'assets/images/placeholder-avatar.png';
    let defaultAvatarUrl = '';

    if (typeof window.sscPluginAssetUrl === 'function') {
        defaultAvatarUrl = window.sscPluginAssetUrl(placeholderAssetPath);
    } else if (typeof SSC !== 'undefined' && SSC && typeof SSC.pluginUrl === 'string') {
        const pluginUrl = SSC.pluginUrl.endsWith('/') ? SSC.pluginUrl : SSC.pluginUrl + '/';
        defaultAvatarUrl = pluginUrl + placeholderAssetPath;
    }
    let currentAvatarUrl = defaultAvatarUrl;

    // Charger les presets depuis la base de données
    function loadPresets() {
        $.ajax({
            url: SSC.rest.root + 'avatar-glow-presets',
            method: 'GET',
            data: { _wpnonce: SSC.rest.nonce },
            beforeSend: x => x.setRequestHeader('X-WP-Nonce', SSC.rest.nonce)
        }).done(response => {
            presets = response || {};
            populatePresetSelector();
            if (Object.keys(presets).length > 0) {
                loadPreset(Object.keys(presets)[0]);
            } else {
                createNewPreset();
            }
        });
    }

    // Sauvegarder tous les presets
    function saveAllPresets() {
        return $.ajax({
            url: SSC.rest.root + 'avatar-glow-presets',
            method: 'POST',
            data: { presets: JSON.stringify(presets), _wpnonce: SSC.rest.nonce },
            beforeSend: x => x.setRequestHeader('X-WP-Nonce', SSC.rest.nonce)
        });
    }

    // Mettre à jour la liste déroulante
    function populatePresetSelector() {
        const select = $('#ssc-glow-preset-select');
        select.empty();
        for (const id in presets) {
            select.append(`<option value="${id}">${presets[id].name}</option>`);
        }
    }

    // Charger les réglages d'un preset dans l'interface
    function loadPreset(id) {
        if (!presets[id]) return;
        activePresetId = id;

        const p = presets[id];
        $('#ssc-glow-preset-select').val(id);
        $('#ssc-glow-preset-name').val(p.name);
        $('#ssc-glow-preset-class').val(p.className);
        $('#ssc-glow-color1').val(p.color1);
        $('#ssc-glow-color2').val(p.color2);
        $('#ssc-glow-speed').val(p.speed);
        $('#ssc-glow-thickness').val(p.thickness);
        $('#ssc-glow-delete-preset').show();

        currentAvatarUrl = p.avatarUrl || defaultAvatarUrl;
        $('#ssc-glow-preview-img').attr('src', currentAvatarUrl);

        generateGlowCSS();
    }

    // Créer un nouveau preset vide
    function createNewPreset() {
        const newId = 'preset_' + Date.now();
        activePresetId = newId;
        
        presets[newId] = {
            name: 'Nouveau Preset',
            className: '.avatar-glow-new',
            color1: '#8b5cf6', color2: '#ec4899',
            speed: 5, thickness: 4,
            avatarUrl: defaultAvatarUrl
        };
        
        populatePresetSelector();
        loadPreset(newId);
        $('#ssc-glow-delete-preset').hide();
    }

    // Mettre à jour les données du preset actif depuis les champs
    function updateActivePresetFromFields() {
        if (!activePresetId) return;
        presets[activePresetId] = {
            name: $('#ssc-glow-preset-name').val(),
            className: $('#ssc-glow-preset-class').val(),
            color1: $('#ssc-glow-color1').val(),
            color2: $('#ssc-glow-color2').val(),
            speed: $('#ssc-glow-speed').val(),
            thickness: $('#ssc-glow-thickness').val(),
            avatarUrl: currentAvatarUrl
        };
    }
    
    // Générer et afficher le CSS
    function generateGlowCSS() {
        if (!activePresetId || !presets[activePresetId]) return;
        const p = presets[activePresetId];
        
        $('#ssc-glow-speed-val').text(p.speed + 's');
        $('#ssc-glow-thickness-val').text(p.thickness + 'px');
        $('#ssc-glow-how-to-use-class').text(p.className);

        const css = `
/* Preset: ${p.name} */
${p.className} {
  position: relative;
  width: fit-content;
  border-radius: 50%;
  isolation: isolate;
}
${p.className}::before {
  content: '';
  position: absolute;
  z-index: -1;
  inset: -${p.thickness}px;
  border-radius: 50%;
  background: conic-gradient(from var(--angle), ${p.color1}, ${p.color2}, ${p.color1});
  animation: ssc-comet-spin ${p.speed}s linear infinite;
  filter: blur(8px);
}`;
        
        $('#ssc-glow-css-output').text(css.trim());

        $('style#ssc-glow-live-style').remove();
        $(`<style id="ssc-glow-live-style">
            @property --angle { syntax: '<angle>'; initial-value: 0deg; inherits: false; }
            @keyframes ssc-comet-spin { to { --angle: 360deg; } }
            ${css}
        </style>`).appendTo('head');

        $('#ssc-glow-preview-container').attr('class', p.className.substring(1));
    }

    $(document).ready(function() {
        if (!$('#ssc-glow-preset-select').length) return;

        loadPresets();

        // Média uploader pour l'image d'avatar
        if (typeof wp !== 'undefined' && wp.media) {
            let frame;
            $('#ssc-glow-upload-btn').on('click', function(e) {
                e.preventDefault();
                if (frame) { frame.open(); return; }
                frame = wp.media({
                    title: 'Choisir un avatar', button: { text: 'Utiliser cette image' }, multiple: false
                });
                frame.on('select', function() {
                    const attachment = frame.state().get('selection').first().toJSON();
                    currentAvatarUrl = attachment.url;
                    $('#ssc-glow-preview-img').attr('src', currentAvatarUrl);
                    updateActivePresetFromFields();
                });
                frame.open();
            });
        }

        // Événements de l'interface
        $('#ssc-glow-preset-select').on('change', function() { loadPreset($(this).val()); });
        $('#ssc-glow-new-preset').on('click', createNewPreset);

        $('#ssc-glow-editor-fields').on('input', 'input', function() {
            updateActivePresetFromFields();
            generateGlowCSS();
            const selectedOption = $('#ssc-glow-preset-select option:selected');
            if (selectedOption.val() === activePresetId) {
                selectedOption.text(presets[activePresetId].name);
            }
        });

        $('#ssc-glow-save-preset').on('click', function() {
            updateActivePresetFromFields();
            const currentName = presets[activePresetId].name;
            if (!currentName || !presets[activePresetId].className.startsWith('.')) {
                alert("Le nom du preset ne peut pas être vide et le nom de la classe doit commencer par un '.'");
                return;
            }
            saveAllPresets().done(() => window.sscToast(`Preset "${currentName}" enregistré !`));
        });
        
        $('#ssc-glow-delete-preset').on('click', function() {
            const currentName = presets[activePresetId].name;
            if (confirm(`Voulez-vous vraiment supprimer le preset "${currentName}" ?`)) {
                delete presets[activePresetId];
                saveAllPresets().done(() => {
                    window.sscToast(`Preset "${currentName}" supprimé.`);
                    loadPresets();
                });
            }
        });

        $('#ssc-glow-apply').on('click', function() {
            const cssToApply = `
@property --angle { syntax: '<angle>'; initial-value: 0deg; inherits: false; }
@keyframes ssc-comet-spin { to { --angle: 360deg; } }
${$('#ssc-glow-css-output').text()}`;

             $.ajax({
                url: SSC.rest.root + 'save-css', method: 'POST', data: { css: cssToApply, append: true, _wpnonce: SSC.rest.nonce },
                beforeSend: x => x.setRequestHeader('X-WP-Nonce', SSC.rest.nonce)
            }).done(() => window.sscToast('Le style du preset a été appliqué sur le site !'));
        });
    });
})(jQuery);