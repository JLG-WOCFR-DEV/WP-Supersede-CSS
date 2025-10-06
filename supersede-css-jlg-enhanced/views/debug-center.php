<?php
if (!defined('ABSPATH')) {
    exit;
}
/** @var array{plugin_version?:string,wordpress_version?:string,php_version?:string} $system_info */
/** @var array<int,array<string,mixed>> $log_entries */
/** @var array<int,array<string,mixed>> $css_revisions */
$plugin_version    = $system_info['plugin_version'] ?? __('N/A', 'supersede-css-jlg');
$wordpress_version = $system_info['wordpress_version'] ?? '';
$php_version       = $system_info['php_version'] ?? '';
$css_revisions     = isset($css_revisions) && is_array($css_revisions) ? $css_revisions : [];
?>
<div class="ssc-wrap ssc-debug-center">
    <h1><?php echo esc_html__('Supersede CSS — Debug Center', 'supersede-css-jlg'); ?></h1>
    <p><?php echo esc_html__('Un hub centralisé pour la santé du système, la gestion des modules et le journal d\'activité.', 'supersede-css-jlg'); ?></p>

    <div class="ssc-two" style="align-items: flex-start; margin-top: 16px;">
        <div class="ssc-pane">
            <h2><?php echo esc_html__('Informations Système', 'supersede-css-jlg'); ?></h2>
            <table class="widefat striped" style="margin: 0;"><tbody>
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
        <div class="ssc-pane">
            <h2><?php echo esc_html__('Actions Globales', 'supersede-css-jlg'); ?></h2>
            <div class="ssc-actions">
                <button class="button button-primary" id="ssc-health-run" aria-controls="ssc-health-summary" aria-expanded="false">
                    <?php esc_html_e('Lancer Health Check', 'supersede-css-jlg'); ?>
                </button>
            </div>
            <div id="ssc-health-panel" class="ssc-health-panel" style="margin-top:10px;" aria-live="polite">
                <div class="ssc-health-panel__top" style="display:flex; justify-content:space-between; gap:12px; align-items:flex-start;">
                    <p class="description" style="margin:0; flex:1;">
                        <?php
                        $site_health_link = '<a href="' . esc_url(admin_url('site-health.php')) . '" target="_blank" rel="noopener noreferrer">' . esc_html__('Santé du site', 'supersede-css-jlg') . '</a>';
                        echo wp_kses_post(sprintf(
                            /* translators: 1: admin URL to the Site Health screen. */
                            __('Ce contrôle complète l’outil Santé du site de WordPress. Comparez les résultats ou ouvrez directement %1$s pour poursuivre le diagnostic.', 'supersede-css-jlg'),
                            $site_health_link
                        ));
                        ?>
                    </p>
                    <div class="ssc-health-panel__actions" style="display:flex; gap:8px;">
                        <button type="button" class="button button-secondary" id="ssc-health-copy" disabled>
                            <?php esc_html_e('Copier le JSON', 'supersede-css-jlg'); ?>
                        </button>
                    </div>
                </div>
                <div id="ssc-health-error" class="ssc-health-error" role="alert" aria-live="assertive" style="display:none; margin-top:10px; padding:8px 12px; border-left:4px solid #dc2626; background:#fef2f2; color:#991b1b;"></div>
                <div id="ssc-health-summary" class="ssc-health-summary" style="margin-top:10px;" role="status" aria-live="polite">
                    <p id="ssc-health-empty-state" class="description" style="margin:0;" aria-live="polite">
                        <?php esc_html_e('Aucun diagnostic lancé pour le moment.', 'supersede-css-jlg'); ?>
                    </p>
                    <p id="ssc-health-summary-meta" class="ssc-health-summary-meta" style="margin:6px 0 0; font-weight:600; display:none;"></p>
                    <p id="ssc-health-summary-generated" class="ssc-health-summary-generated" style="margin:4px 0 0; color:#334155; display:none;"></p>
                    <ul id="ssc-health-summary-list" class="ssc-health-list" style="margin:8px 0 0; padding-left:0; list-style:none; display:none;"></ul>
                </div>
                <details id="ssc-health-details" class="ssc-health-details" style="margin-top:12px;" hidden>
                    <summary><?php esc_html_e('Afficher le JSON brut', 'supersede-css-jlg'); ?></summary>
                    <pre id="ssc-health-json-raw" class="ssc-code" style="max-height:200px; margin-top:8px;"></pre>
                </details>
            </div>
        </div>
    </div>

    <div class="ssc-panel ssc-danger-zone" style="margin-top: 16px;">
         <h2><?php echo esc_html__('🛑 Zone de Danger', 'supersede-css-jlg'); ?></h2>
         <p id="ssc-danger-intro"><?php echo esc_html__('Les actions ci-dessous sont irréversibles. Soyez certain de vouloir continuer.', 'supersede-css-jlg'); ?></p>
         <button id="ssc-reset-all-css" class="button" style="background: #dc2626; border-color: #991b1b; color: white;"><?php esc_html_e('Réinitialiser tout le CSS', 'supersede-css-jlg'); ?></button>
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

    <div class="ssc-panel" style="margin-top: 16px;">
        <div class="ssc-panel-header">
            <div>
                <h2><?php echo esc_html__('Révisions CSS enregistrées', 'supersede-css-jlg'); ?></h2>
                <p class="description" style="margin-bottom: 12px;">
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
                                    <pre class="ssc-code" style="max-height:200px; overflow:auto; margin-top:8px;"><?php echo esc_html($css_source); ?></pre>
                                    <?php if ($hasSegments) : ?>
                                        <div style="margin-top: 10px;">
                                            <strong><?php esc_html_e('Segments responsives', 'supersede-css-jlg'); ?>:</strong>
                                            <ul style="margin: 6px 0 0 16px;">
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

    <div class="ssc-panel" style="margin-top: 16px;">
        <div class="ssc-panel-header">
            <div>
                <h2><?php echo esc_html__('Journal d\'Activité Récent', 'supersede-css-jlg'); ?></h2>
                <p class="description" style="margin-bottom: 12px;">
                    <?php echo esc_html__('Affinez le journal grâce aux filtres pour cibler les actions qui vous intéressent puis exportez les données au format souhaité.', 'supersede-css-jlg'); ?>
                </p>
            </div>
            <div class="ssc-panel-actions">
                <button type="button" class="button" id="ssc-export-log-json" <?php disabled(empty($log_entries)); ?>><?php esc_html_e('Exporter en JSON', 'supersede-css-jlg'); ?></button>
                <button type="button" class="button" id="ssc-export-log-csv" <?php disabled(empty($log_entries)); ?>><?php esc_html_e('Exporter en CSV', 'supersede-css-jlg'); ?></button>
                <button id="ssc-clear-log" class="button button-link-delete"><?php esc_html_e('Vider le journal', 'supersede-css-jlg'); ?></button>
            </div>
        </div>
        <?php
        $log_users   = [];
        $log_actions = [];
        foreach ($log_entries as $entry) {
            if (!empty($entry['user'])) {
                $log_users[] = (string) $entry['user'];
            }
            if (!empty($entry['action'])) {
                $log_actions[] = (string) $entry['action'];
            }
        }
        $log_users   = array_values(array_unique($log_users));
        $log_actions = array_values(array_unique($log_actions));
        ?>
        <div class="ssc-filter-bar" id="ssc-log-filters">
            <div class="ssc-filter">
                <label for="ssc-log-date-start"><?php esc_html_e('Du', 'supersede-css-jlg'); ?></label>
                <input type="date" id="ssc-log-date-start" data-filter="date-start" />
            </div>
            <div class="ssc-filter">
                <label for="ssc-log-date-end"><?php esc_html_e('Au', 'supersede-css-jlg'); ?></label>
                <input type="date" id="ssc-log-date-end" data-filter="date-end" />
            </div>
            <div class="ssc-filter">
                <label for="ssc-log-user"><?php esc_html_e('Utilisateur', 'supersede-css-jlg'); ?></label>
                <select id="ssc-log-user" data-filter="user">
                    <option value=""><?php esc_html_e('Tous', 'supersede-css-jlg'); ?></option>
                    <?php foreach ($log_users as $log_user) : ?>
                        <option value="<?php echo esc_attr($log_user); ?>"><?php echo esc_html($log_user); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="ssc-filter">
                <label for="ssc-log-action"><?php esc_html_e('Action', 'supersede-css-jlg'); ?></label>
                <select id="ssc-log-action" data-filter="action">
                    <option value=""><?php esc_html_e('Toutes', 'supersede-css-jlg'); ?></option>
                    <?php foreach ($log_actions as $log_action) : ?>
                        <option value="<?php echo esc_attr($log_action); ?>"><?php echo esc_html($log_action); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <?php if (!empty($log_entries)) : ?>
            <table class="widefat striped" id="ssc-log-table"><thead><tr>
                <th><?php esc_html_e('Date (UTC)', 'supersede-css-jlg'); ?></th>
                <th><?php esc_html_e('Utilisateur', 'supersede-css-jlg'); ?></th>
                <th><?php esc_html_e('Action', 'supersede-css-jlg'); ?></th>
                <th><?php esc_html_e('Données', 'supersede-css-jlg'); ?></th>
            </tr></thead><tbody>
                <?php foreach ($log_entries as $log_index => $row) : ?>
                    <?php
                    $log_timestamp = isset($row['t']) ? (string) $row['t'] : '';
                    $log_iso       = '';
                    if ($log_timestamp !== '') {
                        $log_time = strtotime($log_timestamp . ' UTC');
                        if ($log_time !== false) {
                            $log_iso = gmdate('Y-m-d\TH:i:s\Z', $log_time);
                        }
                    }
                    $log_user   = isset($row['user']) ? (string) $row['user'] : '';
                    $log_action = isset($row['action']) ? (string) $row['action'] : '';
                    ?>
                    <tr
                        data-index="<?php echo esc_attr((string) $log_index); ?>"
                        data-timestamp="<?php echo esc_attr($log_iso); ?>"
                        data-user="<?php echo esc_attr($log_user); ?>"
                        data-action="<?php echo esc_attr($log_action); ?>"
                    >
                        <td><?php echo esc_html($log_timestamp); ?></td>
                        <td><?php echo esc_html($log_user); ?></td>
                        <td><strong><?php echo esc_html($log_action); ?></strong></td>
                        <td><code><?php echo esc_html(json_encode($row['data'] ?? [])); ?></code></td>
                    </tr>
                <?php endforeach; ?>
            </tbody></table>
            <p id="ssc-log-empty" class="description" hidden><?php esc_html_e('Aucune entrée ne correspond aux filtres sélectionnés.', 'supersede-css-jlg'); ?></p>
            <script type="application/json" id="ssc-log-data"><?php echo wp_json_encode($log_entries); ?></script>
        <?php else : ?>
            <p><?php esc_html_e('Aucune entrée dans le journal.', 'supersede-css-jlg'); ?></p>
        <?php endif; ?>
    </div>
</div>
