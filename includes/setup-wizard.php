<?php
/**
 * Setup Wizard functionality for ZPOS
 *
 * @link       https://yourwebsite.com
 * @since      1.0.0
 *
 * @package    ZPOS
 * @subpackage ZPOS/includes
 */

/**
 * Setup Wizard class.
 *
 * Handles the initial setup wizard for configuring ZPOS.
 *
 * @package    ZPOS
 * @subpackage ZPOS/includes
 * @author     Your Name <your.email@example.com>
 */
class ZPOS_Setup_Wizard {

    /**
     * Current step in the setup wizard.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $step    Current step.
     */
    private $step = '';

    /**
     * Available wizard steps.
     *
     * @since    1.0.0
     * @access   private
     * @var      array    $steps    Available steps.
     */
    private $steps = array();    /**
     * Initialize the setup wizard.
     *
     * @since    1.0.0
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'admin_menus'));
        add_action('admin_init', array($this, 'setup_wizard'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_zpos_setup_wizard', array($this, 'ajax_handler'));
    }

    /**
     * Add admin menus for setup wizard.
     *
     * @since    1.0.0
     */
    public function admin_menus() {
        add_dashboard_page(
            __('ZPOS Setup', 'zpos'),
            __('ZPOS Setup', 'zpos'),
            'manage_options',
            'zpos-setup',
            array($this, 'setup_wizard_page')
        );
    }

    /**
     * Enqueue setup wizard scripts and styles.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts($hook) {
        // Only load on setup wizard page
        if ('dashboard_page_zpos-setup' !== $hook) {
            return;
        }

        // Enqueue wizard styles
        wp_enqueue_style(
            'zpos-setup-wizard',
            plugin_dir_url(__FILE__) . '../assets/css/setup-wizard.css',
            array(),
            '1.0.0'
        );

        // Enqueue wizard scripts
        wp_enqueue_script(
            'zpos-setup-wizard',
            plugin_dir_url(__FILE__) . '../assets/js/setup-wizard.js',
            array('jquery'),
            '1.0.0',
            true
        );

        // Localize script with data
        wp_localize_script('zpos-setup-wizard', 'zpos_wizard_vars', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('zpos_setup_wizard_nonce'),
            'admin_url' => admin_url(),
            'current_step' => isset($_GET['step']) ? sanitize_key($_GET['step']) : 'welcome',
            'text' => array(
                'saving' => __('Saving...', 'zpos'),
                'next' => __('Next Step', 'zpos'),
                'previous' => __('Previous Step', 'zpos'),
                'complete' => __('Complete Setup', 'zpos'),
                'skip' => __('Skip Setup', 'zpos'),
                'error' => __('An error occurred. Please try again.', 'zpos'),
                'confirm_skip' => __('Are you sure you want to skip the setup wizard?', 'zpos'),
                'required_field' => __('This field is required.', 'zpos'),
                'invalid_email' => __('Please enter a valid email address.', 'zpos')
            )
        ));
    }

    /**
     * Setup wizard initialization.
     *
     * @since    1.0.0
     */
    public function setup_wizard() {
        if (empty($_GET['page']) || 'zpos-setup' !== $_GET['page']) {
            return;
        }

        $this->steps = array(
            'welcome' => array(
                'name' => __('Welcome', 'zpos'),
                'view' => array($this, 'setup_welcome'),
                'handler' => array($this, 'setup_welcome_save')
            ),
            'woocommerce' => array(
                'name' => __('WooCommerce Sync', 'zpos'),
                'view' => array($this, 'setup_woocommerce'),
                'handler' => array($this, 'setup_woocommerce_save')
            ),
            'configuration' => array(
                'name' => __('Configuration', 'zpos'),
                'view' => array($this, 'setup_configuration'),
                'handler' => array($this, 'setup_configuration_save')
            ),
            'confirmation' => array(
                'name' => __('Confirmation', 'zpos'),
                'view' => array($this, 'setup_confirmation'),
                'handler' => array($this, 'setup_confirmation_save')
            )
        );

        $this->step = isset($_GET['step']) ? sanitize_key($_GET['step']) : current(array_keys($this->steps));

        // Enqueue wizard styles and scripts
        wp_enqueue_style('zpos-setup-wizard', ZPOS_PLUGIN_URL . 'assets/css/setup-wizard.css', array(), ZPOS_VERSION);
        wp_enqueue_script('zpos-setup-wizard', ZPOS_PLUGIN_URL . 'assets/js/setup-wizard.js', array('jquery'), ZPOS_VERSION, true);
        
        wp_localize_script('zpos-setup-wizard', 'zpos_setup', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('zpos_setup_nonce'),
            'steps' => array_keys($this->steps),
            'current_step' => $this->step,
            'text' => array(
                'saving' => __('Saving...', 'zpos'),
                'next' => __('Next Step', 'zpos'),
                'previous' => __('Previous Step', 'zpos'),
                'finish' => __('Finish Setup', 'zpos'),
                'error' => __('An error occurred. Please try again.', 'zpos')
            )
        ));
    }

    /**
     * Display setup wizard page.
     *
     * @since    1.0.0
     */
    public function setup_wizard_page() {
        $this->setup_wizard_header();
        $this->setup_wizard_steps();
        $this->setup_wizard_content();
        $this->setup_wizard_footer();
    }

    /**
     * Setup wizard header.
     *
     * @since    1.0.0
     */
    private function setup_wizard_header() {
        ?>
        <!DOCTYPE html>
        <html <?php language_attributes(); ?>>
        <head>
            <meta name="viewport" content="width=device-width" />
            <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
            <title><?php _e('ZPOS Setup Wizard', 'zpos'); ?></title>
            <?php wp_print_head_scripts(); ?>
            <?php do_action('admin_print_styles'); ?>
        </head>
        <body class="zpos-setup wp-core-ui">
            <div class="zpos-setup-wrapper">
                <h1 class="zpos-setup-logo">
                    <span class="dashicons dashicons-store"></span>
                    <?php _e('ZPOS Setup', 'zpos'); ?>
                </h1>
        <?php
    }

    /**
     * Setup wizard steps navigation.
     *
     * @since    1.0.0
     */
    private function setup_wizard_steps() {
        $output_steps = $this->steps;
        ?>
        <ol class="zpos-setup-steps">
            <?php
            foreach ($output_steps as $step_key => $step) {
                $is_completed = array_search($this->step, array_keys($this->steps), true) > array_search($step_key, array_keys($this->steps), true);
                $is_current = $step_key === $this->step;
                
                if ($is_completed) {
                    $class = 'done';
                } elseif ($is_current) {
                    $class = 'active';
                } else {
                    $class = '';
                }
                ?>
                <li class="<?php echo esc_attr($class); ?>">
                    <?php echo esc_html($step['name']); ?>
                </li>
                <?php
            }
            ?>
        </ol>
        <?php
    }

    /**
     * Setup wizard content.
     *
     * @since    1.0.0
     */
    private function setup_wizard_content() {
        echo '<div class="zpos-setup-content">';
        if (!empty($this->steps[$this->step]['view'])) {
            call_user_func($this->steps[$this->step]['view']);
        }
        echo '</div>';
    }

    /**
     * Setup wizard footer.
     *
     * @since    1.0.0
     */
    private function setup_wizard_footer() {
        ?>
            </div>
            <?php wp_print_footer_scripts(); ?>
        </body>
        </html>
        <?php
    }

    /**
     * Welcome step view.
     *
     * @since    1.0.0
     */
    private function setup_welcome() {
        ?>
        <div class="zpos-setup-step" id="welcome">
            <h2><?php _e('Welcome to ZPOS!', 'zpos'); ?></h2>
            <p class="zpos-setup-description">
                <?php _e('Thank you for choosing ZPOS - the complete Point of Sale solution for WordPress. This setup wizard will help you configure your store and get you started quickly.', 'zpos'); ?>
            </p>
            
            <div class="zpos-setup-features">
                <div class="feature">
                    <span class="dashicons dashicons-cart"></span>
                    <h3><?php _e('Point of Sale', 'zpos'); ?></h3>
                    <p><?php _e('Complete POS system for in-store sales', 'zpos'); ?></p>
                </div>
                <div class="feature">
                    <span class="dashicons dashicons-products"></span>
                    <h3><?php _e('Inventory Management', 'zpos'); ?></h3>
                    <p><?php _e('Track stock levels and manage products', 'zpos'); ?></p>
                </div>
                <div class="feature">
                    <span class="dashicons dashicons-groups"></span>
                    <h3><?php _e('Customer Management', 'zpos'); ?></h3>
                    <p><?php _e('Manage customer data and purchase history', 'zpos'); ?></p>
                </div>
                <div class="feature">
                    <span class="dashicons dashicons-shield-alt"></span>
                    <h3><?php _e('Warranty System', 'zpos'); ?></h3>
                    <p><?php _e('Track product warranties and service', 'zpos'); ?></p>
                </div>
            </div>

            <p class="zpos-setup-note">
                <strong><?php _e('Note:', 'zpos'); ?></strong>
                <?php _e('This wizard will take approximately 5 minutes to complete. You can always change these settings later from the ZPOS settings page.', 'zpos'); ?>
            </p>

            <div class="zpos-setup-actions">
                <button type="button" class="button-primary button-large zpos-setup-continue" data-next="woocommerce">
                    <?php _e('Let\'s Start!', 'zpos'); ?>
                    <span class="dashicons dashicons-arrow-right-alt"></span>
                </button>
                <a href="<?php echo esc_url(admin_url()); ?>" class="button-secondary">
                    <?php _e('Skip Setup', 'zpos'); ?>
                </a>
            </div>
        </div>
        <?php
    }

    /**
     * WooCommerce sync step view.
     *
     * @since    1.0.0
     */
    private function setup_woocommerce() {
        $woocommerce_active = class_exists('WooCommerce');
        $sync_enabled = get_option('zpos_woocommerce_sync_enabled', false);
        ?>
        <div class="zpos-setup-step" id="woocommerce">
            <h2><?php _e('WooCommerce Integration', 'zpos'); ?></h2>
            <p class="zpos-setup-description">
                <?php _e('ZPOS can work independently or integrate with WooCommerce. Choose your preferred setup below.', 'zpos'); ?>
            </p>

            <form method="post" class="zpos-setup-form">
                <?php wp_nonce_field('zpos_setup_woocommerce', 'zpos_setup_nonce'); ?>
                
                <div class="zpos-woocommerce-status">
                    <?php if ($woocommerce_active) : ?>
                        <div class="status-item status-active">
                            <span class="dashicons dashicons-yes-alt"></span>
                            <strong><?php _e('WooCommerce is installed and active', 'zpos'); ?></strong>
                        </div>
                    <?php else : ?>
                        <div class="status-item status-inactive">
                            <span class="dashicons dashicons-warning"></span>
                            <strong><?php _e('WooCommerce is not installed', 'zpos'); ?></strong>
                            <p><?php _e('ZPOS will work independently without WooCommerce integration.', 'zpos'); ?></p>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if ($woocommerce_active) : ?>
                    <div class="zpos-sync-options">
                        <h3><?php _e('Synchronization Options', 'zpos'); ?></h3>
                        
                        <label class="sync-option">
                            <input type="radio" name="sync_enabled" value="1" <?php checked($sync_enabled, true); ?>>
                            <div class="option-content">
                                <strong><?php _e('Enable WooCommerce Sync', 'zpos'); ?></strong>
                                <p><?php _e('Sync products, customers, and orders between ZPOS and WooCommerce.', 'zpos'); ?></p>
                                <ul class="option-features">
                                    <li><?php _e('Import existing WooCommerce data', 'zpos'); ?></li>
                                    <li><?php _e('Two-way synchronization', 'zpos'); ?></li>
                                    <li><?php _e('Automatic updates', 'zpos'); ?></li>
                                </ul>
                            </div>
                        </label>

                        <label class="sync-option">
                            <input type="radio" name="sync_enabled" value="0" <?php checked($sync_enabled, false); ?>>
                            <div class="option-content">
                                <strong><?php _e('Independent Mode', 'zpos'); ?></strong>
                                <p><?php _e('Use ZPOS with its own database, separate from WooCommerce.', 'zpos'); ?></p>
                                <ul class="option-features">
                                    <li><?php _e('Complete independence', 'zpos'); ?></li>
                                    <li><?php _e('No data conflicts', 'zpos'); ?></li>
                                    <li><?php _e('Faster performance', 'zpos'); ?></li>
                                </ul>
                            </div>
                        </label>
                    </div>

                    <div class="sync-settings" id="sync-settings" style="display: <?php echo $sync_enabled ? 'block' : 'none'; ?>;">
                        <h4><?php _e('Sync Settings', 'zpos'); ?></h4>
                        
                        <label>
                            <input type="checkbox" name="sync_products" value="1" checked>
                            <?php _e('Sync Products', 'zpos'); ?>
                            <span class="description"><?php _e('Import existing products from WooCommerce', 'zpos'); ?></span>
                        </label>

                        <label>
                            <input type="checkbox" name="sync_customers" value="1" checked>
                            <?php _e('Sync Customers', 'zpos'); ?>
                            <span class="description"><?php _e('Import existing customers from WooCommerce', 'zpos'); ?></span>
                        </label>

                        <label>
                            <input type="checkbox" name="sync_orders" value="1">
                            <?php _e('Sync Orders', 'zpos'); ?>
                            <span class="description"><?php _e('Import existing orders from WooCommerce', 'zpos'); ?></span>
                        </label>
                    </div>
                <?php else : ?>
                    <input type="hidden" name="sync_enabled" value="0">
                <?php endif; ?>

                <div class="zpos-setup-actions">
                    <button type="button" class="button-secondary zpos-setup-back" data-prev="welcome">
                        <span class="dashicons dashicons-arrow-left-alt"></span>
                        <?php _e('Previous', 'zpos'); ?>
                    </button>
                    <button type="submit" class="button-primary button-large zpos-setup-continue" data-next="configuration">
                        <?php _e('Continue', 'zpos'); ?>
                        <span class="dashicons dashicons-arrow-right-alt"></span>
                    </button>
                </div>
            </form>
        </div>
        <?php
    }

    /**
     * Configuration step view.
     *
     * @since    1.0.0
     */
    private function setup_configuration() {
        $currency = get_option('zpos_currency', 'USD');
        $timezone = get_option('zpos_timezone', get_option('timezone_string', 'UTC'));
        $store_name = get_option('zpos_store_name', get_bloginfo('name'));
        $store_address = get_option('zpos_store_address', '');
        $store_phone = get_option('zpos_store_phone', '');
        $store_email = get_option('zpos_store_email', get_option('admin_email'));
        ?>
        <div class="zpos-setup-step" id="configuration">
            <h2><?php _e('Store Configuration', 'zpos'); ?></h2>
            <p class="zpos-setup-description">
                <?php _e('Configure your store\'s basic settings. These can be changed later in the settings page.', 'zpos'); ?>
            </p>

            <form method="post" class="zpos-setup-form">
                <?php wp_nonce_field('zpos_setup_configuration', 'zpos_setup_nonce'); ?>
                
                <div class="zpos-config-section">
                    <h3><?php _e('Store Information', 'zpos'); ?></h3>
                    
                    <div class="form-row">
                        <label for="store_name"><?php _e('Store Name', 'zpos'); ?></label>
                        <input type="text" id="store_name" name="store_name" value="<?php echo esc_attr($store_name); ?>" required>
                        <span class="description"><?php _e('This will appear on receipts and invoices', 'zpos'); ?></span>
                    </div>

                    <div class="form-row">
                        <label for="store_address"><?php _e('Store Address', 'zpos'); ?></label>
                        <textarea id="store_address" name="store_address" rows="3"><?php echo esc_textarea($store_address); ?></textarea>
                        <span class="description"><?php _e('Full address including city, state, and postal code', 'zpos'); ?></span>
                    </div>

                    <div class="form-row-group">
                        <div class="form-row">
                            <label for="store_phone"><?php _e('Phone Number', 'zpos'); ?></label>
                            <input type="tel" id="store_phone" name="store_phone" value="<?php echo esc_attr($store_phone); ?>">
                        </div>

                        <div class="form-row">
                            <label for="store_email"><?php _e('Email Address', 'zpos'); ?></label>
                            <input type="email" id="store_email" name="store_email" value="<?php echo esc_attr($store_email); ?>" required>
                        </div>
                    </div>
                </div>

                <div class="zpos-config-section">
                    <h3><?php _e('Regional Settings', 'zpos'); ?></h3>
                    
                    <div class="form-row-group">
                        <div class="form-row">
                            <label for="currency"><?php _e('Currency', 'zpos'); ?></label>
                            <select id="currency" name="currency" required>
                                <?php
                                $currencies = array(
                                    'USD' => __('US Dollar ($)', 'zpos'),
                                    'EUR' => __('Euro (€)', 'zpos'),
                                    'GBP' => __('British Pound (£)', 'zpos'),
                                    'JPY' => __('Japanese Yen (¥)', 'zpos'),
                                    'CAD' => __('Canadian Dollar (C$)', 'zpos'),
                                    'AUD' => __('Australian Dollar (A$)', 'zpos'),
                                    'CHF' => __('Swiss Franc (CHF)', 'zpos'),
                                    'CNY' => __('Chinese Yuan (¥)', 'zpos'),
                                    'SEK' => __('Swedish Krona (kr)', 'zpos'),
                                    'NZD' => __('New Zealand Dollar (NZ$)', 'zpos'),
                                    'VND' => __('Vietnamese Dong (₫)', 'zpos'),
                                );
                                foreach ($currencies as $code => $name) {
                                    echo '<option value="' . esc_attr($code) . '"' . selected($currency, $code, false) . '>' . esc_html($name) . '</option>';
                                }
                                ?>
                            </select>
                        </div>

                        <div class="form-row">
                            <label for="timezone"><?php _e('Timezone', 'zpos'); ?></label>
                            <select id="timezone" name="timezone" required>
                                <?php
                                $timezones = timezone_identifiers_list();
                                foreach ($timezones as $tz) {
                                    echo '<option value="' . esc_attr($tz) . '"' . selected($timezone, $tz, false) . '>' . esc_html($tz) . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="zpos-config-section">
                    <h3><?php _e('Inventory Settings', 'zpos'); ?></h3>
                    
                    <div class="form-row">
                        <label for="low_stock_threshold"><?php _e('Low Stock Threshold', 'zpos'); ?></label>
                        <input type="number" id="low_stock_threshold" name="low_stock_threshold" value="<?php echo esc_attr(get_option('zpos_low_stock_threshold', 5)); ?>" min="0" max="100">
                        <span class="description"><?php _e('Alert when stock falls below this number', 'zpos'); ?></span>
                    </div>

                    <div class="form-row">
                        <label for="tax_rate"><?php _e('Default Tax Rate (%)', 'zpos'); ?></label>
                        <input type="number" id="tax_rate" name="tax_rate" value="<?php echo esc_attr(get_option('zpos_tax_rate', 0)); ?>" min="0" max="100" step="0.01">
                        <span class="description"><?php _e('Default tax rate for products (can be overridden per product)', 'zpos'); ?></span>
                    </div>
                </div>

                <div class="zpos-setup-actions">
                    <button type="button" class="button-secondary zpos-setup-back" data-prev="woocommerce">
                        <span class="dashicons dashicons-arrow-left-alt"></span>
                        <?php _e('Previous', 'zpos'); ?>
                    </button>
                    <button type="submit" class="button-primary button-large zpos-setup-continue" data-next="confirmation">
                        <?php _e('Continue', 'zpos'); ?>
                        <span class="dashicons dashicons-arrow-right-alt"></span>
                    </button>
                </div>
            </form>
        </div>
        <?php
    }

    /**
     * Confirmation step view.
     *
     * @since    1.0.0
     */
    private function setup_confirmation() {
        $sync_enabled = get_option('zpos_woocommerce_sync_enabled', false);
        $currency = get_option('zpos_currency', 'USD');
        $timezone = get_option('zpos_timezone', 'UTC');
        $store_name = get_option('zpos_store_name', '');
        ?>
        <div class="zpos-setup-step" id="confirmation">
            <h2><?php _e('Setup Complete!', 'zpos'); ?></h2>
            <p class="zpos-setup-description">
                <?php _e('Review your settings below and click "Finish Setup" to complete the configuration.', 'zpos'); ?>
            </p>

            <div class="zpos-setup-summary">
                <div class="summary-section">
                    <h3><?php _e('WooCommerce Integration', 'zpos'); ?></h3>
                    <p>
                        <?php if ($sync_enabled) : ?>
                            <span class="status-enabled"><?php _e('Enabled', 'zpos'); ?></span>
                            <?php _e('ZPOS will sync with WooCommerce', 'zpos'); ?>
                        <?php else : ?>
                            <span class="status-disabled"><?php _e('Disabled', 'zpos'); ?></span>
                            <?php _e('ZPOS will work independently', 'zpos'); ?>
                        <?php endif; ?>
                    </p>
                </div>

                <div class="summary-section">
                    <h3><?php _e('Store Information', 'zpos'); ?></h3>
                    <p><strong><?php _e('Name:', 'zpos'); ?></strong> <?php echo esc_html($store_name); ?></p>
                    <p><strong><?php _e('Currency:', 'zpos'); ?></strong> <?php echo esc_html($currency); ?></p>
                    <p><strong><?php _e('Timezone:', 'zpos'); ?></strong> <?php echo esc_html($timezone); ?></p>
                </div>

                <div class="summary-section">
                    <h3><?php _e('Next Steps', 'zpos'); ?></h3>
                    <ul class="next-steps-list">
                        <li><?php _e('Add your first products', 'zpos'); ?></li>
                        <li><?php _e('Configure warranty packages', 'zpos'); ?></li>
                        <li><?php _e('Start making sales with the POS system', 'zpos'); ?></li>
                        <li><?php _e('Explore reports and analytics', 'zpos'); ?></li>
                    </ul>
                </div>
            </div>

            <form method="post" class="zpos-setup-form">
                <?php wp_nonce_field('zpos_setup_confirmation', 'zpos_setup_nonce'); ?>
                
                <div class="zpos-setup-actions">
                    <button type="button" class="button-secondary zpos-setup-back" data-prev="configuration">
                        <span class="dashicons dashicons-arrow-left-alt"></span>
                        <?php _e('Previous', 'zpos'); ?>
                    </button>
                    <button type="submit" class="button-primary button-large button-hero zpos-setup-finish">
                        <?php _e('Finish Setup', 'zpos'); ?>
                        <span class="dashicons dashicons-yes"></span>
                    </button>
                </div>
            </form>
        </div>
        <?php
    }

    /**
     * Save welcome step.
     *
     * @since    1.0.0
     */
    private function setup_welcome_save() {
        // Nothing to save for welcome step
        return true;
    }

    /**
     * Save WooCommerce step.
     *
     * @since    1.0.0
     */
    private function setup_woocommerce_save() {
        if (!wp_verify_nonce($_POST['zpos_setup_nonce'], 'zpos_setup_woocommerce')) {
            return false;
        }

        $sync_enabled = isset($_POST['sync_enabled']) ? (bool) $_POST['sync_enabled'] : false;
        update_option('zpos_woocommerce_sync_enabled', $sync_enabled);

        if ($sync_enabled && class_exists('WooCommerce')) {
            // Save sync preferences
            $sync_products = isset($_POST['sync_products']) ? (bool) $_POST['sync_products'] : false;
            $sync_customers = isset($_POST['sync_customers']) ? (bool) $_POST['sync_customers'] : false;
            $sync_orders = isset($_POST['sync_orders']) ? (bool) $_POST['sync_orders'] : false;

            update_option('zpos_sync_products', $sync_products);
            update_option('zpos_sync_customers', $sync_customers);
            update_option('zpos_sync_orders', $sync_orders);

            // Schedule initial sync
            if (!wp_next_scheduled('zpos_initial_sync')) {
                wp_schedule_single_event(time() + 60, 'zpos_initial_sync');
            }
        }

        return true;
    }

    /**
     * Save configuration step.
     *
     * @since    1.0.0
     */
    private function setup_configuration_save() {
        if (!wp_verify_nonce($_POST['zpos_setup_nonce'], 'zpos_setup_configuration')) {
            return false;
        }

        // Save store information
        update_option('zpos_store_name', sanitize_text_field($_POST['store_name']));
        update_option('zpos_store_address', sanitize_textarea_field($_POST['store_address']));
        update_option('zpos_store_phone', sanitize_text_field($_POST['store_phone']));
        update_option('zpos_store_email', sanitize_email($_POST['store_email']));

        // Save regional settings
        update_option('zpos_currency', sanitize_text_field($_POST['currency']));
        update_option('zpos_timezone', sanitize_text_field($_POST['timezone']));

        // Save inventory settings
        update_option('zpos_low_stock_threshold', intval($_POST['low_stock_threshold']));
        update_option('zpos_tax_rate', floatval($_POST['tax_rate']));

        return true;
    }

    /**
     * Save confirmation step and complete setup.
     *
     * @since    1.0.0
     */
    private function setup_confirmation_save() {
        if (!wp_verify_nonce($_POST['zpos_setup_nonce'], 'zpos_setup_confirmation')) {
            return false;
        }

        // Mark setup as completed
        update_option('zpos_setup_completed', true);
        update_option('zpos_setup_completed_date', current_time('mysql'));

        // Redirect to dashboard
        wp_redirect(admin_url('admin.php?page=zpos&setup_complete=1'));
        exit;
    }

    /**
     * AJAX handler for wizard steps.
     *
     * @since    1.0.0
     */
    public function ajax_handler() {
        if (!wp_verify_nonce($_POST['nonce'], 'zpos_setup_nonce')) {
            wp_die(__('Security check failed', 'zpos'));
        }

        $step = sanitize_key($_POST['step']);
        $action = sanitize_key($_POST['wizard_action']);

        if (!isset($this->steps[$step])) {
            wp_send_json_error(__('Invalid step', 'zpos'));
        }

        if ($action === 'save' && isset($this->steps[$step]['handler'])) {
            $result = call_user_func($this->steps[$step]['handler']);
            if ($result) {
                wp_send_json_success();
            } else {
                wp_send_json_error(__('Failed to save settings', 'zpos'));
            }
        }

        wp_send_json_error(__('Invalid action', 'zpos'));
    }
}
