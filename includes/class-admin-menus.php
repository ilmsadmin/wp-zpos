<?php
/**
 * Admin Menu Class
 *
 * Handles the admin menu and AJAX actions for the plugin.
 *
 * @package    ZPOS
 * @subpackage ZPOS/includes
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class ZPOS_Admin_Menus {
    /**
     * Constructor
     */
    public function __construct() {
        // Add admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));

        // Register AJAX handlers
        add_action('wp_ajax_zpos_get_warranties', array($this, 'ajax_get_warranties'));
        add_action('wp_ajax_zpos_save_warranty', array($this, 'ajax_save_warranty'));
        add_action('wp_ajax_zpos_delete_warranty', array($this, 'ajax_delete_warranty'));
        add_action('wp_ajax_zpos_export_warranties', array($this, 'ajax_export_warranties'));
        add_action('wp_ajax_zpos_send_warranty_notifications', array($this, 'ajax_send_warranty_notifications'));
        add_action('wp_ajax_zpos_get_customers_list', array($this, 'ajax_get_customers_list'));
        add_action('wp_ajax_zpos_get_products_list', array($this, 'ajax_get_products_list'));
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            'ZPOS',
            'ZPOS',
            'manage_options',
            'zpos',
            array($this, 'admin_page'),
            'dashicons-admin-generic',
            6
        );
    }

    /**
     * Admin page callback
     */
    public function admin_page() {
        // Check permissions
        if (!current_user_can('manage_options')) {
            return;
        }

        // Render admin page
        include_once plugin_dir_path(__FILE__) . 'partials/admin-display.php';
    }

    /**
     * AJAX handler to get warranties
     */
    public function ajax_get_warranties() {
        // Check nonce
        if (!check_ajax_referer('zpos_admin_nonce', 'nonce', false)) {
            wp_send_json_error('Security check failed');
        }

        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }

        // ...existing code for getting warranties...
    }

    /**
     * AJAX handler to save warranty
     */
    public function ajax_save_warranty() {
        // Check nonce
        if (!check_ajax_referer('zpos_admin_nonce', 'nonce', false)) {
            wp_send_json_error('Security check failed');
        }

        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }

        // ...existing code for saving warranty...
    }

    /**
     * AJAX handler to delete warranty
     */
    public function ajax_delete_warranty() {
        // Check nonce
        if (!check_ajax_referer('zpos_admin_nonce', 'nonce', false)) {
            wp_send_json_error('Security check failed');
        }

        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }

        // ...existing code for deleting warranty...
    }

    /**
     * AJAX handler to export warranties
     */
    public function ajax_export_warranties() {
        // Check nonce
        if (!check_ajax_referer('zpos_admin_nonce', 'nonce', false)) {
            wp_send_json_error('Security check failed');
        }

        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }

        // ...existing code for exporting warranties...
    }

    /**
     * AJAX handler to send warranty notifications
     */
    public function ajax_send_warranty_notifications() {
        // Check nonce
        if (!check_ajax_referer('zpos_admin_nonce', 'nonce', false)) {
            wp_send_json_error('Security check failed');
        }

        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }

        // ...existing code for sending warranty notifications...
    }

    /**
     * AJAX handler to get customers list
     */
    public function ajax_get_customers_list() {
        // Check nonce
        if (!check_ajax_referer('zpos_admin_nonce', 'nonce', false)) {
            wp_send_json_error('Security check failed');
        }

        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }

        try {
            $customers = array();

            // Try to get WooCommerce customers first
            if (class_exists('WooCommerce')) {
                $customer_query = new WP_User_Query(array(
                    'role' => 'customer',
                    'number' => 100,
                    'orderby' => 'display_name',
                    'order' => 'ASC'
                ));

                foreach ($customer_query->get_results() as $user) {
                    $customers[] = array(
                        'id' => $user->ID,
                        'name' => $user->display_name . ' (' . $user->user_email . ')',
                        'email' => $user->user_email
                    );
                }
            } else {
                // Fallback to all users
                $users = get_users(array(
                    'number' => 100,
                    'orderby' => 'display_name',
                    'order' => 'ASC'
                ));

                foreach ($users as $user) {
                    $customers[] = array(
                        'id' => $user->ID,
                        'name' => $user->display_name . ' (' . $user->user_email . ')',
                        'email' => $user->user_email
                    );
                }
            }

            wp_send_json_success($customers);

        } catch (Exception $e) {
            error_log('ZPOS Get Customers Error: ' . $e->getMessage());
            wp_send_json_error('Failed to load customers: ' . $e->getMessage());
        }
    }

    /**
     * AJAX handler to get products list
     */
    public function ajax_get_products_list() {
        // Check nonce
        if (!check_ajax_referer('zpos_admin_nonce', 'nonce', false)) {
            wp_send_json_error('Security check failed');
        }

        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }

        try {
            $products = array();

            // Try to get WooCommerce products first
            if (class_exists('WooCommerce')) {
                $product_query = new WP_Query(array(
                    'post_type' => 'product',
                    'posts_per_page' => 100,
                    'post_status' => 'publish',
                    'orderby' => 'title',
                    'order' => 'ASC'
                ));

                foreach ($product_query->posts as $product) {
                    $products[] = array(
                        'id' => $product->ID,
                        'name' => $product->post_title,
                        'sku' => get_post_meta($product->ID, '_sku', true)
                    );
                }
            } else {
                // Fallback - you might have custom product implementation
                global $wpdb;
                $table_name = $wpdb->prefix . 'zpos_products';
                
                // Check if custom products table exists
                if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name) {
                    $results = $wpdb->get_results("SELECT id, name, sku FROM $table_name WHERE status = 'active' ORDER BY name ASC LIMIT 100");
                    
                    foreach ($results as $product) {
                        $products[] = array(
                            'id' => $product->id,
                            'name' => $product->name,
                            'sku' => $product->sku
                        );
                    }
                }
            }

            wp_send_json_success($products);

        } catch (Exception $e) {
            error_log('ZPOS Get Products Error: ' . $e->getMessage());
            wp_send_json_error('Failed to load products: ' . $e->getMessage());
        }
    }
}