(function($) {
    let presets = {};
    let activePresetId = null;

    // Charger les presets depuis la BDD
    function loadPresets() {
        $.ajax({
            url: SSC.rest.root + 'presets',
            method: 'GET',
            data: { _wpnonce: SSC.rest.nonce },
            beforeSend: x => x.setRequestHeader('X-WP-Nonce', SSC.rest.nonce)
        }).done(response => {
            presets = response || {};
            renderPresetList();
            populateQuickApply();
        });
    }

    // Sauvegarder tous les presets dans la BDD
    function saveAllPresets() {
        return $.ajax({
            url: SSC.rest.root + 'presets',
            method: 'POST',
            data: { presets: JSON.stringify(presets), _wpnonce: SSC.rest.nonce },
            beforeSend: x => x.setRequestHeader('X-WP-Nonce', SSC.rest.nonce)
        });
    }

    // Afficher la liste des presets existants
    function renderPresetList() {
        const list = $('#ssc-presets-list');
        list.empty();
        if (Object.keys(presets).length === 0) {
            list.append('<li>Aucun preset enregistré.</li>');
            return;
        }
        for (const id in presets) {
            const preset = presets[id];
            const li = $(`
                <li>
                    <span><strong>${preset.name}</strong> (<code>${preset.scope}</code>)</span>
                    <button class="button button-small ssc-edit-preset" data-id="${id}">Modifier</button>
                </li>
            `);
            list.append(li);
        }
    }

    // Remplir le menu déroulant "Quick Apply"
    function populateQuickApply() {
        const select = $('#ssc-qa-select');
        select.empty();
        select.append('<option value="">Choisir un preset...</option>');
        for (const id in presets) {
            select.append(`<option value="${id}">${presets[id].name}</option>`);
        }
    }
    
    // Afficher un preset dans l'éditeur
    function renderEditor(id) {
        activePresetId = id;
        const preset = presets[id];
        
        $('#ssc-preset-name').val(preset.name);
        $('#ssc-preset-scope').val(preset.scope);
        $('#ssc-delete-preset').show();

        const builder = $('#ssc-preset-props-builder');
        builder.empty();
        for (const prop in preset.props) {
            const row = createPropRow(prop, preset.props[prop]);
            builder.append(row);
        }
    }

    // Réinitialiser l'éditeur pour un nouveau preset
    function clearEditor() {
        activePresetId = null;
        $('#ssc-preset-name').val('');
        $('#ssc-preset-scope').val('');
        $('#ssc-preset-props-builder').empty();
        $('#ssc-delete-preset').hide();
        $('#ssc-preset-name').focus();
    }

    // Créer une nouvelle ligne de propriété CSS
    function createPropRow(key = '', value = '') {
        const row = $('<div>')
            .addClass('kv-row')
            .css({ display: 'flex', gap: '8px', marginBottom: '8px' });

        const keyInput = $('<input>', {
            type: 'text',
            class: 'prop-key regular-text',
            placeholder: 'propriété (ex: background-color)'
        }).val(key);

        const valueInput = $('<input>', {
            type: 'text',
            class: 'prop-val regular-text',
            placeholder: 'valeur (ex: #ff0000)'
        }).val(value);

        const removeButton = $('<button>', {
            type: 'button',
            class: 'button button-link-delete remove-prop-btn',
            text: 'X'
        });

        row.append(keyInput, valueInput, removeButton);

        return row;
    }

    $(document).ready(function() {
        if (!$('#ssc-presets-list').length) return;

        // --- GESTION DES ÉVÉNEMENTS ---

        // Le bouton "+ Ajouter"
        $('#ssc-preset-add-prop').on('click', function() {
            $('#ssc-preset-props-builder').append(createPropRow());
        });

        // Supprimer une ligne de propriété
        $('#ssc-preset-props-builder').on('click', '.remove-prop-btn', function() {
            $(this).closest('.kv-row').remove();
        });

        // Enregistrer un preset (nouveau ou modifié)
        $('#ssc-save-preset').on('click', function() {
            const name = $('#ssc-preset-name').val().trim();
            const scope = $('#ssc-preset-scope').val().trim();
            if (!name || !scope) {
                alert('Le nom et le sélecteur sont obligatoires.');
                return;
            }

            if (/[{};@]/.test(scope)) {
                alert('Le sélecteur est invalide.');
                return;
            }

            const props = {};
            $('#ssc-preset-props-builder .kv-row').each(function() {
                const key = $(this).find('.prop-key').val();
                const val = $(this).find('.prop-val').val();
                if (key && val) {
                    props[key] = val;
                }
            });

            const id = activePresetId || 'preset_' + Date.now();
            presets[id] = { name, scope, props };

            saveAllPresets().done(() => {
                window.sscToast(`Preset "${name}" enregistré !`);
                loadPresets();
                clearEditor();
            });
        });

        // Modifier un preset
        $('#ssc-presets-list').on('click', '.ssc-edit-preset', function() {
            const id = $(this).data('id');
            renderEditor(id);
        });
        
        // Supprimer un preset
        $('#ssc-delete-preset').on('click', function() {
            if (!activePresetId || !presets[activePresetId]) return;
            const name = presets[activePresetId].name;
            if (confirm(`Voulez-vous vraiment supprimer le preset "${name}" ?`)) {
                delete presets[activePresetId];
                saveAllPresets().done(() => {
                    window.sscToast(`Preset "${name}" supprimé.`);
                    loadPresets();
                    clearEditor();
                });
            }
        });

        // Appliquer un preset au site
        $('#ssc-qa-apply').on('click', function() {
            const id = $('#ssc-qa-select').val();
            if (!id || !presets[id]) return;

            const preset = presets[id];
            let css = `${preset.scope} {\n`;
            for (const prop in preset.props) {
                css += `  ${prop}: ${preset.props[prop]};\n`;
            }
            css += '}';
            
            $.ajax({
                url: SSC.rest.root + 'save-css',
                method: 'POST',
                data: { css, append: true, _wpnonce: SSC.rest.nonce },
                beforeSend: x => x.setRequestHeader('X-WP-Nonce', SSC.rest.nonce)
            }).done(() => window.sscToast(`Preset "${preset.name}" appliqué sur le site !`));
        });

        // Initialisation
        loadPresets();
        clearEditor();
    });
})(jQuery);