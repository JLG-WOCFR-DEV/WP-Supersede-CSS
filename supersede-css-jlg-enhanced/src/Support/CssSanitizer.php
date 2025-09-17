<?php declare(strict_types=1);

namespace SSC\Support;

if (!defined('ABSPATH')) { exit; }

final class CssSanitizer
{
    public static function sanitize(string $css): string
    {
        $css = trim($css);
        if ($css === '') {
            return '';
        }

        $css = \wp_kses($css, []);
        $css = self::sanitizeImports($css);
        $css = self::sanitizeUrls($css);

        $css = (string) \preg_replace_callback('/\{([^{}]*)\}/m', static function(array $matches): string {
            $sanitized = self::sanitizeDeclarations($matches[1]);
            return $sanitized === '' ? '' : '{' . $sanitized . '}';
        }, $css);

        $css = (string) \preg_replace('/[^{}]+\{\s*\}/m', '', $css);

        return trim($css);
    }

    private static function sanitizeDeclarations(string $declarations): string
    {
        $parts = \preg_split('/;(?![^()]*\))/m', $declarations);
        if (empty($parts)) {
            return '';
        }

        $sanitizedParts = [];
        foreach ($parts as $part) {
            $part = trim($part);
            if ($part === '') {
                continue;
            }

            $colonPosition = strpos($part, ':');
            if ($colonPosition === false) {
                continue;
            }

            $property = trim(substr($part, 0, $colonPosition));
            $value = trim(substr($part, $colonPosition + 1));
            if ($property === '' || $value === '') {
                continue;
            }

            if (!self::isSafePropertyName($property)) {
                continue;
            }

            $declaration = $property . ':' . $value . ';';
            $sanitized = trim(\safecss_filter_attr($declaration));
            if ($sanitized === '') {
                if (strpos($property, '--') === 0) {
                    $customValue = self::sanitizeCustomPropertyValue($value);
                    if ($customValue === '') {
                        continue;
                    }
                    $sanitizedParts[] = $property . ':' . $customValue;
                }
                continue;
            }

            $sanitized = rtrim($sanitized, ';');
            $sanitized = self::sanitizeUrls($sanitized);
            $sanitizedParts[] = $sanitized;
        }

        return implode('; ', $sanitizedParts);
    }

    private static function isSafePropertyName(string $property): bool
    {
        return (bool) \preg_match('/^(--[A-Za-z0-9_-]+|[A-Za-z-][A-Za-z0-9_-]*)$/', $property);
    }

    private static function sanitizeCustomPropertyValue(string $value): string
    {
        $value = \wp_kses($value, []);
        $value = self::sanitizeUrls($value);
        $value = (string) \preg_replace('/expression\s*\([^)]*\)/i', '', $value);
        $value = (string) \preg_replace('/behavior\s*:[^;]+;?/i', '', $value);

        return trim($value);
    }

    private static function sanitizeImports(string $css): string
    {
        return (string) \preg_replace_callback('/@import\s+([^;]+);?/i', static function(array $matches): string {
            $body = trim($matches[1]);
            if ($body === '') {
                return '';
            }

            $quote = '';
            $rawUrl = '';
            $qualifiers = '';

            if (stripos($body, 'url(') === 0) {
                if (!\preg_match('/^url\((?P<content>.*?)\)(?P<qualifiers>.*)$/i', $body, $parts)) {
                    return '';
                }

                $urlContent = trim($parts['content']);
                $qualifiers = $parts['qualifiers'] ?? '';

                if ($urlContent !== '' && (substr($urlContent, 0, 1) === '"' || substr($urlContent, 0, 1) === "'")) {
                    $quote = substr($urlContent, 0, 1);
                    $urlContent = trim($urlContent, "\"'");
                }

                $rawUrl = $urlContent;
            } else {
                $parts = \preg_split('/\s+/', $body, 2);
                if (empty($parts)) {
                    return '';
                }

                $urlPart = trim($parts[0]);
                if ($urlPart === '') {
                    return '';
                }

                if (substr($urlPart, 0, 1) === '"' || substr($urlPart, 0, 1) === "'") {
                    $quote = substr($urlPart, 0, 1);
                    $urlPart = trim($urlPart, "\"'");
                }

                $rawUrl = $urlPart;
                $qualifiers = $parts[1] ?? '';
            }

            $rawUrl = trim($rawUrl);
            if ($rawUrl === '') {
                return '';
            }

            $sanitizedUrl = trim(\wp_kses_bad_protocol($rawUrl, \wp_allowed_protocols()));
            if ($sanitizedUrl === '' || \preg_match('/^(?:javascript|vbscript)/i', $sanitizedUrl)) {
                return '';
            }

            $url = $quote !== '' ? $quote . $sanitizedUrl . $quote : $sanitizedUrl;

            $qualifiers = is_string($qualifiers) ? trim($qualifiers) : '';
            if ($qualifiers !== '') {
                $qualifiers = \wp_kses($qualifiers, []);
                $qualifiers = (string) \preg_replace('/[{};]/', '', $qualifiers);
                $qualifiers = trim($qualifiers);
            }

            $result = '@import url(' . $url . ')';
            if ($qualifiers !== '') {
                $result .= ' ' . $qualifiers;
            }

            return $result . ';';
        }, $css);
    }

    private static function sanitizeUrls(string $css): string
    {
        return (string) \preg_replace_callback('/url\((.*?)\)/i', static function(array $matches): string {
            $raw = trim($matches[1]);
            $quote = '';
            if ($raw !== '' && (substr($raw, 0, 1) === '"' || substr($raw, 0, 1) === "'")) {
                $quote = substr($raw, 0, 1);
                $raw = trim($raw, "\"'");
            }

            if (self::isSafeDataUri($raw)) {
                $sanitized = $raw;
            } else {
                $sanitized = trim(\wp_kses_bad_protocol($raw, \wp_allowed_protocols()));
                if ($sanitized === '' || \preg_match('/^(?:javascript|vbscript)/i', $sanitized)) {
                    return '';
                }
            }

            if ($quote === '') {
                $quote = '"';
            }

            return 'url(' . $quote . $sanitized . $quote . ')';
        }, $css);
    }

    private static function isSafeDataUri(string $value): bool
    {
        if ($value === '') {
            return false;
        }

        if (!\preg_match('#^data:([a-z0-9.+-]+/[a-z0-9.+-]+)#i', $value, $matches)) {
            return false;
        }

        $mime = strtolower($matches[1]);
        if (str_starts_with($mime, 'image/')) {
            return true;
        }

        if (str_starts_with($mime, 'font/')) {
            return true;
        }

        if (!str_starts_with($mime, 'application/')) {
            return false;
        }

        $allowed = [
            'application/font',
            'application/font-ttf',
            'application/font-woff',
            'application/font-woff2',
            'application/font-sfnt',
            'application/x-font-ttf',
            'application/x-font-truetype',
            'application/x-font-opentype',
            'application/x-font-woff',
            'application/x-font-woff2',
            'application/vnd.ms-fontobject',
        ];

        return in_array($mime, $allowed, true);
    }

    public static function sanitizePresetCollection(array $presets): array
    {
        $sanitized = [];
        foreach ($presets as $key => $preset) {
            if (!is_array($preset)) {
                continue;
            }

            $id = is_string($key) ? \sanitize_key($key) : 'preset_' . \absint((int) $key);
            if ($id === '') {
                $id = 'preset_' . md5((string) $key);
            }

            $name = isset($preset['name']) ? \sanitize_text_field((string) $preset['name']) : '';
            $scope = isset($preset['scope']) ? self::sanitizeSelector((string) $preset['scope']) : '';

            $props = [];
            if (!empty($preset['props']) && is_array($preset['props'])) {
                foreach ($preset['props'] as $prop => $value) {
                    $prop = (string) $prop;
                    $value = (string) $value;

                    $clean = self::sanitizeDeclarationPair($prop, $value);
                    if ($clean === null) {
                        continue;
                    }

                    list($cleanProp, $cleanValue) = $clean;
                    $props[$cleanProp] = $cleanValue;
                }
            }

            $sanitized[$id] = [
                'name' => $name,
                'scope' => $scope,
                'props' => $props,
            ];
        }

        return $sanitized;
    }

    private static function sanitizeSelector(string $selector): string
    {
        $selector = \wp_kses($selector, []);
        $selector = (string) \preg_replace('/[\x00-\x1F\x7F]+/u', ' ', $selector);

        return trim($selector);
    }

    private static function sanitizeDeclarationPair(string $property, string $value): ?array
    {
        if (!self::isSafePropertyName($property)) {
            return null;
        }

        $sanitized = trim(\safecss_filter_attr($property . ':' . $value . ';'));
        if ($sanitized === '') {
            if (strpos($property, '--') === 0) {
                $customValue = self::sanitizeCustomPropertyValue($value);
                if ($customValue === '') {
                    return null;
                }

                return [$property, $customValue];
            }

            return null;
        }

        $sanitized = rtrim($sanitized, ';');
        $parts = explode(':', $sanitized, 2);
        if (count($parts) !== 2) {
            return null;
        }

        $propName = trim($parts[0]);
        $propValue = trim($parts[1]);
        if ($propName === '' || $propValue === '') {
            return null;
        }

        return [$propName, $propValue];
    }

    public static function sanitizeAvatarGlowPresets(array $presets): array
    {
        $sanitized = [];
        foreach ($presets as $key => $preset) {
            if (!is_array($preset)) {
                continue;
            }

            $id = is_string($key) ? \sanitize_key($key) : 'preset_' . \absint((int) $key);
            if ($id === '') {
                $id = 'preset_' . md5((string) $key);
            }

            $name = isset($preset['name']) ? \sanitize_text_field((string) $preset['name']) : '';
            $className = isset($preset['className']) ? self::sanitizeSelector((string) $preset['className']) : '';
            $color1 = self::sanitizeColor($preset['color1'] ?? '');
            $color2 = self::sanitizeColor($preset['color2'] ?? '');
            $speed = isset($preset['speed']) ? max(0, (float) $preset['speed']) : 0.0;
            $thickness = isset($preset['thickness']) ? max(0, (float) $preset['thickness']) : 0.0;
            $avatarUrl = isset($preset['avatarUrl']) ? self::sanitizeUrl((string) $preset['avatarUrl']) : '';

            $sanitized[$id] = [
                'name' => $name,
                'className' => $className,
                'color1' => $color1,
                'color2' => $color2,
                'speed' => $speed,
                'thickness' => $thickness,
                'avatarUrl' => $avatarUrl,
            ];
        }

        return $sanitized;
    }

    private static function sanitizeColor($color): string
    {
        $color = is_string($color) ? trim($color) : '';
        if ($color === '') {
            return '';
        }

        $hex = \sanitize_hex_color($color);
        if ($hex !== null) {
            return $hex;
        }

        return \sanitize_text_field($color);
    }

    private static function sanitizeUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }

        $url = \wp_kses_bad_protocol($url, \wp_allowed_protocols());

        return \esc_url_raw($url);
    }
}
