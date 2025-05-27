<?php
/**
 * ZPOS Customers Management Class
 *
 * @package ZPOS
 * @since 1.0.0
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

class ZPOS_Customers {
    
    /**
     * Table name for customers
     */
    private $table_name;
    
    /**
     * Initialize the class
     */
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'zpos_customers';
    }
    
    /**
     * Create customers table
     */
    public function create_table() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $this->table_name (
            id int(11) NOT NULL AUTO_INCREMENT,
            first_name varchar(100) NOT NULL,
            last_name varchar(100) NOT NULL,
            email varchar(100) UNIQUE,
            phone varchar(20),
            address_line_1 varchar(255),
            address_line_2 varchar(255),
            city varchar(100),
            state varchar(100),
            postal_code varchar(20),
            country varchar(100) DEFAULT 'VN',
            date_of_birth date,
            gender enum('male', 'female', 'other') DEFAULT NULL,
            customer_group varchar(50) DEFAULT 'general',
            discount_percent decimal(5,2) DEFAULT 0.00,
            credit_limit decimal(10,2) DEFAULT 0.00,
            total_spent decimal(10,2) DEFAULT 0.00,
            orders_count int(11) DEFAULT 0,
            last_order_date datetime DEFAULT NULL,
            avatar_url varchar(500),
            notes text,
            status varchar(20) DEFAULT 'active',
            woocommerce_id int(11) DEFAULT NULL,
            wordpress_user_id int(11) DEFAULT NULL,
            loyalty_points int(11) DEFAULT 0,
            referral_code varchar(50),
            referred_by int(11) DEFAULT NULL,
            tags text,
            meta_data text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY email (email),
            KEY phone (phone),
            KEY status (status),
            KEY customer_group (customer_group),
            KEY woocommerce_id (woocommerce_id),
            KEY wordpress_user_id (wordpress_user_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Get all customers with pagination and filters
     */
    public function get_customers($args = array()) {
        global $wpdb;
        
        $defaults = array(
            'per_page' => 20,
            'page' => 1,
            'search' => '',
            'customer_group' => '',
            'status' => '',
            'orderby' => 'created_at',
            'order' => 'DESC'
        );
        
        $args = wp_parse_args($args, $defaults);
        
        // Base query
        $where = array('1=1');
        $values = array();
        
        // Search filter
        if (!empty($args['search'])) {
            $where[] = "(first_name LIKE %s OR last_name LIKE %s OR email LIKE %s OR phone LIKE %s)";
            $search_term = '%' . $wpdb->esc_like($args['search']) . '%';
            $values[] = $search_term;
            $values[] = $search_term;
            $values[] = $search_term;
            $values[] = $search_term;
        }
        
        // Customer group filter
        if (!empty($args['customer_group'])) {
            $where[] = "customer_group = %s";
            $values[] = $args['customer_group'];
        }
        
        // Status filter
        if (!empty($args['status'])) {
            $where[] = "status = %s";
            $values[] = $args['status'];
        }
        
        $where_clause = implode(' AND ', $where);
        
        // Count total records
        $count_sql = "SELECT COUNT(*) FROM $this->table_name WHERE $where_clause";
        if (!empty($values)) {
            $count_sql = $wpdb->prepare($count_sql, $values);
        }
        $total_items = $wpdb->get_var($count_sql);
        
        // Get customers
        $offset = ($args['page'] - 1) * $args['per_page'];
        $order_by = sanitize_sql_orderby($args['orderby'] . ' ' . $args['order']);
        
        $sql = "SELECT * FROM $this->table_name WHERE $where_clause ORDER BY $order_by LIMIT %d OFFSET %d";
        $values[] = $args['per_page'];
        $values[] = $offset;
        
        $customers = $wpdb->get_results($wpdb->prepare($sql, $values));
        
        return array(
            'customers' => $customers,
            'total_items' => $total_items,
            'total_pages' => ceil($total_items / $args['per_page']),
            'current_page' => $args['page']
        );
    }
    
    /**
     * Get customer by ID
     */
    public function get_customer($id) {
        global $wpdb;
        
        $sql = "SELECT * FROM $this->table_name WHERE id = %d";
        return $wpdb->get_row($wpdb->prepare($sql, $id));
    }

    /**
     * Get customers with pagination
     */
    public function get_customers_with_pagination($page = 1, $per_page = 20, $search = '', $group = '') {
        global $wpdb;
        
        $offset = ($page - 1) * $per_page;
        
        // Build WHERE clause
        $where = array('1=1');
        $values = array();
        
        if (!empty($search)) {
            $where[] = "(name LIKE %s OR email LIKE %s OR phone LIKE %s)";
            $search_term = '%' . $wpdb->esc_like($search) . '%';
            $values[] = $search_term;
            $values[] = $search_term;
            $values[] = $search_term;
        }
        
        if (!empty($group)) {
            $where[] = "customer_group = %s";
            $values[] = $group;
        }
        
        $where_sql = implode(' AND ', $where);
        
        // Get total count
        $count_sql = "SELECT COUNT(*) FROM {$this->table_name} WHERE {$where_sql}";
        if (!empty($values)) {
            $count_sql = $wpdb->prepare($count_sql, $values);
        }
        $total_items = $wpdb->get_var($count_sql);
        
        // Get customers
        $sql = "SELECT * FROM {$this->table_name} 
                WHERE {$where_sql} 
                ORDER BY created_at DESC 
                LIMIT %d OFFSET %d";
        
        $query_values = array_merge($values, array($per_page, $offset));
        $customers = $wpdb->get_results($wpdb->prepare($sql, $query_values));
        
        // Calculate pagination
        $total_pages = ceil($total_items / $per_page);
        
        return array(
            'customers' => $customers,
            'pagination' => array(
                'total_items' => $total_items,
                'total_pages' => $total_pages,
                'current_page' => $page,
                'per_page' => $per_page,
                'has_prev' => $page > 1,
                'has_next' => $page < $total_pages
            )
        );
    }

    /**
     * Get customer statistics
     */
    public function get_customer_stats() {
        global $wpdb;
        
        $stats = array();
        
        // Total customers
        $stats['total_customers'] = $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name}");
        
        // New customers this month
        $stats['new_this_month'] = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_name} WHERE DATE(created_at) >= %s",
            date('Y-m-01')
        ));
        
        // Active customers (customers with orders)
        $stats['active_customers'] = $wpdb->get_var(
            "SELECT COUNT(DISTINCT customer_id) FROM {$wpdb->prefix}zpos_orders WHERE customer_id IS NOT NULL"
        );
        
        // Customer groups distribution
        $groups = $wpdb->get_results(
            "SELECT customer_group, COUNT(*) as count FROM {$this->table_name} 
             WHERE customer_group IS NOT NULL AND customer_group != '' 
             GROUP BY customer_group"
        );
        
        $stats['groups'] = array();
        foreach ($groups as $group) {
            $stats['groups'][$group->customer_group] = $group->count;
        }
        
        // Average loyalty points
        $stats['avg_loyalty_points'] = $wpdb->get_var(
            "SELECT AVG(loyalty_points) FROM {$this->table_name} WHERE loyalty_points > 0"
        ) ?: 0;
        
        return $stats;
    }

    /**
     * Sync customer to WooCommerce
     */
    public function sync_to_woocommerce($customer_id) {
        if (!function_exists('wc_create_new_customer')) {
            return false;
        }

        $customer = $this->get_customer($customer_id);
        if (!$customer) {
            return false;
        }

        try {
            // Check if customer already exists in WooCommerce
            if ($customer->woocommerce_id) {
                $wc_customer = new WC_Customer($customer->woocommerce_id);
                if ($wc_customer->get_id()) {
                    // Update existing customer
                    $wc_customer->set_email($customer->email);
                    $wc_customer->set_first_name($customer->name);
                    $wc_customer->set_billing_phone($customer->phone);
                    $wc_customer->save();
                    return true;
                }
            }

            // Create new WooCommerce customer
            $wc_customer_id = wc_create_new_customer(
                $customer->email,
                '',
                $customer->name
            );

            if (is_wp_error($wc_customer_id)) {
                return false;
            }

            // Update ZPOS customer with WooCommerce ID
            global $wpdb;
            $wpdb->update(
                $this->table_name,
                array('woocommerce_id' => $wc_customer_id),
                array('id' => $customer_id),
                array('%d'),
                array('%d')
            );

            return true;

        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Create or update customer
     */
    public function save_customer($data, $id = null) {
        global $wpdb;
        
        // Validate required fields
        if (empty($data['first_name'])) {
            return new WP_Error('missing_first_name', __('First name is required', 'zpos'));
        }
        
        if (empty($data['last_name'])) {
            return new WP_Error('missing_last_name', __('Last name is required', 'zpos'));
        }
        
        if (!empty($data['email']) && !is_email($data['email'])) {
            return new WP_Error('invalid_email', __('Invalid email address', 'zpos'));
        }
        
        // Check for duplicate email
        if (!empty($data['email'])) {
            $existing_customer = $this->get_customer_by_email($data['email']);
            if ($existing_customer && (!$id || $existing_customer->id != $id)) {
                return new WP_Error('duplicate_email', __('Email address already exists', 'zpos'));
            }
        }
        
        // Prepare data
        $customer_data = array(
            'first_name' => sanitize_text_field($data['first_name']),
            'last_name' => sanitize_text_field($data['last_name']),
            'email' => !empty($data['email']) ? sanitize_email($data['email']) : '',
            'phone' => sanitize_text_field($data['phone'] ?? ''),
            'address_line_1' => sanitize_text_field($data['address_line_1'] ?? ''),
            'address_line_2' => sanitize_text_field($data['address_line_2'] ?? ''),
            'city' => sanitize_text_field($data['city'] ?? ''),
            'state' => sanitize_text_field($data['state'] ?? ''),
            'postal_code' => sanitize_text_field($data['postal_code'] ?? ''),
            'country' => sanitize_text_field($data['country'] ?? 'VN'),
            'date_of_birth' => !empty($data['date_of_birth']) ? sanitize_text_field($data['date_of_birth']) : null,
            'gender' => in_array($data['gender'] ?? '', array('male', 'female', 'other')) ? $data['gender'] : null,
            'customer_group' => sanitize_text_field($data['customer_group'] ?? 'general'),
            'discount_percent' => floatval($data['discount_percent'] ?? 0),
            'credit_limit' => floatval($data['credit_limit'] ?? 0),
            'avatar_url' => esc_url_raw($data['avatar_url'] ?? ''),
            'notes' => wp_kses_post($data['notes'] ?? ''),
            'status' => sanitize_text_field($data['status'] ?? 'active'),
            'loyalty_points' => intval($data['loyalty_points'] ?? 0),
            'referral_code' => sanitize_text_field($data['referral_code'] ?? ''),
            'referred_by' => !empty($data['referred_by']) ? intval($data['referred_by']) : null,
            'tags' => is_array($data['tags'] ?? null) ? json_encode($data['tags']) : '',
            'meta_data' => is_array($data['meta_data'] ?? null) ? json_encode($data['meta_data']) : ''
        );
        
        if ($id) {
            // Update existing customer
            $result = $wpdb->update(
                $this->table_name,
                $customer_data,
                array('id' => $id),
                array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%f', '%f', '%s', '%s', '%s', '%d', '%s', '%d', '%s', '%s'),
                array('%d')
            );
            
            if ($result === false) {
                return new WP_Error('update_failed', __('Failed to update customer', 'zpos'));
            }
            
            return $id;
        } else {
            // Generate referral code if not provided
            if (empty($customer_data['referral_code'])) {
                $customer_data['referral_code'] = $this->generate_referral_code();
            }
            
            // Create new customer
            $result = $wpdb->insert(
                $this->table_name,
                $customer_data,
                array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%f', '%f', '%s', '%s', '%s', '%d', '%s', '%d', '%s', '%s')
            );
            
            if ($result === false) {
                return new WP_Error('insert_failed', __('Failed to create customer', 'zpos'));
            }
            
            return $wpdb->insert_id;
        }
    }
    
    /**
     * Delete customer
     */
    public function delete_customer($id) {
        global $wpdb;
        
        $result = $wpdb->delete(
            $this->table_name,
            array('id' => $id),
            array('%d')
        );
        
        return $result !== false;
    }
    
    /**
     * Get customer purchase history
     */
    public function get_customer_orders($customer_id, $limit = 10) {
        global $wpdb;
        
        $orders_table = $wpdb->prefix . 'zpos_sales';
        
        $sql = "SELECT * FROM $orders_table WHERE customer_id = %d ORDER BY created_at DESC LIMIT %d";
        return $wpdb->get_results($wpdb->prepare($sql, $customer_id, $limit));
    }
    
    /**
     * Update customer stats
     */
    public function update_customer_stats($customer_id) {
        global $wpdb;
        
        $orders_table = $wpdb->prefix . 'zpos_sales';
        
        // Get customer stats
        $stats = $wpdb->get_row($wpdb->prepare("
            SELECT 
                COUNT(*) as orders_count,
                SUM(total_amount) as total_spent,
                MAX(created_at) as last_order_date
            FROM $orders_table 
            WHERE customer_id = %d AND status = 'completed'
        ", $customer_id));
        
        // Update customer record
        $wpdb->update(
            $this->table_name,
            array(
                'orders_count' => $stats->orders_count ?? 0,
                'total_spent' => $stats->total_spent ?? 0,
                'last_order_date' => $stats->last_order_date
            ),
            array('id' => $customer_id),
            array('%d', '%f', '%s'),
            array('%d')
        );
    }
    
    /**
     * Sync with WooCommerce customers
     */
    public function sync_with_woocommerce($customer_id = null) {
        if (!class_exists('WooCommerce')) {
            return new WP_Error('woocommerce_not_active', __('WooCommerce is not active', 'zpos'));
        }
        
        global $wpdb;
        
        if ($customer_id) {
            // Sync single customer
            $customer = $this->get_customer($customer_id);
            if (!$customer) {
                return new WP_Error('customer_not_found', __('Customer not found', 'zpos'));
            }
            
            return $this->sync_single_customer_to_woocommerce($customer);
        } else {
            // Sync all customers
            $customers = $wpdb->get_results("SELECT * FROM $this->table_name WHERE status = 'active'");
            $synced = 0;
            $errors = array();
            
            foreach ($customers as $customer) {
                $result = $this->sync_single_customer_to_woocommerce($customer);
                if (is_wp_error($result)) {
                    $errors[] = $result->get_error_message();
                } else {
                    $synced++;
                }
            }
            
            return array(
                'synced' => $synced,
                'errors' => $errors
            );
        }
    }
    
    /**
     * Sync single customer to WooCommerce
     */
    private function sync_single_customer_to_woocommerce($customer) {
        if (empty($customer->email)) {
            return new WP_Error('no_email', __('Customer email is required for WooCommerce sync', 'zpos'));
        }
        
        // Check if WooCommerce customer exists
        $wc_customer = get_user_by('email', $customer->email);
        
        if ($wc_customer) {
            // Update existing customer
            $user_id = wp_update_user(array(
                'ID' => $wc_customer->ID,
                'first_name' => $customer->first_name,
                'last_name' => $customer->last_name
            ));
            
            if (is_wp_error($user_id)) {
                return $user_id;
            }
            
            // Update meta data
            update_user_meta($wc_customer->ID, 'billing_first_name', $customer->first_name);
            update_user_meta($wc_customer->ID, 'billing_last_name', $customer->last_name);
            update_user_meta($wc_customer->ID, 'billing_phone', $customer->phone);
            update_user_meta($wc_customer->ID, 'billing_address_1', $customer->address_line_1);
            update_user_meta($wc_customer->ID, 'billing_address_2', $customer->address_line_2);
            update_user_meta($wc_customer->ID, 'billing_city', $customer->city);
            update_user_meta($wc_customer->ID, 'billing_state', $customer->state);
            update_user_meta($wc_customer->ID, 'billing_postcode', $customer->postal_code);
            update_user_meta($wc_customer->ID, 'billing_country', $customer->country);
            
            // Update ZPOS customer with WordPress user ID
            global $wpdb;
            $wpdb->update(
                $this->table_name,
                array('wordpress_user_id' => $wc_customer->ID),
                array('id' => $customer->id),
                array('%d'),
                array('%d')
            );
            
            return $wc_customer->ID;
        } else {
            // Create new customer
            $user_id = wp_create_user(
                $customer->email,
                wp_generate_password(),
                $customer->email
            );
            
            if (is_wp_error($user_id)) {
                return $user_id;
            }
            
            // Update user data
            wp_update_user(array(
                'ID' => $user_id,
                'first_name' => $customer->first_name,
                'last_name' => $customer->last_name,
                'role' => 'customer'
            ));
            
            // Add meta data
            update_user_meta($user_id, 'billing_first_name', $customer->first_name);
            update_user_meta($user_id, 'billing_last_name', $customer->last_name);
            update_user_meta($user_id, 'billing_phone', $customer->phone);
            update_user_meta($user_id, 'billing_address_1', $customer->address_line_1);
            update_user_meta($user_id, 'billing_address_2', $customer->address_line_2);
            update_user_meta($user_id, 'billing_city', $customer->city);
            update_user_meta($user_id, 'billing_state', $customer->state);
            update_user_meta($user_id, 'billing_postcode', $customer->postal_code);
            update_user_meta($user_id, 'billing_country', $customer->country);
            
            // Update ZPOS customer with WordPress user ID
            global $wpdb;
            $wpdb->update(
                $this->table_name,
                array('wordpress_user_id' => $user_id),
                array('id' => $customer->id),
                array('%d'),
                array('%d')
            );
            
            return $user_id;
        }
    }
    
    /**
     * Import customers from WooCommerce
     */
    public function import_from_woocommerce() {
        if (!class_exists('WooCommerce')) {
            return new WP_Error('woocommerce_not_active', __('WooCommerce is not active', 'zpos'));
        }
        
        $users = get_users(array(
            'role' => 'customer',
            'meta_key' => 'billing_first_name',
            'meta_value' => '',
            'meta_compare' => '!='
        ));
        
        $imported = 0;
        $errors = array();
        
        foreach ($users as $user) {
            // Check if already imported
            global $wpdb;
            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $this->table_name WHERE wordpress_user_id = %d",
                $user->ID
            ));
            
            if ($existing) {
                continue;
            }
            
            // Import customer data
            $customer_data = array(
                'first_name' => get_user_meta($user->ID, 'billing_first_name', true) ?: $user->first_name,
                'last_name' => get_user_meta($user->ID, 'billing_last_name', true) ?: $user->last_name,
                'email' => $user->user_email,
                'phone' => get_user_meta($user->ID, 'billing_phone', true),
                'address_line_1' => get_user_meta($user->ID, 'billing_address_1', true),
                'address_line_2' => get_user_meta($user->ID, 'billing_address_2', true),
                'city' => get_user_meta($user->ID, 'billing_city', true),
                'state' => get_user_meta($user->ID, 'billing_state', true),
                'postal_code' => get_user_meta($user->ID, 'billing_postcode', true),
                'country' => get_user_meta($user->ID, 'billing_country', true) ?: 'VN',
                'wordpress_user_id' => $user->ID
            );
            
            $result = $this->save_customer($customer_data);
            if (is_wp_error($result)) {
                $errors[] = $result->get_error_message();
            } else {
                $imported++;
                
                // Update customer stats
                $this->update_customer_stats($result);
            }
        }
        
        return array(
            'imported' => $imported,
            'errors' => $errors
        );
    }
    
    /**
     * Export customers to CSV
     */
    public function export_to_csv($args = array()) {
        $customers_data = $this->get_customers($args);
        $customers = $customers_data['customers'];
        
        if (empty($customers)) {
            return new WP_Error('no_customers', __('No customers found to export', 'zpos'));
        }
        
        $filename = 'zpos-customers-' . date('Y-m-d-H-i-s') . '.csv';
        $upload_dir = wp_upload_dir();
        $file_path = $upload_dir['path'] . '/' . $filename;
        
        $file = fopen($file_path, 'w');
        
        // Headers
        $headers = array(
            'ID', 'First Name', 'Last Name', 'Email', 'Phone', 'Address Line 1', 'Address Line 2',
            'City', 'State', 'Postal Code', 'Country', 'Date of Birth', 'Gender', 'Customer Group',
            'Discount %', 'Credit Limit', 'Total Spent', 'Orders Count', 'Last Order Date',
            'Status', 'Loyalty Points', 'Created At'
        );
        fputcsv($file, $headers);
        
        // Data
        foreach ($customers as $customer) {
            $row = array(
                $customer->id,
                $customer->first_name,
                $customer->last_name,
                $customer->email,
                $customer->phone,
                $customer->address_line_1,
                $customer->address_line_2,
                $customer->city,
                $customer->state,
                $customer->postal_code,
                $customer->country,
                $customer->date_of_birth,
                $customer->gender,
                $customer->customer_group,
                $customer->discount_percent,
                $customer->credit_limit,
                $customer->total_spent,
                $customer->orders_count,
                $customer->last_order_date,
                $customer->status,
                $customer->loyalty_points,
                $customer->created_at
            );
            fputcsv($file, $row);
        }
        
        fclose($file);
        
        return array(
            'file_path' => $file_path,
            'file_url' => $upload_dir['url'] . '/' . $filename,
            'filename' => $filename
        );
    }
    
    /**
     * Generate unique referral code
     */
    private function generate_referral_code() {
        global $wpdb;
        
        do {
            $code = 'REF' . strtoupper(wp_generate_password(6, false));
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $this->table_name WHERE referral_code = %s",
                $code
            ));
        } while ($exists);
        
        return $code;
    }
    
    /**
     * Get customer groups
     */
    public function get_customer_groups() {
        global $wpdb;
        
        $results = $wpdb->get_results("SELECT DISTINCT customer_group FROM $this->table_name ORDER BY customer_group ASC");
        $groups = array();
        
        foreach ($results as $result) {
            if (!empty($result->customer_group)) {
                $groups[] = $result->customer_group;
            }
        }
          // Add default groups if not exists
        $default_groups = array('general', 'vip', 'wholesale', 'retail');
        foreach ($default_groups as $group) {
            if (!in_array($group, $groups)) {
                $groups[] = $group;
            }
        }
        
        return $groups;
    }

    /**
     * Export customers
     */
    public function export_customers($format = 'csv', $group = '') {
        $args = array();
        if (!empty($group)) {
            $args['customer_group'] = $group;
        }

        $customers = $this->get_customers($args);

        if ($format === 'csv') {
            return $this->export_to_csv(array('customers' => $customers));
        }

        return false;
    }
}
