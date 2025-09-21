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
            if ($stored !== $normalized) {
                update_option(self::OPTION_REGISTRY, $normalized, false);
            }
            self::persistCss($normalized);
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
        update_option(self::OPTION_REGISTRY, $normalized, false);
        self::persistCss($normalized);

        return $normalized;
    }

    /**
     * @return array<string, array{label: string, input: string}>
     */
    public static function getSupportedTypes(): array
    {
        return self::SUPPORTED_TYPES;
    }

    /**
     * @param array<int, array{name?: mixed, value?: mixed, type?: mixed, description?: mixed, group?: mixed}> $tokens
     * @return array<int, array{name: string, value: string, type: string, description: string, group: string}>
     */
    public static function normalizeRegistry(array $tokens): array
    {
        $normalized = [];

        foreach ($tokens as $token) {
            if (!is_array($token)) {
                continue;
            }

            $name = isset($token['name']) ? (string) $token['name'] : '';
            $name = trim($name);
            if ($name === '') {
                continue;
            }

            if (strpos($name, '--') !== 0) {
                $name = '--' . ltrim($name, '-');
            }

            $name = '--' . preg_replace('/[^a-z0-9\-]+/i', '-', ltrim($name, '-'));
            $name = strtolower($name);

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

            $normalized[] = [
                'name' => $name,
                'value' => $value,
                'type' => $type,
                'description' => $description,
                'group' => $group,
            ];
        }

        return $normalized;
    }

    /**
     * @param string $css
     * @return array<int, array{name: string, value: string, type: string, description: string, group: string}>
     */
    public static function convertCssToRegistry(string $css): array
    {
        $tokens = [];
        $pattern = '/--([\w\-]+)\s*:\s*([^;]+);/';

        if (preg_match_all($pattern, $css, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $rawName = isset($match[1]) ? '--' . $match[1] : '';
                $rawValue = isset($match[2]) ? trim($match[2]) : '';

                if ($rawName === '' || $rawValue === '') {
                    continue;
                }

                $type = self::guessTypeFromValue($rawValue);

                $tokens[] = [
                    'name' => $rawName,
                    'value' => $rawValue,
                    'type' => $type,
                    'description' => '',
                    'group' => 'Legacy',
                ];
            }
        }

        return self::normalizeRegistry($tokens);
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
