<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://yourwebsite.com
 * @since      1.0.0
 *
 * @package    ZPOS
 * @subpackage ZPOS/includes
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    ZPOS
 * @subpackage ZPOS/includes
 * @author     Your Name <your.email@example.com>
 */
class ZPOS_Admin {

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param      string    $plugin_name       The name of this plugin.
     * @param      string    $version    The version of this plugin.
     */
    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Register the stylesheets for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {
        // Only load on ZPOS admin pages
        if ($this->is_zpos_admin_page()) {
            wp_enqueue_style(
                $this->plugin_name,
                ZPOS_PLUGIN_URL . 'assets/css/admin.css',
                array(),
                $this->version,
                'all'
            );

            // Enqueue Chart.js for reports and dashboard
            wp_enqueue_style(
                'chartjs',
                'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.min.css',
                array(),
                '4.4.0'
            );
        }
    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {
        // Only load on ZPOS admin pages
        if ($this->is_zpos_admin_page()) {
            wp_enqueue_script(
                $this->plugin_name,
                ZPOS_PLUGIN_URL . 'assets/js/admin.js',
                array('jquery'),
                $this->version,
                false
            );

            // Enqueue Chart.js for reports and dashboard
            wp_enqueue_script(
                'chartjs',
                'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.min.js',
                array(),
                '4.4.0',
                false
            );            // Localize script for AJAX
            wp_localize_script($this->plugin_name, 'zpos_admin_vars', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('zpos_admin_nonce'),
                'text_domain' => 'zpos'
            ));
        }
    }

    /**
     * Add admin menu pages.
     *
     * @since    1.0.0
     */
    public function add_admin_menu() {
        // Main menu
        add_menu_page(
            __('ZPOS', 'zpos'),
            __('ZPOS', 'zpos'),
            'manage_options',
            'zpos',
            array($this, 'display_dashboard'),
            'dashicons-store',
            30
        );

        // Dashboard submenu
        add_submenu_page(
            'zpos',
            __('Dashboard', 'zpos'),
            __('Dashboard', 'zpos'),
            'manage_options',
            'zpos',
            array($this, 'display_dashboard')
        );

        // POS submenu
        add_submenu_page(
            'zpos',
            __('POS', 'zpos'),
            __('POS', 'zpos'),
            'manage_options',
            'zpos-pos',
            array($this, 'display_pos')
        );

        // Products submenu
        add_submenu_page(
            'zpos',
            __('Products', 'zpos'),
            __('Products', 'zpos'),
            'manage_options',
            'zpos-products',
            array($this, 'display_products')
        );

        // Customers submenu
        add_submenu_page(
            'zpos',
            __('Customers', 'zpos'),
            __('Customers', 'zpos'),
            'manage_options',
            'zpos-customers',
            array($this, 'display_customers')
        );

        // Orders submenu
        add_submenu_page(
            'zpos',
            __('Orders', 'zpos'),
            __('Orders', 'zpos'),
            'manage_options',
            'zpos-orders',
            array($this, 'display_orders')
        );

        // Inventory submenu
        add_submenu_page(
            'zpos',
            __('Inventory', 'zpos'),
            __('Inventory', 'zpos'),
            'manage_options',
            'zpos-inventory',
            array($this, 'display_inventory')
        );

        // Warranty submenu
        add_submenu_page(
            'zpos',
            __('Warranty', 'zpos'),
            __('Warranty', 'zpos'),
            'manage_options',
            'zpos-warranty',
            array($this, 'display_warranty')
        );

        // Reports submenu
        add_submenu_page(
            'zpos',
            __('Reports', 'zpos'),
            __('Reports', 'zpos'),
            'manage_options',
            'zpos-reports',
            array($this, 'display_reports')
        );

        // Settings submenu
        add_submenu_page(
            'zpos',
            __('Settings', 'zpos'),
            __('Settings', 'zpos'),
            'manage_options',
            'zpos-settings',
            array($this, 'display_settings')
        );
    }

    /**
     * Initialize settings.
     *
     * @since    1.0.0
     */
    public function init_settings() {
        // Check if setup wizard needs to be run
        if (!get_option('zpos_setup_completed') && !isset($_GET['page']) || $_GET['page'] !== 'zpos-setup') {
            if (current_user_can('manage_options')) {
                add_action('admin_notices', array($this, 'setup_wizard_notice'));
            }
        }
    }

    /**
     * Display setup wizard notice.
     *
     * @since    1.0.0
     */
    public function setup_wizard_notice() {
        ?>
        <div class="notice notice-info is-dismissible">
            <p>
                <?php _e('Welcome to ZPOS! Please run the setup wizard to configure your Point of Sale system.', 'zpos'); ?>
                <a href="<?php echo admin_url('admin.php?page=zpos-setup'); ?>" class="button button-primary">
                    <?php _e('Run Setup Wizard', 'zpos'); ?>
                </a>
            </p>
        </div>
        <?php
    }

    /**
     * Check if current page is a ZPOS admin page.
     *
     * @since    1.0.0
     * @return   bool
     */
    private function is_zpos_admin_page() {
        $screen = get_current_screen();
        return $screen && strpos($screen->id, 'zpos') !== false;
    }

    /**
     * Display dashboard page.
     *
     * @since    1.0.0
     */
    public function display_dashboard() {
        include_once ZPOS_PLUGIN_DIR . 'templates/admin/dashboard.php';
    }

    /**
     * Display POS page.
     *
     * @since    1.0.0
     */
    public function display_pos() {
        include_once ZPOS_PLUGIN_DIR . 'templates/admin/pos.php';
    }

    /**
     * Display products page.
     *
     * @since    1.0.0
     */
    public function display_products() {
        include_once ZPOS_PLUGIN_DIR . 'templates/admin/products.php';
    }

    /**
     * Display customers page.
     *
     * @since    1.0.0
     */
    public function display_customers() {
        include_once ZPOS_PLUGIN_DIR . 'templates/admin/customers.php';
    }

    /**
     * Display orders page.
     *
     * @since    1.0.0
     */
    public function display_orders() {
        include_once ZPOS_PLUGIN_DIR . 'templates/admin/orders.php';
    }

    /**
     * Display inventory page.
     *
     * @since    1.0.0
     */
    public function display_inventory() {
        include_once ZPOS_PLUGIN_DIR . 'templates/admin/inventory.php';
    }

    /**
     * Display warranty page.
     *
     * @since    1.0.0
     */
    public function display_warranty() {
        include_once ZPOS_PLUGIN_DIR . 'templates/admin/warranty.php';
    }

    /**
     * Display reports page.
     *
     * @since    1.0.0
     */
    public function display_reports() {
        include_once ZPOS_PLUGIN_DIR . 'templates/admin/reports.php';
    }

    /**
     * Display settings page.
     *
     * @since    1.0.0
     */
    public function display_settings() {
        include_once ZPOS_PLUGIN_DIR . 'templates/admin/settings.php';
    }
}
