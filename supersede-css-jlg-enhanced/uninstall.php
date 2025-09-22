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
    'ssc_tokens_registry',
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

// Supprime les options du site courant
foreach ($ssc_options_to_delete as $option_name) {
    delete_option($option_name);
}

if (!is_multisite()) {
    return;
}

// Supprime également les éventuelles options réseau
foreach ($ssc_options_to_delete as $option_name) {
    delete_site_option($option_name);
}

// Parcourt tous les sites du réseau pour supprimer les options locales
$site_ids = get_sites([
    'fields' => 'ids',
    'number' => 0,
]);

foreach ($site_ids as $site_id) {
    switch_to_blog($site_id);

    foreach ($ssc_options_to_delete as $option_name) {
        delete_option($option_name);
    }

    restore_current_blog();
}

