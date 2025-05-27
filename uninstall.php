<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * When populating this file, consider the following flow
 * of control:
 *
 * - This method should be static
 * - Check if the $_REQUEST content actually is the plugin name
 * - Run an admin referrer check to make sure it goes through authentication
 * - Verify the output of $_GET makes sense
 * - Repeat with other user roles. Best directly by using the links/query string parameters.
 * - Repeat things for multisite. Once for a single site in the network, once sitewide.
 *
 * @link       https://yourwebsite.com
 * @since      1.0.0
 *
 * @package    ZPOS
 */

// If uninstall not called from WordPress, then exit.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

/**
 * Clean up database and options when plugin is uninstalled
 */
class ZPOS_Uninstaller {
    
    /**
     * Run uninstall process
     */
    public static function uninstall() {
        global $wpdb;
        
        // Check if user wants to keep data
        $keep_data = get_option('zpos_keep_data_on_uninstall', false);
        
        if (!$keep_data) {
            // Remove database tables
            self::drop_tables();
            
            // Remove options
            self::remove_options();
            
            // Remove user meta
            self::remove_user_meta();
            
            // Clear any cached data
            self::clear_cache();
        }
    }
    
    /**
     * Drop all ZPOS tables
     */
    private static function drop_tables() {
        global $wpdb;
        
        $tables = [
            $wpdb->prefix . 'zpos_products',
            $wpdb->prefix . 'zpos_customers',
            $wpdb->prefix . 'zpos_orders',
            $wpdb->prefix . 'zpos_order_items',
            $wpdb->prefix . 'zpos_inventory',
            $wpdb->prefix . 'zpos_warranties',
            $wpdb->prefix . 'zpos_warranty_packages',
            $wpdb->prefix . 'zpos_settings',
            $wpdb->prefix . 'zpos_categories'
        ];
        
        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS {$table}");
        }
    }
    
    /**
     * Remove all ZPOS options
     */
    private static function remove_options() {
        $options = [
            'zpos_version',
            'zpos_setup_completed',
            'zpos_woocommerce_sync_enabled',
            'zpos_currency',
            'zpos_timezone',
            'zpos_store_name',
            'zpos_store_address',
            'zpos_store_phone',
            'zpos_store_email',
            'zpos_low_stock_threshold',
            'zpos_tax_rate',
            'zpos_receipt_template',
            'zpos_email_notifications',
            'zpos_keep_data_on_uninstall'
        ];
        
        foreach ($options as $option) {
            delete_option($option);
        }
    }
    
    /**
     * Remove ZPOS user meta
     */
    private static function remove_user_meta() {
        global $wpdb;
        
        $wpdb->query("DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE 'zpos_%'");
    }
    
    /**
     * Clear any cached data
     */
    private static function clear_cache() {
        wp_cache_flush();
        
        // Clear any transients
        global $wpdb;
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_zpos_%'");
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_zpos_%'");
    }
}

// Run the uninstaller
ZPOS_Uninstaller::uninstall();
