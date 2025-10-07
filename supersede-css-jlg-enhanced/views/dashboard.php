<?php
if (!defined('ABSPATH')) {
    exit;
}
/** @var array{utilities?:string,tokens?:string,avatar?:string,debug_center?:string} $quick_links */
?>
<div class="ssc-app ssc-dashboard">
    <h1><?php echo esc_html__('Supersede CSS — Dashboard', 'supersede-css-jlg'); ?></h1>
    <p class="ssc-dashboard-intro"><?php echo esc_html__('Bienvenue ! Utilisez le menu latéral ou la palette de commande (⌘/Ctrl + K) pour accéder à vos studios créatifs, ou choisissez un raccourci ci-dessous.', 'supersede-css-jlg'); ?></p>

    <div class="ssc-dashboard-grid">
        <section class="ssc-dashboard-card" aria-labelledby="ssc-dashboard-quick-title">
            <div class="ssc-dashboard-card__header">
                <h2 id="ssc-dashboard-quick-title"><?php echo esc_html__('Accès rapide', 'supersede-css-jlg'); ?></h2>
                <p><?php echo esc_html__('Lancez directement les modules les plus utilisés sans quitter le tableau de bord.', 'supersede-css-jlg'); ?></p>
            </div>
            <ul class="ssc-dashboard-actions" role="list">
                <li>
                    <a class="button button-primary ssc-dashboard-action" href="<?php echo esc_url($quick_links['utilities'] ?? '#'); ?>">
                        <span><?php esc_html_e('Éditeur CSS responsive', 'supersede-css-jlg'); ?></span>
                        <span class="dashicons dashicons-arrow-right-alt" aria-hidden="true"></span>
                    </a>
                </li>
                <li>
                    <a class="button ssc-dashboard-action" href="<?php echo esc_url($quick_links['tokens'] ?? '#'); ?>">
                        <span><?php esc_html_e('Tokens Manager', 'supersede-css-jlg'); ?></span>
                        <span class="dashicons dashicons-arrow-right-alt" aria-hidden="true"></span>
                    </a>
                </li>
                <li>
                    <a class="button ssc-dashboard-action" href="<?php echo esc_url($quick_links['avatar'] ?? '#'); ?>">
                        <span><?php esc_html_e('Avatar Glow Presets', 'supersede-css-jlg'); ?></span>
                        <span class="dashicons dashicons-arrow-right-alt" aria-hidden="true"></span>
                    </a>
                </li>
                <li>
                    <a class="button ssc-dashboard-action" href="<?php echo esc_url($quick_links['debug_center'] ?? '#'); ?>">
                        <span><?php esc_html_e('Debug Center & Diagnostics', 'supersede-css-jlg'); ?></span>
                        <span class="dashicons dashicons-arrow-right-alt" aria-hidden="true"></span>
                    </a>
                </li>
            </ul>
            <p class="ssc-dashboard-note">
                <span class="dashicons dashicons-visibility" aria-hidden="true"></span>
                <span><?php echo esc_html__('Besoin d’un autre module ? Utilisez la palette de commande ou la navigation pour accéder aux générateurs d’effets, layouts et animations.', 'supersede-css-jlg'); ?></span>
            </p>
        </section>

        <section class="ssc-dashboard-card" aria-labelledby="ssc-dashboard-token-preview">
            <div class="ssc-dashboard-card__header">
                <span class="ssc-dashboard-card__eyebrow"><?php echo esc_html__('Nouveauté', 'supersede-css-jlg'); ?></span>
                <h2 id="ssc-dashboard-token-preview"><?php esc_html_e('Bloc « Token Preview »', 'supersede-css-jlg'); ?></h2>
            </div>
            <p><?php echo esc_html__('Dans l’éditeur de blocs WordPress, insérez « Supersede › Token Preview » pour afficher la bibliothèque de tokens (couleurs, espacements, effets) avec les mêmes styles que sur le front.', 'supersede-css-jlg'); ?></p>
            <p><?php echo esc_html__('Le bloc se met à jour automatiquement quand vous appliquez ou modifiez un preset. Idéal pour partager visuellement votre design system avec l’équipe éditoriale.', 'supersede-css-jlg'); ?></p>
        </section>

        <section class="ssc-dashboard-card" aria-labelledby="ssc-dashboard-workflow">
            <div class="ssc-dashboard-card__header">
                <h2 id="ssc-dashboard-workflow"><?php esc_html_e('Workflow pour activer un style', 'supersede-css-jlg'); ?></h2>
                <p><?php printf(wp_kses_post(__('Suivez ces trois étapes avec %1$s ou %2$s pour publier rapidement vos créations.', 'supersede-css-jlg')), '<strong>Avatar Glow</strong>', '<strong>Preset Designer</strong>'); ?></p>
            </div>
            <ol class="ssc-dashboard-steps">
                <li>
                    <div>
                        <strong><?php esc_html_e('Étape 1 : Créer et enregistrer', 'supersede-css-jlg'); ?></strong>
                        <p><?php printf(wp_kses_post(__('Ouvrez un module, personnalisez l’effet puis nommez-le avec une classe unique (ex.&nbsp;%1$s) avant de cliquer sur %2$s.', 'supersede-css-jlg')), '<code>.aura-speciale</code>', '<strong>'.esc_html__('« Enregistrer le preset »', 'supersede-css-jlg').'</strong>'); ?></p>
                        <em><?php echo esc_html__('Résultat : la recette est stockée dans votre bibliothèque Supersede, mais rien n’est encore injecté sur le site.', 'supersede-css-jlg'); ?></em>
                    </div>
                </li>
                <li>
                    <div>
                        <strong><?php esc_html_e('Étape 2 : Appliquer (activer)', 'supersede-css-jlg'); ?></strong>
                        <p><?php printf(wp_kses_post(__('Avec le preset sélectionné, cliquez sur %s pour ajouter le CSS au front office.', 'supersede-css-jlg')), '<strong>'.esc_html__('« Appliquer sur le site »', 'supersede-css-jlg').'</strong>'); ?></p>
                        <em><?php echo esc_html__('Résultat : la classe devient disponible pour toute l’équipe sur WordPress.', 'supersede-css-jlg'); ?></em>
                    </div>
                </li>
                <li>
                    <div>
                        <strong><?php esc_html_e('Étape 3 : Utiliser sur vos contenus', 'supersede-css-jlg'); ?></strong>
                        <p><?php printf(wp_kses_post(__('Dans l’éditeur de blocs, vos rédacteurs ajoutent la classe (%s) sur l’élément ciblé pour voir l’effet publié.', 'supersede-css-jlg')), '<code>aura-speciale</code>'); ?></p>
                        <em><?php echo esc_html__('Résultat : l’animation ou le style est visible sur le site public instantanément.', 'supersede-css-jlg'); ?></em>
                    </div>
                </li>
            </ol>
        </section>
    </div>
</div>
