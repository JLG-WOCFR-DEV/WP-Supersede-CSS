<?php
declare(strict_types=1);

use SSC\Tests\Support\WordPressInstaller;

$_tests_dir = getenv('WP_TESTS_DIR');
if (!$_tests_dir) {
    $_tests_dir = dirname(__DIR__) . '/vendor/wp-phpunit/wp-phpunit';
}

require __DIR__ . '/Support/WordPressInstaller.php';

$wordpressAvailable = true;

try {
    WordPressInstaller::ensure();
} catch (\RuntimeException $exception) {
    $wordpressAvailable = false;
    fwrite(
        STDERR,
        '[wp-phpunit] WordPress test suite unavailable: ' . $exception->getMessage() . PHP_EOL .
        'Falling back to offline bootstrap (tests relying on WordPress will be skipped).' . PHP_EOL
    );
}

if ($wordpressAvailable && file_exists($_tests_dir . '/includes/functions.php')) {
    require $_tests_dir . '/includes/functions.php';

    require dirname(__DIR__) . '/vendor/autoload.php';

    tests_add_filter('muplugins_loaded', static function (): void {
        require dirname(__DIR__) . '/supersede-css-jlg.php';
    });

    require $_tests_dir . '/includes/bootstrap.php';

    if (function_exists('wp_set_current_user')) {
        $username = 'revision_tester';
        $user = null;

        if (function_exists('get_user_by')) {
            $user = get_user_by('login', $username) ?: null;
        }

        if ($user === null && function_exists('username_exists')) {
            $existingId = username_exists($username);
            if (is_int($existingId) && $existingId > 0 && function_exists('get_user_by')) {
                $user = get_user_by('id', $existingId) ?: null;
            }
        }

        if ($user === null && function_exists('wp_insert_user')) {
            if (function_exists('wp_generate_password')) {
                $password = wp_generate_password(20, true);
            } else {
                try {
                    $password = bin2hex(random_bytes(12));
                } catch (Throwable $exception) {
                    unset($exception);
                    $password = substr(hash('sha256', (string) microtime(true)), 0, 24);
                }
            }

            $createdUserId = wp_insert_user([
                'user_login' => $username,
                'user_pass' => $password,
                'user_email' => $username . '@example.org',
                'role' => 'administrator',
            ]);

            if (is_int($createdUserId) && $createdUserId > 0 && function_exists('get_user_by')) {
                $user = get_user_by('id', $createdUserId) ?: null;
            }

            if ($user === null) {
                if (function_exists('is_wp_error') && is_wp_error($createdUserId)) {
                    $existingLogin = $createdUserId->get_error_data('existing_user_login');
                    if (is_string($existingLogin) && $existingLogin !== '' && function_exists('get_user_by')) {
                        $user = get_user_by('login', $existingLogin) ?: null;
                    }
                }

                if ($user === null && function_exists('username_exists')) {
                    $existingId = username_exists($username);
                    if (is_int($existingId) && $existingId > 0 && function_exists('get_user_by')) {
                        $user = get_user_by('id', $existingId) ?: null;
                    }
                }
            }
        }

        if ($user instanceof WP_User) {
            wp_set_current_user($user->ID);
        }
    }

    return;
}

require __DIR__ . '/offline/bootstrap.php';
