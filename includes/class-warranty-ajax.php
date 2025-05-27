<?php
/**
 * Warranty AJAX Handler Class
 *
 * @package ZPOS
 * @subpackage ZPOS/includes
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class ZPOS_Warranty_Ajax {

    public function __construct() {
        add_action('wp_ajax_zpos_get_customers_list', array($this, 'ajax_get_customers_list'));
        add_action('wp_ajax_zpos_get_products_list', array($this, 'ajax_get_products_list'));
        add_action('wp_ajax_zpos_save_warranty_package', array($this, 'ajax_save_warranty_package'));
        add_action('wp_ajax_zpos_save_warranty', array($this, 'ajax_save_warranty'));
        add_action('wp_ajax_zpos_generate_serial_number', array($this, 'ajax_generate_serial_number'));
        add_action('wp_ajax_zpos_export_warranties', array($this, 'ajax_export_warranties'));
        add_action('wp_ajax_zpos_send_warranty_notifications', array($this, 'ajax_send_warranty_notifications'));
        add_action('wp_ajax_zpos_generate_warranty_report', array($this, 'ajax_generate_warranty_report'));
    }

    /**
     * Get customers list for warranty form
     */
    public function ajax_get_customers_list() {
        if (!check_ajax_referer('zpos_admin_nonce', 'nonce', false)) {
            wp_die('Security check failed');
        }

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
     * Get products list for warranty form
     */
    public function ajax_get_products_list() {
        if (!check_ajax_referer('zpos_admin_nonce', 'nonce', false)) {
            wp_die('Security check failed');
        }

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
                // Fallback to regular posts or custom implementation
                $product_query = new WP_Query(array(
                    'post_type' => 'post', // Change this to your product post type
                    'posts_per_page' => 100,
                    'post_status' => 'publish',
                    'orderby' => 'title',
                    'order' => 'ASC'
                ));

                foreach ($product_query->posts as $product) {
                    $products[] = array(
                        'id' => $product->ID,
                        'name' => $product->post_title,
                        'sku' => ''
                    );
                }
            }

            wp_send_json_success($products);

        } catch (Exception $e) {
            error_log('ZPOS Get Products Error: ' . $e->getMessage());
            wp_send_json_error('Failed to load products: ' . $e->getMessage());
        }
    }

    /**
     * Save warranty package
     */
    public function ajax_save_warranty_package() {
        if (!check_ajax_referer('zpos_admin_nonce', 'nonce', false)) {
            wp_die('Security check failed');
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }

        try {
            if (!class_exists('ZPOS_Warranty')) {
                require_once ZPOS_PLUGIN_DIR . 'includes/warranty.php';
            }

            $warranty_manager = new ZPOS_Warranty();
            
            $package_data = array(
                'name' => sanitize_text_field($_POST['name']),
                'duration_months' => intval($_POST['duration_months']),
                'price' => floatval($_POST['price']),
                'description' => sanitize_textarea_field($_POST['description']),
                'status' => sanitize_text_field($_POST['status'])
            );

            $package_id = !empty($_POST['package_id']) ? intval($_POST['package_id']) : 0;

            if ($package_id > 0) {
                $result = $warranty_manager->update_warranty_package($package_id, $package_data);
            } else {
                $result = $warranty_manager->create_warranty_package($package_data);
            }

            if ($result) {
                wp_send_json_success('Package saved successfully');
            } else {
                wp_send_json_error('Failed to save package');
            }

        } catch (Exception $e) {
            error_log('ZPOS Save Package Error: ' . $e->getMessage());
            wp_send_json_error('Error saving package: ' . $e->getMessage());
        }
    }

    /**
     * Save warranty
     */
    public function ajax_save_warranty() {
        if (!check_ajax_referer('zpos_admin_nonce', 'nonce', false)) {
            wp_die('Security check failed');
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }

        try {
            if (!class_exists('ZPOS_Warranty')) {
                require_once ZPOS_PLUGIN_DIR . 'includes/warranty.php';
            }

            $warranty_manager = new ZPOS_Warranty();
            
            $warranty_data = array(
                'customer_id' => intval($_POST['customer_id']),
                'product_id' => intval($_POST['product_id']),
                'package_id' => intval($_POST['package_id']),
                'serial_number' => sanitize_text_field($_POST['serial_number']),
                'purchase_date' => sanitize_text_field($_POST['purchase_date']),
                'notes' => sanitize_textarea_field($_POST['notes'])
            );

            $warranty_id = !empty($_POST['warranty_id']) ? intval($_POST['warranty_id']) : 0;

            if ($warranty_id > 0) {
                $result = $warranty_manager->update_warranty($warranty_id, $warranty_data);
            } else {
                $result = $warranty_manager->create_warranty($warranty_data);
            }

            if ($result) {
                wp_send_json_success('Warranty saved successfully');
            } else {
                wp_send_json_error('Failed to save warranty');
            }

        } catch (Exception $e) {
            error_log('ZPOS Save Warranty Error: ' . $e->getMessage());
            wp_send_json_error('Error saving warranty: ' . $e->getMessage());
        }
    }

    /**
     * Generate serial number
     */
    public function ajax_generate_serial_number() {
        if (!check_ajax_referer('zpos_admin_nonce', 'nonce', false)) {
            wp_die('Security check failed');
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }

        try {
            // Generate a unique serial number
            $serial = 'WR-' . date('Y') . '-' . strtoupper(wp_generate_password(8, false));
            
            wp_send_json_success($serial);

        } catch (Exception $e) {
            error_log('ZPOS Generate Serial Error: ' . $e->getMessage());
            wp_send_json_error('Error generating serial number: ' . $e->getMessage());
        }
    }

    /**
     * Export warranties
     */
    public function ajax_export_warranties() {
        if (!check_ajax_referer('zpos_admin_nonce', 'nonce', false)) {
            wp_die('Security check failed');
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }

        // This would typically generate and download a CSV file
        wp_send_json_success('Export functionality would be implemented here');
    }

    /**
     * Send warranty notifications
     */
    public function ajax_send_warranty_notifications() {
        if (!check_ajax_referer('zpos_admin_nonce', 'nonce', false)) {
            wp_die('Security check failed');
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }

        // This would typically send email notifications
        wp_send_json_success('Notifications would be sent here');
    }

    /**
     * Generate warranty report
     */
    public function ajax_generate_warranty_report() {
        if (!check_ajax_referer('zpos_admin_nonce', 'nonce', false)) {
            wp_die('Security check failed');
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }

        // This would typically generate report data
        $report_html = '<p>Report functionality would be implemented here</p>';
        
        wp_send_json_success(array('html' => $report_html));
    }
}

// Initialize the AJAX handler
new ZPOS_Warranty_Ajax();
