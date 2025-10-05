<?php declare(strict_types=1);

namespace SSC\Infra\Import;

use SSC\Infra\Logger;
use SSC\Support\CssSanitizer;
use SSC\Support\TokenRegistry;

/**
 * Provides helpers to sanitize imported Supersede CSS configuration payloads.
 */
final class Sanitizer
{
    private const IMPORT_MAX_DEPTH = 20;
    private const IMPORT_MAX_ITEMS = 5000;

    /** @var list<string> */
    private array $duplicateWarnings = [];

    public function resetDuplicateWarnings(): void
    {
        $this->duplicateWarnings = [];
    }

    /**
     * @return list<string>
     */
    public function consumeDuplicateWarnings(): array
    {
        if ($this->duplicateWarnings === []) {
            return [];
        }

        $warnings = array_values(array_unique($this->duplicateWarnings));
        $this->duplicateWarnings = [];

        return $warnings;
    }

    public function recordDuplicateWarning(string $path): void
    {
        if ($path === '') {
            return;
        }

        $this->duplicateWarnings[] = $path;
    }

    /**
     * @param mixed $value
     */
    public function sanitizeImportCss($value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        return CssSanitizer::sanitize($value);
    }

    /**
     * @param mixed $value
     * @return array<int, array<string, mixed>>|null
     */
    public function sanitizeImportTokens($value): ?array
    {
        if (!is_array($value)) {
            return null;
        }

        $sanitized = [];

        foreach ($value as $token) {
            if (!is_array($token)) {
                continue;
            }

            $normalized = TokenRegistry::normalizeToken($token);

            if ($normalized === null) {
                continue;
            }

            $sanitized[] = $normalized;
        }

        if ($sanitized === []) {
            return null;
        }

        $result = TokenRegistry::normalizeRegistry($sanitized);

        if ($result['duplicates'] !== []) {
            foreach ($result['duplicates'] as $duplicate) {
                $path = isset($duplicate['canonical']) ? (string) $duplicate['canonical'] : '';
                if ($path !== '') {
                    $this->recordDuplicateWarning($path);
                }
            }

            return null;
        }

        return $result['tokens'];
    }

    /**
     * @param mixed $value
     * @return array<int, array<string, mixed>>|null
     */
    public function sanitizeImportPresets($value): ?array
    {
        if (!is_array($value)) {
            return null;
        }

        $sanitized = CssSanitizer::sanitizePresetCollection($value);

        return $sanitized === [] ? null : $sanitized;
    }

    /**
     * @param mixed $value
     * @return array<int, array<string, mixed>>|null
     */
    public function sanitizeImportAvatarGlowPresets($value): ?array
    {
        if (!is_array($value)) {
            return null;
        }

        $sanitized = CssSanitizer::sanitizeAvatarGlowPresets($value);

        return $sanitized === [] ? null : $sanitized;
    }

    /**
     * @param mixed $value
     * @return array<int, array<string, mixed>>|null
     */
    public function sanitizeImportVisualEffectsPresets($value): ?array
    {
        if (!is_array($value)) {
            return null;
        }

        $sanitized = CssSanitizer::sanitizeVisualEffectsPresets($value);

        return $sanitized === [] ? null : $sanitized;
    }

    /**
     * @param mixed $value
     */
    public function sanitizeImportArray(
        $value,
        int $depth = 0,
        ?\SplObjectStorage $objectStack = null,
        ?int &$itemBudget = null,
        string $parentPath = '',
        ?array &$arrayReferenceStack = null
    ): ?array {
        if ($depth > self::IMPORT_MAX_DEPTH) {
            return null;
        }

        if (!is_array($value)) {
            return null;
        }

        if ($objectStack === null) {
            $objectStack = new \SplObjectStorage();
        }

        if ($itemBudget === null) {
            $itemBudget = self::IMPORT_MAX_ITEMS;
        }

        if ($arrayReferenceStack === null) {
            $arrayReferenceStack = [];
        }

        $sanitized = [];

        foreach ($value as $key => $item) {
            if ($itemBudget <= 0) {
                break;
            }

            $sanitizedKey = $this->sanitizeImportKey($key, $depth);

            if ($sanitizedKey === null) {
                continue;
            }

            if (array_key_exists($sanitizedKey, $sanitized)) {
                $duplicatePath = $this->formatDuplicateKeyPath($parentPath, $sanitizedKey);
                $this->recordDuplicateWarning($duplicatePath);

                continue;
            }

            $duplicatePath = $this->formatDuplicateKeyPath($parentPath, $sanitizedKey);

            $itemBudget--;

            if (is_array($item)) {
                if ($item === []) {
                    $sanitized[$sanitizedKey] = [];
                    continue;
                }

                $referenceId = $this->extractArrayReferenceId($value, $key);

                if ($referenceId !== null) {
                    if (isset($arrayReferenceStack[$referenceId])) {
                        $this->recordDuplicateWarning($duplicatePath);
                        continue;
                    }

                    $arrayReferenceStack[$referenceId] = true;
                }

                $nested = $this->sanitizeImportArray(
                    $item,
                    $depth + 1,
                    $objectStack,
                    $itemBudget,
                    $duplicatePath,
                    $arrayReferenceStack
                );

                if ($referenceId !== null) {
                    unset($arrayReferenceStack[$referenceId]);
                }

                if ($nested === null) {
                    continue;
                }

                $sanitized[$sanitizedKey] = $nested;
                continue;
            }

            if ($item instanceof \JsonSerializable) {
                if ($objectStack->contains($item)) {
                    $this->recordDuplicateWarning($duplicatePath);
                    continue;
                }

                $objectStack->attach($item);

                $encoded = json_encode($item);
                if (is_string($encoded)) {
                    $sanitized[$sanitizedKey] = $this->sanitizeImportStringValue(
                        $encoded,
                        $depth,
                        $objectStack,
                        $itemBudget,
                        $arrayReferenceStack
                    );
                }

                $objectStack->detach($item);
                continue;
            }

            if (is_object($item)) {
                if ($objectStack->contains($item)) {
                    $this->recordDuplicateWarning($duplicatePath);
                    continue;
                }

                $objectStack->attach($item);

                $sanitized[$sanitizedKey] = $this->sanitizeImportStringValue(
                    $this->encodeObject($item),
                    $depth,
                    $objectStack,
                    $itemBudget,
                    $arrayReferenceStack
                );

                $objectStack->detach($item);
                continue;
            }

            $sanitized[$sanitizedKey] = $this->sanitizeImportStringValue(
                (string) $item,
                $depth,
                $objectStack,
                $itemBudget,
                $arrayReferenceStack
            );
        }

        return $sanitized;
    }

    /**
     * @param array<int|string, mixed> $parent
     * @param mixed $key
     */
    private function extractArrayReferenceId(array &$parent, $key): ?string
    {
        if (!class_exists(\ReflectionReference::class)) {
            return null;
        }

        if (!is_int($key) && !is_string($key)) {
            return null;
        }

        try {
            $reference = \ReflectionReference::fromArrayElement($parent, $key);
        } catch (\Throwable $exception) {
            unset($exception);

            return null;
        }

        if (!$reference instanceof \ReflectionReference) {
            return null;
        }

        return $reference->getId();
    }

    private function encodeObject(object $value): string
    {
        $jsonOptions = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;

        if (defined('JSON_PARTIAL_OUTPUT_ON_ERROR')) {
            $jsonOptions |= JSON_PARTIAL_OUTPUT_ON_ERROR;
        }

        $encoded = function_exists('wp_json_encode')
            ? wp_json_encode($value, $jsonOptions)
            : json_encode($value, $jsonOptions);

        return is_string($encoded) ? $encoded : '';
    }

    /**
     * @param mixed $key
     * @return int|string|null
     */
    public function sanitizeImportKey($key, int $depth)
    {
        if (is_int($key)) {
            return $key;
        }

        if (is_string($key)) {
            $sanitized = sanitize_key($key);

            if ($sanitized === '') {
                $fallback = preg_replace('/[^a-z0-9_\-]+/i', '-', strtolower($key));
                $fallback = is_string($fallback) ? trim($fallback, '-_') : '';
                $sanitized = $fallback;
            }

            return $sanitized !== '' ? $sanitized : null;
        }

        $casted = (string) $key;

        if ($casted === '') {
            return null;
        }

        $sanitized = sanitize_key($casted);

        if ($sanitized === '') {
            $fallback = preg_replace('/[^a-z0-9_\-]+/i', '-', strtolower($casted));
            $fallback = is_string($fallback) ? trim($fallback, '-_') : '';
            $sanitized = $fallback;
        }

        return $sanitized !== '' ? $sanitized : null;
    }

    public function formatDuplicateKeyPath(string $parentPath, int|string $key): string
    {
        $keyPart = (string) $key;

        return $parentPath === '' ? $keyPart : $parentPath . '.' . $keyPart;
    }

    public function sanitizeImportStringValue(
        string $value,
        int $depth = 0,
        ?\SplObjectStorage $objectStack = null,
        ?int &$itemBudget = null,
        ?array &$arrayReferenceStack = null
    ): string {
        if (function_exists('wp_check_invalid_utf8')) {
            $value = wp_check_invalid_utf8($value);
        }

        if ($value === false) {
            return '';
        }

        $value = (string) $value;
        $trimmed = trim($value);

        if ($trimmed !== '' && ($trimmed[0] === '{' || $trimmed[0] === '[')) {
            $decoded = json_decode($trimmed, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $sanitized = $this->sanitizeImportArray(
                    $decoded,
                    $depth + 1,
                    $objectStack,
                    $itemBudget,
                    '',
                    $arrayReferenceStack
                );

                if ($sanitized !== null) {
                    $jsonOptions = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;

                    if (defined('JSON_PARTIAL_OUTPUT_ON_ERROR')) {
                        $jsonOptions |= JSON_PARTIAL_OUTPUT_ON_ERROR;
                    }

                    $encoded = function_exists('wp_json_encode')
                        ? wp_json_encode($sanitized, $jsonOptions)
                        : json_encode($sanitized, $jsonOptions);

                    if (is_string($encoded)) {
                        $value = $encoded;
                    }
                } else {
                    $value = '';
                }
            }
        }

        $value = wp_kses($value, []);
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+/', '', $value);

        if (!is_string($value)) {
            $value = '';
        }

        return trim($value);
    }

    /**
     * @param mixed $value
     */
    public function sanitizeImportString($value): ?string
    {
        if (is_string($value)) {
            return $this->sanitizeImportStringValue($value);
        }

        if (is_scalar($value)) {
            return $this->sanitizeImportStringValue((string) $value);
        }

        return null;
    }

    /**
     * @param mixed $value
     */
    public function sanitizeImportBoolean($value): ?bool
    {
        if ($value === null) {
            return false;
        }

        return (bool) rest_sanitize_boolean($value);
    }

    /**
     * @param mixed $value
     * @return array<int, array<string, mixed>>|null
     */
    public function sanitizeImportAdminLog($value): ?array
    {
        if (!is_array($value)) {
            return null;
        }

        $sanitized = [];

        foreach ($value as $entry) {
            if (!is_array($entry)) {
                continue;
            }

            $timestamp = isset($entry['t']) ? sanitize_text_field((string) $entry['t']) : '';
            $user = isset($entry['user']) ? sanitize_text_field((string) $entry['user']) : 'anon';
            $action = isset($entry['action']) ? sanitize_text_field((string) $entry['action']) : '';
            $data = isset($entry['data']) ? $this->sanitizeImportArray($entry['data']) : [];

            if ($action === '') {
                continue;
            }

            if ($timestamp === '') {
                $timestamp = gmdate('c');
            }

            if ($user === '') {
                $user = 'anon';
            }

            if (!is_array($data)) {
                $data = [];
            }

            $sanitized[] = [
                't' => $timestamp,
                'user' => $user,
                'action' => $action,
                'data' => $data,
            ];
        }

        if ($sanitized === []) {
            return null;
        }

        if (count($sanitized) > Logger::MAX) {
            $sanitized = array_slice($sanitized, 0, Logger::MAX);
        }

        return $sanitized;
    }

    /**
     * @param mixed $value
     */
    public function sanitizeCssSegment($value): string
    {
        if (!is_string($value)) {
            return '';
        }

        $value = wp_unslash($value);

        return CssSanitizer::sanitize($value);
    }

    /**
     * @param array<string, string> $segments
     */
    public function combineResponsiveCss(array $segments): string
    {
        $desktop = $segments['desktop'] ?? '';
        $tablet = $segments['tablet'] ?? '';
        $mobile = $segments['mobile'] ?? '';

        $parts = [];

        if ($desktop !== '') {
            $parts[] = $desktop;
        }

        if (trim($tablet) !== '') {
            $parts[] = "@media (max-width: 782px) {\n{$tablet}\n}";
        }

        if (trim($mobile) !== '') {
            $parts[] = "@media (max-width: 480px) {\n{$mobile}\n}";
        }

        $combined = implode("\n\n", array_filter($parts, static function (string $part): bool {
            return $part !== '';
        }));

        return $combined === '' ? '' : trim($combined);
    }
}
