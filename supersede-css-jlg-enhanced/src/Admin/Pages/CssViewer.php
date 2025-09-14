<?php declare(strict_types=1);
namespace SSC\Admin\Pages;

if (!defined('ABSPATH')) { exit; }

class CssViewer {
    public function render(){ 
        // Récupérer le contenu des options CSS
        $active_css = get_option('ssc_active_css', '/* Cette option est vide. */');
        $tokens_css = get_option('ssc_tokens_css', '/* Cette option est vide. */');
        ?>
        <div class="ssc-app ssc-fullwidth">
            <h2>🔍 Visualiseur de CSS Actif</h2>
            <p>Ce module affiche le contenu brut des options CSS de Supersede telles qu'elles sont enregistrées dans votre base de données. C'est un outil de débogage utile pour voir le code final appliqué à votre site.</p>

            <div class="ssc-panel" style="margin-top: 16px;">
                <h3>Contenu de : <code>ssc_active_css</code></h3>
                <p class="description">C'est la feuille de style principale où la plupart des modules (Utilities, effets visuels, etc.) enregistrent leur code.</p>
                <textarea readonly class="large-text code" rows="15" style="background: #f0f0f0; color: #333; font-family: monospace; width: 100%;"><?php echo esc_textarea($active_css); ?></textarea>
            </div>
            
            <div class="ssc-panel" style="margin-top: 16px;">
                <h3>Contenu de : <code>ssc_tokens_css</code></h3>
                <p class="description">Cette option contient les variables CSS (Tokens) que vous avez définies dans le "Tokens Manager".</p>
                <textarea readonly class="large-text code" rows="10" style="background: #f0f0f0; color: #333; font-family: monospace; width: 100%;"><?php echo esc_textarea($tokens_css); ?></textarea>
            </div>
        </div>
    <?php }
}