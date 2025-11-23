<?php
/**
 * Plugin Name: Client Galleries
 * Description: Photo client galleries with email-based selections.
 * Version: 1.0.0
 * Author: OpenAI Assistant
 * Text Domain: client-galleries
 */

if (! defined('ABSPATH')) {
    exit;
}

define('CG_VERSION', '1.0.0');
define('CG_PLUGIN_FILE', __FILE__);
define('CG_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CG_PLUGIN_URL', plugin_dir_url(__FILE__));

require_once CG_PLUGIN_DIR . 'includes/helpers.php';
require_once CG_PLUGIN_DIR . 'includes/class-plugin.php';
require_once CG_PLUGIN_DIR . 'includes/class-activator.php';
require_once CG_PLUGIN_DIR . 'includes/class-deactivator.php';
require_once CG_PLUGIN_DIR . 'includes/class-db.php';
require_once CG_PLUGIN_DIR . 'includes/class-cpt-gallery.php';
require_once CG_PLUGIN_DIR . 'includes/class-media.php';
require_once CG_PLUGIN_DIR . 'includes/class-shortcodes.php';
require_once CG_PLUGIN_DIR . 'includes/class-ajax.php';
require_once CG_PLUGIN_DIR . 'includes/class-mailer.php';
require_once CG_PLUGIN_DIR . 'includes/class-admin-pages.php';
require_once CG_PLUGIN_DIR . 'includes/class-security.php';

register_activation_hook(__FILE__, ['CG_Activator', 'activate']);
register_deactivation_hook(__FILE__, ['CG_Deactivator', 'deactivate']);

add_action('plugins_loaded', ['CG_Plugin', 'instance']);
