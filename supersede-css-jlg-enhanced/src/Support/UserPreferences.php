<?php declare(strict_types=1);

namespace SSC\Support;

final class UserPreferences
{
    public const MODE_SIMPLE = 'simple';
    public const MODE_EXPERT = 'expert';
    public const META_KEY_UTILITIES_EDITOR_MODE = 'ssc_utilities_editor_mode';

    public static function getUtilitiesEditorMode(?int $userId = null): string
    {
        $userId = self::resolveUserId($userId);

        if ($userId === 0) {
            return self::MODE_SIMPLE;
        }

        $stored = get_user_meta($userId, self::META_KEY_UTILITIES_EDITOR_MODE, true);

        if (is_string($stored) && $stored !== '') {
            return self::normalizeUtilitiesEditorMode($stored);
        }

        return self::MODE_SIMPLE;
    }

    public static function updateUtilitiesEditorMode(string $mode, ?int $userId = null): bool
    {
        $userId = self::resolveUserId($userId);

        if ($userId === 0) {
            return false;
        }

        $normalized = self::normalizeUtilitiesEditorMode($mode);

        return update_user_meta($userId, self::META_KEY_UTILITIES_EDITOR_MODE, $normalized) !== false;
    }

    public static function normalizeUtilitiesEditorMode(?string $mode): string
    {
        if ($mode === self::MODE_EXPERT) {
            return self::MODE_EXPERT;
        }

        return self::MODE_SIMPLE;
    }

    private static function resolveUserId(?int $userId = null): int
    {
        if (is_int($userId) && $userId > 0) {
            return $userId;
        }

        $currentUserId = get_current_user_id();

        return is_int($currentUserId) ? $currentUserId : 0;
    }
}
