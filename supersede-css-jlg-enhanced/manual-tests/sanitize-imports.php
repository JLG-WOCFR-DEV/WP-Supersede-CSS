<?php declare(strict_types=1);

define('ABSPATH', __DIR__);

if (!function_exists('wp_kses')) {
    function wp_kses(string $string, array $allowed_html): string
    {
        return $string;
    }
}

if (!function_exists('wp_allowed_protocols')) {
    function wp_allowed_protocols(): array
    {
        return ['http', 'https'];
    }
}

if (!function_exists('wp_kses_bad_protocol')) {
    function wp_kses_bad_protocol(string $string, array $allowed_protocols)
    {
        $string = trim($string);

        $colonPosition = strpos($string, ':');
        if ($colonPosition === false) {
            return $string;
        }

        $protocol = strtolower(substr($string, 0, $colonPosition));
        if (!in_array($protocol, $allowed_protocols, true)) {
            return '';
        }

        return $string;
    }
}

if (!function_exists('safecss_filter_attr')) {
    function safecss_filter_attr(string $css): string
    {
        return $css;
    }
}

if (!function_exists('sanitize_key')) {
    function sanitize_key(string $key): string
    {
        $key = strtolower($key);
        return preg_replace('/[^a-z0-9_\-]/', '', $key) ?? '';
    }
}

if (!function_exists('sanitize_text_field')) {
    function sanitize_text_field(string $str): string
    {
        return trim($str);
    }
}

if (!function_exists('absint')) {
    function absint(int $number): int
    {
        return abs($number);
    }
}

require dirname(__DIR__) . '/src/Support/CssSanitizer.php';

use SSC\Support\CssSanitizer;

$examples = [
    '@import url("print.css") print;',
    '@import url("javascript:alert(1)") screen;',
    '@import url("styles.css") screen and (color) supports(display: grid) layer(theme);',
    '@import "no-url.css" screen;',
];

foreach ($examples as $example) {
    echo 'Original:   ' . $example . PHP_EOL;
    echo 'Sanitized:  ' . CssSanitizer::sanitize($example) . PHP_EOL;
    echo str_repeat('-', 40) . PHP_EOL;
}
