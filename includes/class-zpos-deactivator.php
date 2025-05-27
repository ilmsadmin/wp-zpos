<?php
/**
 * Fired during plugin deactivation
 *
 * @link       https://yourwebsite.com
 * @since      1.0.0
 *
 * @package    ZPOS
 * @subpackage ZPOS/includes
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    ZPOS
 * @subpackage ZPOS/includes
 * @author     Your Name <your.email@example.com>
 */
class ZPOS_Deactivator {

    /**
     * Short Description. (use period)
     *
     * Long Description.
     *
     * @since    1.0.0
     */
    public static function deactivate() {
        // Clear scheduled events
        self::clear_scheduled_events();
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Clear any cached data
        self::clear_cache();
        
        // Log deactivation
        error_log('ZPOS Plugin deactivated at ' . current_time('mysql'));
    }

    /**
     * Clear scheduled events.
     *
     * @since    1.0.0
     */
    private static function clear_scheduled_events() {
        // Clear WooCommerce sync cron job
        $timestamp = wp_next_scheduled('zpos_woocommerce_sync');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'zpos_woocommerce_sync');
        }

        // Clear inventory alerts cron job
        $timestamp = wp_next_scheduled('zpos_inventory_alerts');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'zpos_inventory_alerts');
        }

        // Clear warranty expiration alerts cron job
        $timestamp = wp_next_scheduled('zpos_warranty_alerts');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'zpos_warranty_alerts');
        }

        // Clear reports cache cleanup cron job
        $timestamp = wp_next_scheduled('zpos_cleanup_cache');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'zpos_cleanup_cache');
        }
    }

    /**
     * Clear cached data.
     *
     * @since    1.0.0
     */
    private static function clear_cache() {
        // Clear WordPress cache
        wp_cache_flush();

        // Clear ZPOS transients
        global $wpdb;
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_zpos_%'");
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_zpos_%'");
    }
}
