<?php
/**
 * Order Management functionality
 *
 * @link       https://yourwebsite.com
 * @since      1.0.0
 *
 * @package    ZPOS
 * @subpackage ZPOS/includes
 */

/**
 * The ZPOS Orders class.
 *
 * This class handles all order management functionality including listing,
 * filtering, search, WooCommerce sync, and export.
 *
 * @since      1.0.0
 * @package    ZPOS
 * @subpackage ZPOS/includes
 * @author     Your Name <your.email@example.com>
 */
class ZPOS_Orders {

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     */
    public function __construct() {
        add_action('wp_ajax_zpos_get_orders', array($this, 'ajax_get_orders'));
        add_action('wp_ajax_zpos_update_order_status', array($this, 'ajax_update_order_status'));
        add_action('wp_ajax_zpos_bulk_update_order_status', array($this, 'ajax_bulk_update_order_status'));
        add_action('wp_ajax_zpos_sync_woocommerce_orders', array($this, 'ajax_sync_woocommerce_orders'));
        add_action('wp_ajax_zpos_export_orders', array($this, 'ajax_export_orders'));
        add_action('wp_ajax_zpos_get_order_details', array($this, 'ajax_get_order_details'));
    }

    /**
     * Get orders with filtering and pagination
     *
     * @since    1.0.0
     * @param    array    $args    Query arguments
     * @return   array    Array of orders
     */
    public function get_orders($args = array()) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'zpos_orders';
        
        $defaults = array(
            'status' => '',
            'date_from' => '',
            'date_to' => '',
            'search' => '',
            'customer_id' => '',
            'per_page' => 20,
            'page' => 1,
            'orderby' => 'created_at',
            'order' => 'DESC'
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $where_clauses = array('1=1');
        $where_values = array();
        
        // Status filter
        if (!empty($args['status'])) {
            $where_clauses[] = 'status = %s';
            $where_values[] = $args['status'];
        }
        
        // Date range filter
        if (!empty($args['date_from'])) {
            $where_clauses[] = 'DATE(created_at) >= %s';
            $where_values[] = $args['date_from'];
        }
        
        if (!empty($args['date_to'])) {
            $where_clauses[] = 'DATE(created_at) <= %s';
            $where_values[] = $args['date_to'];
        }
        
        // Search filter
        if (!empty($args['search'])) {
            $where_clauses[] = '(order_number LIKE %s OR customer_name LIKE %s OR customer_email LIKE %s)';
            $search_term = '%' . $wpdb->esc_like($args['search']) . '%';
            $where_values[] = $search_term;
            $where_values[] = $search_term;
            $where_values[] = $search_term;
        }
        
        // Customer filter
        if (!empty($args['customer_id'])) {
            $where_clauses[] = 'customer_id = %d';
            $where_values[] = $args['customer_id'];
        }
        
        $where_sql = implode(' AND ', $where_clauses);
        
        // Count total records
        $count_sql = "SELECT COUNT(*) FROM {$table_name} WHERE {$where_sql}";
        if (!empty($where_values)) {
            $count_sql = $wpdb->prepare($count_sql, $where_values);
        }
        $total_records = $wpdb->get_var($count_sql);
        
        // Get orders
        $offset = ($args['page'] - 1) * $args['per_page'];
        $orderby = sanitize_sql_orderby($args['orderby'] . ' ' . $args['order']);
        
        $sql = "SELECT * FROM {$table_name} WHERE {$where_sql} ORDER BY {$orderby} LIMIT %d OFFSET %d";
        $query_values = array_merge($where_values, array($args['per_page'], $offset));
        
        $orders = $wpdb->get_results($wpdb->prepare($sql, $query_values));
        
        return array(
            'orders' => $orders,
            'total' => $total_records,
            'pages' => ceil($total_records / $args['per_page']),
            'current_page' => $args['page']
        );
    }

    /**
     * Get order details by ID
     *
     * @since    1.0.0
     * @param    int    $order_id    Order ID
     * @return   object|null    Order object or null if not found
     */
    public function get_order($order_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'zpos_orders';
        $items_table = $wpdb->prefix . 'zpos_order_items';
        
        // Get order details
        $order = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE id = %d",
            $order_id
        ));
        
        if (!$order) {
            return null;
        }
        
        // Get order items
        $items = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$items_table} WHERE order_id = %d",
            $order_id
        ));
        
        $order->items = $items;
        
        return $order;
    }

    /**
     * Update order status
     *
     * @since    1.0.0
     * @param    int       $order_id    Order ID
     * @param    string    $status      New status
     * @return   bool      Success status
     */
    public function update_order_status($order_id, $status) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'zpos_orders';
        
        $valid_statuses = array('pending', 'processing', 'completed', 'cancelled', 'refunded');
        
        if (!in_array($status, $valid_statuses)) {
            return false;
        }
        
        $result = $wpdb->update(
            $table_name,
            array(
                'status' => $status,
                'updated_at' => current_time('mysql')
            ),
            array('id' => $order_id),
            array('%s', '%s'),
            array('%d')
        );
        
        if ($result !== false) {
            // Log status change
            $this->log_order_activity($order_id, 'status_changed', sprintf(
                __('Order status changed to %s', 'zpos'),
                $status
            ));
            
            return true;
        }
        
        return false;
    }

    /**
     * Sync orders from WooCommerce
     *
     * @since    1.0.0
     * @param    array    $args    Sync arguments
     * @return   array    Sync results
     */
    public function sync_woocommerce_orders($args = array()) {
        if (!class_exists('WooCommerce')) {
            return array(
                'success' => false,
                'message' => __('WooCommerce is not active', 'zpos')
            );
        }
        
        $defaults = array(
            'date_from' => date('Y-m-d', strtotime('-7 days')),
            'date_to' => date('Y-m-d'),
            'status' => array('processing', 'completed')
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $wc_orders = wc_get_orders(array(
            'status' => $args['status'],
            'date_created' => $args['date_from'] . '...' . $args['date_to'],
            'limit' => -1
        ));
        
        $synced_count = 0;
        $errors = array();
        
        foreach ($wc_orders as $wc_order) {
            $result = $this->import_woocommerce_order($wc_order);
            if ($result) {
                $synced_count++;
            } else {
                $errors[] = sprintf(
                    __('Failed to sync order #%s', 'zpos'),
                    $wc_order->get_order_number()
                );
            }
        }
        
        return array(
            'success' => true,
            'synced' => $synced_count,
            'total' => count($wc_orders),
            'errors' => $errors
        );
    }

    /**
     * Import WooCommerce order
     *
     * @since    1.0.0
     * @param    WC_Order    $wc_order    WooCommerce order object
     * @return   bool        Success status
     */
    private function import_woocommerce_order($wc_order) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'zpos_orders';
        $items_table = $wpdb->prefix . 'zpos_order_items';
        
        // Check if order already exists
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$table_name} WHERE wc_order_id = %d",
            $wc_order->get_id()
        ));
        
        if ($existing) {
            return true; // Already synced
        }
        
        // Insert order
        $order_data = array(
            'wc_order_id' => $wc_order->get_id(),
            'order_number' => $wc_order->get_order_number(),
            'status' => $wc_order->get_status(),
            'customer_id' => $wc_order->get_customer_id(),
            'customer_name' => trim($wc_order->get_billing_first_name() . ' ' . $wc_order->get_billing_last_name()),
            'customer_email' => $wc_order->get_billing_email(),
            'customer_phone' => $wc_order->get_billing_phone(),
            'total_amount' => $wc_order->get_total(),
            'currency' => $wc_order->get_currency(),
            'payment_method' => $wc_order->get_payment_method_title(),
            'created_at' => $wc_order->get_date_created()->date('Y-m-d H:i:s'),
            'updated_at' => current_time('mysql')
        );
        
        $result = $wpdb->insert($table_name, $order_data);
        
        if ($result === false) {
            return false;
        }
        
        $order_id = $wpdb->insert_id;
        
        // Insert order items
        foreach ($wc_order->get_items() as $item) {
            $product = $item->get_product();
            $item_data = array(
                'order_id' => $order_id,
                'product_id' => $item->get_product_id(),
                'product_name' => $item->get_name(),
                'sku' => $product ? $product->get_sku() : '',
                'quantity' => $item->get_quantity(),
                'price' => $item->get_total() / $item->get_quantity(),
                'total' => $item->get_total()
            );
            
            $wpdb->insert($items_table, $item_data);
        }
        
        return true;
    }

    /**
     * Export orders to CSV
     *
     * @since    1.0.0
     * @param    array    $args    Export arguments
     * @return   string   File path or false on failure
     */
    public function export_orders($args = array()) {
        $orders_data = $this->get_orders($args);
        $orders = $orders_data['orders'];
        
        if (empty($orders)) {
            return false;
        }
        
        $upload_dir = wp_upload_dir();
        $filename = 'zpos-orders-export-' . date('Y-m-d-H-i-s') . '.csv';
        $filepath = $upload_dir['path'] . '/' . $filename;
        
        $file = fopen($filepath, 'w');
        
        // CSV headers
        $headers = array(
            'Order Number',
            'Status',
            'Customer Name',
            'Customer Email',
            'Total Amount',
            'Currency',
            'Payment Method',
            'Created Date'
        );
        
        fputcsv($file, $headers);
        
        // Export data
        foreach ($orders as $order) {
            $row = array(
                $order->order_number,
                ucfirst($order->status),
                $order->customer_name,
                $order->customer_email,
                $order->total_amount,
                $order->currency,
                $order->payment_method,
                $order->created_at
            );
            
            fputcsv($file, $row);
        }
        
        fclose($file);
        
        return array(
            'file_path' => $filepath,
            'file_url' => $upload_dir['url'] . '/' . $filename,
            'filename' => $filename
        );
    }

    /**
     * Log order activity
     *
     * @since    1.0.0
     * @param    int       $order_id    Order ID
     * @param    string    $action      Action type
     * @param    string    $message     Log message
     */
    private function log_order_activity($order_id, $action, $message) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'zpos_order_logs';
        
        $wpdb->insert(
            $table_name,
            array(
                'order_id' => $order_id,
                'action' => $action,
                'message' => $message,
                'user_id' => get_current_user_id(),
                'created_at' => current_time('mysql')
            )
        );
    }

    /**
     * AJAX handler for getting orders
     *
     * @since    1.0.0
     */
    public function ajax_get_orders() {
        check_ajax_referer('zpos_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'zpos'));
        }
        
        $args = array(
            'status' => sanitize_text_field($_POST['status'] ?? ''),
            'date_from' => sanitize_text_field($_POST['date_from'] ?? ''),
            'date_to' => sanitize_text_field($_POST['date_to'] ?? ''),
            'search' => sanitize_text_field($_POST['search'] ?? ''),
            'customer_id' => intval($_POST['customer_id'] ?? 0),
            'per_page' => intval($_POST['per_page'] ?? 20),
            'page' => intval($_POST['page'] ?? 1),
            'orderby' => sanitize_text_field($_POST['orderby'] ?? 'created_at'),
            'order' => sanitize_text_field($_POST['order'] ?? 'DESC')
        );
        
        $result = $this->get_orders($args);
        wp_send_json_success($result);
    }

    /**
     * AJAX handler for updating order status
     *
     * @since    1.0.0
     */
    public function ajax_update_order_status() {
        check_ajax_referer('zpos_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'zpos'));
        }
        
        $order_id = intval($_POST['order_id']);
        $status = sanitize_text_field($_POST['status']);
        
        $result = $this->update_order_status($order_id, $status);
        
        if ($result) {
            wp_send_json_success(__('Order status updated successfully', 'zpos'));
        } else {
            wp_send_json_error(__('Failed to update order status', 'zpos'));
        }
    }

    /**
     * AJAX handler for bulk updating order status
     *
     * @since    1.0.0
     */
    public function ajax_bulk_update_order_status() {
        check_ajax_referer('zpos_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'zpos'));
        }
        
        $order_ids = isset($_POST['order_ids']) ? array_map('intval', $_POST['order_ids']) : array();
        $status = sanitize_text_field($_POST['status']);
        
        if (empty($order_ids) || empty($status)) {
            wp_send_json_error(__('Invalid order IDs or status', 'zpos'));
        }
        
        $success_count = 0;
        $failure_count = 0;
        $errors = array();
        
        foreach ($order_ids as $order_id) {
            $result = $this->update_order_status($order_id, $status);
            if ($result) {
                $success_count++;
            } else {
                $failure_count++;
                $errors[] = sprintf(
                    __('Failed to update order #%d', 'zpos'),
                    $order_id
                );
            }
        }
        
        $message = sprintf(
            _n('%d order status updated successfully', '%d orders status updated successfully', $success_count, 'zpos'),
            $success_count
        );
        
        if ($failure_count > 0) {
            $message .= ' ' . sprintf(
                _n('And %d order status could not be updated', 'And %d orders status could not be updated', $failure_count, 'zpos'),
                $failure_count
            );
        }
        
        wp_send_json_success(array(
            'message' => $message,
            'errors' => $errors
        ));
    }

    /**
     * AJAX handler for syncing WooCommerce orders
     *
     * @since    1.0.0
     */
    public function ajax_sync_woocommerce_orders() {
        check_ajax_referer('zpos_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'zpos'));
        }
        
        $args = array(
            'date_from' => sanitize_text_field($_POST['date_from'] ?? ''),
            'date_to' => sanitize_text_field($_POST['date_to'] ?? ''),
            'status' => array_map('sanitize_text_field', $_POST['status'] ?? array('processing', 'completed'))
        );
        
        $result = $this->sync_woocommerce_orders($args);
        wp_send_json($result);
    }

    /**
     * AJAX handler for exporting orders
     *
     * @since    1.0.0
     */
    public function ajax_export_orders() {
        check_ajax_referer('zpos_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'zpos'));
        }
        
        $args = array(
            'status' => sanitize_text_field($_POST['status'] ?? ''),
            'date_from' => sanitize_text_field($_POST['date_from'] ?? ''),
            'date_to' => sanitize_text_field($_POST['date_to'] ?? ''),
            'search' => sanitize_text_field($_POST['search'] ?? ''),
            'per_page' => -1 // Export all matching records
        );
        
        $result = $this->export_orders($args);
        
        if ($result) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error(__('No orders found to export', 'zpos'));
        }
    }

    /**
     * AJAX handler for getting order details
     *
     * @since    1.0.0
     */
    public function ajax_get_order_details() {
        check_ajax_referer('zpos_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'zpos'));
        }
        
        $order_id = intval($_POST['order_id']);
        $order = $this->get_order($order_id);
        
        if ($order) {
            wp_send_json_success($order);
        } else {
            wp_send_json_error(__('Order not found', 'zpos'));
        }
    }
}
