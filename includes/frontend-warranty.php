<?php
/**
 * Frontend Warranty Check functionality
 *
 * @link       https://yourwebsite.com
 * @since      1.0.0
 *
 * @package    ZPOS
 * @subpackage ZPOS/includes
 */

/**
 * The ZPOS Frontend Warranty class.
 *
 * This class handles all frontend warranty check functionality including shortcode,
 * search functionality, and warranty lookup for customers.
 *
 * @since      1.0.0
 * @package    ZPOS
 * @subpackage ZPOS/includes
 * @author     Your Name <your.email@example.com>
 */
class ZPOS_Frontend_Warranty {

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     */
    public function __construct() {
        // Register shortcode
        add_shortcode('zpos_warranty_check', array($this, 'warranty_check_shortcode'));
        
        // AJAX handlers
        add_action('wp_ajax_zpos_check_warranty_frontend', array($this, 'ajax_check_warranty'));
        add_action('wp_ajax_nopriv_zpos_check_warranty_frontend', array($this, 'ajax_check_warranty'));
        
        // Enqueue frontend scripts
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
    }

    /**
     * Enqueue frontend scripts and styles.
     *
     * @since    1.0.0
     */
    public function enqueue_frontend_scripts() {
        // Only load on pages with warranty check shortcode
        global $post;
        if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'zpos_warranty_check')) {
            wp_enqueue_style(
                'zpos-frontend',
                ZPOS_PLUGIN_URL . 'assets/css/frontend.css',
                array(),
                ZPOS_VERSION,
                'all'
            );

            wp_enqueue_script(
                'zpos-frontend',
                ZPOS_PLUGIN_URL . 'assets/js/frontend.js',
                array('jquery'),
                ZPOS_VERSION,
                true
            );

            // Localize script
            wp_localize_script('zpos-frontend', 'zpos_frontend_vars', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('zpos_frontend_nonce'),
                'messages' => array(
                    'searching' => __('Searching for warranty...', 'zpos'),
                    'no_results' => __('No warranty found with the provided information.', 'zpos'),
                    'error' => __('An error occurred while searching. Please try again.', 'zpos'),
                    'invalid_input' => __('Please provide either a phone number or serial number.', 'zpos'),
                )
            ));
        }
    }

    /**
     * Warranty check shortcode handler.
     *
     * @since    1.0.0
     * @param    array    $atts    Shortcode attributes
     * @return   string            HTML output
     */
    public function warranty_check_shortcode($atts) {
        $atts = shortcode_atts(array(
            'title' => __('Check Your Warranty', 'zpos'),
            'theme' => 'default',
            'show_help' => 'true'
        ), $atts, 'zpos_warranty_check');

        ob_start();
        ?>
        <div class="zpos-warranty-check <?php echo esc_attr('theme-' . $atts['theme']); ?>">
            <div class="zpos-warranty-header">
                <h3><?php echo esc_html($atts['title']); ?></h3>
                <?php if ($atts['show_help'] === 'true'): ?>
                <p class="zpos-warranty-help">
                    <?php _e('Enter your phone number or product serial number to check your warranty status.', 'zpos'); ?>
                </p>
                <?php endif; ?>
            </div>

            <form id="zpos-warranty-form" class="zpos-warranty-form">
                <div class="zpos-form-row">
                    <div class="zpos-field-group">
                        <label for="warranty_phone"><?php _e('Phone Number', 'zpos'); ?></label>
                        <input type="tel" id="warranty_phone" name="phone" placeholder="<?php _e('Enter your phone number', 'zpos'); ?>" class="zpos-input">
                    </div>
                    
                    <div class="zpos-field-separator">
                        <span><?php _e('OR', 'zpos'); ?></span>
                    </div>
                    
                    <div class="zpos-field-group">
                        <label for="warranty_serial"><?php _e('Serial Number', 'zpos'); ?></label>
                        <input type="text" id="warranty_serial" name="serial" placeholder="<?php _e('Enter product serial number', 'zpos'); ?>" class="zpos-input">
                    </div>
                </div>

                <div class="zpos-form-actions">
                    <button type="submit" class="zpos-btn zpos-btn-primary">
                        <span class="zpos-btn-text"><?php _e('Check Warranty', 'zpos'); ?></span>
                        <span class="zpos-spinner"></span>
                    </button>
                </div>
            </form>

            <div id="zpos-warranty-results" class="zpos-warranty-results" style="display: none;">
                <!-- Results will be populated via AJAX -->
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * AJAX handler for warranty check.
     *
     * @since    1.0.0
     */
    public function ajax_check_warranty() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'zpos_frontend_nonce')) {
            wp_send_json_error(__('Security check failed.', 'zpos'));
            return;
        }

        $phone = sanitize_text_field($_POST['phone'] ?? '');
        $serial = sanitize_text_field($_POST['serial'] ?? '');

        // Validate input
        if (empty($phone) && empty($serial)) {
            wp_send_json_error(__('Please provide either a phone number or serial number.', 'zpos'));
            return;
        }

        global $wpdb;
        
        $warranties = array();
        
        // Search by phone number
        if (!empty($phone)) {
            $customer_id = $this->get_customer_id_by_phone($phone);
            if ($customer_id) {
                $warranties = array_merge($warranties, $this->get_warranties_by_customer($customer_id));
            }
        }
        
        // Search by serial number
        if (!empty($serial)) {
            $warranty = $this->get_warranty_by_serial($serial);
            if ($warranty) {
                $warranties[] = $warranty;
            }
        }

        if (empty($warranties)) {
            wp_send_json_error(__('No warranty found with the provided information.', 'zpos'));
            return;
        }

        // Format warranty data for display
        $formatted_warranties = array();
        foreach ($warranties as $warranty) {
            $formatted_warranties[] = $this->format_warranty_for_display($warranty);
        }

        wp_send_json_success(array(
            'warranties' => $formatted_warranties,
            'count' => count($formatted_warranties)
        ));
    }

    /**
     * Get customer ID by phone number.
     *
     * @since    1.0.0
     * @param    string    $phone    Phone number
     * @return   int|null           Customer ID or null
     */
    private function get_customer_id_by_phone($phone) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'zpos_customers';
        
        $customer_id = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$table_name} WHERE phone = %s LIMIT 1",
            $phone
        ));
        
        return $customer_id ? intval($customer_id) : null;
    }

    /**
     * Get warranties by customer ID.
     *
     * @since    1.0.0
     * @param    int      $customer_id    Customer ID
     * @return   array                    Array of warranties
     */
    private function get_warranties_by_customer($customer_id) {
        global $wpdb;
        
        $warranty_table = $wpdb->prefix . 'zpos_warranty';
        $product_table = $wpdb->prefix . 'zpos_products';
        $package_table = $wpdb->prefix . 'zpos_warranty_packages';
        
        $warranties = $wpdb->get_results($wpdb->prepare("
            SELECT w.*, p.name as product_name, p.sku, pkg.name as package_name, pkg.duration_months
            FROM {$warranty_table} w
            LEFT JOIN {$product_table} p ON w.product_id = p.id
            LEFT JOIN {$package_table} pkg ON w.warranty_package_id = pkg.id
            WHERE w.customer_id = %d
            ORDER BY w.created_at DESC
        ", $customer_id), ARRAY_A);
        
        return $warranties ?: array();
    }

    /**
     * Get warranty by serial number.
     *
     * @since    1.0.0
     * @param    string    $serial    Serial number
     * @return   array|null          Warranty data or null
     */
    private function get_warranty_by_serial($serial) {
        global $wpdb;
        
        $warranty_table = $wpdb->prefix . 'zpos_warranty';
        $product_table = $wpdb->prefix . 'zpos_products';
        $package_table = $wpdb->prefix . 'zpos_warranty_packages';
        $customer_table = $wpdb->prefix . 'zpos_customers';
        
        $warranty = $wpdb->get_row($wpdb->prepare("
            SELECT w.*, p.name as product_name, p.sku, pkg.name as package_name, pkg.duration_months,
                   c.name as customer_name, c.phone as customer_phone
            FROM {$warranty_table} w
            LEFT JOIN {$product_table} p ON w.product_id = p.id
            LEFT JOIN {$package_table} pkg ON w.warranty_package_id = pkg.id
            LEFT JOIN {$customer_table} c ON w.customer_id = c.id
            WHERE w.serial_number = %s
            LIMIT 1
        ", $serial), ARRAY_A);
        
        return $warranty;
    }

    /**
     * Format warranty data for frontend display.
     *
     * @since    1.0.0
     * @param    array    $warranty    Warranty data
     * @return   array                Formatted warranty data
     */
    private function format_warranty_for_display($warranty) {
        $start_date = new DateTime($warranty['start_date']);
        $end_date = new DateTime($warranty['end_date']);
        $now = new DateTime();
        
        // Calculate warranty status
        $status = 'active';
        $status_text = __('Active', 'zpos');
        $status_class = 'active';
        
        if ($now > $end_date) {
            $status = 'expired';
            $status_text = __('Expired', 'zpos');
            $status_class = 'expired';
        } elseif ($warranty['status'] === 'cancelled') {
            $status = 'cancelled';
            $status_text = __('Cancelled', 'zpos');
            $status_class = 'cancelled';
        }
        
        // Calculate days remaining
        $days_remaining = 0;
        if ($status === 'active') {
            $interval = $now->diff($end_date);
            $days_remaining = $interval->days;
        }
        
        return array(
            'id' => $warranty['id'],
            'product_name' => $warranty['product_name'] ?: __('Unknown Product', 'zpos'),
            'sku' => $warranty['sku'] ?: '',
            'serial_number' => $warranty['serial_number'],
            'package_name' => $warranty['package_name'] ?: __('Standard Warranty', 'zpos'),
            'start_date' => $start_date->format(get_option('date_format')),
            'end_date' => $end_date->format(get_option('date_format')),
            'status' => $status,
            'status_text' => $status_text,
            'status_class' => $status_class,
            'days_remaining' => $days_remaining,
            'customer_name' => $warranty['customer_name'] ?? '',
            'customer_phone' => $warranty['customer_phone'] ?? ''
        );
    }
}

// Initialize the frontend warranty class
new ZPOS_Frontend_Warranty();
