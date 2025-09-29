<?php declare(strict_types=1);

namespace SSC\Infra;

if (!defined('ABSPATH')) { exit; }

/**
 * Maintains a bounded list of CSS revisions for quick rollbacks.
 */
final class CssRevisions
{
    private const OPTION = 'ssc_css_revisions';
    private const MAX_REVISIONS = 20;

    /**
     * Records a new revision for the provided option.
     */
    public static function record(string $optionName, string $css): void
    {
        $normalizedOption = sanitize_key($optionName);
        $css = (string) $css;

        $revisions = self::getStoredRevisions();

        array_unshift($revisions, [
            'id' => self::generateId(),
            'option' => $normalizedOption,
            'css' => $css,
            't' => gmdate('c'),
            'user' => self::getActingUser(),
        ]);

        if (count($revisions) > self::MAX_REVISIONS) {
            $revisions = array_slice($revisions, 0, self::MAX_REVISIONS);
        }

        update_option(self::OPTION, $revisions, false);
    }

    /**
     * Restores the revision identified by $revisionId.
     */
    public static function restore(string $revisionId): bool
    {
        $revisionId = trim((string) $revisionId);
        if ($revisionId === '') {
            return false;
        }

        $revisions = self::getStoredRevisions();

        foreach ($revisions as $index => $revision) {
            if (!is_array($revision) || ($revision['id'] ?? '') !== $revisionId) {
                continue;
            }

            $option = isset($revision['option']) ? sanitize_key((string) $revision['option']) : '';
            $css = isset($revision['css']) ? (string) $revision['css'] : '';

            if ($option === '') {
                return false;
            }

            update_option($option, $css, false);

            if (function_exists('\\ssc_invalidate_css_cache')) {
                \ssc_invalidate_css_cache();
            }

            if (class_exists(Logger::class)) {
                Logger::add('css_revision_restored', [
                    'option' => $option,
                    'revision' => $revisionId,
                ]);
            }

            unset($revisions[$index]);

            $revision['id'] = self::generateId();
            $revision['t'] = gmdate('c');
            $revision['user'] = self::getActingUser();
            $revision['option'] = $option;
            $revision['css'] = $css;

            array_unshift($revisions, $revision);

            if (count($revisions) > self::MAX_REVISIONS) {
                $revisions = array_slice($revisions, 0, self::MAX_REVISIONS);
            }

            update_option(self::OPTION, array_values($revisions), false);

            return true;
        }

        return false;
    }

    /**
     * @return array<int, array{id: string, option: string, css: string, t: string, user: string}>
     */
    public static function all(): array
    {
        return self::getStoredRevisions();
    }

    /**
     * @return array<int, array{id: string, option: string, css: string, t: string, user: string}>
     */
    private static function getStoredRevisions(): array
    {
        $stored = get_option(self::OPTION, []);

        if (!is_array($stored)) {
            return [];
        }

        $normalized = [];

        foreach ($stored as $revision) {
            if (!is_array($revision)) {
                continue;
            }

            $id = isset($revision['id']) ? (string) $revision['id'] : '';
            $option = isset($revision['option']) ? sanitize_key((string) $revision['option']) : '';
            $css = isset($revision['css']) ? (string) $revision['css'] : '';
            $timestamp = isset($revision['t']) ? (string) $revision['t'] : '';
            $user = isset($revision['user']) ? sanitize_text_field((string) $revision['user']) : 'anon';

            if ($id === '' || $option === '' || $timestamp === '') {
                continue;
            }

            $normalized[] = [
                'id' => $id,
                'option' => $option,
                'css' => $css,
                't' => $timestamp,
                'user' => $user !== '' ? $user : 'anon',
            ];
        }

        return array_values($normalized);
    }

    private static function getActingUser(): string
    {
        if (!function_exists('wp_get_current_user')) {
            return 'anon';
        }

        $user = wp_get_current_user();

        if (is_object($user) && isset($user->user_login) && isset($user->ID) && (int) $user->ID > 0) {
            return sanitize_text_field((string) $user->user_login);
        }

        return 'anon';
    }

    private static function generateId(): string
    {
        try {
            return bin2hex(random_bytes(8));
        } catch (\Exception $exception) {
            unset($exception);

            return substr(md5(uniqid('ssc-css', true)), 0, 16);
        }
    }
}
