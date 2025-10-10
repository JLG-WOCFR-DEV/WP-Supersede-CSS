<?php
if (!defined('ABSPATH')) {
    exit;
}
/** @var array{plugin_version?:string,wordpress_version?:string,php_version?:string} $system_info */
/** @var array<int,array<string,mixed>> $approvals */
/** @var bool $can_review_approvals */
/** @var array{entries: array<int,array<string,mixed>>, pagination: array<string,int>, filters: array<string,string>} $activity_log */
/** @var array<int,array<string,mixed>> $css_revisions */
$can_export_tokens = isset($can_export_tokens) ? (bool) $can_export_tokens : false;
$plugin_version    = $system_info['plugin_version'] ?? __('N/A', 'supersede-css-jlg');
$wordpress_version = $system_info['wordpress_version'] ?? '';
$php_version       = $system_info['php_version'] ?? '';
$css_revisions     = isset($css_revisions) && is_array($css_revisions) ? $css_revisions : [];
$approvals         = isset($approvals) && is_array($approvals) ? $approvals : [];
$activity_log      = isset($activity_log) && is_array($activity_log) ? $activity_log : ['entries' => [], 'pagination' => ['total' => 0, 'total_pages' => 1, 'page' => 1], 'filters' => []];
$can_review        = isset($can_review_approvals) ? (bool) $can_review_approvals : false;

$normalize_user = static function ($user_id): array {
    $user_id = (int) $user_id;
    if ($user_id <= 0) {
        return ['id' => 0, 'name' => __('Compte inconnu', 'supersede-css-jlg'), 'avatar' => ''];
    }

    $user = get_userdata($user_id);
    if (!$user instanceof \WP_User) {
        return ['id' => $user_id, 'name' => __('Compte inconnu', 'supersede-css-jlg'), 'avatar' => ''];
    }

    return [
        'id' => $user->ID,
        'name' => $user->display_name,
        'avatar' => get_avatar_url($user->ID, ['size' => 32]),
    ];
};

$approvals_enriched = array_map(static function ($entry) use ($normalize_user) {
    if (!is_array($entry)) {
        return null;
    }

    $token = isset($entry['token']) && is_array($entry['token']) ? $entry['token'] : [];
    $requestedBy = isset($entry['requested_by']) ? (int) $entry['requested_by'] : 0;
    $decision = isset($entry['decision']) && is_array($entry['decision']) ? $entry['decision'] : null;

    $decisionStruct = null;
    $decisionUser = null;
    if ($decision !== null) {
        $decisionStruct = [
            'user_id' => isset($decision['user_id']) ? (int) $decision['user_id'] : 0,
            'comment' => isset($decision['comment']) ? (string) $decision['comment'] : '',
            'decided_at' => isset($decision['decided_at']) ? (string) $decision['decided_at'] : '',
        ];

        $decisionUser = $normalize_user($decisionStruct['user_id']);
    }

    return [
        'id' => isset($entry['id']) ? (string) $entry['id'] : '',
        'token' => [
            'name' => isset($token['name']) ? (string) $token['name'] : '',
            'context' => isset($token['context']) ? (string) $token['context'] : '',
        ],
        'status' => isset($entry['status']) ? (string) $entry['status'] : 'pending',
        'requested_at' => isset($entry['requested_at']) ? (string) $entry['requested_at'] : '',
        'requested_by' => $requestedBy,
        'requested_by_user' => $normalize_user($requestedBy),
        'comment' => isset($entry['comment']) ? (string) $entry['comment'] : '',
        'decision' => $decisionStruct,
        'decision_user' => $decisionUser,
    ];
}, $approvals);

$approvals_enriched = array_values(array_filter($approvals_enriched));
$activity_entries = isset($activity_log['entries']) && is_array($activity_log['entries']) ? $activity_log['entries'] : [];
$activity_pagination = isset($activity_log['pagination']) && is_array($activity_log['pagination']) ? $activity_log['pagination'] : ['total' => 0, 'total_pages' => 1, 'page' => 1];
$activity_filters = isset($activity_log['filters']) && is_array($activity_log['filters']) ? $activity_log['filters'] : [];
$format_datetime = static function (string $iso): string {
    if ($iso === '') {
        return '';
    }

    $timestamp = strtotime($iso);
    if ($timestamp === false) {
        return $iso;
    }

    $format = trim(get_option('date_format', 'Y-m-d') . ' ' . get_option('time_format', 'H:i'));

    return wp_date($format, $timestamp);
};
?>
<div class="ssc-wrap ssc-debug-center">
    <h1><?php echo esc_html__('Supersede CSS — Debug Center', 'supersede-css-jlg'); ?></h1>
    <p><?php echo esc_html__('Un hub centralisé pour la santé du système, la gestion des modules et le journal d\'activité.', 'supersede-css-jlg'); ?></p>

    <div class="ssc-two ssc-two--align-start ssc-mt-200">
        <div class="ssc-pane" data-ssc-debug-label="<?php echo esc_attr__('Système', 'supersede-css-jlg'); ?>">
            <h2><?php echo esc_html__('Informations Système', 'supersede-css-jlg'); ?></h2>
            <table class="widefat striped ssc-table--flush"><tbody>
                <tr>
                    <td><strong><?php esc_html_e('Version du Plugin', 'supersede-css-jlg'); ?></strong></td>
                    <td><?php echo esc_html($plugin_version); ?></td>
                </tr>
                <tr>
                    <td><strong><?php esc_html_e('Version WordPress', 'supersede-css-jlg'); ?></strong></td>
                    <td><?php echo esc_html($wordpress_version); ?></td>
                </tr>
                <tr>
                    <td><strong><?php esc_html_e('Version PHP', 'supersede-css-jlg'); ?></strong></td>
                    <td><?php echo esc_html($php_version); ?></td>
                </tr>
            </tbody></table>
        </div>
        <div class="ssc-pane" data-ssc-debug-label="<?php echo esc_attr__('Santé', 'supersede-css-jlg'); ?>">
            <h2><?php echo esc_html__('Actions Globales', 'supersede-css-jlg'); ?></h2>
            <div class="ssc-actions">
                <button class="button button-primary" id="ssc-health-run" aria-controls="ssc-health-summary" aria-expanded="false">
                    <?php esc_html_e('Lancer Health Check', 'supersede-css-jlg'); ?>
                </button>
            </div>
            <div id="ssc-health-panel" class="ssc-health-panel" aria-live="polite">
                <div class="ssc-health-panel__top">
                    <p class="description ssc-description--flush ssc-health-panel__description">
                        <?php
                        $site_health_link = '<a href="' . esc_url(admin_url('site-health.php')) . '" target="_blank" rel="noopener noreferrer">' . esc_html__('Santé du site', 'supersede-css-jlg') . '</a>';
                        echo wp_kses_post(sprintf(
                            /* translators: 1: admin URL to the Site Health screen. */
                            __('Ce contrôle complète l’outil Santé du site de WordPress. Comparez les résultats ou ouvrez directement %1$s pour poursuivre le diagnostic.', 'supersede-css-jlg'),
                            $site_health_link
                        ));
                        ?>
                    </p>
                    <div class="ssc-health-panel__actions ssc-actions">
                        <button type="button" class="button button-secondary" id="ssc-health-copy" disabled>
                            <?php esc_html_e('Copier le JSON', 'supersede-css-jlg'); ?>
                        </button>
                    </div>
                </div>
                <div id="ssc-health-error" class="ssc-health-error" role="alert" aria-live="assertive" hidden></div>
                <div id="ssc-health-summary" class="ssc-health-summary" role="status" aria-live="polite">
                    <p id="ssc-health-empty-state" class="description ssc-description--flush" aria-live="polite">
                        <?php esc_html_e('Aucun diagnostic lancé pour le moment.', 'supersede-css-jlg'); ?>
                    </p>
                    <p id="ssc-health-summary-meta" class="ssc-health-summary__meta" hidden></p>
                    <p id="ssc-health-summary-generated" class="ssc-health-summary__generated" hidden></p>
                    <ul id="ssc-health-summary-list" class="ssc-health-list" hidden></ul>
                </div>
                <details id="ssc-health-details" class="ssc-health-details" hidden>
                    <summary><?php esc_html_e('Afficher le JSON brut', 'supersede-css-jlg'); ?></summary>
                    <pre id="ssc-health-json-raw" class="ssc-code ssc-code--scrollable ssc-mt-100"></pre>
                </details>
            </div>
        </div>
    </div>

    <div class="ssc-panel ssc-mt-200" id="ssc-visual-debug-panel" data-ssc-debug-label="<?php echo esc_attr__('Débogage visuel', 'supersede-css-jlg'); ?>">
        <h2><?php echo esc_html__('Assistant de débogage visuel', 'supersede-css-jlg'); ?></h2>
        <p class="description" id="ssc-visual-debug-description">
            <?php echo esc_html__('Activez les contours d’interface, les grilles et les repères d’espacement pour inspecter rapidement la mise en page après vos modifications CSS.', 'supersede-css-jlg'); ?>
        </p>
        <div class="ssc-visual-debug-actions">
            <button
                type="button"
                class="button button-secondary"
                id="ssc-visual-debug-toggle"
                aria-pressed="false"
                aria-describedby="ssc-visual-debug-description ssc-visual-debug-status"
            >
                <?php echo esc_html__('Activer le débogage visuel', 'supersede-css-jlg'); ?>
            </button>
            <p id="ssc-visual-debug-status" class="description" role="status" aria-live="polite"></p>
        </div>
        <p id="ssc-visual-debug-note" class="description ssc-visual-debug-note" hidden></p>
        <div class="ssc-visual-debug-legend" aria-hidden="true">
            <span class="ssc-visual-debug-legend__item">
                <span class="ssc-visual-debug-legend__swatch ssc-visual-debug-legend__swatch--surface" aria-hidden="true"></span>
                <?php echo esc_html__('Contours des panneaux', 'supersede-css-jlg'); ?>
            </span>
            <span class="ssc-visual-debug-legend__item">
                <span class="ssc-visual-debug-legend__swatch ssc-visual-debug-legend__swatch--grid" aria-hidden="true"></span>
                <?php echo esc_html__('Grilles & espacements', 'supersede-css-jlg'); ?>
            </span>
            <span class="ssc-visual-debug-legend__item">
                <span class="ssc-visual-debug-legend__swatch ssc-visual-debug-legend__swatch--focus" aria-hidden="true"></span>
                <?php echo esc_html__('Points d’interaction', 'supersede-css-jlg'); ?>
            </span>
        </div>
    </div>

    <div class="ssc-panel ssc-mt-200" id="ssc-token-exports-panel" data-ssc-debug-label="<?php echo esc_attr__('Exports', 'supersede-css-jlg'); ?>">
        <div class="ssc-panel-header">
            <div>
                <h2><?php echo esc_html__('Exports multi-plateformes', 'supersede-css-jlg'); ?></h2>
                <p class="description ssc-description--spaced">
                    <?php echo esc_html__('Générez un bundle de tokens approuvés pour Style Dictionary, Android ou iOS afin de synchroniser vos équipes.', 'supersede-css-jlg'); ?>
                </p>
            </div>
        </div>
        <?php if (!$can_export_tokens) : ?>
            <div class="notice notice-warning inline">
                <p><?php esc_html_e('Vous n’avez pas la permission d’exporter les tokens. Contactez un administrateur.', 'supersede-css-jlg'); ?></p>
            </div>
        <?php endif; ?>
        <div class="ssc-filter-bar ssc-filter-bar--compact" id="ssc-token-export-controls">
            <div class="ssc-filter">
                <label for="ssc-token-export-format"><?php esc_html_e('Format', 'supersede-css-jlg'); ?></label>
                <select id="ssc-token-export-format" <?php disabled(!$can_export_tokens); ?>>
                    <option value="style-dictionary"><?php esc_html_e('Style Dictionary (JSON hiérarchique)', 'supersede-css-jlg'); ?></option>
                    <option value="json"><?php esc_html_e('JSON brut', 'supersede-css-jlg'); ?></option>
                    <option value="android"><?php esc_html_e('Android XML', 'supersede-css-jlg'); ?></option>
                    <option value="ios"><?php esc_html_e('iOS JSON', 'supersede-css-jlg'); ?></option>
                </select>
            </div>
            <div class="ssc-filter">
                <label for="ssc-token-export-scope"><?php esc_html_e('Portée', 'supersede-css-jlg'); ?></label>
                <select id="ssc-token-export-scope" <?php disabled(!$can_export_tokens); ?>>
                    <option value="ready"><?php esc_html_e('Tokens approuvés (ready)', 'supersede-css-jlg'); ?></option>
                    <option value="deprecated"><?php esc_html_e('Tokens dépréciés uniquement', 'supersede-css-jlg'); ?></option>
                    <option value="all"><?php esc_html_e('Tous les tokens', 'supersede-css-jlg'); ?></option>
                </select>
            </div>
            <div class="ssc-actions">
                <button type="button" class="button button-primary" id="ssc-token-export-run" data-can-export="<?php echo $can_export_tokens ? '1' : '0'; ?>" <?php disabled(!$can_export_tokens); ?>>
                    <?php esc_html_e('Générer l’export', 'supersede-css-jlg'); ?>
                </button>
            </div>
        </div>
        <p class="description" id="ssc-token-export-status" aria-live="polite" hidden></p>
        <script type="application/json" id="ssc-exports-permissions"><?php echo wp_json_encode(['canExport' => $can_export_tokens]); ?></script>
    </div>

    <div class="ssc-panel ssc-danger-zone ssc-mt-200" data-ssc-debug-label="<?php echo esc_attr__('Zone de danger', 'supersede-css-jlg'); ?>">
         <h2><?php echo esc_html__('🛑 Zone de Danger', 'supersede-css-jlg'); ?></h2>
         <p id="ssc-danger-intro" class="description">
             <?php echo esc_html__('Les actions ci-dessous sont irréversibles. Soyez certain de vouloir continuer.', 'supersede-css-jlg'); ?>
         </p>
         <button id="ssc-reset-all-css" class="button button-destructive" aria-describedby="ssc-danger-desc">
             <span class="dashicons dashicons-warning ssc-button-icon" aria-hidden="true"></span>
             <?php esc_html_e('Réinitialiser tout le CSS', 'supersede-css-jlg'); ?>
         </button>
         <?php
         $danger_desc = sprintf(
             /* translators: 1: Supersede CSS option name, 2: Supersede CSS option name. */
             __('Cette action videra les options %1$s et %2$s de votre base de données, désactivant tous les styles ajoutés par Supersede.', 'supersede-css-jlg'),
             '<code>ssc_active_css</code>',
             '<code>ssc_tokens_css</code>'
         );
         ?>
         <p id="ssc-danger-desc" class="description"><?php echo wp_kses_post($danger_desc); ?></p>
    </div>

    <div class="ssc-panel ssc-mt-200" data-ssc-debug-label="<?php echo esc_attr__('Révisions CSS', 'supersede-css-jlg'); ?>">
        <div class="ssc-panel-header">
            <div>
                <h2><?php echo esc_html__('Révisions CSS enregistrées', 'supersede-css-jlg'); ?></h2>
                <p class="description ssc-description--spaced">
                    <?php echo esc_html__('Chaque sauvegarde conserve une version du CSS avec horodatage et auteur. Utilisez cette liste pour restaurer ou comparer des états précédents.', 'supersede-css-jlg'); ?>
                </p>
            </div>
            <div class="ssc-panel-actions">
                <button type="button" class="button button-secondary" id="ssc-export-css" <?php disabled(empty($css_revisions)); ?>>
                    <?php esc_html_e('Exporter le CSS sélectionné', 'supersede-css-jlg'); ?>
                </button>
            </div>
        </div>
        <?php
        $revision_users = [];
        foreach ($css_revisions as $revision_item) {
            if (!empty($revision_item['author'])) {
                $revision_users[] = (string) $revision_item['author'];
            }
        }
        $revision_users = array_values(array_unique($revision_users));
        ?>
        <div class="ssc-filter-bar" id="ssc-revision-filters">
            <div class="ssc-filter">
                <label for="ssc-revision-date-start"><?php esc_html_e('Du', 'supersede-css-jlg'); ?></label>
                <input type="date" id="ssc-revision-date-start" data-filter="date-start" />
            </div>
            <div class="ssc-filter">
                <label for="ssc-revision-date-end"><?php esc_html_e('Au', 'supersede-css-jlg'); ?></label>
                <input type="date" id="ssc-revision-date-end" data-filter="date-end" />
            </div>
            <div class="ssc-filter">
                <label for="ssc-revision-user"><?php esc_html_e('Utilisateur', 'supersede-css-jlg'); ?></label>
                <select id="ssc-revision-user" data-filter="user">
                    <option value=""><?php esc_html_e('Tous', 'supersede-css-jlg'); ?></option>
                    <?php foreach ($revision_users as $revision_user) : ?>
                        <option value="<?php echo esc_attr($revision_user); ?>"><?php echo esc_html($revision_user); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <?php if (!empty($css_revisions)) : ?>
            <table class="widefat striped" id="ssc-revisions-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Horodatage (UTC)', 'supersede-css-jlg'); ?></th>
                        <th><?php esc_html_e('Option', 'supersede-css-jlg'); ?></th>
                        <th><?php esc_html_e('Auteur', 'supersede-css-jlg'); ?></th>
                        <th><?php esc_html_e('Taille', 'supersede-css-jlg'); ?></th>
                        <th><?php esc_html_e('Détails', 'supersede-css-jlg'); ?></th>
                        <th><?php esc_html_e('Actions', 'supersede-css-jlg'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($css_revisions as $index => $revision) :
                        $revision_id   = isset($revision['id']) ? (string) $revision['id'] : '';
                        $option_name   = isset($revision['option']) ? (string) $revision['option'] : '';
                        $timestamp     = isset($revision['timestamp']) ? (string) $revision['timestamp'] : '';
                        $iso_timestamp = '';
                        if ($timestamp !== '') {
                            $timestamp_gmt = strtotime($timestamp . ' UTC');
                            if ($timestamp_gmt !== false) {
                                $iso_timestamp = gmdate('Y-m-d\TH:i:s\Z', $timestamp_gmt);
                            }
                        }
                        $author        = isset($revision['author']) ? (string) $revision['author'] : '';
                        $css_source    = isset($revision['css']) ? (string) $revision['css'] : '';
                        $css_length    = strlen($css_source);
                        $segments_data = [];
                        if (isset($revision['segments']) && is_array($revision['segments'])) {
                            foreach (['desktop', 'tablet', 'mobile'] as $segmentKey) {
                                $segments_data[$segmentKey] = isset($revision['segments'][$segmentKey]) && is_string($revision['segments'][$segmentKey])
                                    ? (string) $revision['segments'][$segmentKey]
                                    : '';
                            }
                        }
                        $hasSegments = false;
                        foreach ($segments_data as $segmentValue) {
                            if ($segmentValue !== '') {
                                $hasSegments = true;
                                break;
                            }
                        }
                        ?>
                        <tr
                            data-index="<?php echo esc_attr((string) $index); ?>"
                            data-revision-id="<?php echo esc_attr($revision_id); ?>"
                            data-timestamp="<?php echo esc_attr($iso_timestamp); ?>"
                            data-author="<?php echo esc_attr($author); ?>"
                        >
                            <td><?php echo esc_html($timestamp); ?></td>
                            <td><code><?php echo esc_html($option_name); ?></code></td>
                            <td><?php echo esc_html($author); ?></td>
                            <td><?php echo esc_html(number_format_i18n($css_length)); ?></td>
                            <td>
                                <details>
                                    <summary><?php echo esc_html(sprintf(__('Afficher le CSS (%d caractères)', 'supersede-css-jlg'), $css_length)); ?></summary>
                                    <pre class="ssc-code ssc-code--scrollable ssc-mt-100"><?php echo esc_html($css_source); ?></pre>
                                    <?php if ($hasSegments) : ?>
                                        <div class="ssc-mt-150">
                                            <strong><?php esc_html_e('Segments responsives', 'supersede-css-jlg'); ?>:</strong>
                                            <ul class="ssc-list--indented">
                                                <?php
                                                $segmentLabels = [
                                                    'desktop' => __('Bureau', 'supersede-css-jlg'),
                                                    'tablet' => __('Tablette', 'supersede-css-jlg'),
                                                    'mobile' => __('Mobile', 'supersede-css-jlg'),
                                                ];
                                                foreach ($segmentLabels as $segmentKey => $segmentLabel) :
                                                    $segmentValue = $segments_data[$segmentKey] ?? '';
                                                    ?>
                                                    <li>
                                                        <strong><?php echo esc_html($segmentLabel); ?>:</strong>
                                                        <code><?php echo esc_html($segmentValue); ?></code>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                    <?php endif; ?>
                                </details>
                            </td>
                            <td>
                                <button
                                    class="button button-secondary ssc-revision-restore"
                                    data-revision="<?php echo esc_attr($revision_id); ?>"
                                    data-option="<?php echo esc_attr($option_name); ?>"
                                >
                                    <?php esc_html_e('Restaurer', 'supersede-css-jlg'); ?>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <p id="ssc-revision-empty" class="description" hidden><?php esc_html_e('Aucune révision ne correspond aux filtres sélectionnés.', 'supersede-css-jlg'); ?></p>
            <div class="ssc-revision-diff" aria-live="polite">
                <h3><?php esc_html_e('Comparer des révisions', 'supersede-css-jlg'); ?></h3>
                <div class="ssc-diff-controls">
                    <label for="ssc-diff-base" class="screen-reader-text"><?php esc_html_e('Révision de base', 'supersede-css-jlg'); ?></label>
                    <select id="ssc-diff-base">
                        <?php foreach ($css_revisions as $index => $revision) :
                            $revision_id = isset($revision['id']) ? (string) $revision['id'] : '';
                            $timestamp   = isset($revision['timestamp']) ? (string) $revision['timestamp'] : '';
                            $author      = isset($revision['author']) ? (string) $revision['author'] : '';
                            ?>
                            <option value="<?php echo esc_attr($revision_id); ?>" data-index="<?php echo esc_attr((string) $index); ?>">
                                <?php echo esc_html(sprintf('%1$s — %2$s', $timestamp, $author)); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <label for="ssc-diff-compare" class="screen-reader-text"><?php esc_html_e('Révision à comparer', 'supersede-css-jlg'); ?></label>
                    <select id="ssc-diff-compare">
                        <?php foreach ($css_revisions as $index => $revision) :
                            $revision_id = isset($revision['id']) ? (string) $revision['id'] : '';
                            $timestamp   = isset($revision['timestamp']) ? (string) $revision['timestamp'] : '';
                            $author      = isset($revision['author']) ? (string) $revision['author'] : '';
                            ?>
                            <option value="<?php echo esc_attr($revision_id); ?>" data-index="<?php echo esc_attr((string) $index); ?>">
                                <?php echo esc_html(sprintf('%1$s — %2$s', $timestamp, $author)); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="button" class="button" id="ssc-diff-load"><?php esc_html_e('Afficher le diff', 'supersede-css-jlg'); ?></button>
                </div>
                <div id="ssc-diff-output" class="ssc-diff-output" data-placeholder="<?php echo esc_attr__('Sélectionnez deux révisions pour visualiser leurs différences.', 'supersede-css-jlg'); ?>"></div>
            </div>
            <script type="application/json" id="ssc-revisions-data"><?php echo wp_json_encode($css_revisions); ?></script>
        <?php else : ?>
            <p><?php esc_html_e('Aucune révision enregistrée pour le moment.', 'supersede-css-jlg'); ?></p>
        <?php endif; ?>
    </div>

    <div class="ssc-panel ssc-mt-200" data-ssc-debug-label="<?php echo esc_attr__('Approbations', 'supersede-css-jlg'); ?>">
        <div class="ssc-panel-header">
            <div>
                <h2><?php echo esc_html__('Approbations de Tokens', 'supersede-css-jlg'); ?></h2>
                <p class="description ssc-description--spaced">
                    <?php echo esc_html__('Surveillez les demandes de revue, validez les tokens prêts pour la production ou demandez des ajustements avec un commentaire structuré.', 'supersede-css-jlg'); ?>
                </p>
            </div>
            <div class="ssc-panel-actions ssc-approvals-actions">
                <label for="ssc-approvals-filter" class="screen-reader-text"><?php esc_html_e('Filtrer les approbations', 'supersede-css-jlg'); ?></label>
                <select id="ssc-approvals-filter">
                    <option value="pending"><?php esc_html_e('En attente', 'supersede-css-jlg'); ?></option>
                    <option value="approved"><?php esc_html_e('Approuvés', 'supersede-css-jlg'); ?></option>
                    <option value="changes_requested"><?php esc_html_e('Changements demandés', 'supersede-css-jlg'); ?></option>
                    <option value="all"><?php esc_html_e('Tous', 'supersede-css-jlg'); ?></option>
                </select>
                <button type="button" class="button" id="ssc-approvals-refresh"><?php esc_html_e('Actualiser les demandes', 'supersede-css-jlg'); ?></button>
            </div>
        </div>
        <?php if (!$can_review) : ?>
            <div class="notice notice-warning inline ssc-mt-100">
                <p><?php esc_html_e('Vous pouvez consulter les demandes en cours, mais seul un membre disposant de la capacité appropriée peut prendre une décision.', 'supersede-css-jlg'); ?></p>
            </div>
        <?php endif; ?>
        <div class="ssc-table-wrapper">
            <table class="widefat striped" id="ssc-approvals-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Token', 'supersede-css-jlg'); ?></th>
                        <th><?php esc_html_e('Statut', 'supersede-css-jlg'); ?></th>
                        <th><?php esc_html_e('Demandé par', 'supersede-css-jlg'); ?></th>
                        <th><?php esc_html_e('Commentaires', 'supersede-css-jlg'); ?></th>
                        <th><?php esc_html_e('Actions', 'supersede-css-jlg'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $approval_status_labels = [
                        'pending' => __('En attente', 'supersede-css-jlg'),
                        'approved' => __('Approuvé', 'supersede-css-jlg'),
                        'changes_requested' => __('Changements demandés', 'supersede-css-jlg'),
                    ];
                    foreach ($approvals_enriched as $approval) :
                        $token_name = $approval['token']['name'] ?? '';
                        $token_context = $approval['token']['context'] ?? '';
                        $status = strtolower($approval['status'] ?? 'pending');
                        $status_class = 'ssc-approval-badge--' . preg_replace('/[^a-z0-9_-]/', '', $status);
                        $status_label = $approval_status_labels[$status] ?? __('Statut inconnu', 'supersede-css-jlg');
                        $requested_by = isset($approval['requested_by_user']) && is_array($approval['requested_by_user'])
                            ? $approval['requested_by_user']
                            : $normalize_user($approval['requested_by'] ?? 0);
                        $requested_at = $format_datetime($approval['requested_at'] ?? '');
                        $decision = $approval['decision'];
                        $comment = $approval['comment'] ?? '';
                        $decision_comment = is_array($decision) ? ($decision['comment'] ?? '') : '';
                        $decision_user_data = isset($approval['decision_user']) && is_array($approval['decision_user'])
                            ? $approval['decision_user']
                            : (is_array($decision) ? $normalize_user($decision['user_id'] ?? 0) : null);
                        $decision_user = is_array($decision_user_data) ? ($decision_user_data['name'] ?? '') : '';
                        $decision_at = is_array($decision) ? $format_datetime($decision['decided_at'] ?? '') : '';
                        ?>
                        <tr data-approval-id="<?php echo esc_attr($approval['id']); ?>">
                            <td>
                                <div class="ssc-approval-token">
                                    <code><?php echo esc_html($token_name); ?></code>
                                    <?php if ($token_context !== '') : ?>
                                        <span class="ssc-approval-context"><?php echo esc_html($token_context); ?></span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <span class="ssc-approval-badge <?php echo esc_attr($status_class); ?>">
                                    <?php echo esc_html($status_label); ?>
                                </span>
                            </td>
                            <td>
                                <div class="ssc-approval-meta">
                                    <?php if (!empty($requested_by['avatar'])) : ?>
                                        <img src="<?php echo esc_url($requested_by['avatar']); ?>" alt="" class="ssc-approval-avatar" />
                                    <?php endif; ?>
                                    <div>
                                        <strong><?php echo esc_html($requested_by['name']); ?></strong>
                                        <?php if ($requested_at !== '') : ?>
                                            <p class="description ssc-description--flush"><?php echo esc_html(sprintf(__('Envoyé le %s', 'supersede-css-jlg'), $requested_at)); ?></p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <?php if ($comment !== '') : ?>
                                    <p class="ssc-approval-comment">“<?php echo esc_html($comment); ?>”</p>
                                <?php else : ?>
                                    <p class="description ssc-description--flush"><?php esc_html_e('Aucun commentaire fourni lors de la demande.', 'supersede-css-jlg'); ?></p>
                                <?php endif; ?>
                                <?php if ($decision_comment !== '' && $decision_user !== '') : ?>
                                    <p class="ssc-approval-decision"><strong><?php echo esc_html($decision_user); ?></strong><?php if ($decision_at !== '') : ?> — <?php echo esc_html($decision_at); ?><?php endif; ?> : “<?php echo esc_html($decision_comment); ?>”</p>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($status === 'pending' && $can_review) : ?>
                                    <div class="ssc-approval-actions">
                                        <button type="button" class="button button-secondary ssc-approval-approve" data-approval-id="<?php echo esc_attr($approval['id']); ?>"><?php esc_html_e('Approuver', 'supersede-css-jlg'); ?></button>
                                        <button type="button" class="button button-link-delete ssc-approval-request-changes" data-approval-id="<?php echo esc_attr($approval['id']); ?>"><?php esc_html_e('Demander des changements', 'supersede-css-jlg'); ?></button>
                                    </div>
                                <?php else : ?>
                                    <p class="description ssc-description--flush"><?php esc_html_e('Aucune action disponible.', 'supersede-css-jlg'); ?></p>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <p id="ssc-approvals-empty" class="description" <?php if (!empty($approvals_enriched)) : ?>hidden<?php endif; ?>><?php esc_html_e('Aucune demande d’approbation pour le moment.', 'supersede-css-jlg'); ?></p>
        <script type="application/json" id="ssc-approvals-data"><?php echo wp_json_encode($approvals_enriched); ?></script>
        <script type="application/json" id="ssc-approvals-permissions"><?php echo wp_json_encode(['canReview' => $can_review]); ?></script>
    </div>

    <div class="ssc-panel ssc-mt-200" data-ssc-debug-label="<?php echo esc_attr__('Journal d’activité', 'supersede-css-jlg'); ?>">
        <div class="ssc-panel-header">
            <div>
                <h2><?php echo esc_html__('Journal d\'Activité', 'supersede-css-jlg'); ?></h2>
                <p class="description ssc-description--spaced">
                    <?php echo esc_html__('Analysez les événements clés générés par le workflow Supersede CSS, filtrez par type et exportez les résultats pour les audits.', 'supersede-css-jlg'); ?>
                </p>
            </div>
            <div class="ssc-panel-actions">
                <button type="button" class="button" id="ssc-activity-export-json"><?php esc_html_e('Exporter en JSON', 'supersede-css-jlg'); ?></button>
                <button type="button" class="button" id="ssc-activity-export-csv"><?php esc_html_e('Exporter en CSV', 'supersede-css-jlg'); ?></button>
            </div>
        </div>
        <div class="ssc-filter-bar" id="ssc-activity-filters">
            <div class="ssc-filter">
                <label for="ssc-activity-event"><?php esc_html_e('Événement', 'supersede-css-jlg'); ?></label>
                <input type="search" id="ssc-activity-event" placeholder="<?php esc_attr_e('token.updated…', 'supersede-css-jlg'); ?>" />
            </div>
            <div class="ssc-filter">
                <label for="ssc-activity-entity"><?php esc_html_e('Type d’entité', 'supersede-css-jlg'); ?></label>
                <input type="search" id="ssc-activity-entity" placeholder="<?php esc_attr_e('token, preset…', 'supersede-css-jlg'); ?>" />
            </div>
            <div class="ssc-filter">
                <label for="ssc-activity-resource"><?php esc_html_e('Ressource', 'supersede-css-jlg'); ?></label>
                <input type="search" id="ssc-activity-resource" placeholder="<?php esc_attr_e('Identifiant, slug…', 'supersede-css-jlg'); ?>" />
            </div>
            <div class="ssc-filter">
                <label for="ssc-activity-window"><?php esc_html_e('Période', 'supersede-css-jlg'); ?></label>
                <select id="ssc-activity-window">
                    <option value=""><?php esc_html_e('Toutes', 'supersede-css-jlg'); ?></option>
                    <option value="24h"><?php esc_html_e('Dernières 24h', 'supersede-css-jlg'); ?></option>
                    <option value="7d"><?php esc_html_e('7 derniers jours', 'supersede-css-jlg'); ?></option>
                    <option value="30d"><?php esc_html_e('30 derniers jours', 'supersede-css-jlg'); ?></option>
                </select>
            </div>
            <div class="ssc-filter">
                <label for="ssc-activity-per-page"><?php esc_html_e('Éléments par page', 'supersede-css-jlg'); ?></label>
                <select id="ssc-activity-per-page">
                    <option value="10">10</option>
                    <option value="20" selected>20</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
            </div>
            <div class="ssc-filter ssc-filter--actions">
                <button type="button" class="button" id="ssc-activity-apply"><?php esc_html_e('Appliquer', 'supersede-css-jlg'); ?></button>
                <button type="button" class="button button-secondary" id="ssc-activity-reset"><?php esc_html_e('Réinitialiser', 'supersede-css-jlg'); ?></button>
            </div>
        </div>
        <div class="ssc-activity-meta">
            <p id="ssc-activity-summary" class="description"></p>
        </div>
        <div class="ssc-table-wrapper">
            <table class="widefat striped" id="ssc-activity-log-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Date', 'supersede-css-jlg'); ?></th>
                        <th><?php esc_html_e('Événement', 'supersede-css-jlg'); ?></th>
                        <th><?php esc_html_e('Entité', 'supersede-css-jlg'); ?></th>
                        <th><?php esc_html_e('Auteur', 'supersede-css-jlg'); ?></th>
                        <th><?php esc_html_e('Détails', 'supersede-css-jlg'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($activity_entries)) : ?>
                        <?php foreach ($activity_entries as $entry) :
                            $created_at = isset($entry['created_at']) ? (string) $entry['created_at'] : '';
                            $created_at_formatted = $format_datetime($created_at);
                            $event = isset($entry['event']) ? (string) $entry['event'] : '';
                            $entity_type = isset($entry['entity_type']) ? (string) $entry['entity_type'] : '';
                            $entity_id = isset($entry['entity_id']) ? (string) $entry['entity_id'] : '';
                            $author = isset($entry['created_by']) && is_array($entry['created_by']) ? $entry['created_by'] : null;
                            $author_name = is_array($author) && isset($author['name']) ? (string) $author['name'] : __('Système', 'supersede-css-jlg');
                            $details = isset($entry['details']) && is_array($entry['details']) ? $entry['details'] : [];
                            ?>
                            <tr data-entry-id="<?php echo esc_attr((string) ($entry['id'] ?? '')); ?>">
                                <td data-label="<?php esc_attr_e('Date', 'supersede-css-jlg'); ?>">
                                    <time datetime="<?php echo esc_attr($created_at); ?>"><?php echo esc_html($created_at_formatted !== '' ? $created_at_formatted : $created_at); ?></time>
                                </td>
                                <td data-label="<?php esc_attr_e('Événement', 'supersede-css-jlg'); ?>"><code><?php echo esc_html($event); ?></code></td>
                                <td data-label="<?php esc_attr_e('Entité', 'supersede-css-jlg'); ?>">
                                    <span class="ssc-activity-entity"><?php echo esc_html($entity_type); ?></span>
                                    <?php if ($entity_id !== '') : ?>
                                        <p class="description ssc-description--flush"><?php echo esc_html($entity_id); ?></p>
                                    <?php endif; ?>
                                </td>
                                <td data-label="<?php esc_attr_e('Auteur', 'supersede-css-jlg'); ?>"><?php echo esc_html($author_name); ?></td>
                                <td data-label="<?php esc_attr_e('Détails', 'supersede-css-jlg'); ?>">
                                    <code><?php echo esc_html(wp_json_encode($details, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)); ?></code>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <p id="ssc-activity-empty" class="description" <?php if (!empty($activity_entries)) : ?>hidden<?php endif; ?>><?php esc_html_e('Aucune entrée ne correspond aux filtres actuels.', 'supersede-css-jlg'); ?></p>
        <div class="ssc-pagination" id="ssc-activity-pagination">
            <button type="button" class="button button-secondary" id="ssc-activity-prev" disabled><?php esc_html_e('Précédent', 'supersede-css-jlg'); ?></button>
            <span id="ssc-activity-page-indicator" class="ssc-activity-page-indicator"></span>
            <button type="button" class="button button-secondary" id="ssc-activity-next" disabled><?php esc_html_e('Suivant', 'supersede-css-jlg'); ?></button>
        </div>
        <script type="application/json" id="ssc-activity-log-data"><?php echo wp_json_encode([
            'entries' => $activity_entries,
            'pagination' => $activity_pagination,
            'filters' => $activity_filters,
        ]); ?></script>
    </div>
</div>
