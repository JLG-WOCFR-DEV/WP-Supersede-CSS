<?php declare(strict_types=1);

namespace SSC\Admin\Pages;

use SSC\Admin\AbstractPage;
use SSC\Support\CssRevisions;

if (!defined('ABSPATH')) {
    exit;
}

class DebugCenter extends AbstractPage
{
    public function render(): void
    {
        $log_entries = class_exists('\\SSC\\Infra\\Logger') ? \SSC\Infra\Logger::all() : [];

        if (function_exists('wp_localize_script')) {
            wp_localize_script(
                'ssc-debug-center',
                'sscDebugCenterL10n',
                [
                    'domain'  => 'supersede-css-jlg',
                    'strings' => [
                        'healthCheckCheckingLabel'       => __('Vérification...', 'supersede-css-jlg'),
                        'healthCheckRunningMessage'      => __('Vérification en cours...', 'supersede-css-jlg'),
                        'healthCheckSuccessMessage'      => __('Health Check terminé.', 'supersede-css-jlg'),
                        'healthCheckErrorMessage'        => __('Erreur lors du Health Check. Vérifiez la console du navigateur pour plus de détails.', 'supersede-css-jlg'),
                        'healthCheckRunLabel'            => __('Lancer Health Check', 'supersede-css-jlg'),
                        'confirmClearLog'                => __('Voulez-vous vraiment effacer tout le journal d\'activité ? Cette action est irréversible.', 'supersede-css-jlg'),
                        'clearLogSuccess'                => __('Journal effacé ! La page va se recharger.', 'supersede-css-jlg'),
                        'clearLogError'                  => __('Erreur lors de la suppression du journal.', 'supersede-css-jlg'),
                        'confirmResetAllCss'             => __("ATTENTION : Vous êtes sur le point de supprimer TOUT le CSS généré par Supersede. Cette action est irréversible.\n\nVoulez-vous vraiment continuer ?", 'supersede-css-jlg'),
                        'resetAllCssWorking'             => __('Réinitialisation...', 'supersede-css-jlg'),
                        'resetAllCssSuccess'             => __('Tout le CSS a été réinitialisé !', 'supersede-css-jlg'),
                        'resetAllCssLabel'               => __('Réinitialiser tout le CSS', 'supersede-css-jlg'),
                        'resetAllCssError'               => __('Erreur lors de la réinitialisation.', 'supersede-css-jlg'),
                        'restUnavailable'                => __('L’API REST est indisponible.', 'supersede-css-jlg'),
                        'revisionNotFound'               => __('Révision introuvable.', 'supersede-css-jlg'),
                        /* translators: %s: Option name associated with the revision. */
                        'confirmRestoreRevisionWithOption' => __('Restaurer la révision pour « %s » ?\nCette opération remplacera le CSS actuel.', 'supersede-css-jlg'),
                        'confirmRestoreRevision'         => __('Restaurer cette révision ?\nCette opération remplacera le CSS actuel.', 'supersede-css-jlg'),
                        'restoreWorking'                 => __('Restauration…', 'supersede-css-jlg'),
                        'restoreSuccess'                 => __('Révision restaurée. Actualisation de la page…', 'supersede-css-jlg'),
                        'restoreError'                   => __('Impossible de restaurer cette révision.', 'supersede-css-jlg'),
                    ],
                ]
            );
        }

        $this->render_view('debug-center', [
            'system_info' => [
                'plugin_version'    => defined('SSC_VERSION') ? SSC_VERSION : 'N/A',
                'wordpress_version' => get_bloginfo('version'),
                'php_version'       => phpversion(),
            ],
            'log_entries' => $log_entries,
            'css_revisions' => CssRevisions::all(),
        ]);
    }
}
