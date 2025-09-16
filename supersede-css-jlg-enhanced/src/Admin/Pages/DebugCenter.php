<?php declare(strict_types=1);
namespace SSC\Admin\Pages;

if (!defined('ABSPATH')) { exit; }

class DebugCenter {
    public function render(): void { 
        ?>
        <div class="ssc-wrap ssc-debug-center">
            <h1><?php echo esc_html__('Supersede CSS — Debug Center', 'supersede-css-jlg'); ?></h1>
            <p><?php echo esc_html__('Un hub centralisé pour la santé du système, la gestion des modules et le journal d\'activité.', 'supersede-css-jlg'); ?></p>
            
            <div class="ssc-two" style="align-items: flex-start; margin-top: 16px;">
                <div class="ssc-pane">
                    <h2><?php echo esc_html__('Informations Système', 'supersede-css-jlg'); ?></h2>
                    <table class="widefat striped" style="margin: 0;"><tbody>
                        <tr><td><strong>Version du Plugin</strong></td><td><?php echo esc_html(defined('SSC_VERSION') ? SSC_VERSION : 'N/A'); ?></td></tr>
                        <tr><td><strong>Version WordPress</strong></td><td><?php echo esc_html(get_bloginfo('version')); ?></td></tr>
                        <tr><td><strong>Version PHP</strong></td><td><?php echo esc_html(phpversion()); ?></td></tr>
                    </tbody></table>
                </div>
                <div class="ssc-pane">
                    <h2><?php echo esc_html__('Actions Globales', 'supersede-css-jlg'); ?></h2>
                    <div class="ssc-actions">
                        <button class="button button-primary" id="ssc-health-run">Lancer Health Check</button>
                    </div>
                    <pre id="ssc-health-json" class="ssc-code" style="max-height:120px; margin-top:10px;"></pre>
                </div>
            </div>
            
            <div class="ssc-panel ssc-danger-zone" style="margin-top: 16px;">
                 <h2>🛑 Zone de Danger</h2>
                 <p id="ssc-danger-intro">Les actions ci-dessous sont irréversibles. Soyez certain de vouloir continuer.</p>
                 <button id="ssc-reset-all-css" class="button" style="background: #dc2626; border-color: #991b1b; color: white;">Réinitialiser tout le CSS</button>
                 <p id="ssc-danger-desc" class="description">Cette action videra les options <code>ssc_active_css</code> et <code>ssc_tokens_css</code> de votre base de données, désactivant tous les styles ajoutés par Supersede.</p>
            </div>

            <div class="ssc-panel" style="margin-top: 16px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                    <h2><?php echo esc_html__('Journal d\'Activité Récent', 'supersede-css-jlg'); ?></h2>
                    <button id="ssc-clear-log" class="button button-link-delete">Vider le journal</button>
                </div>
                <?php $log = class_exists('\SSC\Infra\Logger') ? \SSC\Infra\Logger::all() : []; if(!empty($log)): ?>
                  <table class="widefat striped"><thead><tr><th>Date (UTC)</th><th>Utilisateur</th><th>Action</th><th>Données</th></tr></thead><tbody>
                    <?php foreach ($log as $row): ?>
                        <tr>
                            <td><?php echo esc_html($row['t'] ?? ''); ?></td>
                            <td><?php echo esc_html($row['user'] ?? ''); ?></td>
                            <td><strong><?php echo esc_html($row['action'] ?? ''); ?></strong></td>
                            <td><code><?php echo esc_html(json_encode($row['data'] ?? [])); ?></code></td>
                        </tr>
                    <?php endforeach; ?>
                  </tbody></table>
                <?php else: ?><p>Aucune entrée dans le journal.</p><?php endif; ?>
            </div>
        </div>
    <?php }
}
