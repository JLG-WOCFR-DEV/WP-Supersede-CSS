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

        $css = self::stripHtmlTags($css);
        $css = self::sanitizeImports($css);
        $css = self::sanitizeUrls($css);

        $length = strlen($css);
        if ($length > 0) {
            $result = '';
            $cursor = 0;
            $index = 0;
            $inSingleQuote = false;
            $inDoubleQuote = false;
            $inComment = false;
            $escaped = false;

            while ($index < $length) {
                $character = $css[$index];

                if ($inComment) {
                    if ($character === '*' && $index + 1 < $length && $css[$index + 1] === '/') {
                        $inComment = false;
                        $index += 2;
                        continue;
                    }

                    $index++;
                    continue;
                }

                if (!$inSingleQuote && !$inDoubleQuote && $character === '/' && $index + 1 < $length && $css[$index + 1] === '*') {
                    $inComment = true;
                    $index += 2;
                    continue;
                }

                if ($escaped) {
                    $escaped = false;
                    $index++;
                    continue;
                }

                if ($character === '\\') {
                    $escaped = true;
                    $index++;
                    continue;
                }

                if ($character === "'" && !$inDoubleQuote) {
                    $inSingleQuote = !$inSingleQuote;
                    $index++;
                    continue;
                }

                if ($character === '"' && !$inSingleQuote) {
                    $inDoubleQuote = !$inDoubleQuote;
                    $index++;
                    continue;
                }

                if (!$inSingleQuote && !$inDoubleQuote && $character === '{') {
                    $prefix = substr($css, $cursor, $index - $cursor);

                    $nextCursor = $cursor;
                    $sanitizedBlock = self::sanitizeStructuralBlock($css, $index, $prefix, $nextCursor);

                    if ($sanitizedBlock === null) {
                        $result .= substr($css, $cursor);
                        $cursor = $length;
                        $index = $length;
                        break;
                    }

                    $result .= $sanitizedBlock;
                    $cursor = $nextCursor;
                    $index = $nextCursor;
                    $inSingleQuote = false;
                    $inDoubleQuote = false;
                    $escaped = false;
                    continue;
                }

                $index++;
            }

            if ($cursor < $length) {
                $result .= substr($css, $cursor);
            }

            $css = $result;
        }

        $css = (string) \preg_replace('/[^{}]+(?<!["\'])\{\s*\}/m', '', $css);

        return trim($css);
    }

    private static function sanitizeStructuralBlock(string $css, int $openingBracePosition, string $prefix, int &$nextCursor): ?string
    {
        $length = strlen($css);
        $bodyStart = $openingBracePosition + 1;
        $depth = 1;
        $index = $bodyStart;
        $inSingleQuote = false;
        $inDoubleQuote = false;
        $inComment = false;
        $escaped = false;

        while ($index < $length) {
            $character = $css[$index];

            if ($inComment) {
                if ($character === '*' && $index + 1 < $length && $css[$index + 1] === '/') {
                    $inComment = false;
                    $index += 2;
                    continue;
                }

                $index++;
                continue;
            }

            if (!$inSingleQuote && !$inDoubleQuote && $character === '/' && $index + 1 < $length && $css[$index + 1] === '*') {
                $inComment = true;
                $index += 2;
                continue;
            }

            if ($escaped) {
                $escaped = false;
                $index++;
                continue;
            }

            if ($character === '\\') {
                $escaped = true;
                $index++;
                continue;
            }

            if ($character === "'" && !$inDoubleQuote) {
                $inSingleQuote = !$inSingleQuote;
                $index++;
                continue;
            }

            if ($character === '"' && !$inSingleQuote) {
                $inDoubleQuote = !$inDoubleQuote;
                $index++;
                continue;
            }

            if (!$inSingleQuote && !$inDoubleQuote) {
                if ($character === '{') {
                    $depth++;
                    $index++;
                    continue;
                }

                if ($character === '}') {
                    $depth--;
                    if ($depth === 0) {
                        $body = substr($css, $bodyStart, $index - $bodyStart);

                        $beforeProperty = $prefix;
                        $propertyPrefix = '';
                        $isPropertyContext = false;

                        if ($prefix !== '' && \preg_match('/@property\s+[^{}]*$/', $prefix, $propertyMatch, PREG_OFFSET_CAPTURE)) {
                            $isPropertyContext = true;
                            $propertyPrefix = $propertyMatch[0][0];
                            $beforeProperty = substr($prefix, 0, $propertyMatch[0][1]);
                        }

                        $nextCursor = $index + 1;

                        if ($isPropertyContext) {
                            $sanitized = self::sanitizeDeclarations($body, true);

                            if ($sanitized === '') {
                                return $beforeProperty;
                            }

                            return $beforeProperty . $propertyPrefix . '{' . $sanitized . '}';
                        }

                        $hasNestedBlocks = self::containsNestedBlocks($body);
                        $atRuleName = self::detectAtRuleName($prefix);
                        $requiresNestedTraversal = $hasNestedBlocks || ($atRuleName !== null && self::isNestedAtRule($atRuleName));

                        if ($requiresNestedTraversal) {
                            $sanitized = self::sanitizeNestedRuleBody($body);
                        } else {
                            $sanitized = self::sanitizeDeclarations($body, false);
                        }

                        if ($sanitized === '') {
                            return $prefix . '{}';
                        }

                        return $prefix . '{' . $sanitized . '}';
                    }

                    $index++;
                    continue;
                }
            }

            $index++;
        }

        return null;
    }

    private static function containsNestedBlocks(string $body): bool
    {
        $length = strlen($body);
        if ($length === 0) {
            return false;
        }

        $inSingleQuote = false;
        $inDoubleQuote = false;
        $inComment = false;
        $escaped = false;

        for ($i = 0; $i < $length; $i++) {
            $character = $body[$i];

            if ($inComment) {
                if ($character === '*' && $i + 1 < $length && $body[$i + 1] === '/') {
                    $inComment = false;
                    $i++;
                }

                continue;
            }

            if ($escaped) {
                $escaped = false;
                continue;
            }

            if ($character === '\\') {
                $escaped = true;
                continue;
            }

            if ($character === '/' && $i + 1 < $length && $body[$i + 1] === '*') {
                $inComment = true;
                $i++;
                continue;
            }

            if ($character === "'" && !$inDoubleQuote) {
                $inSingleQuote = !$inSingleQuote;
                continue;
            }

            if ($character === '"' && !$inSingleQuote) {
                $inDoubleQuote = !$inDoubleQuote;
                continue;
            }

            if (!$inSingleQuote && !$inDoubleQuote && $character === '{') {
                return true;
            }
        }

        return false;
    }

    private static function detectAtRuleName(string $prefix): ?string
    {
        if ($prefix === '') {
            return null;
        }

        if (!\preg_match('/@([a-z-]+)\b[^@]*$/i', $prefix, $matches)) {
            return null;
        }

        return strtolower($matches[1]);
    }

    private static function isNestedAtRule(string $atRule): bool
    {
        if ($atRule === 'media' || $atRule === 'supports') {
            return true;
        }

        if ($atRule === 'keyframes' || str_ends_with($atRule, 'keyframes')) {
            return true;
        }

        return false;
    }

    private static function sanitizeNestedRuleBody(string $body): string
    {
        $sanitized = self::sanitize($body);

        return $sanitized;
    }

    private static function sanitizeDeclarations(string $declarations, bool $isPropertyContext = false): string
    {
        $parts = self::splitDeclarations($declarations);
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

            if ($isPropertyContext && self::isAllowedPropertyDefinitionProperty($property)) {
                $normalized = self::sanitizePropertyDefinitionValue($property, $value);
                if ($normalized === '') {
                    continue;
                }

                $sanitizedParts[] = $property . ':' . $normalized;
                continue;
            }

            if (strpos($property, '--') === 0) {
                $customValue = self::sanitizeCustomPropertyValue($value);
                if ($customValue === '') {
                    continue;
                }

                $sanitizedParts[] = $property . ':' . $customValue;
                continue;
            }

            if (!self::isAllowedStandardProperty($property)) {
                continue;
            }

            $normalizedValue = self::sanitizeStandardPropertyValue($property, $value);
            if ($normalizedValue === '') {
                continue;
            }

            $sanitizedParts[] = $property . ':' . $normalizedValue;
        }

        return implode('; ', $sanitizedParts);
    }

    private static function isAllowedStandardProperty(string $property): bool
    {
        $normalized = strtolower($property);
        if (isset(self::ALLOWED_PROPERTIES[$normalized])) {
            return true;
        }

        foreach (self::ALLOWED_PROPERTY_PREFIXES as $prefix) {
            if (str_starts_with($normalized, $prefix)) {
                return true;
            }
        }

        return false;
    }

    private static function isAllowedPropertyDefinitionProperty(string $property): bool
    {
        return in_array($property, ['syntax', 'initial-value', 'inherits'], true);
    }

    private static function sanitizePropertyDefinitionValue(string $property, string $value): string
    {
        $value = trim($value);

        switch ($property) {
            case 'syntax':
                $value = self::stripHtmlTags($value);
                $value = self::sanitizeUrls($value);

                if ($value === '') {
                    return '';
                }

                $quote = $value[0];
                if ($quote !== '"' && $quote !== "'") {
                    return '';
                }

                if (substr($value, -1) !== $quote) {
                    return '';
                }

                $inner = substr($value, 1, -1);
                if ($inner === '') {
                    return '';
                }

                if (strpbrk($inner, "\"'{};\\") !== false) {
                    return '';
                }

                if (\preg_match('/[\x00-\x1F\x7F]/', $inner)) {
                    return '';
                }

                return $quote . $inner . $quote;

            case 'initial-value':
                $normalized = self::sanitizeCustomPropertyValue($value);
                return $normalized;

            case 'inherits':
                $lowerValue = strtolower($value);
                if ($lowerValue === 'true' || $lowerValue === 'false') {
                    return $lowerValue;
                }

                return '';
        }

        return '';
    }

    /**
     * @return list<string>
     */
    private static function splitDeclarations(string $declarations): array
    {
        $length = strlen($declarations);
        if ($length === 0) {
            return [];
        }

        $parts = [];
        $buffer = '';
        $parenDepth = 0;
        $inSingleQuote = false;
        $inDoubleQuote = false;
        $escaped = false;

        for ($i = 0; $i < $length; $i++) {
            $char = $declarations[$i];

            if ($escaped) {
                $buffer .= $char;
                $escaped = false;
                continue;
            }

            if ($char === '\\') {
                $buffer .= $char;
                $escaped = true;
                continue;
            }

            if ($char === "'" && !$inDoubleQuote) {
                $inSingleQuote = !$inSingleQuote;
                $buffer .= $char;
                continue;
            }

            if ($char === '"' && !$inSingleQuote) {
                $inDoubleQuote = !$inDoubleQuote;
                $buffer .= $char;
                continue;
            }

            if (!$inSingleQuote && !$inDoubleQuote) {
                if ($char === '(') {
                    $parenDepth++;
                } elseif ($char === ')' && $parenDepth > 0) {
                    $parenDepth--;
                } elseif ($char === ';' && $parenDepth === 0) {
                    $parts[] = $buffer;
                    $buffer = '';
                    continue;
                }
            }

            $buffer .= $char;
        }

        if ($buffer !== '') {
            $parts[] = $buffer;
        }

        return $parts;
    }

    private static function isSafePropertyName(string $property): bool
    {
        return (bool) \preg_match('/^(--[A-Za-z0-9_-]+|[A-Za-z-][A-Za-z0-9_-]*)$/', $property);
    }

    private static function sanitizeCustomPropertyValue(string $value): string
    {
        $value = self::stripHtmlTags($value);
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
        if ($css === '') {
            return '';
        }

        $length = strlen($css);
        $result = '';
        $offset = 0;

        while (($position = stripos($css, 'url(', $offset)) !== false) {
            $result .= substr($css, $offset, $position - $offset);

            $cursor = $position + 4;
            $inSingleQuote = false;
            $inDoubleQuote = false;
            $escaped = false;

            while ($cursor < $length) {
                $character = $css[$cursor];

                if ($escaped) {
                    $escaped = false;
                    $cursor++;
                    continue;
                }

                if ($character === '\\') {
                    $escaped = true;
                    $cursor++;
                    continue;
                }

                if ($character === "'" && !$inDoubleQuote) {
                    $inSingleQuote = !$inSingleQuote;
                    $cursor++;
                    continue;
                }

                if ($character === '"' && !$inSingleQuote) {
                    $inDoubleQuote = !$inDoubleQuote;
                    $cursor++;
                    continue;
                }

                if (!$inSingleQuote && !$inDoubleQuote && $character === ')') {
                    break;
                }

                $cursor++;
            }

            if ($cursor >= $length) {
                $result .= substr($css, $position);
                $offset = $length;
                break;
            }

            $content = substr($css, $position + 4, $cursor - ($position + 4));
            $sanitizedToken = self::sanitizeUrlToken($content);

            if ($sanitizedToken !== '') {
                $result .= $sanitizedToken;
            }

            $offset = $cursor + 1;
        }

        if ($offset < $length) {
            $result .= substr($css, $offset);
        }

        return $result;
    }

    private static function sanitizeUrlToken(string $content): string
    {
        $raw = trim($content);
        if ($raw === '') {
            return '';
        }

        $quote = '';
        $firstCharacter = $raw[0];
        if ($firstCharacter === '"' || $firstCharacter === "'") {
            $quote = $firstCharacter;
            if (substr($raw, -1) === $quote) {
                $raw = substr($raw, 1, -1);
            } else {
                $raw = trim($raw, "\"'");
            }
        }

        if (self::isSafeDataUri($raw)) {
            $sanitized = $raw;
        } else {
            $sanitized = trim(\wp_kses_bad_protocol($raw, \wp_allowed_protocols()));
            if ($sanitized === '' || \preg_match('/^(?:javascript|vbscript)/i', $sanitized)) {
                return '';
            }

            if (str_starts_with(strtolower($sanitized), 'data:')) {
                return '';
            }
        }

        if ($quote === '') {
            $quote = '"';
        }

        return 'url(' . $quote . $sanitized . $quote . ')';
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

        $allowedImageTypes = [
            'image/png',
            'image/jpeg',
            'image/gif',
            'image/webp',
        ];

        if (in_array($mime, $allowedImageTypes, true)) {
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

    private static function stripHtmlTags(string $css): string
    {
        if ($css === '') {
            return '';
        }

        $placeholders = [];
        $masked = self::maskQuotedSegments($css, $placeholders);

        $clean = \wp_kses($masked, []);

        if (!empty($placeholders)) {
            $clean = strtr($clean, $placeholders);
        }

        return $clean;
    }

    private static function maskQuotedSegments(string $css, array &$placeholders): string
    {
        $length = strlen($css);
        if ($length === 0) {
            return '';
        }

        $buffer = '';
        $index = 0;

        while ($index < $length) {
            $char = $css[$index];

            if ($char === '"' || $char === "'") {
                $quote = $char;
                $index++;
                $segment = $quote;

                while ($index < $length) {
                    $segment .= $css[$index];

                    if ($css[$index] === '\\' && ($index + 1) < $length) {
                        $index++;
                        $segment .= $css[$index];
                        $index++;
                        continue;
                    }

                    if ($css[$index] === $quote) {
                        $index++;
                        break;
                    }

                    $index++;
                }

                if ($segment === $quote) {
                    $buffer .= $segment;
                    continue;
                }

                $placeholder = '__SSC_CSS_TOKEN_' . count($placeholders) . '__';
                $placeholders[$placeholder] = $segment;
                $buffer .= $placeholder;

                continue;
            }

            $buffer .= $char;
            $index++;
        }

        return $buffer;
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

        if (strpos($property, '--') === 0) {
            $customValue = self::sanitizeCustomPropertyValue($value);
            if ($customValue === '') {
                return null;
            }

            return [$property, $customValue];
        }

        if (!self::isAllowedStandardProperty($property)) {
            return null;
        }

        $normalizedValue = self::sanitizeStandardPropertyValue($property, $value);
        if ($normalizedValue === '') {
            return null;
        }

        return [$property, $normalizedValue];
    }

    private static function sanitizeStandardPropertyValue(string $property, string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        $value = self::stripHtmlTags($value);
        $value = self::sanitizeUrls($value);
        $value = (string) \preg_replace('/expression\s*\([^)]*\)/i', '', $value);
        $value = (string) \preg_replace('/behaviou?r\s*:[^;]+;?/i', '', $value);
        $value = (string) \preg_replace('/-moz-binding\s*:[^;]+;?/i', '', $value);

        $value = trim($value);
        $value = rtrim($value, ';');

        if ($value === '') {
            return '';
        }

        if (\preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $value)) {
            return '';
        }

        return $value;
    }

    private const ALLOWED_PROPERTIES = [
        'accent-color' => true,
        'align-content' => true,
        'align-items' => true,
        'align-self' => true,
        'animation' => true,
        'animation-composition' => true,
        'animation-delay' => true,
        'animation-direction' => true,
        'animation-duration' => true,
        'animation-fill-mode' => true,
        'animation-iteration-count' => true,
        'animation-name' => true,
        'animation-play-state' => true,
        'animation-range' => true,
        'animation-range-end' => true,
        'animation-range-start' => true,
        'animation-timing-function' => true,
        'backdrop-filter' => true,
        'backface-visibility' => true,
        'aspect-ratio' => true,
        'background' => true,
        'background-attachment' => true,
        'background-blend-mode' => true,
        'background-clip' => true,
        'background-color' => true,
        'background-image' => true,
        'background-origin' => true,
        'background-position' => true,
        'background-position-x' => true,
        'background-position-y' => true,
        'background-repeat' => true,
        'background-size' => true,
        'block-size' => true,
        'border' => true,
        'border-block' => true,
        'border-block-color' => true,
        'border-block-end' => true,
        'border-block-end-color' => true,
        'border-block-end-style' => true,
        'border-block-end-width' => true,
        'border-block-start' => true,
        'border-block-start-color' => true,
        'border-block-start-style' => true,
        'border-block-start-width' => true,
        'border-block-style' => true,
        'border-block-width' => true,
        'border-bottom' => true,
        'border-bottom-color' => true,
        'border-bottom-left-radius' => true,
        'border-bottom-right-radius' => true,
        'border-bottom-style' => true,
        'border-bottom-width' => true,
        'border-collapse' => true,
        'border-color' => true,
        'border-end-end-radius' => true,
        'border-end-start-radius' => true,
        'border-image' => true,
        'border-image-outset' => true,
        'border-image-repeat' => true,
        'border-image-slice' => true,
        'border-image-source' => true,
        'border-image-width' => true,
        'border-inline' => true,
        'border-inline-color' => true,
        'border-inline-end' => true,
        'border-inline-end-color' => true,
        'border-inline-end-style' => true,
        'border-inline-end-width' => true,
        'border-inline-start' => true,
        'border-inline-start-color' => true,
        'border-inline-start-style' => true,
        'border-inline-start-width' => true,
        'border-inline-style' => true,
        'border-inline-width' => true,
        'border-left' => true,
        'border-left-color' => true,
        'border-left-style' => true,
        'border-left-width' => true,
        'border-radius' => true,
        'border-right' => true,
        'border-right-color' => true,
        'border-right-style' => true,
        'border-right-width' => true,
        'border-spacing' => true,
        'border-start-end-radius' => true,
        'border-start-start-radius' => true,
        'border-style' => true,
        'border-top' => true,
        'border-top-color' => true,
        'border-top-left-radius' => true,
        'border-top-right-radius' => true,
        'border-top-style' => true,
        'border-top-width' => true,
        'border-width' => true,
        'bottom' => true,
        'box-shadow' => true,
        'box-sizing' => true,
        'break-after' => true,
        'break-before' => true,
        'break-inside' => true,
        'caption-side' => true,
        'caret-color' => true,
        'clear' => true,
        'clip' => true,
        'clip-path' => true,
        'color' => true,
        'color-scheme' => true,
        'column-count' => true,
        'column-fill' => true,
        'column-gap' => true,
        'column-rule' => true,
        'column-rule-color' => true,
        'column-rule-style' => true,
        'column-rule-width' => true,
        'column-span' => true,
        'column-width' => true,
        'columns' => true,
        'contain' => true,
        'contain-intrinsic-block-size' => true,
        'contain-intrinsic-height' => true,
        'contain-intrinsic-inline-size' => true,
        'contain-intrinsic-size' => true,
        'contain-intrinsic-width' => true,
        'content' => true,
        'counter-increment' => true,
        'counter-reset' => true,
        'counter-set' => true,
        'cursor' => true,
        'direction' => true,
        'display' => true,
        'filter' => true,
        'flex' => true,
        'flex-basis' => true,
        'flex-direction' => true,
        'flex-flow' => true,
        'flex-grow' => true,
        'flex-shrink' => true,
        'flex-wrap' => true,
        'float' => true,
        'font' => true,
        'font-display' => true,
        'font-family' => true,
        'font-feature-settings' => true,
        'font-language-override' => true,
        'font-kerning' => true,
        'font-optical-sizing' => true,
        'font-size' => true,
        'font-size-adjust' => true,
        'font-stretch' => true,
        'font-style' => true,
        'font-synthesis' => true,
        'font-variant' => true,
        'font-variant-caps' => true,
        'font-variant-east-asian' => true,
        'font-variant-ligatures' => true,
        'font-variant-numeric' => true,
        'font-variation-settings' => true,
        'font-weight' => true,
        'src' => true,
        'forced-color-adjust' => true,
        'gap' => true,
        'grid-gap' => true,
        'grid' => true,
        'grid-area' => true,
        'grid-auto-columns' => true,
        'grid-auto-flow' => true,
        'grid-auto-rows' => true,
        'grid-column' => true,
        'grid-column-end' => true,
        'grid-column-gap' => true,
        'grid-column-start' => true,
        'grid-row' => true,
        'grid-row-end' => true,
        'grid-row-gap' => true,
        'grid-row-start' => true,
        'grid-template' => true,
        'grid-template-areas' => true,
        'grid-template-columns' => true,
        'grid-template-rows' => true,
        'height' => true,
        'hyphens' => true,
        'image-rendering' => true,
        'inline-size' => true,
        'inset' => true,
        'inset-block' => true,
        'inset-block-end' => true,
        'inset-block-start' => true,
        'inset-inline' => true,
        'inset-inline-end' => true,
        'inset-inline-start' => true,
        'isolation' => true,
        'justify-content' => true,
        'justify-items' => true,
        'justify-self' => true,
        'left' => true,
        'letter-spacing' => true,
        'line-break' => true,
        'line-clamp' => true,
        'line-height' => true,
        'list-style' => true,
        'list-style-image' => true,
        'list-style-position' => true,
        'list-style-type' => true,
        'margin' => true,
        'margin-block' => true,
        'margin-block-end' => true,
        'margin-block-start' => true,
        'margin-bottom' => true,
        'margin-inline' => true,
        'margin-inline-end' => true,
        'margin-inline-start' => true,
        'margin-left' => true,
        'margin-right' => true,
        'margin-top' => true,
        'mask' => true,
        'mask-border' => true,
        'mask-border-mode' => true,
        'mask-border-outset' => true,
        'mask-border-repeat' => true,
        'mask-border-slice' => true,
        'mask-border-source' => true,
        'mask-border-width' => true,
        'mask-clip' => true,
        'mask-composite' => true,
        'mask-image' => true,
        'mask-mode' => true,
        'mask-origin' => true,
        'mask-position' => true,
        'mask-repeat' => true,
        'mask-size' => true,
        'mask-type' => true,
        'max-block-size' => true,
        'max-height' => true,
        'max-inline-size' => true,
        'max-width' => true,
        'min-block-size' => true,
        'min-height' => true,
        'min-inline-size' => true,
        'min-width' => true,
        'mix-blend-mode' => true,
        'object-fit' => true,
        'object-position' => true,
        'opacity' => true,
        'order' => true,
        'overflow-anchor' => true,
        'outline' => true,
        'outline-color' => true,
        'outline-offset' => true,
        'outline-style' => true,
        'outline-width' => true,
        'overflow' => true,
        'overflow-wrap' => true,
        'overflow-x' => true,
        'overflow-y' => true,
        'overscroll-behavior' => true,
        'overscroll-behavior-block' => true,
        'overscroll-behavior-inline' => true,
        'overscroll-behavior-x' => true,
        'overscroll-behavior-y' => true,
        'padding' => true,
        'padding-block' => true,
        'padding-block-end' => true,
        'padding-block-start' => true,
        'padding-bottom' => true,
        'padding-inline' => true,
        'padding-inline-end' => true,
        'padding-inline-start' => true,
        'padding-left' => true,
        'padding-right' => true,
        'padding-top' => true,
        'perspective' => true,
        'perspective-origin' => true,
        'place-content' => true,
        'place-items' => true,
        'place-self' => true,
        'pointer-events' => true,
        'position' => true,
        'resize' => true,
        'right' => true,
        'row-gap' => true,
        'scale' => true,
        'scroll-behavior' => true,
        'scroll-margin' => true,
        'scroll-margin-block' => true,
        'scroll-margin-block-end' => true,
        'scroll-margin-block-start' => true,
        'scroll-margin-bottom' => true,
        'scroll-margin-inline' => true,
        'scroll-margin-inline-end' => true,
        'scroll-margin-inline-start' => true,
        'scroll-margin-left' => true,
        'scroll-margin-right' => true,
        'scroll-margin-top' => true,
        'scroll-padding' => true,
        'scroll-padding-block' => true,
        'scroll-padding-block-end' => true,
        'scroll-padding-block-start' => true,
        'scroll-padding-bottom' => true,
        'scroll-padding-inline' => true,
        'scroll-padding-inline-end' => true,
        'scroll-padding-inline-start' => true,
        'scroll-padding-left' => true,
        'scroll-padding-right' => true,
        'scroll-padding-top' => true,
        'scroll-snap-align' => true,
        'scroll-snap-stop' => true,
        'scroll-snap-type' => true,
        'scrollbar-color' => true,
        'scrollbar-width' => true,
        'shape-image-threshold' => true,
        'shape-margin' => true,
        'shape-outside' => true,
        'shape-rendering' => true,
        'tab-size' => true,
        'table-layout' => true,
        'text-align' => true,
        'text-align-last' => true,
        'text-decoration' => true,
        'text-decoration-color' => true,
        'text-decoration-line' => true,
        'text-decoration-style' => true,
        'text-decoration-thickness' => true,
        'text-indent' => true,
        'text-justify' => true,
        'text-overflow' => true,
        'text-underline-offset' => true,
        'text-underline-position' => true,
        'text-decoration-skip-ink' => true,
        'text-emphasis' => true,
        'text-emphasis-color' => true,
        'text-emphasis-position' => true,
        'text-emphasis-style' => true,
        'text-shadow' => true,
        'text-transform' => true,
        'top' => true,
        'touch-action' => true,
        'transform' => true,
        'transform-origin' => true,
        'transform-style' => true,
        'translate' => true,
        'rotate' => true,
        'scale' => true,
        'skew' => true,
        'skew-x' => true,
        'skew-y' => true,
        'transition' => true,
        'transition-delay' => true,
        'transition-duration' => true,
        'transition-property' => true,
        'transition-timing-function' => true,
        'unicode-bidi' => true,
        'unicode-range' => true,
        'vertical-align' => true,
        'visibility' => true,
        'user-select' => true,
        'white-space' => true,
        'will-change' => true,
        'word-break' => true,
        'word-spacing' => true,
        'word-wrap' => true,
        'writing-mode' => true,
        'z-index' => true,
    ];

    private const ALLOWED_PROPERTY_PREFIXES = [
        'grid-template-',
        'grid-auto-',
        'grid-column-',
        'grid-row-',
        'inset-',
        'margin-',
        'padding-',
        'background-',
        'border-',
        'animation-',
        'transition-',
        'scroll-margin-',
        'scroll-padding-',
        'mask-',
        'contain-intrinsic-',
        'font-variation-',
        'text-emphasis-',
        'scrollbar-',
    ];

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
