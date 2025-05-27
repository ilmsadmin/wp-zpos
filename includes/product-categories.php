<?php
/**
 * ZPOS Product Categories Management Class
 *
 * @package ZPOS
 * @since 1.0.0
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

class ZPOS_Product_Categories {
    
    /**
     * Table name for categories
     */
    private $table_name;
    
    /**
     * Initialize the class
     */
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'zpos_product_categories';
    }
    
    /**
     * Create categories table
     */
    public function create_table() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $this->table_name (
            id int(11) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            slug varchar(255) UNIQUE,
            description text,
            parent_id int(11) DEFAULT NULL,
            image_url varchar(500),
            status varchar(20) DEFAULT 'active',
            sort_order int(11) DEFAULT 0,
            meta_data text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY parent_id (parent_id),
            KEY status (status),
            KEY sort_order (sort_order)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Insert default categories
        $this->insert_default_categories();
    }
    
    /**
     * Insert default categories
     */
    private function insert_default_categories() {
        global $wpdb;
        
        $default_categories = array(
            array('name' => 'Electronics', 'slug' => 'electronics', 'description' => 'Electronic devices and accessories'),
            array('name' => 'Clothing', 'slug' => 'clothing', 'description' => 'Apparel and fashion items'),
            array('name' => 'Books', 'slug' => 'books', 'description' => 'Books and publications'),
            array('name' => 'Food & Beverages', 'slug' => 'food-beverages', 'description' => 'Food and drink items'),
            array('name' => 'Home & Garden', 'slug' => 'home-garden', 'description' => 'Home and garden products'),
            array('name' => 'Sports & Outdoors', 'slug' => 'sports-outdoors', 'description' => 'Sports and outdoor equipment'),
            array('name' => 'Health & Beauty', 'slug' => 'health-beauty', 'description' => 'Health and beauty products'),
            array('name' => 'Toys & Games', 'slug' => 'toys-games', 'description' => 'Toys and games for all ages')
        );
        
        foreach ($default_categories as $category) {
            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $this->table_name WHERE slug = %s",
                $category['slug']
            ));
            
            if (!$existing) {
                $wpdb->insert(
                    $this->table_name,
                    $category,
                    array('%s', '%s', '%s')
                );
            }
        }
    }
    
    /**
     * Get all categories
     */
    public function get_categories($args = array()) {
        global $wpdb;
        
        $defaults = array(
            'parent_id' => null,
            'status' => 'active',
            'orderby' => 'sort_order',
            'order' => 'ASC'
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $where = array('1=1');
        $values = array();
        
        if ($args['parent_id'] !== null) {
            if ($args['parent_id'] === 0) {
                $where[] = "parent_id IS NULL";
            } else {
                $where[] = "parent_id = %d";
                $values[] = $args['parent_id'];
            }
        }
        
        if (!empty($args['status'])) {
            $where[] = "status = %s";
            $values[] = $args['status'];
        }
        
        $where_clause = implode(' AND ', $where);
        $order_by = sanitize_sql_orderby($args['orderby'] . ' ' . $args['order']);
        
        $sql = "SELECT * FROM $this->table_name WHERE $where_clause ORDER BY $order_by";
        
        if (!empty($values)) {
            return $wpdb->get_results($wpdb->prepare($sql, $values));
        } else {
            return $wpdb->get_results($sql);
        }
    }
    
    /**
     * Get single category
     */
    public function get_category($id) {
        global $wpdb;
        
        $sql = "SELECT * FROM $this->table_name WHERE id = %d";
        return $wpdb->get_row($wpdb->prepare($sql, $id));
    }
    
    /**
     * Save category
     */
    public function save_category($data, $id = null) {
        global $wpdb;
        
        if (empty($data['name'])) {
            return new WP_Error('missing_name', __('Category name is required', 'zpos'));
        }
        
        // Generate slug if not provided
        if (empty($data['slug'])) {
            $data['slug'] = sanitize_title($data['name']);
        } else {
            $data['slug'] = sanitize_title($data['slug']);
        }
        
        // Check for duplicate slug
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $this->table_name WHERE slug = %s" . ($id ? " AND id != %d" : ""),
            $data['slug'],
            $id ? $id : null
        ));
        
        if ($existing) {
            return new WP_Error('duplicate_slug', __('Category slug already exists', 'zpos'));
        }
        
        $category_data = array(
            'name' => sanitize_text_field($data['name']),
            'slug' => $data['slug'],
            'description' => wp_kses_post($data['description'] ?? ''),
            'parent_id' => !empty($data['parent_id']) ? intval($data['parent_id']) : null,
            'image_url' => esc_url_raw($data['image_url'] ?? ''),
            'status' => sanitize_text_field($data['status'] ?? 'active'),
            'sort_order' => intval($data['sort_order'] ?? 0),
            'meta_data' => is_array($data['meta_data'] ?? null) ? json_encode($data['meta_data']) : ''
        );
        
        if ($id) {
            // Update
            $result = $wpdb->update(
                $this->table_name,
                $category_data,
                array('id' => $id),
                array('%s', '%s', '%s', '%d', '%s', '%s', '%d', '%s'),
                array('%d')
            );
            
            return $result !== false ? $id : new WP_Error('update_failed', __('Failed to update category', 'zpos'));
        } else {
            // Insert
            $result = $wpdb->insert(
                $this->table_name,
                $category_data,
                array('%s', '%s', '%s', '%d', '%s', '%s', '%d', '%s')
            );
            
            return $result !== false ? $wpdb->insert_id : new WP_Error('insert_failed', __('Failed to create category', 'zpos'));
        }
    }
    
    /**
     * Delete category
     */
    public function delete_category($id) {
        global $wpdb;
        
        // Check if category has products
        $products_table = $wpdb->prefix . 'zpos_products';
        $product_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $products_table WHERE category_id = %d",
            $id
        ));
        
        if ($product_count > 0) {
            return new WP_Error('category_has_products', __('Cannot delete category that contains products', 'zpos'));
        }
        
        // Check if category has children
        $children_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $this->table_name WHERE parent_id = %d",
            $id
        ));
        
        if ($children_count > 0) {
            return new WP_Error('category_has_children', __('Cannot delete category that has sub-categories', 'zpos'));
        }
        
        $result = $wpdb->delete(
            $this->table_name,
            array('id' => $id),
            array('%d')
        );
        
        return $result !== false;
    }
    
    /**
     * Get category tree (hierarchical)
     */
    public function get_category_tree($parent_id = null) {
        $categories = $this->get_categories(array('parent_id' => $parent_id ?: 0));
        
        foreach ($categories as &$category) {
            $category->children = $this->get_category_tree($category->id);
        }
        
        return $categories;
    }
    
    /**
     * Get category options for select dropdown
     */
    public function get_category_options($selected = '', $parent_id = null, $level = 0) {
        $categories = $this->get_categories(array('parent_id' => $parent_id ?: 0));
        $options = '';
        
        foreach ($categories as $category) {
            $indent = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $level);
            $selected_attr = selected($selected, $category->id, false);
            $options .= "<option value='{$category->id}' {$selected_attr}>{$indent}{$category->name}</option>";
            
            // Get children
            $options .= $this->get_category_options($selected, $category->id, $level + 1);
        }
        
        return $options;
    }
    
    /**
     * Get product count for a category
     */
    public function get_category_product_count($category_id) {
        global $wpdb;
        
        // Check if products table exists
        $products_table = $wpdb->prefix . 'zpos_products';
        $table_exists = $wpdb->get_var($wpdb->prepare(
            "SHOW TABLES LIKE %s",
            $products_table
        ));
        
        if (!$table_exists) {
            return 0;
        }
        
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $products_table WHERE category_id = %d",
            $category_id
        ));
        
        return intval($count);
    }
}
