<?php
/**
 * Warranty Management functionality
 *
 * @link       https://yourwebsite.com
 * @since      1.0.0
 *
 * @package    ZPOS
 * @subpackage ZPOS/includes
 */

/**
 * The ZPOS Warranty class.
 *
 * This class handles all warranty management functionality including warranty packages,
 * registration, tracking, and notifications.
 *
 * @since      1.0.0
 * @package    ZPOS
 * @subpackage ZPOS/includes
 * @author     Your Name <your.email@example.com>
 */
class ZPOS_Warranty {

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     */
    public function __construct() {
        // Register AJAX actions
        add_action('wp_ajax_zpos_get_warranty_packages', array($this, 'ajax_get_warranty_packages'));
        add_action('wp_ajax_zpos_save_warranty_package', array($this, 'ajax_save_warranty_package'));
        add_action('wp_ajax_zpos_delete_warranty_package', array($this, 'ajax_delete_warranty_package'));
        add_action('wp_ajax_zpos_register_warranty', array($this, 'ajax_register_warranty'));
        add_action('wp_ajax_zpos_get_warranties', array($this, 'ajax_get_warranties'));
        add_action('wp_ajax_zpos_search_warranty', array($this, 'ajax_search_warranty'));
        add_action('wp_ajax_zpos_update_warranty_status', array($this, 'ajax_update_warranty_status'));
        add_action('wp_ajax_zpos_get_warranty_details', array($this, 'ajax_get_warranty_details'));
        add_action('wp_ajax_zpos_get_expiring_warranties', array($this, 'ajax_get_expiring_warranties'));
        add_action('wp_ajax_zpos_generate_warranty_report', array($this, 'ajax_generate_warranty_report'));
        
        // Public AJAX actions for warranty registration form
        add_action('wp_ajax_nopriv_zpos_register_warranty_public', array($this, 'ajax_register_warranty_public'));
        add_action('wp_ajax_zpos_register_warranty_public', array($this, 'ajax_register_warranty_public'));
        
        // Schedule daily warranty expiration check
        add_action('zpos_daily_warranty_check', array($this, 'check_warranty_expiration_daily'));
        if (!wp_next_scheduled('zpos_daily_warranty_check')) {
            wp_schedule_event(time(), 'daily', 'zpos_daily_warranty_check');
        }
        
        // Register additional AJAX actions
        add_action('wp_ajax_zpos_save_warranty', array($this, 'ajax_save_warranty'));
        add_action('wp_ajax_zpos_generate_serial_number', array($this, 'ajax_generate_serial_number'));
        add_action('wp_ajax_zpos_get_customers_list', array($this, 'ajax_get_customers_list'));
        add_action('wp_ajax_zpos_get_products_list', array($this, 'ajax_get_products_list'));
        add_action('wp_ajax_zpos_export_warranties', array($this, 'ajax_export_warranties'));
        add_action('wp_ajax_zpos_send_warranty_notifications', array($this, 'ajax_send_warranty_notifications'));
    }

    /**
     * Get recent warranties
     *
     * @since    1.0.0
     * @param    int      $limit    Number of warranties to retrieve
     * @return   array    Array of recent warranties
     */
    public function get_recent_warranties($limit = 5) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'zpos_warranties';
        $table_packages = $wpdb->prefix . 'zpos_warranty_packages';
        
        $warranties = $wpdb->get_results($wpdb->prepare("
            SELECT 
                w.*,
                p.name as package_name,
                p.duration_months
            FROM {$table_name} w
            LEFT JOIN {$table_packages} p ON w.package_id = p.id
            ORDER BY w.created_at DESC
            LIMIT %d
        ", $limit));
        
        // Process warranties to add additional information
        foreach ($warranties as &$warranty) {
            // Calculate warranty status
            $today = new DateTime();
            $end_date = new DateTime($warranty->warranty_end_date);
            $days_remaining = $today->diff($end_date)->days;
            
            if ($warranty->status === 'cancelled') {
                $warranty->status_text = __('Cancelled', 'zpos');
                $warranty->status_class = 'cancelled';
            } else if ($warranty->status === 'claimed') {
                $warranty->status_text = __('Claimed', 'zpos');
                $warranty->status_class = 'claimed';
            } else if ($end_date < $today) {
                $warranty->status_text = __('Expired', 'zpos');
                $warranty->status_class = 'expired';
            } else if ($days_remaining <= 30) {
                $warranty->status_text = __('Expiring Soon', 'zpos');
                $warranty->status_class = 'expiring';
            } else {
                $warranty->status_text = __('Active', 'zpos');
                $warranty->status_class = 'active';
            }
        }
        
        return $warranties;
    }

    /**
     * Get warranty packages
     *
     * @since    1.0.0
     * @return   array    Array of warranty packages
     */
    public function get_warranty_packages() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'zpos_warranty_packages';
        
        $packages = $wpdb->get_results("
            SELECT * FROM {$table_name} 
            WHERE status = 'active' 
            ORDER BY duration_months ASC
        ");
        
        return $packages;
    }

    /**
     * Save warranty package
     *
     * @since    1.0.0
     * @param    array    $package_data    Package data
     * @return   int|bool Package ID on success, false on failure
     */
    public function save_warranty_package($package_data) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'zpos_warranty_packages';
        
        $data = array(
            'name' => sanitize_text_field($package_data['name']),
            'description' => sanitize_textarea_field($package_data['description']),
            'duration_months' => intval($package_data['duration_months']),
            'price' => floatval($package_data['price']),
            'terms_conditions' => wp_kses_post($package_data['terms_conditions']),
            'coverage_details' => wp_kses_post($package_data['coverage_details']),
            'status' => sanitize_text_field($package_data['status']),
            'updated_at' => current_time('mysql')
        );
        
        if (!empty($package_data['id'])) {
            // Update existing package
            $result = $wpdb->update(
                $table_name,
                $data,
                array('id' => intval($package_data['id'])),
                array('%s', '%s', '%d', '%f', '%s', '%s', '%s', '%s'),
                array('%d')
            );
            
            return $result !== false ? intval($package_data['id']) : false;
        } else {
            // Create new package
            $data['created_at'] = current_time('mysql');
            
            $result = $wpdb->insert(
                $table_name,
                $data,
                array('%s', '%s', '%d', '%f', '%s', '%s', '%s', '%s', '%s')
            );
            
            return $result !== false ? $wpdb->insert_id : false;
        }
    }

    /**
     * Delete warranty package
     *
     * @since    1.0.0
     * @param    int    $package_id    Package ID
     * @return   bool   Success status
     */
    public function delete_warranty_package($package_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'zpos_warranty_packages';
        
        // Soft delete by setting status to 'deleted'
        $result = $wpdb->update(
            $table_name,
            array(
                'status' => 'deleted',
                'updated_at' => current_time('mysql')
            ),
            array('id' => intval($package_id)),
            array('%s', '%s'),
            array('%d')
        );
        
        return $result !== false;
    }

    /**
     * Register warranty
     *
     * @since    1.0.0
     * @param    array    $warranty_data    Warranty data
     * @return   int|bool Warranty ID on success, false on failure
     */
    public function register_warranty($warranty_data) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'zpos_warranties';
        
        // Validate required fields
        $required_fields = array('product_name', 'serial_number', 'customer_name', 'customer_email', 'package_id', 'purchase_date');
        foreach ($required_fields as $field) {
            if (empty($warranty_data[$field])) {
                return false;
            }
        }
        
        // Check if serial number already exists
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$table_name} WHERE serial_number = %s AND status != 'cancelled'",
            $warranty_data['serial_number']
        ));
        
        if ($existing) {
            return false; // Serial number already registered
        }
        
        // Get warranty package details
        $package = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}zpos_warranty_packages WHERE id = %d AND status = 'active'",
            intval($warranty_data['package_id'])
        ));
        
        if (!$package) {
            return false; // Invalid package
        }
        
        // Calculate warranty dates
        $purchase_date = $warranty_data['purchase_date'];
        $start_date = $warranty_data['warranty_start_date'] ?? $purchase_date;
        $end_date = date('Y-m-d', strtotime($start_date . ' + ' . $package->duration_months . ' months'));
        
        $data = array(
            'warranty_number' => $this->generate_warranty_number(),
            'product_name' => sanitize_text_field($warranty_data['product_name']),
            'product_model' => sanitize_text_field($warranty_data['product_model'] ?? ''),
            'serial_number' => sanitize_text_field($warranty_data['serial_number']),
            'customer_name' => sanitize_text_field($warranty_data['customer_name']),
            'customer_email' => sanitize_email($warranty_data['customer_email']),
            'customer_phone' => sanitize_text_field($warranty_data['customer_phone'] ?? ''),
            'customer_address' => sanitize_textarea_field($warranty_data['customer_address'] ?? ''),
            'package_id' => intval($warranty_data['package_id']),
            'purchase_date' => $purchase_date,
            'warranty_start_date' => $start_date,
            'warranty_end_date' => $end_date,
            'purchase_amount' => floatval($warranty_data['purchase_amount'] ?? 0),
            'dealer_name' => sanitize_text_field($warranty_data['dealer_name'] ?? ''),
            'notes' => sanitize_textarea_field($warranty_data['notes'] ?? ''),
            'status' => 'active',
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        );
        
        $result = $wpdb->insert($table_name, $data);
        
        if ($result !== false) {
            $warranty_id = $wpdb->insert_id;
            
            // Send warranty confirmation email
            $this->send_warranty_confirmation_email($warranty_id);
            
            return $warranty_id;
        }
        
        return false;
    }

    /**
     * Generate unique warranty number
     *
     * @since    1.0.0
     * @return   string    Warranty number
     */
    private function generate_warranty_number() {
        $prefix = 'WR';
        $year = date('Y');
        $random = str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
        
        return $prefix . $year . $random;
    }

    /**
     * Get warranties with filtering and pagination
     *
     * @since    1.0.0
     * @param    array    $args    Query arguments
     * @return   array    Array of warranties
     */
    public function get_warranties($args = array()) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'zpos_warranties';
        $packages_table = $wpdb->prefix . 'zpos_warranty_packages';
        
        $defaults = array(
            'status' => '',
            'package_id' => '',
            'search' => '',
            'expiring_soon' => false,
            'expired' => false,
            'date_from' => '',
            'date_to' => '',
            'per_page' => 20,
            'page' => 1,
            'orderby' => 'created_at',
            'order' => 'DESC'
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $where_clauses = array('w.id IS NOT NULL');
        $where_values = array();
        
        // Status filter
        if (!empty($args['status'])) {
            $where_clauses[] = 'w.status = %s';
            $where_values[] = $args['status'];
        }
        
        // Package filter
        if (!empty($args['package_id'])) {
            $where_clauses[] = 'w.package_id = %d';
            $where_values[] = intval($args['package_id']);
        }
        
        // Search filter
        if (!empty($args['search'])) {
            $where_clauses[] = '(w.warranty_number LIKE %s OR w.product_name LIKE %s OR w.serial_number LIKE %s OR w.customer_name LIKE %s OR w.customer_email LIKE %s)';
            $search_term = '%' . $wpdb->esc_like($args['search']) . '%';
            $where_values[] = $search_term;
            $where_values[] = $search_term;
            $where_values[] = $search_term;
            $where_values[] = $search_term;
            $where_values[] = $search_term;
        }
        
        // Expiring soon filter (within 30 days)
        if ($args['expiring_soon']) {
            $where_clauses[] = 'w.warranty_end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)';
            $where_clauses[] = 'w.status = "active"';
        }
        
        // Expired filter
        if ($args['expired']) {
            $where_clauses[] = 'w.warranty_end_date < CURDATE()';
            $where_clauses[] = 'w.status = "active"';
        }
        
        // Date range filter
        if (!empty($args['date_from'])) {
            $where_clauses[] = 'DATE(w.created_at) >= %s';
            $where_values[] = $args['date_from'];
        }
        
        if (!empty($args['date_to'])) {
            $where_clauses[] = 'DATE(w.created_at) <= %s';
            $where_values[] = $args['date_to'];
        }
        
        $where_sql = implode(' AND ', $where_clauses);
        
        // Count total records
        $count_sql = "
            SELECT COUNT(*) 
            FROM {$table_name} w
            LEFT JOIN {$packages_table} p ON w.package_id = p.id
            WHERE {$where_sql}
        ";
        if (!empty($where_values)) {
            $count_sql = $wpdb->prepare($count_sql, $where_values);
        }
        $total_records = $wpdb->get_var($count_sql);          // Get warranties
        $offset = ($args['page'] - 1) * $args['per_page'];
          // Make sure orderby contains a valid column name
        if (empty($args['orderby']) || $args['orderby'] === '') {
            $args['orderby'] = 'created_at';
        }
        
        // Properly format the ORDER BY clause and check if it's valid
        $orderby_input = 'w.' . $args['orderby'] . ' ' . $args['order'];
        $orderby = sanitize_sql_orderby($orderby_input);
        
        // If sanitize_sql_orderby returns false, use a default ordering
        if ($orderby === false) {
            $orderby = 'w.created_at DESC';
        }
          
        $sql = "
            SELECT w.*, p.name as package_name, p.duration_months
            FROM {$table_name} w
            LEFT JOIN {$packages_table} p ON w.package_id = p.id
            WHERE {$where_sql} 
            ORDER BY {$orderby} 
            LIMIT %d OFFSET %d
        ";
        
        $query_values = array_merge($where_values, array($args['per_page'], $offset));
        
        $warranties = $wpdb->get_results($wpdb->prepare($sql, $query_values));
        
        // Add warranty status for each warranty
        foreach ($warranties as $warranty) {
            $warranty->warranty_status = $this->get_warranty_status($warranty);
        }
        
        return array(
            'warranties' => $warranties,
            'total' => $total_records,
            'pages' => ceil($total_records / $args['per_page']),
            'current_page' => $args['page']
        );
    }

    /**
     * Get warranty status
     *
     * @since    1.0.0
     * @param    object    $warranty    Warranty object
     * @return   string    Warranty status
     */
    public function get_warranty_status($warranty) {
        if ($warranty->status !== 'active') {
            return $warranty->status;
        }
        
        $today = date('Y-m-d');
        $end_date = $warranty->warranty_end_date;
        
        if ($end_date < $today) {
            return 'expired';
        }
        
        $days_remaining = (strtotime($end_date) - strtotime($today)) / (60 * 60 * 24);
        
        if ($days_remaining <= 30) {
            return 'expiring_soon';
        }
        
        return 'active';
    }

    /**
     * Search warranty by serial number or warranty number
     *
     * @since    1.0.0
     * @param    string    $search_term    Search term
     * @return   object|null    Warranty object or null
     */
    public function search_warranty($search_term) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'zpos_warranties';
        $packages_table = $wpdb->prefix . 'zpos_warranty_packages';
        
        $warranty = $wpdb->get_row($wpdb->prepare("
            SELECT w.*, p.name as package_name, p.duration_months, p.terms_conditions, p.coverage_details
            FROM {$table_name} w
            LEFT JOIN {$packages_table} p ON w.package_id = p.id
            WHERE (w.serial_number = %s OR w.warranty_number = %s)
            AND w.status != 'cancelled'
            ORDER BY w.created_at DESC
            LIMIT 1
        ", $search_term, $search_term));
        
        if ($warranty) {
            $warranty->warranty_status = $this->get_warranty_status($warranty);
        }
        
        return $warranty;
    }

    /**
     * Update warranty status
     *
     * @since    1.0.0
     * @param    int       $warranty_id    Warranty ID
     * @param    string    $status         New status
     * @param    string    $notes          Optional notes
     * @return   bool      Success status
     */
    public function update_warranty_status($warranty_id, $status, $notes = '') {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'zpos_warranties';
        $logs_table = $wpdb->prefix . 'zpos_warranty_logs';
        
        $valid_statuses = array('active', 'cancelled', 'claimed', 'expired');
        
        if (!in_array($status, $valid_statuses)) {
            return false;
        }
        
        $result = $wpdb->update(
            $table_name,
            array(
                'status' => $status,
                'updated_at' => current_time('mysql')
            ),
            array('id' => intval($warranty_id)),
            array('%s', '%s'),
            array('%d')
        );
        
        if ($result !== false) {
            // Log status change
            $this->log_warranty_activity(
                $warranty_id,
                'status_changed',
                sprintf(__('Warranty status changed to %s', 'zpos'), $status),
                $notes
            );
            
            return true;
        }
        
        return false;
    }

    /**
     * Get warranty details by ID
     *
     * @since    1.0.0
     * @param    int    $warranty_id    Warranty ID
     * @return   object|null    Warranty object or null
     */
    public function get_warranty_details($warranty_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'zpos_warranties';
        $packages_table = $wpdb->prefix . 'zpos_warranty_packages';
        $logs_table = $wpdb->prefix . 'zpos_warranty_logs';
        
        // Get warranty details
        $warranty = $wpdb->get_row($wpdb->prepare("
            SELECT w.*, p.name as package_name, p.duration_months, p.terms_conditions, p.coverage_details
            FROM {$table_name} w
            LEFT JOIN {$packages_table} p ON w.package_id = p.id
            WHERE w.id = %d
        ", intval($warranty_id)));
        
        if (!$warranty) {
            return null;
        }
        
        // Get warranty logs
        $logs = $wpdb->get_results($wpdb->prepare("
            SELECT * FROM {$logs_table} 
            WHERE warranty_id = %d 
            ORDER BY created_at DESC
        ", intval($warranty_id)));
        
        $warranty->logs = $logs;
        $warranty->warranty_status = $this->get_warranty_status($warranty);
        
        return $warranty;
    }

    /**
     * Get expiring warranties
     *
     * @since    1.0.0
     * @param    int    $days    Days ahead to check
     * @return   array  Array of expiring warranties
     */
    public function get_expiring_warranties($days = 30) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'zpos_warranties';
        $packages_table = $wpdb->prefix . 'zpos_warranty_packages';
        
        $warranties = $wpdb->get_results($wpdb->prepare("
            SELECT w.*, p.name as package_name
            FROM {$table_name} w
            LEFT JOIN {$packages_table} p ON w.package_id = p.id
            WHERE w.warranty_end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL %d DAY)
            AND w.status = 'active'
            ORDER BY w.warranty_end_date ASC
        ", $days));
        
        foreach ($warranties as $warranty) {
            $warranty->warranty_status = $this->get_warranty_status($warranty);
            $warranty->days_remaining = (strtotime($warranty->warranty_end_date) - time()) / (60 * 60 * 24);
        }
        
        return $warranties;
    }

    /**
     * Generate warranty report
     *
     * @since    1.0.0
     * @param    array    $args    Report arguments
     * @return   array    Report data
     */
    public function generate_warranty_report($args = array()) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'zpos_warranties';
        $packages_table = $wpdb->prefix . 'zpos_warranty_packages';
        
        $defaults = array(
            'date_from' => date('Y-m-01'), // First day of current month
            'date_to' => date('Y-m-d')     // Today
        );
        
        $args = wp_parse_args($args, $defaults);
        
        // Total warranties registered
        $total_warranties = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM {$table_name} 
            WHERE DATE(created_at) BETWEEN %s AND %s
        ", $args['date_from'], $args['date_to']));
        
        // Active warranties
        $active_warranties = $wpdb->get_var("
            SELECT COUNT(*) FROM {$table_name} 
            WHERE status = 'active' AND warranty_end_date >= CURDATE()
        ");
        
        // Expired warranties
        $expired_warranties = $wpdb->get_var("
            SELECT COUNT(*) FROM {$table_name} 
            WHERE status = 'active' AND warranty_end_date < CURDATE()
        ");
        
        // Expiring soon (30 days)
        $expiring_soon = $wpdb->get_var("
            SELECT COUNT(*) FROM {$table_name} 
            WHERE status = 'active' 
            AND warranty_end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
        ");
        
        // Warranties by status
        $by_status = $wpdb->get_results($wpdb->prepare("
            SELECT status, COUNT(*) as count
            FROM {$table_name}
            WHERE DATE(created_at) BETWEEN %s AND %s
            GROUP BY status
        ", $args['date_from'], $args['date_to']));
        
        // Warranties by package
        $by_package = $wpdb->get_results($wpdb->prepare("
            SELECT p.name, COUNT(w.id) as count
            FROM {$table_name} w
            LEFT JOIN {$packages_table} p ON w.package_id = p.id
            WHERE DATE(w.created_at) BETWEEN %s AND %s
            GROUP BY w.package_id, p.name
            ORDER BY count DESC
        ", $args['date_from'], $args['date_to']));
        
        // Monthly registrations (last 12 months)
        $monthly_registrations = $wpdb->get_results("
            SELECT 
                DATE_FORMAT(created_at, '%Y-%m') as month,
                COUNT(*) as count
            FROM {$table_name}
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
            GROUP BY DATE_FORMAT(created_at, '%Y-%m')
            ORDER BY month ASC
        ");
        
        return array(
            'summary' => array(
                'total_warranties' => $total_warranties,
                'active_warranties' => $active_warranties,
                'expired_warranties' => $expired_warranties,
                'expiring_soon' => $expiring_soon
            ),
            'by_status' => $by_status,
            'by_package' => $by_package,
            'monthly_registrations' => $monthly_registrations,
            'date_range' => $args
        );
    }

    /**
     * Send warranty confirmation email
     *
     * @since    1.0.0
     * @param    int    $warranty_id    Warranty ID
     */
    private function send_warranty_confirmation_email($warranty_id) {
        $warranty = $this->get_warranty_details($warranty_id);
        
        if (!$warranty) {
            return;
        }
        
        $subject = sprintf(__('[%s] Warranty Registration Confirmation - %s', 'zpos'), 
            get_bloginfo('name'), 
            $warranty->warranty_number
        );
        
        $message = sprintf(__('Dear %s,', 'zpos'), $warranty->customer_name) . "\n\n";
        $message .= __('Your warranty has been successfully registered with the following details:', 'zpos') . "\n\n";
        $message .= sprintf(__('Warranty Number: %s', 'zpos'), $warranty->warranty_number) . "\n";
        $message .= sprintf(__('Product: %s', 'zpos'), $warranty->product_name) . "\n";
        $message .= sprintf(__('Serial Number: %s', 'zpos'), $warranty->serial_number) . "\n";
        $message .= sprintf(__('Warranty Package: %s', 'zpos'), $warranty->package_name) . "\n";
        $message .= sprintf(__('Warranty Period: %s to %s', 'zpos'), $warranty->warranty_start_date, $warranty->warranty_end_date) . "\n\n";
        $message .= __('Please keep this information safe for future reference.', 'zpos') . "\n\n";
        $message .= __('Thank you for choosing our warranty service.', 'zpos') . "\n\n";
        $message .= sprintf(__('Best regards,', 'zpos')) . "\n";
        $message .= get_bloginfo('name');
        
        wp_mail($warranty->customer_email, $subject, $message);
    }

    /**
     * Daily warranty expiration check (scheduled)
     *
     * @since    1.0.0
     */
    public function check_warranty_expiration_daily() {
        $expiring_warranties = $this->get_expiring_warranties(7); // 7 days ahead
        
        if (!empty($expiring_warranties)) {
            // Send notification emails
            foreach ($expiring_warranties as $warranty) {
                $this->send_warranty_expiration_notice($warranty);
            }
            
            // Send admin summary
            $this->send_admin_expiration_summary($expiring_warranties);
        }
    }

    /**
     * Send warranty expiration notice
     *
     * @since    1.0.0
     * @param    object    $warranty    Warranty object
     */
    private function send_warranty_expiration_notice($warranty) {
        $subject = sprintf(__('[%s] Warranty Expiration Notice - %s', 'zpos'), 
            get_bloginfo('name'), 
            $warranty->warranty_number
        );
        
        $days_remaining = ceil($warranty->days_remaining);
        
        $message = sprintf(__('Dear %s,', 'zpos'), $warranty->customer_name) . "\n\n";
        $message .= sprintf(__('This is to inform you that your warranty for %s (Serial: %s) will expire in %d days on %s.', 'zpos'), 
            $warranty->product_name, 
            $warranty->serial_number, 
            $days_remaining, 
            $warranty->warranty_end_date
        ) . "\n\n";
        $message .= __('If you need to make a warranty claim, please contact us before the expiration date.', 'zpos') . "\n\n";
        $message .= sprintf(__('Warranty Number: %s', 'zpos'), $warranty->warranty_number) . "\n";
        $message .= sprintf(__('Package: %s', 'zpos'), $warranty->package_name) . "\n\n";
        $message .= __('Thank you for choosing our warranty service.', 'zpos') . "\n\n";
        $message .= sprintf(__('Best regards,', 'zpos')) . "\n";
        $message .= get_bloginfo('name');
        
        wp_mail($warranty->customer_email, $subject, $message);
    }

    /**
     * Send admin expiration summary
     *
     * @since    1.0.0
     * @param    array    $expiring_warranties    Array of expiring warranties
     */
    private function send_admin_expiration_summary($expiring_warranties) {
        $admin_email = get_option('admin_email');
        $subject = sprintf(__('[%s] Warranty Expiration Summary', 'zpos'), get_bloginfo('name'));
        
        $message = sprintf(__('The following warranties are expiring within the next 7 days:', 'zpos')) . "\n\n";
        
        foreach ($expiring_warranties as $warranty) {
            $days_remaining = ceil($warranty->days_remaining);
            $message .= sprintf(
                "- %s (%s) - Customer: %s - Expires: %s (%d days)\n",
                $warranty->product_name,
                $warranty->warranty_number,
                $warranty->customer_name,
                $warranty->warranty_end_date,
                $days_remaining
            );
        }
        
        $message .= "\n" . __('Please review these warranties and contact customers if necessary.', 'zpos');
        
        wp_mail($admin_email, $subject, $message);
    }

    /**
     * Log warranty activity
     *
     * @since    1.0.0
     * @param    int       $warranty_id    Warranty ID
     * @param    string    $action         Action type
     * @param    string    $message        Log message
     * @param    string    $notes          Additional notes
     */
    private function log_warranty_activity($warranty_id, $action, $message, $notes = '') {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'zpos_warranty_logs';
        
        $wpdb->insert(
            $table_name,
            array(
                'warranty_id' => intval($warranty_id),
                'action' => $action,
                'message' => $message,
                'notes' => $notes,
                'user_id' => get_current_user_id(),
                'created_at' => current_time('mysql')
            )
        );
    }

    /**
     * Get basic warranty statistics
     *
     * @since    1.0.0
     * @return   array    Array with warranty statistics
     */
    public function get_warranty_stats() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'zpos_warranties';
        $today = date('Y-m-d');
        $expiry_threshold = date('Y-m-d', strtotime('+30 days'));
        
        // Get total warranties
        $total_warranties = $wpdb->get_var("SELECT COUNT(*) FROM {$table_name}");
        
        // Get active warranties
        $active_warranties = $wpdb->get_var("
            SELECT COUNT(*) 
            FROM {$table_name} 
            WHERE warranty_end_date >= '{$today}'
            AND status != 'cancelled'
            AND status != 'claimed'
        ");
        
        // Get warranties expiring soon (in the next 30 days)
        $expiring_soon = $wpdb->get_var("
            SELECT COUNT(*) 
            FROM {$table_name} 
            WHERE warranty_end_date BETWEEN '{$today}' AND '{$expiry_threshold}'
            AND status != 'cancelled'
            AND status != 'claimed'
        ");
        
        // Get expired warranties
        $expired_warranties = $wpdb->get_var("
            SELECT COUNT(*) 
            FROM {$table_name} 
            WHERE warranty_end_date < '{$today}'
            AND status != 'cancelled'
            AND status != 'claimed'
        ");
        
        return array(
            'total_warranties' => (int)$total_warranties,
            'active_warranties' => (int)$active_warranties,
            'expiring_soon' => (int)$expiring_soon,
            'expired_warranties' => (int)$expired_warranties
        );
    }

    // AJAX handlers start here...

    /**
     * AJAX handler for getting warranty packages
     *
     * @since    1.0.0
     */
    public function ajax_get_warranty_packages() {
        check_ajax_referer('zpos_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'zpos'));
        }
        
        $packages = $this->get_warranty_packages();
        wp_send_json_success($packages);
    }

    /**
     * AJAX handler for saving warranty package
     *
     * @since    1.0.0
     */
    public function ajax_save_warranty_package() {
        check_ajax_referer('zpos_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'zpos'));
        }
        
        $package_data = array(
            'id' => intval($_POST['id'] ?? 0),
            'name' => sanitize_text_field($_POST['name']),
            'description' => sanitize_textarea_field($_POST['description']),
            'duration_months' => intval($_POST['duration_months']),
            'price' => floatval($_POST['price']),
            'terms_conditions' => wp_kses_post($_POST['terms_conditions']),
            'coverage_details' => wp_kses_post($_POST['coverage_details']),
            'status' => sanitize_text_field($_POST['status'])
        );
        
        $result = $this->save_warranty_package($package_data);
        
        if ($result) {
            wp_send_json_success(array(
                'message' => __('Warranty package saved successfully', 'zpos'),
                'package_id' => $result
            ));
        } else {
            wp_send_json_error(__('Failed to save warranty package', 'zpos'));
        }
    }

    /**
     * AJAX handler for deleting warranty package
     *
     * @since    1.0.0
     */
    public function ajax_delete_warranty_package() {
        check_ajax_referer('zpos_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'zpos'));
        }
        
        $package_id = intval($_POST['package_id']);
        $result = $this->delete_warranty_package($package_id);
        
        if ($result) {
            wp_send_json_success(__('Warranty package deleted successfully', 'zpos'));
        } else {
            wp_send_json_error(__('Failed to delete warranty package', 'zpos'));
        }
    }

    /**
     * AJAX handler for registering warranty (admin)
     *
     * @since    1.0.0
     */
    public function ajax_register_warranty() {
        check_ajax_referer('zpos_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'zpos'));
        }
        
        $warranty_data = array(
            'product_name' => sanitize_text_field($_POST['product_name']),
            'product_model' => sanitize_text_field($_POST['product_model'] ?? ''),
            'serial_number' => sanitize_text_field($_POST['serial_number']),
            'customer_name' => sanitize_text_field($_POST['customer_name']),
            'customer_email' => sanitize_email($_POST['customer_email']),
            'customer_phone' => sanitize_text_field($_POST['customer_phone'] ?? ''),
            'customer_address' => sanitize_textarea_field($_POST['customer_address'] ?? ''),
            'package_id' => intval($_POST['package_id']),
            'purchase_date' => sanitize_text_field($_POST['purchase_date']),
            'warranty_start_date' => sanitize_text_field($_POST['warranty_start_date'] ?? ''),
            'purchase_amount' => floatval($_POST['purchase_amount'] ?? 0),
            'dealer_name' => sanitize_text_field($_POST['dealer_name'] ?? ''),
            'notes' => sanitize_textarea_field($_POST['notes'] ?? '')
        );
        
        $result = $this->register_warranty($warranty_data);
        
        if ($result) {
            wp_send_json_success(array(
                'message' => __('Warranty registered successfully', 'zpos'),
                'warranty_id' => $result
            ));
        } else {
            wp_send_json_error(__('Failed to register warranty. Please check if the serial number is already registered.', 'zpos'));
        }
    }

    /**
     * AJAX handler for public warranty registration
     *
     * @since    1.0.0
     */
    public function ajax_register_warranty_public() {
        // No nonce check for public form, but add basic security
        if (!wp_verify_nonce($_POST['warranty_nonce'], 'zpos_warranty_registration')) {
            wp_die(__('Security check failed', 'zpos'));
        }
        
        $warranty_data = array(
            'product_name' => sanitize_text_field($_POST['product_name']),
            'product_model' => sanitize_text_field($_POST['product_model'] ?? ''),
            'serial_number' => sanitize_text_field($_POST['serial_number']),
            'customer_name' => sanitize_text_field($_POST['customer_name']),
            'customer_email' => sanitize_email($_POST['customer_email']),
            'customer_phone' => sanitize_text_field($_POST['customer_phone'] ?? ''),
            'customer_address' => sanitize_textarea_field($_POST['customer_address'] ?? ''),
            'package_id' => intval($_POST['package_id']),
            'purchase_date' => sanitize_text_field($_POST['purchase_date']),
            'purchase_amount' => floatval($_POST['purchase_amount'] ?? 0),
            'dealer_name' => sanitize_text_field($_POST['dealer_name'] ?? '')
        );
        
        $result = $this->register_warranty($warranty_data);
        
        if ($result) {
            wp_send_json_success(array(
                'message' => __('Warranty registered successfully. You will receive a confirmation email shortly.', 'zpos'),
                'warranty_id' => $result
            ));
        } else {
            wp_send_json_error(__('Failed to register warranty. Please check if all required fields are filled and the serial number is not already registered.', 'zpos'));
        }
    }

    /**
     * AJAX handler for getting warranties
     *
     * @since    1.0.0
     */
    public function ajax_get_warranties() {
        check_ajax_referer('zpos_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'zpos'));
        }
          $args = array(
            'status' => sanitize_text_field($_POST['status'] ?? ''),
            'package_id' => intval($_POST['package_id'] ?? 0),
            'search' => sanitize_text_field($_POST['search'] ?? ''),
            'expiring_soon' => !empty($_POST['expiring_soon']),
            'expired' => !empty($_POST['expired']),
            'date_from' => sanitize_text_field($_POST['date_from'] ?? ''),
            'date_to' => sanitize_text_field($_POST['date_to'] ?? ''),
            'per_page' => intval($_POST['per_page'] ?? 20),
            'page' => intval($_POST['page'] ?? 1),
            'orderby' => '',
            'order' => ''
        );
        
        // Validate orderby parameter - only allow certain columns
        $allowed_orderby_columns = array('created_at', 'updated_at', 'warranty_end_date', 'warranty_start_date', 
                                         'purchase_date', 'product_name', 'customer_name', 'serial_number', 'status');
        
        $orderby = sanitize_text_field($_POST['orderby'] ?? 'created_at');
        if (in_array($orderby, $allowed_orderby_columns)) {
            $args['orderby'] = $orderby;
        } else {
            $args['orderby'] = 'created_at'; // Default fallback
        }
        
        // Validate order direction - only allow ASC or DESC
        $order = strtoupper(sanitize_text_field($_POST['order'] ?? 'DESC'));
        if ($order === 'ASC' || $order === 'DESC') {
            $args['order'] = $order;
        } else {
            $args['order'] = 'DESC'; // Default fallback
        };
        
        $result = $this->get_warranties($args);
        wp_send_json_success($result);
    }

    /**
     * AJAX handler for searching warranty
     *
     * @since    1.0.0
     */
    public function ajax_search_warranty() {
        // Allow both admin and public access for warranty lookup
        if (is_admin()) {
            check_ajax_referer('zpos_admin_nonce', 'nonce');
            
            if (!current_user_can('manage_options')) {
                wp_die(__('Insufficient permissions', 'zpos'));
            }
        }
        
        $search_term = sanitize_text_field($_POST['search_term']);
        $warranty = $this->search_warranty($search_term);
        
        if ($warranty) {
            wp_send_json_success($warranty);
        } else {
            wp_send_json_error(__('Warranty not found', 'zpos'));
        }
    }

    /**
     * AJAX handler for updating warranty status
     *
     * @since    1.0.0
     */
    public function ajax_update_warranty_status() {
        check_ajax_referer('zpos_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'zpos'));
        }
        
        $warranty_id = intval($_POST['warranty_id']);
        $status = sanitize_text_field($_POST['status']);
        $notes = sanitize_textarea_field($_POST['notes'] ?? '');
        
        $result = $this->update_warranty_status($warranty_id, $status, $notes);
        
        if ($result) {
            wp_send_json_success(__('Warranty status updated successfully', 'zpos'));
        } else {
            wp_send_json_error(__('Failed to update warranty status', 'zpos'));
        }
    }

    /**
     * AJAX handler for getting warranty details
     *
     * @since    1.0.0
     */
    public function ajax_get_warranty_details() {
        check_ajax_referer('zpos_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'zpos'));
        }
        
        $warranty_id = intval($_POST['warranty_id']);
        $warranty = $this->get_warranty_details($warranty_id);
        
        if ($warranty) {
            wp_send_json_success($warranty);
        } else {
            wp_send_json_error(__('Warranty not found', 'zpos'));
        }
    }

    /**
     * AJAX handler for getting expiring warranties
     *
     * @since    1.0.0
     */
    public function ajax_get_expiring_warranties() {
        check_ajax_referer('zpos_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'zpos'));
        }
        
        $days = intval($_POST['days'] ?? 30);
        $warranties = $this->get_expiring_warranties($days);
        
        wp_send_json_success($warranties);
    }

    /**
     * AJAX handler for generating warranty report
     *
     * @since    1.0.0
     */
    public function ajax_generate_warranty_report() {
        check_ajax_referer('zpos_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'zpos'));
        }
        
        $args = array(
            'date_from' => sanitize_text_field($_POST['date_from'] ?? ''),
            'date_to' => sanitize_text_field($_POST['date_to'] ?? '')
        );
          $result = $this->generate_warranty_report($args);
        wp_send_json_success($result);
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
                
                wp_reset_postdata();
            } else {
                // Fallback - check for custom products table
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

        try {
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
                $result = $this->update_warranty($warranty_id, $warranty_data);
            } else {
                $result = $this->create_warranty($warranty_data);
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
     * AJAX handler to generate serial number
     */
    public function ajax_generate_serial_number() {
        // Check nonce
        if (!check_ajax_referer('zpos_admin_nonce', 'nonce', false)) {
            wp_send_json_error('Security check failed');
        }

        // Check permissions
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
     * Create warranty package
     */
    public function create_warranty_package($data) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'zpos_warranty_packages';
        
        $result = $wpdb->insert(
            $table_name,
            array(
                'name' => $data['name'],
                'duration_months' => $data['duration_months'],
                'price' => $data['price'],
                'description' => $data['description'],
                'status' => $data['status'],
                'created_at' => current_time('mysql')
            ),
            array('%s', '%d', '%f', '%s', '%s', '%s')
        );
        
        return $result !== false;
    }
    
    /**
     * Update warranty package
     */
    public function update_warranty_package($id, $data) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'zpos_warranty_packages';
        
        $result = $wpdb->update(
            $table_name,
            array(
                'name' => $data['name'],
                'duration_months' => $data['duration_months'],
                'price' => $data['price'],
                'description' => $data['description'],
                'status' => $data['status'],
                'updated_at' => current_time('mysql')
            ),
            array('id' => $id),
            array('%s', '%d', '%f', '%s', '%s', '%s'),
            array('%d')
        );
        
        return $result !== false;
    }
    
    /**
     * Create warranty
     */
    public function create_warranty($data) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'zpos_warranties';
        
        // Calculate end date based on package
        $package = $this->get_warranty_package($data['package_id']);
        if (!$package) {
            return false;
        }
        
        $start_date = $data['purchase_date'];
        $end_date = date('Y-m-d', strtotime($start_date . ' + ' . $package->duration_months . ' months'));
        
        $result = $wpdb->insert(
            $table_name,
            array(
                'customer_id' => $data['customer_id'],
                'product_id' => $data['product_id'],
                'package_id' => $data['package_id'],
                'serial_number' => $data['serial_number'],
                'purchase_date' => $data['purchase_date'],
                'start_date' => $start_date,
                'end_date' => $end_date,
                'status' => 'active',
                'notes' => $data['notes'],
                'created_at' => current_time('mysql')
            ),
            array('%d', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
        );
        
        return $result !== false;
    }
    
    /**
     * Update warranty
     */
    public function update_warranty($id, $data) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'zpos_warranties';
        
        // Recalculate end date if package changed
        if (isset($data['package_id'])) {
            $package = $this->get_warranty_package($data['package_id']);
            if ($package) {
                $start_date = $data['purchase_date'];
                $data['end_date'] = date('Y-m-d', strtotime($start_date . ' + ' . $package->duration_months . ' months'));
            }
        }
        
        $data['updated_at'] = current_time('mysql');
        
        $result = $wpdb->update(
            $table_name,
            $data,
            array('id' => $id),
            null,
            array('%d')
        );
        
        return $result !== false;
    }
    
    /**
     * Get warranty package by ID
     */
    public function get_warranty_package($id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'zpos_warranty_packages';
        
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id));
    }
    
    // ...existing code...
}
