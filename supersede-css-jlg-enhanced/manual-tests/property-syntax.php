<?php declare(strict_types=1);

define('ABSPATH', __DIR__);

if (!function_exists('wp_kses')) {
    function wp_kses(string $string, array $allowed_html): string
    {
        return strip_tags($string);
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

require dirname(__DIR__) . '/src/Support/CssSanitizer.php';

use SSC\Support\CssSanitizer;

$css = <<<'CSS'
@property --angle {
    syntax: '<angle>';
    inherits: false;
    initial-value: 0deg;
}

.foo {
    background-image: url('javascript:alert(1)');
}

@import url("javascript:alert(2)");
CSS;

echo "Original CSS:\n" . $css . "\n\n";

$sanitized = CssSanitizer::sanitize($css);

echo "Sanitized CSS:\n" . $sanitized . "\n";
