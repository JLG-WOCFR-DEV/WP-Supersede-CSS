<?php
// Si WordPress n'appelle pas ce fichier, ne rien faire.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Liste de toutes les options à supprimer
$ssc_options_to_delete = [
    'ssc_admin_log',
    'ssc_presets',
    'ssc_active_css',
    'ssc_tokens_css',
    'ssc_settings', // Legacy
    'ssc_secret', // Legacy
    'ssc_modules_enabled', // Legacy
    'ssc_safe_mode', // Legacy
    'ssc_css_desktop', // Ajouté
    'ssc_css_tablet', // Ajouté
    'ssc_css_mobile', // Ajouté
    'ssc_avatar_glow_presets', // Ajouté
    'ssc_optimization_settings', // Ajouté
];

// Boucle sur la liste et supprime chaque option
foreach ($ssc_options_to_delete as $option_name) {
    if (is_multisite()) {
        delete_site_option($option_name);
    } else {
        delete_option($option_name);
    }
}
