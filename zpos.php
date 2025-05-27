<?php
/**
 * Plugin Name: ZPOS - Point of Sale System
 * Plugin URI: https://yourwebsite.com/zpos
 * Description: A comprehensive Point of Sale system for WordPress with optional WooCommerce integration, inventory management, warranty tracking, and customer management.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://yourwebsite.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: zpos
 * Domain Path: /languages
 * Requires at least: 6.0
 * Tested up to: 6.6
 * Requires PHP: 7.4
 * Network: false
 *
 * @package ZPOS
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Currently plugin version.
 */
define('ZPOS_VERSION', '1.0.0');

/**
 * Plugin root file.
 */
define('ZPOS_PLUGIN_FILE', __FILE__);

/**
 * Plugin root directory.
 */
define('ZPOS_PLUGIN_DIR', plugin_dir_path(__FILE__));

/**
 * Plugin root URL.
 */
define('ZPOS_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Plugin basename.
 */
define('ZPOS_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * The code that runs during plugin activation.
 */
function activate_zpos() {
    require_once ZPOS_PLUGIN_DIR . 'includes/class-zpos-activator.php';
    ZPOS_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_zpos() {
    require_once ZPOS_PLUGIN_DIR . 'includes/class-zpos-deactivator.php';
    ZPOS_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_zpos');
register_deactivation_hook(__FILE__, 'deactivate_zpos');

/**
 * Redirect to setup wizard after activation.
 */
add_action('admin_init', function() {
    require_once ZPOS_PLUGIN_DIR . 'includes/class-zpos-activator.php';
    ZPOS_Activator::activation_redirect();
});

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require ZPOS_PLUGIN_DIR . 'includes/class-zpos.php';

/**
 * Begins execution of the plugin.
 */
function run_zpos() {
    $plugin = new ZPOS();
    $plugin->run();
}
run_zpos();
