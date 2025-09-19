<?php declare(strict_types=1);

\define('ABSPATH', __DIR__);

if (!\function_exists('wp_kses')) {
    function wp_kses(string $string, array $allowed_html): string
    {
        return strip_tags($string);
    }
}

if (!\function_exists('wp_allowed_protocols')) {
    function wp_allowed_protocols(): array
    {
        return ['http', 'https'];
    }
}

if (!\function_exists('wp_kses_bad_protocol')) {
    function wp_kses_bad_protocol(string $string, array $allowed_protocols)
    {
        return $string;
    }
}

if (!\function_exists('sanitize_hex_color')) {
    function sanitize_hex_color($color)
    {
        return $color;
    }
}

if (!\function_exists('sanitize_text_field')) {
    function sanitize_text_field($string)
    {
        return (string) $string;
    }
}

if (!\function_exists('esc_url_raw')) {
    function esc_url_raw($url)
    {
        return (string) $url;
    }
}

require \dirname(__DIR__) . '/src/Support/CssSanitizer.php';

use SSC\Support\CssSanitizer;

$tests = [
    'grid animation and transforms are preserved' => [
        'input' => '.layout { grid-template-columns: repeat(3, minmax(0, 1fr)); animation: fade-in 2s ease-in-out infinite; transform: translate3d(0, 10px, 0); filter: drop-shadow(0 0 2px rgba(0,0,0,.5)); }',
        'expected' => '.layout {grid-template-columns:repeat(3, minmax(0, 1fr)); animation:fade-in 2s ease-in-out infinite; transform:translate3d(0, 10px, 0); filter:drop-shadow(0 0 2px rgba(0,0,0,.5))}',
    ],
    'backdrop filter survives' => [
        'input' => '.frosted { backdrop-filter: blur(10px); }',
        'expected' => '.frosted {backdrop-filter:blur(10px)}',
    ],
    'javascript urls are stripped' => [
        'input' => '.danger { background-image: url("javascript:alert(1)"); }',
        'expected' => '',
    ],
    'html tags are removed' => [
        'input' => '.safe { color: red; }<script>alert(1)</script>',
        'expected' => '.safe {color:red}alert(1)',
    ],
];

foreach ($tests as $label => $test) {
    $sanitized = CssSanitizer::sanitize($test['input']);
    $status = $sanitized === $test['expected'] ? 'OK' : 'FAIL';

    echo $label . ':' . PHP_EOL;
    echo '  Input:     ' . $test['input'] . PHP_EOL;
    echo '  Sanitized: ' . $sanitized . PHP_EOL;
    echo '  Expected:  ' . $test['expected'] . PHP_EOL;
    echo '  Result:    ' . $status . PHP_EOL;
    echo str_repeat('-', 40) . PHP_EOL;

    if ($status === 'FAIL') {
        exit(1);
    }
}
