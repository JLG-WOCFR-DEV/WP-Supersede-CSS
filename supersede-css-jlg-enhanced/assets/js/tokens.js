(function($) {
    const restRoot = window.SSC && window.SSC.rest && window.SSC.rest.root ? window.SSC.rest.root : '';
    const restNonce = window.SSC && window.SSC.rest && window.SSC.rest.nonce ? window.SSC.rest.nonce : '';
    const localized = window.SSC_TOKENS_DATA || {};
    let defaultContext = typeof localized.defaultContext === 'string' ? localized.defaultContext : ':root';
    const defaultTokenTypes = {
        color: {
            label: 'Couleur',
            input: 'color',
            help: 'Utilisez un code hexadécimal (ex. #4f46e5) ou une variable existante.',
        },
        text: {
            label: 'Texte',
            input: 'text',
            placeholder: 'Ex. 16px ou clamp(1rem, 2vw, 2rem)',
            help: 'Idéal pour les valeurs libres (unités CSS, fonctions, etc.).',
        },
        number: {
            label: 'Nombre',
            input: 'number',
            help: 'Pour les valeurs strictement numériques (ex. 1.25).',
        },
        spacing: {
            label: 'Espacement',
            input: 'text',
            placeholder: 'Ex. 16px 24px',
            help: 'Convient aux marges/paddings ou aux espacements multiples.',
        },
        font: {
            label: 'Typographie',
            input: 'text',
            placeholder: 'Ex. "Inter", sans-serif',
            help: 'Définissez la pile de polices complète avec les guillemets requis.',
        },
        shadow: {
            label: 'Ombre',
            input: 'textarea',
            placeholder: '0 2px 4px rgba(15, 23, 42, 0.25)',
            rows: 3,
            help: 'Accepte plusieurs valeurs box-shadow, une par ligne si nécessaire.',
        },
        gradient: {
            label: 'Dégradé',
            input: 'textarea',
            placeholder: 'linear-gradient(135deg, #4f46e5, #7c3aed)',
            rows: 3,
            help: 'Pour les dégradés CSS complexes (linear-, radial-…).',
        },
        border: {
            label: 'Bordure',
            input: 'text',
            placeholder: 'Ex. 1px solid currentColor',
            help: 'Combinez largeur, style et couleur de bordure.',
        },
        dimension: {
            label: 'Dimensions',
            input: 'text',
            placeholder: 'Ex. 320px ou clamp(280px, 50vw, 480px)',
            help: 'Largeurs/hauteurs ou tailles maximales avec clamp/min/max.',
        },
        transition: {
            label: 'Transition',
            input: 'textarea',
            placeholder: 'all 0.3s ease-in-out\ncolor 150ms ease',
            rows: 2,
            help: 'Définissez des transitions multi-propriétés, une par ligne.',
        },
    };

    const contextOptions = Array.isArray(localized.contexts) ? localized.contexts : [];
    const contextOptionValues = [];
    const contextMetaMap = new Map();
    const activePreviewContexts = new Set();
    let contextsDirty = false;

    defaultContext = normalizeContextValue(defaultContext, ':root');

    contextOptions.forEach(function(context) {
        registerContextMeta(context, true);
    });

    if (contextOptionValues.indexOf(defaultContext) === -1) {
        registerContextMeta({ value: defaultContext, label: defaultContext }, true);
    }

    let tokens = prepareIncomingTokens(localized.tokens);
    let hasLocalChanges = false;
    let beforeUnloadHandler = null;
    const tokenTypes = $.extend(true, {}, defaultTokenTypes, localized.types || {});
    const i18n = localized.i18n || {};

    function getUnsavedChangesMessage() {
        return i18n.reloadConfirm || 'Des modifications locales non enregistrées seront perdues. Continuer ?';
    }

    function detachBeforeUnloadHandler() {
        if (!beforeUnloadHandler) {
            return;
        }

        window.removeEventListener('beforeunload', beforeUnloadHandler);
        beforeUnloadHandler = null;
    }

    function ensureBeforeUnloadHandler() {
        if (beforeUnloadHandler) {
            return;
        }

        beforeUnloadHandler = function(event) {
            if (!hasLocalChanges) {
                return undefined;
            }

            const message = getUnsavedChangesMessage();
            event.preventDefault();
            event.returnValue = message;
            return message;
        };

        window.addEventListener('beforeunload', beforeUnloadHandler);
    }

    function setHasLocalChanges(value) {
        hasLocalChanges = Boolean(value);
        if (hasLocalChanges) {
            ensureBeforeUnloadHandler();
        } else {
            detachBeforeUnloadHandler();
        }
    }

    const builder = $('#ssc-token-builder');
    const cssTextarea = $('#ssc-tokens');
    const reloadButton = $('#ssc-tokens-reload');
    const previewStyle = $('#ssc-tokens-preview-style');
    const previewContextSwitcher = $('#ssc-preview-context-switcher');
    const previewContextPanel = previewContextSwitcher.length ? previewContextSwitcher.closest('.ssc-preview-context-panel') : $();
    const previewContainer = $('#ssc-tokens-preview');
    const groupDatalistId = 'ssc-token-groups-list';
    const duplicateRowClass = 'ssc-token-row--duplicate';
    const duplicateInputClass = 'token-field-input--duplicate';

    function getDefaultTypeKey() {
        if (tokenTypes.text) {
            return 'text';
        }

        const keys = Object.keys(tokenTypes);
        return keys.length ? keys[0] : 'text';
    }

    function getTypeMeta(typeKey) {
        if (typeKey && Object.prototype.hasOwnProperty.call(tokenTypes, typeKey)) {
            return tokenTypes[typeKey];
        }

        const fallbackKey = getDefaultTypeKey();
        return tokenTypes[fallbackKey] || { label: fallbackKey, input: 'text' };
    }

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
        if (!Array.isArray(registry) || !registry.length) {
            return defaultContext + ' {\n}\n';
        }

        const groups = {};
        const order = [];

        registry.forEach(function(token) {
            if (!token || typeof token !== 'object') {
                return;
            }

            const context = normalizeContextValue(token.context, defaultContext);
            if (!groups[context]) {
                groups[context] = [];
                order.push(context);
            }
            groups[context].push(token);
        });

        const sections = order.map(function(context) {
            const lines = groups[context].map(function(token) {
                const name = token && typeof token.name === 'string' ? token.name : '';
                const rawValue = token && token.value != null ? String(token.value) : '';
                const segments = rawValue.split(/\r?\n/);
                const firstSegment = segments.shift() || '';
                let line = '    ' + name + ': ' + firstSegment;

                if (segments.length) {
                    const indented = segments.map(function(segment) {
                        return '        ' + segment;
                    });
                    line += '\n' + indented.join('\n');
                }

                return line + ';';
            });

            return context + ' {\n' + lines.join('\n') + '\n}';
        });

        return sections.join('\n\n');
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

    function normalizeContextValue(value, fallbackValue) {
        const fallbackContext = typeof fallbackValue === 'string' && fallbackValue.trim() !== ''
            ? fallbackValue.trim()
            : ':root';

        if (typeof value !== 'string') {
            return fallbackContext;
        }

        const trimmed = value.trim();
        if (trimmed === '') {
            return fallbackContext;
        }

        const collapsed = trimmed.replace(/\s+/g, ' ');
        const sanitized = collapsed.replace(/\{/g, '').trim();

        return sanitized === '' ? fallbackContext : sanitized;
    }

    function registerContextMeta(meta, addToOrder) {
        if (!meta || typeof meta.value !== 'string') {
            return null;
        }

        const normalizedValue = normalizeContextValue(meta.value, defaultContext);
        const existing = contextMetaMap.get(normalizedValue);
        const nextMeta = existing ? $.extend(true, {}, existing) : { value: normalizedValue };
        let mutated = !existing;

        nextMeta.value = normalizedValue;

        const providedLabel = typeof meta.label === 'string' ? meta.label.trim() : '';
        const existingLabel = existing && typeof existing.label === 'string' ? existing.label : '';
        if (providedLabel !== '') {
            if (providedLabel !== existingLabel) {
                nextMeta.label = providedLabel;
                mutated = true;
            }
        } else if (!nextMeta.label || nextMeta.label.trim() === '') {
            nextMeta.label = normalizedValue;
            mutated = mutated || !existing;
        }

        if (meta.preview && typeof meta.preview === 'object') {
            const existingPreview = existing && existing.preview ? JSON.stringify(existing.preview) : null;
            const incomingPreview = JSON.stringify(meta.preview);
            if (existingPreview !== incomingPreview) {
                nextMeta.preview = meta.preview;
                mutated = true;
            }
        }

        contextMetaMap.set(normalizedValue, nextMeta);

        if (addToOrder && contextOptionValues.indexOf(normalizedValue) === -1) {
            contextOptionValues.push(normalizedValue);
            mutated = true;
        }

        if (mutated) {
            contextsDirty = true;
        }

        return nextMeta;
    }

    function getContextMeta(contextValue) {
        if (typeof contextValue !== 'string') {
            return null;
        }

        const normalized = normalizeContextValue(contextValue, defaultContext);
        if (contextMetaMap.has(normalized)) {
            return contextMetaMap.get(normalized);
        }

        return registerContextMeta({ value: normalized }, false);
    }

    function prepareIncomingTokens(collection) {
        if (!Array.isArray(collection)) {
            return [];
        }

        const prepared = [];

        collection.forEach(function(item) {
            if (!item || typeof item !== 'object') {
                return;
            }

            const clone = $.extend(true, {}, item);
            clone.context = normalizeContextValue(clone.context, defaultContext);
            registerContextMeta({ value: clone.context }, true);
            prepared.push(clone);
        });

        return prepared;
    }

    function updateSupportedContextsFromResponse(payload) {
        if (!payload || typeof payload !== 'object') {
            return;
        }

        if (Array.isArray(payload.contexts)) {
            payload.contexts.forEach(function(context) {
                registerContextMeta(context, true);
            });
        }

        if (typeof payload.defaultContext === 'string') {
            const normalizedDefault = normalizeContextValue(payload.defaultContext, defaultContext);
            if (normalizedDefault !== defaultContext) {
                defaultContext = normalizedDefault;
                registerContextMeta({ value: defaultContext }, true);
            }
        }
    }

    function buildPreviewContextId(value) {
        return 'ssc-preview-context-' + normalizeContextValue(value, defaultContext).replace(/[^a-zA-Z0-9_-]/g, '-');
    }

    function applyPreviewContext(meta, shouldEnable) {
        if (!meta || !previewContainer.length) {
            return;
        }

        const normalized = normalizeContextValue(meta.value, defaultContext);
        const config = meta.preview && typeof meta.preview === 'object' ? meta.preview : null;

        if (shouldEnable) {
            activePreviewContexts.add(normalized);
        } else {
            activePreviewContexts.delete(normalized);
        }

        if (!config) {
            return;
        }

        if (config.type === 'class' && config.value) {
            previewContainer.toggleClass(config.value, shouldEnable);
            return;
        }

        if (config.type === 'attribute' && config.name) {
            if (shouldEnable) {
                previewContainer.attr(config.name, config.value != null ? config.value : '');
            } else {
                let keepAttribute = false;
                activePreviewContexts.forEach(function(activeValue) {
                    if (keepAttribute) {
                        return;
                    }
                    const activeMeta = contextMetaMap.get(activeValue);
                    if (!activeMeta || !activeMeta.preview) {
                        return;
                    }
                    const activeConfig = activeMeta.preview;
                    if (activeConfig.type === 'attribute' && activeConfig.name === config.name) {
                        keepAttribute = true;
                        previewContainer.attr(activeConfig.name, activeConfig.value != null ? activeConfig.value : '');
                    }
                });
                if (!keepAttribute) {
                    previewContainer.removeAttr(config.name);
                }
            }
        }
    }

    function handlePreviewContextChange(event) {
        const checkbox = $(event.target);
        if (!checkbox.length) {
            return;
        }

        const rawValue = checkbox.data('context');
        const meta = getContextMeta(rawValue);
        applyPreviewContext(meta, checkbox.is(':checked'));
    }

    function buildPreviewContextSwitch() {
        if (!previewContextSwitcher.length) {
            contextsDirty = false;
            return;
        }

        if (!contextsDirty && previewContextSwitcher.children().length) {
            return;
        }

        const contextsForPreview = contextOptionValues
            .map(function(value) {
                return contextMetaMap.get(value);
            })
            .filter(function(meta) {
                return meta && meta.value !== defaultContext && meta.preview && typeof meta.preview === 'object';
            });

        const toDisable = [];
        activePreviewContexts.forEach(function(activeValue) {
            const stillAvailable = contextsForPreview.some(function(meta) {
                return meta.value === activeValue;
            });
            if (!stillAvailable) {
                toDisable.push(activeValue);
            }
        });
        toDisable.forEach(function(value) {
            const meta = contextMetaMap.get(value);
            if (meta) {
                applyPreviewContext(meta, false);
            } else {
                activePreviewContexts.delete(value);
            }
        });

        if (!contextsForPreview.length) {
            previewContextSwitcher.empty();
            previewContextSwitcher.attr('hidden', 'hidden');
            if (previewContextPanel.length) {
                previewContextPanel.attr('hidden', 'hidden').attr('aria-hidden', 'true');
            }
            contextsDirty = false;
            return;
        }

        if (previewContextPanel.length) {
            previewContextPanel.removeAttr('hidden').attr('aria-hidden', 'false');
        }
        previewContextSwitcher.removeAttr('hidden');
        previewContextSwitcher.empty();
        previewContextSwitcher.off('change', 'input[type="checkbox"]', handlePreviewContextChange);

        contextsForPreview.forEach(function(meta) {
            const contextId = buildPreviewContextId(meta.value);
            const isActive = activePreviewContexts.has(meta.value);
            const checkbox = $('<input>', {
                type: 'checkbox',
                id: contextId,
                'data-context': meta.value,
                class: 'ssc-preview-context-toggle-input',
            }).prop('checked', isActive);
            const label = $('<label>', {
                class: 'ssc-preview-context-toggle',
                for: contextId,
            });
            label.append(checkbox);
            label.append($('<span>', { text: meta.label || meta.value }));
            previewContextSwitcher.append(label);
            applyPreviewContext(meta, isActive);
        });

        previewContextSwitcher.on('change', 'input[type="checkbox"]', handlePreviewContextChange);
        contextsDirty = false;
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

    function createField(label, input, helpText) {
        const field = $('<label>', { class: 'ssc-token-field' });
        field.append($('<span>', { class: 'ssc-token-field__label', text: label }));
        field.append(input);
        if (helpText) {
            field.append($('<span>', {
                class: 'ssc-token-field__help',
                text: helpText,
            }));
        }
        return field;
    }

    function createTokenRow(token, index) {
        const row = $('<div>', { class: 'ssc-token-row', 'data-index': index });
        const typeOptions = Object.keys(tokenTypes);
        const resolvedType = (token && typeof token.type === 'string' && tokenTypes[token.type])
            ? token.type
            : getDefaultTypeKey();
        if (token.type !== resolvedType) {
            token.type = resolvedType;
        }
        const typeMeta = getTypeMeta(resolvedType);
        const inputKind = typeMeta.input || 'text';
        const currentValue = token.value == null ? '' : String(token.value);
        const resolvedContext = normalizeContextValue(token.context, defaultContext);
        if (token.context !== resolvedContext) {
            token.context = resolvedContext;
        }

        const nameInput = $('<input>', {
            type: 'text',
            class: 'regular-text token-field-input token-name',
            value: token.name || '',
        });

        let valueInput;
        if (inputKind === 'textarea') {
            valueInput = $('<textarea>', {
                class: 'token-field-input token-value',
                rows: typeMeta.rows || 3,
            });
            valueInput.val(currentValue);
        } else if (inputKind === 'color') {
            const hasHexValue = /^#([0-9a-f]{3}|[0-9a-f]{6})$/i.test(currentValue.trim());
            if (hasHexValue) {
                valueInput = $('<input>', {
                    type: 'color',
                    class: 'token-field-input token-value',
                    value: currentValue.trim(),
                });
            } else {
                valueInput = $('<input>', {
                    type: 'text',
                    class: 'token-field-input token-value',
                    value: currentValue,
                    placeholder: '#000000',
                });
            }
        } else {
            valueInput = $('<input>', {
                type: inputKind === 'number' ? 'number' : 'text',
                class: 'token-field-input token-value',
                value: currentValue,
            });
            if (inputKind === 'number') {
                valueInput.attr('step', '0.01');
            }
        }

        if (typeMeta.placeholder && valueInput && valueInput.attr) {
            if (inputKind !== 'color' || valueInput.attr('type') === 'text') {
                valueInput.attr('placeholder', typeMeta.placeholder);
            }
        }
        if (typeMeta.help && valueInput && valueInput.attr) {
            const helpId = 'token-help-' + resolvedType + '-' + index;
            valueInput.attr('aria-describedby', helpId);
            valueInput.data('help-id', helpId);
        }

        const typeSelect = $('<select>', { class: 'token-field-input token-type' });
        typeOptions.forEach(function(optionKey) {
            const optionMeta = getTypeMeta(optionKey);
            const optionLabel = optionMeta && optionMeta.label ? optionMeta.label : optionKey;
            const option = $('<option>', { value: optionKey, text: optionLabel });
            if (optionKey === resolvedType) {
                option.prop('selected', true);
            }
            typeSelect.append(option);
        });
        typeSelect.val(resolvedType);

        const groupInput = $('<input>', {
            type: 'text',
            class: 'token-field-input token-group',
            value: token.group || 'Général',
            list: groupDatalistId,
        });

        const contextSelect = $('<select>', { class: 'token-field-input token-context' });
        const appendedContexts = new Set();

        contextOptionValues.forEach(function(optionValue) {
            const meta = contextMetaMap.get(optionValue);
            if (!meta) {
                return;
            }

            const option = $('<option>', {
                value: meta.value,
                text: meta.label || meta.value,
            });

            if (meta.value === resolvedContext) {
                option.prop('selected', true);
            }

            contextSelect.append(option);
            appendedContexts.add(meta.value);
        });

        if (!appendedContexts.has(resolvedContext)) {
            contextSelect.append($('<option>', {
                value: resolvedContext,
                text: resolvedContext,
                selected: true,
            }));
        }

        contextSelect.val(resolvedContext);

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

        const nameField = createField(i18n.nameLabel || 'Nom', nameInput);
        const valueField = createField(i18n.valueLabel || 'Valeur', valueInput, typeMeta.help);
        const typeField = createField(i18n.typeLabel || 'Type', typeSelect);
        const groupField = createField(i18n.groupLabel || 'Groupe', groupInput);
        const contextField = createField(i18n.contextLabel || 'Contexte', contextSelect);
        const descriptionField = createField(i18n.descriptionLabel || 'Description', descriptionInput);

        if (typeMeta.help && valueField) {
            const helpElement = valueField.find('.ssc-token-field__help');
            const helpId = valueInput.data('help-id');
            if (helpElement.length && helpId) {
                helpElement.attr('id', helpId);
            }
        }

        row.append(nameField);
        row.append(valueField);
        row.append(typeField);
        row.append(groupField);
        row.append(contextField);
        row.append(descriptionField);
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
        const defaultType = tokenTypes.color ? 'color' : getDefaultTypeKey();
        const defaultMeta = getTypeMeta(defaultType);
        let defaultValue = defaultType === 'color' ? '#ffffff' : '';
        if (defaultType !== 'color' && defaultMeta && typeof defaultMeta.placeholder === 'string') {
            defaultValue = defaultMeta.placeholder;
        }

        tokens.push({
            name: '--nouveau-token',
            value: defaultValue,
            type: defaultType,
            description: '',
            group: 'Général',
            context: defaultContext,
        });
        setHasLocalChanges(true);
        renderTokens();
        refreshCssFromTokens();
    }

    function removeToken(index) {
        tokens.splice(index, 1);
        setHasLocalChanges(true);
        renderTokens();
        refreshCssFromTokens();
    }

    function updateToken(index, key, value) {
        if (!tokens[index]) {
            return;
        }
        if (key === 'context') {
            tokens[index][key] = normalizeContextValue(value, defaultContext);
        } else {
            tokens[index][key] = value;
        }
        setHasLocalChanges(true);
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
            updateSupportedContextsFromResponse(response);
            if (!hasLocalChanges) {
                if (response && Array.isArray(response.tokens)) {
                    tokens = prepareIncomingTokens(response.tokens);
                    renderTokens();
                    buildPreviewContextSwitch();
                    setHasLocalChanges(false);
                }
                if (response && typeof response.css === 'string') {
                    applyCss(response.css);
                } else {
                    refreshCssFromTokens();
                }
            } else {
                refreshCssFromTokens();
            }

            if (contextsDirty) {
                buildPreviewContextSwitch();
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
            updateSupportedContextsFromResponse(response);
            if (response && Array.isArray(response.tokens)) {
                tokens = prepareIncomingTokens(response.tokens);
                renderTokens();
                buildPreviewContextSwitch();
            }
            if (response && typeof response.css === 'string') {
                applyCss(response.css);
            } else {
                refreshCssFromTokens();
            }
            refreshDuplicateState();
            setHasLocalChanges(false);
            if (typeof window.sscToast === 'function') {
                window.sscToast(i18n.saveSuccess || 'Tokens enregistrés');
            }

            if (contextsDirty) {
                buildPreviewContextSwitch();
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
        const tokenLayout = $('.ssc-token-layout');
        const helpPane = $('#ssc-token-help');
        const helpToggle = $('#ssc-token-help-toggle');
        const helpStorageKey = 'ssc-token-help-collapsed';

        if (helpToggle.length && helpPane.length && tokenLayout.length) {
            const expandedLabel = helpToggle.data('expandedLabel');
            const collapsedLabel = helpToggle.data('collapsedLabel');

            const setHelpState = function(collapsed, persist) {
                tokenLayout.toggleClass('ssc-help-collapsed', collapsed);
                helpPane.attr('aria-hidden', collapsed ? 'true' : 'false');

                if (collapsed) {
                    helpPane.attr('hidden', 'hidden');
                } else {
                    helpPane.removeAttr('hidden');
                }

                helpToggle.attr('aria-expanded', collapsed ? 'false' : 'true');

                const label = collapsed ? collapsedLabel : expandedLabel;
                if (typeof label === 'string' && label.length) {
                    helpToggle.text(label);
                }

                if (persist) {
                    try {
                        window.localStorage.setItem(helpStorageKey, collapsed ? '1' : '0');
                    } catch (storageError) {
                        // Ignore storage errors (private mode, quota, etc.).
                    }
                }
            };

            let initialCollapsed = false;
            try {
                initialCollapsed = window.localStorage.getItem(helpStorageKey) === '1';
            } catch (storageError) {
                initialCollapsed = false;
            }

            setHelpState(initialCollapsed, false);

            helpToggle.on('click', function(event) {
                event.preventDefault();
                const collapsed = !tokenLayout.hasClass('ssc-help-collapsed');
                setHelpState(collapsed, true);
            });
        }

        if (!builder.length || !cssTextarea.length) {
            return;
        }

        if (localized.css) {
            applyCss(localized.css);
        }

        renderTokens();
        buildPreviewContextSwitch();
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
                    const confirmMessage = getUnsavedChangesMessage();
                    if (!window.confirm(confirmMessage)) {
                        return;
                    }
                }
                setHasLocalChanges(false);
                fetchTokensFromServer();
            });
        }

        builder.on('input', '.token-name', function() {
            const index = $(this).closest('.ssc-token-row').data('index');
            setHasLocalChanges(true);
            updateToken(index, 'name', $(this).val());
            refreshCssFromTokens();
            refreshDuplicateState();
        });

        builder.on('blur', '.token-name', function() {
            const index = $(this).closest('.ssc-token-row').data('index');
            const normalized = normalizeName($(this).val());
            $(this).val(normalized);
            setHasLocalChanges(true);
            updateToken(index, 'name', normalized);
            refreshCssFromTokens();
            refreshDuplicateState();
        });

        builder.on('input', '.token-value', function() {
            const index = $(this).closest('.ssc-token-row').data('index');
            setHasLocalChanges(true);
            updateToken(index, 'value', $(this).val());
            refreshCssFromTokens();
            refreshDuplicateState();
        });

        builder.on('input', '.token-description', function() {
            const index = $(this).closest('.ssc-token-row').data('index');
            setHasLocalChanges(true);
            updateToken(index, 'description', $(this).val());
        });

        builder.on('change', '.token-type', function() {
            const index = $(this).closest('.ssc-token-row').data('index');
            setHasLocalChanges(true);
            updateToken(index, 'type', $(this).val());
            renderTokens();
            refreshCssFromTokens();
        });

        builder.on('change', '.token-group', function() {
            const index = $(this).closest('.ssc-token-row').data('index');
            const newGroup = $(this).val().trim() || 'Général';
            setHasLocalChanges(true);
            updateToken(index, 'group', newGroup);
            renderTokens();
            refreshCssFromTokens();
        });

        builder.on('change', '.token-context', function() {
            const index = $(this).closest('.ssc-token-row').data('index');
            const normalizedContext = normalizeContextValue($(this).val(), defaultContext);
            $(this).val(normalizedContext);
            setHasLocalChanges(true);
            updateToken(index, 'context', normalizedContext);
            refreshCssFromTokens();
        });

        builder.on('click', '.token-delete', function(event) {
            event.preventDefault();
            const index = $(this).closest('.ssc-token-row').data('index');
            removeToken(index);
        });
    });
})(jQuery);
