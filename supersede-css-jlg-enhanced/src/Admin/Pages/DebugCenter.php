<?php declare(strict_types=1);

namespace SSC\Admin\Pages;

use SSC\Admin\AbstractPage;
use SSC\Infra\Activity\ActivityLogRepository;
use SSC\Infra\Approvals\TokenApprovalStore;
use SSC\Infra\Capabilities\CapabilityManager;
use SSC\Support\CssRevisions;

if (!defined('ABSPATH')) {
    exit;
}

class DebugCenter extends AbstractPage
{
    public function render(): void
    {
        $approvalStore = new TokenApprovalStore();
        $activityRepository = null;
        $activityLog = [
            'entries' => [],
            'pagination' => [
                'total' => 0,
                'total_pages' => 1,
                'page' => 1,
            ],
            'filters' => [],
        ];

        try {
            $activityRepository = new ActivityLogRepository();
            $activityLog = $activityRepository->fetch(20, 1);
        } catch (\Throwable $exception) {
            // The activity log relies on the dedicated wp_ssc_activity_log table.
            // If the table is missing (e.g. migrations not executed yet) we keep
            // the UI functional by falling back to an empty dataset.
            $activityLog = [
                'entries' => [],
                'pagination' => [
                    'total' => 0,
                    'total_pages' => 1,
                    'page' => 1,
                ],
                'filters' => [],
            ];
        }

        $approvals = $approvalStore->all();
        $canReviewApprovals = current_user_can(CapabilityManager::getApprovalCapability());

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
                        'healthCheckErrorPersistent'     => __('Impossible de récupérer les données du diagnostic. Réessayez ou consultez Santé du site.', 'supersede-css-jlg'),
                        'healthCheckRunLabel'            => __('Lancer Health Check', 'supersede-css-jlg'),
                        'confirmClearLog'                => __('Voulez-vous vraiment effacer tout le journal d\'activité ? Cette action est irréversible.', 'supersede-css-jlg'),
                        'clearLogSuccess'                => __('Journal effacé ! La page va se recharger.', 'supersede-css-jlg'),
                        'clearLogError'                  => __('Erreur lors de la suppression du journal.', 'supersede-css-jlg'),
                        'confirmResetAllCss'             => __("ATTENTION : Vous êtes sur le point de supprimer TOUT le CSS généré par Supersede. Cette action est irréversible.\n\nVoulez-vous vraiment continuer ?", 'supersede-css-jlg'),
                        'confirmResetAllCssSecondary'    => __('Confirmez une seconde fois : cette action est définitive et supprimera toutes vos personnalisations CSS.', 'supersede-css-jlg'),
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
                        'healthSummaryGeneratedAt'       => __('Diagnostic généré le %s', 'supersede-css-jlg'),
                        'healthSummaryCacheHit'          => __('Réponse servie depuis le cache (expire dans %s).', 'supersede-css-jlg'),
                        'healthSummaryCacheHitExpiresAt' => __('Réponse servie depuis le cache (expiration le %s).', 'supersede-css-jlg'),
                        'healthSummaryCacheHitNoExpiry'  => __('Réponse servie depuis le cache.', 'supersede-css-jlg'),
                        'healthSummaryCacheMiss'         => __('Réponse recalculée à la demande.', 'supersede-css-jlg'),
                        'healthSummaryCacheDisabled'     => __('Cache désactivé pour ce diagnostic.', 'supersede-css-jlg'),
                        'durationLessThanSecond'         => __('moins d’une seconde', 'supersede-css-jlg'),
                        'durationSeconds'                => __('%d seconde(s)', 'supersede-css-jlg'),
                        'durationMinutes'                => __('%d minute(s)', 'supersede-css-jlg'),
                        'durationHours'                  => __('%d heure(s)', 'supersede-css-jlg'),
                        'durationDays'                   => __('%d jour(s)', 'supersede-css-jlg'),
                        'visualDebugToggleOnLabel'       => __('Désactiver le débogage visuel', 'supersede-css-jlg'),
                        'visualDebugToggleOffLabel'      => __('Activer le débogage visuel', 'supersede-css-jlg'),
                        'visualDebugEnabledMessage'      => __('Débogage visuel actif. Les surfaces sont annotées dans toute l’interface.', 'supersede-css-jlg'),
                        'visualDebugDisabledMessage'     => __('Débogage visuel inactif.', 'supersede-css-jlg'),
                        'visualDebugEnabledToast'        => __('Débogage visuel activé.', 'supersede-css-jlg'),
                        'visualDebugDisabledToast'       => __('Débogage visuel désactivé.', 'supersede-css-jlg'),
                        'visualDebugPersistedNotice'     => __('Préférence sauvegardée pour toutes les pages Supersede CSS.', 'supersede-css-jlg'),
                        'approvalsEmptyState'            => __('Aucune demande d’approbation à afficher pour ce filtre.', 'supersede-css-jlg'),
                        'approvalsRefreshLabel'          => __('Actualiser les demandes', 'supersede-css-jlg'),
                        'approvalsDecisionApprove'       => __('Approuver', 'supersede-css-jlg'),
                        'approvalsDecisionRequestChanges'=> __('Demander des changements', 'supersede-css-jlg'),
                        'approvalsDecisionConfirmApprove'=> __('Confirmez-vous l’approbation de ce token ?', 'supersede-css-jlg'),
                        'approvalsDecisionConfirmChanges'=> __('Confirmez-vous la demande de changements ? Un commentaire est requis.', 'supersede-css-jlg'),
                        'approvalsDecisionPromptComment' => __('Précisez un commentaire pour guider l’auteur :', 'supersede-css-jlg'),
                        'approvalsDecisionCommentRequired' => __('Un commentaire est obligatoire pour demander des changements.', 'supersede-css-jlg'),
                        'approvalsDecisionSuccess'       => __('Décision enregistrée.', 'supersede-css-jlg'),
                        'approvalsDecisionError'         => __('Impossible d’enregistrer la décision.', 'supersede-css-jlg'),
                        'approvalsFetchError'            => __('Impossible de récupérer les demandes d’approbation.', 'supersede-css-jlg'),
                        'approvalsNoComment'             => __('Aucun commentaire fourni lors de la demande.', 'supersede-css-jlg'),
                        'approvalsNoActions'             => __('Aucune action disponible.', 'supersede-css-jlg'),
                        'approvalStatusPending'          => __('En attente', 'supersede-css-jlg'),
                        'approvalStatusApproved'         => __('Approuvé', 'supersede-css-jlg'),
                        'approvalStatusChangesRequested' => __('Changements demandés', 'supersede-css-jlg'),
                        'approvalStatusUnknown'          => __('Statut inconnu', 'supersede-css-jlg'),
                        'approvalsRequestedBy'           => __('Demandé par %s', 'supersede-css-jlg'),
                        'approvalsRequestedAt'           => __('Envoyé le %s', 'supersede-css-jlg'),
                        'approvalsDecisionBy'            => __('Décision : %1$s le %2$s', 'supersede-css-jlg'),
                        'activityLoading'                => __('Chargement du journal…', 'supersede-css-jlg'),
                        'activityEmpty'                  => __('Aucune entrée ne correspond aux filtres appliqués.', 'supersede-css-jlg'),
                        'activityFetchError'             => __('Impossible de charger le journal d’activité.', 'supersede-css-jlg'),
                        'activityPaginationSummary'      => __('Page %1$s sur %2$s — %3$s entrée(s)', 'supersede-css-jlg'),
                        'activityFiltersCleared'         => __('Filtres réinitialisés.', 'supersede-css-jlg'),
                        'activityExportError'            => __('Impossible d’exporter le journal.', 'supersede-css-jlg'),
                        'activityExportPreparing'        => __('Préparation de l’export…', 'supersede-css-jlg'),
                        'activityExportReady'            => __('Export prêt.', 'supersede-css-jlg'),
                        'activitySystemUser'             => __('Système', 'supersede-css-jlg'),
                        'activityColumnDate'             => __('Date', 'supersede-css-jlg'),
                        'activityColumnEvent'            => __('Événement', 'supersede-css-jlg'),
                        'activityColumnEntity'           => __('Entité', 'supersede-css-jlg'),
                        'activityColumnAuthor'           => __('Auteur', 'supersede-css-jlg'),
                        'activityColumnDetails'          => __('Détails', 'supersede-css-jlg'),
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
            'approvals' => $approvals,
            'can_review_approvals' => $canReviewApprovals,
            'activity_log' => $activityLog,
            'css_revisions' => CssRevisions::all(),
        ]);
    }
}
