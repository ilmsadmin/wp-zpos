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
     * Initialize the admin menus.
     *
     * @since    1.0.0
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'create_admin_menus'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));

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
        );        // Products submenu
        add_submenu_page(
            'zpos',
            __('Products', 'zpos'),
            __('Products', 'zpos'),
            'manage_options',
            'zpos-products',
            array($this, 'products_page')
        );        // Categories submenu
        add_submenu_page(
            'zpos',
            __('Categories', 'zpos'),
            __('Categories', 'zpos'),
            'edit_posts',
            'zpos-categories',
            array($this, 'categories_page')
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
        );

        // Enqueue admin JS
        wp_enqueue_script(
            'zpos-admin',
            plugin_dir_url(__FILE__) . '../assets/js/admin.js',
            array('jquery', 'chartjs'),
            '1.0.0',
            true
        );        // Localize script with AJAX data
        wp_localize_script('zpos-admin', 'zpos_admin_vars', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('zpos_admin_nonce'),
            'text' => array(
                'loading' => __('Loading...', 'zpos'),
                'error' => __('An error occurred. Please try again.', 'zpos'),
                'success' => __('Success!', 'zpos'),
                'confirm_delete' => __('Are you sure you want to delete this item?', 'zpos'),
                'no_data' => __('No data available', 'zpos'),
                'all_categories' => __('All Categories', 'zpos'),
                'no_products' => __('No products found', 'zpos')
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
    }    /**
     * Products page callback.
     *
     * @since    1.0.0
     */
    public function products_page() {
        $this->render_admin_page('products');
    }    /**
     * Categories page callback.
     *
     * @since    1.0.0
     */
    public function categories_page() {
        // Check user permissions
        if (!current_user_can('edit_posts')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'zpos'));
        }
        
        try {
            $this->render_admin_page('categories');
        } catch (Exception $e) {
            echo '<div class="wrap">';
            echo '<h1>' . __('Categories - Error', 'zpos') . '</h1>';
            echo '<div class="notice notice-error"><p>Error: ' . esc_html($e->getMessage()) . '</p></div>';
            echo '</div>';
        }
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
    }    /**
     * Render admin page template.
     *
     * @since    1.0.0
     * @param    string    $page    Page template name
     */
    private function render_admin_page($page) {
        $template_path = plugin_dir_path(__FILE__) . '../templates/admin/' . $page . '.php';
        
        if (file_exists($template_path)) {
            try {
                // Pass data to template
                $data = $this->get_page_data($page);
                include $template_path;
            } catch (Exception $e) {
                echo '<div class="wrap">';
                echo '<h1>' . esc_html__('ZPOS - ' . ucfirst($page), 'zpos') . '</h1>';
                echo '<div class="notice notice-error"><p>Template Error: ' . esc_html($e->getMessage()) . '</p></div>';
                echo '</div>';
            }
        } else {
            // Fallback if template doesn't exist
            echo '<div class="wrap">';
            echo '<h1>' . esc_html__('ZPOS - ' . ucfirst($page), 'zpos') . '</h1>';
            echo '<div class="notice notice-error"><p>Template file not found: ' . esc_html($template_path) . '</p></div>';
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
                
            case 'categories':
                // Get categories data
                $data['categories'] = $this->get_categories_data();
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
    }    /**
     * Get products data for products page.
     *
     * @since    1.0.0
     * @return   array    Products data
     */    private function get_products_data() {
        return array(
            'page_title' => __('Products Management', 'zpos'),
            'nonce' => wp_create_nonce('zpos_admin_nonce')
        );
    }/**
     * Get categories data for categories page.
     *
     * @since    1.0.0
     * @return   array    Categories data
     */
    private function get_categories_data() {
        try {
            // Initialize category manager
            $category_file = plugin_dir_path(__FILE__) . 'product-categories.php';
            if (!file_exists($category_file)) {
                throw new Exception('Product categories file not found: ' . $category_file);
            }
            
            require_once $category_file;
            
            if (!class_exists('ZPOS_Product_Categories')) {
                throw new Exception('ZPOS_Product_Categories class not found');
            }
            
            $category_manager = new ZPOS_Product_Categories();
              return array(
                'page_title' => __('Categories Management', 'zpos'),
                'nonce' => wp_create_nonce('zpos_admin_nonce'),
                'categories' => $category_manager->get_categories(),
                'category_tree' => $category_manager->get_category_tree(),
                'category_options' => $category_manager->get_category_options()
            );        } catch (Exception $e) {
            return array(
                'page_title' => __('Categories Management', 'zpos'),
                'nonce' => wp_create_nonce('zpos_admin_nonce'),
                'categories' => array(),
                'category_tree' => array(),
                'category_options' => '',
                'error' => $e->getMessage()
            );
        }
    }

    /**
     * Get customers data for customers page.
     *
     * @since    1.0.0
     * @return   array    Customers data
     */    private function get_customers_data() {
        return array(
            'page_title' => __('Customers Management', 'zpos'),
            'nonce' => wp_create_nonce('zpos_admin_nonce')
        );
    }

    /**
     * Get orders data for orders page.
     *
     * @since    1.0.0
     * @return   array    Orders data
     */    private function get_orders_data() {
        return array(
            'page_title' => __('Orders Management', 'zpos'),
            'nonce' => wp_create_nonce('zpos_admin_nonce')
        );
    }
    
    /**
     * Get inventory data for inventory page.
     *
     * @since    1.0.0
     * @return   array    Inventory data
     */    private function get_inventory_data() {
        return array(
            'page_title' => __('Inventory Management', 'zpos'),
            'nonce' => wp_create_nonce('zpos_admin_nonce')
        );
    }
    
    /**
     * Get warranty data for warranty page.
     *
     * @since    1.0.0
     * @return   array    Warranty data
     */    private function get_warranty_data() {
        return array(
            'page_title' => __('Warranty Management', 'zpos'),
            'nonce' => wp_create_nonce('zpos_admin_nonce')
        );
    }

    /**
     * Get reports data for reports page.
     *
     * @since    1.0.0
     * @return   array    Reports data
     */    private function get_reports_data() {
        return array(
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
          $settings = new ZPOS_Settings();
        return array(
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
        
        // Categories AJAX handlers
        add_action('wp_ajax_zpos_get_categories', array($this, 'ajax_get_categories'));
        add_action('wp_ajax_zpos_save_category', array($this, 'ajax_save_category'));
        add_action('wp_ajax_zpos_delete_category', array($this, 'ajax_delete_category'));
        add_action('wp_ajax_zpos_get_category', array($this, 'ajax_get_category'));
        add_action('wp_ajax_zpos_bulk_category_action', array($this, 'ajax_bulk_category_action'));
        add_action('wp_ajax_zpos_get_category_product_count', array($this, 'ajax_get_category_product_count'));
        
        // Reports AJAX handlers
        add_action('wp_ajax_zpos_generate_report', array($this, 'ajax_generate_report'));
        add_action('wp_ajax_zpos_export_report', array($this, 'ajax_export_report'));
          // Settings AJAX handlers
        add_action('wp_ajax_zpos_save_settings', array($this, 'ajax_save_settings'));
        add_action('wp_ajax_zpos_reset_settings', array($this, 'ajax_reset_settings'));
          // POS AJAX handlers - Initialize POS class and delegate to it
        $this->init_pos_handlers();
        
        // Orders AJAX handlers - Initialize Orders class and delegate to it
        $this->init_orders_handlers();
        
        // Inventory AJAX handlers - Initialize Inventory class and delegate to it
        $this->init_inventory_handlers();
        
        // Warranty AJAX handlers - Initialize Warranty class and delegate to it
        $this->init_warranty_handlers();
    }
    
    /**
     * Initialize POS AJAX handlers
     */
    private function init_pos_handlers() {
        // Include POS class if not already loaded
        if (!class_exists('ZPOS_POS')) {
            require_once plugin_dir_path(__FILE__) . 'pos.php';
        }
        
        // The POS class handles its own AJAX registration in its constructor
        new ZPOS_POS();
    }
    
    /**
     * Initialize Orders AJAX handlers
     */
    private function init_orders_handlers() {
        // Include Orders class if not already loaded
        if (!class_exists('ZPOS_Orders')) {
            require_once plugin_dir_path(__FILE__) . 'orders.php';
        }
        
        // The Orders class handles its own AJAX registration in its constructor
        new ZPOS_Orders();
    }
    
    /**
     * Initialize Inventory AJAX handlers
     */
    private function init_inventory_handlers() {
        // Include Inventory class if not already loaded
        if (!class_exists('ZPOS_Inventory')) {
            require_once plugin_dir_path(__FILE__) . 'inventory.php';
        }
        
        // The Inventory class handles its own AJAX registration in its constructor
        new ZPOS_Inventory();
    }
    
    /**
     * Initialize Warranty AJAX handlers
     */
    private function init_warranty_handlers() {
        // Include Warranty class if not already loaded
        if (!class_exists('ZPOS_Warranty')) {
            require_once plugin_dir_path(__FILE__) . 'warranty.php';
        }
        
        // The Warranty class handles its own AJAX registration in its constructor
        new ZPOS_Warranty();
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
     * AJAX handler for getting categories
     */
    public function ajax_get_categories() {
        check_ajax_referer('zpos_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'zpos'));
        }

        require_once plugin_dir_path(__FILE__) . 'product-categories.php';
        $category_manager = new ZPOS_Product_Categories();
        
        $categories = $category_manager->get_categories();
        wp_send_json_success($categories);
    }    /**
     * AJAX handler for saving category
     */
    public function ajax_save_category() {
        check_ajax_referer('zpos_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'zpos'));
        }

        require_once plugin_dir_path(__FILE__) . 'product-categories.php';
        $category_manager = new ZPOS_Product_Categories();
        
        $category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : null;
        $category_data = array(
            'name' => sanitize_text_field($_POST['name'] ?? ''),
            'slug' => sanitize_text_field($_POST['slug'] ?? ''),
            'description' => wp_kses_post($_POST['description'] ?? ''),
            'parent_id' => !empty($_POST['parent_id']) ? intval($_POST['parent_id']) : null,
            'image_url' => esc_url_raw($_POST['image_url'] ?? ''),
            'status' => sanitize_text_field($_POST['status'] ?? 'active'),
            'sort_order' => intval($_POST['sort_order'] ?? 0)
        );

        $result = $category_manager->save_category($category_data, $category_id);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        } else {
            wp_send_json_success(array(
                'id' => $result,
                'message' => $category_id ? __('Category updated successfully', 'zpos') : __('Category created successfully', 'zpos')
            ));
        }
    }    /**
     * AJAX handler for deleting category
     */
    public function ajax_delete_category() {
        check_ajax_referer('zpos_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'zpos'));
        }

        $category_id = intval($_POST['category_id'] ?? 0);
        
        if (!$category_id) {
            wp_send_json_error(__('Invalid category ID', 'zpos'));
        }

        require_once plugin_dir_path(__FILE__) . 'product-categories.php';
        $category_manager = new ZPOS_Product_Categories();
        
        $result = $category_manager->delete_category($category_id);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        } else {
            wp_send_json_success(__('Category deleted successfully', 'zpos'));
        }
    }    /**
     * AJAX handler for getting single category
     */
    public function ajax_get_category() {
        check_ajax_referer('zpos_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'zpos'));
        }

        $category_id = intval($_POST['category_id'] ?? 0);
        
        if (!$category_id) {
            wp_send_json_error(__('Invalid category ID', 'zpos'));
        }

        require_once plugin_dir_path(__FILE__) . 'product-categories.php';
        $category_manager = new ZPOS_Product_Categories();
        
        $category = $category_manager->get_category($category_id);
        
        if ($category) {
            wp_send_json_success($category);
        } else {
            wp_send_json_error(__('Category not found', 'zpos'));
        }
    }    /**
     * AJAX handler for bulk category actions
     */
    public function ajax_bulk_category_action() {
        check_ajax_referer('zpos_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'zpos'));
        }

        $bulk_action = sanitize_text_field($_POST['bulk_action'] ?? '');
        $category_ids = array_map('intval', $_POST['category_ids'] ?? array());
        
        if (empty($bulk_action) || empty($category_ids)) {
            wp_send_json_error(__('Invalid bulk action or no categories selected', 'zpos'));
        }

        require_once plugin_dir_path(__FILE__) . 'product-categories.php';
        $category_manager = new ZPOS_Product_Categories();
        
        $success_count = 0;
        $errors = array();
        
        foreach ($category_ids as $category_id) {
            switch ($bulk_action) {
                case 'delete':
                    $result = $category_manager->delete_category($category_id);
                    if (is_wp_error($result)) {
                        $errors[] = $result->get_error_message();
                    } else {
                        $success_count++;
                    }
                    break;
                    
                case 'activate':
                    $result = $category_manager->save_category(array('status' => 'active'), $category_id);
                    if (is_wp_error($result)) {
                        $errors[] = $result->get_error_message();
                    } else {
                        $success_count++;
                    }
                    break;
                    
                case 'deactivate':
                    $result = $category_manager->save_category(array('status' => 'inactive'), $category_id);
                    if (is_wp_error($result)) {
                        $errors[] = $result->get_error_message();
                    } else {
                        $success_count++;
                    }
                    break;
            }
        }
        
        if (!empty($errors)) {
            wp_send_json_error(sprintf(__('Some operations failed: %s', 'zpos'), implode(', ', $errors)));
        } else {
            wp_send_json_success(sprintf(__('%d categories processed successfully', 'zpos'), $success_count));
        }
    }    /**
     * AJAX handler for getting category product count
     */
    public function ajax_get_category_product_count() {
        check_ajax_referer('zpos_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'zpos'));
        }

        $category_id = intval($_POST['category_id'] ?? 0);
        
        if (!$category_id) {
            wp_send_json_error(__('Invalid category ID', 'zpos'));
        }

        global $wpdb;
        $products_table = $wpdb->prefix . 'zpos_products';
        
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $products_table WHERE category_id = %d AND status = 'active'",
            $category_id
        ));
        
        wp_send_json_success(intval($count));
    }

    /**
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
}
