<?php declare(strict_types=1);

namespace SSC\Support;

if (!defined('ABSPATH')) { exit; }

final class CssRevisions
{
    private const OPTION = 'ssc_css_revisions';
    private const MAX_REVISIONS = 20;

    /**
     * @var array<int, array{canonical: string, variants: array<int, string>, conflicts: array<int, array{name: string, value: string}>}>
     */
    private static $lastTokenDuplicates = [];

    /**
     * @param array{segments?: array<string, mixed>} $context
     */
    public static function record(string $option, string $css, array $context = []): void
    {
        $normalizedOption = sanitize_key($option);
        if ($normalizedOption === '') {
            return;
        }

        $css = CssSanitizer::sanitize($css);

        $author = 'anon';
        if (function_exists('wp_get_current_user') || function_exists('\\wp_get_current_user')) {
            $user = \wp_get_current_user();
            $candidate = self::resolveAuthorFromUser($user);

            if ($candidate !== '') {
                $author = $candidate;
            }
        }

        $revision = [
            'id' => self::generateRevisionId(),
            'option' => $normalizedOption,
            'css' => $css,
            'timestamp' => gmdate('c'),
            'author' => sanitize_text_field($author),
        ];

        if ($normalizedOption === 'ssc_active_css') {
            $segments = $context['segments'] ?? null;
            $revision['segments'] = self::sanitizeSegments(is_array($segments) ? $segments : null);
        }

        $stored = get_option(self::OPTION, []);
        if (!is_array($stored)) {
            $stored = [];
        }

        array_unshift($stored, $revision);
        $maxRevisions = self::getMaxRevisions();
        if ($maxRevisions > 0 && count($stored) > $maxRevisions) {
            $stored = array_slice($stored, 0, $maxRevisions);
        }

        update_option(self::OPTION, array_values($stored), false);
    }

    private static function getMaxRevisions(): int
    {
        $max = self::MAX_REVISIONS;

        if (function_exists('apply_filters')) {
            $filtered = apply_filters('ssc_css_revisions_max', $max);

            if (is_numeric($filtered)) {
                $max = (int) $filtered;
            }
        }

        if ($max < 1) {
            return self::MAX_REVISIONS;
        }

        return $max;
    }

    /**
     * @param mixed $user
     */
    private static function resolveAuthorFromUser($user): string
    {
        if (!is_object($user)) {
            return '';
        }

        if (method_exists($user, 'exists') && !$user->exists()) {
            return '';
        }

        $candidates = [];

        if (isset($user->user_login) && is_string($user->user_login)) {
            $candidates[] = $user->user_login;
        }

        if (method_exists($user, 'get')) {
            $login = $user->get('user_login');
            if (is_string($login)) {
                $candidates[] = $login;
            }
        }

        foreach (['user_nicename', 'display_name', 'user_email'] as $property) {
            if (isset($user->{$property}) && is_string($user->{$property})) {
                $candidates[] = $user->{$property};
            }
        }

        foreach ($candidates as $candidate) {
            $candidate = trim((string) $candidate);
            if ($candidate !== '') {
                return $candidate;
            }
        }

        return '';
    }

    /**
     * @return array<int, array{id: string, option: string, css: string, timestamp: string, author: string, segments?: array<string, string>}> 
     */
    public static function all(): array
    {
        $stored = get_option(self::OPTION, []);
        if (!is_array($stored)) {
            return [];
        }

        $normalized = [];

        foreach ($stored as $revision) {
            $candidate = self::normalizeRevision($revision);
            if ($candidate !== null) {
                $normalized[] = $candidate;
            }
        }

        return $normalized;
    }

    /**
     * Applies a stored revision and returns the normalized representation when the identifier exists.
     *
     * @return array{id: string, option: string, css: string, timestamp: string, author: string, segments?: array<string, string>}|null|array{error: string, duplicates: array<int, array{canonical: string, variants: array<int, string>, conflicts: array<int, array{name: string, value: string}>}>, revision: array{id: string, option: string, css: string, timestamp: string, author: string, segments?: array<string, string>}}
     */
    public static function restore(string $revisionId)
    {
        $revisionId = sanitize_text_field($revisionId);
        if ($revisionId === '') {
            return null;
        }

        foreach (self::all() as $revision) {
            if ($revision['id'] !== $revisionId) {
                continue;
            }

            self::$lastTokenDuplicates = [];
            $restored = self::applyRevision($revision);
            if ($restored === false) {
                return [
                    'error' => 'tokens_duplicates',
                    'duplicates' => self::$lastTokenDuplicates,
                    'revision' => $revision,
                ];
            }

            return $revision;
        }

        return null;
    }

    /**
     * @param array{id: string, option: string, css: string, timestamp: string, author: string, segments?: array<string, string>} $revision
     */
    private static function applyRevision(array $revision): bool
    {
        $option = $revision['option'];
        $css = $revision['css'];

        if ($option === 'ssc_tokens_css') {
            $conversion = TokenRegistry::convertCssToRegistryDetailed($css);
            if ($conversion['duplicates'] !== []) {
                self::$lastTokenDuplicates = $conversion['duplicates'];
                return false;
            }

            $tokens = $conversion['tokens'];
            $existingRegistry = TokenRegistry::getRegistry();
            $tokensWithMetadata = TokenRegistry::mergeMetadata($tokens, $existingRegistry);
            $savedTokens = TokenRegistry::saveRegistry($tokensWithMetadata);
            if ($savedTokens['duplicates'] !== []) {
                self::$lastTokenDuplicates = $savedTokens['duplicates'];
                return false;
            }
            $css = TokenRegistry::tokensToCss($savedTokens['tokens']);
            update_option('ssc_tokens_css', $css, false);
        } else {
            update_option($option, $css, false);

            if ($option === 'ssc_active_css') {
                $segments = $revision['segments'] ?? ['desktop' => '', 'tablet' => '', 'mobile' => ''];
                $segments = is_array($segments) ? $segments : ['desktop' => '', 'tablet' => '', 'mobile' => ''];
                $map = [
                    'desktop' => 'ssc_css_desktop',
                    'tablet' => 'ssc_css_tablet',
                    'mobile' => 'ssc_css_mobile',
                ];

                foreach ($map as $key => $segmentOption) {
                    $value = '';
                    if (isset($segments[$key]) && is_string($segments[$key])) {
                        $value = CssSanitizer::sanitize($segments[$key]);
                    }

                    update_option($segmentOption, $value, false);
                }
            }
        }

        if (function_exists('ssc_invalidate_css_cache')) {
            \ssc_invalidate_css_cache();
        }

        self::$lastTokenDuplicates = [];

        return true;
    }

    /**
     * @param mixed $revision
     * @return array{id: string, option: string, css: string, timestamp: string, author: string, segments?: array<string, string>}|null
     */
    private static function normalizeRevision($revision): ?array
    {
        if (!is_array($revision)) {
            return null;
        }

        $id = isset($revision['id']) ? sanitize_text_field((string) $revision['id']) : '';
        $option = isset($revision['option']) ? sanitize_key($revision['option']) : '';
        if ($id === '' || $option === '') {
            return null;
        }

        $cssRaw = isset($revision['css']) ? (string) $revision['css'] : '';
        $css = CssSanitizer::sanitize($cssRaw);

        $timestamp = isset($revision['timestamp']) ? sanitize_text_field((string) $revision['timestamp']) : '';
        if ($timestamp === '') {
            $timestamp = gmdate('c');
        }

        $author = isset($revision['author']) ? sanitize_text_field((string) $revision['author']) : 'anon';
        if ($author === '') {
            $author = 'anon';
        }

        $normalized = [
            'id' => $id,
            'option' => $option,
            'css' => $css,
            'timestamp' => $timestamp,
            'author' => $author,
        ];

        if ($option === 'ssc_active_css') {
            $segments = $revision['segments'] ?? null;
            $normalized['segments'] = self::sanitizeSegments(is_array($segments) ? $segments : null);
        }

        return $normalized;
    }

    /**
     * @param array<string, mixed>|null $segments
     * @return array<string, string>
     */
    private static function sanitizeSegments(?array $segments): array
    {
        $normalized = [];
        foreach (['desktop', 'tablet', 'mobile'] as $key) {
            $value = '';
            if ($segments !== null && array_key_exists($key, $segments) && is_string($segments[$key])) {
                $value = CssSanitizer::sanitize($segments[$key]);
            }

            $normalized[$key] = $value;
        }

        return $normalized;
    }

    private static function generateRevisionId(): string
    {
        try {
            $bytes = random_bytes(9);
            $encoded = rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
        } catch (\Throwable $e) {
            $encoded = uniqid('rev_', true);
            $encoded = str_replace('.', '-', $encoded);
        }

        return sanitize_text_field($encoded);
    }
}
