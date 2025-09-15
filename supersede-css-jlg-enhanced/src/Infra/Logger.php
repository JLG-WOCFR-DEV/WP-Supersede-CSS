<?php declare(strict_types=1);
namespace SSC\Infra;

if (!defined('ABSPATH')) { exit; }

/**
 * Maintains the structured admin log stored in the {@see self::OPT} option.
 *
 * Each log entry contains:
 * - `t`: ISO-8601 timestamp generated with {@see gmdate()}.
 * - `user`: login of the acting user or `anon` when not authenticated.
 * - `action`: sanitized identifier describing the action performed.
 * - `data`: associative array of contextual information where every value is
 *   sanitized with {@see sanitize_text_field()} to avoid persisting unsafe
 *   content.
 */
class Logger {
    const OPT = 'ssc_admin_log';
    const MAX = 50;

    /**
     * Adds a new entry to the log.
     *
     * @param string $action Machine-readable identifier for the action.
     * @param array<mixed>|scalar|null $data Contextual information associated
     *     with the log entry. The method expects an associative array whose
     *     values are scalars (or nested arrays of scalars) so the stored log
     *     remains consistent.
     */
    public static function add(string $action, $data = []): void {
        $log = get_option(self::OPT, []);
        if (!is_array($log)) {
            $log = [];
        }
        
        $user = wp_get_current_user();
        
        array_unshift($log, [
            't' => gmdate('c'),
            'user' => ($user && $user->ID) ? $user->user_login : 'anon',
            'action' => sanitize_text_field($action),
            'data' => self::sanitizeLogData($data)
        ]);
        
        if (count($log) > self::MAX) {
            $log = array_slice($log, 0, self::MAX);
        }
        
        update_option(self::OPT, $log, false);
    }

    /**
     * @param array<mixed>|scalar|null $data
     * @return array<mixed>
     */
    private static function sanitizeLogData($data): array {
        if (!is_array($data)) {
            $data = ['value' => $data];
        }

        $sanitized = [];

        foreach ($data as $key => $value) {
            if (!is_string($key)) {
                $key = (string) $key;
            }

            $key = sanitize_key($key);

            if (is_array($value)) {
                $sanitized[$key] = self::sanitizeLogData($value);
                continue;
            }

            if (is_object($value)) {
                $value = method_exists($value, '__toString') ? (string) $value : wp_json_encode($value);
            }

            if (is_bool($value)) {
                $value = $value ? '1' : '0';
            }

            if (!is_scalar($value) && null !== $value) {
                $value = wp_json_encode($value);
            }

            $sanitized[$key] = sanitize_text_field((string) $value);
        }

        return $sanitized;
    }

    public static function all(): array {
        return get_option(self::OPT, []);
    }

    public static function clear(): void {
        delete_option(self::OPT);
    }
}

