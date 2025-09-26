<?php
if (!defined('ABSPATH')) {
    exit;
}
/** @var array{plugin_version?:string,wordpress_version?:string,php_version?:string} $system_info */
/** @var array<int,array<string,mixed>> $log_entries */
$plugin_version    = $system_info['plugin_version'] ?? __('N/A', 'supersede-css-jlg');
$wordpress_version = $system_info['wordpress_version'] ?? '';
$php_version       = $system_info['php_version'] ?? '';
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
