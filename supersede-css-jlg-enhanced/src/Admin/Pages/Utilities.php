<?php declare(strict_types=1);
namespace SSC\Admin\Pages;

if (!defined('ABSPATH')) { exit; }

class Utilities {
    public function render(){ ?>
    <style>
        .ssc-editor-tabs { display: flex; border-bottom: 1px solid var(--ssc-border); }
        .ssc-editor-tab { padding: 8px 16px; cursor: pointer; border-bottom: 2px solid transparent; }
        .ssc-editor-tab.active { color: var(--ssc-accent); border-bottom-color: var(--ssc-accent); font-weight: 600; }
        .ssc-editor-panel { display: none; height: 100%; }
        .ssc-editor-panel.active { display: block; }
        .ssc-tutorial-content { padding: 16px; }
        .ssc-tutorial-content code { background: var(--ssc-bg); padding: 2px 6px; border-radius: 4px; }
        #ssc-picker-overlay { position: absolute; inset: 0; background: rgba(79, 70, 229, 0.2); z-index: 9998; display: none; cursor: crosshair; }
        #ssc-picker-tooltip { position: fixed; background: #0f172a; color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px; z-index: 9999; white-space: nowrap; display: none; }
    </style>
    <div class="ssc-wrap ssc-utilities-wrap">
        <div class="ssc-editor-layout">
            <div class="ssc-editor-column">
                <div class="ssc-editor-header">
                    <h2><?php echo esc_html__('Éditeur CSS', 'supersede-css-jlg'); ?></h2>
                    <div class="ssc-actions">
                        <button id="ssc-save-css" class="button button-primary"><?php echo esc_html__('Enregistrer le CSS', 'supersede-css-jlg'); ?></button>
                    </div>
                </div>
                <div class="ssc-editor-tabs">
                    <div class="ssc-editor-tab active" data-tab="desktop">🖥️ Desktop</div>
                    <div class="ssc-editor-tab" data-tab="tablet">📲 Tablette</div>
                    <div class="ssc-editor-tab" data-tab="mobile">📱 Mobile</div>
                    <div class="ssc-editor-tab" data-tab="tutorial">💡 Tutoriel @media queries</div>
                </div>
                <div class="ssc-editor-container">
                    <div id="ssc-editor-panel-desktop" class="ssc-editor-panel active"><textarea id="ssc-css-editor-desktop"><?php echo esc_textarea(get_option('ssc_css_desktop', '')); ?></textarea></div>
                    <div id="ssc-editor-panel-tablet" class="ssc-editor-panel"><textarea id="ssc-css-editor-tablet"><?php echo esc_textarea(get_option('ssc_css_tablet', '')); ?></textarea></div>
                    <div id="ssc-editor-panel-mobile" class="ssc-editor-panel"><textarea id="ssc-css-editor-mobile"><?php echo esc_textarea(get_option('ssc_css_mobile', '')); ?></textarea></div>
                    <div id="ssc-editor-panel-tutorial" class="ssc-editor-panel ssc-tutorial-content">
                        <h3>Le Principe : "Desktop First" Simplifié</h3>
                        <p>Pensez à votre design comme à la construction d'une maison :</p>
                        <ol>
                            <li><strong>L'onglet <code>Desktop</code> est le plan de base de la maison.</strong> C'est ici que vous définissez tous les styles fondamentaux (couleurs, polices, espacements). Ces styles s'appliquent par défaut à <strong>toutes les tailles d'écran</strong>.</li>
                            <li><strong>L'onglet <code>Tablette</code> est l'aménagement pour les pièces moyennes.</strong> Vous ne redessinez pas tout, vous spécifiez uniquement les changements. Par exemple, réduire la taille d'un titre.</li>
                            <li><strong>L'onglet <code>Mobile</code> est pour les plus petites pièces.</strong> Vous faites les derniers ajustements pour que tout soit parfait sur un petit écran.</li>
                        </ol>
                        <p>En coulisses, le plugin enveloppe automatiquement le code des onglets Tablette et Mobile dans des <strong>@media queries</strong>, vous faisant gagner du temps.</p>
                        <hr>
                        <h4>Exemple Concret : Un Titre Adaptatif</h4>
                        <p><strong>Objectif :</strong> Un titre <code>.mon-titre</code> qui change de taille et d'alignement.</p>
                        <p><strong>1. Onglet <code>Desktop</code> (la base) :</strong></p>
                        <pre class="ssc-code">.mon-titre {
  font-size: 48px;
  color: blue;
  font-weight: bold;
}</pre>
                        <p><strong>2. Onglet <code>Tablette</code> (premier ajustement) :</strong></p>
                        <pre class="ssc-code">.mon-titre {
  font-size: 36px;
}</pre>
                        <p><strong>3. Onglet <code>Mobile</code> (ajustement final) :</strong></p>
                        <pre class="ssc-code">.mon-titre {
  font-size: 24px;
  text-align: center;
}</pre>
                    </div>
                </div>
            </div>
            <div class="ssc-preview-column">
                <div class="ssc-preview-header">
                    <div class="ssc-url-bar">
                        <input type="url" id="ssc-preview-url" value="<?php echo esc_url(get_home_url()); ?>">
                        <button class="button" id="ssc-preview-load"><?php echo esc_html__('Load', 'supersede-css-jlg'); ?></button>
                        <button class="button" id="ssc-element-picker-toggle" title="Cibler un élément">🎯</button>
                    </div>
                    <div class="ssc-responsive-toggles">
                        <button class="button button-primary" data-vp="desktop" title="Desktop">🖥️</button><button class="button" data-vp="tablet" title="Tablet">📲</button><button class="button" data-vp="mobile" title="Mobile">📱</button>
                    </div>
                </div>
                <div class="ssc-preview-frame-container">
                    <div id="ssc-picker-overlay"></div>
                    <div id="ssc-picker-tooltip"></div>
                    <iframe id="ssc-preview-frame" sandbox="allow-same-origin allow-forms allow-scripts"></iframe>
                </div>
                <div style="padding-top: 8px;">
                    <label>Sélecteur Ciblé :</label>
                    <input type="text" id="ssc-picked-selector" readonly class="large-text" placeholder="Cliquez sur 🎯 puis sur un élément dans l'aperçu.">
                </div>
            </div>
        </div>
    </div>
    <?php }
}
