<?php declare(strict_types=1);

namespace SSC\Support;

if (!defined('ABSPATH')) { exit; }

final class TokenRegistry
{
    private const OPTION_REGISTRY = 'ssc_tokens_registry';
    private const OPTION_CSS = 'ssc_tokens_css';
    private const REGISTRY_NOT_FOUND = '__ssc_tokens_registry_missing__';

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
     * @return array<int, array{name: string, value: string, type: string, description: string, group: string}>
     */
    public static function getRegistry(): array
    {
        $stored = get_option(self::OPTION_REGISTRY, self::REGISTRY_NOT_FOUND);

        if ($stored !== self::REGISTRY_NOT_FOUND && is_array($stored)) {
            $result = self::normalizeRegistry($stored);
            $normalized = $result['tokens'];
            $shouldPersistCss = false;

            if ($stored !== $normalized) {
                update_option(self::OPTION_REGISTRY, $normalized, false);
                $shouldPersistCss = true;
            }

            $existingCss = get_option(self::OPTION_CSS, null);
            if (!is_string($existingCss) || trim($existingCss) === '') {
                $shouldPersistCss = true;
            }

            if ($shouldPersistCss) {
                self::persistCss($normalized);
            }

            return $normalized;
        }

        $legacyCss = get_option(self::OPTION_CSS, '');
        if (is_string($legacyCss) && trim($legacyCss) !== '') {
            $converted = self::convertCssToRegistry($legacyCss);
            $result = self::normalizeRegistry($converted);
            $fromCss = $result['tokens'];
            if ($fromCss !== []) {
                update_option(self::OPTION_REGISTRY, $fromCss, false);
                self::persistCss($fromCss);
                return $fromCss;
            }
        }

        $defaultsResult = self::normalizeRegistry(self::getDefaultRegistry());
        $defaults = $defaultsResult['tokens'];
        update_option(self::OPTION_REGISTRY, $defaults, false);
        self::persistCss($defaults);

        return $defaults;
    }

    /**
     * @return array<int, array{name: string, value: string, type: string, description: string, group: string}>
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
            ],
            [
                'name' => '--radius-moyen',
                'value' => '8px',
                'type' => 'text',
                'description' => 'Rayon par défaut appliqué aux composants principaux.',
                'group' => 'Général',
            ],
        ];
    }

    /**
     * @param array<int, array{name?: mixed, value?: mixed, type?: mixed, description?: mixed, group?: mixed}> $tokens
     * @return array{tokens: array<int, array{name: string, value: string, type: string, description: string, group: string}>, duplicates: array<int, array{canonical: string, variants: array<int, string>, conflicts: array<int, array{name: string, value: string}>}>}
     */
    public static function saveRegistry(array $tokens): array
    {
        $result = self::normalizeRegistry($tokens);
        $normalized = $result['tokens'];

        if ($result['duplicates'] !== []) {
            return $result;
        }

        $stored = get_option(self::OPTION_REGISTRY, null);
        if (!is_array($stored) || $stored !== $normalized) {
            update_option(self::OPTION_REGISTRY, $normalized, false);
        }

        self::persistCss($normalized);

        return $result;
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
     * @param array{name?: mixed, value?: mixed, type?: mixed, description?: mixed, group?: mixed} $token
     * @return array{name: string, value: string, type: string, description: string, group: string}|null
     */
    public static function normalizeToken(array $token): ?array
    {
        $rawName = isset($token['name']) ? (string) $token['name'] : '';
        $rawName = trim($rawName);
        if ($rawName === '') {
            return null;
        }

        if (strpos($rawName, '--') !== 0) {
            $rawName = '--' . ltrim($rawName, '-');
        }

        $normalizedName = '--' . preg_replace('/[^a-z0-9_-]+/i', '-', ltrim($rawName, '-'));
        if ($normalizedName === '--') {
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
            $group = 'Général';
        }

        return [
            'name' => $normalizedName,
            'value' => $value,
            'type' => $type,
            'description' => $description,
            'group' => $group,
        ];
    }

    /**
     * @param array<int, array{name?: mixed, value?: mixed, type?: mixed, description?: mixed, group?: mixed}> $tokens
     * @return array{tokens: array<int, array{name: string, value: string, type: string, description: string, group: string}>, duplicates: array<int, array{canonical: string, variants: array<int, string>, conflicts: array<int, array{name: string, value: string}>}>}
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

            $rawName = isset($token['name']) ? (string) $token['name'] : '';
            $rawName = trim($rawName);
            if ($rawName === '') {
                continue;
            }

            if (strpos($rawName, '--') !== 0) {
                $rawName = '--' . ltrim($rawName, '-');
            }

            $normalizedName = '--' . preg_replace('/[^a-z0-9_-]+/i', '-', ltrim($rawName, '-'));
            if ($normalizedName === '--') {
                continue;
            }

            $normalizedKey = strtolower($normalizedName);
            if (!isset($variantsByKey[$normalizedKey])) {
                $variantsByKey[$normalizedKey] = [];
            }
            $variantsByKey[$normalizedKey][] = $normalizedName;
            if (!isset($conflictTokensByKey[$normalizedKey])) {
                $conflictTokensByKey[$normalizedKey] = [];
            }
            $conflictTokensByKey[$normalizedKey][] = $token;

            $normalizedToken = self::normalizeToken($token);
            if ($normalizedToken === null) {
                continue;
            }

            $normalizedToken['name'] = $normalizedName;

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
            $canonical = $normalizedByName[$duplicateKey]['name'] ?? ($variants[0] ?? $duplicateKey);

            $conflictDetails = array_values(array_filter(array_map(
                static function (array $original): ?array {
                    if (!is_array($original)) {
                        return null;
                    }

                    return [
                        'name' => isset($original['name']) ? (string) $original['name'] : '',
                        'value' => isset($original['value']) ? (string) $original['value'] : '',
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
                $hash = $nameKey . '|' . $valueKey;
                if (!isset($uniqueConflicts[$hash])) {
                    $uniqueConflicts[$hash] = [
                        'name' => $nameValue,
                        'value' => $valueKey,
                    ];
                }
            }

            $duplicates[] = [
                'canonical' => $canonical,
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
     * @return array{tokens: array<int, array{name: string, value: string, type: string, description: string, group: string}>, duplicates: array<int, array{canonical: string, variants: array<int, string>, conflicts: array<int, array{name: string, value: string}>}>}
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
     * @return array<int, array{name: string, value: string, type: string, description: string, group: string}>
     */
    public static function convertCssToRegistry(string $css): array
    {
        $result = self::convertCssToRegistryDetailed($css);

        return $result['tokens'];
    }

    /**
     * @param array<int, array{name: string, value: string, type: string, description: string, group: string}> $tokens
     * @param array<int, array{name: string, value: string, type: string, description: string, group: string}> $existing
     * @return array<int, array{name: string, value: string, type: string, description: string, group: string}>
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
            }

            $merged[] = $token;
        }

        return $merged;
    }

    /**
     * @param array<int, array{name: string, value: string, type: string, description: string, group: string}> $tokens
     */
    public static function tokensToCss(array $tokens): string
    {
        if ($tokens === []) {
            return ':root {\n}\n';
        }

        $lines = [];

        foreach ($tokens as $token) {
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

        $css = ":root {\n" . implode("\n", $lines) . "\n}";

        return CssSanitizer::sanitize($css);
    }

    /**
     * @param array<int, array{name: string, value: string, type: string, description: string, group: string}> $tokens
     */
    private static function persistCss(array $tokens): void
    {
        $css = self::tokensToCss($tokens);
        update_option(self::OPTION_CSS, $css, false);

        if (function_exists('\ssc_invalidate_css_cache')) {
            \ssc_invalidate_css_cache();
        }
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
