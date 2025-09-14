<?php declare(strict_types=1);
namespace SSC\Admin\Pages; if (!defined('ABSPATH')) { exit; }
class AvatarGlow {
    public function render(){ ?>
    <div class="ssc-app ssc-fullwidth">
        <h2>✨ Gestionnaire de Presets Avatar Glow</h2>
        <p>Créez et gérez des effets d'aura réutilisables pour vos rédacteurs. Chaque preset aura son propre nom de classe.</p>
        
        <div class="ssc-two" style="align-items: flex-start;">
            <div class="ssc-pane">
                <h3>Éditeur de Presets</h3>
                <label><strong>Preset Actif</strong></label>
                <div class="ssc-actions">
                    <select id="ssc-glow-preset-select" class="regular-text" style="flex: 1;"></select>
                    <button id="ssc-glow-new-preset" class="button">Nouveau</button>
                </div>

                <div id="ssc-glow-editor-fields">
                    <hr>
                    <div class="ssc-two">
                        <div><label><strong>Nom du Preset</strong></label><input type="text" id="ssc-glow-preset-name" class="regular-text" placeholder="Aura Bleue Rapide"></div>
                        <div><label><strong>Nom de la Classe CSS</strong></label><input type="text" id="ssc-glow-preset-class" class="regular-text" placeholder=".avatar-glow-blue"></div>
                    </div>
                    <p class="description">Le nom de la classe doit être unique et commencer par un point (ex: <code>.glow-team-1</code>).</p>
                    <hr>
                    
                    <h4>Paramètres de l'Effet</h4>
                    <label><strong>Couleur du dégradé</strong></label>
                    <div class="ssc-actions">
                        <span>Début :</span> <input type="color" id="ssc-glow-color1" value="#8b5cf6">
                        <span>Fin :</span> <input type="color" id="ssc-glow-color2" value="#ec4899">
                    </div>
                    <label style="margin-top:16px;"><strong>Vitesse (secondes)</strong></label>
                    <input type="range" id="ssc-glow-speed" min="1" max="20" value="5" step="0.5">
                    <span id="ssc-glow-speed-val">5s</span>
                    <label style="margin-top:16px;"><strong>Épaisseur (pixels)</strong></label>
                    <input type="range" id="ssc-glow-thickness" min="2" max="12" value="4" step="1">
                    <span id="ssc-glow-thickness-val">4px</span>
                </div>

                <div class="ssc-actions" style="margin-top:24px; border-top: 1px solid var(--ssc-border); padding-top: 16px;">
                    <button id="ssc-glow-save-preset" class="button button-primary">Enregistrer ce Preset</button>
                    <button id="ssc-glow-apply" class="button">Appliquer sur le site</button>
                    <button id="ssc-glow-delete-preset" class="button button-link-delete" style="display: none;">Supprimer ce Preset</button>
                </div>
            </div>
            
            <div class="ssc-pane">
                <h3>Aperçu en Direct</h3>
                <div id="ssc-glow-preview-bg" style="display:grid; place-items:center; height:250px; background: #0b1020; border-radius: 12px; transition: background 0.3s; border: 1px solid var(--ssc-border);">
                    <div id="ssc-glow-preview-container" style="width: 128px; height: 128px;">
                        <img id="ssc-glow-preview-img" src="<?php echo esc_url(SSC_PLUGIN_URL . 'assets/images/placeholder-avatar.png'); ?>" alt="avatar" style="width:100%; height:100%; border-radius:50%; object-fit:cover;">
                    </div>
                </div>
                <button id="ssc-glow-upload-btn" class="button" style="margin-top:16px;">Changer l'image d'avatar</button>

                 <h4 style="margin-top:16px;">Comment l'utiliser ?</h4>
                 <p class="description">Une fois le preset enregistré et appliqué, demandez à vos rédacteurs d'ajouter la classe <code id="ssc-glow-how-to-use-class">.avatar-glow-blue</code> au conteneur (la `div`) de leur image.</p>
                 <pre id="ssc-glow-css-output" class="ssc-code"></pre>
            </div>
        </div>
    <?php }
}