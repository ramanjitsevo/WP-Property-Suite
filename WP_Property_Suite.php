<?php
/**
 * Plugin Name: Evolvan Real Estate Listings
 * Plugin URI: https://evolvan.com/wp-property-suite/
 * Description: A React-powered real estate plugin for managing property listings, search filters, lead capture, and single-property pages.
 * Version: 1.0.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author: Evolvan Info Solutions
 * Author URI: https://evolvan.com/
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wps
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit;
}

define('WPS_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('WPS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WPS_PLUGIN_VERSION', '1.0.0');

require_once WPS_PLUGIN_PATH . 'includes/helpers.php';
require_once WPS_PLUGIN_PATH . 'includes/post-types.php';
require_once WPS_PLUGIN_PATH . 'includes/email-templates.php';
require_once WPS_PLUGIN_PATH . 'includes/frontend.php';
require_once WPS_PLUGIN_PATH . 'includes/rest-api.php';
require_once WPS_PLUGIN_PATH . 'includes/demo-data.php';
require_once WPS_PLUGIN_PATH . 'admin/settings.php';
require_once WPS_PLUGIN_PATH . 'admin/admin.php';

/**
 * Initialize plugin hooks.
 */
function wps_init() {
    add_action('init', 'wps_register_post_type');
    add_action('init', 'wps_register_taxonomies');
    add_action('rest_api_init', 'wps_register_routes');
    add_shortcode('wps_search', 'wps_shortcode');
    add_shortcode('wps_recent_properties', 'wps_recent_properties_shortcode');
    add_shortcode('wps_featured_properties', 'wps_featured_properties_shortcode');
    add_action('wp_enqueue_scripts', 'wps_enqueue_assets');
    add_action('wp', 'wps_handle_layout');
    add_action('wp_head', 'wps_default_boxed_container_width', 15);
}
add_action('plugins_loaded', 'wps_init');

/**
 * Activation hook.
 */
function wps_activate() {
    wps_register_post_type();
    wps_register_taxonomies();
    wps_create_leads_table();

    flush_rewrite_rules();
    
    // Install default demo properties on activation
    wps_install_default_data();
    
    set_transient('wps_show_activation_notice', true, 60);
}
register_activation_hook(__FILE__, 'wps_activate');

add_action('plugins_loaded', 'wps_create_leads_table');

/**
 * Deactivation hook.
 */
function wps_deactivate() {
    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'wps_deactivate');
