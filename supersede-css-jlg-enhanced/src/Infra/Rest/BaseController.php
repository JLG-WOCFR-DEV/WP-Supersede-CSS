<?php declare(strict_types=1);

namespace SSC\Infra\Rest;

use WP_Error;
use WP_REST_Request;

abstract class BaseController implements ControllerInterface
{
    /**
     * @return bool|WP_Error
     */
    public function authorizeRequest(WP_REST_Request $request)
    {
        $nonce = $request->get_param('_wpnonce');

        if (!is_string($nonce) || $nonce === '') {
            $nonce = $request->get_header('x-wp-nonce');
        }

        $nonceProvided = is_string($nonce) && $nonce !== '';

        if ($nonceProvided) {
            $nonce = (string) wp_unslash($nonce);

            if (!wp_verify_nonce($nonce, 'wp_rest')) {
                return new WP_Error(
                    'rest_forbidden',
                    __('Invalid nonce.', 'supersede-css-jlg'),
                    ['status' => 403]
                );
            }
        } elseif (!$this->requestHasNonCookieAuthentication($request)) {
            return new WP_Error(
                'rest_forbidden',
                __('Invalid nonce.', 'supersede-css-jlg'),
                ['status' => 403]
            );
        }

        $required_capability = ssc_get_required_capability();

        if (!current_user_can($required_capability)) {
            return new WP_Error(
                'rest_forbidden',
                __('You are not allowed to access this endpoint.', 'supersede-css-jlg'),
                ['status' => 403]
            );
        }

        return true;
    }

    private function requestHasNonCookieAuthentication(WP_REST_Request $request): bool
    {
        $authorizationHeader = $request->get_header('authorization');

        if (is_string($authorizationHeader) && $authorizationHeader !== '') {
            return true;
        }

        if (function_exists('apply_filters')) {
            return (bool) apply_filters('ssc_request_has_non_cookie_auth', false, $request);
        }

        return false;
    }
}
