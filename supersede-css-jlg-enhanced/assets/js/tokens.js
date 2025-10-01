(function($) {
    const restRoot = window.SSC && window.SSC.rest && window.SSC.rest.root ? window.SSC.rest.root : '';
    const restNonce = window.SSC && window.SSC.rest && window.SSC.rest.nonce ? window.SSC.rest.nonce : '';
    const localized = window.SSC_TOKENS_DATA || {};

    let tokens = Array.isArray(localized.tokens) ? localized.tokens.slice() : [];
    let hasLocalChanges = false;
    const tokenTypes = localized.types || {
        color: { label: 'Couleur', input: 'color' },
        text: { label: 'Texte', input: 'text' },
        number: { label: 'Nombre', input: 'number' },
    };
    const i18n = localized.i18n || {};

    const builder = $('#ssc-token-builder');
    const cssTextarea = $('#ssc-tokens');
    const reloadButton = $('#ssc-tokens-reload');
    const previewStyle = $('#ssc-tokens-preview-style');
    const groupDatalistId = 'ssc-token-groups-list';
    const duplicateRowClass = 'ssc-token-row--duplicate';
    const duplicateInputClass = 'token-field-input--duplicate';

    function getCanonicalName(value) {
        const normalized = normalizeName(value);
        if (!normalized) {
            return '';
        }

        return normalized.toLowerCase();
    }

    function calculateDuplicateKeys() {
        const seen = Object.create(null);
        const duplicates = [];

        tokens.forEach(function(token) {
            if (!token || typeof token !== 'object') {
                return;
            }

            const canonical = getCanonicalName(token.name);
            if (!canonical) {
                return;
            }

            const rawValue = token.value == null ? '' : String(token.value);
            if (rawValue.trim() === '') {
                return;
            }

            if (seen[canonical]) {
                if (duplicates.indexOf(canonical) === -1) {
                    duplicates.push(canonical);
                }
            } else {
                seen[canonical] = true;
            }
        });

        return duplicates;
    }

    function buildDuplicateLabels(duplicateKeys) {
        if (!Array.isArray(duplicateKeys) || !duplicateKeys.length) {
            return [];
        }

        const canonicalSet = new Set(
            duplicateKeys
                .map(function(key) {
                    return typeof key === 'string' ? key.toLowerCase() : '';
                })
                .filter(function(key) {
                    return key !== '';
                })
        );

        if (!canonicalSet.size) {
            return [];
        }

        const labels = [];

        tokens.forEach(function(token) {
            if (!token || typeof token.name !== 'string') {
                return;
            }

            const normalized = normalizeName(token.name);
            const canonical = normalized ? normalized.toLowerCase() : '';
            if (!canonical || !canonicalSet.has(canonical)) {
                return;
            }

            if (normalized && labels.indexOf(normalized) === -1) {
                labels.push(normalized);
            }
        });

        return labels;
    }

    function updateDuplicateHighlights(duplicateKeys) {
        const canonicalSet = new Set(
            (duplicateKeys || []).map(function(key) {
                return typeof key === 'string' ? key.toLowerCase() : '';
            }).filter(function(key) {
                return key !== '';
            })
        );

        if (!builder.length) {
            return;
        }

        builder.find('.ssc-token-row').each(function() {
            const row = $(this);
            const nameInput = row.find('.token-name');
            if (!nameInput.length) {
                return;
            }

            const canonical = getCanonicalName(nameInput.val());
            const isDuplicate = canonical !== '' && canonicalSet.has(canonical);
            row.toggleClass(duplicateRowClass, isDuplicate);
            nameInput.toggleClass(duplicateInputClass, isDuplicate);
            if (isDuplicate) {
                nameInput.attr('aria-invalid', 'true');
            } else {
                nameInput.removeAttr('aria-invalid');
            }
        });
    }

    function notifyDuplicateError(labels, customMessage) {
        const fallbackMessage = i18n.duplicateError || 'Certains tokens utilisent le même nom. Corrigez les doublons avant d’enregistrer.';
        const message = (typeof customMessage === 'string' && customMessage.trim() !== '') ? customMessage : fallbackMessage;
        const normalizedLabels = Array.isArray(labels)
            ? labels
                .map(function(label) {
                    return typeof label === 'string' ? label.trim() : '';
                })
                .filter(function(label) {
                    return label !== '';
                })
            : [];

        let finalMessage = message;
        if (normalizedLabels.length) {
            const prefix = i18n.duplicateListPrefix || 'Doublons :';
            finalMessage = message + ' ' + prefix + ' ' + normalizedLabels.join(', ');
        }

        if (typeof window.sscToast === 'function') {
            window.sscToast(finalMessage, { politeness: 'assertive', role: 'alert' });
        }

        if (window.wp && window.wp.a11y && typeof window.wp.a11y.speak === 'function') {
            window.wp.a11y.speak(finalMessage, 'assertive');
        }
    }

    function handleDuplicateConflict(duplicateKeys, labels, message) {
        updateDuplicateHighlights(duplicateKeys);
        notifyDuplicateError(labels, message);
    }

    function refreshDuplicateState() {
        const duplicates = calculateDuplicateKeys();
        updateDuplicateHighlights(duplicates);
        return duplicates;
    }

    function parseServerDuplicateResponse(response) {
        if (!response || !Array.isArray(response.duplicates) || !response.duplicates.length) {
            return null;
        }

        const canonicalKeys = [];
        const labels = [];

        response.duplicates.forEach(function(item) {
            if (!item || typeof item !== 'object') {
                return;
            }

            const canonical = typeof item.canonical === 'string' ? item.canonical.toLowerCase() : '';
            if (canonical && canonicalKeys.indexOf(canonical) === -1) {
                canonicalKeys.push(canonical);
            }

            if (Array.isArray(item.variants)) {
                item.variants.forEach(function(variant) {
                    if (typeof variant !== 'string') {
                        return;
                    }
                    const trimmed = variant.trim();
                    if (trimmed !== '' && labels.indexOf(trimmed) === -1) {
                        labels.push(trimmed);
                    }
                });
            }

            if (Array.isArray(item.conflicts)) {
                item.conflicts.forEach(function(conflict) {
                    if (!conflict || typeof conflict !== 'object') {
                        return;
                    }
                    const rawName = typeof conflict.name === 'string' ? conflict.name.trim() : '';
                    if (rawName !== '' && labels.indexOf(rawName) === -1) {
                        labels.push(rawName);
                    }
                });
            } else if (typeof item.canonical === 'string') {
                const trimmedCanonical = item.canonical.trim();
                if (trimmedCanonical !== '' && labels.indexOf(trimmedCanonical) === -1) {
                    labels.push(trimmedCanonical);
                }
            }
        });

        if (!canonicalKeys.length && !labels.length) {
            return null;
        }

        return {
            canonicalKeys: canonicalKeys,
            labels: labels,
        };
    }

    function copyToClipboard(text) {
        if (navigator.clipboard && window.isSecureContext) {
            return navigator.clipboard.writeText(text);
        }

        const textArea = document.createElement('textarea');
        textArea.value = text;
        textArea.style.position = 'fixed';
        textArea.style.left = '-999999px';
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();
        try {
            document.execCommand('copy');
        } catch (err) {
            console.error('Fallback copy failed', err); // eslint-disable-line no-console
        }
        document.body.removeChild(textArea);
        return Promise.resolve();
    }

    function generateCss(registry) {
        if (!registry || !registry.length) {
            return ':root {\n}\n';
        }

        const lines = registry.map(function(token) {
            return '    ' + token.name + ': ' + token.value + ';';
        });

        return ':root {\n' + lines.join('\n') + '\n}';
    }

    function applyCss(css) {
        if (cssTextarea.length) {
            cssTextarea.val(css);
        }
        if (previewStyle.length) {
            previewStyle.text(css);
        }
    }

    function refreshCssFromTokens() {
        applyCss(generateCss(tokens));
    }

    function normalizeName(value) {
        if (typeof value !== 'string') {
            return '';
        }
        let name = value.trim();
        if (name === '') {
            return '';
        }
        if (name.indexOf('--') !== 0) {
            name = '--' + name.replace(/^-+/, '');
        }
        return name.replace(/[^a-zA-Z0-9_\-]/g, '-');
    }

    function ensureGroupDatalist(groups) {
        let datalist = document.getElementById(groupDatalistId);
        if (!datalist) {
            datalist = document.createElement('datalist');
            datalist.id = groupDatalistId;
            builder.parent().append(datalist);
        }
        const $datalist = $(datalist);
        $datalist.empty();
        const seen = new Set();
        groups.forEach(function(group) {
            const key = group.trim();
            if (key === '' || seen.has(key)) {
                return;
            }
            seen.add(key);
            $('<option>').attr('value', key).appendTo($datalist);
        });
    }

    function createField(label, input) {
        const field = $('<label>', { class: 'ssc-token-field' });
        field.append($('<span>', { class: 'ssc-token-field__label', text: label }));
        field.append(input);
        return field;
    }

    function createTokenRow(token, index) {
        const row = $('<div>', { class: 'ssc-token-row', 'data-index': index });
        const typeOptions = Object.keys(tokenTypes);
        const valueType = token.type && tokenTypes[token.type] ? tokenTypes[token.type].input : 'text';

        const nameInput = $('<input>', {
            type: 'text',
            class: 'regular-text token-field-input token-name',
            value: token.name || '',
        });

        let valueInput;
        if (valueType === 'color') {
            const hasHexValue = typeof token.value === 'string' && /^#([0-9a-f]{3}|[0-9a-f]{6})$/i.test(token.value.trim());
            if (hasHexValue) {
                valueInput = $('<input>', {
                    type: 'color',
                    class: 'token-field-input token-value',
                    value: token.value.trim(),
                });
            } else {
                valueInput = $('<input>', {
                    type: 'text',
                    class: 'token-field-input token-value',
                    value: token.value || '',
                    placeholder: '#000000',
                });
            }
        } else {
            valueInput = $('<input>', {
                type: valueType === 'number' ? 'number' : 'text',
                class: 'token-field-input token-value',
                value: token.value || '',
            });
            if (valueType === 'number') {
                valueInput.attr('step', '0.01');
            }
        }

        const typeSelect = $('<select>', { class: 'token-field-input token-type' });
        typeOptions.forEach(function(optionKey) {
            const optionMeta = tokenTypes[optionKey];
            const optionLabel = optionMeta && optionMeta.label ? optionMeta.label : optionKey;
            const option = $('<option>', { value: optionKey, text: optionLabel });
            if (optionKey === token.type) {
                option.prop('selected', true);
            }
            typeSelect.append(option);
        });

        const groupInput = $('<input>', {
            type: 'text',
            class: 'token-field-input token-group',
            value: token.group || 'Général',
            list: groupDatalistId,
        });

        const descriptionInput = $('<textarea>', {
            class: 'token-field-input token-description',
            rows: 2,
            text: token.description || '',
        });

        const deleteButton = $('<button>', {
            type: 'button',
            class: 'button button-link-delete token-delete',
            text: i18n.deleteLabel || 'Supprimer',
        });

        row.append(createField(i18n.nameLabel || 'Nom', nameInput));
        row.append(createField(i18n.valueLabel || 'Valeur', valueInput));
        row.append(createField(i18n.typeLabel || 'Type', typeSelect));
        row.append(createField(i18n.groupLabel || 'Groupe', groupInput));
        row.append(createField(i18n.descriptionLabel || 'Description', descriptionInput));
        row.append(deleteButton);

        return row;
    }

    function renderTokens() {
        if (!builder.length) {
            return;
        }

        builder.empty();

        if (!tokens.length) {
            builder.append($('<p>', {
                class: 'ssc-token-empty',
                text: i18n.emptyState || 'Aucun token pour le moment. Utilisez le bouton ci-dessous pour commencer.',
            }));
            ensureGroupDatalist(['Général']);
            updateDuplicateHighlights([]);
            return;
        }

        const groups = {};
        const order = [];

        tokens.forEach(function(token, index) {
            const groupName = (token.group || 'Général').trim() || 'Général';
            if (!groups[groupName]) {
                groups[groupName] = [];
                order.push(groupName);
            }
            groups[groupName].push({ token: token, index: index });
        });

        ensureGroupDatalist(order);

        order.forEach(function(groupName) {
            const section = $('<div>', { class: 'ssc-token-group' });
            section.append($('<h4>', { text: groupName }));
            groups[groupName].forEach(function(item) {
                section.append(createTokenRow(item.token, item.index));
            });
            builder.append(section);
        });

        refreshDuplicateState();
    }

    function addToken() {
        tokens.push({
            name: '--nouveau-token',
            value: '#ffffff',
            type: tokenTypes.color ? 'color' : 'text',
            description: '',
            group: 'Général',
        });
        hasLocalChanges = true;
        renderTokens();
        refreshCssFromTokens();
    }

    function removeToken(index) {
        tokens.splice(index, 1);
        hasLocalChanges = true;
        renderTokens();
        refreshCssFromTokens();
    }

    function updateToken(index, key, value) {
        if (!tokens[index]) {
            return;
        }
        tokens[index][key] = value;
        hasLocalChanges = true;
    }

    function fetchTokensFromServer() {
        if (!restRoot) {
            return;
        }
        $.ajax({
            url: restRoot + 'tokens',
            method: 'GET',
            beforeSend: function(xhr) {
                if (restNonce) {
                    xhr.setRequestHeader('X-WP-Nonce', restNonce);
                }
            },
        }).done(function(response) {
            if (!hasLocalChanges) {
                if (response && Array.isArray(response.tokens)) {
                    tokens = response.tokens;
                    renderTokens();
                    hasLocalChanges = false;
                }
                if (response && typeof response.css === 'string') {
                    applyCss(response.css);
                } else {
                    refreshCssFromTokens();
                }
            } else {
                refreshCssFromTokens();
            }
        });
    }

    function saveTokens() {
        if (!restRoot) {
            const deferred = $.Deferred();
            deferred.reject();
            return deferred.promise();
        }

        const duplicates = refreshDuplicateState();
        if (duplicates.length) {
            const labels = buildDuplicateLabels(duplicates);
            notifyDuplicateError(labels);
            const deferred = $.Deferred();
            deferred.reject({ duplicates: duplicates, labels: labels, source: 'local' });
            return deferred.promise();
        }

        return $.ajax({
            url: restRoot + 'tokens',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ tokens: tokens }),
            beforeSend: function(xhr) {
                if (restNonce) {
                    xhr.setRequestHeader('X-WP-Nonce', restNonce);
                }
            },
        }).done(function(response) {
            if (response && Array.isArray(response.tokens)) {
                tokens = response.tokens;
                renderTokens();
            }
            if (response && typeof response.css === 'string') {
                applyCss(response.css);
            } else {
                refreshCssFromTokens();
            }
            refreshDuplicateState();
            hasLocalChanges = false;
            if (typeof window.sscToast === 'function') {
                window.sscToast(i18n.saveSuccess || 'Tokens enregistrés');
            }
        }).fail(function(jqXHR) {
            const response = jqXHR && jqXHR.responseJSON ? jqXHR.responseJSON : null;
            const parsed = parseServerDuplicateResponse(response);
            if (parsed) {
                const message = response && typeof response.message === 'string' ? response.message : '';
                handleDuplicateConflict(parsed.canonicalKeys, parsed.labels, message);
            } else if (typeof window.sscToast === 'function') {
                window.sscToast(i18n.saveError || 'Impossible d’enregistrer les tokens.');
            }
        });
    }

    $(document).ready(function() {
        if (!builder.length || !cssTextarea.length) {
            return;
        }

        if (localized.css) {
            applyCss(localized.css);
        }

        renderTokens();
        refreshCssFromTokens();
        fetchTokensFromServer();

        $('#ssc-token-add').on('click', function(event) {
            event.preventDefault();
            addToken();
        });

        $('#ssc-tokens-save').on('click', function(event) {
            event.preventDefault();
            saveTokens();
        });

        $('#ssc-tokens-copy').on('click', function(event) {
            event.preventDefault();
            copyToClipboard(cssTextarea.val());
            if (typeof window.sscToast === 'function') {
                window.sscToast(i18n.copySuccess || 'Tokens copiés');
            }
        });

        if (reloadButton.length) {
            reloadButton.on('click', function(event) {
                event.preventDefault();
                if (hasLocalChanges) {
                    const confirmMessage = i18n.reloadConfirm || 'Des modifications locales non enregistrées seront perdues. Continuer ?';
                    if (!window.confirm(confirmMessage)) {
                        return;
                    }
                }
                hasLocalChanges = false;
                fetchTokensFromServer();
            });
        }

        builder.on('input', '.token-name', function() {
            const index = $(this).closest('.ssc-token-row').data('index');
            hasLocalChanges = true;
            updateToken(index, 'name', $(this).val());
            refreshCssFromTokens();
            refreshDuplicateState();
        });

        builder.on('blur', '.token-name', function() {
            const index = $(this).closest('.ssc-token-row').data('index');
            const normalized = normalizeName($(this).val());
            $(this).val(normalized);
            hasLocalChanges = true;
            updateToken(index, 'name', normalized);
            refreshCssFromTokens();
            refreshDuplicateState();
        });

        builder.on('input', '.token-value', function() {
            const index = $(this).closest('.ssc-token-row').data('index');
            hasLocalChanges = true;
            updateToken(index, 'value', $(this).val());
            refreshCssFromTokens();
            refreshDuplicateState();
        });

        builder.on('input', '.token-description', function() {
            const index = $(this).closest('.ssc-token-row').data('index');
            hasLocalChanges = true;
            updateToken(index, 'description', $(this).val());
        });

        builder.on('change', '.token-type', function() {
            const index = $(this).closest('.ssc-token-row').data('index');
            hasLocalChanges = true;
            updateToken(index, 'type', $(this).val());
            renderTokens();
            refreshCssFromTokens();
        });

        builder.on('change', '.token-group', function() {
            const index = $(this).closest('.ssc-token-row').data('index');
            const newGroup = $(this).val().trim() || 'Général';
            hasLocalChanges = true;
            updateToken(index, 'group', newGroup);
            renderTokens();
            refreshCssFromTokens();
        });

        builder.on('click', '.token-delete', function(event) {
            event.preventDefault();
            const index = $(this).closest('.ssc-token-row').data('index');
            removeToken(index);
        });
    });
})(jQuery);
