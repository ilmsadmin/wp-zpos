<?php
/**
 * Inventory Management functionality
 *
 * @link       https://yourwebsite.com
 * @since      1.0.0
 *
 * @package    ZPOS
 * @subpackage ZPOS/includes
 */

/**
 * The ZPOS Inventory class.
 *
 * This class handles all inventory management functionality including stock tracking,
 * low stock alerts, inventory movements, and reports.
 *
 * @since      1.0.0
 * @package    ZPOS
 * @subpackage ZPOS/includes
 * @author     Your Name <your.email@example.com>
 */
class ZPOS_Inventory {

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     */
    public function __construct() {
        add_action('wp_ajax_zpos_get_inventory', array($this, 'ajax_get_inventory'));
        add_action('wp_ajax_zpos_update_stock', array($this, 'ajax_update_stock'));
        add_action('wp_ajax_zpos_bulk_update_stock', array($this, 'ajax_bulk_update_stock'));
        add_action('wp_ajax_zpos_get_low_stock_alerts', array($this, 'ajax_get_low_stock_alerts'));
        add_action('wp_ajax_zpos_update_stock_threshold', array($this, 'ajax_update_stock_threshold'));
        add_action('wp_ajax_zpos_get_inventory_movements', array($this, 'ajax_get_inventory_movements'));
        add_action('wp_ajax_zpos_export_inventory', array($this, 'ajax_export_inventory'));
        add_action('wp_ajax_zpos_generate_inventory_report', array($this, 'ajax_generate_inventory_report'));
        
        // Schedule daily low stock check
        add_action('zpos_daily_inventory_check', array($this, 'check_low_stock_daily'));
        if (!wp_next_scheduled('zpos_daily_inventory_check')) {
            wp_schedule_event(time(), 'daily', 'zpos_daily_inventory_check');
        }
    }

    /**
     * Get inventory items with filtering and pagination
     *
     * @since    1.0.0
     * @param    array    $args    Query arguments
     * @return   array    Array of inventory items
     */    public function get_inventory($args = array()) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'zpos_inventory';
        $products_table = $wpdb->prefix . 'zpos_products';
        $categories_table = $wpdb->prefix . 'zpos_product_categories';
        
        $defaults = array(
            'search' => '',
            'category' => '',
            'low_stock_only' => false,
            'out_of_stock_only' => false,
            'per_page' => 20,
            'page' => 1,
            'orderby' => 'product_name',
            'order' => 'ASC'
        );
        
        $args = wp_parse_args($args, $defaults);
        
        // Check if inventory table has current_stock column (new schema)
        $columns = $wpdb->get_col("DESCRIBE $table_name");
        $has_current_stock = in_array('current_stock', $columns);
        $has_product_name = in_array('product_name', $columns);
        
        // Build select clause based on available columns
        if ($has_current_stock && $has_product_name) {
            // New schema - inventory table has all needed columns
            $select_sql = "SELECT i.*, 
                          COALESCE(i.current_stock, i.quantity_on_hand, 0) as current_stock,
                          COALESCE(i.unit_price, p.price, 0) as unit_price,
                          COALESCE(i.product_name, p.name) as product_name,
                          COALESCE(i.sku, p.sku) as sku,
                          COALESCE(i.barcode, p.barcode) as barcode,
                          COALESCE(i.category, c.name) as category";
            $from_sql = "FROM $table_name i
                        LEFT JOIN $products_table p ON i.product_id = p.id
                        LEFT JOIN $categories_table c ON p.category_id = c.id";
        } else {
            // Old schema - need to join with products table for missing data
            $select_sql = "SELECT i.*, 
                          p.name as product_name,
                          p.sku,
                          p.barcode,
                          p.price as unit_price,
                          c.name as category,
                          COALESCE(i.quantity_on_hand, i.quantity_available, 0) as current_stock";
            $from_sql = "FROM $table_name i
                        LEFT JOIN $products_table p ON i.product_id = p.id
                        LEFT JOIN $categories_table c ON p.category_id = c.id";
        }
        
        $where_clauses = array('1=1');
        $where_values = array();
        
        // Search filter - check both inventory and products table
        if (!empty($args['search'])) {
            if ($has_product_name) {
                $where_clauses[] = '(i.product_name LIKE %s OR i.sku LIKE %s OR i.barcode LIKE %s OR p.name LIKE %s OR p.sku LIKE %s OR p.barcode LIKE %s)';
            } else {
                $where_clauses[] = '(p.name LIKE %s OR p.sku LIKE %s OR p.barcode LIKE %s)';
            }
            $search_term = '%' . $wpdb->esc_like($args['search']) . '%';
            $where_values[] = $search_term;
            $where_values[] = $search_term;
            $where_values[] = $search_term;
            if ($has_product_name) {
                $where_values[] = $search_term;
                $where_values[] = $search_term;
                $where_values[] = $search_term;
            }
        }
        
        // Category filter
        if (!empty($args['category'])) {
            if (in_array('category', $columns)) {
                $where_clauses[] = '(i.category = %s OR c.name = %s)';
                $where_values[] = $args['category'];
                $where_values[] = $args['category'];
            } else {
                $where_clauses[] = 'c.name = %s';
                $where_values[] = $args['category'];
            }
        }
        
        // Low stock filter
        if ($args['low_stock_only']) {
            if ($has_current_stock) {
                $where_clauses[] = 'COALESCE(i.current_stock, i.quantity_on_hand, 0) <= i.low_stock_threshold AND i.low_stock_threshold > 0';
            } else {
                $where_clauses[] = 'COALESCE(i.quantity_on_hand, i.quantity_available, 0) <= i.low_stock_threshold AND i.low_stock_threshold > 0';
            }
        }
        
        // Out of stock filter
        if ($args['out_of_stock_only']) {
            if ($has_current_stock) {
                $where_clauses[] = 'COALESCE(i.current_stock, i.quantity_on_hand, 0) <= 0';
            } else {
                $where_clauses[] = 'COALESCE(i.quantity_on_hand, i.quantity_available, 0) <= 0';
            }
        }
          $where_sql = implode(' AND ', $where_clauses);
        
        // Count total records
        $count_sql = "SELECT COUNT(*) $from_sql WHERE $where_sql";
        if (!empty($where_values)) {
            $count_sql = $wpdb->prepare($count_sql, $where_values);
        }
        $total_records = $wpdb->get_var($count_sql);
        
        // Get inventory items
        $offset = ($args['page'] - 1) * $args['per_page'];
        $orderby = sanitize_sql_orderby($args['orderby'] . ' ' . $args['order']);
        
        $sql = "$select_sql $from_sql WHERE $where_sql ORDER BY $orderby LIMIT %d OFFSET %d";
        $query_values = array_merge($where_values, array($args['per_page'], $offset));
        
        $items = $wpdb->get_results($wpdb->prepare($sql, $query_values));
        
        // Populate missing data and ensure all items have required fields
        foreach ($items as $item) {
            // Ensure current_stock is available
            if (!isset($item->current_stock)) {
                $item->current_stock = isset($item->quantity_on_hand) ? $item->quantity_on_hand : 0;
            }
            
            // Ensure unit_price is available
            if (!isset($item->unit_price) || $item->unit_price == 0) {
                $item->unit_price = isset($item->price) ? $item->price : 0;
            }
            
            // Ensure product_name is available
            if (!isset($item->product_name) || empty($item->product_name)) {
                $item->product_name = isset($item->name) ? $item->name : 'Unknown Product';
            }
            
            // Calculate total value
            $item->total_value = $item->current_stock * $item->unit_price;
        }
        
        // Add stock status to each item
        foreach ($items as $item) {
            $item->stock_status = $this->get_stock_status($item);
        }
        
        return array(
            'items' => $items,
            'total' => $total_records,
            'pages' => ceil($total_records / $args['per_page']),
            'current_page' => $args['page']
        );
    }

    /**
     * Get stock status for an inventory item
     *
     * @since    1.0.0
     * @param    object    $item    Inventory item
     * @return   string    Stock status
     */    public function get_stock_status($item) {
        // Handle both old and new column names for backward compatibility
        $current_stock = isset($item->current_stock) ? $item->current_stock : $item->quantity_on_hand;
        
        if ($current_stock <= 0) {
            return 'out_of_stock';
        } elseif ($item->low_stock_threshold > 0 && $current_stock <= $item->low_stock_threshold) {
            return 'low_stock';
        } else {
            return 'in_stock';
        }
    }

    /**
     * Update stock for a product
     *
     * @since    1.0.0
     * @param    int       $product_id    Product ID
     * @param    int       $quantity      Quantity change (positive or negative)
     * @param    string    $reason        Reason for stock change
     * @param    string    $type          Type of movement (manual, sale, purchase, adjustment)
     * @return   bool      Success status
     */
    public function update_stock($product_id, $quantity, $reason = '', $type = 'manual') {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'zpos_inventory';
        $movements_table = $wpdb->prefix . 'zpos_inventory_movements';
          // Get current stock
        $current_item = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE product_id = %d",
            $product_id
        ));
        
        if (!$current_item) {
            return false;
        }
        
        // Handle both old and new column names for backward compatibility
        $old_stock = isset($current_item->current_stock) ? $current_item->current_stock : $current_item->quantity_on_hand;
        $new_stock = max(0, $old_stock + $quantity); // Prevent negative stock
        
        // Prepare update data with both old and new column names
        $update_data = array(
            'updated_at' => current_time('mysql')
        );
        
        // Update current_stock if column exists, otherwise update quantity_on_hand
        if (isset($current_item->current_stock)) {
            $update_data['current_stock'] = $new_stock;
        } else {
            $update_data['quantity_on_hand'] = $new_stock;
            $update_data['quantity_available'] = $new_stock - $current_item->quantity_reserved;
        }
          // Update stock
        $result = $wpdb->update(
            $table_name,
            $update_data,
            array('product_id' => $product_id),
            array_fill(0, count($update_data), '%s'), // All values as strings for safety
            array('%d')
        );
        
        if ($result !== false) {
            // Log movement
            $this->log_inventory_movement(
                $product_id,
                $type,
                $quantity,
                $old_stock,
                $new_stock,
                $reason
            );
            
            // Check for low stock alert
            if ($this->get_stock_status($wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$table_name} WHERE product_id = %d",
                $product_id
            ))) === 'low_stock') {
                $this->trigger_low_stock_alert($product_id);
            }
            
            return true;
        }
        
        return false;
    }

    /**
     * Bulk update stock for multiple products
     *
     * @since    1.0.0
     * @param    array    $updates    Array of updates (product_id => quantity)
     * @param    string   $reason     Reason for bulk update
     * @return   array    Results array
     */
    public function bulk_update_stock($updates, $reason = '') {
        $success_count = 0;
        $error_count = 0;
        $errors = array();
        
        foreach ($updates as $product_id => $quantity) {
            $result = $this->update_stock($product_id, $quantity, $reason, 'bulk_adjustment');
            
            if ($result) {
                $success_count++;
            } else {
                $error_count++;
                $errors[] = sprintf(__('Failed to update stock for product ID %d', 'zpos'), $product_id);
            }
        }
        
        return array(
            'success' => $success_count,
            'errors' => $error_count,
            'error_messages' => $errors
        );
    }

    /**
     * Update stock threshold for a product
     *
     * @since    1.0.0
     * @param    int    $product_id    Product ID
     * @param    int    $threshold     Low stock threshold
     * @return   bool   Success status
     */
    public function update_stock_threshold($product_id, $threshold) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'zpos_inventory';
        
        $result = $wpdb->update(
            $table_name,
            array(
                'low_stock_threshold' => max(0, $threshold),
                'updated_at' => current_time('mysql')
            ),
            array('product_id' => $product_id),
            array('%d', '%s'),
            array('%d')
        );
        
        return $result !== false;
    }

    /**
     * Get low stock alerts
     *
     * @since    1.0.0
     * @return   array    Array of low stock items
     */    public function get_low_stock_alerts() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'zpos_inventory';
        $products_table = $wpdb->prefix . 'zpos_products';
        $categories_table = $wpdb->prefix . 'zpos_product_categories';
        
        // Check if we have current_stock column (new schema)
        $columns = $wpdb->get_col("DESCRIBE $table_name");
        $has_current_stock = in_array('current_stock', $columns);
        $has_product_name = in_array('product_name', $columns);
        
        if ($has_current_stock && $has_product_name) {
            // New schema
            $sql = "SELECT i.*, 
                           COALESCE(i.current_stock, i.quantity_on_hand, 0) as current_stock,
                           COALESCE(i.product_name, p.name) as product_name,
                           COALESCE(i.sku, p.sku) as sku,
                           COALESCE(i.unit_price, p.price, 0) as unit_price
                    FROM $table_name i
                    LEFT JOIN $products_table p ON i.product_id = p.id
                    WHERE COALESCE(i.current_stock, i.quantity_on_hand, 0) <= i.low_stock_threshold 
                    AND i.low_stock_threshold > 0 
                    ORDER BY (COALESCE(i.current_stock, i.quantity_on_hand, 0) / NULLIF(i.low_stock_threshold, 0)) ASC";
        } else {
            // Old schema - need to join with products table
            $sql = "SELECT i.*, 
                           p.name as product_name,
                           p.sku,
                           p.price as unit_price,
                           COALESCE(i.quantity_on_hand, i.quantity_available, 0) as current_stock
                    FROM $table_name i
                    LEFT JOIN $products_table p ON i.product_id = p.id
                    WHERE COALESCE(i.quantity_on_hand, i.quantity_available, 0) <= i.low_stock_threshold 
                    AND i.low_stock_threshold > 0 
                    ORDER BY (COALESCE(i.quantity_on_hand, i.quantity_available, 0) / NULLIF(i.low_stock_threshold, 0)) ASC";
        }
        
        $items = $wpdb->get_results($sql);
        
        foreach ($items as $item) {
            $item->stock_status = $this->get_stock_status($item);
        }
        
        return $items;
    }

    /**
     * Get inventory movements
     *
     * @since    1.0.0
     * @param    array    $args    Query arguments
     * @return   array    Array of movements
     */
    public function get_inventory_movements($args = array()) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'zpos_inventory_movements';
        $inventory_table = $wpdb->prefix . 'zpos_inventory';
        
        $defaults = array(
            'product_id' => '',
            'type' => '',
            'date_from' => '',
            'date_to' => '',
            'per_page' => 20,
            'page' => 1,
            'orderby' => 'created_at',
            'order' => 'DESC'
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $where_clauses = array('1=1');
        $where_values = array();
        
        // Product filter
        if (!empty($args['product_id'])) {
            $where_clauses[] = 'm.product_id = %d';
            $where_values[] = $args['product_id'];
        }
        
        // Type filter
        if (!empty($args['type'])) {
            $where_clauses[] = 'm.type = %s';
            $where_values[] = $args['type'];
        }
        
        // Date range filter
        if (!empty($args['date_from'])) {
            $where_clauses[] = 'DATE(m.created_at) >= %s';
            $where_values[] = $args['date_from'];
        }
        
        if (!empty($args['date_to'])) {
            $where_clauses[] = 'DATE(m.created_at) <= %s';
            $where_values[] = $args['date_to'];
        }
        
        $where_sql = implode(' AND ', $where_clauses);
        
        // Count total records
        $count_sql = "SELECT COUNT(*) FROM {$table_name} m WHERE {$where_sql}";
        if (!empty($where_values)) {
            $count_sql = $wpdb->prepare($count_sql, $where_values);
        }
        $total_records = $wpdb->get_var($count_sql);
        
        // Get movements
        $offset = ($args['page'] - 1) * $args['per_page'];
        $orderby = sanitize_sql_orderby('m.' . $args['orderby'] . ' ' . $args['order']);
        
        $sql = "
            SELECT m.*, i.product_name, i.sku 
            FROM {$table_name} m
            LEFT JOIN {$inventory_table} i ON m.product_id = i.product_id
            WHERE {$where_sql} 
            ORDER BY {$orderby} 
            LIMIT %d OFFSET %d
        ";
        
        $query_values = array_merge($where_values, array($args['per_page'], $offset));
        
        $movements = $wpdb->get_results($wpdb->prepare($sql, $query_values));
        
        return array(
            'movements' => $movements,
            'total' => $total_records,
            'pages' => ceil($total_records / $args['per_page']),
            'current_page' => $args['page']
        );
    }    /**
     * Log inventory movement
     *
     * @since    1.0.0
     * @param    int       $product_id     Product ID
     * @param    string    $type           Movement type
     * @param    int       $quantity       Quantity change
     * @param    int       $old_stock      Stock before change
     * @param    int       $new_stock      Stock after change
     * @param    string    $reason         Reason for movement
     */
    private function log_inventory_movement($product_id, $type, $quantity, $old_stock, $new_stock, $reason = '') {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'zpos_inventory_movements';
        $inventory_table = $wpdb->prefix . 'zpos_inventory';
        $products_table = $wpdb->prefix . 'zpos_products';
        
        // Get product details for the movement log
        $product_details = $wpdb->get_row($wpdb->prepare("
            SELECT 
                COALESCE(i.product_name, p.name) as product_name,
                COALESCE(i.sku, p.sku) as sku
            FROM $inventory_table i
            LEFT JOIN $products_table p ON i.product_id = p.id
            WHERE i.product_id = %d
        ", $product_id));
        
        if (!$product_details) {
            // Fallback to products table only
            $product_details = $wpdb->get_row($wpdb->prepare("
                SELECT name as product_name, sku 
                FROM $products_table 
                WHERE id = %d
            ", $product_id));
        }
        
        $wpdb->insert(
            $table_name,
            array(
                'product_id' => $product_id,
                'product_name' => $product_details ? $product_details->product_name : '',
                'sku' => $product_details ? $product_details->sku : '',
                'type' => $type,
                'quantity_change' => $quantity,
                'stock_before' => $old_stock,
                'stock_after' => $new_stock,
                'reason' => $reason,
                'user_id' => get_current_user_id(),
                'created_at' => current_time('mysql')
            )
        );
    }

    /**
     * Trigger low stock alert
     *
     * @since    1.0.0
     * @param    int    $product_id    Product ID
     */
    private function trigger_low_stock_alert($product_id) {
        // Here you could send email notifications, create admin notices, etc.
        // For now, we'll just create a transient for admin notice
        $low_stock_alerts = get_transient('zpos_low_stock_alerts') ?: array();
        if (!in_array($product_id, $low_stock_alerts)) {
            $low_stock_alerts[] = $product_id;
            set_transient('zpos_low_stock_alerts', $low_stock_alerts, DAY_IN_SECONDS);
        }
    }

    /**
     * Daily low stock check (scheduled)
     *
     * @since    1.0.0
     */
    public function check_low_stock_daily() {
        $low_stock_items = $this->get_low_stock_alerts();
        
        if (!empty($low_stock_items)) {
            // Send email notification to admin
            $admin_email = get_option('admin_email');
            $subject = sprintf(__('[%s] Low Stock Alert', 'zpos'), get_bloginfo('name'));
            
            $message = __('The following items are running low on stock:', 'zpos') . "\n\n";
            
            foreach ($low_stock_items as $item) {
                $message .= sprintf(
                    "- %s (SKU: %s) - %d remaining (threshold: %d)\n",
                    $item->product_name,
                    $item->sku,
                    $item->current_stock,
                    $item->low_stock_threshold
                );
            }
            
            wp_mail($admin_email, $subject, $message);
        }
    }

    /**
     * Export inventory to CSV
     *
     * @since    1.0.0
     * @param    array    $args    Export arguments
     * @return   string   File path or false on failure
     */
    public function export_inventory($args = array()) {
        $inventory_data = $this->get_inventory(array_merge($args, array('per_page' => -1)));
        $items = $inventory_data['items'];
        
        if (empty($items)) {
            return false;
        }
        
        $upload_dir = wp_upload_dir();
        $filename = 'zpos-inventory-export-' . date('Y-m-d-H-i-s') . '.csv';
        $filepath = $upload_dir['path'] . '/' . $filename;
        
        $file = fopen($filepath, 'w');
        
        // CSV headers
        $headers = array(
            'Product Name',
            'SKU',
            'Barcode',
            'Category',
            'Current Stock',
            'Low Stock Threshold',
            'Stock Status',
            'Unit Price',
            'Total Value',
            'Last Updated'
        );
        
        fputcsv($file, $headers);
        
        // Export data
        foreach ($items as $item) {
            $total_value = $item->current_stock * $item->unit_price;
            
            $row = array(
                $item->product_name,
                $item->sku,
                $item->barcode,
                $item->category,
                $item->current_stock,
                $item->low_stock_threshold,
                ucfirst(str_replace('_', ' ', $item->stock_status)),
                number_format($item->unit_price, 2),
                number_format($total_value, 2),
                $item->updated_at
            );
            
            fputcsv($file, $row);
        }
        
        fclose($file);
        
        return array(
            'file_path' => $filepath,
            'file_url' => $upload_dir['url'] . '/' . $filename,
            'filename' => $filename
        );
    }    /**
     * Generate inventory report
     *
     * @since    1.0.0
     * @param    array    $args    Report arguments
     * @return   array    Report data
     */
    public function generate_inventory_report($args = array()) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'zpos_inventory';
        $movements_table = $wpdb->prefix . 'zpos_inventory_movements';
        $products_table = $wpdb->prefix . 'zpos_products';
        
        $defaults = array(
            'date_from' => date('Y-m-01'), // First day of current month
            'date_to' => date('Y-m-d')     // Today
        );
        
        $args = wp_parse_args($args, $defaults);
        
        // Check schema to determine which columns to use
        $columns = $wpdb->get_col("DESCRIBE $table_name");
        $has_current_stock = in_array('current_stock', $columns);
        $has_unit_price = in_array('unit_price', $columns);
        $has_category = in_array('category', $columns);
        
        // Build the appropriate queries based on schema
        if ($has_current_stock && $has_unit_price) {
            // New schema
            $stock_column = 'current_stock';
            $price_column = 'unit_price';
            $category_column = $has_category ? 'category' : "''";
            
            $total_value = $wpdb->get_var("
                SELECT SUM($stock_column * $price_column) 
                FROM {$table_name}
                WHERE $stock_column IS NOT NULL AND $price_column IS NOT NULL
            ");
            
            // Total items
            $total_items = $wpdb->get_var("SELECT COUNT(*) FROM {$table_name}");
            
            // Low stock items count
            $low_stock_count = $wpdb->get_var("
                SELECT COUNT(*) FROM {$table_name} 
                WHERE $stock_column <= low_stock_threshold 
                AND low_stock_threshold > 0
            ");
            
            // Out of stock items count
            $out_of_stock_count = $wpdb->get_var("
                SELECT COUNT(*) FROM {$table_name} 
                WHERE $stock_column <= 0
            ");
            
            // Top categories by value
            if ($has_category) {
                $top_categories = $wpdb->get_results("
                    SELECT 
                        $category_column as category,
                        COUNT(*) as item_count,
                        SUM($stock_column * $price_column) as total_value
                    FROM {$table_name}
                    WHERE $category_column != '' AND $category_column IS NOT NULL
                    GROUP BY $category_column
                    ORDER BY total_value DESC
                    LIMIT 10
                ");
            } else {
                $top_categories = array();
            }
        } else {
            // Old schema - need to join with products table
            $stock_column = 'COALESCE(i.quantity_on_hand, i.quantity_available, 0)';
            
            $total_value = $wpdb->get_var("
                SELECT SUM($stock_column * COALESCE(p.price, 0)) 
                FROM {$table_name} i
                LEFT JOIN {$products_table} p ON i.product_id = p.id
            ");
            
            // Total items
            $total_items = $wpdb->get_var("SELECT COUNT(*) FROM {$table_name}");
            
            // Low stock items count
            $low_stock_count = $wpdb->get_var("
                SELECT COUNT(*) FROM {$table_name} 
                WHERE $stock_column <= low_stock_threshold 
                AND low_stock_threshold > 0
            ");
            
            // Out of stock items count
            $out_of_stock_count = $wpdb->get_var("
                SELECT COUNT(*) FROM {$table_name} 
                WHERE $stock_column <= 0
            ");
            
            // Top categories by value (from products table)
            $categories_table = $wpdb->prefix . 'zpos_product_categories';
            $top_categories = $wpdb->get_results("
                SELECT 
                    c.name as category,
                    COUNT(*) as item_count,
                    SUM($stock_column * COALESCE(p.price, 0)) as total_value
                FROM {$table_name} i
                LEFT JOIN {$products_table} p ON i.product_id = p.id
                LEFT JOIN {$categories_table} c ON p.category_id = c.id
                WHERE c.name IS NOT NULL AND c.name != ''
                GROUP BY c.name
                ORDER BY total_value DESC
                LIMIT 10
            ");
        }
        
        // Stock movements in date range
        $movements_count = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM {$movements_table} 
            WHERE DATE(created_at) BETWEEN %s AND %s
        ", $args['date_from'], $args['date_to']));
        
        // Stock adjustments in date range
        $adjustments = $wpdb->get_results($wpdb->prepare("
            SELECT 
                type,
                COUNT(*) as count,
                SUM(ABS(quantity_change)) as total_quantity
            FROM {$movements_table} 
            WHERE DATE(created_at) BETWEEN %s AND %s
            GROUP BY type
        ", $args['date_from'], $args['date_to']));
        
        return array(
            'summary' => array(
                'total_value' => floatval($total_value),
                'total_items' => intval($total_items),
                'low_stock_count' => intval($low_stock_count),
                'out_of_stock_count' => intval($out_of_stock_count),
                'movements_count' => intval($movements_count)
            ),
            'adjustments' => $adjustments,
            'top_categories' => $top_categories,
            'date_range' => $args
        );
    }

    /**
     * AJAX handler for getting inventory
     *
     * @since    1.0.0
     */
    public function ajax_get_inventory() {
        check_ajax_referer('zpos_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'zpos'));
        }
        
        $args = array(
            'search' => sanitize_text_field($_POST['search'] ?? ''),
            'category' => sanitize_text_field($_POST['category'] ?? ''),
            'low_stock_only' => !empty($_POST['low_stock_only']),
            'out_of_stock_only' => !empty($_POST['out_of_stock_only']),
            'per_page' => intval($_POST['per_page'] ?? 20),
            'page' => intval($_POST['page'] ?? 1),
            'orderby' => sanitize_text_field($_POST['orderby'] ?? 'product_name'),
            'order' => sanitize_text_field($_POST['order'] ?? 'ASC')
        );
        
        $result = $this->get_inventory($args);
        wp_send_json_success($result);
    }

    /**
     * AJAX handler for updating stock
     *
     * @since    1.0.0
     */
    public function ajax_update_stock() {
        check_ajax_referer('zpos_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'zpos'));
        }
        
        $product_id = intval($_POST['product_id']);
        $quantity = intval($_POST['quantity']);
        $reason = sanitize_text_field($_POST['reason'] ?? '');
        
        $result = $this->update_stock($product_id, $quantity, $reason, 'manual');
        
        if ($result) {
            wp_send_json_success(__('Stock updated successfully', 'zpos'));
        } else {
            wp_send_json_error(__('Failed to update stock', 'zpos'));
        }
    }

    /**
     * AJAX handler for bulk stock update
     *
     * @since    1.0.0
     */
    public function ajax_bulk_update_stock() {
        check_ajax_referer('zpos_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'zpos'));
        }
        
        $updates = $_POST['updates'] ?? array();
        $reason = sanitize_text_field($_POST['reason'] ?? '');
        
        // Sanitize updates
        $sanitized_updates = array();
        foreach ($updates as $product_id => $quantity) {
            $sanitized_updates[intval($product_id)] = intval($quantity);
        }
        
        $result = $this->bulk_update_stock($sanitized_updates, $reason);
        wp_send_json_success($result);
    }

    /**
     * AJAX handler for getting low stock alerts
     *
     * @since    1.0.0
     */
    public function ajax_get_low_stock_alerts() {
        check_ajax_referer('zpos_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'zpos'));
        }
        
        $result = $this->get_low_stock_alerts();
        wp_send_json_success($result);
    }

    /**
     * AJAX handler for updating stock threshold
     *
     * @since    1.0.0
     */
    public function ajax_update_stock_threshold() {
        check_ajax_referer('zpos_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'zpos'));
        }
        
        $product_id = intval($_POST['product_id']);
        $threshold = intval($_POST['threshold']);
        
        $result = $this->update_stock_threshold($product_id, $threshold);
        
        if ($result) {
            wp_send_json_success(__('Stock threshold updated successfully', 'zpos'));
        } else {
            wp_send_json_error(__('Failed to update stock threshold', 'zpos'));
        }
    }

    /**
     * AJAX handler for getting inventory movements
     *
     * @since    1.0.0
     */
    public function ajax_get_inventory_movements() {
        check_ajax_referer('zpos_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'zpos'));
        }
        
        $args = array(
            'product_id' => intval($_POST['product_id'] ?? 0),
            'type' => sanitize_text_field($_POST['type'] ?? ''),
            'date_from' => sanitize_text_field($_POST['date_from'] ?? ''),
            'date_to' => sanitize_text_field($_POST['date_to'] ?? ''),
            'per_page' => intval($_POST['per_page'] ?? 20),
            'page' => intval($_POST['page'] ?? 1),
            'orderby' => sanitize_text_field($_POST['orderby'] ?? 'created_at'),
            'order' => sanitize_text_field($_POST['order'] ?? 'DESC')
        );
        
        $result = $this->get_inventory_movements($args);
        wp_send_json_success($result);
    }

    /**
     * AJAX handler for exporting inventory
     *
     * @since    1.0.0
     */
    public function ajax_export_inventory() {
        check_ajax_referer('zpos_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'zpos'));
        }
        
        $args = array(
            'search' => sanitize_text_field($_POST['search'] ?? ''),
            'category' => sanitize_text_field($_POST['category'] ?? ''),
            'low_stock_only' => !empty($_POST['low_stock_only']),
            'out_of_stock_only' => !empty($_POST['out_of_stock_only'])
        );
        
        $result = $this->export_inventory($args);
        
        if ($result) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error(__('No inventory items found to export', 'zpos'));
        }
    }    /**
     * AJAX handler for generating inventory report
     *
     * @since    1.0.0
     */
    public function ajax_generate_inventory_report() {
        check_ajax_referer('zpos_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'zpos'));
        }
        
        $args = array(
            'date_from' => sanitize_text_field($_POST['date_from'] ?? ''),
            'date_to' => sanitize_text_field($_POST['date_to'] ?? '')
        );
        
        $result = $this->generate_inventory_report($args);
        wp_send_json_success($result);
    }

    /**
     * Populate missing data in inventory records
     * This method helps migrate old inventory records to have complete data
     *
     * @since    1.0.0
     */
    public function populate_missing_inventory_data() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'zpos_inventory';
        $products_table = $wpdb->prefix . 'zpos_products';
        $categories_table = $wpdb->prefix . 'zpos_product_categories';
        
        // Check if we have the new columns
        $columns = $wpdb->get_col("DESCRIBE $table_name");
        $has_product_name = in_array('product_name', $columns);
        $has_sku = in_array('sku', $columns);
        $has_category = in_array('category', $columns);
        $has_unit_price = in_array('unit_price', $columns);
        $has_current_stock = in_array('current_stock', $columns);
        
        if (!$has_product_name && !$has_sku && !$has_category && !$has_unit_price) {
            // Old schema, nothing to populate
            return false;
        }
        
        // Get inventory records that need data population
        $inventory_items = $wpdb->get_results("
            SELECT i.*, p.name, p.sku as product_sku, p.price, c.name as category_name
            FROM $table_name i
            LEFT JOIN $products_table p ON i.product_id = p.id
            LEFT JOIN $categories_table c ON p.category_id = c.id
            WHERE i.product_id > 0
        ");
        
        $updated_count = 0;
        
        foreach ($inventory_items as $item) {
            $update_data = array();
            $update_formats = array();
            
            // Populate product_name if missing or empty
            if ($has_product_name && (empty($item->product_name) || is_null($item->product_name)) && !empty($item->name)) {
                $update_data['product_name'] = $item->name;
                $update_formats[] = '%s';
            }
            
            // Populate SKU if missing or empty
            if ($has_sku && (empty($item->sku) || is_null($item->sku)) && !empty($item->product_sku)) {
                $update_data['sku'] = $item->product_sku;
                $update_formats[] = '%s';
            }
            
            // Populate category if missing or empty
            if ($has_category && (empty($item->category) || is_null($item->category)) && !empty($item->category_name)) {
                $update_data['category'] = $item->category_name;
                $update_formats[] = '%s';
            }
            
            // Populate unit_price if missing or empty
            if ($has_unit_price && (empty($item->unit_price) || is_null($item->unit_price)) && !empty($item->price)) {
                $update_data['unit_price'] = $item->price;
                $update_formats[] = '%f';
            }
            
            // Populate current_stock from old columns if missing
            if ($has_current_stock && (empty($item->current_stock) || is_null($item->current_stock))) {
                $stock_value = !empty($item->quantity_on_hand) ? $item->quantity_on_hand : 
                              (!empty($item->quantity_available) ? $item->quantity_available : 0);
                $update_data['current_stock'] = $stock_value;
                $update_formats[] = '%d';
            }
            
            // Update total_value if we have both stock and price
            if ($has_current_stock && $has_unit_price) {
                $stock = isset($update_data['current_stock']) ? $update_data['current_stock'] : $item->current_stock;
                $price = isset($update_data['unit_price']) ? $update_data['unit_price'] : $item->unit_price;
                if ($stock > 0 && $price > 0) {
                    $update_data['total_value'] = $stock * $price;
                    $update_formats[] = '%f';
                }
            }
            
            // Update the record if we have data to update
            if (!empty($update_data)) {
                $result = $wpdb->update(
                    $table_name,
                    $update_data,
                    array('id' => $item->id),
                    $update_formats,
                    array('%d')
                );
                
                if ($result !== false) {
                    $updated_count++;
                }
            }
        }
        
        return $updated_count;
    }

    /**
     * Get dashboard statistics
     *
     * @since    1.0.0
     * @return   array    Dashboard stats
     */
    public function get_dashboard_stats() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'zpos_inventory';
        $movements_table = $wpdb->prefix . 'zpos_inventory_movements';
        
        // Check schema
        $columns = $wpdb->get_col("DESCRIBE $table_name");
        $has_current_stock = in_array('current_stock', $columns);
        $has_unit_price = in_array('unit_price', $columns);
        
        if ($has_current_stock) {
            $stock_column = 'current_stock';
        } else {
            $stock_column = 'COALESCE(quantity_on_hand, quantity_available, 0)';
        }
        
        // Total inventory value
        if ($has_unit_price) {
            $total_value = $wpdb->get_var("
                SELECT SUM($stock_column * unit_price) 
                FROM {$table_name}
                WHERE $stock_column > 0 AND unit_price > 0
            ");
        } else {
            $products_table = $wpdb->prefix . 'zpos_products';
            $total_value = $wpdb->get_var("
                SELECT SUM($stock_column * COALESCE(p.price, 0)) 
                FROM {$table_name} i
                LEFT JOIN {$products_table} p ON i.product_id = p.id
                WHERE $stock_column > 0
            ");
        }
        
        // Total items count
        $total_items = $wpdb->get_var("SELECT COUNT(*) FROM {$table_name}");
        
        // Low stock count
        $low_stock_count = $wpdb->get_var("
            SELECT COUNT(*) FROM {$table_name} 
            WHERE $stock_column <= low_stock_threshold 
            AND low_stock_threshold > 0
        ");
        
        // Out of stock count
        $out_of_stock_count = $wpdb->get_var("
            SELECT COUNT(*) FROM {$table_name} 
            WHERE $stock_column <= 0
        ");
        
        // Recent movements (last 7 days)
        $recent_movements = $wpdb->get_var("
            SELECT COUNT(*) FROM {$movements_table} 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        ");
        
        return array(
            'total_value' => floatval($total_value),
            'total_items' => intval($total_items),
            'low_stock_count' => intval($low_stock_count),
            'out_of_stock_count' => intval($out_of_stock_count),
            'recent_movements' => intval($recent_movements)
        );
    }
}
