<?php
/**
 * ZPOS Products Management Class
 *
 * @package ZPOS
 * @since 1.0.0
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

class ZPOS_Products {
    
    /**
     * Table name for products
     */
    private $table_name;
    
    /**
     * Initialize the class
     */
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'zpos_products';
    }
    
    /**
     * Create products table
     */
    public function create_table() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $this->table_name (
            id int(11) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            sku varchar(100) UNIQUE,
            description text,
            short_description text,
            price decimal(10,2) NOT NULL DEFAULT 0.00,
            sale_price decimal(10,2) DEFAULT NULL,
            cost_price decimal(10,2) DEFAULT 0.00,
            stock_quantity int(11) DEFAULT 0,
            manage_stock tinyint(1) DEFAULT 1,
            stock_status varchar(20) DEFAULT 'instock',
            category_id int(11) DEFAULT NULL,
            barcode varchar(100),
            image_url varchar(500),
            gallery_images text,
            weight decimal(8,3) DEFAULT NULL,
            dimensions varchar(100),
            tax_status varchar(20) DEFAULT 'taxable',
            tax_class varchar(50) DEFAULT '',
            status varchar(20) DEFAULT 'publish',
            woocommerce_id int(11) DEFAULT NULL,
            featured tinyint(1) DEFAULT 0,
            attributes text,
            meta_data text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY category_id (category_id),
            KEY woocommerce_id (woocommerce_id),
            KEY status (status),
            KEY stock_status (stock_status)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Get all products with pagination and filters
     */
    public function get_products($args = array()) {
        global $wpdb;
        
        $defaults = array(
            'per_page' => 20,
            'page' => 1,
            'search' => '',
            'category_id' => '',
            'status' => '',
            'stock_status' => '',
            'orderby' => 'created_at',
            'order' => 'DESC'
        );
        
        $args = wp_parse_args($args, $defaults);
        
        // Base query
        $where = array('1=1');
        $values = array();
        
        // Search filter
        if (!empty($args['search'])) {
            $where[] = "(name LIKE %s OR sku LIKE %s OR barcode LIKE %s)";
            $search_term = '%' . $wpdb->esc_like($args['search']) . '%';
            $values[] = $search_term;
            $values[] = $search_term;
            $values[] = $search_term;
        }
        
        // Category filter
        if (!empty($args['category_id'])) {
            $where[] = "category_id = %d";
            $values[] = $args['category_id'];
        }
        
        // Status filter
        if (!empty($args['status'])) {
            $where[] = "status = %s";
            $values[] = $args['status'];
        }
        
        // Stock status filter
        if (!empty($args['stock_status'])) {
            $where[] = "stock_status = %s";
            $values[] = $args['stock_status'];
        }
        
        $where_clause = implode(' AND ', $where);
        
        // Count total records
        $count_sql = "SELECT COUNT(*) FROM $this->table_name WHERE $where_clause";
        if (!empty($values)) {
            $count_sql = $wpdb->prepare($count_sql, $values);
        }
        $total_items = $wpdb->get_var($count_sql);
        
        // Get products
        $offset = ($args['page'] - 1) * $args['per_page'];
        $order_by = sanitize_sql_orderby($args['orderby'] . ' ' . $args['order']);
        
        $sql = "SELECT * FROM $this->table_name WHERE $where_clause ORDER BY $order_by LIMIT %d OFFSET %d";
        $values[] = $args['per_page'];
        $values[] = $offset;
        
        $products = $wpdb->get_results($wpdb->prepare($sql, $values));
        
        return array(
            'products' => $products,
            'total_items' => $total_items,
            'total_pages' => ceil($total_items / $args['per_page']),
            'current_page' => $args['page']        );
    }

    /**
     * Get products with pagination
     */
    public function get_products_with_pagination($page = 1, $per_page = 20, $search = '', $category = 0, $status = '') {
        global $wpdb;
        
        $offset = ($page - 1) * $per_page;
        
        // Build WHERE clause
        $where = array('1=1');
        $values = array();
        
        if (!empty($search)) {
            $where[] = "(name LIKE %s OR sku LIKE %s OR description LIKE %s)";
            $search_term = '%' . $wpdb->esc_like($search) . '%';
            $values[] = $search_term;
            $values[] = $search_term;
            $values[] = $search_term;
        }
        
        if (!empty($category)) {
            $where[] = "category_id = %d";
            $values[] = $category;
        }
        
        if (!empty($status)) {
            $where[] = "status = %s";
            $values[] = $status;
        }
        
        $where_sql = implode(' AND ', $where);
        
        // Get total count
        $count_sql = "SELECT COUNT(*) FROM {$this->table_name} WHERE {$where_sql}";
        if (!empty($values)) {
            $count_sql = $wpdb->prepare($count_sql, $values);
        }
        $total_items = $wpdb->get_var($count_sql);
        
        // Get products
        $sql = "SELECT p.*, pc.name as category_name 
                FROM {$this->table_name} p 
                LEFT JOIN {$wpdb->prefix}zpos_product_categories pc ON p.category_id = pc.id 
                WHERE {$where_sql} 
                ORDER BY p.created_at DESC 
                LIMIT %d OFFSET %d";
        
        $query_values = array_merge($values, array($per_page, $offset));
        $products = $wpdb->get_results($wpdb->prepare($sql, $query_values));
        
        // Calculate pagination
        $total_pages = ceil($total_items / $per_page);
        
        return array(
            'products' => $products,
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
     * Get single product by ID
     */
    public function get_product($id) {
        global $wpdb;
        
        $sql = "SELECT * FROM $this->table_name WHERE id = %d";
        return $wpdb->get_row($wpdb->prepare($sql, $id));
    }
    
    /**
     * Create or update product
     */
    public function save_product($data, $id = null) {
        global $wpdb;
        
        // Validate required fields
        if (empty($data['name'])) {
            return new WP_Error('missing_name', __('Product name is required', 'zpos'));
        }
        
        // Prepare data
        $product_data = array(
            'name' => sanitize_text_field($data['name']),
            'sku' => !empty($data['sku']) ? sanitize_text_field($data['sku']) : '',
            'description' => wp_kses_post($data['description'] ?? ''),
            'short_description' => wp_kses_post($data['short_description'] ?? ''),
            'price' => floatval($data['price'] ?? 0),
            'sale_price' => !empty($data['sale_price']) ? floatval($data['sale_price']) : null,
            'cost_price' => floatval($data['cost_price'] ?? 0),
            'stock_quantity' => intval($data['stock_quantity'] ?? 0),
            'manage_stock' => !empty($data['manage_stock']) ? 1 : 0,
            'stock_status' => sanitize_text_field($data['stock_status'] ?? 'instock'),
            'category_id' => !empty($data['category_id']) ? intval($data['category_id']) : null,
            'barcode' => sanitize_text_field($data['barcode'] ?? ''),
            'image_url' => esc_url_raw($data['image_url'] ?? ''),
            'gallery_images' => is_array($data['gallery_images'] ?? null) ? json_encode($data['gallery_images']) : '',
            'weight' => !empty($data['weight']) ? floatval($data['weight']) : null,
            'dimensions' => sanitize_text_field($data['dimensions'] ?? ''),
            'tax_status' => sanitize_text_field($data['tax_status'] ?? 'taxable'),
            'tax_class' => sanitize_text_field($data['tax_class'] ?? ''),
            'status' => sanitize_text_field($data['status'] ?? 'publish'),
            'featured' => !empty($data['featured']) ? 1 : 0,
            'attributes' => is_array($data['attributes'] ?? null) ? json_encode($data['attributes']) : '',
            'meta_data' => is_array($data['meta_data'] ?? null) ? json_encode($data['meta_data']) : ''
        );
        
        if ($id) {
            // Update existing product
            $result = $wpdb->update(
                $this->table_name,
                $product_data,
                array('id' => $id),
                array('%s', '%s', '%s', '%s', '%f', '%f', '%f', '%d', '%d', '%s', '%d', '%s', '%s', '%s', '%f', '%s', '%s', '%s', '%s', '%d', '%s', '%s'),
                array('%d')
            );
            
            if ($result === false) {
                return new WP_Error('update_failed', __('Failed to update product', 'zpos'));
            }
            
            return $id;
        } else {
            // Create new product
            $result = $wpdb->insert(
                $this->table_name,
                $product_data,
                array('%s', '%s', '%s', '%s', '%f', '%f', '%f', '%d', '%d', '%s', '%d', '%s', '%s', '%s', '%f', '%s', '%s', '%s', '%s', '%d', '%s', '%s')
            );
            
            if ($result === false) {
                return new WP_Error('insert_failed', __('Failed to create product', 'zpos'));
            }
            
            return $wpdb->insert_id;
        }
    }
    
    /**
     * Delete product
     */
    public function delete_product($id) {
        global $wpdb;
        
        $result = $wpdb->delete(
            $this->table_name,
            array('id' => $id),
            array('%d')
        );
        
        return $result !== false;
    }
    
    /**
     * Bulk delete products
     */
    public function bulk_delete($ids) {
        global $wpdb;
        
        if (empty($ids) || !is_array($ids)) {
            return false;
        }
        
        $ids = array_map('intval', $ids);
        $placeholders = implode(',', array_fill(0, count($ids), '%d'));
        
        $sql = "DELETE FROM $this->table_name WHERE id IN ($placeholders)";
        $result = $wpdb->query($wpdb->prepare($sql, $ids));
        
        return $result !== false;
    }
    
    /**
     * Sync with WooCommerce products
     */
    public function sync_with_woocommerce($product_id = null) {
        if (!class_exists('WooCommerce')) {
            return new WP_Error('woocommerce_not_active', __('WooCommerce is not active', 'zpos'));
        }
        
        global $wpdb;
        
        if ($product_id) {
            // Sync single product
            $product = $this->get_product($product_id);
            if (!$product) {
                return new WP_Error('product_not_found', __('Product not found', 'zpos'));
            }
            
            return $this->sync_single_product_to_woocommerce($product);
        } else {
            // Sync all products
            $products = $wpdb->get_results("SELECT * FROM $this->table_name WHERE status = 'publish'");
            $synced = 0;
            $errors = array();
            
            foreach ($products as $product) {
                $result = $this->sync_single_product_to_woocommerce($product);
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
     * Sync single product to WooCommerce
     */
    private function sync_single_product_to_woocommerce($product) {
        // WooCommerce product data
        $wc_product_data = array(
            'name' => $product->name,
            'sku' => $product->sku,
            'description' => $product->description,
            'short_description' => $product->short_description,
            'regular_price' => $product->price,
            'sale_price' => $product->sale_price,
            'manage_stock' => $product->manage_stock,
            'stock_quantity' => $product->stock_quantity,
            'stock_status' => $product->stock_status,
            'weight' => $product->weight,
            'tax_status' => $product->tax_status,
            'tax_class' => $product->tax_class,
            'status' => $product->status
        );
        
        if ($product->woocommerce_id) {
            // Update existing WooCommerce product
            $wc_product = wc_get_product($product->woocommerce_id);
            if (!$wc_product) {
                return new WP_Error('wc_product_not_found', __('WooCommerce product not found', 'zpos'));
            }
            
            foreach ($wc_product_data as $key => $value) {
                $wc_product->set_prop($key, $value);
            }
            
            $result = $wc_product->save();
        } else {
            // Create new WooCommerce product
            $wc_product = new WC_Product_Simple();
            
            foreach ($wc_product_data as $key => $value) {
                $wc_product->set_prop($key, $value);
            }
            
            $wc_product_id = $wc_product->save();
            
            // Update ZPOS product with WooCommerce ID
            global $wpdb;
            $wpdb->update(
                $this->table_name,
                array('woocommerce_id' => $wc_product_id),
                array('id' => $product->id),
                array('%d'),
                array('%d')
            );
            
            $result = $wc_product_id;
        }
        
        return $result;
    }
    
    /**
     * Import products from WooCommerce
     */
    public function import_from_woocommerce() {
        if (!class_exists('WooCommerce')) {
            return new WP_Error('woocommerce_not_active', __('WooCommerce is not active', 'zpos'));
        }
        
        $wc_products = get_posts(array(
            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => -1
        ));
        
        $imported = 0;
        $errors = array();
        
        foreach ($wc_products as $wc_post) {
            $wc_product = wc_get_product($wc_post->ID);
            
            // Check if already imported
            global $wpdb;
            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $this->table_name WHERE woocommerce_id = %d",
                $wc_product->get_id()
            ));
            
            if ($existing) {
                continue;
            }
            
            // Import product data
            $product_data = array(
                'name' => $wc_product->get_name(),
                'sku' => $wc_product->get_sku(),
                'description' => $wc_product->get_description(),
                'short_description' => $wc_product->get_short_description(),
                'price' => $wc_product->get_regular_price(),
                'sale_price' => $wc_product->get_sale_price(),
                'stock_quantity' => $wc_product->get_stock_quantity(),
                'manage_stock' => $wc_product->get_manage_stock(),
                'stock_status' => $wc_product->get_stock_status(),
                'weight' => $wc_product->get_weight(),
                'tax_status' => $wc_product->get_tax_status(),
                'tax_class' => $wc_product->get_tax_class(),
                'status' => $wc_product->get_status(),
                'woocommerce_id' => $wc_product->get_id(),
                'image_url' => wp_get_attachment_url($wc_product->get_image_id())
            );
            
            $result = $this->save_product($product_data);
            if (is_wp_error($result)) {
                $errors[] = $result->get_error_message();
            } else {
                $imported++;
            }
        }
        
        return array(
            'imported' => $imported,
            'errors' => $errors
        );
    }
    
    /**
     * Get product categories
     */
    public function get_categories() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'zpos_product_categories';
        
        return $wpdb->get_results("SELECT * FROM $table_name ORDER BY name ASC");
    }
    
    /**
     * Upload product image
     */
    public function upload_image($file) {
        if (!function_exists('wp_handle_upload')) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
        }
        
        $upload_overrides = array('test_form' => false);
        $movefile = wp_handle_upload($file, $upload_overrides);
        
        if ($movefile && !isset($movefile['error'])) {
            return $movefile['url'];
        } else {
            return new WP_Error('upload_failed', $movefile['error']);
        }
    }
}
