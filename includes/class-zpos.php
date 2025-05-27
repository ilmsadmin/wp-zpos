<?php
/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://yourwebsite.com
 * @since      1.0.0
 *
 * @package    ZPOS
 * @subpackage ZPOS/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    ZPOS
 * @subpackage ZPOS/includes
 * @author     Your Name <your.email@example.com>
 */
class ZPOS {

    /**
     * The loader that's responsible for maintaining and registering all hooks that power
     * the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      ZPOS_Loader    $loader    Maintains and registers all hooks for the plugin.
     */
    protected $loader;

    /**
     * The unique identifier of this plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $plugin_name    The string used to uniquely identify this plugin.
     */
    protected $plugin_name;

    /**
     * The current version of the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $version    The current version of the plugin.
     */
    protected $version;

    /**
     * Define the core functionality of the plugin.
     *
     * Set the plugin name and the plugin version that can be used throughout the plugin.
     * Load the dependencies, define the locale, and set the hooks for the admin area and
     * the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function __construct() {
        if (defined('ZPOS_VERSION')) {
            $this->version = ZPOS_VERSION;
        } else {
            $this->version = '1.0.0';
        }
        $this->plugin_name = 'zpos';

        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }

    /**
     * Load the required dependencies for this plugin.
     *
     * Include the following files that make up the plugin:
     *
     * - ZPOS_Loader. Orchestrates the hooks of the plugin.
     * - ZPOS_i18n. Defines internationalization functionality.
     * - ZPOS_Admin. Defines all hooks for the admin area.
     * - ZPOS_Public. Defines all hooks for the public side of the site.
     *
     * Create an instance of the loader which will be used to register the hooks
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function load_dependencies() {

        /**
         * The class responsible for orchestrating the actions and filters of the
         * core plugin.
         */
        require_once ZPOS_PLUGIN_DIR . 'includes/class-zpos-loader.php';

        /**
         * The class responsible for defining internationalization functionality
         * of the plugin.
         */
        require_once ZPOS_PLUGIN_DIR . 'includes/class-zpos-i18n.php';

        /**
         * The class responsible for defining all actions that occur in the admin area.
         */
        require_once ZPOS_PLUGIN_DIR . 'includes/class-zpos-admin.php';        /**
         * The class responsible for defining all actions that occur in the public-facing
         * side of the site.
         */
        require_once ZPOS_PLUGIN_DIR . 'includes/class-zpos-public.php';        /**
         * The class responsible for database operations.
         */
        require_once ZPOS_PLUGIN_DIR . 'includes/database.php';        /**
         * The class responsible for setup wizard functionality.
         */
        require_once ZPOS_PLUGIN_DIR . 'includes/setup-wizard.php';        /**
         * The class responsible for product management.
         */
        require_once ZPOS_PLUGIN_DIR . 'includes/products.php';

        /**
         * The class responsible for customer management.
         */
        require_once ZPOS_PLUGIN_DIR . 'includes/customers.php';        /**
         * The class responsible for product category management.
         */
        require_once ZPOS_PLUGIN_DIR . 'includes/product-categories.php';

        /**
         * The class responsible for order management.
         */
        require_once ZPOS_PLUGIN_DIR . 'includes/orders.php';        /**
         * The class responsible for inventory management.
         */
        require_once ZPOS_PLUGIN_DIR . 'includes/inventory.php';        /**
         * The class responsible for warranty management.
         */
        require_once ZPOS_PLUGIN_DIR . 'includes/warranty.php';

        /**
         * The class responsible for reports system.
         */
        require_once ZPOS_PLUGIN_DIR . 'includes/reports.php';

        /**
         * The class responsible for settings system.
         */
        require_once ZPOS_PLUGIN_DIR . 'includes/settings.php';        /**
         * The class responsible for POS system.
         */        require_once ZPOS_PLUGIN_DIR . 'includes/pos.php';        /**
         * Database fix for missing columns
         */

        require_once ZPOS_PLUGIN_DIR . 'includes/admin-notices.php';

        $this->loader = new ZPOS_Loader();
    }

    /**
     * Define the locale for this plugin for internationalization.
     *
     * Uses the ZPOS_i18n class in order to set the domain and to register the hook
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function set_locale() {
        $plugin_i18n = new ZPOS_i18n();

        $this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
    }    /**
     * Register all of the hooks related to the admin area functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */    private function define_admin_hooks() {
        $plugin_admin = new ZPOS_Admin($this->get_plugin_name(), $this->get_version());
        
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles', 25);
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts', 25);
        // $this->loader->add_action('admin_menu', $plugin_admin, 'add_admin_menu'); // Removed - using ZPOS_Admin_Menus instead
        $this->loader->add_action('admin_init', $plugin_admin, 'init_settings');
        
        // Initialize admin menus (replaces basic admin menu)
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/admin-menus-clean.php';
        $admin_menus = new ZPOS_Admin_Menus();

        // Remove these lines if they exist - they're causing the error
        // $this->loader->add_action('wp_ajax_zpos_get_customers_list', $this->admin_menus, 'ajax_get_customers_list');
        // $this->loader->add_action('wp_ajax_zpos_get_products_list', $this->admin_menus, 'ajax_get_products_list');
        
        // Initialize reports system
        $reports = new ZPOS_Reports();

        // Initialize settings system
        $settings = new ZPOS_Settings();        // Initialize setup wizard if not completed
        if (!get_option('zpos_setup_completed', false)) {
            $setup_wizard = new ZPOS_Setup_Wizard();
        }

        // Initialize database
        ZPOS_Database::init();
    }

    /**
     * Register all of the hooks related to the public-facing functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_public_hooks() {
        $plugin_public = new ZPOS_Public($this->get_plugin_name(), $this->get_version());

        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');
        $this->loader->add_action('init', $plugin_public, 'register_shortcodes');
    }

    /**
     * Run the loader to execute all of the hooks with WordPress.
     *
     * @since    1.0.0
     */
    public function run() {
        $this->loader->run();
    }

    /**
     * The name of the plugin used to uniquely identify it within the context of
     * WordPress and to define internationalization functionality.
     *
     * @since     1.0.0
     * @return    string    The name of the plugin.
     */
    public function get_plugin_name() {
        return $this->plugin_name;
    }

    /**
     * The reference to the class that orchestrates the hooks with the plugin.
     *
     * @since     1.0.0
     * @return    ZPOS_Loader    Orchestrates the hooks of the plugin.
     */
    public function get_loader() {
        return $this->loader;
    }

    /**
     * Retrieve the version number of the plugin.
     *
     * @since     1.0.0
     * @return    string    The version number of the plugin.
     */
    public function get_version() {
        return $this->version;
    }
}
