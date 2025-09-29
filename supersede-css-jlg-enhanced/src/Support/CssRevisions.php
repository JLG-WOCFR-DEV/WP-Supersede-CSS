<?php declare(strict_types=1);

namespace SSC\Support;

if (!defined('ABSPATH')) { exit; }

final class CssRevisions
{
    private const OPTION = 'ssc_css_revisions';
    private const MAX_REVISIONS = 20;

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
        if (function_exists('wp_get_current_user')) {
            $user = wp_get_current_user();
            if ($user && isset($user->ID) && (int) $user->ID !== 0 && isset($user->user_login) && $user->user_login !== '') {
                $author = (string) $user->user_login;
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
        if (count($stored) > self::MAX_REVISIONS) {
            $stored = array_slice($stored, 0, self::MAX_REVISIONS);
        }

        update_option(self::OPTION, array_values($stored), false);
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
     * @return array{id: string, option: string, css: string, timestamp: string, author: string, segments?: array<string, string>}|null
     */
    public static function restore(string $revisionId): ?array
    {
        $revisionId = sanitize_text_field($revisionId);
        if ($revisionId === '') {
            return null;
        }

        foreach (self::all() as $revision) {
            if ($revision['id'] !== $revisionId) {
                continue;
            }

            self::applyRevision($revision);

            return $revision;
        }

        return null;
    }

    /**
     * @param array{id: string, option: string, css: string, timestamp: string, author: string, segments?: array<string, string>} $revision
     */
    private static function applyRevision(array $revision): void
    {
        $option = $revision['option'];
        $css = $revision['css'];

        if ($option === 'ssc_tokens_css') {
            $tokens = TokenRegistry::convertCssToRegistry($css);
            $existingRegistry = TokenRegistry::getRegistry();
            $tokensWithMetadata = TokenRegistry::mergeMetadata($tokens, $existingRegistry);
            $savedTokens = TokenRegistry::saveRegistry($tokensWithMetadata);
            $css = TokenRegistry::tokensToCss($savedTokens);
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
