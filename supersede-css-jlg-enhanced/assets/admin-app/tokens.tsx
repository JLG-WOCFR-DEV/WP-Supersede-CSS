import { useCallback, useEffect, useMemo, useRef, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import {
    Button,
    Card,
    CardBody,
    CardHeader,
    Flex,
    FlexBlock,
    FlexItem,
    Spinner,
    Badge,
    Notice,
} from '@wordpress/components';
import {
    Token,
    TokenTypeMap,
    calculateDuplicateCanonical,
    buildDuplicateLabels,
    canonicalName,
    copyToClipboard,
    defaultToken,
    generateCss,
    getGroupName,
    normalizeName,
    parseServerDuplicateResponse,
} from './utils';

type ToastOptions = {
    politeness?: 'assertive' | 'polite';
    role?: string;
};

type TokensData = {
    tokens?: Token[];
    types?: TokenTypeMap;
    css?: string;
    i18n?: Partial<Record<string, string>>;
};

type RestInfo = {
    root: string;
    nonce: string;
};

declare global {
    interface Window {
        SSC_TOKENS_DATA?: TokensData;
        SSC?: {
            rest?: Partial<RestInfo>;
        };
        sscToast?: (message: string, options?: ToastOptions) => void;
        wp?: {
            a11y?: {
                speak?: (message: string, politeness?: 'assertive' | 'polite') => void;
            };
        };
    }
}

const FALLBACK_TYPES: TokenTypeMap = {
    color: { label: __('Couleur', 'supersede-css-jlg'), input: 'color' },
    text: { label: __('Texte', 'supersede-css-jlg'), input: 'text' },
    number: { label: __('Nombre', 'supersede-css-jlg'), input: 'number' },
};

const DEFAULT_I18N: Record<string, string> = {
    addToken: __('Ajouter un token', 'supersede-css-jlg'),
    emptyState: __('Aucun token pour le moment. Utilisez le bouton ci-dessous pour commencer.', 'supersede-css-jlg'),
    groupLabel: __('Groupe', 'supersede-css-jlg'),
    nameLabel: __('Nom', 'supersede-css-jlg'),
    valueLabel: __('Valeur', 'supersede-css-jlg'),
    typeLabel: __('Type', 'supersede-css-jlg'),
    descriptionLabel: __('Description', 'supersede-css-jlg'),
    deleteLabel: __('Supprimer', 'supersede-css-jlg'),
    saveSuccess: __('Tokens enregistrés', 'supersede-css-jlg'),
    saveError: __('Impossible d’enregistrer les tokens.', 'supersede-css-jlg'),
    duplicateError: __('Certains tokens utilisent le même nom. Corrigez les doublons avant d’enregistrer.', 'supersede-css-jlg'),
    duplicateListPrefix: __('Doublons :', 'supersede-css-jlg'),
    copySuccess: __('Tokens copiés', 'supersede-css-jlg'),
    reloadConfirm: __('Des modifications locales non enregistrées seront perdues. Continuer ?', 'supersede-css-jlg'),
    toolbarTitle: __('🎨 Éditeur Visuel de Tokens', 'supersede-css-jlg'),
    generatedTitle: __('📜 Code CSS généré', 'supersede-css-jlg'),
    saveLabel: __('Enregistrer les Tokens', 'supersede-css-jlg'),
    copyLabel: __('Copier le CSS', 'supersede-css-jlg'),
    reloadLabel: __('Recharger', 'supersede-css-jlg'),
    unsavedBadge: __('Modifications locales', 'supersede-css-jlg'),
};

function useRestInfo(): RestInfo {
    const root = window.SSC?.rest?.root ?? '';
    const nonce = window.SSC?.rest?.nonce ?? '';
    return { root, nonce };
}

function speak(message: string, politeness: 'assertive' | 'polite' = 'assertive') {
    if (window.wp?.a11y?.speak) {
        window.wp.a11y.speak(message, politeness);
    }
}

function toast(message: string, options: ToastOptions = {}) {
    if (typeof window.sscToast === 'function') {
        window.sscToast(message, options);
    }
    if (options.politeness === 'assertive') {
        speak(message, 'assertive');
    }
}

function useBeforeUnload(enabled: boolean, message: string) {
    useEffect(() => {
        if (!enabled) {
            return;
        }
        const handler = (event: BeforeUnloadEvent) => {
            event.preventDefault();
            event.returnValue = message;
            return message;
        };
        window.addEventListener('beforeunload', handler);
        return () => window.removeEventListener('beforeunload', handler);
    }, [enabled, message]);
}

type TokenRowProps = {
    token: Token;
    index: number;
    types: TokenTypeMap;
    duplicateSet: Set<string>;
    i18n: Record<string, string>;
    onUpdate: (index: number, patch: Partial<Token>) => void;
    onBlurName: (index: number) => void;
    onDelete: (index: number) => void;
};

const TokenRow = ({
    token,
    index,
    types,
    duplicateSet,
    i18n,
    onUpdate,
    onBlurName,
    onDelete,
}: TokenRowProps) => {
    const canonical = canonicalName(token.name);
    const isDuplicate = canonical !== '' && duplicateSet.has(canonical);
    const typeOptions = Object.keys(types).length ? Object.keys(types) : Object.keys(FALLBACK_TYPES);
    const currentType = token.type && types[token.type] ? token.type : typeOptions[0];
    const typeMeta = (types[currentType] ?? FALLBACK_TYPES[currentType] ?? { input: 'text' });
    const inputKind = typeMeta?.input ?? 'text';
    const showColorPicker = inputKind === 'color' && /^#([0-9a-f]{3}|[0-9a-f]{6})$/i.test(token.value.trim());

    return (
        <Card className={`ssc-token-row${isDuplicate ? ' ssc-token-row--duplicate' : ''}`} data-index={index}>
            <CardHeader>
                <Flex justify="space-between" align="center" wrap>
                    <FlexBlock>
                        <strong>{token.name || i18n.nameLabel}</strong>
                    </FlexBlock>
                    <FlexItem>
                        <Button
                            variant="tertiary"
                            isDestructive
                            className="token-delete"
                            onClick={() => onDelete(index)}
                        >
                            {i18n.deleteLabel}
                        </Button>
                    </FlexItem>
                </Flex>
            </CardHeader>
            <CardBody>
                <div className="ssc-token-grid">
                    <label className="ssc-token-field">
                        <span className="ssc-token-field__label">{i18n.nameLabel}</span>
                        <input
                            type="text"
                            className={`token-field-input token-name${isDuplicate ? ' token-field-input--duplicate' : ''}`}
                            aria-invalid={isDuplicate ? 'true' : undefined}
                            value={token.name}
                            onChange={(event) => onUpdate(index, { name: event.target.value })}
                            onBlur={() => onBlurName(index)}
                        />
                    </label>
                    <label className="ssc-token-field">
                        <span className="ssc-token-field__label">{i18n.valueLabel}</span>
                        {inputKind === 'color' ? (
                            <input
                                type={showColorPicker ? 'color' : 'text'}
                                className="token-field-input token-value"
                                value={token.value}
                                onChange={(event) => onUpdate(index, { value: event.target.value })}
                            />
                        ) : (
                            <input
                                type={inputKind === 'number' ? 'number' : 'text'}
                                step={inputKind === 'number' ? '0.01' : undefined}
                                className="token-field-input token-value"
                                value={token.value}
                                onChange={(event) => onUpdate(index, { value: event.target.value })}
                            />
                        )}
                    </label>
                    <label className="ssc-token-field">
                        <span className="ssc-token-field__label">{i18n.typeLabel}</span>
                        <select
                            className="token-field-input token-type"
                            value={currentType}
                            onChange={(event) => onUpdate(index, { type: event.target.value })}
                        >
                            {typeOptions.map((optionKey) => {
                                const optionMeta = types[optionKey] ?? FALLBACK_TYPES[optionKey];
                                const optionLabel = optionMeta?.label ?? optionKey;
                                return (
                                    <option key={optionKey} value={optionKey}>
                                        {optionLabel}
                                    </option>
                                );
                            })}
                        </select>
                    </label>
                    <label className="ssc-token-field">
                        <span className="ssc-token-field__label">{i18n.groupLabel}</span>
                        <input
                            type="text"
                            list="ssc-token-groups-list"
                            className="token-field-input token-group"
                            value={token.group}
                            onChange={(event) => onUpdate(index, { group: event.target.value })}
                        />
                    </label>
                    <label className="ssc-token-field">
                        <span className="ssc-token-field__label">{i18n.descriptionLabel}</span>
                        <textarea
                            className="token-field-input token-description"
                            rows={2}
                            value={token.description}
                            onChange={(event) => onUpdate(index, { description: event.target.value })}
                        />
                    </label>
                </div>
            </CardBody>
        </Card>
    );
};

const EMPTY_TRANSLATIONS: Record<string, string> = {};

export function TokensApp(): JSX.Element | null {
    const localized = window.SSC_TOKENS_DATA ?? ({} as TokensData);
    const translations = useMemo(() => ({ ...DEFAULT_I18N, ...(localized.i18n ?? EMPTY_TRANSLATIONS) }), [localized.i18n]);
    const rest = useRestInfo();
    const [tokens, setTokens] = useState<Token[]>(() => {
        if (Array.isArray(localized.tokens)) {
            return localized.tokens.map((token) => ({ ...token }));
        }
        return [];
    });
    const tokenTypes = useMemo(() => ({ ...FALLBACK_TYPES, ...(localized.types ?? {}) }), [localized.types]);
    const [isSaving, setIsSaving] = useState(false);
    const [isFetching, setIsFetching] = useState(false);
    const [hasLocalChanges, setHasLocalChanges] = useState(false);
    const [lastError, setLastError] = useState<string | null>(null);
    const initializedRef = useRef(false);

    const cssValue = useMemo(() => generateCss(tokens), [tokens]);

    const duplicates = useMemo(() => calculateDuplicateCanonical(tokens), [tokens]);
    const duplicateSet = useMemo(() => new Set(duplicates), [duplicates]);

    const groupOptions = useMemo(() => {
        const groups = new Set<string>();
        tokens.forEach((token) => {
            groups.add(getGroupName(token.group));
        });
        return Array.from(groups);
    }, [tokens]);

    useBeforeUnload(hasLocalChanges, translations.reloadConfirm);

    useEffect(() => {
        if (initializedRef.current) {
            return;
        }
        if (typeof localized.css === 'string' && localized.css) {
            // Preload initial CSS until the first render updates the style element.
            const previewStyle = document.getElementById('ssc-tokens-preview-style');
            if (previewStyle) {
                previewStyle.textContent = localized.css;
            }
        }
        initializedRef.current = true;
    }, [localized.css]);

    const updateToken = useCallback(
        (index: number, patch: Partial<Token>) => {
            setTokens((current) => {
                const next = current.map((item, itemIndex) =>
                    itemIndex === index ? { ...item, ...patch } : item
                );
                return next;
            });
            setHasLocalChanges(true);
        },
        []
    );

    const handleBlurName = useCallback(
        (index: number) => {
            setTokens((current) => {
                const next = current.map((item, itemIndex) => {
                    if (itemIndex !== index) {
                        return item;
                    }
                    const normalized = normalizeName(item.name);
                    return { ...item, name: normalized };
                });
                return next;
            });
            setHasLocalChanges(true);
        },
        []
    );

    const handleDelete = useCallback(
        (index: number) => {
            setTokens((current) => current.filter((_, itemIndex) => itemIndex !== index));
            setHasLocalChanges(true);
        },
        []
    );

    const handleAdd = useCallback(() => {
        setTokens((current) => [...current, defaultToken(tokenTypes)]);
        setHasLocalChanges(true);
    }, [tokenTypes]);

    const notifyDuplicateError = useCallback(
        (labels: string[], message?: string) => {
            const baseMessage = message && message.trim() ? message : translations.duplicateError;
            const normalizedLabels = labels.filter((label) => label && label.trim() !== '');
            const finalMessage = normalizedLabels.length
                ? `${baseMessage} ${translations.duplicateListPrefix} ${normalizedLabels.join(', ')}`
                : baseMessage;
            toast(finalMessage, { politeness: 'assertive', role: 'alert' });
        },
        [translations.duplicateError, translations.duplicateListPrefix]
    );

    const handleDuplicateConflict = useCallback(
        (labels: string[], message?: string) => {
            notifyDuplicateError(labels, message);
        },
        [notifyDuplicateError]
    );

    const fetchTokens = useCallback(
        async (force = false) => {
            if (!rest.root) {
                return;
            }
            setIsFetching(true);
            setLastError(null);
            try {
                const response = await fetch(`${rest.root}tokens`, {
                    headers: {
                        'X-WP-Nonce': rest.nonce,
                    },
                });
                if (!response.ok) {
                    return;
                }
                const json = (await response.json()) as TokensData & { css?: string };
                if (Array.isArray(json.tokens) && (force || !hasLocalChanges)) {
                    setTokens(json.tokens.map((token) => ({ ...token })));
                    setHasLocalChanges(false);
                }
            } catch (error) {
                // eslint-disable-next-line no-console
                console.error('SSC tokens fetch failed', error);
            } finally {
                setIsFetching(false);
            }
        },
        [rest.root, rest.nonce, hasLocalChanges]
    );

    useEffect(() => {
        fetchTokens();
    }, [fetchTokens]);

    const handleReload = useCallback(() => {
        if (hasLocalChanges && !window.confirm(translations.reloadConfirm)) {
            return;
        }
        setHasLocalChanges(false);
        fetchTokens(true);
    }, [fetchTokens, hasLocalChanges, translations.reloadConfirm]);

    const handleSave = useCallback(async () => {
        if (!rest.root) {
            return;
        }
        const duplicatesCanonical = calculateDuplicateCanonical(tokens);
        if (duplicatesCanonical.length) {
            const labels = buildDuplicateLabels(tokens, duplicatesCanonical);
            notifyDuplicateError(labels);
            return;
        }
        setIsSaving(true);
        setLastError(null);
        try {
            const response = await fetch(`${rest.root}tokens`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': rest.nonce,
                },
                body: JSON.stringify({ tokens }),
            });
            if (!response.ok) {
                if (response.status === 422) {
                    const payload = (await response.json()) as Record<string, unknown>;
                    const parsed = parseServerDuplicateResponse(payload);
                    if (parsed) {
                        const rawMessage = payload['message'];
                        const message = typeof rawMessage === 'string' ? rawMessage : undefined;
                        handleDuplicateConflict(parsed.labels, message);
                        return;
                    }
                }
                setLastError(translations.saveError);
                toast(translations.saveError, { politeness: 'assertive' });
                return;
            }
            const json = (await response.json()) as TokensData;
            if (Array.isArray(json.tokens)) {
                setTokens(json.tokens.map((token) => ({ ...token })));
            }
            setHasLocalChanges(false);
            toast(translations.saveSuccess, { politeness: 'polite' });
        } catch (error) {
            setLastError(translations.saveError);
            // eslint-disable-next-line no-console
            console.error('SSC tokens save failed', error);
            toast(translations.saveError, { politeness: 'assertive' });
        } finally {
            setIsSaving(false);
        }
    }, [handleDuplicateConflict, notifyDuplicateError, rest.nonce, rest.root, tokens, translations.saveError, translations.saveSuccess]);

    const handleCopy = useCallback(async () => {
        await copyToClipboard(cssValue);
        toast(translations.copySuccess, { politeness: 'polite' });
    }, [cssValue, translations.copySuccess]);

    const groupedTokens = useMemo(() => {
        const groups = new Map<string, Array<{ token: Token; index: number }>>();
        tokens.forEach((token, index) => {
            const groupName = getGroupName(token.group);
            if (!groups.has(groupName)) {
                groups.set(groupName, []);
            }
            groups.get(groupName)!.push({ token, index });
        });
        return Array.from(groups.entries());
    }, [tokens]);

    return (
        <div className="ssc-token-app" data-component="tokens">
            <Card className="ssc-token-toolbar">
                <CardBody>
                    <Flex align="center" justify="space-between" wrap>
                        <FlexBlock>
                            <h3>{translations.toolbarTitle}</h3>
                        </FlexBlock>
                        <FlexItem>
                            <Flex align="center" gap={8} wrap>
                                {hasLocalChanges && <Badge status="warning">{translations.unsavedBadge}</Badge>}
                                <Button id="ssc-token-add" variant="primary" onClick={handleAdd}>
                                    {translations.addToken}
                                </Button>
                                <Button
                                    id="ssc-tokens-save"
                                    variant="primary"
                                    isBusy={isSaving}
                                    disabled={isSaving}
                                    onClick={handleSave}
                                >
                                    {translations.saveLabel}
                                </Button>
                                <Button id="ssc-tokens-copy" variant="secondary" onClick={handleCopy}>
                                    {translations.copyLabel}
                                </Button>
                                <Button id="ssc-tokens-reload" variant="tertiary" onClick={handleReload}>
                                    {translations.reloadLabel}
                                </Button>
                            </Flex>
                        </FlexItem>
                    </Flex>
                </CardBody>
            </Card>

            {isFetching && (
                <Notice status="info" isDismissible={false} className="ssc-token-notice">
                    <Flex align="center" gap={8}>
                        <Spinner />
                        <span>{__('Chargement des tokens…', 'supersede-css-jlg')}</span>
                    </Flex>
                </Notice>
            )}

            {lastError && (
                <Notice status="error" onRemove={() => setLastError(null)}>
                    {lastError}
                </Notice>
            )}

            {tokens.length === 0 ? (
                <Card className="ssc-token-empty">
                    <CardBody>{translations.emptyState}</CardBody>
                </Card>
            ) : (
                groupedTokens.map(([groupName, items]) => (
                    <Card key={groupName} className="ssc-token-group">
                        <CardHeader>
                            <h4>{groupName}</h4>
                        </CardHeader>
                        <CardBody>
                            <div className="ssc-token-group-grid">
                                {items.map(({ token, index }) => (
                                    <TokenRow
                                        key={`${groupName}-${index}`}
                                        token={token}
                                        index={index}
                                        types={tokenTypes}
                                        duplicateSet={duplicateSet}
                                        i18n={translations}
                                        onUpdate={updateToken}
                                        onBlurName={handleBlurName}
                                        onDelete={handleDelete}
                                    />
                                ))}
                            </div>
                        </CardBody>
                    </Card>
                ))
            )}

            <Card className="ssc-token-css">
                <CardHeader>
                    <h3>{translations.generatedTitle}</h3>
                </CardHeader>
                <CardBody>
                    {groupOptions.length > 0 && (
                        <datalist id="ssc-token-groups-list">
                            {groupOptions.map((group) => (
                                <option key={group} value={group} />
                            ))}
                        </datalist>
                    )}
                    <textarea id="ssc-tokens" readOnly className="ssc-token-css-textarea" value={cssValue} rows={12} />
                </CardBody>
            </Card>

            <Card className="ssc-token-preview">
                <CardHeader>
                    <h3>{__('👁️ Aperçu en Direct', 'supersede-css-jlg')}</h3>
                </CardHeader>
                <CardBody>
                    <style id="ssc-tokens-preview-style">{cssValue}</style>
                    <div id="ssc-tokens-preview" className="ssc-token-preview-box">
                        <Button variant="primary" style={{ backgroundColor: 'var(--couleur-principale)', borderRadius: 'var(--radius-moyen)' }}>
                            {__('Bouton Principal', 'supersede-css-jlg')}
                        </Button>
                        <a href="#" style={{ marginLeft: '16px', color: 'var(--couleur-principale)' }}>
                            {__('Lien Principal', 'supersede-css-jlg')}
                        </a>
                    </div>
                </CardBody>
            </Card>
        </div>
    );
}

export default TokensApp;
