<?php declare(strict_types=1);

namespace SSC\Infra\Rest;

use SSC\Infra\Import\Sanitizer;
use SSC\Support\CssRevisions;
use SSC\Support\CssSanitizer;
use SSC\Support\TokenRegistry;
use WP_REST_Request;
use WP_REST_Response;

final class CssController extends BaseController
{
    public function __construct(private readonly Sanitizer $sanitizer)
    {
    }

    public function registerRoutes(): void
    {
        register_rest_route('ssc/v1', '/save-css', [
            'methods' => 'POST',
            'permission_callback' => [$this, 'authorizeRequest'],
            'callback' => [$this, 'saveCss'],
        ]);

        register_rest_route('ssc/v1', '/reset-all-css', [
            'methods' => 'POST',
            'permission_callback' => [$this, 'authorizeRequest'],
            'callback' => [$this, 'resetAllCss'],
        ]);

        register_rest_route(
            'ssc/v1',
            '/css-revisions/(?P<revision>[A-Za-z0-9_-]+)/restore',
            [
                'methods' => 'POST',
                'permission_callback' => [$this, 'authorizeRequest'],
                'callback' => [$this, 'restoreCssRevision'],
            ]
        );
    }

    public function saveCss(WP_REST_Request $request): WP_REST_Response
    {
        $css_raw = $request->get_param('css');
        $option_name = $request->get_param('option_name') ?: 'ssc_active_css';

        if (!is_string($option_name)) {
            $option_name = 'ssc_active_css';
        } else {
            $option_name = sanitize_key($option_name);
        }

        $allowed_options = ['ssc_active_css', 'ssc_tokens_css'];

        if (!in_array($option_name, $allowed_options, true)) {
            $option_name = 'ssc_active_css';
        }

        $append = rest_sanitize_boolean($request->get_param('append'));

        $sanitized_segments = ['desktop' => '', 'tablet' => '', 'mobile' => ''];
        $segment_payload = false;

        if ($option_name === 'ssc_active_css') {
            $segments_config = [
                'desktop' => ['param' => 'css_desktop', 'option' => 'ssc_css_desktop'],
                'tablet' => ['param' => 'css_tablet', 'option' => 'ssc_css_tablet'],
                'mobile' => ['param' => 'css_mobile', 'option' => 'ssc_css_mobile'],
            ];

            $pendingUpdates = [];

            foreach ($segments_config as $key => $config) {
                $raw_value = $request->get_param($config['param']);
                if ($raw_value !== null) {
                    $segment_payload = true;
                    if (!is_string($raw_value)) {
                        return new WP_REST_Response([
                            'ok' => false,
                            'message' => __('Invalid CSS segment.', 'supersede-css-jlg'),
                        ], 400);
                    }

                    $sanitized_value = $this->sanitizer->sanitizeCssSegment($raw_value);
                    $pendingUpdates[$config['option']] = $sanitized_value;
                    $sanitized_segments[$key] = $sanitized_value;
                } else {
                    $existing_value = get_option($config['option'], '');
                    $existing_value = is_string($existing_value) ? $existing_value : '';
                    $sanitized_value = CssSanitizer::sanitize($existing_value);
                    $sanitized_segments[$key] = $sanitized_value;

                    if ($sanitized_value !== $existing_value) {
                        update_option($config['option'], $sanitized_value, false);

                        if (function_exists('ssc_invalidate_css_cache')) {
                            ssc_invalidate_css_cache();
                        }
                    }
                }
            }

            foreach ($pendingUpdates as $option => $value) {
                update_option($option, $value, false);
            }
        }

        if ($segment_payload) {
            $incoming_css = $this->sanitizer->combineResponsiveCss($sanitized_segments);
            $append = false;
        } else {
            if (!is_string($css_raw)) {
                return new WP_REST_Response([
                    'ok' => false,
                    'message' => __('Invalid CSS.', 'supersede-css-jlg'),
                ], 400);
            }

            $incoming_css = CssSanitizer::sanitize(wp_unslash($css_raw));
        }

        $stored_css = get_option($option_name, '');
        $stored_css = is_string($stored_css) ? $stored_css : '';
        $existing_css = CssSanitizer::sanitize($stored_css);

        if ($append) {
            if ($incoming_css !== '' && strpos($existing_css, $incoming_css) === false) {
                $css_to_store = trim($existing_css . "\n\n" . $incoming_css);
            } else {
                $css_to_store = $existing_css;
            }
        } else {
            $css_to_store = $incoming_css;
        }

        $responsePayload = ['ok' => true];

        if ($option_name === 'ssc_tokens_css') {
            $tokens = TokenRegistry::convertCssToRegistry($css_to_store);
            $existingRegistry = TokenRegistry::getRegistry();
            $tokensWithMetadata = TokenRegistry::mergeMetadata($tokens, $existingRegistry);
            $sanitizedTokens = TokenRegistry::saveRegistry($tokensWithMetadata);
            if ($sanitizedTokens['duplicates'] !== []) {
                return new WP_REST_Response([
                    'ok' => false,
                    'message' => __('Some tokens use the same name. Please choose unique names before saving.', 'supersede-css-jlg'),
                    'duplicates' => $sanitizedTokens['duplicates'],
                ], 422);
            }
            $css_to_store = TokenRegistry::tokensToCss($sanitizedTokens['tokens']);
            $responsePayload['tokens'] = $sanitizedTokens['tokens'];
            $responsePayload['css'] = $css_to_store;
        } else {
            update_option($option_name, $css_to_store, false);
        }

        $revisionContext = [];
        if ($option_name === 'ssc_active_css') {
            $revisionContext['segments'] = $sanitized_segments;
        }

        CssRevisions::record($option_name, $css_to_store, $revisionContext);

        if (class_exists('\\SSC\\Infra\\Logger')) {
            \SSC\Infra\Logger::add('css_saved', ['size' => strlen($css_to_store) . ' bytes', 'option' => $option_name]);
        }

        if (function_exists('ssc_invalidate_css_cache')) {
            ssc_invalidate_css_cache();
        }

        return new WP_REST_Response($responsePayload, 200);
    }

    public function restoreCssRevision(WP_REST_Request $request): WP_REST_Response
    {
        $revisionId = $request->get_param('revision');
        if (!is_string($revisionId)) {
            return new WP_REST_Response([
                'ok' => false,
                'message' => __('Invalid revision identifier.', 'supersede-css-jlg'),
            ], 400);
        }

        $restored = CssRevisions::restore($revisionId);
        if ($restored === null) {
            return new WP_REST_Response([
                'ok' => false,
                'message' => __('Revision not found.', 'supersede-css-jlg'),
            ], 404);
        }

        if (is_array($restored) && isset($restored['error'])) {
            $duplicates = $restored['duplicates'] ?? [];

            return new \WP_Error(
                'ssc_tokens_conflict',
                __('Some tokens use the same name. Please choose unique names before restoring.', 'supersede-css-jlg'),
                [
                    'status' => 409,
                    'duplicates' => $duplicates,
                    'revision' => $restored['revision']['id'] ?? $revisionId,
                ]
            );
        }

        /** @var array{id: string, option: string, css: string, timestamp: string, author: string, segments?: array<string, string>} $restored */
        $context = [];
        if (($restored['option'] ?? '') === 'ssc_active_css' && isset($restored['segments']) && is_array($restored['segments'])) {
            $context['segments'] = $restored['segments'];
        }

        CssRevisions::record($restored['option'], $restored['css'], $context);

        if (class_exists('\\SSC\\Infra\\Logger')) {
            \SSC\Infra\Logger::add('css_revision_restored', [
                'revision' => $restored['id'],
                'option' => $restored['option'],
            ]);
        }

        return new WP_REST_Response([
            'ok' => true,
            'revision' => $restored['id'],
        ], 200);
    }

    public function resetAllCss(): WP_REST_Response
    {
        delete_option('ssc_active_css');
        delete_option('ssc_tokens_css');
        delete_option('ssc_tokens_registry');
        delete_option('ssc_css_desktop');
        delete_option('ssc_css_tablet');
        delete_option('ssc_css_mobile');

        if (function_exists('ssc_invalidate_css_cache')) {
            ssc_invalidate_css_cache();
        }

        if (class_exists('\\SSC\\Infra\\Logger')) {
            \SSC\Infra\Logger::add('css_resetted', []);
        }

        return new WP_REST_Response([
            'ok' => true,
            'message' => __('All CSS options have been reset.', 'supersede-css-jlg'),
        ]);
    }
}
