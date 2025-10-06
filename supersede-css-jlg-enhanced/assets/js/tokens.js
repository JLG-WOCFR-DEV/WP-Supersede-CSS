(function($) {
    const restRoot = window.SSC && window.SSC.rest && window.SSC.rest.root ? window.SSC.rest.root : '';
    const restNonce = window.SSC && window.SSC.rest && window.SSC.rest.nonce ? window.SSC.rest.nonce : '';
    const localized = window.SSC_TOKENS_DATA || {};
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

    const defaultContext = typeof localized.defaultContext === 'string' && localized.defaultContext.trim()
        ? localized.defaultContext.trim()
        : ':root';
    let contextOptions = [];
    let contextOptionsDirty = false;
    initializeContextOptions(Array.isArray(localized.contexts) ? localized.contexts : []);

    let tokens = Array.isArray(localized.tokens) ? localized.tokens.slice() : [];
    let hasLocalChanges = false;
    let beforeUnloadHandler = null;
    const tokenTypes = $.extend(true, {}, defaultTokenTypes, localized.types || {});
    const i18n = localized.i18n || {};
    const defaultGroupName = 'Général';
    const diacriticRegex = /[\u0300-\u036f]/g;
    const hasStringNormalize = typeof ''.normalize === 'function';
    const localUiState = {
        filters: {
            query: '',
            type: '',
        },
    };

    tokens = normalizeRegistryTokens(tokens);

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

    function setHasLocalChanges(value, metadata) {
        hasLocalChanges = Boolean(value);
        if (metadata && metadata.filters) {
            const filters = metadata.filters;
            if (Object.prototype.hasOwnProperty.call(filters, 'query')) {
                localUiState.filters.query = typeof filters.query === 'string' ? filters.query : '';
            }
            if (Object.prototype.hasOwnProperty.call(filters, 'type')) {
                localUiState.filters.type = typeof filters.type === 'string' ? filters.type : '';
            }
        }
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
    const previewContextSelect = $('#ssc-preview-context');
    const previewContainer = $('#ssc-tokens-preview');
    let previewAppliedClasses = [];
    let previewAppliedAttributes = [];
    let activePreviewContext = defaultContext;
    const groupDatalistId = 'ssc-token-groups-list';
    const duplicateRowClass = 'ssc-token-row--duplicate';
    const duplicateInputClass = 'token-field-input--duplicate';
    const searchInput = $('#ssc-token-search');
    const typeFilterSelect = $('#ssc-token-type-filter');
    const resultsCounter = $('#ssc-token-results-count');

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

            const contextKey = normalizeContextValue(token.context).toLowerCase();
            const duplicateKey = contextKey + '::' + canonical;
            const rawValue = token.value == null ? '' : String(token.value);
            if (rawValue.trim() === '') {
                return;
            }

            if (seen[duplicateKey]) {
                if (duplicates.indexOf(duplicateKey) === -1) {
                    duplicates.push(duplicateKey);
                }
            } else {
                seen[duplicateKey] = true;
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
                    return typeof key === 'string' ? key : '';
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
            const contextKey = normalizeContextValue(token.context).toLowerCase();
            const duplicateKey = contextKey + '::' + canonical;
            if (!canonical || !canonicalSet.has(duplicateKey)) {
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
                return typeof key === 'string' ? key : '';
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
            const index = row.data('index');
            const token = typeof index === 'number' ? tokens[index] : null;
            const contextValue = token ? normalizeContextValue(token.context) : normalizeContextValue(row.find('.token-context').val());
            const duplicateKey = contextValue.toLowerCase() + '::' + canonical;
            const isDuplicate = canonical !== '' && canonicalSet.has(duplicateKey);
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
            const contextValue = typeof item.context === 'string' ? normalizeContextValue(item.context) : defaultContext;
            const duplicateKey = contextValue.toLowerCase() + '::' + canonical;
            if (canonical && canonicalKeys.indexOf(duplicateKey) === -1) {
                canonicalKeys.push(duplicateKey);
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
            return defaultContext + ' {\n}\n';
        }

        const grouped = {};
        const order = [];

        registry.forEach(function(token) {
            if (!token || typeof token !== 'object') {
                return;
            }

            const context = normalizeContextValue(token.context);
            if (!grouped[context]) {
                grouped[context] = [];
                order.push(context);
            }

            grouped[context].push(token);
        });

        const blocks = order.map(function(context) {
            const lines = grouped[context].map(function(token) {
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

        return blocks.join('\n\n');
    }

    function renderPreviewContextOptions() {
        if (!previewContextSelect.length) {
            return;
        }

        const options = getContextOptions();
        const defaultMeta = findContextOption(defaultContext);
        const defaultLabel = (defaultMeta && defaultMeta.label)
            ? defaultMeta.label
            : (i18n.previewContextDefault || defaultContext);

        let hasActive = false;
        options.forEach(function(option) {
            if (option && option.value === activePreviewContext) {
                hasActive = true;
            }
        });

        if (!hasActive) {
            activePreviewContext = defaultContext;
        }

        previewContextSelect.empty();
        previewContextSelect.append($('<option>', { value: defaultContext, text: defaultLabel }));

        options.forEach(function(option) {
            if (!option || typeof option !== 'object') {
                return;
            }
            if (option.value === defaultContext) {
                return;
            }
            const label = option.label || option.value;
            previewContextSelect.append($('<option>', { value: option.value, text: label }));
        });

        previewContextSelect.val(activePreviewContext);
    }

    function clearPreviewContext() {
        if (!previewContainer.length) {
            return;
        }

        previewAppliedClasses.forEach(function(className) {
            previewContainer.removeClass(className);
        });
        previewAppliedClasses = [];

        previewAppliedAttributes.forEach(function(attribute) {
            previewContainer.removeAttr(attribute);
        });
        previewAppliedAttributes = [];
    }

    function applyPreviewContext(value) {
        const normalized = ensureContextOption(value);
        activePreviewContext = normalized;

        commitContextOptions();

        if (!previewContainer.length) {
            return;
        }

        clearPreviewContext();

        const optionMeta = findContextOption(normalized);
        if (optionMeta && optionMeta.preview && typeof optionMeta.preview === 'object') {
            const previewMeta = optionMeta.preview;
            if (previewMeta.type === 'class' && typeof previewMeta.value === 'string') {
                previewMeta.value.split(/\s+/).forEach(function(className) {
                    const trimmed = className.trim();
                    if (!trimmed) {
                        return;
                    }
                    previewContainer.addClass(trimmed);
                    previewAppliedClasses.push(trimmed);
                });
            } else if (previewMeta.type === 'attribute' && typeof previewMeta.name === 'string') {
                const attributeName = previewMeta.name;
                const attributeValue = typeof previewMeta.value === 'string' ? previewMeta.value : '';
                previewContainer.attr(attributeName, attributeValue);
                previewAppliedAttributes.push(attributeName);
            }
        } else if (normalized.charAt(0) === '.') {
            normalized.split('.').forEach(function(part, index) {
                if (index === 0) {
                    return;
                }
                const trimmed = part.trim();
                if (!trimmed) {
                    return;
                }
                previewContainer.addClass(trimmed);
                previewAppliedClasses.push(trimmed);
            });
        } else if (normalized.charAt(0) === '[') {
            const attributeMatch = normalized.match(/^\[\s*([^=\s\]]+)(?:\s*=\s*(?:"([^\"]*)"|'([^\']*)'|([^\s\]]+)))?\s*\]$/);
            if (attributeMatch) {
                const attributeName = attributeMatch[1];
                const attributeValue = attributeMatch[2] || attributeMatch[3] || attributeMatch[4] || '';
                previewContainer.attr(attributeName, attributeValue);
                previewAppliedAttributes.push(attributeName);
            }
        }

        if (previewContextSelect.length) {
            previewContextSelect.val(activePreviewContext);
        }
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

    function getFilterState() {
        return {
            query: localUiState.filters.query || '',
            type: localUiState.filters.type || '',
        };
    }

    function updateFilters(partial) {
        if (!partial || typeof partial !== 'object') {
            return;
        }
        if (Object.prototype.hasOwnProperty.call(partial, 'query')) {
            const newQuery = partial.query;
            localUiState.filters.query = typeof newQuery === 'string' ? newQuery : '';
        }
        if (Object.prototype.hasOwnProperty.call(partial, 'type')) {
            const newType = partial.type;
            localUiState.filters.type = typeof newType === 'string' ? newType : '';
        }
        setHasLocalChanges(hasLocalChanges, {
            filters: {
                query: localUiState.filters.query,
                type: localUiState.filters.type,
            },
        });
    }

    function syncFilterControls() {
        const filters = getFilterState();
        if (searchInput.length) {
            searchInput.val(filters.query);
        }
        if (typeFilterSelect.length) {
            typeFilterSelect.val(filters.type);
        }
    }

    function normalizeSearchTerm(value) {
        if (typeof value !== 'string') {
            return '';
        }
        let term = value.trim();
        if (!term) {
            return '';
        }
        if (hasStringNormalize) {
            term = term.normalize('NFD').replace(diacriticRegex, '');
        }
        return term.toLowerCase();
    }

    function prepareSearchContent(value) {
        const stringValue = typeof value === 'string' ? value : '';
        if (!stringValue) {
            return { original: '', normalized: '', map: [] };
        }
        const characters = Array.from(stringValue);
        const normalizedCharacters = [];
        const map = [];
        characters.forEach(function(char, index) {
            let normalizedChar = char;
            if (hasStringNormalize && typeof normalizedChar.normalize === 'function') {
                normalizedChar = normalizedChar.normalize('NFD').replace(diacriticRegex, '');
            }
            if (!normalizedChar) {
                return;
            }
            for (let i = 0; i < normalizedChar.length; i += 1) {
                normalizedCharacters.push(normalizedChar[i].toLowerCase());
                map.push(index);
            }
        });
        return {
            original: stringValue,
            normalized: normalizedCharacters.join(''),
            map: map,
        };
    }

    function escapeHtml(value) {
        if (value == null) {
            return '';
        }
        return String(value).replace(/[&<>"']/g, function(match) {
            switch (match) {
                case '&':
                    return '&amp;';
                case '<':
                    return '&lt;';
                case '>':
                    return '&gt;';
                case '"':
                    return '&quot;';
                case "'":
                    return '&#039;';
                default:
                    return match;
            }
        });
    }

    function buildHighlightMarkup(value, normalizedQuery) {
        const stringValue = typeof value === 'string' ? value : '';
        if (!normalizedQuery) {
            return escapeHtml(stringValue);
        }
        const prepared = prepareSearchContent(stringValue);
        if (!prepared.normalized || !normalizedQuery) {
            return escapeHtml(stringValue);
        }
        const queryLength = normalizedQuery.length;
        if (!queryLength) {
            return escapeHtml(stringValue);
        }
        const ranges = [];
        let searchIndex = 0;
        while (searchIndex <= prepared.normalized.length) {
            const matchIndex = prepared.normalized.indexOf(normalizedQuery, searchIndex);
            if (matchIndex === -1) {
                break;
            }
            const endIndex = matchIndex + queryLength - 1;
            const startOriginal = prepared.map[matchIndex];
            const endOriginalIndex = prepared.map[endIndex];
            if (startOriginal == null || endOriginalIndex == null) {
                break;
            }
            ranges.push([startOriginal, endOriginalIndex + 1]);
            searchIndex = matchIndex + queryLength;
        }
        if (!ranges.length) {
            return escapeHtml(stringValue);
        }
        ranges.sort(function(a, b) {
            return a[0] - b[0];
        });
        const merged = [ranges[0].slice()];
        for (let i = 1; i < ranges.length; i += 1) {
            const current = ranges[i];
            const last = merged[merged.length - 1];
            if (current[0] <= last[1]) {
                last[1] = Math.max(last[1], current[1]);
            } else {
                merged.push(current.slice());
            }
        }
        let result = '';
        let pointer = 0;
        merged.forEach(function(range) {
            if (pointer < range[0]) {
                result += escapeHtml(stringValue.slice(pointer, range[0]));
            }
            result += '<mark>' + escapeHtml(stringValue.slice(range[0], range[1])) + '</mark>';
            pointer = range[1];
        });
        if (pointer < stringValue.length) {
            result += escapeHtml(stringValue.slice(pointer));
        }
        return result;
    }

    function computeTokenHighlights(token, normalizedQuery) {
        const fragments = [];
        if (!normalizedQuery) {
            return { hasMatch: true, fragments: fragments };
        }
        let hasMatch = false;
        const groupValue = (token && typeof token.group === 'string' ? token.group : '').trim() || defaultGroupName;
        const fields = [
            { key: 'name', label: i18n.nameLabel || 'Nom', value: token && typeof token.name === 'string' ? token.name : '' },
            { key: 'group', label: i18n.groupLabel || 'Groupe', value: groupValue },
            { key: 'description', label: i18n.descriptionLabel || 'Description', value: token && typeof token.description === 'string' ? token.description : '' },
            { key: 'context', label: i18n.contextLabel || 'Contexte', value: token && typeof token.context === 'string' ? token.context : defaultContext },
        ];
        fields.forEach(function(field) {
            const normalized = normalizeSearchTerm(field.value);
            if (normalized && normalized.indexOf(normalizedQuery) !== -1) {
                hasMatch = true;
                fragments.push({
                    key: field.key,
                    label: field.label,
                    value: field.value,
                    html: buildHighlightMarkup(field.value, normalizedQuery),
                });
            }
        });
        return { hasMatch: hasMatch, fragments: fragments };
    }

    function updateResultsCount(displayed, total) {
        if (!resultsCounter.length) {
            return;
        }
        let text = '';
        if (!total) {
            text = i18n.resultsCountZero || '';
        } else if (!displayed) {
            if (i18n.resultsCountZeroFiltered) {
                text = i18n.resultsCountZeroFiltered
                    .replace('%1$s', '0')
                    .replace('%2$s', String(total));
            } else if (i18n.resultsCountZero) {
                text = i18n.resultsCountZero;
            } else {
                text = '0 / ' + total;
            }
        } else {
            const template = displayed === 1 ? i18n.resultsCountSingular : i18n.resultsCountPlural;
            if (template) {
                text = template
                    .replace('%1$s', String(displayed))
                    .replace('%2$s', String(total));
            } else {
                text = displayed + ' / ' + total;
            }
        }
        resultsCounter.text(text);
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

    function normalizeContextValue(value) {
        if (typeof value !== 'string') {
            return defaultContext;
        }
        const trimmed = value.trim();
        if (!trimmed) {
            return defaultContext;
        }
        return trimmed.replace(/\s+/g, ' ');
    }

    function normalizeContextOption(option) {
        if (typeof option === 'string') {
            const normalizedValue = normalizeContextValue(option);
            return normalizedValue ? { value: normalizedValue, label: normalizedValue } : null;
        }

        if (!option || typeof option !== 'object') {
            return null;
        }

        const normalizedValue = normalizeContextValue(option.value);
        if (!normalizedValue) {
            return null;
        }

        const normalized = {
            value: normalizedValue,
            label: typeof option.label === 'string' && option.label.trim() ? option.label : normalizedValue,
        };

        if (option.preview && typeof option.preview === 'object') {
            normalized.preview = option.preview;
        }

        return normalized;
    }

    function initializeContextOptions(rawOptions) {
        const seen = new Set();
        contextOptions = [];

        let defaultMeta = null;
        if (Array.isArray(rawOptions)) {
            rawOptions.forEach(function(option) {
                const normalized = normalizeContextOption(option);
                if (!normalized) {
                    return;
                }
                if (normalized.value === defaultContext && defaultMeta === null) {
                    defaultMeta = normalized;
                }
            });
        }

        if (defaultMeta) {
            contextOptions.push(defaultMeta);
            seen.add(defaultMeta.value);
        } else {
            contextOptions.push({ value: defaultContext, label: defaultContext });
            seen.add(defaultContext);
        }

        if (Array.isArray(rawOptions)) {
            rawOptions.forEach(function(option) {
                const normalized = normalizeContextOption(option);
                if (!normalized) {
                    return;
                }
                const value = normalized.value;
                if (seen.has(value)) {
                    const existing = contextOptions.find(function(item) { return item.value === value; });
                    if (existing) {
                        if (!existing.preview && normalized.preview) {
                            existing.preview = normalized.preview;
                            contextOptionsDirty = true;
                        }
                        if (normalized.label && (!existing.label || existing.label === existing.value)) {
                            existing.label = normalized.label;
                            contextOptionsDirty = true;
                        }
                    }
                    return;
                }
                seen.add(value);
                contextOptions.push(normalized);
            });
        }

        contextOptionsDirty = true;
    }

    function findContextOption(value) {
        const normalized = normalizeContextValue(value);
        for (let index = 0; index < contextOptions.length; index++) {
            const option = contextOptions[index];
            if (option && option.value === normalized) {
                return option;
            }
        }
        return null;
    }

    function ensureContextOption(value, meta) {
        const normalized = normalizeContextValue(value);
        let option = findContextOption(normalized);

        if (option) {
            if (meta && meta.preview && !option.preview && typeof meta.preview === 'object') {
                option.preview = meta.preview;
                contextOptionsDirty = true;
            }
            if (meta && meta.label && (!option.label || option.label === option.value)) {
                option.label = meta.label;
                contextOptionsDirty = true;
            }
            return normalized;
        }

        const newOption = {
            value: normalized,
            label: meta && typeof meta.label === 'string' && meta.label.trim() ? meta.label : normalized,
        };

        if (meta && meta.preview && typeof meta.preview === 'object') {
            newOption.preview = meta.preview;
        }

        if (normalized === defaultContext) {
            contextOptions.unshift(newOption);
        } else {
            contextOptions.push(newOption);
        }

        contextOptionsDirty = true;

        return normalized;
    }

    function getContextOptions() {
        return contextOptions.slice();
    }

    function commitContextOptions() {
        if (!contextOptionsDirty) {
            return;
        }
        contextOptionsDirty = false;
        renderPreviewContextOptions();
    }

    function normalizeRegistryTokens(registry) {
        if (!Array.isArray(registry)) {
            return [];
        }

        return registry.map(function(token) {
            if (!token || typeof token !== 'object') {
                return {
                    name: '',
                    value: '',
                    type: getDefaultTypeKey(),
                    description: '',
                    group: defaultGroupName,
                    context: ensureContextOption(defaultContext),
                };
            }

            const normalized = Object.assign({}, token);
            normalized.name = typeof normalized.name === 'string' ? normalized.name : '';
            normalized.value = normalized.value == null ? '' : String(normalized.value);
            normalized.type = typeof normalized.type === 'string' ? normalized.type : getDefaultTypeKey();
            normalized.description = typeof normalized.description === 'string' ? normalized.description : '';
            normalized.group = typeof normalized.group === 'string' && normalized.group.trim() !== '' ? normalized.group : defaultGroupName;
            normalized.context = ensureContextOption(normalized.context);

            return normalized;
        });
    }

    function refreshContextRegistry() {
        if (!Array.isArray(tokens)) {
            return;
        }

        tokens.forEach(function(token) {
            if (!token || typeof token !== 'object') {
                return;
            }
            token.context = ensureContextOption(token.context);
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

    function createTokenRow(token, index, highlights) {
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
        const currentContext = ensureContextOption(token.context);
        token.context = currentContext;

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
            value: token.group || defaultGroupName,
            list: groupDatalistId,
        });

        const contextSelect = $('<select>', { class: 'token-field-input token-context' });
        const availableContexts = getContextOptions();
        availableContexts.forEach(function(option) {
            if (!option || typeof option !== 'object') {
                return;
            }
            contextSelect.append($('<option>', {
                value: option.value,
                text: option.label || option.value,
            }));
        });
        if (!contextSelect.children('option').filter(function() {
            return $(this).val() === currentContext;
        }).length) {
            contextSelect.append($('<option>', { value: currentContext, text: currentContext }));
        }
        contextSelect.val(currentContext);

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

        if (Array.isArray(highlights) && highlights.length) {
            row.addClass('ssc-token-row--matches');
            const highlightContainer = $('<div>', { class: 'ssc-token-row__matches' });
            if (i18n.matchesLabel) {
                highlightContainer.append($('<span>', {
                    class: 'ssc-token-row__matches-title',
                    text: i18n.matchesLabel,
                }));
            }
            highlights.forEach(function(fragment) {
                if (!fragment || typeof fragment !== 'object') {
                    return;
                }
                const item = $('<div>', { class: 'ssc-token-row__match' });
                if (fragment.label) {
                    item.append($('<span>', {
                        class: 'ssc-token-row__match-label',
                        text: fragment.label + ' : ',
                    }));
                }
                const valueSpan = $('<span>', { class: 'ssc-token-row__match-value' });
                if (fragment.html) {
                    valueSpan.html(fragment.html);
                } else if (fragment.value) {
                    valueSpan.text(fragment.value);
                }
                item.append(valueSpan);
                highlightContainer.append(item);
            });
            row.append(highlightContainer);
        }

        row.append(deleteButton);

        return row;
    }

    function renderTokens() {
        if (!builder.length) {
            commitContextOptions();
            return;
        }

        refreshContextRegistry();

        const totalTokens = tokens.length;
        const filters = getFilterState();
        const activeType = filters.type || '';
        const rawQuery = filters.query || '';
        const normalizedQuery = normalizeSearchTerm(rawQuery);

        builder.empty();

        const seenGroups = new Set();
        const allGroups = [];
        tokens.forEach(function(token) {
            if (!token || typeof token !== 'object') {
                return;
            }
            const groupName = (token.group || defaultGroupName).trim() || defaultGroupName;
            if (!seenGroups.has(groupName)) {
                seenGroups.add(groupName);
                allGroups.push(groupName);
            }
        });
        if (!allGroups.length) {
            allGroups.push(defaultGroupName);
        }
        ensureGroupDatalist(allGroups);

        if (!totalTokens) {
            builder.append($('<p>', {
                class: 'ssc-token-empty',
                text: i18n.emptyState || 'Aucun token pour le moment. Utilisez le bouton ci-dessous pour commencer.',
            }));
            updateResultsCount(0, 0);
            updateDuplicateHighlights([]);
            commitContextOptions();
            return;
        }

        const filteredItems = [];
        tokens.forEach(function(token, index) {
            if (!token || typeof token !== 'object') {
                return;
            }
            const tokenType = typeof token.type === 'string' ? token.type : '';
            if (activeType && tokenType !== activeType) {
                return;
            }
            let highlightFragments = null;
            if (normalizedQuery) {
                const highlightData = computeTokenHighlights(token, normalizedQuery);
                if (!highlightData.hasMatch) {
                    return;
                }
                highlightFragments = highlightData.fragments;
            }
            filteredItems.push({
                token: token,
                index: index,
                highlights: highlightFragments,
            });
        });

        if (!filteredItems.length) {
            builder.append($('<p>', {
                class: 'ssc-token-empty',
                text: i18n.emptyFilteredState || 'Aucun token ne correspond à votre recherche ou filtre actuel.',
            }));
            updateResultsCount(0, totalTokens);
            updateDuplicateHighlights([]);
            commitContextOptions();
            return;
        }

        const groupedItems = {};
        const order = [];
        filteredItems.forEach(function(item) {
            const groupName = (item.token.group || defaultGroupName).trim() || defaultGroupName;
            if (!groupedItems[groupName]) {
                groupedItems[groupName] = [];
                order.push(groupName);
            }
            groupedItems[groupName].push(item);
        });

        order.forEach(function(groupName) {
            const section = $('<div>', { class: 'ssc-token-group' });
            const heading = $('<h4>');
            if (normalizedQuery) {
                heading.html(buildHighlightMarkup(groupName, normalizedQuery));
            } else {
                heading.text(groupName);
            }
            section.append(heading);
            groupedItems[groupName].forEach(function(item) {
                section.append(createTokenRow(item.token, item.index, normalizedQuery ? item.highlights : null));
            });
        builder.append(section);
        });

        updateResultsCount(filteredItems.length, totalTokens);
        refreshDuplicateState();
        commitContextOptions();
    }

    function addToken() {
        const defaultType = tokenTypes.color ? 'color' : getDefaultTypeKey();
        const defaultMeta = getTypeMeta(defaultType);
        let defaultValue = defaultType === 'color' ? '#ffffff' : '';
        if (defaultType !== 'color' && defaultMeta && typeof defaultMeta.placeholder === 'string') {
            defaultValue = defaultMeta.placeholder;
        }

        ensureContextOption(defaultContext);

        tokens.push({
            name: '--nouveau-token',
            value: defaultValue,
            type: defaultType,
            description: '',
            group: defaultGroupName,
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
            const normalizedContext = ensureContextOption(value);
            tokens[index][key] = normalizedContext;
            commitContextOptions();
        } else if (key === 'group') {
            const normalizedGroup = (typeof value === 'string' ? value : '').trim() || defaultGroupName;
            tokens[index][key] = normalizedGroup;
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
            if (!hasLocalChanges) {
                if (response && Array.isArray(response.tokens)) {
                    tokens = normalizeRegistryTokens(response.tokens);
                    renderTokens();
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
                tokens = normalizeRegistryTokens(response.tokens);
                renderTokens();
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
        const helpPaneElement = helpPane.get(0);
        const focusableSelector = 'a[href], area[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])';

        if (helpToggle.length && helpPane.length && tokenLayout.length) {
            const expandedLabel = helpToggle.data('expandedLabel');
            const collapsedLabel = helpToggle.data('collapsedLabel');

            const setHelpState = function(collapsed, persist) {
                if (collapsed && helpToggle.is(':focus')) {
                    const fallbackTarget = tokenLayout
                        .find(focusableSelector)
                        .filter(function() {
                            if (!$(this).is(':visible')) {
                                return false;
                            }

                            if (this === helpToggle[0]) {
                                return false;
                            }

                            if (helpPaneElement && helpPaneElement.contains(this)) {
                                return false;
                            }

                            return true;
                        })
                        .first();

                    if (fallbackTarget.length) {
                        fallbackTarget.trigger('focus');
                    } else {
                        helpToggle.trigger('blur');
                    }
                }

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

        if (previewContextSelect.length) {
            previewContextSelect.on('change', function() {
                applyPreviewContext($(this).val());
            });
        }

        commitContextOptions();
        applyPreviewContext(activePreviewContext);

        if (!builder.length || !cssTextarea.length) {
            return;
        }

        if (searchInput.length) {
            if (i18n.searchPlaceholder) {
                searchInput.attr('placeholder', i18n.searchPlaceholder);
            }
            if (i18n.searchLabel) {
                searchInput.attr('aria-label', i18n.searchLabel);
            }
            const initialSearch = searchInput.val();
            updateFilters({ query: typeof initialSearch === 'string' ? initialSearch : '' });
            searchInput.on('input', function() {
                const value = $(this).val();
                updateFilters({ query: typeof value === 'string' ? value : '' });
                renderTokens();
            });
        }

        if (typeFilterSelect.length) {
            if (i18n.typeFilterLabel) {
                typeFilterSelect.attr('aria-label', i18n.typeFilterLabel);
            }
            if (i18n.typeFilterAll) {
                const defaultOption = typeFilterSelect.find('option[value=""]');
                if (defaultOption.length) {
                    defaultOption.text(i18n.typeFilterAll);
                }
            }
            const initialType = typeFilterSelect.val();
            updateFilters({ type: typeof initialType === 'string' ? initialType : '' });
            typeFilterSelect.on('change', function() {
                const value = $(this).val();
                updateFilters({ type: typeof value === 'string' ? value : '' });
                renderTokens();
            });
        }

        syncFilterControls();

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
            const rawValue = $(this).val();
            const newGroup = (typeof rawValue === 'string' ? rawValue : '').trim() || defaultGroupName;
            setHasLocalChanges(true);
            updateToken(index, 'group', newGroup);
            renderTokens();
            refreshCssFromTokens();
        });

        builder.on('change', '.token-context', function() {
            const index = $(this).closest('.ssc-token-row').data('index');
            const value = $(this).val();
            updateToken(index, 'context', value);
            refreshCssFromTokens();
        });

        builder.on('click', '.token-delete', function(event) {
            event.preventDefault();
            const index = $(this).closest('.ssc-token-row').data('index');
            removeToken(index);
        });
    });
})(jQuery);
