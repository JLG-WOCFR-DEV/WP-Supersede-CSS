<?php declare(strict_types=1);

namespace SSC\Infra\Rest;

use SSC\Support\CssSanitizer;
use WP_REST_Request;
use WP_REST_Response;

final class PresetsController extends BaseController
{
    public function registerRoutes(): void
    {
        register_rest_route('ssc/v1', '/avatar-glow-presets', [
            [
                'methods' => 'GET',
                'permission_callback' => [$this, 'authorizeRequest'],
                'callback' => [$this, 'getAvatarGlowPresets'],
            ],
            [
                'methods' => 'POST',
                'permission_callback' => [$this, 'authorizeRequest'],
                'callback' => [$this, 'saveAvatarGlowPresets'],
            ],
        ]);

        register_rest_route('ssc/v1', '/presets', [
            [
                'methods' => 'GET',
                'permission_callback' => [$this, 'authorizeRequest'],
                'callback' => [$this, 'getPresets'],
            ],
            [
                'methods' => 'POST',
                'permission_callback' => [$this, 'authorizeRequest'],
                'callback' => [$this, 'savePresets'],
            ],
        ]);
    }

    public function getAvatarGlowPresets(): WP_REST_Response
    {
        $presets = get_option('ssc_avatar_glow_presets', []);
        $presets = is_array($presets) ? $presets : [];
        $presets = CssSanitizer::sanitizeAvatarGlowPresets($presets);

        return new WP_REST_Response($presets, 200);
    }

    public function saveAvatarGlowPresets(WP_REST_Request $request): WP_REST_Response
    {
        $payload = $request->get_json_params();

        if (!is_array($payload) || !array_key_exists('presets', $payload)) {
            $raw_presets = $request->get_param('presets');
            if (is_string($raw_presets)) {
                $decoded = json_decode(wp_unslash($raw_presets), true);
                $payload = ['presets' => $decoded];
            } elseif (is_array($raw_presets)) {
                $payload = ['presets' => $raw_presets];
            } else {
                $payload = [];
            }
        }

        $presets = $payload['presets'] ?? null;

        if (!is_array($presets)) {
            return new WP_REST_Response([
                'ok' => false,
                'message' => __('Invalid presets payload.', 'supersede-css-jlg'),
            ], 400);
        }

        $presets = CssSanitizer::sanitizeAvatarGlowPresets($presets);

        update_option('ssc_avatar_glow_presets', $presets, false);

        return new WP_REST_Response(['ok' => true], 200);
    }

    public function getPresets(): WP_REST_Response
    {
        $presets = get_option('ssc_presets', []);
        $presets = is_array($presets) ? $presets : [];
        $presets = CssSanitizer::sanitizePresetCollection($presets);

        return new WP_REST_Response($presets, 200);
    }

    public function savePresets(WP_REST_Request $request): WP_REST_Response
    {
        $payload = $request->get_json_params();

        if (!is_array($payload) || !array_key_exists('presets', $payload)) {
            $raw_presets = $request->get_param('presets');
            if (is_string($raw_presets)) {
                $decoded = json_decode(wp_unslash($raw_presets), true);
                $payload = ['presets' => $decoded];
            } elseif (is_array($raw_presets)) {
                $payload = ['presets' => $raw_presets];
            } else {
                $payload = [];
            }
        }

        $presets = $payload['presets'] ?? null;

        if (!is_array($presets)) {
            return new WP_REST_Response([
                'ok' => false,
                'message' => __('Invalid presets payload.', 'supersede-css-jlg'),
            ], 400);
        }

        $presets = CssSanitizer::sanitizePresetCollection($presets);

        update_option('ssc_presets', $presets, false);

        return new WP_REST_Response(['ok' => true], 200);
    }
}
