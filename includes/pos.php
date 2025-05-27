<?php
/**
 * Point of Sale functionality
 *
 * @link       https://yourwebsite.com
 * @since      1.0.0
 *
 * @package    ZPOS
 * @subpackage ZPOS/includes
 */

/**
 * The ZPOS POS class.
 *
 * This class handles all Point of Sale functionality including cart management,
 * product search, customer management, order creation, and invoice printing.
 *
 * @since      1.0.0
 * @package    ZPOS
 * @subpackage ZPOS/includes
 * @author     Your Name <your.email@example.com>
 */
class ZPOS_POS {

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     */
    public function __construct() {
        // AJAX handlers for POS operations
        add_action('wp_ajax_zpos_search_products', array($this, 'ajax_search_products'));
        add_action('wp_ajax_zpos_get_product_details', array($this, 'ajax_get_product_details'));
        add_action('wp_ajax_zpos_search_customers', array($this, 'ajax_search_customers'));
        add_action('wp_ajax_zpos_create_customer', array($this, 'ajax_create_customer'));
        add_action('wp_ajax_zpos_calculate_cart', array($this, 'ajax_calculate_cart'));
        add_action('wp_ajax_zpos_create_order', array($this, 'ajax_create_order'));
        add_action('wp_ajax_zpos_get_order_receipt', array($this, 'ajax_get_order_receipt'));
        add_action('wp_ajax_zpos_void_order', array($this, 'ajax_void_order'));
        add_action('wp_ajax_zpos_hold_order', array($this, 'ajax_hold_order'));
        add_action('wp_ajax_zpos_get_held_orders', array($this, 'ajax_get_held_orders'));
        add_action('wp_ajax_zpos_recall_held_order', array($this, 'ajax_recall_held_order'));
    }

    /**
     * Search products for POS
     *
     * @since    1.0.0
     */
    public function ajax_search_products() {
        check_ajax_referer('zpos_admin_nonce', 'nonce');
          if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', 'zpos'));
        }

        $search = sanitize_text_field($_POST['search']);
        $category = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;
        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 20;
        $offset = ($page - 1) * $limit;
        
        global $wpdb;
        
        $table_products = $wpdb->prefix . 'zpos_products';
        
        $where = "WHERE status = 'active'";
        $params = array();
        
        if (!empty($search)) {
            $where .= " AND (name LIKE %s OR sku LIKE %s OR barcode LIKE %s)";
            $params[] = '%' . $wpdb->esc_like($search) . '%';
            $params[] = '%' . $wpdb->esc_like($search) . '%';            $params[] = '%' . $wpdb->esc_like($search) . '%';
        }
        
        if ($category > 0) {
            $where .= " AND category_id = %d";
            $params[] = $category;
        }
        
        $sql = "SELECT id, name, sku, barcode, price, sale_price, stock_quantity, manage_stock, image_url 
                FROM {$table_products} 
                {$where} 
                ORDER BY name ASC 
                LIMIT %d OFFSET %d";        $params[] = $limit;
        $params[] = $offset;
        
        $products = $wpdb->get_results($wpdb->prepare($sql, $params));
        
        // Check for database errors
        if ($wpdb->last_error) {
            wp_send_json_error(array(
                'message' => 'Database error: ' . $wpdb->last_error,
                'sql' => $sql,
                'params' => $params
            ));
        }
        
        // Format products for POS display
        $formatted_products = array();
        if ($products) {
            foreach ($products as $product) {
                $formatted_products[] = array(
                    'id' => $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'barcode' => $product->barcode,
                    'price' => floatval($product->sale_price ?: $product->price),
                    'regular_price' => floatval($product->price),
                    'stock_quantity' => intval($product->stock_quantity),
                    'manage_stock' => $product->manage_stock === 'yes',
                    'in_stock' => $product->manage_stock === 'yes' ? ($product->stock_quantity > 0) : true,
                    'image_url' => $product->image_url
                );
            }
        }
        
        wp_send_json_success(array(
            'products' => $formatted_products,
            'total' => count($formatted_products),
            'search' => $search,
            'category' => $category,
            'page' => $page
        ));
    }

    /**
     * Get detailed product information
     *
     * @since    1.0.0
     */
    public function ajax_get_product_details() {
        check_ajax_referer('zpos_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', 'zpos'));
        }

        $product_id = intval($_POST['product_id']);
        
        global $wpdb;
        $table_products = $wpdb->prefix . 'zpos_products';
        
        $product = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table_products} WHERE id = %d AND status = 'publish'",
            $product_id
        ));
        
        if (!$product) {
            wp_send_json_error(__('Product not found', 'zpos'));
        }
        
        $product_data = array(
            'id' => $product->id,
            'name' => $product->name,
            'sku' => $product->sku,
            'barcode' => $product->barcode,
            'description' => $product->description,
            'short_description' => $product->short_description,
            'price' => floatval($product->price),
            'sale_price' => floatval($product->sale_price),
            'cost_price' => floatval($product->cost_price),
            'stock_quantity' => intval($product->stock_quantity),
            'manage_stock' => $product->manage_stock === 'yes',
            'stock_status' => $product->stock_status,
            'weight' => $product->weight,
            'tax_status' => $product->tax_status,
            'tax_class' => $product->tax_class,
            'image_url' => $product->image_url,
            'gallery_images' => json_decode($product->gallery_images, true),
            'attributes' => json_decode($product->attributes, true)
        );
        
        wp_send_json_success($product_data);
    }

    /**
     * Search customers for POS
     *
     * @since    1.0.0
     */
    public function ajax_search_customers() {
        check_ajax_referer('zpos_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', 'zpos'));
        }

        $search = sanitize_text_field($_POST['search']);
        $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 10;
        
        global $wpdb;
        $table_customers = $wpdb->prefix . 'zpos_customers';
        
        $where = "WHERE 1=1";
        $params = array();
        
        if (!empty($search)) {
            $where .= " AND (name LIKE %s OR email LIKE %s OR phone LIKE %s)";
            $params[] = '%' . $wpdb->esc_like($search) . '%';
            $params[] = '%' . $wpdb->esc_like($search) . '%';
            $params[] = '%' . $wpdb->esc_like($search) . '%';
        }
        
        $sql = "SELECT id, name, email, phone, address_line_1, address_line_2, city, state, postal_code, country, total_spent, order_count 
                FROM {$table_customers} 
                {$where} 
                ORDER BY name ASC 
                LIMIT %d";
        $params[] = $limit;
        
        $customers = $wpdb->get_results($wpdb->prepare($sql, $params));
        
        wp_send_json_success(array(
            'customers' => $customers,
            'total' => count($customers)
        ));
    }

    /**
     * Create new customer from POS
     *
     * @since    1.0.0
     */
    public function ajax_create_customer() {
        check_ajax_referer('zpos_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', 'zpos'));
        }

        $customer_data = array(
            'name' => sanitize_text_field($_POST['name']),
            'email' => sanitize_email($_POST['email']),
            'phone' => sanitize_text_field($_POST['phone']),
            'address_line_1' => sanitize_text_field($_POST['address_line_1']),
            'address_line_2' => sanitize_text_field($_POST['address_line_2']),
            'city' => sanitize_text_field($_POST['city']),
            'state' => sanitize_text_field($_POST['state']),
            'postal_code' => sanitize_text_field($_POST['postal_code']),
            'country' => sanitize_text_field($_POST['country'])
        );
        
        // Validate required fields
        if (empty($customer_data['name'])) {
            wp_send_json_error(__('Customer name is required', 'zpos'));
        }
        
        // Check for duplicate email
        if (!empty($customer_data['email'])) {
            global $wpdb;
            $table_customers = $wpdb->prefix . 'zpos_customers';
            
            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$table_customers} WHERE email = %s",
                $customer_data['email']
            ));
            
            if ($existing) {
                wp_send_json_error(__('Customer with this email already exists', 'zpos'));
            }
        }
        
        // Create customer
        require_once ZPOS_PLUGIN_DIR . 'includes/customers.php';
        $customers = new ZPOS_Customers();
        $customer_id = $customers->create_customer($customer_data);
        
        if ($customer_id) {
            $customer = $customers->get_customer($customer_id);
            wp_send_json_success(array(
                'customer' => $customer,
                'message' => __('Customer created successfully', 'zpos')
            ));
        } else {
            wp_send_json_error(__('Failed to create customer', 'zpos'));
        }
    }

    /**
     * Calculate cart totals with discounts and taxes
     *
     * @since    1.0.0
     */
    public function ajax_calculate_cart() {
        check_ajax_referer('zpos_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', 'zpos'));
        }

        $cart_items = isset($_POST['cart_items']) ? json_decode(stripslashes($_POST['cart_items']), true) : array();
        $discount_type = sanitize_text_field($_POST['discount_type'] ?? '');
        $discount_value = floatval($_POST['discount_value'] ?? 0);
        $customer_id = intval($_POST['customer_id'] ?? 0);
        
        $calculations = $this->calculate_order_totals($cart_items, $discount_type, $discount_value, $customer_id);
        
        wp_send_json_success($calculations);
    }

    /**
     * Create order from POS
     *
     * @since    1.0.0
     */
    public function ajax_create_order() {
        check_ajax_referer('zpos_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', 'zpos'));
        }

        $cart_items = isset($_POST['cart_items']) ? json_decode(stripslashes($_POST['cart_items']), true) : array();
        $customer_id = intval($_POST['customer_id']);
        $payment_method = sanitize_text_field($_POST['payment_method']);
        $discount_type = sanitize_text_field($_POST['discount_type'] ?? '');
        $discount_value = floatval($_POST['discount_value'] ?? 0);
        $notes = sanitize_textarea_field($_POST['notes'] ?? '');
        
        if (empty($cart_items)) {
            wp_send_json_error(__('Cart is empty', 'zpos'));
        }
        
        // Calculate totals
        $calculations = $this->calculate_order_totals($cart_items, $discount_type, $discount_value, $customer_id);
        
        // Create order
        $order_id = $this->create_order($cart_items, $customer_id, $payment_method, $calculations, $notes);
        
        if ($order_id) {
            // Update inventory
            $this->update_inventory_after_sale($cart_items, $order_id);
            
            wp_send_json_success(array(
                'order_id' => $order_id,
                'message' => __('Order created successfully', 'zpos')
            ));
        } else {
            wp_send_json_error(__('Failed to create order', 'zpos'));
        }
    }

    /**
     * Get order receipt for printing
     *
     * @since    1.0.0
     */
    public function ajax_get_order_receipt() {
        check_ajax_referer('zpos_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', 'zpos'));
        }

        $order_id = intval($_POST['order_id']);
        
        $receipt_html = $this->generate_receipt_html($order_id);
        
        if ($receipt_html) {
            wp_send_json_success(array('receipt_html' => $receipt_html));
        } else {
            wp_send_json_error(__('Order not found', 'zpos'));
        }
    }

    /**
     * Void an order
     *
     * @since    1.0.0
     */
    public function ajax_void_order() {
        check_ajax_referer('zpos_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', 'zpos'));
        }

        $order_id = intval($_POST['order_id']);
        $reason = sanitize_textarea_field($_POST['reason'] ?? '');
        
        global $wpdb;
        $table_orders = $wpdb->prefix . 'zpos_orders';
        
        // Get order details before voiding
        $order = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table_orders} WHERE id = %d",
            $order_id
        ));
        
        if (!$order) {
            wp_send_json_error(__('Order not found', 'zpos'));
        }
        
        if ($order->status === 'voided') {
            wp_send_json_error(__('Order is already voided', 'zpos'));
        }
        
        // Update order status
        $updated = $wpdb->update(
            $table_orders,
            array(
                'status' => 'voided',
                'notes' => $order->notes . "\n\nVoided: " . $reason,
                'updated_at' => current_time('mysql')
            ),
            array('id' => $order_id),
            array('%s', '%s', '%s'),
            array('%d')
        );
        
        if ($updated !== false) {
            // Restore inventory
            $this->restore_inventory_after_void($order_id);
            
            wp_send_json_success(array('message' => __('Order voided successfully', 'zpos')));
        } else {
            wp_send_json_error(__('Failed to void order', 'zpos'));
        }
    }

    /**
     * Hold an order for later
     *
     * @since    1.0.0
     */
    public function ajax_hold_order() {
        check_ajax_referer('zpos_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', 'zpos'));
        }

        $cart_items = isset($_POST['cart_items']) ? json_decode(stripslashes($_POST['cart_items']), true) : array();
        $customer_id = intval($_POST['customer_id'] ?? 0);
        $discount_type = sanitize_text_field($_POST['discount_type'] ?? '');
        $discount_value = floatval($_POST['discount_value'] ?? 0);
        $hold_name = sanitize_text_field($_POST['hold_name'] ?? '');
        
        if (empty($cart_items)) {
            wp_send_json_error(__('Cart is empty', 'zpos'));
        }
        
        if (empty($hold_name)) {
            $hold_name = 'Hold ' . date('Y-m-d H:i');
        }
        
        global $wpdb;
        $table_orders = $wpdb->prefix . 'zpos_orders';
        
        // Calculate totals
        $calculations = $this->calculate_order_totals($cart_items, $discount_type, $discount_value, $customer_id);
        
        // Create held order
        $result = $wpdb->insert(
            $table_orders,
            array(
                'customer_id' => $customer_id,
                'order_number' => 'HOLD-' . time(),
                'status' => 'held',
                'subtotal' => $calculations['subtotal'],
                'discount_amount' => $calculations['discount_amount'],
                'tax_amount' => $calculations['tax_amount'],
                'total_amount' => $calculations['total'],
                'payment_method' => '',
                'currency' => get_option('zpos_currency', 'USD'),
                'notes' => $hold_name,
                'created_by' => get_current_user_id(),
                'created_at' => current_time('mysql')
            ),
            array('%d', '%s', '%s', '%f', '%f', '%f', '%f', '%s', '%s', '%s', '%d', '%s')
        );
        
        if ($result) {
            $hold_id = $wpdb->insert_id;
            
            // Save cart items
            $this->save_order_items($hold_id, $cart_items);
            
            wp_send_json_success(array(
                'hold_id' => $hold_id,
                'message' => __('Order held successfully', 'zpos')
            ));
        } else {
            wp_send_json_error(__('Failed to hold order', 'zpos'));
        }
    }

    /**
     * Get list of held orders
     *
     * @since    1.0.0
     */
    public function ajax_get_held_orders() {
        check_ajax_referer('zpos_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', 'zpos'));
        }

        global $wpdb;
        $table_orders = $wpdb->prefix . 'zpos_orders';
        $table_customers = $wpdb->prefix . 'zpos_customers';
        
        $held_orders = $wpdb->get_results("
            SELECT o.*, c.name as customer_name 
            FROM {$table_orders} o
            LEFT JOIN {$table_customers} c ON o.customer_id = c.id
            WHERE o.status = 'held'
            ORDER BY o.created_at DESC
        ");
        
        wp_send_json_success(array('held_orders' => $held_orders));
    }

    /**
     * Recall a held order back to cart
     *
     * @since    1.0.0
     */
    public function ajax_recall_held_order() {
        check_ajax_referer('zpos_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', 'zpos'));
        }

        $hold_id = intval($_POST['hold_id']);
        
        global $wpdb;
        $table_orders = $wpdb->prefix . 'zpos_orders';
        $table_order_items = $wpdb->prefix . 'zpos_order_items';
        $table_products = $wpdb->prefix . 'zpos_products';
        
        // Get held order
        $order = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table_orders} WHERE id = %d AND status = 'held'",
            $hold_id
        ));
        
        if (!$order) {
            wp_send_json_error(__('Held order not found', 'zpos'));
        }
        
        // Get order items with product details
        $order_items = $wpdb->get_results($wpdb->prepare("
            SELECT oi.*, p.name, p.sku, p.image_url, p.stock_quantity, p.manage_stock
            FROM {$table_order_items} oi
            LEFT JOIN {$table_products} p ON oi.product_id = p.id
            WHERE oi.order_id = %d
        ", $hold_id));
        
        // Format for cart
        $cart_items = array();
        foreach ($order_items as $item) {
            $cart_items[] = array(
                'product_id' => $item->product_id,
                'name' => $item->name,
                'sku' => $item->sku,
                'price' => floatval($item->price),
                'quantity' => intval($item->quantity),
                'image_url' => $item->image_url,
                'stock_quantity' => $item->stock_quantity,
                'manage_stock' => $item->manage_stock
            );
        }
        
        // Delete held order
        $wpdb->delete($table_order_items, array('order_id' => $hold_id), array('%d'));
        $wpdb->delete($table_orders, array('id' => $hold_id), array('%d'));
        
        wp_send_json_success(array(
            'cart_items' => $cart_items,
            'customer_id' => $order->customer_id,
            'message' => __('Order recalled successfully', 'zpos')
        ));
    }

    /**
     * Calculate order totals
     *
     * @since    1.0.0
     * @param    array    $cart_items
     * @param    string   $discount_type
     * @param    float    $discount_value
     * @param    int      $customer_id
     * @return   array
     */
    private function calculate_order_totals($cart_items, $discount_type = '', $discount_value = 0, $customer_id = 0) {
        $subtotal = 0;
        $discount_amount = 0;
        $tax_amount = 0;
        
        // Calculate subtotal
        foreach ($cart_items as $item) {
            $subtotal += floatval($item['price']) * intval($item['quantity']);
        }
        
        // Calculate discount
        if ($discount_value > 0) {
            if ($discount_type === 'percentage') {
                $discount_amount = ($subtotal * $discount_value) / 100;
            } else {
                $discount_amount = $discount_value;
            }
            $discount_amount = min($discount_amount, $subtotal);
        }
        
        // Calculate tax (after discount)
        $taxable_amount = $subtotal - $discount_amount;
        $tax_rate = floatval(get_option('zpos_tax_rate', 0));
        if ($tax_rate > 0) {
            $tax_amount = ($taxable_amount * $tax_rate) / 100;
        }
        
        $total = $subtotal - $discount_amount + $tax_amount;
        
        return array(
            'subtotal' => round($subtotal, 2),
            'discount_amount' => round($discount_amount, 2),
            'tax_amount' => round($tax_amount, 2),
            'total' => round($total, 2)
        );
    }

    /**
     * Create order in database
     *
     * @since    1.0.0
     * @param    array    $cart_items
     * @param    int      $customer_id
     * @param    string   $payment_method
     * @param    array    $calculations
     * @param    string   $notes
     * @return   int|false
     */
    private function create_order($cart_items, $customer_id, $payment_method, $calculations, $notes = '') {
        global $wpdb;
        $table_orders = $wpdb->prefix . 'zpos_orders';
        
        // Generate order number
        $order_number = 'POS-' . date('Ymd') . '-' . str_pad(wp_rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        // Insert order
        $result = $wpdb->insert(
            $table_orders,
            array(
                'customer_id' => $customer_id,
                'order_number' => $order_number,
                'status' => 'completed',
                'subtotal' => $calculations['subtotal'],
                'discount_amount' => $calculations['discount_amount'],
                'tax_amount' => $calculations['tax_amount'],
                'total_amount' => $calculations['total'],
                'payment_method' => $payment_method,
                'currency' => get_option('zpos_currency', 'USD'),
                'notes' => $notes,
                'created_by' => get_current_user_id(),
                'created_at' => current_time('mysql')
            ),
            array('%d', '%s', '%s', '%f', '%f', '%f', '%f', '%s', '%s', '%s', '%d', '%s')
        );
        
        if ($result) {
            $order_id = $wpdb->insert_id;
            
            // Save order items
            $this->save_order_items($order_id, $cart_items);
            
            // Update customer stats
            $this->update_customer_stats($customer_id, $calculations['total']);
            
            return $order_id;
        }
        
        return false;
    }

    /**
     * Save order items
     *
     * @since    1.0.0
     * @param    int      $order_id
     * @param    array    $cart_items
     */
    private function save_order_items($order_id, $cart_items) {
        global $wpdb;
        $table_order_items = $wpdb->prefix . 'zpos_order_items';
        
        foreach ($cart_items as $item) {
            $wpdb->insert(
                $table_order_items,
                array(
                    'order_id' => $order_id,
                    'product_id' => intval($item['product_id']),
                    'product_name' => sanitize_text_field($item['name']),
                    'product_sku' => sanitize_text_field($item['sku'] ?? ''),
                    'quantity' => intval($item['quantity']),
                    'price' => floatval($item['price']),
                    'total' => floatval($item['price']) * intval($item['quantity'])
                ),
                array('%d', '%d', '%s', '%s', '%d', '%f', '%f')
            );
        }
    }

    /**
     * Update inventory after sale
     *
     * @since    1.0.0
     * @param    array    $cart_items
     * @param    int      $order_id
     */
    private function update_inventory_after_sale($cart_items, $order_id) {
        global $wpdb;
        $table_products = $wpdb->prefix . 'zpos_products';
        $table_inventory = $wpdb->prefix . 'zpos_inventory';
        $table_movements = $wpdb->prefix . 'zpos_inventory_movements';
        
        foreach ($cart_items as $item) {
            $product_id = intval($item['product_id']);
            $quantity_sold = intval($item['quantity']);
            
            // Get current stock
            $current_stock = $wpdb->get_var($wpdb->prepare(
                "SELECT stock_quantity FROM {$table_products} WHERE id = %d AND manage_stock = 'yes'",
                $product_id
            ));
            
            if ($current_stock !== null) {
                $new_stock = max(0, intval($current_stock) - $quantity_sold);
                
                // Update product stock
                $wpdb->update(
                    $table_products,
                    array('stock_quantity' => $new_stock),
                    array('id' => $product_id),
                    array('%d'),
                    array('%d')
                );
                
                // Update inventory table if exists
                $inventory_exists = $wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM {$table_inventory} WHERE product_id = %d",
                    $product_id
                ));
                
                if ($inventory_exists) {
                    $wpdb->update(
                        $table_inventory,
                        array('current_stock' => $new_stock),
                        array('product_id' => $product_id),
                        array('%d'),
                        array('%d')
                    );
                }
                
                // Record movement
                if ($wpdb->get_var("SHOW TABLES LIKE '{$table_movements}'") === $table_movements) {
                    $wpdb->insert(
                        $table_movements,
                        array(
                            'product_id' => $product_id,
                            'sku' => sanitize_text_field($item['sku'] ?? ''),
                            'type' => 'sale',
                            'quantity_change' => -$quantity_sold,
                            'stock_before' => intval($current_stock),
                            'stock_after' => $new_stock,
                            'reason' => 'POS Sale - Order #' . $order_id,
                            'user_id' => get_current_user_id(),
                            'created_at' => current_time('mysql')
                        ),
                        array('%d', '%s', '%s', '%d', '%d', '%d', '%s', '%d', '%s')
                    );
                }
            }
        }
    }

    /**
     * Update customer statistics
     *
     * @since    1.0.0
     * @param    int      $customer_id
     * @param    float    $order_total
     */
    private function update_customer_stats($customer_id, $order_total) {
        if ($customer_id <= 0) return;
        
        global $wpdb;
        $table_customers = $wpdb->prefix . 'zpos_customers';
        
        $customer = $wpdb->get_row($wpdb->prepare(
            "SELECT total_spent, order_count FROM {$table_customers} WHERE id = %d",
            $customer_id
        ));
        
        if ($customer) {
            $wpdb->update(
                $table_customers,
                array(
                    'total_spent' => floatval($customer->total_spent) + $order_total,
                    'order_count' => intval($customer->order_count) + 1,
                    'last_order_date' => current_time('mysql'),
                    'updated_at' => current_time('mysql')
                ),
                array('id' => $customer_id),
                array('%f', '%d', '%s', '%s'),
                array('%d')
            );
        }
    }

    /**
     * Restore inventory after void
     *
     * @since    1.0.0
     * @param    int      $order_id
     */
    private function restore_inventory_after_void($order_id) {
        global $wpdb;
        $table_orders = $wpdb->prefix . 'zpos_orders';
        $table_order_items = $wpdb->prefix . 'zpos_order_items';
        $table_products = $wpdb->prefix . 'zpos_products';
        $table_inventory = $wpdb->prefix . 'zpos_inventory';
        $table_movements = $wpdb->prefix . 'zpos_inventory_movements';
        
        // Get order items
        $order_items = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table_order_items} WHERE order_id = %d",
            $order_id
        ));
        
        foreach ($order_items as $item) {
            $product_id = $item->product_id;
            $quantity_to_restore = $item->quantity;
            
            // Get current stock
            $current_stock = $wpdb->get_var($wpdb->prepare(
                "SELECT stock_quantity FROM {$table_products} WHERE id = %d AND manage_stock = 'yes'",
                $product_id
            ));
            
            if ($current_stock !== null) {
                $new_stock = intval($current_stock) + $quantity_to_restore;
                
                // Update product stock
                $wpdb->update(
                    $table_products,
                    array('stock_quantity' => $new_stock),
                    array('id' => $product_id),
                    array('%d'),
                    array('%d')
                );
                
                // Update inventory table if exists
                $inventory_exists = $wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM {$table_inventory} WHERE product_id = %d",
                    $product_id
                ));
                
                if ($inventory_exists) {
                    $wpdb->update(
                        $table_inventory,
                        array('current_stock' => $new_stock),
                        array('product_id' => $product_id),
                        array('%d'),
                        array('%d')
                    );
                }
                
                // Record movement
                if ($wpdb->get_var("SHOW TABLES LIKE '{$table_movements}'") === $table_movements) {
                    $wpdb->insert(
                        $table_movements,
                        array(
                            'product_id' => $product_id,
                            'sku' => $item->product_sku,
                            'type' => 'void_return',
                            'quantity_change' => $quantity_to_restore,
                            'stock_before' => intval($current_stock),
                            'stock_after' => $new_stock,
                            'reason' => 'Order Void - Order #' . $order_id,
                            'user_id' => get_current_user_id(),
                            'created_at' => current_time('mysql')
                        ),
                        array('%d', '%s', '%s', '%d', '%d', '%d', '%s', '%d', '%s')
                    );
                }
            }
        }
    }

    /**
     * Generate receipt HTML
     *
     * @since    1.0.0
     * @param    int      $order_id
     * @return   string|false
     */
    private function generate_receipt_html($order_id) {
        global $wpdb;
        $table_orders = $wpdb->prefix . 'zpos_orders';
        $table_order_items = $wpdb->prefix . 'zpos_order_items';
        $table_customers = $wpdb->prefix . 'zpos_customers';
        
        // Get order details
        $order = $wpdb->get_row($wpdb->prepare(
            "SELECT o.*, c.name as customer_name, c.email as customer_email, c.phone as customer_phone
             FROM {$table_orders} o
             LEFT JOIN {$table_customers} c ON o.customer_id = c.id
             WHERE o.id = %d",
            $order_id
        ));
        
        if (!$order) {
            return false;
        }
        
        // Get order items
        $order_items = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table_order_items} WHERE order_id = %d ORDER BY id",
            $order_id
        ));
        
        // Store settings
        $store_name = get_option('zpos_store_name', get_bloginfo('name'));
        $store_address = get_option('zpos_store_address', '');
        $store_phone = get_option('zpos_store_phone', '');
        $store_email = get_option('zpos_store_email', get_option('admin_email'));
        $currency_symbol = get_option('zpos_currency_symbol', '$');
        
        ob_start();
        ?>
        <div class="zpos-receipt">
            <div class="receipt-header">
                <h2><?php echo esc_html($store_name); ?></h2>
                <?php if ($store_address): ?>
                    <p><?php echo esc_html($store_address); ?></p>
                <?php endif; ?>
                <?php if ($store_phone): ?>
                    <p><?php _e('Phone:', 'zpos'); ?> <?php echo esc_html($store_phone); ?></p>
                <?php endif; ?>
                <?php if ($store_email): ?>
                    <p><?php _e('Email:', 'zpos'); ?> <?php echo esc_html($store_email); ?></p>
                <?php endif; ?>
            </div>
            
            <div class="receipt-separator"></div>
            
            <div class="receipt-order-info">
                <p><strong><?php _e('Order #:', 'zpos'); ?></strong> <?php echo esc_html($order->order_number); ?></p>
                <p><strong><?php _e('Date:', 'zpos'); ?></strong> <?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($order->created_at)); ?></p>
                <?php if ($order->customer_name): ?>
                    <p><strong><?php _e('Customer:', 'zpos'); ?></strong> <?php echo esc_html($order->customer_name); ?></p>
                <?php endif; ?>
                <?php if ($order->customer_phone): ?>
                    <p><strong><?php _e('Phone:', 'zpos'); ?></strong> <?php echo esc_html($order->customer_phone); ?></p>
                <?php endif; ?>
            </div>
            
            <div class="receipt-separator"></div>
            
            <div class="receipt-items">
                <table>
                    <thead>
                        <tr>
                            <th><?php _e('Item', 'zpos'); ?></th>
                            <th><?php _e('Qty', 'zpos'); ?></th>
                            <th><?php _e('Price', 'zpos'); ?></th>
                            <th><?php _e('Total', 'zpos'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($order_items as $item): ?>
                            <tr>
                                <td>
                                    <?php echo esc_html($item->product_name); ?>
                                    <?php if ($item->product_sku): ?>
                                        <br><small><?php echo esc_html($item->product_sku); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo intval($item->quantity); ?></td>
                                <td><?php echo $currency_symbol . number_format($item->price, 2); ?></td>
                                <td><?php echo $currency_symbol . number_format($item->total, 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="receipt-separator"></div>
            
            <div class="receipt-totals">
                <p><strong><?php _e('Subtotal:', 'zpos'); ?></strong> <span><?php echo $currency_symbol . number_format($order->subtotal, 2); ?></span></p>
                <?php if ($order->discount_amount > 0): ?>
                    <p><strong><?php _e('Discount:', 'zpos'); ?></strong> <span>-<?php echo $currency_symbol . number_format($order->discount_amount, 2); ?></span></p>
                <?php endif; ?>
                <?php if ($order->tax_amount > 0): ?>
                    <p><strong><?php _e('Tax:', 'zpos'); ?></strong> <span><?php echo $currency_symbol . number_format($order->tax_amount, 2); ?></span></p>
                <?php endif; ?>
                <p class="total-line"><strong><?php _e('Total:', 'zpos'); ?></strong> <span><strong><?php echo $currency_symbol . number_format($order->total_amount, 2); ?></strong></span></p>
                <p><strong><?php _e('Payment Method:', 'zpos'); ?></strong> <?php echo esc_html(ucfirst($order->payment_method)); ?></p>
            </div>
            
            <div class="receipt-separator"></div>
            
            <div class="receipt-footer">
                <p><?php _e('Thank you for your business!', 'zpos'); ?></p>
                <?php if ($order->notes): ?>
                    <p><em><?php echo esc_html($order->notes); ?></em></p>
                <?php endif; ?>
            </div>
        </div>
        
        <style>
            .zpos-receipt {
                font-family: 'Courier New', monospace;
                width: 80mm;
                margin: 0 auto;
                background: white;
                padding: 20px;
                line-height: 1.4;
            }
            .receipt-header {
                text-align: center;
                margin-bottom: 15px;
            }
            .receipt-header h2 {
                margin: 0 0 5px 0;
                font-size: 18px;
            }
            .receipt-header p {
                margin: 2px 0;
                font-size: 12px;
            }
            .receipt-separator {
                border-bottom: 1px dashed #333;
                margin: 10px 0;
            }
            .receipt-order-info p {
                margin: 3px 0;
                font-size: 12px;
            }
            .receipt-items table {
                width: 100%;
                border-collapse: collapse;
                font-size: 11px;
            }
            .receipt-items th,
            .receipt-items td {
                text-align: left;
                padding: 2px 0;
                vertical-align: top;
            }
            .receipt-items th:last-child,
            .receipt-items td:last-child {
                text-align: right;
            }
            .receipt-totals {
                font-size: 12px;
            }
            .receipt-totals p {
                display: flex;
                justify-content: space-between;
                margin: 3px 0;
            }
            .total-line {
                border-top: 1px solid #333;
                padding-top: 5px;
                margin-top: 5px;
            }
            .receipt-footer {
                text-align: center;
                font-size: 12px;
                margin-top: 15px;
            }
            @media print {
                .zpos-receipt {
                    width: auto;
                    margin: 0;
                    padding: 0;
                }
            }
        </style>
        <?php
        
        return ob_get_clean();
    }
}
