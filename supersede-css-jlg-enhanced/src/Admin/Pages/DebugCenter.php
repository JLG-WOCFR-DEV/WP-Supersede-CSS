<?php declare(strict_types=1);

namespace SSC\Admin\Pages;

use SSC\Admin\AbstractPage;
use SSC\Infra\Activity\ActivityLogRepository;
use SSC\Infra\Approvals\TokenApprovalStore;
use SSC\Infra\Capabilities\CapabilityManager;
use SSC\Support\CssRevisions;
use SSC\Support\TokenRegistry;

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
        $canExportTokens = current_user_can(CapabilityManager::getExportCapability());
        $tokenRegistry = TokenRegistry::getRegistry();
        $tokenStatuses = TokenRegistry::getSupportedStatuses();
        $approvalSlaRules = [
            'low' => ['hours' => 120],
            'normal' => ['hours' => 48],
            'high' => ['hours' => 12],
        ];

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
                        'approvalsOpenReview'            => __('Examiner', 'supersede-css-jlg'),
                        'approvalsReviewTitle'           => __('Revue de token', 'supersede-css-jlg'),
                        'approvalsReviewSummary'         => __('Résumé', 'supersede-css-jlg'),
                        'approvalsReviewStatusLabel'     => __('Statut', 'supersede-css-jlg'),
                        'approvalsReviewPriorityLabel'   => __('Priorité', 'supersede-css-jlg'),
                        'approvalsReviewOwnerLabel'      => __('Référent', 'supersede-css-jlg'),
                        'approvalsReviewVersionLabel'    => __('Version', 'supersede-css-jlg'),
                        'approvalsReviewContextLabel'    => __('Contexte', 'supersede-css-jlg'),
                        'approvalsReviewTypeLabel'       => __('Type', 'supersede-css-jlg'),
                        'approvalsReviewValueLabel'      => __('Valeur CSS', 'supersede-css-jlg'),
                        'approvalsReviewValueUnavailable'=> __('Valeur indisponible.', 'supersede-css-jlg'),
                        'approvalsReviewCopyValue'       => __('Copier la valeur CSS', 'supersede-css-jlg'),
                        'approvalsReviewCopySuccess'     => __('Valeur copiée dans le presse-papiers.', 'supersede-css-jlg'),
                        'approvalsReviewCopyError'       => __('Impossible de copier la valeur.', 'supersede-css-jlg'),
                        'approvalsReviewLinkedComponentsLabel' => __('Composants liés', 'supersede-css-jlg'),
                        'approvalsReviewLinkedComponentsEmpty' => __('Aucun composant référencé.', 'supersede-css-jlg'),
                        'approvalsReviewChangelogLabel'  => __('Changelog', 'supersede-css-jlg'),
                        'approvalsReviewChangelogEmpty'  => __('Aucune note pour ce token.', 'supersede-css-jlg'),
                        'approvalsReviewCommentsTitle'   => __('Commentaires de la demande', 'supersede-css-jlg'),
                        'approvalsReviewRequesterLabel'  => __('Demande initiale', 'supersede-css-jlg'),
                        'approvalsReviewDecisionLabel'   => __('Dernière décision', 'supersede-css-jlg'),
                        'approvalsReviewNoDecision'      => __('Aucune décision enregistrée pour le moment.', 'supersede-css-jlg'),
                        'approvalsReviewTimelineTitle'   => __('Historique d’activité', 'supersede-css-jlg'),
                        'approvalsReviewTimelineLoading' => __('Chargement de l’historique…', 'supersede-css-jlg'),
                        'approvalsReviewTimelineEmpty'   => __('Aucune activité récente pour ce token.', 'supersede-css-jlg'),
                        'approvalsReviewTimelineError'   => __('Impossible de charger l’historique.', 'supersede-css-jlg'),
                        'approvalsReviewCloseLabel'      => __('Fermer la revue', 'supersede-css-jlg'),
                        'approvalsReviewMissingToken'    => __('Le token associé est introuvable ou a été supprimé.', 'supersede-css-jlg'),
                        'approvalsReviewSlaLabel'        => __('SLA', 'supersede-css-jlg'),
                        'approvalsReviewSlaTarget'       => __('Délai cible : %s', 'supersede-css-jlg'),
                        'approvalsReviewSlaRemaining'    => __('Temps restant : %s', 'supersede-css-jlg'),
                        'approvalsReviewSlaOverdue'      => __('Retard de %s', 'supersede-css-jlg'),
                        'approvalsReviewSlaMet'          => __('Revue clôturée dans les temps.', 'supersede-css-jlg'),
                        'approvalsReviewSlaLate'         => __('Clôturée avec %s de retard.', 'supersede-css-jlg'),
                        'approvalsReviewOpenedAgo'       => __('Demande ouverte depuis %s', 'supersede-css-jlg'),
                        'approvalsReviewRequestedAt'     => __('Demande envoyée le %s', 'supersede-css-jlg'),
                        'approvalsReviewDecisionAt'      => __('Décision enregistrée le %s', 'supersede-css-jlg'),
                        'approvalsTimelineTokenCreated'  => __('Token créé', 'supersede-css-jlg'),
                        'approvalsTimelineTokenUpdated'  => __('Token mis à jour', 'supersede-css-jlg'),
                        'approvalsTimelineTokenApproved' => __('Token approuvé', 'supersede-css-jlg'),
                        'approvalsTimelineTokenDeprecated' => __('Token déprécié', 'supersede-css-jlg'),
                        'approvalsTimelineApprovalRequested' => __('Demande d’approbation', 'supersede-css-jlg'),
                        'approvalsTimelineApprovalChangesRequested' => __('Changements demandés', 'supersede-css-jlg'),
                        'approvalsTimelineCssPublished'  => __('CSS publié', 'supersede-css-jlg'),
                        'approvalsTimelinePresetChanged' => __('Preset mis à jour', 'supersede-css-jlg'),
                        'approvalsTimelineExportGenerated' => __('Export généré', 'supersede-css-jlg'),
                        'approvalPriorityColumn'         => __('Priorité', 'supersede-css-jlg'),
                        'approvalPriorityLow'            => __('Faible', 'supersede-css-jlg'),
                        'approvalPriorityNormal'         => __('Normale', 'supersede-css-jlg'),
                        'approvalPriorityHigh'           => __('Haute', 'supersede-css-jlg'),
                        'approvalPriorityUnknown'        => __('Priorité inconnue', 'supersede-css-jlg'),
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
                        'activityColumnResource'         => __('Ressource', 'supersede-css-jlg'),
                        'activityColumnAuthor'           => __('Auteur', 'supersede-css-jlg'),
                        'activityColumnDetails'          => __('Détails', 'supersede-css-jlg'),
                        'exportsPreparing'               => __('Préparation de l’export…', 'supersede-css-jlg'),
                        'exportsSuccess'                 => __('Export prêt. Le téléchargement va démarrer.', 'supersede-css-jlg'),
                        'exportsError'                   => __('Impossible de générer l’export.', 'supersede-css-jlg'),
                        'exportsForbidden'               => __('Vous n’avez pas les droits nécessaires pour exporter.', 'supersede-css-jlg'),
                        'exportsUnavailable'             => __('Export indisponible : API REST injoignable.', 'supersede-css-jlg'),
                        'exportsDownloadError'           => __('Le fichier n’a pas pu être téléchargé. Réessayez.', 'supersede-css-jlg'),
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
            'approval_priorities' => TokenApprovalStore::getSupportedPriorities(),
            'can_review_approvals' => $canReviewApprovals,
            'can_export_tokens' => $canExportTokens,
            'activity_log' => $activityLog,
            'css_revisions' => CssRevisions::all(),
            'token_registry' => $tokenRegistry,
            'token_statuses' => $tokenStatuses,
            'approval_sla_rules' => $approvalSlaRules,
        ]);
    }
}
