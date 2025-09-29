<?php declare(strict_types=1);

namespace SSC\Admin\Pages;

use SSC\Admin\AbstractPage;
use SSC\Infra\CssRevisions;

if (!defined('ABSPATH')) {
    exit;
}

class DebugCenter extends AbstractPage
{
    public function render(): void
    {
        $log_entries = class_exists('\SSC\Infra\Logger') ? \SSC\Infra\Logger::all() : [];
        $notice = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ssc_restore_revision'])) {
            if (function_exists('check_admin_referer')) {
                check_admin_referer('ssc_restore_revision');
            }

            $revision_id = isset($_POST['ssc_revision_id']) ? sanitize_text_field((string) $_POST['ssc_revision_id']) : '';

            $authorized = true;

            if (function_exists('ssc_get_required_capability') && function_exists('current_user_can')) {
                $required_capability = ssc_get_required_capability();
                $authorized = current_user_can($required_capability);
            }

            if ($authorized && $revision_id !== '') {
                if (CssRevisions::restore($revision_id)) {
                    $notice = [
                        'type' => 'success',
                        'message' => __('La révision a été restaurée avec succès.', 'supersede-css-jlg'),
                    ];
                } else {
                    $notice = [
                        'type' => 'error',
                        'message' => __('Impossible de restaurer la révision sélectionnée.', 'supersede-css-jlg'),
                    ];
                }
            } else {
                $notice = [
                    'type' => 'error',
                    'message' => __('Action non autorisée ou identifiant invalide.', 'supersede-css-jlg'),
                ];
            }
        }

        $revisions = class_exists('\SSC\Infra\CssRevisions') ? CssRevisions::all() : [];

        $this->render_view('debug-center', [
            'system_info' => [
                'plugin_version'    => defined('SSC_VERSION') ? SSC_VERSION : 'N/A',
                'wordpress_version' => get_bloginfo('version'),
                'php_version'       => phpversion(),
            ],
            'log_entries' => $log_entries,
            'css_revisions' => $revisions,
            'revision_notice' => $notice,
        ]);
    }
}
