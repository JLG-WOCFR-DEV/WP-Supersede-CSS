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
                <button class="button button-primary" id="ssc-health-run"><?php esc_html_e('Lancer Health Check', 'supersede-css-jlg'); ?></button>
            </div>
            <pre id="ssc-health-json" class="ssc-code" style="max-height:120px; margin-top:10px;"></pre>
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
        <h2><?php echo esc_html__('Révisions CSS enregistrées', 'supersede-css-jlg'); ?></h2>
        <p class="description" style="margin-bottom: 12px;">
            <?php echo esc_html__('Chaque sauvegarde conserve une version du CSS avec horodatage et auteur. Utilisez cette liste pour restaurer un état précédent en un clic.', 'supersede-css-jlg'); ?>
        </p>
        <?php if (!empty($css_revisions)) : ?>
            <table class="widefat striped">
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
                    <?php foreach ($css_revisions as $revision) :
                        $revision_id   = isset($revision['id']) ? (string) $revision['id'] : '';
                        $option_name   = isset($revision['option']) ? (string) $revision['option'] : '';
                        $timestamp     = isset($revision['timestamp']) ? (string) $revision['timestamp'] : '';
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
                        <tr>
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
        <?php else : ?>
            <p><?php esc_html_e('Aucune révision enregistrée pour le moment.', 'supersede-css-jlg'); ?></p>
        <?php endif; ?>
    </div>

    <div class="ssc-panel" style="margin-top: 16px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
            <h2><?php echo esc_html__('Journal d\'Activité Récent', 'supersede-css-jlg'); ?></h2>
            <button id="ssc-clear-log" class="button button-link-delete"><?php esc_html_e('Vider le journal', 'supersede-css-jlg'); ?></button>
        </div>
        <?php if (!empty($log_entries)) : ?>
            <table class="widefat striped"><thead><tr>
                <th><?php esc_html_e('Date (UTC)', 'supersede-css-jlg'); ?></th>
                <th><?php esc_html_e('Utilisateur', 'supersede-css-jlg'); ?></th>
                <th><?php esc_html_e('Action', 'supersede-css-jlg'); ?></th>
                <th><?php esc_html_e('Données', 'supersede-css-jlg'); ?></th>
            </tr></thead><tbody>
                <?php foreach ($log_entries as $row) : ?>
                    <tr>
                        <td><?php echo esc_html($row['t'] ?? ''); ?></td>
                        <td><?php echo esc_html($row['user'] ?? ''); ?></td>
                        <td><strong><?php echo esc_html($row['action'] ?? ''); ?></strong></td>
                        <td><code><?php echo esc_html(json_encode($row['data'] ?? [])); ?></code></td>
                    </tr>
                <?php endforeach; ?>
            </tbody></table>
        <?php else : ?>
            <p><?php esc_html_e('Aucune entrée dans le journal.', 'supersede-css-jlg'); ?></p>
        <?php endif; ?>
    </div>
</div>
