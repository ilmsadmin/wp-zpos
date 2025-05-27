<?php
/**
 * Settings System for ZPOS
 *
 * @link       https://yourwebsite.com
 * @since      1.0.0
 *
 * @package    ZPOS
 * @subpackage ZPOS/includes
 */

/**
 * Settings System class.
 *
 * Handles all settings functionality for ZPOS including general settings,
 * WooCommerce sync, currency, timezone, store information, and customization.
 *
 * @package    ZPOS
 * @subpackage ZPOS/includes
 * @author     Your Name <your.email@example.com>
 */
class ZPOS_Settings {

    /**
     * The database instance.
     *
     * @since    1.0.0
     * @access   private
     * @var      object    $db    Database instance
     */
    private $db;

    /**
     * Settings option name.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $option_name    Settings option name
     */
    private $option_name = 'zpos_settings';

    /**
     * Default settings.
     *
     * @since    1.0.0
     * @access   private
     * @var      array    $default_settings    Default settings
     */
    private $default_settings = array();

    /**
     * Initialize the settings system.
     *
     * @since    1.0.0
     */
    public function __construct() {
        global $wpdb;
        $this->db = $wpdb;

        // Set default settings
        $this->set_default_settings();

        // Initialize AJAX handlers
        add_action('wp_ajax_zpos_save_settings', array($this, 'ajax_save_settings'));
        add_action('wp_ajax_zpos_reset_settings', array($this, 'ajax_reset_settings'));
        add_action('wp_ajax_zpos_test_woocommerce', array($this, 'ajax_test_woocommerce'));
        add_action('wp_ajax_zpos_rerun_wizard', array($this, 'ajax_rerun_wizard'));
        add_action('wp_ajax_zpos_sync_woocommerce', array($this, 'ajax_sync_woocommerce'));
    }

    /**
     * Set default settings.
     *
     * @since    1.0.0
     */
    private function set_default_settings() {
        $this->default_settings = array(
            // General Settings
            'store_name' => get_bloginfo('name'),
            'store_address' => '',
            'store_city' => '',
            'store_country' => 'VN',
            'store_phone' => '',
            'store_email' => get_option('admin_email'),
            
            // Currency & Locale
            'currency' => 'VND',
            'currency_symbol' => '₫',
            'currency_position' => 'right',
            'thousand_separator' => ',',
            'decimal_separator' => '.',
            'number_decimals' => 0,
            'timezone' => 'Asia/Ho_Chi_Minh',
            
            // WooCommerce Integration
            'woocommerce_enabled' => false,
            'sync_products' => true,
            'sync_customers' => true,
            'sync_orders' => true,
            'auto_sync' => false,
            'sync_interval' => 'hourly',
            
            // Inventory Settings
            'low_stock_threshold' => 10,
            'out_of_stock_threshold' => 0,
            'enable_stock_alerts' => true,
            'auto_reduce_stock' => true,
            
            // Interface Customization
            'theme_color' => '#0073aa',
            'dashboard_layout' => 'grid',
            'items_per_page' => 20,
            'enable_dark_mode' => false,
            'show_dashboard_widgets' => true,
            
            // POS Settings
            'default_customer' => 0,
            'enable_receipt_printing' => true,
            'receipt_template' => 'default',
            'enable_barcode_scanner' => false,
            
            // Security & Performance
            'enable_logging' => true,
            'log_level' => 'error',
            'cache_enabled' => true,
            'cache_duration' => 3600,
            
            // Notifications
            'enable_email_notifications' => true,
            'low_stock_email' => true,
            'new_order_email' => true,
            'daily_report_email' => false
        );
    }

    /**
     * Get all settings.
     *
     * @since    1.0.0
     * @return   array    Settings array
     */
    public function get_settings() {
        $settings = get_option($this->option_name, array());
        return wp_parse_args($settings, $this->default_settings);
    }

    /**
     * Get a specific setting.
     *
     * @since    1.0.0
     * @param    string    $key    Setting key
     * @param    mixed     $default    Default value
     * @return   mixed     Setting value
     */
    public function get_setting($key, $default = null) {
        $settings = $this->get_settings();
        return isset($settings[$key]) ? $settings[$key] : $default;
    }

    /**
     * Save settings.
     *
     * @since    1.0.0
     * @param    array    $new_settings    New settings to save
     * @return   bool     True on success, false on failure
     */
    public function save_settings($new_settings) {
        $current_settings = $this->get_settings();
        $updated_settings = wp_parse_args($new_settings, $current_settings);
        
        // Sanitize settings
        $updated_settings = $this->sanitize_settings($updated_settings);
        
        // Validate settings
        $validation_result = $this->validate_settings($updated_settings);
        if (is_wp_error($validation_result)) {
            return $validation_result;
        }
        
        return update_option($this->option_name, $updated_settings);
    }

    /**
     * Sanitize settings.
     *
     * @since    1.0.0
     * @param    array    $settings    Settings to sanitize
     * @return   array    Sanitized settings
     */
    private function sanitize_settings($settings) {
        $sanitized = array();
        
        // Text fields
        $text_fields = array(
            'store_name', 'store_address', 'store_city', 'store_country',
            'store_phone', 'store_email', 'currency', 'currency_symbol',
            'currency_position', 'thousand_separator', 'decimal_separator',
            'timezone', 'sync_interval', 'theme_color', 'dashboard_layout',
            'receipt_template', 'log_level'
        );
        
        foreach ($text_fields as $field) {
            if (isset($settings[$field])) {
                $sanitized[$field] = sanitize_text_field($settings[$field]);
            }
        }
        
        // Email fields
        if (isset($settings['store_email'])) {
            $sanitized['store_email'] = sanitize_email($settings['store_email']);
        }
        
        // Integer fields
        $int_fields = array(
            'number_decimals', 'low_stock_threshold', 'out_of_stock_threshold',
            'items_per_page', 'default_customer', 'cache_duration'
        );
        
        foreach ($int_fields as $field) {
            if (isset($settings[$field])) {
                $sanitized[$field] = intval($settings[$field]);
            }
        }
        
        // Boolean fields
        $bool_fields = array(
            'woocommerce_enabled', 'sync_products', 'sync_customers', 'sync_orders',
            'auto_sync', 'enable_stock_alerts', 'auto_reduce_stock', 'enable_dark_mode',
            'show_dashboard_widgets', 'enable_receipt_printing', 'enable_barcode_scanner',
            'enable_logging', 'cache_enabled', 'enable_email_notifications',
            'low_stock_email', 'new_order_email', 'daily_report_email'
        );
        
        foreach ($bool_fields as $field) {
            if (isset($settings[$field])) {
                $sanitized[$field] = (bool) $settings[$field];
            }
        }
        
        return $sanitized;
    }

    /**
     * Validate settings.
     *
     * @since    1.0.0
     * @param    array    $settings    Settings to validate
     * @return   bool|WP_Error    True on success, WP_Error on failure
     */
    private function validate_settings($settings) {
        $errors = array();
        
        // Validate email
        if (!empty($settings['store_email']) && !is_email($settings['store_email'])) {
            $errors[] = __('Invalid store email address.', 'zpos');
        }
        
        // Validate currency
        if (empty($settings['currency'])) {
            $errors[] = __('Currency is required.', 'zpos');
        }
        
        // Validate thresholds
        if ($settings['low_stock_threshold'] < 0) {
            $errors[] = __('Low stock threshold must be 0 or greater.', 'zpos');
        }
        
        if ($settings['out_of_stock_threshold'] < 0) {
            $errors[] = __('Out of stock threshold must be 0 or greater.', 'zpos');
        }
        
        // Validate theme color
        if (!empty($settings['theme_color']) && !preg_match('/^#[a-f0-9]{6}$/i', $settings['theme_color'])) {
            $errors[] = __('Invalid theme color format.', 'zpos');
        }
        
        if (!empty($errors)) {
            return new WP_Error('validation_failed', implode(' ', $errors));
        }
        
        return true;
    }

    /**
     * Reset settings to default.
     *
     * @since    1.0.0
     * @return   bool    True on success, false on failure
     */
    public function reset_settings() {
        return update_option($this->option_name, $this->default_settings);
    }

    /**
     * Test WooCommerce connection.
     *
     * @since    1.0.0
     * @return   array    Test results
     */
    public function test_woocommerce_connection() {
        $results = array(
            'plugin_active' => false,
            'version' => '',
            'products_count' => 0,
            'customers_count' => 0,
            'orders_count' => 0,
            'status' => 'error',
            'message' => ''
        );
        
        // Check if WooCommerce is active
        if (!class_exists('WooCommerce')) {
            $results['message'] = __('WooCommerce plugin is not active.', 'zpos');
            return $results;
        }
        
        $results['plugin_active'] = true;
        $results['version'] = WC()->version;
        
        // Get counts
        try {
            $results['products_count'] = wp_count_posts('product')->publish;
            
            $customer_query = new WP_User_Query(array(
                'role' => 'customer',
                'count_total' => true
            ));
            $results['customers_count'] = $customer_query->get_total();
            
            $results['orders_count'] = wp_count_posts('shop_order')->{'wc-completed'};
            
            $results['status'] = 'success';
            $results['message'] = __('WooCommerce connection successful.', 'zpos');
            
        } catch (Exception $e) {
            $results['message'] = sprintf(__('Error connecting to WooCommerce: %s', 'zpos'), $e->getMessage());
        }
        
        return $results;
    }

    /**
     * Get available currencies.
     *
     * @since    1.0.0
     * @return   array    Currencies array
     */
    public function get_currencies() {
        return array(
            'VND' => array('name' => __('Vietnamese Dong', 'zpos'), 'symbol' => '₫'),
            'USD' => array('name' => __('US Dollar', 'zpos'), 'symbol' => '$'),
            'EUR' => array('name' => __('Euro', 'zpos'), 'symbol' => '€'),
            'GBP' => array('name' => __('British Pound', 'zpos'), 'symbol' => '£'),
            'JPY' => array('name' => __('Japanese Yen', 'zpos'), 'symbol' => '¥'),
            'CNY' => array('name' => __('Chinese Yuan', 'zpos'), 'symbol' => '¥'),
            'KRW' => array('name' => __('South Korean Won', 'zpos'), 'symbol' => '₩'),
            'THB' => array('name' => __('Thai Baht', 'zpos'), 'symbol' => '฿'),
            'SGD' => array('name' => __('Singapore Dollar', 'zpos'), 'symbol' => 'S$'),
            'MYR' => array('name' => __('Malaysian Ringgit', 'zpos'), 'symbol' => 'RM')
        );
    }

    /**
     * Get available timezones.
     *
     * @since    1.0.0
     * @return   array    Timezones array
     */
    public function get_timezones() {
        return array(
            'Asia/Ho_Chi_Minh' => __('Ho Chi Minh City (GMT+7)', 'zpos'),
            'Asia/Bangkok' => __('Bangkok (GMT+7)', 'zpos'),
            'Asia/Jakarta' => __('Jakarta (GMT+7)', 'zpos'),
            'Asia/Singapore' => __('Singapore (GMT+8)', 'zpos'),
            'Asia/Kuala_Lumpur' => __('Kuala Lumpur (GMT+8)', 'zpos'),
            'Asia/Manila' => __('Manila (GMT+8)', 'zpos'),
            'Asia/Tokyo' => __('Tokyo (GMT+9)', 'zpos'),
            'Asia/Seoul' => __('Seoul (GMT+9)', 'zpos'),
            'Europe/London' => __('London (GMT+0)', 'zpos'),
            'Europe/Paris' => __('Paris (GMT+1)', 'zpos'),
            'America/New_York' => __('New York (GMT-5)', 'zpos'),
            'America/Los_Angeles' => __('Los Angeles (GMT-8)', 'zpos'),
            'UTC' => __('UTC (GMT+0)', 'zpos')
        );
    }

    /**
     * AJAX handler for saving settings.
     *
     * @since    1.0.0
     */
    public function ajax_save_settings() {
        check_ajax_referer('zpos_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'zpos'));
        }

        $settings = $_POST['settings'] ?? array();
        
        // Process each setting
        $processed_settings = array();
        foreach ($settings as $setting) {
            $key = sanitize_text_field($setting['name']);
            $value = $setting['value'];
            
            // Handle checkboxes
            if ($setting['type'] === 'checkbox') {
                $value = $value === 'true' || $value === true;
            }
            
            $processed_settings[$key] = $value;
        }

        $result = $this->save_settings($processed_settings);

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        } else {
            wp_send_json_success(__('Settings saved successfully.', 'zpos'));
        }
    }

    /**
     * AJAX handler for resetting settings.
     *
     * @since    1.0.0
     */
    public function ajax_reset_settings() {
        check_ajax_referer('zpos_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'zpos'));
        }

        $result = $this->reset_settings();

        if ($result) {
            wp_send_json_success(__('Settings reset to default values.', 'zpos'));
        } else {
            wp_send_json_error(__('Failed to reset settings.', 'zpos'));
        }
    }

    /**
     * AJAX handler for testing WooCommerce connection.
     *
     * @since    1.0.0
     */
    public function ajax_test_woocommerce() {
        check_ajax_referer('zpos_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'zpos'));
        }

        $results = $this->test_woocommerce_connection();
        wp_send_json_success($results);
    }

    /**
     * AJAX handler for re-running setup wizard.
     *
     * @since    1.0.0
     */
    public function ajax_rerun_wizard() {
        check_ajax_referer('zpos_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'zpos'));
        }

        // Reset wizard completion flag
        delete_option('zpos_setup_completed');
        
        // Redirect URL
        $redirect_url = admin_url('admin.php?page=zpos-setup');
        
        wp_send_json_success(array(
            'message' => __('Setup wizard will be restarted.', 'zpos'),
            'redirect' => $redirect_url
        ));
    }

    /**
     * AJAX handler for WooCommerce sync.
     *
     * @since    1.0.0
     */
    public function ajax_sync_woocommerce() {
        check_ajax_referer('zpos_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'zpos'));
        }

        $sync_type = sanitize_text_field($_POST['sync_type'] ?? 'all');
        $results = array(
            'products' => 0,
            'customers' => 0,
            'orders' => 0,
            'errors' => array()
        );

        try {
            // Sync products
            if ($sync_type === 'all' || $sync_type === 'products') {
                if (class_exists('ZPOS_Products')) {
                    $products_handler = new ZPOS_Products();
                    $sync_result = $products_handler->sync_with_woocommerce();
                    $results['products'] = $sync_result['count'] ?? 0;
                }
            }

            // Sync customers
            if ($sync_type === 'all' || $sync_type === 'customers') {
                if (class_exists('ZPOS_Customers')) {
                    $customers_handler = new ZPOS_Customers();
                    $sync_result = $customers_handler->sync_with_woocommerce();
                    $results['customers'] = $sync_result['count'] ?? 0;
                }
            }

            // Sync orders
            if ($sync_type === 'all' || $sync_type === 'orders') {
                if (class_exists('ZPOS_Orders')) {
                    $orders_handler = new ZPOS_Orders();
                    $sync_result = $orders_handler->sync_with_woocommerce();
                    $results['orders'] = $sync_result['count'] ?? 0;
                }
            }

            wp_send_json_success(array(
                'message' => __('Sync completed successfully.', 'zpos'),
                'results' => $results
            ));

        } catch (Exception $e) {
            wp_send_json_error(sprintf(__('Sync failed: %s', 'zpos'), $e->getMessage()));
        }
    }
}
