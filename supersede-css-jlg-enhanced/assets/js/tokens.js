(function($) {
    const restRoot = window.SSC && window.SSC.rest && window.SSC.rest.root ? window.SSC.rest.root : '';
    const restNonce = window.SSC && window.SSC.rest && window.SSC.rest.nonce ? window.SSC.rest.nonce : '';
    const localized = window.SSC_TOKENS_DATA || {};
    const i18n = localized.i18n || {};
    const wpI18n = window.wp && window.wp.i18n ? window.wp.i18n : null;
    const hasI18n = !!(wpI18n && typeof wpI18n.__ === 'function');
    const sprintf = hasI18n && typeof wpI18n.sprintf === 'function'
        ? wpI18n.sprintf
        : function(message) {
            const args = Array.prototype.slice.call(arguments, 1);
            let index = 0;
            return String(message).replace(/%s/g, function() {
                const replacement = typeof args[index] === 'undefined' ? '' : args[index];
                index += 1;
                return String(replacement);
            });
        };
    const statusDefinitions = Array.isArray(localized.statuses) ? localized.statuses : [];
    const statusMetaMap = {};
    let defaultStatusValue = 'draft';

    function translate(key, fallback) {
        if (Object.prototype.hasOwnProperty.call(i18n, key) && typeof i18n[key] === 'string') {
            return i18n[key];
        }
        if (hasI18n && typeof fallback === 'string') {
            const translated = wpI18n.__(fallback, 'supersede-css-jlg');
            if (typeof translated === 'string' && translated.length) {
                return translated;
            }
        }
        if (typeof fallback === 'string') {
            return fallback;
        }
        return key;
    }

    statusDefinitions.forEach((definition) => {
        if (!definition || typeof definition !== 'object') {
            return;
        }
        const value = typeof definition.value === 'string' ? definition.value.trim().toLowerCase() : '';
        if (!value) {
            return;
        }
        statusMetaMap[value] = {
            label: typeof definition.label === 'string' && definition.label ? definition.label : value,
            description: typeof definition.description === 'string' ? definition.description : '',
        };
    });

    if (!statusMetaMap[defaultStatusValue] && statusDefinitions.length) {
        const first = statusDefinitions[0];
        if (first && typeof first.value === 'string' && first.value.trim()) {
            defaultStatusValue = first.value.trim().toLowerCase();
        }
    }

    function normalizeApprovalPriority(value) {
        const normalized = typeof value === 'string' ? value.trim().toLowerCase() : '';

        if (normalized && approvalPriorityMap[normalized]) {
            return normalized;
        }

        return defaultApprovalPriority;
    }

    function getApprovalPriorityLabel(value) {
        const normalized = normalizeApprovalPriority(value);
        const meta = approvalPriorityMap[normalized];

        if (meta && meta.label) {
            return meta.label;
        }

        if (normalized === 'low') {
            return translate('approvalPriorityLow', 'Faible');
        }
        if (normalized === 'normal') {
            return translate('approvalPriorityNormal', 'Normale');
        }
        if (normalized === 'high') {
            return translate('approvalPriorityHigh', 'Haute');
        }

        return translate('approvalPriorityUnknown', 'Priorité inconnue');
    }

    function getApprovalPriorityClass(value) {
        return `ssc-approval-priority--${normalizeApprovalPriority(value).replace(/[^a-z0-9_-]/g, '')}`;
    }

    const browserLocale = (document.documentElement && document.documentElement.lang)
        ? document.documentElement.lang
        : ((navigator.language || navigator.userLanguage || 'en-US'));
    const dateTimeFormatter = (typeof Intl !== 'undefined' && typeof Intl.DateTimeFormat === 'function')
        ? new Intl.DateTimeFormat(browserLocale, { dateStyle: 'medium', timeStyle: 'short' })
        : null;
    const relativeTimeFormatter = (typeof Intl !== 'undefined' && typeof Intl.RelativeTimeFormat === 'function')
        ? new Intl.RelativeTimeFormat(browserLocale, { numeric: 'auto' })
        : null;
    const initialApprovals = Array.isArray(localized.approvals) ? localized.approvals : [];
    const approvalPriorityDefinitions = Array.isArray(localized.approvalPriorities) ? localized.approvalPriorities : [];
    const approvalSlaRules = localized.approvalSlaRules && typeof localized.approvalSlaRules === 'object'
        ? localized.approvalSlaRules
        : {};
    const approvalPriorityMap = Object.create(null);
    const approvalPriorityOptions = [];
    let defaultApprovalPriority = 'normal';

    approvalPriorityDefinitions.forEach((definition) => {
        if (!definition || typeof definition !== 'object') {
            return;
        }

        const value = typeof definition.value === 'string' ? definition.value.trim().toLowerCase() : '';
        if (!value) {
            return;
        }

        const label = typeof definition.label === 'string' && definition.label.length
            ? definition.label
            : value;

        approvalPriorityMap[value] = {
            value,
            label,
            description: typeof definition.description === 'string' ? definition.description : '',
        };

        approvalPriorityOptions.push({ value, label });

        if (definition.default === true) {
            defaultApprovalPriority = value;
        }
    });

    if (!approvalPriorityMap[defaultApprovalPriority]) {
        if (approvalPriorityMap.normal) {
            defaultApprovalPriority = 'normal';
        } else if (approvalPriorityOptions.length) {
            defaultApprovalPriority = approvalPriorityOptions[0].value;
        } else {
            defaultApprovalPriority = 'normal';
        }
    }
    let approvalsIndex = Object.create(null);
    let approvalsAvailable = true;
    const defaultTokenTypes = {
        color: {
            label: translate('tokenTypeColorLabel', 'Couleur'),
            input: 'color',
            help: translate('tokenTypeColorHelp', 'Utilisez un code hexadécimal (ex. #4f46e5) ou une variable existante.'),
        },
        text: {
            label: translate('tokenTypeTextLabel', 'Texte'),
            input: 'text',
            placeholder: translate('tokenTypeTextPlaceholder', 'Ex. 16px ou clamp(1rem, 2vw, 2rem)'),
            help: translate('tokenTypeTextHelp', 'Idéal pour les valeurs libres (unités CSS, fonctions, etc.).'),
        },
        number: {
            label: translate('tokenTypeNumberLabel', 'Nombre'),
            input: 'number',
            help: translate('tokenTypeNumberHelp', 'Pour les valeurs strictement numériques (ex. 1.25).'),
        },
        spacing: {
            label: translate('tokenTypeSpacingLabel', 'Espacement'),
            input: 'text',
            placeholder: translate('tokenTypeSpacingPlaceholder', 'Ex. 16px 24px'),
            help: translate('tokenTypeSpacingHelp', 'Convient aux marges/paddings ou aux espacements multiples.'),
        },
        font: {
            label: translate('tokenTypeFontLabel', 'Typographie'),
            input: 'text',
            placeholder: translate('tokenTypeFontPlaceholder', 'Ex. "Inter", sans-serif'),
            help: translate('tokenTypeFontHelp', 'Définissez la pile de polices complète avec les guillemets requis.'),
        },
        shadow: {
            label: translate('tokenTypeShadowLabel', 'Ombre'),
            input: 'textarea',
            placeholder: translate('tokenTypeShadowPlaceholder', '0 2px 4px rgba(15, 23, 42, 0.25)'),
            rows: 3,
            help: translate('tokenTypeShadowHelp', 'Accepte plusieurs valeurs box-shadow, une par ligne si nécessaire.'),
        },
        gradient: {
            label: translate('tokenTypeGradientLabel', 'Dégradé'),
            input: 'textarea',
            placeholder: translate('tokenTypeGradientPlaceholder', 'linear-gradient(135deg, #4f46e5, #7c3aed)'),
            rows: 3,
            help: translate('tokenTypeGradientHelp', 'Pour les dégradés CSS complexes (linear-, radial-…).'),
        },
        border: {
            label: translate('tokenTypeBorderLabel', 'Bordure'),
            input: 'text',
            placeholder: translate('tokenTypeBorderPlaceholder', 'Ex. 1px solid currentColor'),
            help: translate('tokenTypeBorderHelp', 'Combinez largeur, style et couleur de bordure.'),
        },
        dimension: {
            label: translate('tokenTypeDimensionLabel', 'Dimensions'),
            input: 'text',
            placeholder: translate('tokenTypeDimensionPlaceholder', 'Ex. 320px ou clamp(280px, 50vw, 480px)'),
            help: translate('tokenTypeDimensionHelp', 'Largeurs/hauteurs ou tailles maximales avec clamp/min/max.'),
        },
        transition: {
            label: translate('tokenTypeTransitionLabel', 'Transition'),
            input: 'textarea',
            placeholder: translate('tokenTypeTransitionPlaceholder', 'all 0.3s ease-in-out\ncolor 150ms ease'),
            rows: 2,
            help: translate('tokenTypeTransitionHelp', 'Définissez des transitions multi-propriétés, une par ligne.'),
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
    const collaborators = Array.isArray(localized.collaborators) ? localized.collaborators : [];
    const permissions = (localized.permissions && typeof localized.permissions === 'object') ? localized.permissions : {};
    const features = (localized.features && typeof localized.features === 'object') ? localized.features : {};
    const commentsEnabled = !!features.comments;
    const readModeAvailable = !!features.readMode;
    const canComment = !!permissions.canComment;
    const canEdit = !!permissions.canEdit;
    let readMode = false;
    const tokenCommentMap = new Map();
    let commentsPrimed = false;

    function speak(message, politeness) {
        if (!message) {
            return;
        }
        if (window.wp && window.wp.a11y && typeof window.wp.a11y.speak === 'function') {
            window.wp.a11y.speak(message, politeness || 'polite');
        }
    }
    const defaultGroupName = translate('defaultGroupName', 'Général');
    const defaultNewTokenName = (function() {
        const fallback = '--nouveau-token';
        const translated = translate('newTokenDefaultName', fallback);
        if (typeof translated === 'string' && translated.trim().length) {
            return translated;
        }
        return fallback;
    })();
    const diacriticRegex = /[\u0300-\u036f]/g;
    const hasStringNormalize = typeof ''.normalize === 'function';
    const localUiState = {
        filters: {
            query: '',
            type: '',
        },
    };

    tokens = normalizeRegistryTokens(tokens);

    function computeTokenKey(token) {
        if (!token || typeof token !== 'object') {
            return '';
        }
        const name = (token.name || '').toString().trim();
        const context = (token.context || defaultContext).toString().trim() || defaultContext;
        return `${name}@@${context}`;
    }

    function escapeCssSelector(value) {
        if (window.CSS && typeof window.CSS.escape === 'function') {
            return window.CSS.escape(value);
        }
        return String(value).replace(/([!"#$%&'()*+,./:;<=>?@[\]^`{|}~])/g, '\\$1');
    }

    function getCommentsForKey(key) {
        if (!key || !tokenCommentMap.has(key)) {
            return [];
        }
        return tokenCommentMap.get(key) || [];
    }

    function setCommentsForKey(key, comments) {
        if (!key) {
            return;
        }
        const normalized = Array.isArray(comments) ? comments : [];
        tokenCommentMap.set(key, normalized);
    }

    function appendCommentToKey(key, comment) {
        if (!key) {
            return;
        }
        const existing = getCommentsForKey(key).slice();
        existing.push(comment);
        setCommentsForKey(key, existing);
    }

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
    const addButton = $('#ssc-token-add');
    const readModeToggle = $('#ssc-token-readmode');
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
    const devicePanel = $('.ssc-device-lab-panel');
    const deviceStage = $('#ssc-device-stage');
    const deviceViewport = $('#ssc-device-viewport');
    const devicePresetButtons = $('#ssc-device-presets button[data-device]');
    const deviceDimensionsLabel = $('#ssc-device-dimensions');
    const deviceZoomSlider = $('#ssc-device-zoom');
    const deviceZoomValue = $('#ssc-device-zoom-value');
    const deviceOrientationToggle = $('#ssc-device-orientation');
    const deviceStateButtons = $('#ssc-device-states button[data-state]');
    const reducedMotionToggle = $('#ssc-device-motion');
    const deviceOrientationLabels = {
        landscape: deviceOrientationToggle.data('label-landscape') || translate('deviceOrientationLandscape', 'Orientation paysage'),
        portrait: deviceOrientationToggle.data('label-portrait') || translate('deviceOrientationPortrait', 'Orientation portrait'),
        disabled: deviceOrientationToggle.data('label-disabled') || translate('deviceOrientationLocked', 'Rotation non disponible pour cet appareil.'),
    };
    const devicePresets = {
        mobile: {
            label: $.trim(devicePresetButtons.filter('[data-device="mobile"]').text()) || 'Mobile',
            allowRotate: true,
            defaultOrientation: 'portrait',
            viewport: {
                portrait: { width: 375, height: 812 },
                landscape: { width: 812, height: 375 },
            },
        },
        tablet: {
            label: $.trim(devicePresetButtons.filter('[data-device="tablet"]').text()) || 'Tablet',
            allowRotate: true,
            defaultOrientation: 'portrait',
            viewport: {
                portrait: { width: 834, height: 1112 },
                landscape: { width: 1112, height: 834 },
            },
        },
        laptop: {
            label: $.trim(devicePresetButtons.filter('[data-device="laptop"]').text()) || 'Laptop',
            allowRotate: false,
            defaultOrientation: 'landscape',
            viewport: {
                landscape: { width: 1280, height: 800 },
            },
        },
        desktop: {
            label: $.trim(devicePresetButtons.filter('[data-device="desktop"]').text()) || 'Desktop',
            allowRotate: false,
            defaultOrientation: 'landscape',
            viewport: {
                landscape: { width: 1440, height: 900 },
            },
        },
        ultrawide: {
            label: $.trim(devicePresetButtons.filter('[data-device="ultrawide"]').text()) || 'Ultra-wide',
            allowRotate: false,
            defaultOrientation: 'landscape',
            viewport: {
                landscape: { width: 1920, height: 1080 },
            },
        },
    };
    let activeDeviceKey = 'mobile';
    let deviceOrientation = 'portrait';
    let deviceZoomLevel = 85;

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

    function getDevicePreset(key) {
        if (key && Object.prototype.hasOwnProperty.call(devicePresets, key)) {
            return devicePresets[key];
        }
        return devicePresets.desktop;
    }

    function getDeviceViewport(preset, orientation) {
        const safePreset = preset || getDevicePreset(activeDeviceKey);
        const safeOrientation = orientation === 'portrait' || orientation === 'landscape'
            ? orientation
            : 'landscape';
        if (safeOrientation === 'portrait' && safePreset.viewport && safePreset.viewport.portrait) {
            return safePreset.viewport.portrait;
        }
        if (safePreset.viewport && safePreset.viewport.landscape) {
            return safePreset.viewport.landscape;
        }
        if (safePreset.viewport && safePreset.viewport.portrait) {
            return safePreset.viewport.portrait;
        }
        return { width: 1280, height: 800 };
    }

    function updateOrientationButtonState(allowRotate) {
        if (!deviceOrientationToggle.length) {
            return;
        }
        const labelNode = deviceOrientationToggle.find('.ssc-device-orientation__text');
        if (!allowRotate) {
            deviceOrientationToggle.prop('disabled', true).attr('aria-disabled', 'true');
            deviceOrientationToggle.attr('aria-pressed', 'false');
            if (labelNode.length) {
                labelNode.text(deviceOrientationLabels.disabled);
            } else {
                deviceOrientationToggle.text(deviceOrientationLabels.disabled);
            }
            return;
        }

        deviceOrientationToggle.prop('disabled', false).attr('aria-disabled', 'false');
        const isPortrait = deviceOrientation === 'portrait';
        deviceOrientationToggle.attr('aria-pressed', isPortrait ? 'true' : 'false');
        const labelText = isPortrait ? deviceOrientationLabels.portrait : deviceOrientationLabels.landscape;
        if (labelNode.length) {
            labelNode.text(labelText);
        } else {
            deviceOrientationToggle.text(labelText);
        }
    }

    function applyDevicePreset(key, options) {
        const preset = getDevicePreset(key);
        const previousDevice = activeDeviceKey;
        const previousOrientation = deviceOrientation;
        activeDeviceKey = key && Object.prototype.hasOwnProperty.call(devicePresets, key)
            ? key
            : activeDeviceKey;

        let nextOrientation = deviceOrientation;
        if (options && options.resetOrientation) {
            nextOrientation = preset.defaultOrientation || 'landscape';
        }
        if (options && typeof options.orientation === 'string') {
            nextOrientation = options.orientation;
        }

        if (!preset.allowRotate) {
            nextOrientation = 'landscape';
        }

        if (nextOrientation !== 'portrait' && nextOrientation !== 'landscape') {
            nextOrientation = preset.defaultOrientation || 'landscape';
        }

        if (nextOrientation === 'portrait' && !(preset.viewport && preset.viewport.portrait)) {
            nextOrientation = 'landscape';
        }

        if (nextOrientation === 'landscape' && !(preset.viewport && preset.viewport.landscape) && preset.viewport && preset.viewport.portrait) {
            nextOrientation = 'portrait';
        }

        deviceOrientation = nextOrientation;
        const viewport = getDeviceViewport(preset, deviceOrientation);

        if (deviceStage.length) {
            deviceStage.attr('data-device', activeDeviceKey);
            deviceStage.attr('data-orientation', deviceOrientation);
            deviceStage.css('--ssc-device-width', viewport.width + 'px');
            deviceStage.css('--ssc-device-height', viewport.height + 'px');
        }

        if (deviceViewport.length) {
            deviceViewport.attr('data-orientation', deviceOrientation);
        }

        if (deviceDimensionsLabel.length) {
            deviceDimensionsLabel.text(viewport.width + ' × ' + viewport.height + ' px');
        }

        if (devicePresetButtons.length) {
            devicePresetButtons.removeClass('is-active').attr('aria-pressed', 'false');
            const activeButton = devicePresetButtons.filter('[data-device="' + activeDeviceKey + '"]').first();
            if (activeButton.length) {
                activeButton.addClass('is-active').attr('aria-pressed', 'true');
            }
        }

        updateOrientationButtonState(preset.allowRotate);

        if (!options || options.announce !== false) {
            speak(sprintf(translate('devicePresetAnnouncement', 'Appareil sélectionné : %s'), preset.label || activeDeviceKey));
        }

        const shouldAnnounceOrientation = !options || options.announceOrientation !== false;
        if (shouldAnnounceOrientation) {
            if (!preset.allowRotate && previousDevice !== activeDeviceKey) {
                speak(translate('deviceOrientationLocked', 'Rotation non disponible pour cet appareil.'), 'polite');
            } else if (deviceOrientation !== previousOrientation || previousDevice !== activeDeviceKey) {
                const orientationKey = deviceOrientation === 'portrait'
                    ? 'deviceOrientationPortrait'
                    : 'deviceOrientationLandscape';
                const fallback = deviceOrientation === 'portrait'
                    ? 'Orientation portrait'
                    : 'Orientation paysage';
                speak(translate(orientationKey, fallback));
            }
        }
    }

    function updateZoomControl(value, announce) {
        const rawValue = parseInt(value, 10);
        const normalized = Number.isNaN(rawValue) ? deviceZoomLevel : rawValue;
        const clamped = Math.min(140, Math.max(60, normalized));
        deviceZoomLevel = clamped;
        const scaleValue = Math.max(0.3, clamped / 100);

        if (deviceZoomSlider.length && deviceZoomSlider.val() !== String(clamped)) {
            deviceZoomSlider.val(String(clamped));
        }
        if (deviceZoomSlider.length) {
            deviceZoomSlider.attr('aria-valuenow', String(clamped));
        }
        if (deviceZoomValue.length) {
            deviceZoomValue.text(clamped + '%');
        }
        if (deviceStage.length) {
            deviceStage.css('--ssc-device-scale', scaleValue.toFixed(2));
            deviceStage.attr('data-scale', String(clamped));
        }

        if (announce) {
            speak(sprintf(translate('deviceZoomAnnouncement', 'Zoom défini sur %s %%'), clamped));
        }
    }

    function applyInteractionState(stateKey, announce) {
        const validStates = ['default', 'hover', 'focus', 'active'];
        const normalized = validStates.indexOf(stateKey) === -1 ? 'default' : stateKey;

        if (previewContainer.length) {
            previewContainer.attr('data-simulated-state', normalized);
        }

        if (deviceStateButtons.length) {
            deviceStateButtons.removeClass('is-active').attr('aria-pressed', 'false');
            const activeButton = deviceStateButtons.filter('[data-state="' + normalized + '"]').first();
            if (activeButton.length) {
                activeButton.addClass('is-active').attr('aria-pressed', 'true');
            }
        }

        if (announce) {
            const activeButton = deviceStateButtons.filter('[data-state="' + normalized + '"]').first();
            const label = activeButton.length ? $.trim(activeButton.text()) : normalized;
            speak(sprintf(translate('deviceStateAnnouncement', 'Simulation de l’état : %s'), label));
        }
    }

    function applyReducedMotion(enabled, announce) {
        const isEnabled = Boolean(enabled);
        if (deviceStage.length) {
            deviceStage.toggleClass('is-reduced-motion', isEnabled);
            deviceStage.attr('data-motion', isEnabled ? 'reduced' : 'default');
        }
        if (announce) {
            speak(isEnabled ? translate('deviceReducedMotionOn', 'Préférence « réduction des animations » activée') : translate('deviceReducedMotionOff', 'Préférence « réduction des animations » désactivée'));
        }
    }

    function initializeDeviceLab() {
        if (!devicePanel.length || !deviceStage.length) {
            return;
        }

        const initialDevice = deviceStage.data('device');
        if (typeof initialDevice === 'string' && Object.prototype.hasOwnProperty.call(devicePresets, initialDevice)) {
            activeDeviceKey = initialDevice;
        }

        const initialOrientation = deviceStage.data('orientation');
        if (initialOrientation === 'portrait' || initialOrientation === 'landscape') {
            deviceOrientation = initialOrientation;
        } else {
            const preset = getDevicePreset(activeDeviceKey);
            deviceOrientation = preset.defaultOrientation || 'landscape';
        }

        if (deviceZoomSlider.length) {
            const sliderValue = parseInt(deviceZoomSlider.val(), 10);
            if (!Number.isNaN(sliderValue)) {
                deviceZoomLevel = sliderValue;
            }
        }

        applyDevicePreset(activeDeviceKey, { orientation: deviceOrientation, announce: false, announceOrientation: false });
        updateZoomControl(deviceZoomLevel, false);

        const initialState = previewContainer.attr('data-simulated-state') || 'default';
        applyInteractionState(initialState, false);
        applyReducedMotion(reducedMotionToggle.length && reducedMotionToggle.is(':checked'), false);

        devicePresetButtons.on('click', function(event) {
            event.preventDefault();
            const deviceKey = $(this).data('device');
            if (typeof deviceKey !== 'string') {
                return;
            }
            const shouldReset = deviceKey !== activeDeviceKey;
            applyDevicePreset(deviceKey, {
                resetOrientation: shouldReset,
                announce: true,
                announceOrientation: true,
            });
        });

        if (deviceOrientationToggle.length) {
            deviceOrientationToggle.on('click', function(event) {
                event.preventDefault();
                const preset = getDevicePreset(activeDeviceKey);
                if (!preset.allowRotate) {
                    speak(deviceOrientationLabels.disabled, 'assertive');
                    return;
                }
                const nextOrientation = deviceOrientation === 'portrait' ? 'landscape' : 'portrait';
                applyDevicePreset(activeDeviceKey, { orientation: nextOrientation, announce: false, announceOrientation: true });
            });
        }

        if (deviceZoomSlider.length) {
            deviceZoomSlider.on('input', function() {
                updateZoomControl($(this).val(), false);
            });
            deviceZoomSlider.on('change', function() {
                updateZoomControl($(this).val(), true);
            });
        }

        if (deviceStateButtons.length) {
            deviceStateButtons.on('click', function(event) {
                event.preventDefault();
                const state = $(this).data('state');
                applyInteractionState(state, true);
            });
        }

        if (reducedMotionToggle.length) {
            reducedMotionToggle.on('change', function() {
                applyReducedMotion($(this).is(':checked'), true);
            });
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

    function getDefaultStatusValue() {
        return statusMetaMap[defaultStatusValue] ? defaultStatusValue : 'draft';
    }

    function normalizeStatusValue(value) {
        if (typeof value !== 'string') {
            return getDefaultStatusValue();
        }
        const normalized = value.trim().toLowerCase();
        if (!normalized) {
            return getDefaultStatusValue();
        }
        return statusMetaMap[normalized] ? normalized : getDefaultStatusValue();
    }

    function getStatusInfo(value) {
        const normalized = normalizeStatusValue(value);
        return statusMetaMap[normalized] || null;
    }

    function buildTokenKey(name, context) {
        const normalizedName = typeof name === 'string' ? name.trim().toLowerCase() : '';
        const normalizedContext = normalizeContextValue(context || defaultContext).toLowerCase();
        if (!normalizedName) {
            return '';
        }
        return normalizedContext + '|' + normalizedName;
    }

    function primeApprovals(entries) {
        approvalsIndex = Object.create(null);
        if (!Array.isArray(entries)) {
            return;
        }
        entries.forEach((entry) => {
            if (!entry || typeof entry !== 'object') {
                return;
            }
            const token = entry.token && typeof entry.token === 'object' ? entry.token : null;
            if (!token) {
                return;
            }
            const key = buildTokenKey(token.name, token.context);
            if (!key) {
                return;
            }
            const normalizedEntry = { ...entry };
            normalizedEntry.priority = normalizeApprovalPriority(entry.priority);
            approvalsIndex[key] = normalizedEntry;
        });
    }

    function getApprovalForToken(token) {
        if (!token || typeof token !== 'object') {
            return null;
        }
        const key = buildTokenKey(token.name, token.context);
        if (!key) {
            return null;
        }
        return approvalsIndex[key] || null;
    }

    function formatDateTimeValue(isoString) {
        if (typeof isoString !== 'string' || !isoString.length) {
            return '';
        }
        const parsed = new Date(isoString);
        if (Number.isNaN(parsed.getTime())) {
            return '';
        }
        if (dateTimeFormatter) {
            try {
                return dateTimeFormatter.format(parsed);
            } catch (err) {
                // Ignore formatting errors and fall back to native formatting.
            }
        }
        try {
            return parsed.toLocaleString(browserLocale);
        } catch (err) {
            return parsed.toISOString();
        }
    }

    function formatDuration(seconds) {
        if (typeof seconds !== 'number' || Number.isNaN(seconds) || seconds <= 0) {
            return translate('durationLessThanSecond', 'moins d’une seconde');
        }

        const units = [
            { limit: 60, divisor: 1, unit: 'second', fallback: 'durationSeconds' },
            { limit: 3600, divisor: 60, unit: 'minute', fallback: 'durationMinutes' },
            { limit: 86400, divisor: 3600, unit: 'hour', fallback: 'durationHours' },
            { limit: Infinity, divisor: 86400, unit: 'day', fallback: 'durationDays' },
        ];

        for (let i = 0; i < units.length; i += 1) {
            const { limit, divisor, unit, fallback } = units[i];

            if (seconds < limit) {
                const value = Math.max(1, Math.round(seconds / divisor));

                if (relativeTimeFormatter) {
                    try {
                        return relativeTimeFormatter.format(value, unit);
                    } catch (err) {
                        // Continue with fallback below.
                    }
                }

                return sprintf(translate(fallback, '%d'), value);
            }
        }

        return sprintf(translate('durationDays', '%d'), Math.round(seconds / 86400));
    }

    function computeApprovalSlaMeta(approval) {
        if (!approval) {
            return null;
        }

        const requestedAtIso = approval.requested_at || '';
        if (!requestedAtIso) {
            return null;
        }

        const requestedAt = new Date(requestedAtIso);
        if (Number.isNaN(requestedAt.getTime())) {
            return null;
        }

        const priority = normalizeApprovalPriority(approval.priority);
        const sla = approval.sla && typeof approval.sla === 'object' ? approval.sla : null;

        let targetTime = null;
        let deadlineIso = '';

        if (sla && sla.deadline_at) {
            const deadline = new Date(sla.deadline_at);
            if (!Number.isNaN(deadline.getTime())) {
                targetTime = deadline.getTime();
                deadlineIso = deadline.toISOString();
            }
        }

        if (!targetTime) {
            const rule = approvalSlaRules[priority];
            if (!rule || typeof rule.hours !== 'number' || Number.isNaN(rule.hours) || rule.hours <= 0) {
                return null;
            }

            targetTime = requestedAt.getTime() + (rule.hours * 60 * 60 * 1000);
            deadlineIso = new Date(targetTime).toISOString();
        }

        const status = (approval.status || 'pending').toLowerCase();
        const now = Date.now();

        let state = 'pending';
        let diffSeconds = Math.max(1, Math.round(Math.abs(targetTime - now) / 1000));
        let escalationLevel = 0;

        if (sla && typeof sla.current_level === 'number') {
            escalationLevel = Math.max(0, Math.floor(sla.current_level));
        } else if (sla && Array.isArray(sla.escalations)) {
            sla.escalations.forEach((escalation) => {
                if (escalation && escalation.notified_at) {
                    const level = parseInt(escalation.level, 10);
                    if (!Number.isNaN(level)) {
                        escalationLevel = Math.max(escalationLevel, level);
                    }
                }
            });
        }

        if (status === 'pending') {
            if (sla && sla.breached_at) {
                const breached = new Date(sla.breached_at);
                if (!Number.isNaN(breached.getTime())) {
                    state = 'overdue';
                    diffSeconds = Math.max(1, Math.round((now - breached.getTime()) / 1000));
                }
            }

            if (state !== 'overdue') {
                diffSeconds = Math.round((targetTime - now) / 1000);
                if (diffSeconds < 0) {
                    state = 'overdue';
                    diffSeconds = Math.abs(diffSeconds);
                }
            }
        } else {
            const completionIso = (sla && sla.completed_at)
                || (approval.decision && approval.decision.decided_at)
                || '';
            const completion = completionIso ? new Date(completionIso) : null;

            if (completion && !Number.isNaN(completion.getTime())) {
                const delta = completion.getTime() - targetTime;
                diffSeconds = Math.max(1, Math.round(Math.abs(delta) / 1000));
                state = delta <= 0 ? 'fulfilled' : 'fulfilled_late';
            } else {
                diffSeconds = Math.max(1, Math.round(Math.abs(targetTime - now) / 1000));
                state = targetTime < now ? 'overdue' : 'pending';
            }
        }

        return {
            state,
            diffSeconds,
            deadlineIso,
            escalationLevel,
        };
    }

    function buildApprovalSlaDisplay(approval) {
        const meta = computeApprovalSlaMeta(approval);
        if (!meta) {
            return null;
        }

        const targetLabel = formatDateTimeValue(meta.deadlineIso) || meta.deadlineIso;
        let text = '';
        let cssClass = '';

        if (meta.state === 'pending') {
            text = translate('approvalsReviewSlaRemaining', 'Temps restant : %s')
                .replace('%s', formatDuration(Math.max(1, meta.diffSeconds)));
        } else if (meta.state === 'overdue') {
            text = translate('approvalsReviewSlaOverdue', 'Retard de %s')
                .replace('%s', formatDuration(Math.max(1, meta.diffSeconds)));
            cssClass = 'is-overdue';
        } else if (meta.state === 'fulfilled') {
            text = translate('approvalsReviewSlaMet', 'Revue clôturée dans les temps.');
            cssClass = 'is-success';
        } else if (meta.state === 'fulfilled_late') {
            text = translate('approvalsReviewSlaLate', 'Clôturée avec %s de retard.')
                .replace('%s', formatDuration(Math.max(1, meta.diffSeconds)));
            cssClass = 'is-overdue';
        }

        if (meta.escalationLevel > 0) {
            const escalationText = translate('approvalsReviewSlaEscalated', 'Escalade niveau %s')
                .replace('%s', meta.escalationLevel);
            text = `${escalationText} — ${text}`;
        }

        return {
            text,
            cssClass,
            meta,
            title: translate('approvalsReviewSlaTarget', 'Délai cible : %s').replace('%s', targetLabel),
        };
    }

    function formatApprovalTooltip(approval) {
        if (!approval || typeof approval !== 'object') {
            return '';
        }
        const pieces = [];
        if (approval.comment) {
            pieces.push(`${translate('approvalTooltipComment', 'Commentaire')}: ${approval.comment}`);
        }
        if (approval.requested_at) {
            const formatted = formatDateTimeValue(approval.requested_at);
            if (formatted) {
                pieces.push(sprintf(translate('approvalTooltipRequestedAt', 'Envoyée le %s'), formatted));
            }
        }
        const priorityLabel = getApprovalPriorityLabel(approval.priority);
        if (priorityLabel) {
            pieces.push(`${translate('approvalTooltipPriority', 'Priorité')}: ${priorityLabel}`);
        }
        const slaDisplay = buildApprovalSlaDisplay(approval);
        if (slaDisplay && slaDisplay.text) {
            pieces.push(`${translate('approvalsReviewSlaLabel', 'SLA')}: ${slaDisplay.text}`);
        }
        return pieces.join('\n');
    }

    function getApprovalStatusLabel(status) {
        if (typeof status !== 'string') {
            return translate('approvalPendingLabel', 'Revue en attente');
        }
        const normalized = status.trim().toLowerCase();
        if (normalized === 'approved') {
            return translate('approvalApprovedLabel', 'Revue approuvée');
        }
        if (normalized === 'changes_requested') {
            return translate('approvalChangesRequestedLabel', 'Modifications demandées');
        }
        return translate('approvalPendingLabel', 'Revue en attente');
    }

    primeApprovals(initialApprovals);

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
                    status: getDefaultStatusValue(),
                    owner: 0,
                    version: '',
                    changelog: '',
                    linked_components: [],
                };
            }

            const normalized = Object.assign({}, token);
            normalized.name = typeof normalized.name === 'string' ? normalized.name : '';
            normalized.value = normalized.value == null ? '' : String(normalized.value);
            normalized.type = typeof normalized.type === 'string' ? normalized.type : getDefaultTypeKey();
            normalized.description = typeof normalized.description === 'string' ? normalized.description : '';
            normalized.group = typeof normalized.group === 'string' && normalized.group.trim() !== '' ? normalized.group : defaultGroupName;
            normalized.context = ensureContextOption(normalized.context);
            normalized.status = normalizeStatusValue(normalized.status);

            if (typeof normalized.owner === 'number' && Number.isFinite(normalized.owner)) {
                normalized.owner = Math.max(0, Math.floor(normalized.owner));
            } else if (typeof normalized.owner === 'string' && normalized.owner.trim()) {
                const parsedOwner = parseInt(normalized.owner, 10);
                normalized.owner = Number.isNaN(parsedOwner) ? 0 : Math.max(0, parsedOwner);
            } else {
                normalized.owner = 0;
            }

            normalized.version = typeof normalized.version === 'string' ? normalized.version : '';
            normalized.changelog = typeof normalized.changelog === 'string' ? normalized.changelog : '';

            if (Array.isArray(normalized.linked_components)) {
                normalized.linked_components = normalized.linked_components
                    .map(function(component) {
                        return typeof component === 'string' ? component.trim() : '';
                    })
                    .filter(function(component) {
                        return component !== '';
                    });
            } else {
                normalized.linked_components = [];
            }

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
        const tokenKey = computeTokenKey(token);
        const row = $('<div>', { class: 'ssc-token-row', 'data-index': index });
        if (tokenKey) {
            row.attr('data-token-key', tokenKey);
            row.data('tokenKey', tokenKey);
            if (!tokenCommentMap.has(tokenKey)) {
                tokenCommentMap.set(tokenKey, []);
            }
        }
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

        

        const isReadMode = readModeAvailable && readMode;

        token.status = normalizeStatusValue(token.status);
        const statusInfo = getStatusInfo(token.status);
        const statusLabelText = (statusInfo && statusInfo.label) ? statusInfo.label : translate('statusUnknown', 'Statut inconnu');
        const statusChip = $('<span>', {
            class: 'ssc-token-status ssc-token-status--' + token.status,
            role: 'status',
            'aria-label': `${translate('statusLabel', 'Statut')}: ${statusLabelText}`,
        });
        statusChip.append($('<span>', { class: 'ssc-token-status__dot', 'aria-hidden': 'true' }));
        statusChip.append($('<span>', { class: 'ssc-token-status__text', text: statusLabelText }));
        if (statusInfo && statusInfo.description) {
            statusChip.attr('title', statusInfo.description);
        }

        const metaBar = $('<div>', { class: 'ssc-token-row__meta' });
        const metaPrimary = $('<div>', { class: 'ssc-token-row__meta-primary' });
        const metaActions = $('<div>', { class: 'ssc-token-row__meta-actions' });
        metaPrimary.append(statusChip);

        const approval = getApprovalForToken(token);
        let hasPendingApproval = false;
        if (approval) {
            const approvalStatus = typeof approval.status === 'string' ? approval.status.trim().toLowerCase() : 'pending';
            const approvalBadge = $('<span>', {
                class: 'ssc-token-approval ssc-token-approval--' + approvalStatus,
                text: getApprovalStatusLabel(approvalStatus),
            });
            const tooltip = formatApprovalTooltip(approval);
            if (tooltip) {
                approvalBadge.attr('title', tooltip);
            }
            metaPrimary.append(approvalBadge);
            const approvalPriorityLabel = getApprovalPriorityLabel(approval.priority);
            if (approvalPriorityLabel) {
                const approvalPriorityChip = $('<span>', {
                    class: `ssc-approval-priority ${getApprovalPriorityClass(approval.priority)}`,
                    text: approvalPriorityLabel,
                });
                metaPrimary.append(approvalPriorityChip);
            }
            const slaDisplay = buildApprovalSlaDisplay(approval);
            if (slaDisplay && slaDisplay.text) {
                const slaChip = $('<span>', {
                    class: 'ssc-token-approval-sla',
                    text: slaDisplay.text,
                    title: slaDisplay.title,
                });

                if (slaDisplay.cssClass) {
                    slaChip.addClass(slaDisplay.cssClass);
                }

                if (slaDisplay.meta && slaDisplay.meta.escalationLevel > 0) {
                    slaChip.addClass('is-escalated');
                    slaChip.attr('data-escalation-level', slaDisplay.meta.escalationLevel);
                }

                metaPrimary.append(slaChip);
            }
            hasPendingApproval = approvalStatus === 'pending';
        }

        if (metaPrimary.children().length) {
            metaBar.append(metaPrimary);
        }

        if (approvalsAvailable) {
            const approvalButton = $('<button>', {
                type: 'button',
                class: 'button button-secondary token-request-approval',
                text: i18n.approvalRequestLabel || 'Demander une revue',
            });
            let disableApproval = false;
            let disableReason = '';
            const trimmedName = typeof token.name === 'string' ? token.name.trim() : '';

            if (!restRoot) {
                disableApproval = true;
            }
            if (!trimmedName) {
                disableApproval = true;
            }
            if (token.status === 'ready') {
                disableApproval = true;
            }
            if (hasPendingApproval) {
                disableApproval = true;
                if (!disableReason) {
                    disableReason = getApprovalStatusLabel('pending');
                }
            }
            if (hasLocalChanges) {
                disableApproval = true;
                disableReason = translate('approvalRequestDisabledUnsaved', 'Enregistrez vos tokens avant de demander une revue.');
            }

            if (disableApproval) {
                approvalButton.prop('disabled', true);
                if (disableReason) {
                    approvalButton.attr('title', disableReason);
                }
            }

            metaActions.append(approvalButton);
        } else {
            const unavailableMessage = i18n.approvalUnavailableLabel
                || 'Les demandes d’approbation nécessitent un accès supplémentaire.';
            metaActions.append($('<span>', {
                class: 'ssc-token-approval-unavailable',
                text: unavailableMessage,
            }));
        }

        metaActions.append(deleteButton);
        if (isReadMode) {
            deleteButton.hide();
            metaActions.find('button').prop('disabled', true);
            metaActions.append($('<span>', {
                class: 'ssc-token-readmode',
                text: translate('readModeBadge', 'Lecture seule'),
            }));
        }
        metaBar.append(metaActions);
        row.append(metaBar);

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

        if (isReadMode) {
            row.addClass('ssc-token-row--readonly');
            nameInput.prop('readonly', true);
            valueInput.prop('disabled', true);
            typeSelect.prop('disabled', true);
            groupInput.prop('readonly', true);
            contextSelect.prop('disabled', true);
            descriptionInput.prop('disabled', true);
        }

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

        if (commentsEnabled) {
            row.append(buildCommentsSection(token, tokenKey));
        }

        return row;
    }

    function renderTokenComments(listElement, comments, countElement) {
        const safeComments = Array.isArray(comments) ? comments : [];
        if (countElement && countElement.length) {
            countElement.text(safeComments.length);
        }

        listElement.empty();

        if (!safeComments.length) {
            listElement.append($('<li>', {
                class: 'ssc-token-comments__empty',
                text: translate('commentsEmpty', 'Aucun commentaire pour ce token pour le moment.'),
            }));
            return;
        }

        safeComments.forEach((comment) => {
            if (!comment || typeof comment !== 'object') {
                return;
            }

            const item = $('<li>', { class: 'ssc-token-comments__item' });
            const body = $('<div>', { class: 'ssc-token-comments__body' });

            const author = comment.created_by && typeof comment.created_by === 'object' ? comment.created_by : null;
            const mentions = Array.isArray(comment.mentions) ? comment.mentions : [];
            const message = typeof comment.message === 'string' ? comment.message : '';
            const createdAt = typeof comment.created_at === 'string' ? comment.created_at : '';
            const formattedDate = formatDateTimeValue(createdAt) || createdAt;

            const header = $('<div>', { class: 'ssc-token-comments__meta' });
            if (author && author.avatar) {
                header.append($('<img>', {
                    src: author.avatar,
                    alt: '',
                    class: 'ssc-token-comments__avatar',
                    loading: 'lazy',
                }));
            }

            const authorLine = $('<p>', { class: 'ssc-token-comments__author' });
            const authorName = author && author.name ? author.name : translate('activitySystemUser', 'Système');
            authorLine.text(authorName);
            header.append(authorLine);

            const metaLine = $('<p>', { class: 'ssc-token-comments__meta-line' });
            if (formattedDate) {
                metaLine.append($('<span>', {
                    text: sprintf(translate('commentsCreatedAt', 'Publié le %s'), formattedDate),
                }));
            }
            if (author && author.name) {
                if (formattedDate) {
                    metaLine.append($('<span>', { text: ' · ' }));
                }
                metaLine.append($('<span>', {
                    text: sprintf(translate('commentsCreatedBy', 'par %s'), author.name),
                }));
            }
            if (metaLine.text()) {
                header.append(metaLine);
            }

            item.append(header);

            const messageElement = $('<p>', {
                class: 'ssc-token-comments__message',
                text: message,
            });
            body.append(messageElement);

            if (mentions.length) {
                const mentionsList = $('<ul>', { class: 'ssc-token-comments__mentions' });
                mentions.forEach((mention) => {
                    if (!mention || typeof mention !== 'object') {
                        return;
                    }
                    const mentionItem = $('<li>', { class: 'ssc-token-comments__mention' });
                    if (mention.avatar) {
                        mentionItem.append($('<img>', {
                            src: mention.avatar,
                            alt: '',
                            loading: 'lazy',
                        }));
                    }
                    mentionItem.append($('<span>', { text: mention.name || '' }));
                    mentionsList.append(mentionItem);
                });
                body.append(mentionsList);
            }

            item.append(body);
            listElement.append(item);
        });
    }

    function buildCommentMentionSelect() {
        const select = $('<select>', { class: 'ssc-token-comment-mention-select' });
        select.append($('<option>', {
            value: '',
            text: translate('commentsMentionsLabel', 'Mentionner un collaborateur'),
        }));
        collaborators.forEach((collaborator) => {
            if (!collaborator || typeof collaborator !== 'object') {
                return;
            }
            select.append($('<option>', {
                value: collaborator.id,
                text: collaborator.name,
            }));
        });

        return select;
    }

    function updateMentionChips(form) {
        const mentions = form.data('mentions') || [];
        const container = form.find('.ssc-token-comment-mentions');
        container.empty();

        if (!mentions.length) {
            container.attr('hidden', 'hidden');
            return;
        }

        container.removeAttr('hidden');

        mentions.forEach((id) => {
            const collaborator = collaborators.find((entry) => Number(entry.id) === Number(id));
            if (!collaborator) {
                return;
            }
            const chip = $('<span>', {
                class: 'ssc-token-comment-mention-chip',
                'data-id': collaborator.id,
            });
            chip.append($('<span>', {
                class: 'ssc-token-comment-mention-name',
                text: collaborator.name,
            }));
            chip.append($('<button>', {
                type: 'button',
                class: 'ssc-token-comment-mention-remove',
                'data-id': collaborator.id,
                'aria-label': sprintf(translate('commentsRemoveMention', 'Retirer'), collaborator.name),
                text: '×',
            }));
            container.append(chip);
        });
    }

    function buildCommentsSection(token, tokenKey) {
        const section = $('<section>', {
            class: 'ssc-token-comments',
            'data-token-key': tokenKey,
        });

        const header = $('<header>', { class: 'ssc-token-comments__header' });
        header.append($('<h4>', { text: translate('commentsPanelTitle', 'Commentaires') }));
        const count = $('<span>', { class: 'ssc-token-comments__count' });
        header.append(count);
        section.append(header);

        const listElement = $('<ul>', { class: 'ssc-token-comments__list' });
        section.append(listElement);

        renderTokenComments(listElement, getCommentsForKey(tokenKey), count);

        if (canComment && restRoot) {
            const form = $('<form>', {
                class: 'ssc-token-comment-form',
                'data-token-key': tokenKey,
            });
            form.data('mentions', []);

            if (collaborators.length) {
                const mentionRow = $('<div>', { class: 'ssc-token-comment-form__row' });
                mentionRow.append(buildCommentMentionSelect());
                mentionRow.append($('<div>', {
                    class: 'ssc-token-comment-mentions',
                    hidden: 'hidden',
                    'aria-live': 'polite',
                }));
                form.append(mentionRow);
            } else {
                form.append($('<p>', {
                    class: 'description ssc-token-comment-mentions-empty',
                    text: translate('commentsMentionsEmpty', 'Aucun collaborateur disponible à mentionner.'),
                }));
            }

            const textarea = $('<textarea>', {
                class: 'ssc-token-comment-input',
                rows: 3,
                placeholder: translate('commentsPlaceholder', 'Ajouter un commentaire…'),
            });
            form.append(textarea);

            const actions = $('<div>', { class: 'ssc-token-comment-actions' });
            actions.append($('<button>', {
                type: 'submit',
                class: 'button button-primary',
                text: translate('commentsSubmitLabel', 'Publier'),
            }));
            form.append(actions);

            section.append(form);
        }

        return section;
    }

    function refreshCommentPanelForKey(key) {
        if (!key) {
            return;
        }
        const selector = `.ssc-token-comments[data-token-key="${escapeCssSelector(key)}"]`;
        const section = builder.find(selector);
        if (!section.length) {
            return;
        }
        const list = section.find('.ssc-token-comments__list');
        const count = section.find('.ssc-token-comments__count');
        renderTokenComments(list, getCommentsForKey(key), count);
    }

    function refreshAllCommentPanels() {
        tokenCommentMap.forEach((comments, key) => {
            refreshCommentPanelForKey(key);
        });
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
        builder.toggleClass('ssc-token-builder--readonly', readModeAvailable && readMode);
        if (addButton.length) {
            addButton.prop('disabled', readModeAvailable && readMode);
        }

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
            name: defaultNewTokenName,
            value: defaultValue,
            type: defaultType,
            description: '',
            group: defaultGroupName,
            context: defaultContext,
            status: getDefaultStatusValue(),
            owner: 0,
            version: '',
            changelog: '',
            linked_components: [],
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

    function fetchApprovals() {
        if (!restRoot || !approvalsAvailable) {
            return;
        }

        $.ajax({
            url: restRoot + 'approvals',
            method: 'GET',
            beforeSend: function(xhr) {
                if (restNonce) {
                    xhr.setRequestHeader('X-WP-Nonce', restNonce);
                }
            },
        }).done(function(response) {
            if (response && Array.isArray(response.approvals)) {
                primeApprovals(response.approvals);
                if (!hasLocalChanges) {
                    renderTokens();
                }
            }
        }).fail(function(jqXHR) {
            if (jqXHR && jqXHR.status === 403) {
                approvalsAvailable = false;
                if (!hasLocalChanges) {
                    renderTokens();
                }
            }
        });
    }

    function primeComments(comments) {
        if (!Array.isArray(comments)) {
            return;
        }
        const grouped = {};
        comments.forEach((comment) => {
            if (!comment || typeof comment !== 'object') {
                return;
            }
            const key = typeof comment.entity_id === 'string' ? comment.entity_id : '';
            if (!key) {
                return;
            }
            if (!grouped[key]) {
                grouped[key] = [];
            }
            grouped[key].push(comment);
        });

        Object.keys(grouped).forEach((key) => {
            setCommentsForKey(key, grouped[key]);
        });
    }

    function fetchTokenComments() {
        if (!commentsEnabled || commentsPrimed || !restRoot) {
            return;
        }

        commentsPrimed = true;

        $.ajax({
            url: restRoot + 'comments',
            method: 'GET',
            data: {
                entity_type: 'token',
            },
            beforeSend: function(xhr) {
                if (restNonce) {
                    xhr.setRequestHeader('X-WP-Nonce', restNonce);
                }
            },
        }).done(function(response) {
            if (response && Array.isArray(response.comments)) {
                primeComments(response.comments);
                refreshAllCommentPanels();
            }
        }).fail(function() {
            commentsPrimed = false;
        });
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
            fetchApprovals();
            fetchTokenComments();
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
            fetchApprovals();
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

    function promptForApprovalPriority() {
        if (!approvalPriorityOptions.length) {
            return defaultApprovalPriority;
        }

        const promptMessage = translate('approvalPriorityPrompt', 'Choisissez la priorité (faible, normale ou haute).');
        const optionsSummary = approvalPriorityOptions
            .map((option) => `- ${option.label} (${option.value})`)
            .join('\n');
        const defaultLabel = getApprovalPriorityLabel(defaultApprovalPriority);
        const response = window.prompt(`${promptMessage}\n${optionsSummary}`, defaultLabel);

        if (response === null) {
            return null;
        }

        const trimmed = response.trim();
        if (!trimmed.length) {
            return defaultApprovalPriority;
        }

        const normalized = trimmed.toLowerCase();
        const directValue = approvalPriorityOptions.find((option) => option.value.toLowerCase() === normalized);
        if (directValue) {
            return directValue.value;
        }

        const exactLabel = approvalPriorityOptions.find((option) => option.label && option.label.toLowerCase() === normalized);
        if (exactLabel) {
            return exactLabel.value;
        }

        const partialLabel = approvalPriorityOptions.find((option) => option.label && option.label.toLowerCase().startsWith(normalized));
        if (partialLabel) {
            return partialLabel.value;
        }

        if (typeof window.sscToast === 'function') {
            window.sscToast(translate('approvalPriorityInvalid', 'Priorité inconnue. La demande a été annulée.'));
        }

        return null;
    }

    function requestTokenApproval(token, comment, priority) {
        if (!restRoot) {
            const deferred = $.Deferred();
            deferred.reject();
            return deferred.promise();
        }

        const payload = {
            token: {
                name: token && typeof token.name === 'string' ? token.name : '',
                context: token && typeof token.context === 'string' ? token.context : defaultContext,
            },
            priority: normalizeApprovalPriority(priority),
        };

        if (comment && comment.trim()) {
            payload.comment = comment.trim();
        }

        return $.ajax({
            url: restRoot + 'approvals',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(payload),
            beforeSend: function(xhr) {
                if (restNonce) {
                    xhr.setRequestHeader('X-WP-Nonce', restNonce);
                }
            },
        });
    }

    $(document).ready(function() {
        const tokenLayout = $('.ssc-token-layout');
        const helpPane = $('#ssc-token-help');
        const helpContent = $('#ssc-token-help-content');
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

                const visibilityTarget = helpContent.length ? helpContent : helpPane;

                if (visibilityTarget.length) {
                    visibilityTarget.attr('aria-hidden', collapsed ? 'true' : 'false');

                    if (collapsed) {
                        visibilityTarget.attr('hidden', 'hidden');
                    } else {
                        visibilityTarget.removeAttr('hidden');
                    }
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

        initializeDeviceLab();

        const paletteApi = (typeof window !== 'undefined' && typeof window.sscCommandPalette === 'object')
            ? window.sscCommandPalette
            : null;
        const paletteSupportsTokens = !!(paletteApi && typeof paletteApi.registerSource === 'function');
        const highlightClassName = 'ssc-token-row--highlight';
        let highlightTimeoutId = null;

        const highlightTokenRowByKey = function(tokenKey) {
            if (!tokenKey) {
                return;
            }

            const filters = getFilterState();
            if (filters.query || filters.type) {
                updateFilters({ query: '', type: '' });
                syncFilterControls();
                renderTokens();
            }

            const builderRoot = builder.length ? builder : $('#ssc-token-builder');
            if (!builderRoot.length) {
                return;
            }

            const targetRow = builderRoot.find('.ssc-token-row').filter(function() {
                return $(this).data('tokenKey') === tokenKey;
            });

            if (!targetRow.length) {
                return;
            }

            if (highlightTimeoutId) {
                window.clearTimeout(highlightTimeoutId);
                highlightTimeoutId = null;
            }

            builderRoot.find(`.${highlightClassName}`).removeClass(highlightClassName);
            targetRow.addClass(highlightClassName);

            highlightTimeoutId = window.setTimeout(function() {
                targetRow.removeClass(highlightClassName);
                highlightTimeoutId = null;
            }, 2400);

            const element = targetRow.get(0);
            if (element && typeof element.scrollIntoView === 'function') {
                element.scrollIntoView({ block: 'center', behavior: 'smooth' });
            }

            targetRow.attr('tabindex', '-1');
            targetRow.trigger('focus');
            targetRow.removeAttr('tabindex');

            const tokenName = targetRow.find('.token-name').val() || tokenKey.split('@@')[0] || tokenKey;
            speak(sprintf(translate('commandPaletteTokenFocused', 'Token sélectionné : %s'), tokenName), 'polite');
        };

        if (paletteSupportsTokens) {
            paletteApi.registerSource('tokens', function() {
                if (!Array.isArray(tokens) || !tokens.length) {
                    return [];
                }

                return tokens.map(function(token) {
                    const tokenKey = computeTokenKey(token);
                    if (!tokenKey) {
                        return null;
                    }

                    const rawName = token && typeof token.name === 'string' ? token.name : '';
                    const tokenName = rawName && rawName.trim() ? rawName.trim() : translate('unnamedToken', 'Token sans nom');
                    const rawContext = token && typeof token.context === 'string' ? token.context : '';
                    const contextLabel = rawContext && rawContext.trim() ? rawContext.trim() : defaultContext;
                    const rawGroup = token && typeof token.group === 'string' ? token.group : '';
                    const groupLabel = rawGroup && rawGroup.trim() ? rawGroup.trim() : defaultGroupName;
                    const typeMeta = token && typeof token.type === 'string' ? tokenTypes[token.type] : null;
                    const typeLabel = typeMeta && typeMeta.label ? typeMeta.label : (token.type || '');
                    const keywords = [
                        tokenName,
                        contextLabel,
                        groupLabel,
                        typeLabel,
                        tokenKey,
                    ];

                    if (token && typeof token.value === 'string' && token.value.trim()) {
                        keywords.push(token.value.trim());
                    }
                    if (token && typeof token.description === 'string' && token.description.trim()) {
                        keywords.push(token.description.trim());
                    }

                    return {
                        title: tokenName,
                        subtitle: `${contextLabel} · ${groupLabel}`,
                        keywords: keywords.filter(Boolean),
                        perform: function() {
                            highlightTokenRowByKey(tokenKey);
                        },
                    };
                }).filter(Boolean);
            });
        }

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
        fetchApprovals();

        if (readModeToggle.length) {
            if (readModeAvailable) {
                readModeToggle.text(translate('readModeToggleOn', 'Activer le mode lecture'));
                readModeToggle.on('click', function(event) {
                    event.preventDefault();
                    readMode = !readMode;
                    readModeToggle.attr('aria-pressed', readMode ? 'true' : 'false');
                    readModeToggle.text(readMode
                        ? translate('readModeToggleOff', 'Quitter le mode lecture')
                        : translate('readModeToggleOn', 'Activer le mode lecture'));
                    if (typeof window.sscToast === 'function') {
                        window.sscToast(readMode
                            ? translate('readModeActivated', 'Mode lecture activé — modifications désactivées.')
                            : translate('readModeDeactivated', 'Mode lecture désactivé.')
                        );
                    }
                    renderTokens();
                });
            } else {
                readModeToggle.hide();
            }
        }

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

        builder.on('click', '.token-request-approval', function(event) {
            event.preventDefault();
            const button = $(this);

            if (button.is(':disabled')) {
                return;
            }

            const row = button.closest('.ssc-token-row');
            const index = row.data('index');
            if (typeof index !== 'number' || index < 0 || index >= tokens.length) {
                return;
            }

            const token = tokens[index];
            if (!token || typeof token !== 'object') {
                return;
            }

            if (hasLocalChanges) {
                if (typeof window.sscToast === 'function') {
                    window.sscToast(i18n.approvalRequestDisabledUnsaved || 'Enregistrez vos tokens avant de demander une revue.');
                }
                button.prop('disabled', true);
                return;
            }

            const promptMessage = i18n.approvalCommentPrompt || '';
            let comment = '';
            if (promptMessage) {
                const response = window.prompt(promptMessage, '');
                if (response === null) {
                    return;
                }
                comment = response;
            }

            const priority = promptForApprovalPriority();
            if (priority === null) {
                return;
            }

            button.prop('disabled', true).addClass('is-busy');

            requestTokenApproval(token, comment, priority).done(function() {
                if (typeof window.sscToast === 'function') {
                    window.sscToast(i18n.approvalRequestedToast || 'Demande d’approbation envoyée.');
                }
                fetchApprovals();
            }).fail(function(jqXHR) {
                if (jqXHR && jqXHR.status === 403) {
                    approvalsAvailable = false;
                    if (!hasLocalChanges) {
                        renderTokens();
                    }
                } else {
                    if (typeof window.sscToast === 'function') {
                        window.sscToast(i18n.approvalRequestFailedToast || 'Impossible d’envoyer la demande d’approbation.');
                    }
                    button.prop('disabled', false);
                }
            }).always(function() {
                button.removeClass('is-busy');
            });
        });

        builder.on('change', '.ssc-token-comment-mention-select', function() {
            const select = $(this);
            const rawValue = select.val();
            const mentionId = parseInt(rawValue, 10);
            if (!mentionId) {
                return;
            }
            const form = select.closest('form');
            const mentions = form.data('mentions') || [];
            if (mentions.indexOf(mentionId) === -1) {
                mentions.push(mentionId);
                form.data('mentions', mentions);
                updateMentionChips(form);
                const collaborator = collaborators.find((entry) => Number(entry.id) === mentionId);
                if (collaborator && typeof window.sscToast === 'function') {
                    window.sscToast(sprintf(translate('commentsMentionAdded', 'Mention ajoutée : %s'), collaborator.name));
                }
            }
            select.val('');
        });

        builder.on('click', '.ssc-token-comment-mention-remove', function() {
            const button = $(this);
            const mentionId = parseInt(button.data('id'), 10);
            const form = button.closest('form');
            const mentions = form.data('mentions') || [];
            const nextMentions = mentions.filter((value) => Number(value) !== mentionId);
            form.data('mentions', nextMentions);
            updateMentionChips(form);
        });

        builder.on('submit', '.ssc-token-comment-form', function(event) {
            event.preventDefault();
            if (!canComment || !restRoot) {
                return;
            }

            const form = $(this);
            const tokenKey = form.data('token-key');
            const textarea = form.find('.ssc-token-comment-input');
            const message = (textarea.val() || '').toString().trim();

            if (!message) {
                textarea.focus();
                return;
            }

            const submitButton = form.find('button[type="submit"]');
            submitButton.prop('disabled', true).addClass('is-busy');

            $.ajax({
                url: restRoot + 'comments',
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({
                    entity_type: 'token',
                    entity_id: tokenKey,
                    message: message,
                    mentions: form.data('mentions') || [],
                }),
                beforeSend: function(xhr) {
                    if (restNonce) {
                        xhr.setRequestHeader('X-WP-Nonce', restNonce);
                    }
                },
            }).done(function(response) {
                if (response && response.comment) {
                    appendCommentToKey(tokenKey, response.comment);
                    refreshCommentPanelForKey(tokenKey);
                    textarea.val('');
                    form.data('mentions', []);
                    updateMentionChips(form);
                    if (typeof window.sscToast === 'function') {
                        window.sscToast(translate('commentsSendSuccess', 'Commentaire publié.'));
                    }
                }
            }).fail(function() {
                if (typeof window.sscToast === 'function') {
                    window.sscToast(translate('commentsSendError', 'Impossible d’enregistrer le commentaire.'));
                }
            }).always(function() {
                submitButton.prop('disabled', false).removeClass('is-busy');
            });
        });
    });
})(jQuery);
