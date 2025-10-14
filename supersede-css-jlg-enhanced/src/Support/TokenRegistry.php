<?php declare(strict_types=1);

namespace SSC\Support;

use SSC\Infra\Activity\EventRecorder;

if (!defined('ABSPATH')) { exit; }

final class TokenRegistry
{
    private const OPTION_REGISTRY = 'ssc_tokens_registry';
    private const OPTION_CSS = 'ssc_tokens_css';
    private const REGISTRY_NOT_FOUND = '__ssc_tokens_registry_missing__';
    private const DEFAULT_CONTEXT = ':root';
    private const STATUS_DRAFT = 'draft';
    private const STATUS_READY = 'ready';
    private const STATUS_DEPRECATED = 'deprecated';

    /**
     * @var array<string, array{label: string, description: string}>
     */
    private const SUPPORTED_STATUSES = [
        self::STATUS_DRAFT => [
            'label' => 'Brouillon',
            'description' => 'Travail en cours, nécessite une revue avant diffusion.',
        ],
        self::STATUS_READY => [
            'label' => 'Prêt',
            'description' => 'Validé pour la production et utilisable par les équipes.',
        ],
        self::STATUS_DEPRECATED => [
            'label' => 'Déprécié',
            'description' => 'Remplacé ou obsolète, à retirer des interfaces.',
        ],
    ];

    /**
     * @var array<int, array{value: string, label: string, preview?: array<string, string>}>
     */
    private const SUPPORTED_CONTEXTS = [
        [
            'value' => ':root',
            'label' => 'Global (:root)',
            'preview' => ['type' => 'root'],
        ],
        [
            'value' => '[data-theme="dark"]',
            'label' => 'Mode sombre ([data-theme="dark"])',
            'preview' => [
                'type' => 'attribute',
                'name' => 'data-theme',
                'value' => 'dark',
            ],
        ],
        [
            'value' => '[data-theme="light"]',
            'label' => 'Mode clair ([data-theme="light"])',
            'preview' => [
                'type' => 'attribute',
                'name' => 'data-theme',
                'value' => 'light',
            ],
        ],
        [
            'value' => '.is-admin',
            'label' => 'Interface administrateur (.is-admin)',
            'preview' => [
                'type' => 'class',
                'value' => 'is-admin',
            ],
        ],
    ];

    /**
     * Supported token types exposed to the UI.
     *
     * The `input` key controls the form control rendered for the token value.
     * Accepted values are:
     *  - `color`: renders an `<input type="color">` (falls back to text if value is not a valid hex).
     *  - `number`: renders an `<input type="number">`.
     *  - `text`: renders an `<input type="text">`.
     *  - `textarea`: renders a `<textarea>` and keeps multi-line values intact.
     *
     * @var array<string, array{
     *     label: string,
     *     input: 'color'|'number'|'text'|'textarea',
     *     placeholder?: string,
     *     help?: string,
     *     rows?: positive-int
     * }>
     */
    private const SUPPORTED_TYPES = [
        'color' => [
            'label' => 'Couleur',
            'input' => 'color',
            'help' => 'Utilisez un code hexadécimal (ex. #4f46e5) ou une variable existante.',
        ],
        'text' => [
            'label' => 'Texte',
            'input' => 'text',
            'placeholder' => 'Ex. 16px ou clamp(1rem, 2vw, 2rem)',
            'help' => 'Idéal pour les valeurs libres (unités CSS, fonctions, etc.).',
        ],
        'number' => [
            'label' => 'Nombre',
            'input' => 'number',
            'help' => 'Pour les valeurs strictement numériques (ex. 1.25).',
        ],
        'spacing' => [
            'label' => 'Espacement',
            'input' => 'text',
            'placeholder' => 'Ex. 16px 24px',
            'help' => 'Convient aux marges/paddings ou aux espacements multiples.',
        ],
        'font' => [
            'label' => 'Typographie',
            'input' => 'text',
            'placeholder' => 'Ex. "Inter", sans-serif',
            'help' => 'Définissez la pile de polices complète avec les guillemets requis.',
        ],
        'shadow' => [
            'label' => 'Ombre',
            'input' => 'textarea',
            'placeholder' => "0 2px 4px rgba(15, 23, 42, 0.25)",
            'rows' => 3,
            'help' => 'Accepte plusieurs valeurs box-shadow, une par ligne si nécessaire.',
        ],
        'gradient' => [
            'label' => 'Dégradé',
            'input' => 'textarea',
            'placeholder' => 'linear-gradient(135deg, #4f46e5, #7c3aed)',
            'rows' => 3,
            'help' => 'Pour les dégradés CSS complexes (linear-, radial-…).',
        ],
        'border' => [
            'label' => 'Bordure',
            'input' => 'text',
            'placeholder' => 'Ex. 1px solid currentColor',
            'help' => 'Combinez largeur, style et couleur de bordure.',
        ],
        'dimension' => [
            'label' => 'Dimensions',
            'input' => 'text',
            'placeholder' => 'Ex. 320px ou clamp(280px, 50vw, 480px)',
            'help' => 'Largeurs/hauteurs ou tailles maximales avec clamp/min/max.',
        ],
        'transition' => [
            'label' => 'Transition',
            'input' => 'textarea',
            'placeholder' => "all 0.3s ease-in-out\ncolor 150ms ease", // multi-ligne possible
            'rows' => 2,
            'help' => 'Définissez des transitions multi-propriétés, une par ligne.',
        ],
    ];

    /**
     * @return array<int, array{value: string, label: string, preview?: array<string, string>}>
     */
    public static function getSupportedContexts(): array
    {
        $contexts = [];

        foreach (self::SUPPORTED_CONTEXTS as $context) {
            $normalized = [
                'value' => $context['value'],
                'label' => __($context['label'], 'supersede-css-jlg'),
            ];

            if (isset($context['preview']) && is_array($context['preview'])) {
                $normalized['preview'] = $context['preview'];
            }

            $contexts[] = $normalized;
        }

        return $contexts;
    }

    public static function getDefaultContext(): string
    {
        return self::DEFAULT_CONTEXT;
    }

    /**
     * @return array<int, array{value: string, label: string, description: string}>
     */
    public static function getSupportedStatuses(): array
    {
        $statuses = [];

        foreach (self::SUPPORTED_STATUSES as $value => $meta) {
            $statuses[] = [
                'value' => $value,
                'label' => __($meta['label'], 'supersede-css-jlg'),
                'description' => __($meta['description'], 'supersede-css-jlg'),
            ];
        }

        return $statuses;
    }

    /**
     * @return array<int, string>
     */
    private static function getSupportedContextValues(): array
    {
        static $values = null;

        if ($values !== null) {
            return $values;
        }

        $values = array_map(
            static fn(array $context): string => $context['value'],
            self::SUPPORTED_CONTEXTS
        );

        return $values;
    }

    /**
     * @param mixed $raw
     */
    private static function sanitizeContext($raw): string
    {
        if (!is_string($raw)) {
            return self::DEFAULT_CONTEXT;
        }

        $value = trim($raw);
        if ($value === '') {
            return self::DEFAULT_CONTEXT;
        }

        $value = preg_replace('/\s+/', ' ', $value) ?? $value;
        $value = preg_replace(
            "~[^A-Za-z0-9_\\-:#\\.\\[\\]= \"'\\\\]~",
            '',
            $value
        ) ?? $value;
        $value = trim($value);

        if ($value === '') {
            return self::DEFAULT_CONTEXT;
        }

        foreach (self::getSupportedContextValues() as $supported) {
            if ($supported === $value) {
                return $supported;
            }
        }

        return $value;
    }

    private static function sanitizeStatus($raw): string
    {
        if (!is_string($raw)) {
            $raw = '';
        }

        $status = strtolower(trim($raw));
        $allowed = [self::STATUS_DRAFT, self::STATUS_READY, self::STATUS_DEPRECATED];

        if (!in_array($status, $allowed, true)) {
            return self::STATUS_DRAFT;
        }

        return $status;
    }

    /**
     * @param mixed $raw
     */
    private static function sanitizeOwner($raw): int
    {
        if (is_numeric($raw)) {
            $owner = (int) $raw;

            return $owner > 0 ? $owner : 0;
        }

        return 0;
    }

    /**
     * @param mixed $raw
     */
    private static function sanitizeVersion($raw): string
    {
        if (!is_string($raw)) {
            return '';
        }

        $value = trim($raw);

        if ($value === '') {
            return '';
        }

        $value = ltrim($value, "vV");

        if (!preg_match('/^(\d+\.\d+\.\d+)(?:[+-][0-9A-Za-z.-]+)?$/', $value)) {
            return '';
        }

        return $value;
    }

    /**
     * @param mixed $raw
     * @return array<int, string>
     */
    private static function sanitizeLinkedComponentsList($raw): array
    {
        if (is_string($raw)) {
            $raw = array_filter(array_map('trim', preg_split('/[,\s]+/', $raw) ?: []));
        }

        if (!is_array($raw)) {
            return [];
        }

        $normalized = [];

        foreach ($raw as $value) {
            if (!is_scalar($value)) {
                continue;
            }

            $stringValue = (string) $value;

            if ($stringValue === '') {
                continue;
            }

            $slug = sanitize_title($stringValue);

            if ($slug === '') {
                $slug = strtolower(preg_replace('/[^a-z0-9_-]+/i', '-', $stringValue) ?? '');
            }

            $slug = trim($slug, '-');

            if ($slug === '') {
                continue;
            }

            $normalized[$slug] = $slug;
        }

        ksort($normalized);

        return array_values($normalized);
    }

    /**
     * @return array<int, array{name: string, value: string, type: string, description: string, group: string, context: string, status: string, owner: int, version: string, changelog: string, linked_components: array<int, string>}>
     */
    public static function getRegistry(): array
    {
        $stored = self::readOption(self::OPTION_REGISTRY, self::REGISTRY_NOT_FOUND);

        if ($stored !== self::REGISTRY_NOT_FOUND && is_array($stored)) {
            $result = self::normalizeRegistry($stored);
            $normalized = $result['tokens'];
            $shouldPersistCss = false;

            if ($stored !== $normalized) {
                self::writeOption(self::OPTION_REGISTRY, $normalized);
                $shouldPersistCss = true;
            }

            $existingCss = self::readOption(self::OPTION_CSS, null);
            if (!is_string($existingCss) || trim($existingCss) === '') {
                $shouldPersistCss = true;
            }

            if ($shouldPersistCss) {
                self::persistCss($normalized);
            }

            return $normalized;
        }

        $legacyCss = self::readOption(self::OPTION_CSS, '');
        if (is_string($legacyCss) && trim($legacyCss) !== '') {
            $converted = self::convertCssToRegistry($legacyCss);
            $result = self::normalizeRegistry($converted);
            $fromCss = $result['tokens'];
            if ($fromCss !== []) {
                self::writeOption(self::OPTION_REGISTRY, $fromCss);
                self::persistCss($fromCss);
                return $fromCss;
            }
        }

        $defaultsResult = self::normalizeRegistry(self::getDefaultRegistry());
        $defaults = $defaultsResult['tokens'];
        self::writeOption(self::OPTION_REGISTRY, $defaults);
        self::persistCss($defaults);

        return $defaults;
    }

    /**
     * @return array<int, array{name: string, value: string, type: string, description: string, group: string, context: string, status: string, owner: int, version: string, changelog: string, linked_components: array<int, string>}>
     */
    public static function getDefaultRegistry(): array
    {
        return [
            [
                'name' => '--couleur-principale',
                'value' => '#4f46e5',
                'type' => 'color',
                'description' => 'Couleur principale utilisée pour les éléments interactifs.',
                'group' => 'Couleurs',
                'context' => self::DEFAULT_CONTEXT,
                'status' => self::STATUS_READY,
                'owner' => 0,
                'version' => '1.0.0',
                'changelog' => '',
                'linked_components' => [],
            ],
            [
                'name' => '--radius-moyen',
                'value' => '8px',
                'type' => 'text',
                'description' => 'Rayon par défaut appliqué aux composants principaux.',
                'group' => 'Général',
                'context' => self::DEFAULT_CONTEXT,
                'status' => self::STATUS_READY,
                'owner' => 0,
                'version' => '1.0.0',
                'changelog' => '',
                'linked_components' => [],
            ],
        ];
    }

    /**
     * @param array<int, array{name?: mixed, value?: mixed, type?: mixed, description?: mixed, group?: mixed, context?: mixed, status?: mixed, owner?: mixed, version?: mixed, changelog?: mixed, linked_components?: mixed}> $tokens
     * @return array{tokens: array<int, array{name: string, value: string, type: string, description: string, group: string, context: string, status: string, owner: int, version: string, changelog: string, linked_components: array<int, string>}>, duplicates: array<int, array{canonical: string, context: string, variants: array<int, string>, conflicts: array<int, array{name: string, value: string, context?: string}>}>}
     */
    public static function saveRegistry(array $tokens): array
    {
        $previous = self::getRegistry();
        $result = self::normalizeRegistry($tokens);
        $normalized = $result['tokens'];

        if ($result['duplicates'] !== []) {
            return $result;
        }

        $stored = self::readOption(self::OPTION_REGISTRY, null);
        if (!is_array($stored) || $stored !== $normalized) {
            self::writeOption(self::OPTION_REGISTRY, $normalized);
        }

        self::persistCss($normalized);

        self::recordRegistryChanges($previous, $normalized);

        return $result;
    }

    public static function updateTokenMetadata(string $name, string $context, array $metadata): ?array
    {
        $registry = self::getRegistry();
        $nameKey = strtolower($name);
        $contextKey = strtolower($context);
        $updated = false;

        foreach ($registry as $index => $token) {
            if (strtolower($token['name']) !== $nameKey || strtolower($token['context']) !== $contextKey) {
                continue;
            }

            $registry[$index] = self::applyMetadataPatch($token, $metadata);
            $updated = true;
            break;
        }

        if (!$updated) {
            return null;
        }

        $result = self::saveRegistry($registry);

        if ($result['duplicates'] !== []) {
            return null;
        }

        foreach ($result['tokens'] as $token) {
            if (strtolower($token['name']) === $nameKey && strtolower($token['context']) === $contextKey) {
                return $token;
            }
        }

        return null;
    }

    /**
     * @return array<string, array{label: string, input: string, placeholder?: string, rows?: int}>
     */
    public static function getSupportedTypes(): array
    {
        $types = self::SUPPORTED_TYPES;

        foreach ($types as $key => $type) {
            $types[$key]['label'] = __($type['label'], 'supersede-css-jlg');
            if (isset($type['placeholder'])) {
                $types[$key]['placeholder'] = __($type['placeholder'], 'supersede-css-jlg');
            }
            if (isset($type['help'])) {
                $types[$key]['help'] = __($type['help'], 'supersede-css-jlg');
            }
        }

        return $types;
    }

    /**
     * @param mixed $rawName
     */
    private static function normalizeTokenName($rawName): ?string
    {
        if (!is_string($rawName)) {
            return null;
        }

        $rawName = trim($rawName);
        if ($rawName === '') {
            return null;
        }

        if (strpos($rawName, '--') !== 0) {
            $rawName = '--' . ltrim($rawName, '-');
        }

        $normalizedName = '--' . preg_replace('/[^a-z0-9_-]+/i', '-', ltrim($rawName, '-'));

        if (!is_string($normalizedName) || $normalizedName === '--') {
            return null;
        }

        return $normalizedName;
    }

    /**
     * @param array{name?: mixed, value?: mixed, type?: mixed, description?: mixed, group?: mixed, context?: mixed, status?: mixed, owner?: mixed, version?: mixed, changelog?: mixed, linked_components?: mixed} $token
     * @return array{name: string, value: string, type: string, description: string, group: string, context: string, status: string, owner: int, version: string, changelog: string, linked_components: array<int, string>}|null
     */
    public static function normalizeToken(array $token): ?array
    {
        $normalizedName = self::normalizeTokenName($token['name'] ?? '');
        if ($normalizedName === null) {
            return null;
        }

        $rawType = isset($token['type']) ? (string) $token['type'] : 'text';
        $type = isset(self::SUPPORTED_TYPES[$rawType]) ? $rawType : 'text';
        $typeMeta = self::SUPPORTED_TYPES[$type];
        $inputKind = $typeMeta['input'];

        $valueRaw = isset($token['value']) ? (string) $token['value'] : '';
        $sanitizedValue = $inputKind === 'textarea'
            ? sanitize_textarea_field($valueRaw)
            : sanitize_text_field($valueRaw);

        if (trim($sanitizedValue) === '') {
            return null;
        }

        if ($inputKind === 'textarea') {
            $value = preg_replace("/\r\n?/", "\n", $sanitizedValue);
        } else {
            $value = trim($sanitizedValue);
        }

        $description = isset($token['description'])
            ? sanitize_textarea_field((string) $token['description'])
            : '';
        $group = isset($token['group']) ? sanitize_text_field((string) $token['group']) : '';
        if ($group === '') {
            $group = __('Général', 'supersede-css-jlg');
        }

        $context = self::sanitizeContext($token['context'] ?? self::DEFAULT_CONTEXT);
        $status = self::sanitizeStatus($token['status'] ?? self::STATUS_DRAFT);
        $owner = self::sanitizeOwner($token['owner'] ?? 0);
        $version = self::sanitizeVersion($token['version'] ?? '');
        $changelogRaw = isset($token['changelog']) ? (string) $token['changelog'] : '';
        $changelog = sanitize_textarea_field($changelogRaw);
        $linkedComponents = self::sanitizeLinkedComponentsList($token['linked_components'] ?? []);

        return [
            'name' => $normalizedName,
            'value' => $value,
            'type' => $type,
            'description' => $description,
            'group' => $group,
            'context' => $context,
            'status' => $status,
            'owner' => $owner,
            'version' => $version,
            'changelog' => $changelog,
            'linked_components' => $linkedComponents,
        ];
    }

    /**
     * @param array<int, array{name?: mixed, value?: mixed, type?: mixed, description?: mixed, group?: mixed, context?: mixed, status?: mixed, owner?: mixed, version?: mixed, changelog?: mixed, linked_components?: mixed}> $tokens
     * @return array{tokens: array<int, array{name: string, value: string, type: string, description: string, group: string, context: string, status: string, owner: int, version: string, changelog: string, linked_components: array<int, string>}>, duplicates: array<int, array{canonical: string, context: string, variants: array<int, string>, conflicts: array<int, array{name: string, value: string, context?: string}>}>}
     */
    public static function normalizeRegistry(array $tokens): array
    {
        $normalizedByName = [];
        $duplicateKeys = [];
        $variantsByKey = [];
        $conflictTokensByKey = [];

        foreach ($tokens as $token) {
            if (!is_array($token)) {
                continue;
            }

            $normalizedToken = self::normalizeToken($token);
            if ($normalizedToken === null) {
                continue;
            }

            $normalizedName = $normalizedToken['name'];
            $normalizedContext = $normalizedToken['context'];
            $contextKey = strtolower($normalizedContext);
            $normalizedKey = $contextKey . '|' . strtolower($normalizedName);

            if (!isset($variantsByKey[$normalizedKey])) {
                $variantsByKey[$normalizedKey] = [];
            }
            $variantsByKey[$normalizedKey][] = $normalizedName;

            if (!isset($conflictTokensByKey[$normalizedKey])) {
                $conflictTokensByKey[$normalizedKey] = [];
            }
            $conflictTokensByKey[$normalizedKey][] = $token;

            if (array_key_exists($normalizedKey, $normalizedByName)) {
                $duplicateKeys[$normalizedKey] = true;
                continue;
            }

            $normalizedByName[$normalizedKey] = $normalizedToken;
        }

        $duplicates = [];

        foreach (array_keys($duplicateKeys) as $duplicateKey) {
            $variants = $variantsByKey[$duplicateKey] ?? [];
            $variants = array_values(array_unique($variants));
            $canonicalToken = $normalizedByName[$duplicateKey] ?? null;
            $canonical = $canonicalToken['name'] ?? ($variants[0] ?? $duplicateKey);
            $duplicateContext = $canonicalToken['context'] ?? self::DEFAULT_CONTEXT;

            $conflictDetails = array_values(array_filter(array_map(
                static function (array $original): ?array {
                    if (!is_array($original)) {
                        return null;
                    }

                    return [
                        'name' => isset($original['name']) ? (string) $original['name'] : '',
                        'value' => isset($original['value']) ? (string) $original['value'] : '',
                        'context' => isset($original['context']) ? (string) $original['context'] : '',
                    ];
                },
                $conflictTokensByKey[$duplicateKey] ?? []
            )));

            $uniqueConflicts = [];
            foreach ($conflictDetails as $conflict) {
                if (!isset($conflict['name'])) {
                    continue;
                }
                $nameValue = trim((string) $conflict['name']);
                if ($nameValue === '') {
                    continue;
                }
                $nameKey = strtolower($nameValue);
                $valueKey = isset($conflict['value']) ? trim((string) $conflict['value']) : '';
                $contextValue = isset($conflict['context']) ? trim((string) $conflict['context']) : '';
                $hash = $nameKey . '|' . $valueKey . '|' . strtolower($contextValue);
                if (!isset($uniqueConflicts[$hash])) {
                    $uniqueConflicts[$hash] = [
                        'name' => $nameValue,
                        'value' => $valueKey,
                        'context' => $contextValue,
                    ];
                }
            }

            $duplicates[] = [
                'canonical' => $canonical,
                'context' => $duplicateContext,
                'variants' => $variants,
                'conflicts' => array_values($uniqueConflicts),
            ];
        }

        return [
            'tokens' => array_values($normalizedByName),
            'duplicates' => $duplicates,
        ];
    }

    /**
     * @param string $css
     * @return array{tokens: array<int, array{name: string, value: string, type: string, description: string, group: string, context: string, status: string, owner: int, version: string, changelog: string, linked_components: array<int, string>}>, duplicates: array<int, array{canonical: string, context: string, variants: array<int, string>, conflicts: array<int, array{name: string, value: string, context?: string}>}>}
     */
    public static function convertCssToRegistryDetailed(string $css): array
    {
        $tokensByName = [];
        $length = strlen($css);
        $cursor = 0;

        while ($cursor < $length) {
            $declarationStart = strpos($css, '--', $cursor);
            if ($declarationStart === false) {
                break;
            }

            $before = $declarationStart - 1;
            while ($before >= 0) {
                $character = $css[$before];

                if (ctype_space($character)) {
                    $before--;
                    continue;
                }

                if ($character === '/' && $before > 0 && $css[$before - 1] === '*') {
                    $before -= 2;

                    while ($before >= 0) {
                        if ($css[$before] === '/' && ($before + 1) < $length && $css[$before + 1] === '*') {
                            $before--;
                            break;
                        }

                        $before--;
                    }

                    continue;
                }

                if ($character === '/' && ($before + 1) < $length && $css[$before + 1] === '*') {
                    $before--;
                    continue;
                }

                if ($character === '*' && $before > 0 && $css[$before - 1] === '/') {
                    $before -= 2;
                    continue;
                }

                if ($character === '*' && ($before + 1) < $length && $css[$before + 1] === '/') {
                    $before--;
                    continue;
                }

                break;
            }

            if ($before >= 0) {
                $prefixCharacter = $css[$before];
                if ($prefixCharacter !== '{' && $prefixCharacter !== ';') {
                    $cursor = $declarationStart + 2;
                    continue;
                }
            }

            $name = '--';
            $index = $declarationStart + 2;

            while ($index < $length) {
                $character = $css[$index];
                if (preg_match('/[A-Za-z0-9_-]/', $character) !== 1) {
                    break;
                }

                $name .= $character;
                $index++;
            }

            if ($name === '--') {
                $cursor = $declarationStart + 2;
                continue;
            }

            while ($index < $length && ctype_space($css[$index])) {
                $index++;
            }

            if ($index >= $length || $css[$index] !== ':') {
                $cursor = $declarationStart + 2;
                continue;
            }

            $index++;

            while ($index < $length && ctype_space($css[$index])) {
                $index++;
            }

            $context = self::detectContextForDeclaration($css, $declarationStart);

            $value = '';
            $inSingleQuote = false;
            $inDoubleQuote = false;
            $parenthesesDepth = 0;
            $escaped = false;

            while ($index < $length) {
                $character = $css[$index];

                if ($escaped) {
                    $value .= $character;
                    $escaped = false;
                    $index++;
                    continue;
                }

                if ($character === '\\') {
                    $value .= $character;
                    $escaped = true;
                    $index++;
                    continue;
                }

                if ($character === "'" && !$inDoubleQuote) {
                    $inSingleQuote = !$inSingleQuote;
                    $value .= $character;
                    $index++;
                    continue;
                }

                if ($character === '"' && !$inSingleQuote) {
                    $inDoubleQuote = !$inDoubleQuote;
                    $value .= $character;
                    $index++;
                    continue;
                }

                if (!$inSingleQuote && !$inDoubleQuote) {
                    if ($character === '(') {
                        $parenthesesDepth++;
                        $value .= $character;
                        $index++;
                        continue;
                    }

                    if ($character === ')') {
                        if ($parenthesesDepth > 0) {
                            $parenthesesDepth--;
                        }
                        $value .= $character;
                        $index++;
                        continue;
                    }

                    if (($character === ';' || $character === '}') && $parenthesesDepth === 0) {
                        break;
                    }
                }

                $value .= $character;
                $index++;
            }

            $rawValue = trim($value);

            if ($rawValue !== '') {
                $token = [
                    'name' => $name,
                    'value' => $rawValue,
                    'type' => self::guessTypeFromValue($rawValue),
                    'description' => '',
                    'group' => 'Legacy',
                    'context' => $context,
                    'status' => self::STATUS_DRAFT,
                    'owner' => 0,
                    'version' => '',
                    'changelog' => '',
                    'linked_components' => [],
                ];

                $tokensByName[] = $token;
            }

            if ($index < $length && ($css[$index] === ';' || $css[$index] === '}')) {
                $index++;
            }

            $cursor = $index;
        }

        $result = self::normalizeRegistry(array_values($tokensByName));

        return $result;
    }

    /**
     * @param string $css
     * @return array<int, array{name: string, value: string, type: string, description: string, group: string, context: string, status: string, owner: int, version: string, changelog: string, linked_components: array<int, string>}>
     */
    public static function convertCssToRegistry(string $css): array
    {
        $result = self::convertCssToRegistryDetailed($css);

        return $result['tokens'];
    }

    /**
     * @param array<int, array<string, mixed>> $previous
     * @param array<int, array<string, mixed>> $current
     */
    private static function recordRegistryChanges(array $previous, array $current): void
    {
        $previousIndex = self::indexTokensByKey($previous);
        $currentIndex = self::indexTokensByKey($current);

        foreach ($currentIndex as $key => $token) {
            if (!isset($previousIndex[$key])) {
                EventRecorder::record('token.created', [
                    'entity_type' => 'token',
                    'entity_id' => $key,
                    'details' => [
                        'name' => $token['name'] ?? '',
                        'context' => $token['context'] ?? '',
                        'status' => $token['status'] ?? self::STATUS_DRAFT,
                    ],
                ]);

                continue;
            }

            $diff = self::computeTokenDiff($previousIndex[$key], $token);

            if ($diff === []) {
                continue;
            }

            EventRecorder::record('token.updated', [
                'entity_type' => 'token',
                'entity_id' => $key,
                'details' => [
                    'name' => $token['name'] ?? '',
                    'context' => $token['context'] ?? '',
                    'diff' => $diff,
                ],
            ]);

            if (isset($diff['status'])) {
                $newStatus = $diff['status']['new'] ?? null;
                if ($newStatus === self::STATUS_DEPRECATED) {
                    EventRecorder::record('token.deprecated', [
                        'entity_type' => 'token',
                        'entity_id' => $key,
                        'details' => [
                            'name' => $token['name'] ?? '',
                            'context' => $token['context'] ?? '',
                        ],
                    ]);
                }
            }
        }

        foreach ($previousIndex as $key => $token) {
            if (isset($currentIndex[$key])) {
                continue;
            }

            EventRecorder::record('token.deleted', [
                'entity_type' => 'token',
                'entity_id' => $key,
                'details' => [
                    'name' => $token['name'] ?? '',
                    'context' => $token['context'] ?? '',
                ],
            ]);
        }
    }

    /**
     * @param array<int, array<string, mixed>> $tokens
     * @return array<string, array<string, mixed>>
     */
    private static function indexTokensByKey(array $tokens): array
    {
        $indexed = [];

        foreach ($tokens as $token) {
            if (!isset($token['name'], $token['context'])) {
                continue;
            }

            $key = self::buildTokenKey((string) $token['name'], (string) $token['context']);
            $indexed[$key] = $token;
        }

        return $indexed;
    }

    private static function buildTokenKey(string $name, string $context): string
    {
        return strtolower($context) . '|' . strtolower($name);
    }

    /**
     * @param array<string, mixed> $token
     */
    private static function applyMetadataPatch(array $token, array $metadata): array
    {
        $patch = [];

        if (array_key_exists('status', $metadata)) {
            $patch['status'] = self::sanitizeStatus((string) $metadata['status']);
        }

        if (array_key_exists('owner', $metadata)) {
            $patch['owner'] = self::sanitizeOwner($metadata['owner']);
        }

        if (array_key_exists('version', $metadata)) {
            $patch['version'] = self::sanitizeVersion($metadata['version']);
        }

        if (array_key_exists('changelog', $metadata)) {
            $patch['changelog'] = sanitize_textarea_field((string) $metadata['changelog']);
        }

        if (array_key_exists('linked_components', $metadata)) {
            $patch['linked_components'] = self::sanitizeLinkedComponentsList($metadata['linked_components']);
        }

        return array_merge($token, $patch);
    }

    /**
     * @param array<string, mixed> $previous
     * @param array<string, mixed> $current
     * @return array<string, array{old: mixed, new: mixed}>
     */
    private static function computeTokenDiff(array $previous, array $current): array
    {
        $fields = ['value', 'type', 'description', 'group', 'context', 'status', 'owner', 'version', 'changelog'];
        $diff = [];

        foreach ($fields as $field) {
            $old = $previous[$field] ?? null;
            $new = $current[$field] ?? null;

            if ($field === 'owner') {
                $old = (int) $old;
                $new = (int) $new;
            }

            if ($old === $new) {
                continue;
            }

            $diff[$field] = [
                'old' => $old,
                'new' => $new,
            ];
        }

        $oldComponents = self::sanitizeLinkedComponentsList($previous['linked_components'] ?? []);
        $newComponents = self::sanitizeLinkedComponentsList($current['linked_components'] ?? []);

        if ($oldComponents !== $newComponents) {
            $diff['linked_components'] = [
                'old' => $oldComponents,
                'new' => $newComponents,
            ];
        }

        return $diff;
    }

    /**
     * @param array<int, array{name: string, value: string, type: string, description: string, group: string, context: string}> $tokens
     * @param array<int, array{name: string, value: string, type: string, description: string, group: string, context: string}> $existing
     * @return array<int, array{name: string, value: string, type: string, description: string, group: string, context: string}>
     */
    public static function mergeMetadata(array $tokens, array $existing): array
    {
        if ($tokens === []) {
            return [];
        }

        $existingByName = [];

        $existingResult = self::normalizeRegistry($existing);

        foreach ($existingResult['tokens'] as $existingToken) {
            $name = strtolower($existingToken['name']);
            $existingByName[$name] = [
                'type' => $existingToken['type'],
                'group' => $existingToken['group'],
                'description' => $existingToken['description'],
                'context' => $existingToken['context'],
            ];
        }

        $merged = [];

        foreach ($tokens as $token) {
            if (!is_array($token)) {
                continue;
            }

            $name = isset($token['name']) ? (string) $token['name'] : '';
            if ($name === '') {
                continue;
            }

            $key = strtolower($name);

            if (isset($existingByName[$key])) {
                $metadata = $existingByName[$key];
                $token['type'] = $metadata['type'];
                $token['group'] = $metadata['group'];
                $token['description'] = $metadata['description'];
                if (!isset($token['context']) || trim((string) $token['context']) === '') {
                    $token['context'] = $metadata['context'];
                }
            }

            $merged[] = $token;
        }

        return $merged;
    }

    /**
     * @param array<int, array{name: string, value: string, type: string, description: string, group: string, context: string}> $tokens
     */
    public static function tokensToCss(array $tokens): string
    {
        if ($tokens === []) {
            return self::DEFAULT_CONTEXT . " {\n}\n";
        }

        $grouped = [];
        $order = [];

        foreach ($tokens as $token) {
            $context = self::sanitizeContext($token['context'] ?? self::DEFAULT_CONTEXT);

            if (!isset($grouped[$context])) {
                $grouped[$context] = [];
                $order[] = $context;
            }

            $grouped[$context][] = $token;
        }

        $blocks = [];

        foreach ($order as $context) {
            $contextTokens = $grouped[$context];
            $lines = [];

            foreach ($contextTokens as $token) {
                $value = (string) $token['value'];
                $valueLines = preg_split("/\r\n|\n/", $value) ?: [$value];

                $firstLine = array_shift($valueLines);
                $line = sprintf('    %s: %s', $token['name'], $firstLine);

                if ($valueLines !== []) {
                    $indented = array_map(
                        static fn(string $lineValue): string => '        ' . $lineValue,
                        $valueLines
                    );
                    $line .= "\n" . implode("\n", $indented);
                }

                $lines[] = $line . ';';
            }

            $blocks[] = $context . " {\n" . implode("\n", $lines) . "\n}";
        }

        $css = implode("\n\n", $blocks);

        return CssSanitizer::sanitize($css);
    }

    /**
     * @param array<int, array{name: string, value: string, type: string, description: string, group: string, context: string}> $tokens
     */
    private static function persistCss(array $tokens): void
    {
        $css = self::tokensToCss($tokens);
        self::writeOption(self::OPTION_CSS, $css);

        if (function_exists('\ssc_invalidate_css_cache')) {
            \ssc_invalidate_css_cache();
        }
    }

    /**
     * @param mixed $default
     * @return mixed
     */
    private static function readOption(string $name, $default = false)
    {
        if (isset($GLOBALS['ssc_options_store']) && is_array($GLOBALS['ssc_options_store'])) {
            return $GLOBALS['ssc_options_store'][$name] ?? $default;
        }

        return get_option($name, $default);
    }

    /**
     * @param mixed $value
     * @global array<string, mixed> $ssc_options_store
     */
    private static function writeOption(string $name, $value): void
    {
        if (function_exists('update_option')) {
            update_option($name, $value, $autoload);
        }

        if (isset($GLOBALS['ssc_options_store']) && is_array($GLOBALS['ssc_options_store'])) {
            $GLOBALS['ssc_options_store'][$name] = $value;
        }
    }

    private static function detectContextForDeclaration(string $css, int $offset): string
    {
        $depth = 0;

        for ($index = $offset; $index >= 0; $index--) {
            $character = $css[$index];

            if ($character === '}') {
                $depth++;
                continue;
            }

            if ($character === '{') {
                if ($depth === 0) {
                    $selectorEnd = $index - 1;

                    while ($selectorEnd >= 0 && ctype_space($css[$selectorEnd])) {
                        $selectorEnd--;
                    }

                    if ($selectorEnd < 0) {
                        return self::DEFAULT_CONTEXT;
                    }

                    $selectorStart = $selectorEnd;
                    while ($selectorStart >= 0 && $css[$selectorStart] !== '}') {
                        $selectorStart--;
                    }

                    $selectorStart++;
                    if ($selectorStart < 0) {
                        $selectorStart = 0;
                    }

                    $selector = substr($css, $selectorStart, $selectorEnd - $selectorStart + 1);
                    if (!is_string($selector)) {
                        return self::DEFAULT_CONTEXT;
                    }

                    $selector = trim($selector);

                    if ($selector === '') {
                        return self::DEFAULT_CONTEXT;
                    }

                    return self::sanitizeContext($selector);
                }

                $depth--;
            }
        }

        return self::DEFAULT_CONTEXT;
    }

    private static function guessTypeFromValue(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return 'text';
        }

        if ($value[0] === '#' || str_starts_with(strtolower($value), 'rgb') || str_starts_with(strtolower($value), 'hsl')) {
            return 'color';
        }

        if (preg_match('/^-?\d+(?:\.\d+)?$/', $value)) {
            return 'number';
        }

        return 'text';
    }
}
