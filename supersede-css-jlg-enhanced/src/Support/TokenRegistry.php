<?php declare(strict_types=1);

namespace SSC\Support;

if (!defined('ABSPATH')) { exit; }

final class TokenRegistry
{
    private const OPTION_REGISTRY = 'ssc_tokens_registry';
    private const OPTION_CSS = 'ssc_tokens_css';
    private const REGISTRY_NOT_FOUND = '__ssc_tokens_registry_missing__';

    /**
     * @var array<string, array{label: string, input: string}>
     */
    private const SUPPORTED_TYPES = [
        'color' => [
            'label' => 'Couleur',
            'input' => 'color',
        ],
        'text' => [
            'label' => 'Texte',
            'input' => 'text',
        ],
        'number' => [
            'label' => 'Nombre',
            'input' => 'number',
        ],
    ];

    /**
     * @return array<int, array{name: string, value: string, type: string, description: string, group: string}>
     */
    public static function getRegistry(): array
    {
        $stored = get_option(self::OPTION_REGISTRY, self::REGISTRY_NOT_FOUND);

        if ($stored !== self::REGISTRY_NOT_FOUND && is_array($stored)) {
            $normalized = self::normalizeRegistry($stored);
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
            $fromCss = self::normalizeRegistry(self::convertCssToRegistry($legacyCss));
            if ($fromCss !== []) {
                update_option(self::OPTION_REGISTRY, $fromCss, false);
                self::persistCss($fromCss);
                return $fromCss;
            }
        }

        $defaults = self::normalizeRegistry(self::getDefaultRegistry());
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
     * @return array<int, array{name: string, value: string, type: string, description: string, group: string}>
     */
    public static function saveRegistry(array $tokens): array
    {
        $normalized = self::normalizeRegistry($tokens);

        $stored = get_option(self::OPTION_REGISTRY, null);
        if (!is_array($stored) || $stored !== $normalized) {
            update_option(self::OPTION_REGISTRY, $normalized, false);
        }

        self::persistCss($normalized);

        if (function_exists('\ssc_invalidate_css_cache')) {
            \ssc_invalidate_css_cache();
        }

        return $normalized;
    }

    /**
     * @return array<string, array{label: string, input: string}>
     */
    public static function getSupportedTypes(): array
    {
        $types = self::SUPPORTED_TYPES;

        foreach ($types as $key => $type) {
            $types[$key]['label'] = __($type['label'], 'supersede-css-jlg');
        }

        return $types;
    }

    /**
     * @param array<int, array{name?: mixed, value?: mixed, type?: mixed, description?: mixed, group?: mixed}> $tokens
     * @return array<int, array{name: string, value: string, type: string, description: string, group: string}>
     */
    public static function normalizeRegistry(array $tokens): array
    {
        $normalizedByName = [];

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

            $valueRaw = isset($token['value']) ? (string) $token['value'] : '';
            $value = trim(sanitize_textarea_field($valueRaw));
            if ($value === '') {
                continue;
            }

            $type = isset($token['type']) ? (string) $token['type'] : 'text';
            if (!isset(self::SUPPORTED_TYPES[$type])) {
                $type = 'text';
            }

            $description = isset($token['description']) ? sanitize_textarea_field((string) $token['description']) : '';
            $group = isset($token['group']) ? sanitize_text_field((string) $token['group']) : '';
            if ($group === '') {
                $group = 'Général';
            }

            $normalizedToken = [
                'name' => $normalizedName,
                'value' => $value,
                'type' => $type,
                'description' => $description,
                'group' => $group,
            ];

            if (array_key_exists($normalizedKey, $normalizedByName)) {
                unset($normalizedByName[$normalizedKey]);
            }

            $normalizedByName[$normalizedKey] = $normalizedToken;
        }

        return array_values($normalizedByName);
    }

    /**
     * @param string $css
     * @return array<int, array{name: string, value: string, type: string, description: string, group: string}>
     */
    public static function convertCssToRegistry(string $css): array
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
            while ($before >= 0 && ctype_space($css[$before])) {
                $before--;
            }

            if ($before >= 0 && !in_array($css[$before], ['{', ';'], true)) {
                $cursor = $declarationStart + 2;
                continue;
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

                if (array_key_exists($name, $tokensByName)) {
                    unset($tokensByName[$name]);
                }

                $tokensByName[$name] = $token;
            }

            if ($index < $length && ($css[$index] === ';' || $css[$index] === '}')) {
                $index++;
            }

            $cursor = $index;
        }

        return self::normalizeRegistry(array_values($tokensByName));
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

        foreach (self::normalizeRegistry($existing) as $existingToken) {
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
            $lines[] = sprintf('    %s: %s;', $token['name'], $token['value']);
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
