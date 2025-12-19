<?php
/**
 * Plugin Name: Prompt Codex Client Galleries
 * Description: Client gallery management with ratings and submissions.
 * Version: 1.0.0
 * Author: Prompt Codex
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'CG_VERSION', '1.0.0' );
define( 'CG_PLUGIN_FILE', __FILE__ );
define( 'CG_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'CG_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

autoload_files();

function autoload_files() {
    $files = array(
        'includes/helpers.php',
        'includes/class-cg-activator.php',
        'includes/class-cg-deactivator.php',
        'includes/class-cg-db.php',
        'includes/class-cg-security.php',
        'includes/class-cg-plugin.php',
        'includes/class-cg-cpt.php',
        'includes/class-cg-metabox.php',
        'includes/class-cg-media.php',
        'includes/class-cg-ajax-admin.php',
        'includes/class-cg-ajax-front.php',
        'includes/class-cg-shortcodes.php',
        'includes/class-cg-email.php',
    );

    foreach ( $files as $file ) {
        $path = CG_PLUGIN_DIR . $file;
        if ( file_exists( $path ) ) {
            require_once $path;
        }
    }
}

register_activation_hook( __FILE__, array( 'CG_Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'CG_Deactivator', 'deactivate' ) );

add_action( 'plugins_loaded', array( 'CG_Plugin', 'instance' ) );

// Validation: Step 1 bootstrap complet
