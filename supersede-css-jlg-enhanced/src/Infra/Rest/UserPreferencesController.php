<?php declare(strict_types=1);

namespace SSC\Infra\Rest;

use SSC\Support\UserPreferences;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

final class UserPreferencesController extends BaseController
{
    public function registerRoutes(): void
    {
        register_rest_route(
            'ssc/v1',
            '/user-preferences',
            [
                [
                    'methods' => WP_REST_Server::READABLE,
                    'callback' => [$this, 'getPreferences'],
                    'permission_callback' => [$this, 'authorizeRequest'],
                ],
                [
                    'methods' => WP_REST_Server::CREATABLE,
                    'callback' => [$this, 'updatePreferences'],
                    'permission_callback' => [$this, 'authorizeRequest'],
                    'args' => [
                        'utilities_editor_mode' => [
                            'type' => 'string',
                            'required' => false,
                            'sanitize_callback' => 'sanitize_text_field',
                            'validate_callback' => [$this, 'validateEditorMode'],
                        ],
                    ],
                ],
            ]
        );
    }

    public function getPreferences(WP_REST_Request $request): WP_REST_Response
    {
        return new WP_REST_Response([
            'utilities_editor_mode' => UserPreferences::getUtilitiesEditorMode(),
        ]);
    }

    /**
     * @return WP_REST_Response|WP_Error
     */
    public function updatePreferences(WP_REST_Request $request)
    {
        $modeParam = $request->get_param('utilities_editor_mode');
        $normalized = UserPreferences::normalizeUtilitiesEditorMode(is_string($modeParam) ? $modeParam : null);

        $updated = UserPreferences::updateUtilitiesEditorMode($normalized);

        if (!$updated) {
            return new WP_Error(
                'ssc_user_preferences_update_failed',
                __('Impossible d\'enregistrer votre préférence utilisateur.', 'supersede-css-jlg'),
                ['status' => 500]
            );
        }

        return new WP_REST_Response([
            'utilities_editor_mode' => $normalized,
        ]);
    }

    public function validateEditorMode($value): bool
    {
        if ($value === null) {
            return true;
        }

        if (!is_string($value)) {
            return false;
        }

        $normalized = UserPreferences::normalizeUtilitiesEditorMode($value);

        return in_array($normalized, [UserPreferences::MODE_SIMPLE, UserPreferences::MODE_EXPERT], true);
    }
}
