<?php
/**
 * Admin Menu System for ZPOS
 *
 * @link       https://yourwebsite.com
 * @since      1.0.0
 *
 * @package    ZPOS
 * @subpackage ZPOS/includes
 */

/**
 * Admin Menu System class.
 *
 * Handles all admin menu creation and management for ZPOS.
 *
 * @package    ZPOS
 * @subpackage ZPOS/includes
 * @author     Your Name <your.email@example.com>
 */
class ZPOS_Admin_Menus {

    /**
     * Initialize the a        return a        return array(
            'page_title' => __('Order Management', 'zpos'),
            'nonce' => wp_create_nonce('zpos_admin_nonce')
        );(
            'page_title' => __('Customer Management', 'zpos'),
            'nonce' => wp_create_nonce('zpos_admin_nonce')
        ); menus.
     *
     * @since    1.0.0
     */    public function __construct() {
        add_action('admin_menu', array($this, 'create_admin_menus'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'), 25);

        // Initialize AJAX handlers
        $this->init_ajax_handlers();
        
        // Show admin notice for successful integration
        add_action('admin_notices', array($this, 'show_integration_notice'));
    }

    /**
     * Create admin menus for ZPOS.
     *
     * @since    1.0.0
     */
    public function create_admin_menus() {
        // Check if user has required capability
        if (!current_user_can('manage_options')) {
            return;
        }

        // Main ZPOS menu
        add_menu_page(
            __('ZPOS', 'zpos'),                    // Page title
            __('ZPOS', 'zpos'),                    // Menu title
            'manage_options',                       // Capability
            'zpos',                                // Menu slug
            array($this, 'dashboard_page'),        // Callback function
            $this->get_menu_icon(),                // Icon
            26                                     // Position (after Plugins)
        );

        // Dashboard submenu (rename the first menu item)
        add_submenu_page(
            'zpos',                                // Parent slug
            __('Dashboard', 'zpos'),               // Page title
            __('Dashboard', 'zpos'),               // Menu title
            'manage_options',                      // Capability
            'zpos',                                // Menu slug (same as parent for first item)
            array($this, 'dashboard_page')         // Callback function
        );

        // POS submenu
        add_submenu_page(
            'zpos',
            __('Point of Sale', 'zpos'),
            __('POS', 'zpos'),
            'manage_options',
            'zpos-pos',
            array($this, 'pos_page')
        );

        // Products submenu
        add_submenu_page(
            'zpos',
            __('Products', 'zpos'),
            __('Products', 'zpos'),
            'manage_options',
            'zpos-products',
            array($this, 'products_page')
        );

        // Customers submenu
        add_submenu_page(
            'zpos',
            __('Customers', 'zpos'),
            __('Customers', 'zpos'),
            'manage_options',
            'zpos-customers',
            array($this, 'customers_page')
        );

        // Orders submenu
        add_submenu_page(
            'zpos',
            __('Orders', 'zpos'),
            __('Orders', 'zpos'),
            'manage_options',
            'zpos-orders',
            array($this, 'orders_page')
        );

        // Inventory submenu
        add_submenu_page(
            'zpos',
            __('Inventory', 'zpos'),
            __('Inventory', 'zpos'),
            'manage_options',
            'zpos-inventory',
            array($this, 'inventory_page')
        );

        // Warranty submenu
        add_submenu_page(
            'zpos',
            __('Warranty', 'zpos'),
            __('Warranty', 'zpos'),
            'manage_options',
            'zpos-warranty',
            array($this, 'warranty_page')
        );

        // Reports submenu
        add_submenu_page(
            'zpos',
            __('Reports', 'zpos'),
            __('Reports', 'zpos'),
            'manage_options',
            'zpos-reports',
            array($this, 'reports_page')
        );

        // Settings submenu
        add_submenu_page(
            'zpos',
            __('Settings', 'zpos'),
            __('Settings', 'zpos'),
            'manage_options',
            'zpos-settings',
            array($this, 'settings_page')
        );
    }

    /**
     * Get menu icon for ZPOS.
     *
     * @since    1.0.0
     * @return   string  SVG icon or dashicon
     */
    private function get_menu_icon() {
        // Custom SVG icon for store/POS
        return 'data:image/svg+xml;base64,' . base64_encode('<svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M3 3H5L5.4 5M7 13H17L21 5H5.4M7 13L5.4 5M7 13L4.7 15.3C4.3 15.7 4.6 16.5 5.1 16.5H17M17 13V16.5M9 19.5C9.8 19.5 10.5 20.2 10.5 21S9.8 22.5 9 22.5 7.5 21.8 7.5 21 8.2 19.5 9 19.5ZM20 19.5C20.8 19.5 21.5 20.2 21.5 21S20.8 22.5 20 22.5 18.5 21.8 18.5 21 19.2 19.5 20 19.5Z" stroke="black" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>');
    }

    /**
     * Enqueue admin scripts and styles.
     *
     * @since    1.0.0
     * @param    string    $hook    Current admin page hook
     */
    public function enqueue_admin_scripts($hook) {
        // Only load on ZPOS admin pages
        if (strpos($hook, 'zpos') === false && $hook !== 'toplevel_page_zpos') {
            return;
        }

        // Enqueue Chart.js for dashboard
        wp_enqueue_script(
            'chartjs',
            'https://cdn.jsdelivr.net/npm/chart.js',
            array(),
            '3.9.1',
            true
        );

        // Enqueue admin CSS
        wp_enqueue_style(
            'zpos-admin',
            plugin_dir_url(__FILE__) . '../assets/css/admin.css',
            array(),
            '1.0.0'
        );        // Enqueue admin JS
        wp_enqueue_script(
            'zpos-admin',
            plugin_dir_url(__FILE__) . '../assets/js/admin.js',
            array('jquery', 'chartjs'),
            '1.0.0',
            true        );

        // Localize script with AJAX data
        wp_localize_script('zpos', 'zpos_admin_vars', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('zpos_admin_nonce'),
            'text' => array(
                'loading' => __('Loading...', 'zpos'),
                'error' => __('An error occurred. Please try again.', 'zpos'),
                'success' => __('Success!', 'zpos'),
                'confirm_delete' => __('Are you sure you want to delete this item?', 'zpos'),
                'no_data' => __('No data available', 'zpos')
            )
        ));
    }

    /**
     * Dashboard page callback.
     *
     * @since    1.0.0
     */
    public function dashboard_page() {
        $this->render_admin_page('dashboard');
    }

    /**
     * POS page callback.
     *
     * @since    1.0.0
     */
    public function pos_page() {
        $this->render_admin_page('pos');
    }

    /**
     * Products page callback.
     *
     * @since    1.0.0
     */
    public function products_page() {
        $this->render_admin_page('products');
    }

    /**
     * Customers page callback.
     *
     * @since    1.0.0
     */
    public function customers_page() {
        $this->render_admin_page('customers');
    }

    /**
     * Orders page callback.
     *
     * @since    1.0.0
     */
    public function orders_page() {
        $this->render_admin_page('orders');
    }

    /**
     * Inventory page callback.
     *
     * @since    1.0.0
     */
    public function inventory_page() {
        $this->render_admin_page('inventory');
    }

    /**
     * Warranty page callback.
     *
     * @since    1.0.0
     */
    public function warranty_page() {
        $this->render_admin_page('warranty');
    }

    /**
     * Reports page callback.
     *
     * @since    1.0.0
     */
    public function reports_page() {
        $this->render_admin_page('reports');
    }

    /**
     * Settings page callback.
     *
     * @since    1.0.0
     */
    public function settings_page() {
        $this->render_admin_page('settings');
    }

    /**
     * Render admin page template.
     *
     * @since    1.0.0
     * @param    string    $page    Page template name
     */
    private function render_admin_page($page) {
        $template_path = plugin_dir_path(__FILE__) . '../templates/admin/' . $page . '.php';
        
        if (file_exists($template_path)) {
            // Pass data to template
            $data = $this->get_page_data($page);
            include $template_path;
        } else {
            // Fallback if template doesn't exist
            echo '<div class="wrap">';
            echo '<h1>' . esc_html__('ZPOS - ' . ucfirst($page), 'zpos') . '</h1>';
            echo '<p>' . esc_html__('This page is under development.', 'zpos') . '</p>';
            echo '</div>';
        }
    }

    /**
     * Get data for specific admin page.
     *
     * @since    1.0.0
     * @param    string    $page    Page name
     * @return   array              Page data
     */
    private function get_page_data($page) {
        $data = array();
        
        switch ($page) {
            case 'dashboard':
                // Get dashboard statistics
                $data['stats'] = $this->get_dashboard_stats();
                $data['charts'] = $this->get_dashboard_charts();
                break;
                
            case 'products':
                // Get products data
                $data['products'] = $this->get_products_data();
                break;
                
            case 'customers':
                // Get customers data
                $data['customers'] = $this->get_customers_data();
                break;
                
            case 'orders':
                // Get orders data
                $data['orders'] = $this->get_orders_data();
                break;
                
            case 'inventory':
                // Get inventory data
                $data['inventory'] = $this->get_inventory_data();
                break;
                
            case 'warranty':
                // Get warranty data
                $data['warranty'] = $this->get_warranty_data();
                break;
                
            case 'reports':
                // Get reports data
                $data['reports'] = $this->get_reports_data();
                break;
                
            case 'settings':
                // Get settings data
                $data['settings'] = $this->get_settings_data();
                break;
                
            default:
                $data = array();
                break;
        }
        
        return $data;
    }

    /**
     * Get dashboard statistics.
     *
     * @since    1.0.0
     * @return   array    Dashboard stats
     */
    private function get_dashboard_stats() {
        global $wpdb;
        
        $stats = array();
        
        // Total products
        $stats['total_products'] = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}zpos_products WHERE status = 'active'"
        ) ?: 0;
        
        // Total customers
        $stats['total_customers'] = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}zpos_customers"
        ) ?: 0;
        
        // Total orders
        $stats['total_orders'] = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}zpos_orders"
        ) ?: 0;
        
        // Total revenue
        $stats['total_revenue'] = $wpdb->get_var(
            "SELECT SUM(total_amount) FROM {$wpdb->prefix}zpos_orders WHERE payment_status = 'completed'"
        ) ?: 0;
        
        return $stats;
    }

    /**
     * Get dashboard chart data.
     *
     * @since    1.0.0
     * @return   array    Chart data
     */
    private function get_dashboard_charts() {
        return array(
            'revenue' => array('labels' => array(), 'data' => array()),
            'top_products' => array('labels' => array(), 'data' => array())
        );
    }

    /**
     * Get products data for products page.
     *
     * @since    1.0.0
     * @return   array    Products data
     */
    private function get_products_data() {        return array(
            'page_title' => __('Products Management', 'zpos'),
            'nonce' => wp_create_nonce('zpos_admin_nonce')
        );
    }

    /**
     * Get customers data for customers page.
     *
     * @since    1.0.0
     * @return   array    Customers data
     */
    private function get_customers_data() {
        return array(
            'page_title' => __('Customers Management', 'zpos'),
            'nonce' => wp_create_nonce('zpos_nonce')
        );
    }

    /**
     * Get orders data for orders page.
     *
     * @since    1.0.0
     * @return   array    Orders data
     */
    private function get_orders_data() {
        return array(
            'page_title' => __('Orders Management', 'zpos'),
            'nonce' => wp_create_nonce('zpos_nonce')
        );
    }
    
    /**
     * Get inventory data for inventory page.
     *
     * @since    1.0.0
     * @return   array    Inventory data
     */
    private function get_inventory_data() {        return array(
            'page_title' => __('Inventory Management', 'zpos'),
            'nonce' => wp_create_nonce('zpos_admin_nonce')
        );
    }
    
    /**
     * Get warranty data for warranty page.
     *
     * @since    1.0.0
     * @return   array    Warranty data
     */
    private function get_warranty_data() {        return array(
            'page_title' => __('Warranty Management', 'zpos'),
            'nonce' => wp_create_nonce('zpos_admin_nonce')
        );
    }

    /**
     * Get reports data for reports page.
     *
     * @since    1.0.0
     * @return   array    Reports data
     */
    private function get_reports_data() {        return array(
            'page_title' => __('Reports', 'zpos'),
            'nonce' => wp_create_nonce('zpos_admin_nonce'),
            'currency_symbol' => get_option('zpos_currency_symbol', '$'),
            'date_format' => get_option('date_format', 'Y-m-d')
        );
    }

    /**
     * Get settings data for settings page.
     *
     * @since    1.0.0
     * @return   array    Settings data
     */
    private function get_settings_data() {
        // Initialize settings class if not already done
        if (!class_exists('ZPOS_Settings')) {
            require_once ZPOS_PLUGIN_DIR . 'includes/settings.php';
        }
        
        $settings = new ZPOS_Settings();        return array(
            'page_title' => __('ZPOS Settings', 'zpos'),
            'settings' => $settings->get_settings(),
            'nonce' => wp_create_nonce('zpos_admin_nonce')
        );
    }
    
    /**
     * Initialize AJAX handlers
     */    private function init_ajax_handlers() {
        // Dashboard AJAX handlers
        add_action('wp_ajax_zpos_get_recent_activity', array($this, 'ajax_get_recent_activity'));
        add_action('wp_ajax_zpos_get_dashboard_stats', array($this, 'ajax_get_dashboard_stats'));
        add_action('wp_ajax_zpos_get_chart_data', array($this, 'ajax_get_chart_data'));
        
        // Orders AJAX handlers
        add_action('wp_ajax_zpos_get_orders', array($this, 'ajax_get_orders'));
        add_action('wp_ajax_zpos_update_order_status', array($this, 'ajax_update_order_status'));
        add_action('wp_ajax_zpos_bulk_update_order_status', array($this, 'ajax_bulk_update_order_status'));
        add_action('wp_ajax_zpos_sync_woocommerce_orders', array($this, 'ajax_sync_woocommerce_orders'));
        add_action('wp_ajax_zpos_export_orders', array($this, 'ajax_export_orders'));
        add_action('wp_ajax_zpos_get_order_details', array($this, 'ajax_get_order_details'));
        
        // Warranty AJAX handlers
        add_action('wp_ajax_zpos_get_warranties', array($this, 'ajax_get_warranties'));
        add_action('wp_ajax_zpos_add_warranty_package', array($this, 'ajax_add_warranty_package'));
        add_action('wp_ajax_zpos_edit_warranty_package', array($this, 'ajax_edit_warranty_package'));
        add_action('wp_ajax_zpos_delete_warranty_package', array($this, 'ajax_delete_warranty_package'));
        add_action('wp_ajax_zpos_register_warranty', array($this, 'ajax_register_warranty'));
        add_action('wp_ajax_zpos_search_warranty', array($this, 'ajax_search_warranty'));
        add_action('wp_ajax_zpos_update_warranty_status', array($this, 'ajax_update_warranty_status'));
        
        // Reports AJAX handlers
        add_action('wp_ajax_zpos_generate_report', array($this, 'ajax_generate_report'));
        add_action('wp_ajax_zpos_export_report', array($this, 'ajax_export_report'));
        add_action('wp_ajax_zpos_get_revenue_data', array($this, 'ajax_get_revenue_data'));
        add_action('wp_ajax_zpos_get_product_performance', array($this, 'ajax_get_product_performance'));
        
        // Settings AJAX handlers
        add_action('wp_ajax_zpos_save_settings', array($this, 'ajax_save_settings'));
        add_action('wp_ajax_zpos_reset_settings', array($this, 'ajax_reset_settings'));
        add_action('wp_ajax_zpos_test_woocommerce_connection', array($this, 'ajax_test_woocommerce_connection'));
    }

    /**
     * AJAX handler for recent activity
     */
    public function ajax_get_recent_activity() {
        check_ajax_referer('zpos_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'zpos'));
        }

        $activities = array(
            array(
                'type' => 'order',
                'description' => __('New order created', 'zpos'),
                'time_ago' => '5 minutes ago'
            )
        );

        wp_send_json_success($activities);
    }

    /**
     * AJAX handler for dashboard statistics
     */
    public function ajax_get_dashboard_stats() {
        check_ajax_referer('zpos_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'zpos'));
        }

        $stats = $this->get_dashboard_stats();
        wp_send_json_success($stats);
    }

    /**
     * AJAX handler for chart data
     */
    public function ajax_get_chart_data() {
        check_ajax_referer('zpos_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'zpos'));
        }

        $chart_data = $this->get_dashboard_charts();
        wp_send_json_success($chart_data);
    }

    /**
     * AJAX handler for generating reports
     */
    public function ajax_generate_report() {
        check_ajax_referer('zpos_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'zpos'));
        }

        $report_type = sanitize_text_field($_POST['report_type'] ?? '');
        $start_date = sanitize_text_field($_POST['start_date'] ?? '');
        $end_date = sanitize_text_field($_POST['end_date'] ?? '');

        if (!class_exists('ZPOS_Reports')) {
            require_once ZPOS_PLUGIN_DIR . 'includes/reports.php';
        }

        $reports = new ZPOS_Reports();
        $report_data = $reports->generate_report($report_type, $start_date, $end_date);

        if ($report_data) {
            wp_send_json_success($report_data);
        } else {
            wp_send_json_error(__('Failed to generate report.', 'zpos'));
        }
    }

    /**
     * AJAX handler for exporting reports
     */
    public function ajax_export_report() {
        check_ajax_referer('zpos_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'zpos'));
        }

        $report_type = sanitize_text_field($_POST['report_type'] ?? '');
        $format = sanitize_text_field($_POST['format'] ?? 'csv');
        $start_date = sanitize_text_field($_POST['start_date'] ?? '');
        $end_date = sanitize_text_field($_POST['end_date'] ?? '');

        if (!class_exists('ZPOS_Reports')) {
            require_once ZPOS_PLUGIN_DIR . 'includes/reports.php';
        }

        $reports = new ZPOS_Reports();
        $export_url = $reports->export_report($report_type, $format, $start_date, $end_date);

        if ($export_url) {
            wp_send_json_success(array('download_url' => $export_url));
        } else {
            wp_send_json_error(__('Failed to export report.', 'zpos'));
        }
    }

    /**
     * AJAX handler for saving settings
     */
    public function ajax_save_settings() {
        check_ajax_referer('zpos_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'zpos'));
        }

        $settings_data = $_POST['settings'] ?? array();
        
        if (!class_exists('ZPOS_Settings')) {
            require_once ZPOS_PLUGIN_DIR . 'includes/settings.php';
        }

        $settings = new ZPOS_Settings();
        $result = $settings->save_settings($settings_data);

        if ($result) {
            wp_send_json_success(__('Settings saved successfully.', 'zpos'));
        } else {
            wp_send_json_error(__('Failed to save settings.', 'zpos'));
        }
    }

    /**
     * AJAX handler for resetting settings
     */
    public function ajax_reset_settings() {
        check_ajax_referer('zpos_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'zpos'));
        }

        if (!class_exists('ZPOS_Settings')) {
            require_once ZPOS_PLUGIN_DIR . 'includes/settings.php';
        }

        $settings = new ZPOS_Settings();
        $result = $settings->reset_settings();

        if ($result) {
            wp_send_json_success(__('Settings reset successfully.', 'zpos'));
        } else {
            wp_send_json_error(__('Failed to reset settings.', 'zpos'));
        }
    }    /**
     * Show integration success notice
     *
     * @since    1.0.0
     */
    public function show_integration_notice() {
        if (isset($_GET['page']) && strpos($_GET['page'], 'zpos') === 0) {
            // Only show on ZPOS pages and only once
            if (!get_transient('zpos_integration_notice_shown')) {
                echo '<div class="notice notice-success is-dismissible">';
                echo '<p><strong>' . __('ZPOS Integration Complete!', 'zpos') . '</strong> ';
                echo __('Reports and Settings systems are now fully integrated with the admin interface.', 'zpos');
                echo '</p></div>';
                
                // Set transient so notice only shows once
                set_transient('zpos_integration_notice_shown', true, DAY_IN_SECONDS);
            }
        }
    }

    // ========================================================================
    // ORDERS AJAX HANDLERS
    // ========================================================================

    /**
     * AJAX handler for getting orders
     */
    public function ajax_get_orders() {
        check_ajax_referer('zpos_admin_nonce', 'nonce');

        if (!class_exists('ZPOS_Orders')) {
            require_once ZPOS_PLUGIN_DIR . 'includes/orders.php';
        }

        $orders = new ZPOS_Orders();
        $result = $orders->ajax_get_orders();
    }

    /**
     * AJAX handler for updating order status
     */
    public function ajax_update_order_status() {
        check_ajax_referer('zpos_admin_nonce', 'nonce');

        if (!class_exists('ZPOS_Orders')) {
            require_once ZPOS_PLUGIN_DIR . 'includes/orders.php';
        }

        $orders = new ZPOS_Orders();
        $result = $orders->ajax_update_order_status();
    }

    /**
     * AJAX handler for bulk updating order status
     */
    public function ajax_bulk_update_order_status() {
        check_ajax_referer('zpos_admin_nonce', 'nonce');

        if (!class_exists('ZPOS_Orders')) {
            require_once ZPOS_PLUGIN_DIR . 'includes/orders.php';
        }

        $orders = new ZPOS_Orders();
        $result = $orders->ajax_bulk_update_order_status();
    }

    /**
     * AJAX handler for syncing WooCommerce orders
     */
    public function ajax_sync_woocommerce_orders() {
        check_ajax_referer('zpos_admin_nonce', 'nonce');

        if (!class_exists('ZPOS_Orders')) {
            require_once ZPOS_PLUGIN_DIR . 'includes/orders.php';
        }

        $orders = new ZPOS_Orders();
        $result = $orders->ajax_sync_woocommerce_orders();
    }

    /**
     * AJAX handler for exporting orders
     */
    public function ajax_export_orders() {
        check_ajax_referer('zpos_admin_nonce', 'nonce');

        if (!class_exists('ZPOS_Orders')) {
            require_once ZPOS_PLUGIN_DIR . 'includes/orders.php';
        }

        $orders = new ZPOS_Orders();
        $result = $orders->ajax_export_orders();
    }

    /**
     * AJAX handler for getting order details
     */
    public function ajax_get_order_details() {
        check_ajax_referer('zpos_admin_nonce', 'nonce');

        if (!class_exists('ZPOS_Orders')) {
            require_once ZPOS_PLUGIN_DIR . 'includes/orders.php';
        }

        $orders = new ZPOS_Orders();
        $result = $orders->ajax_get_order_details();
    }

    // ========================================================================
    // WARRANTY AJAX HANDLERS
    // ========================================================================

    /**
     * AJAX handler for getting warranties
     */
    public function ajax_get_warranties() {
        check_ajax_referer('zpos_admin_nonce', 'nonce');

        if (!class_exists('ZPOS_Warranty')) {
            require_once ZPOS_PLUGIN_DIR . 'includes/warranty.php';
        }

        $warranty = new ZPOS_Warranty();
        $result = $warranty->ajax_get_warranties();
    }

    /**
     * AJAX handler for adding warranty package
     */
    public function ajax_add_warranty_package() {
        check_ajax_referer('zpos_admin_nonce', 'nonce');

        if (!class_exists('ZPOS_Warranty')) {
            require_once ZPOS_PLUGIN_DIR . 'includes/warranty.php';
        }

        $warranty = new ZPOS_Warranty();
        $result = $warranty->ajax_add_warranty_package();
    }

    /**
     * AJAX handler for editing warranty package
     */
    public function ajax_edit_warranty_package() {
        check_ajax_referer('zpos_admin_nonce', 'nonce');

        if (!class_exists('ZPOS_Warranty')) {
            require_once ZPOS_PLUGIN_DIR . 'includes/warranty.php';
        }

        $warranty = new ZPOS_Warranty();
        $result = $warranty->ajax_edit_warranty_package();
    }

    /**
     * AJAX handler for deleting warranty package
     */
    public function ajax_delete_warranty_package() {
        check_ajax_referer('zpos_admin_nonce', 'nonce');

        if (!class_exists('ZPOS_Warranty')) {
            require_once ZPOS_PLUGIN_DIR . 'includes/warranty.php';
        }

        $warranty = new ZPOS_Warranty();
        $result = $warranty->ajax_delete_warranty_package();
    }

    /**
     * AJAX handler for registering warranty
     */
    public function ajax_register_warranty() {
        check_ajax_referer('zpos_admin_nonce', 'nonce');

        if (!class_exists('ZPOS_Warranty')) {
            require_once ZPOS_PLUGIN_DIR . 'includes/warranty.php';
        }

        $warranty = new ZPOS_Warranty();
        $result = $warranty->ajax_register_warranty();
    }

    /**
     * AJAX handler for searching warranty
     */
    public function ajax_search_warranty() {
        check_ajax_referer('zpos_admin_nonce', 'nonce');

        if (!class_exists('ZPOS_Warranty')) {
            require_once ZPOS_PLUGIN_DIR . 'includes/warranty.php';
        }

        $warranty = new ZPOS_Warranty();
        $result = $warranty->ajax_search_warranty();
    }

    /**
     * AJAX handler for updating warranty status
     */
    public function ajax_update_warranty_status() {
        check_ajax_referer('zpos_admin_nonce', 'nonce');

        if (!class_exists('ZPOS_Warranty')) {
            require_once ZPOS_PLUGIN_DIR . 'includes/warranty.php';
        }

        $warranty = new ZPOS_Warranty();
        $result = $warranty->ajax_update_warranty_status();
    }

    // ========================================================================
    // ADDITIONAL REPORTS AJAX HANDLERS
    // ========================================================================

    /**
     * AJAX handler for getting revenue data
     */
    public function ajax_get_revenue_data() {
        check_ajax_referer('zpos_admin_nonce', 'nonce');

        if (!class_exists('ZPOS_Reports')) {
            require_once ZPOS_PLUGIN_DIR . 'includes/reports.php';
        }

        $reports = new ZPOS_Reports();
        $result = $reports->ajax_get_revenue_data();
    }

    /**
     * AJAX handler for getting product performance data
     */
    public function ajax_get_product_performance() {
        check_ajax_referer('zpos_admin_nonce', 'nonce');

        if (!class_exists('ZPOS_Reports')) {
            require_once ZPOS_PLUGIN_DIR . 'includes/reports.php';
        }

        $reports = new ZPOS_Reports();
        $result = $reports->ajax_get_product_performance();
    }

    // ========================================================================
    // ADDITIONAL SETTINGS AJAX HANDLERS
    // ========================================================================

    /**
     * AJAX handler for testing WooCommerce connection
     */
    public function ajax_test_woocommerce_connection() {
        check_ajax_referer('zpos_admin_nonce', 'nonce');

        if (!class_exists('ZPOS_Settings')) {
            require_once ZPOS_PLUGIN_DIR . 'includes/settings.php';
        }

        $settings = new ZPOS_Settings();
        $result = $settings->ajax_test_woocommerce_connection();
    }
}
