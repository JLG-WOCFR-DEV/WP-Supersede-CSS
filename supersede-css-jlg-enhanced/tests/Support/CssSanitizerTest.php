<?php declare(strict_types=1);

use ReflectionClass;
use SSC\Support\CssSanitizer;

class CssSanitizerTest extends WP_UnitTestCase
{
    public function test_literal_braces_and_behavior_property(): void
    {
        $cssWithLiteralBrace = '.foo::before { content: "{"; behavior: url(http://evil); }';
        $sanitizedWithLiteralBrace = CssSanitizer::sanitize($cssWithLiteralBrace);

        $this->assertSame(
            '.foo::before {content:"{"}',
            $sanitizedWithLiteralBrace
        );
        $this->assertStringNotContainsString('behavior', $sanitizedWithLiteralBrace);

        $cssWithDoubleBrace = '.foo::before { content: "{}"; behavior: url(http://evil); }';
        $sanitizedWithDoubleBrace = CssSanitizer::sanitize($cssWithDoubleBrace);

        $this->assertSame(
            '.foo::before {content:"{}"}',
            $sanitizedWithDoubleBrace
        );
        $this->assertStringNotContainsString('behavior', $sanitizedWithDoubleBrace);
    }

    public function test_custom_properties_and_font_face_rules(): void
    {
        $customPropertyWithScrollBehavior = ':root { --snippet: scroll-behavior: smooth; color: red; }';
        $sanitizedCustomProperty = CssSanitizer::sanitize($customPropertyWithScrollBehavior);
        $this->assertSame(1, preg_match('/scroll-behavior:\\s*smooth/', $sanitizedCustomProperty));

        $fontFaceCss = "@font-face { font-family: 'Custom'; src: url('https://example.com/font.woff2') format('woff2'), url('data:font/woff2;base64,AAAA'), url('javascript:alert(1)'); font-display: swap; unicode-range: U+000-5FF; }";
        $sanitizedFontFace = CssSanitizer::sanitize($fontFaceCss);

        $this->assertSame(
            "@font-face {font-family:'Custom'; src:url('https://example.com/font.woff2') format('woff2'), url('data:font/woff2;base64,AAAA'); font-display:swap; unicode-range:U+000-5FF}",
            $sanitizedFontFace
        );
        $this->assertStringNotContainsString('javascript', $sanitizedFontFace);
        $this->assertStringNotContainsString(',;', $sanitizedFontFace);
    }

    public function test_background_media_supports_and_keyframes_cleanup(): void
    {
        $multiBackgroundCss = "body { background-image: url('javascript:alert(1)'), url('https://example.com/safe.png'), url('data:image/png;base64,AAAA'); }";
        $sanitizedMultiBackground = CssSanitizer::sanitize($multiBackgroundCss);

        $this->assertSame(
            "body {background-image:url('https://example.com/safe.png'), url('data:image/png;base64,AAAA')}",
            $sanitizedMultiBackground
        );
        $this->assertStringNotContainsString('javascript:alert(1)', $sanitizedMultiBackground);

        $mediaCss = '@media screen and (min-width: 600px) { .foo { color: red; behavior: url(http://evil); } }';
        $sanitizedMedia = CssSanitizer::sanitize($mediaCss);
        $this->assertSame(
            '@media screen and (min-width: 600px) {.foo {color:red}}',
            $sanitizedMedia
        );
        $this->assertStringNotContainsString('behavior', $sanitizedMedia);

        $supportsCss = '@supports (display: grid) { .grid { display: grid; behavior: url(https://evil); } }';
        $sanitizedSupports = CssSanitizer::sanitize($supportsCss);
        $this->assertSame(
            '@supports (display: grid) {.grid {display:grid}}',
            $sanitizedSupports
        );
        $this->assertStringNotContainsString('behavior', $sanitizedSupports);

        $keyframesCss = '@keyframes spin { from { transform: rotate(0deg); behavior: url(https://evil); } 50% { transform: rotate(180deg); } to { transform: rotate(360deg); } }';
        $sanitizedKeyframes = CssSanitizer::sanitize($keyframesCss);
        $this->assertSame(
            '@keyframes spin {from {transform:rotate(0deg)} 50% {transform:rotate(180deg)} to {transform:rotate(360deg)}}',
            $sanitizedKeyframes
        );
        $this->assertStringNotContainsString('behavior', $sanitizedKeyframes);
    }

    public function test_container_rule_preserves_container_properties(): void
    {
        $css = '@container layout (min-width: 500px) { .card { container-type: inline-size; container-name: layout; } }';
        $sanitized = CssSanitizer::sanitize($css);

        $this->assertSame(
            '@container layout (min-width: 500px) {.card {container-type:inline-size; container-name:layout}}',
            $sanitized
        );
    }

    public function test_text_wrap_and_text_size_adjust_are_preserved(): void
    {
        $css = '.hero { text-wrap: balance; text-size-adjust: 100%; }';
        $sanitized = CssSanitizer::sanitize($css);

        $this->assertSame('.hero {text-wrap:balance; text-size-adjust:100%}', $sanitized);
    }

    public function test_import_rules_and_quoted_exploit_handling(): void
    {
        $cssWithDanglingImport = "@import url(\"foo.css\")\nbody { color: red; }";
        $sanitizedDanglingImport = CssSanitizer::sanitize($cssWithDanglingImport);
        $this->assertSame('body {color:red}', $sanitizedDanglingImport);

        $quotedExploit = '.foo { content: "</style><script>alert(1)</script>"; color: blue; } .bar { color: __SSC_CSS_TOKEN_0__; }';
        $sanitizedExploit = CssSanitizer::sanitize($quotedExploit);
        $this->assertStringNotContainsString('</style>', $sanitizedExploit);
        $this->assertStringNotContainsString('<script', $sanitizedExploit);
        $this->assertStringNotContainsString('__SSC_CSS_TOKEN_', $sanitizedExploit);
    }

    public function test_preset_collection_sanitization(): void
    {
        $presetWithBraceSelector = CssSanitizer::sanitizePresetCollection([
            'dangerous' => [
                'name' => 'Danger',
                'scope' => '.foo { color: red; }',
                'props' => [
                    'color' => 'red',
                ],
            ],
        ]);
        $this->assertSame('', $presetWithBraceSelector['dangerous']['scope']);

        $presetWithDirectiveSelector = CssSanitizer::sanitizePresetCollection([
            [
                'name' => 'Directive',
                'scope' => '@media (min-width: 600px)',
                'props' => [
                    'color' => 'blue',
                ],
            ],
        ]);
        $this->assertSame('', $presetWithDirectiveSelector['preset_0']['scope']);
    }

    public function test_internal_url_sanitizer_preserves_literals(): void
    {
        $reflection = new ReflectionClass(CssSanitizer::class);
        $sanitizeUrls = $reflection->getMethod('sanitizeUrls');
        $sanitizeUrls->setAccessible(true);

        $this->assertSame(
            'content: "url(foo)"',
            $sanitizeUrls->invoke(null, 'content: "url(foo)"')
        );
        $this->assertSame(
            "content: 'url(bar)'",
            $sanitizeUrls->invoke(null, "content: 'url(bar)'")
        );
        $this->assertSame(
            '/* url(foo) inside comment */',
            $sanitizeUrls->invoke(null, '/* url(foo) inside comment */')
        );
        $this->assertSame(
            'background: url("https://example.com/image.png")',
            $sanitizeUrls->invoke(null, 'background: url(https://example.com/image.png)')
        );
        $this->assertSame(
            'background:url("https://example.com/image.png")',
            $sanitizeUrls->invoke(null, 'background:url ( "https://example.com/image.png" )')
        );
        $this->assertSame(
            'background:)',
            $sanitizeUrls->invoke(null, 'background: url(javascript:alert(1))')
        );
        $this->assertSame(
            'background:',
            $sanitizeUrls->invoke(null, "background:url ( '\\6a\\61\\76\\61\\73\\63\\72\\69\\70\\74:alert(1)' )")
        );
        $this->assertSame(
            'background:',
            $sanitizeUrls->invoke(null, 'background: url("\\6a\\61\\76\\61\\73\\63\\72\\69\\70\\74:alert(1)")')
        );
        $this->assertStringNotContainsString(
            '__SSC_CSS_TOKEN_',
            $sanitizeUrls->invoke(null, '__SSC_CSS_TOKEN_0__')
        );
    }

    public function test_internal_import_sanitizer_preserves_literals(): void
    {
        $reflection = new ReflectionClass(CssSanitizer::class);
        $sanitizeImports = $reflection->getMethod('sanitizeImports');
        $sanitizeImports->setAccessible(true);

        $this->assertStringNotContainsString(
            '__SSC_CSS_TOKEN_',
            $sanitizeImports->invoke(null, '@import url(https://example.com); __SSC_CSS_TOKEN_1__')
        );
        $this->assertSame(
            '@import url("https://example.com/style.css");',
            $sanitizeImports->invoke(null, '@import "https://example.com/style.css";')
        );
        $this->assertSame(
            '@import url("https://example.com/style.css");',
            $sanitizeImports->invoke(null, '@import/*comment*/ url("https://example.com/style.css");')
        );
        $this->assertSame(
            'content: "@import url(foo)"',
            $sanitizeImports->invoke(null, 'content: "@import url(foo)"')
        );
        $this->assertSame(
            '--example: "@import url(foo)"',
            $sanitizeImports->invoke(null, '--example: "@import url(foo)"')
        );

        $cssWithDanglingImport = '@import url("https://example.com/style.css")' . PHP_EOL . 'body { color: red; }';
        $sanitizedDanglingImport = CssSanitizer::sanitize($cssWithDanglingImport);
        $this->assertSame('body {color:red}', $sanitizedDanglingImport);
    }

    public function test_dangling_blocks_remove_disallowed_declarations(): void
    {
        $danglingBlockCss = 'body { width: expression(alert(1))';
        $sanitizedDanglingBlock = CssSanitizer::sanitize($danglingBlockCss);
        $this->assertStringNotContainsString('expression', $sanitizedDanglingBlock);
        $this->assertStringNotContainsString('width', $sanitizedDanglingBlock);

        $imageSetCss = 'div { background-image: image-set(url("javascript:alert(1)") 1x type("image/png"), url("https://example.com/safe.png") 2x type("image/png")); }';
        $sanitizedImageSet = CssSanitizer::sanitize($imageSetCss);
        $this->assertSame(
            'div {background-image:image-set(url("https://example.com/safe.png") 2x type("image/png"))}',
            $sanitizedImageSet
        );
    }
}
