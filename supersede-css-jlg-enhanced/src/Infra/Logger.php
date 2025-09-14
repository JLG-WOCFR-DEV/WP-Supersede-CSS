<?php declare(strict_types=1);
namespace SSC\Infra;

if (!defined('ABSPATH')) { exit; }

class Logger {
    const OPT = 'ssc_admin_log';
    const MAX = 50;

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
            'data' => $data
        ]);
        
        if (count($log) > self::MAX) {
            $log = array_slice($log, 0, self::MAX);
        }
        
        update_option(self::OPT, $log, false);
    }

    public static function all(): array {
        return get_option(self::OPT, []);
    }

    public static function clear(): void {
        delete_option(self::OPT);
    }
}