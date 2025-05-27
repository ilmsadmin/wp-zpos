<?php
/**
 * Fired during plugin activation
 *
 * @link       https://yourwebsite.com
 * @since      1.0.0
 *
 * @package    ZPOS
 * @subpackage ZPOS/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    ZPOS
 * @subpackage ZPOS/includes
 * @author     Your Name <your.email@example.com>
 */
class ZPOS_Activator {
    
    /**
     * Short Description. (use period)
     *
     * Long Description.
     *
     * @since    1.0.0
     */    public static function activate() {
        // Create database tables
        self::create_tables();
        
        // Run database migrations for existing installations
        self::migrate_database();
        
        // Set default options
        self::set_default_options();
        
        // Add name column to customers table if it doesn't exist
        self::add_name_column_to_customers_table();
        
        // Add package_id column to warranties table if it doesn't exist
        self::add_package_id_column_to_warranties_table();
        
        // Set plugin version
        update_option('zpos_version', ZPOS_VERSION);
        
        // Mark setup as not completed to trigger wizard
        update_option('zpos_setup_completed', false);
        
        // Set activation redirect flag
        add_option('zpos_activation_redirect', true);
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Create plugin database tables.
     *
     * @since    1.0.0
     */
    private static function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        // Products table
        $table_products = $wpdb->prefix . 'zpos_products';
        $sql_products = "CREATE TABLE $table_products (
            id int(11) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            description text,
            short_description text,
            price decimal(10,2) NOT NULL DEFAULT 0.00,
            sale_price decimal(10,2) DEFAULT NULL,
            cost_price decimal(10,2) NOT NULL DEFAULT 0.00,
            sku varchar(100),
            barcode varchar(100),
            category_id int(11),
            stock_quantity int(11) NOT NULL DEFAULT 0,
            manage_stock tinyint(1) NOT NULL DEFAULT 1,
            stock_status varchar(20) DEFAULT 'instock',
            image_url varchar(500),
            gallery_images text,
            weight varchar(50),
            dimensions varchar(100),
            tax_status varchar(20) DEFAULT 'taxable',
            tax_class varchar(100),
            featured tinyint(1) NOT NULL DEFAULT 0,
            attributes text,
            meta_data text,
            status varchar(20) NOT NULL DEFAULT 'active',
            woocommerce_id int(11),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_sku (sku),
            KEY idx_barcode (barcode),
            KEY idx_category (category_id),
            KEY idx_status (status),
            KEY idx_woocommerce (woocommerce_id),
            KEY idx_featured (featured)
        ) $charset_collate;";        // Categories table
        $table_categories = $wpdb->prefix . 'zpos_product_categories';
        $sql_categories = "CREATE TABLE $table_categories (
            id int(11) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            slug varchar(255) UNIQUE,
            description text,
            parent_id int(11) DEFAULT NULL,
            image_url varchar(500),
            status varchar(20) NOT NULL DEFAULT 'active',
            sort_order int(11) DEFAULT 0,
            meta_data text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_parent (parent_id),
            KEY idx_status (status),
            KEY idx_sort_order (sort_order)
        ) $charset_collate;";        // Customers table
        $table_customers = $wpdb->prefix . 'zpos_customers';
        $sql_customers = "CREATE TABLE $table_customers (
            id int(11) NOT NULL AUTO_INCREMENT,
            first_name varchar(100) NOT NULL,
            last_name varchar(100) NOT NULL,
            name varchar(255),
            email varchar(100),
            phone varchar(20),
            address text,
            city varchar(100),
            state varchar(100),
            postal_code varchar(20),
            country varchar(50),
            customer_group varchar(50) DEFAULT 'general',
            notes text,
            woocommerce_id int(11),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_email (email),
            KEY idx_phone (phone),
            KEY idx_woocommerce (woocommerce_id)
        ) $charset_collate;";

        // Orders table
        $table_orders = $wpdb->prefix . 'zpos_orders';
        $sql_orders = "CREATE TABLE $table_orders (
            id int(11) NOT NULL AUTO_INCREMENT,
            order_number varchar(50) NOT NULL,
            customer_id int(11),
            subtotal decimal(10,2) NOT NULL DEFAULT 0.00,
            tax_amount decimal(10,2) NOT NULL DEFAULT 0.00,
            discount_amount decimal(10,2) NOT NULL DEFAULT 0.00,
            total_amount decimal(10,2) NOT NULL DEFAULT 0.00,
            payment_method varchar(50),
            payment_status varchar(20) NOT NULL DEFAULT 'pending',
            order_status varchar(20) NOT NULL DEFAULT 'processing',
            notes text,
            woocommerce_id int(11),
            created_by int(11),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY idx_order_number (order_number),
            KEY idx_customer (customer_id),
            KEY idx_payment_status (payment_status),
            KEY idx_order_status (order_status),
            KEY idx_woocommerce (woocommerce_id),
            KEY idx_created_by (created_by)
        ) $charset_collate;";

        // Order items table
        $table_order_items = $wpdb->prefix . 'zpos_order_items';
        $sql_order_items = "CREATE TABLE $table_order_items (
            id int(11) NOT NULL AUTO_INCREMENT,
            order_id int(11) NOT NULL,
            product_id int(11) NOT NULL,
            product_name varchar(255) NOT NULL,
            price decimal(10,2) NOT NULL DEFAULT 0.00,
            quantity int(11) NOT NULL DEFAULT 1,
            line_total decimal(10,2) NOT NULL DEFAULT 0.00,
            serial_number varchar(100),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_order (order_id),
            KEY idx_product (product_id),
            KEY idx_serial (serial_number)
        ) $charset_collate;";

        // Inventory table
        $table_inventory = $wpdb->prefix . 'zpos_inventory';
        $sql_inventory = "CREATE TABLE $table_inventory (
            id int(11) NOT NULL AUTO_INCREMENT,
            product_id int(11) NOT NULL,
            product_name varchar(255) NOT NULL,
            sku varchar(100),
            barcode varchar(100),
            category varchar(255),
            current_stock int(11) NOT NULL DEFAULT 0,
            quantity_on_hand int(11) NOT NULL DEFAULT 0,
            quantity_reserved int(11) NOT NULL DEFAULT 0,
            quantity_available int(11) NOT NULL DEFAULT 0,
            low_stock_threshold int(11) NOT NULL DEFAULT 5,
            unit_price decimal(10,2) NOT NULL DEFAULT 0.00,
            cost_per_unit decimal(10,2) NOT NULL DEFAULT 0.00,
            total_value decimal(10,2) NOT NULL DEFAULT 0.00,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            last_updated datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY idx_product (product_id),
            KEY idx_sku (sku),
            KEY idx_barcode (barcode),
            KEY idx_category (category),
            KEY idx_low_stock (low_stock_threshold),
            KEY idx_current_stock (current_stock)
        ) $charset_collate;";

        // Inventory movements table
        $table_inventory_movements = $wpdb->prefix . 'zpos_inventory_movements';
        $sql_inventory_movements = "CREATE TABLE $table_inventory_movements (
            id int(11) NOT NULL AUTO_INCREMENT,
            product_id int(11) NOT NULL,
            product_name varchar(255) NOT NULL,
            sku varchar(100),
            type varchar(50) NOT NULL,
            quantity_change int(11) NOT NULL,
            stock_before int(11) NOT NULL DEFAULT 0,
            stock_after int(11) NOT NULL DEFAULT 0,
            reason text,
            user_id int(11),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_product (product_id),
            KEY idx_sku (sku),
            KEY idx_type (type),
            KEY idx_user (user_id),
            KEY idx_created_at (created_at)
        ) $charset_collate;";

        // Warranty packages table
        $table_warranty_packages = $wpdb->prefix . 'zpos_warranty_packages';
        $sql_warranty_packages = "CREATE TABLE $table_warranty_packages (
            id int(11) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            description text,
            duration_months int(11) NOT NULL,
            price decimal(10,2) NOT NULL DEFAULT 0.00,
            status varchar(20) NOT NULL DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_status (status)
        ) $charset_collate;";        // Warranty table
        $table_warranty = $wpdb->prefix . 'zpos_warranties';
        $sql_warranty = "CREATE TABLE $table_warranty (
            id int(11) NOT NULL AUTO_INCREMENT,
            product_id int(11) NOT NULL,
            customer_id int(11) NOT NULL,
            order_id int(11),
            warranty_package_id int(11) NOT NULL,
            package_id int(11) NOT NULL,
            serial_number varchar(100) NOT NULL,
            purchase_date date NOT NULL,
            warranty_start_date date NOT NULL,
            warranty_end_date date NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'active',
            notes text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY idx_serial (serial_number),
            KEY idx_product (product_id),
            KEY idx_customer (customer_id),
            KEY idx_order (order_id),
            KEY idx_warranty_package (warranty_package_id),
            KEY idx_package (package_id),
            KEY idx_status (status),
            KEY idx_warranty_dates (warranty_start_date, warranty_end_date)
        ) $charset_collate;";

        // Settings table
        $table_settings = $wpdb->prefix . 'zpos_settings';
        $sql_settings = "CREATE TABLE $table_settings (
            id int(11) NOT NULL AUTO_INCREMENT,
            setting_key varchar(255) NOT NULL,
            setting_value longtext,
            autoload varchar(20) NOT NULL DEFAULT 'yes',
            PRIMARY KEY (id),
            UNIQUE KEY idx_setting_key (setting_key)
        ) $charset_collate;";        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        dbDelta($sql_categories);
        dbDelta($sql_products);
        dbDelta($sql_customers);
        dbDelta($sql_orders);
        dbDelta($sql_order_items);
        dbDelta($sql_inventory);
        dbDelta($sql_inventory_movements);
        dbDelta($sql_warranty_packages);
        dbDelta($sql_warranty);
        dbDelta($sql_settings);
        
        // Create default categories with slugs
        self::create_default_categories();
    }

    /**
     * Migrate database schema for existing installations.
     *
     * @since    1.0.0
     */
    private static function migrate_database() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();        // Check if old categories table exists and rename it
        $old_categories_table = $wpdb->prefix . 'zpos_categories';
        $new_categories_table = $wpdb->prefix . 'zpos_product_categories';
        
        if ($wpdb->get_var("SHOW TABLES LIKE '$old_categories_table'") && 
            !$wpdb->get_var("SHOW TABLES LIKE '$new_categories_table'")) {
            $wpdb->query("RENAME TABLE `$old_categories_table` TO `$new_categories_table`");
        }

        // Check if old warranty table exists and rename it
        $old_warranty_table = $wpdb->prefix . 'zpos_warranty';
        $new_warranty_table = $wpdb->prefix . 'zpos_warranties';
        
        if ($wpdb->get_var("SHOW TABLES LIKE '$old_warranty_table'") && 
            !$wpdb->get_var("SHOW TABLES LIKE '$new_warranty_table'")) {
            $wpdb->query("RENAME TABLE `$old_warranty_table` TO `$new_warranty_table`");
        }

        // Add missing columns to categories table
        if ($wpdb->get_var("SHOW TABLES LIKE '$new_categories_table'")) {
            $columns = $wpdb->get_col("DESCRIBE $new_categories_table");
            
            // Add slug column if missing
            if (!in_array('slug', $columns)) {
                $wpdb->query("ALTER TABLE $new_categories_table ADD COLUMN slug varchar(255) UNIQUE AFTER name");
                
                // Generate slugs for existing categories
                $categories = $wpdb->get_results("SELECT id, name FROM $new_categories_table WHERE slug IS NULL OR slug = ''");
                foreach ($categories as $category) {
                    $slug = sanitize_title($category->name);
                    
                    // Make sure slug is unique
                    $original_slug = $slug;
                    $counter = 1;
                    while ($wpdb->get_var($wpdb->prepare(
                        "SELECT id FROM $new_categories_table WHERE slug = %s AND id != %d",
                        $slug, $category->id
                    ))) {
                        $slug = $original_slug . '-' . $counter;
                        $counter++;
                    }
                    
                    $wpdb->update(
                        $new_categories_table,
                        array('slug' => $slug),
                        array('id' => $category->id),
                        array('%s'),
                        array('%d')
                    );
                }
            }
            
            // Add image_url column if missing
            if (!in_array('image_url', $columns)) {
                $wpdb->query("ALTER TABLE $new_categories_table ADD COLUMN image_url varchar(500) AFTER parent_id");
            }
            
            // Add sort_order column if missing
            if (!in_array('sort_order', $columns)) {
                $wpdb->query("ALTER TABLE $new_categories_table ADD COLUMN sort_order int(11) DEFAULT 0 AFTER status");
            }
            
            // Add meta_data column if missing
            if (!in_array('meta_data', $columns)) {
                $wpdb->query("ALTER TABLE $new_categories_table ADD COLUMN meta_data text AFTER sort_order");
            }
            
            // Update parent_id column to allow NULL instead of 0
            $wpdb->query("ALTER TABLE $new_categories_table MODIFY COLUMN parent_id int(11) DEFAULT NULL");
        }

        // Update inventory table schema if it exists but is missing columns
        $inventory_table = $wpdb->prefix . 'zpos_inventory';
        if ($wpdb->get_var("SHOW TABLES LIKE '$inventory_table'")) {
            // Check and add missing columns
            $columns = $wpdb->get_col("DESCRIBE $inventory_table");
            
            if (!in_array('product_name', $columns)) {
                $wpdb->query("ALTER TABLE $inventory_table ADD COLUMN product_name varchar(255) NOT NULL DEFAULT '' AFTER product_id");
            }
            if (!in_array('sku', $columns)) {
                $wpdb->query("ALTER TABLE $inventory_table ADD COLUMN sku varchar(100) AFTER product_name");
            }
            if (!in_array('barcode', $columns)) {
                $wpdb->query("ALTER TABLE $inventory_table ADD COLUMN barcode varchar(100) AFTER sku");
            }
            if (!in_array('category', $columns)) {
                $wpdb->query("ALTER TABLE $inventory_table ADD COLUMN category varchar(255) AFTER barcode");
            }
            if (!in_array('current_stock', $columns)) {
                $wpdb->query("ALTER TABLE $inventory_table ADD COLUMN current_stock int(11) NOT NULL DEFAULT 0 AFTER category");
            }
            if (!in_array('unit_price', $columns)) {
                $wpdb->query("ALTER TABLE $inventory_table ADD COLUMN unit_price decimal(10,2) NOT NULL DEFAULT 0.00 AFTER low_stock_threshold");
            }
            if (!in_array('cost_per_unit', $columns)) {
                $wpdb->query("ALTER TABLE $inventory_table ADD COLUMN cost_per_unit decimal(10,2) NOT NULL DEFAULT 0.00 AFTER unit_price");
            }
            if (!in_array('total_value', $columns)) {
                $wpdb->query("ALTER TABLE $inventory_table ADD COLUMN total_value decimal(10,2) NOT NULL DEFAULT 0.00 AFTER cost_per_unit");
            }
            if (!in_array('updated_at', $columns)) {
                $wpdb->query("ALTER TABLE $inventory_table ADD COLUMN updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER total_value");
            }
            
            // Add indexes if they don't exist
            $indexes = $wpdb->get_results("SHOW INDEX FROM $inventory_table", ARRAY_A);
            $index_names = array_column($indexes, 'Key_name');
            
            if (!in_array('idx_sku', $index_names)) {
                $wpdb->query("ALTER TABLE $inventory_table ADD INDEX idx_sku (sku)");
            }
            if (!in_array('idx_barcode', $index_names)) {
                $wpdb->query("ALTER TABLE $inventory_table ADD INDEX idx_barcode (barcode)");
            }
            if (!in_array('idx_category', $index_names)) {
                $wpdb->query("ALTER TABLE $inventory_table ADD INDEX idx_category (category)");
            }
            if (!in_array('idx_current_stock', $index_names)) {
                $wpdb->query("ALTER TABLE $inventory_table ADD INDEX idx_current_stock (current_stock)");
            }
        }
        
        // Add missing columns to products table
        $products_table = $wpdb->prefix . 'zpos_products';
        if ($wpdb->get_var("SHOW TABLES LIKE '$products_table'")) {
            $columns = $wpdb->get_col("DESCRIBE $products_table");
            if (!in_array('short_description', $columns)) {
                $wpdb->query("ALTER TABLE $products_table ADD COLUMN short_description text AFTER description");
            }
            // Add missing sale_price column to products table
            if (!in_array('sale_price', $columns)) {
                $wpdb->query("ALTER TABLE $products_table ADD COLUMN sale_price decimal(10,2) DEFAULT NULL AFTER price");
            }
            // Add missing stock_status column to products table
            if (!in_array('stock_status', $columns)) {
                $wpdb->query("ALTER TABLE $products_table ADD COLUMN stock_status varchar(20) DEFAULT 'instock' AFTER manage_stock");
            }
            // Add missing gallery_images column
            if (!in_array('gallery_images', $columns)) {
                $wpdb->query("ALTER TABLE $products_table ADD COLUMN gallery_images text AFTER image_url");
            }
            // Add missing weight column
            if (!in_array('weight', $columns)) {
                $wpdb->query("ALTER TABLE $products_table ADD COLUMN weight varchar(50) AFTER gallery_images");
            }
            // Add missing dimensions column
            if (!in_array('dimensions', $columns)) {
                $wpdb->query("ALTER TABLE $products_table ADD COLUMN dimensions varchar(100) AFTER weight");
            }
            // Add missing tax_status column
            if (!in_array('tax_status', $columns)) {
                $wpdb->query("ALTER TABLE $products_table ADD COLUMN tax_status varchar(20) DEFAULT 'taxable' AFTER dimensions");
            }
            // Add missing tax_class column
            if (!in_array('tax_class', $columns)) {
                $wpdb->query("ALTER TABLE $products_table ADD COLUMN tax_class varchar(100) AFTER tax_status");
            }
            // Add missing featured column
            if (!in_array('featured', $columns)) {
                $wpdb->query("ALTER TABLE $products_table ADD COLUMN featured tinyint(1) NOT NULL DEFAULT 0 AFTER status");
            }
            // Add missing attributes column
            if (!in_array('attributes', $columns)) {
                $wpdb->query("ALTER TABLE $products_table ADD COLUMN attributes text AFTER featured");
            }
            // Add missing meta_data column
            if (!in_array('meta_data', $columns)) {
                $wpdb->query("ALTER TABLE $products_table ADD COLUMN meta_data text AFTER attributes");
            }
            // Rename cost to cost_price if needed
            if (in_array('cost', $columns) && !in_array('cost_price', $columns)) {
                $wpdb->query("ALTER TABLE $products_table CHANGE COLUMN cost cost_price decimal(10,2) NOT NULL DEFAULT 0.00");
            } else if (!in_array('cost', $columns) && !in_array('cost_price', $columns)) {
                $wpdb->query("ALTER TABLE $products_table ADD COLUMN cost_price decimal(10,2) NOT NULL DEFAULT 0.00 AFTER sale_price");
            }
        }
          // Add missing columns to categories table
        $categories_table = $wpdb->prefix . 'zpos_product_categories';
        if ($wpdb->get_var("SHOW TABLES LIKE '$categories_table'")) {
            $columns = $wpdb->get_col("DESCRIBE $categories_table");
            if (!in_array('slug', $columns)) {
                $wpdb->query("ALTER TABLE $categories_table ADD COLUMN slug varchar(255) UNIQUE AFTER name");
                
                // Generate slugs for existing categories
                $existing_categories = $wpdb->get_results("SELECT id, name FROM $categories_table WHERE slug IS NULL OR slug = ''");
                foreach ($existing_categories as $category) {
                    $slug = sanitize_title($category->name);
                    // Ensure unique slug
                    $original_slug = $slug;
                    $counter = 1;
                    while ($wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $categories_table WHERE slug = %s AND id != %d", $slug, $category->id))) {
                        $slug = $original_slug . '-' . $counter;
                        $counter++;
                    }
                    $wpdb->update($categories_table, array('slug' => $slug), array('id' => $category->id));
                }
            }
        }        // Add missing columns to customers table
        $customers_table = $wpdb->prefix . 'zpos_customers';
        if ($wpdb->get_var("SHOW TABLES LIKE '$customers_table'")) {
            $columns = $wpdb->get_col("DESCRIBE $customers_table");
            if (!in_array('customer_group', $columns)) {
                $wpdb->query("ALTER TABLE $customers_table ADD COLUMN customer_group varchar(50) DEFAULT 'general' AFTER country");
            }
            if (!in_array('name', $columns)) {
                $wpdb->query("ALTER TABLE $customers_table ADD COLUMN name varchar(255) AFTER last_name");
                $wpdb->query("UPDATE $customers_table SET name = CONCAT(first_name, ' ', last_name) WHERE name IS NULL OR name = ''");
            }
        }
          // Add missing columns to warranties table
        $warranties_table = $wpdb->prefix . 'zpos_warranties';
        if ($wpdb->get_var("SHOW TABLES LIKE '$warranties_table'")) {
            $columns = $wpdb->get_col("DESCRIBE $warranties_table");
            if (!in_array('package_id', $columns)) {
                $wpdb->query("ALTER TABLE $warranties_table ADD COLUMN package_id int(11) NOT NULL DEFAULT 0 AFTER warranty_package_id");
                $wpdb->query("UPDATE $warranties_table SET package_id = warranty_package_id");
                $wpdb->query("ALTER TABLE $warranties_table ADD KEY idx_package (package_id)");
                error_log("ZPOS Migration: Added 'package_id' column to warranties table");
            } else {
                // Make sure all warranties have package_id set correctly
                $wpdb->query("UPDATE $warranties_table SET package_id = warranty_package_id WHERE package_id = 0 OR package_id IS NULL");
            }
        }
        
        // Fix order_items table - rename product_price to price if needed
        $order_items_table = $wpdb->prefix . 'zpos_order_items';
        if ($wpdb->get_var("SHOW TABLES LIKE '$order_items_table'")) {
            $columns = $wpdb->get_col("DESCRIBE $order_items_table");
            if (in_array('product_price', $columns) && !in_array('price', $columns)) {
                $wpdb->query("ALTER TABLE $order_items_table CHANGE COLUMN product_price price decimal(10,2) NOT NULL DEFAULT 0.00");
            } else if (!in_array('product_price', $columns) && !in_array('price', $columns)) {
                $wpdb->query("ALTER TABLE $order_items_table ADD COLUMN price decimal(10,2) NOT NULL DEFAULT 0.00 AFTER product_name");
            }
        }

        // Create inventory movements table if it doesn't exist
        $movements_table = $wpdb->prefix . 'zpos_inventory_movements';
        if (!$wpdb->get_var("SHOW TABLES LIKE '$movements_table'")) {
            $sql_movements = "CREATE TABLE $movements_table (
                id int(11) NOT NULL AUTO_INCREMENT,
                product_id int(11) NOT NULL,
                product_name varchar(255) NOT NULL,
                sku varchar(100),
                type varchar(50) NOT NULL,
                quantity_change int(11) NOT NULL,
                stock_before int(11) NOT NULL DEFAULT 0,
                stock_after int(11) NOT NULL DEFAULT 0,
                reason text,
                user_id int(11),
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_product (product_id),
                KEY idx_sku (sku),
                KEY idx_type (type),
                KEY idx_user (user_id),
                KEY idx_created_at (created_at)
            ) $charset_collate;";
            
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql_movements);
        }

        // Populate missing data in inventory records after migration
        require_once plugin_dir_path(__FILE__) . 'inventory.php';
        if (class_exists('Zpos_Inventory')) {
            $inventory = new Zpos_Inventory();
            $updated_count = $inventory->populate_missing_inventory_data();
            if ($updated_count > 0) {
                error_log("ZPOS Migration: Updated $updated_count inventory records with missing data");
            }
        }
    }

    /**
     * Set default plugin options.
     *
     * @since    1.0.0
     */
    private static function set_default_options() {
        $default_options = array(
            'zpos_setup_completed' => false,
            'zpos_woocommerce_sync_enabled' => false,
            'zpos_currency' => 'USD',
            'zpos_currency_symbol' => '$',
            'zpos_currency_position' => 'left',
            'zpos_thousand_separator' => ',',
            'zpos_decimal_separator' => '.',
            'zpos_number_of_decimals' => 2,
            'zpos_timezone' => 'UTC',
            'zpos_store_name' => get_bloginfo('name'),
            'zpos_store_address' => '',
            'zpos_store_phone' => '',
            'zpos_store_email' => get_option('admin_email'),
            'zpos_low_stock_threshold' => 5,
            'zpos_tax_rate' => 0,
            'zpos_receipt_template' => 'default',
            'zpos_email_notifications' => true,
            'zpos_keep_data_on_uninstall' => false
        );

        foreach ($default_options as $option_name => $option_value) {
            if (get_option($option_name) === false) {
                add_option($option_name, $option_value);
            }
        }
    }    /**
     * Create default warranty packages.
     *
     * @since    1.0.0
     */
    private static function create_default_warranty_packages() {
        global $wpdb;

        $table_warranty_packages = $wpdb->prefix . 'zpos_warranty_packages';

        $default_packages = array(
            array(
                'name' => __('6 Months Warranty', 'zpos'),
                'description' => __('Standard 6 months warranty coverage', 'zpos'),
                'duration_months' => 6,
                'price' => 0.00,
                'status' => 'active'
            ),
            array(
                'name' => __('1 Year Warranty', 'zpos'),
                'description' => __('Extended 1 year warranty coverage', 'zpos'),
                'duration_months' => 12,
                'price' => 0.00,
                'status' => 'active'
            ),
            array(
                'name' => __('2 Years Warranty', 'zpos'),
                'description' => __('Premium 2 years warranty coverage', 'zpos'),
                'duration_months' => 24,
                'price' => 0.00,
                'status' => 'active'
            )
        );

        foreach ($default_packages as $package) {
            $wpdb->insert($table_warranty_packages, $package);
        }
    }

    /**
     * Create default product categories.
     *
     * @since    1.0.0
     */
    private static function create_default_categories() {
        global $wpdb;

        $table_categories = $wpdb->prefix . 'zpos_product_categories';

        $default_categories = array(
            array(
                'name' => 'Electronics',
                'slug' => 'electronics',
                'description' => 'Electronic devices and accessories',
                'parent_id' => null,
                'status' => 'active',
                'sort_order' => 1
            ),
            array(
                'name' => 'Clothing',
                'slug' => 'clothing',
                'description' => 'Apparel and fashion items',
                'parent_id' => null,
                'status' => 'active',
                'sort_order' => 2
            ),
            array(
                'name' => 'Books',
                'slug' => 'books',
                'description' => 'Books and publications',
                'parent_id' => null,
                'status' => 'active',
                'sort_order' => 3
            ),
            array(
                'name' => 'Food & Beverages',
                'slug' => 'food-beverages',
                'description' => 'Food and drink items',
                'parent_id' => null,
                'status' => 'active',
                'sort_order' => 4
            ),
            array(
                'name' => 'Home & Garden',
                'slug' => 'home-garden',
                'description' => 'Home and garden products',
                'parent_id' => null,
                'status' => 'active',
                'sort_order' => 5
            ),
            array(
                'name' => 'Sports & Outdoors',
                'slug' => 'sports-outdoors',
                'description' => 'Sports and outdoor equipment',
                'parent_id' => null,
                'status' => 'active',
                'sort_order' => 6
            ),
            array(
                'name' => 'Health & Beauty',
                'slug' => 'health-beauty',
                'description' => 'Health and beauty products',
                'parent_id' => null,
                'status' => 'active',
                'sort_order' => 7
            ),
            array(
                'name' => 'Toys & Games',
                'slug' => 'toys-games',
                'description' => 'Toys and games for all ages',
                'parent_id' => null,
                'status' => 'active',
                'sort_order' => 8
            )
        );

        foreach ($default_categories as $category) {
            // Check if category already exists
            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $table_categories WHERE slug = %s",
                $category['slug']
            ));

            if (!$existing) {
                $wpdb->insert(
                    $table_categories,
                    $category,
                    array('%s', '%s', '%s', '%d', '%s', '%d')
                );
            }
        }
    }

    /**
     * Handle activation redirect to setup wizard.
     *
     * @since    1.0.0
     */
    public static function activation_redirect() {
        // Only redirect if flag is set and user has capability
        if (get_option('zpos_activation_redirect', false) && current_user_can('manage_options')) {
            delete_option('zpos_activation_redirect');
            
            // Don't redirect if activating multiple plugins or doing bulk activation
            if (!isset($_GET['activate-multi']) && !wp_doing_ajax()) {
                wp_safe_redirect(admin_url('admin.php?page=zpos-setup'));
                exit;
            }
        }
    }
    
    /**
     * Add the name column to the customers table if it doesn't exist.
     *
     * @since    1.0.0
     */
    private static function add_name_column_to_customers_table() {
        global $wpdb;
        
        $customers_table = $wpdb->prefix . 'zpos_customers';
        
        if ($wpdb->get_var("SHOW TABLES LIKE '$customers_table'")) {
            $columns = $wpdb->get_col("DESCRIBE $customers_table");
            
            // Add name column if it doesn't exist
            if (!in_array('name', $columns)) {
                $wpdb->query("ALTER TABLE $customers_table ADD COLUMN name varchar(255) AFTER last_name");
                
                // Update existing customers to set name as first_name + last_name
                $wpdb->query("UPDATE $customers_table SET name = CONCAT(first_name, ' ', last_name) WHERE name IS NULL OR name = ''");
                
                error_log("ZPOS Migration: Added 'name' column to customers table");
            }
        }
    }
      /**
     * Add the package_id column to the warranties table if it doesn't exist.
     *
     * @since    1.0.0
     */
    private static function add_package_id_column_to_warranties_table() {
        global $wpdb;
        
        $warranties_table = $wpdb->prefix . 'zpos_warranties';
        
        if ($wpdb->get_var("SHOW TABLES LIKE '$warranties_table'")) {
            $columns = $wpdb->get_col("DESCRIBE $warranties_table");
            
            // Add package_id column if it doesn't exist
            if (!in_array('package_id', $columns)) {
                $wpdb->query("ALTER TABLE $warranties_table ADD COLUMN package_id int(11) NOT NULL DEFAULT 0 AFTER warranty_package_id");
                
                // Update existing warranties to set package_id = warranty_package_id
                $wpdb->query("UPDATE $warranties_table SET package_id = warranty_package_id");
                
                // Add index for package_id column
                $wpdb->query("ALTER TABLE $warranties_table ADD KEY idx_package (package_id)");
                
                error_log("ZPOS Migration: Added 'package_id' column to warranties table");
            }
            
            // Ensure all existing warranties have package_id set correctly
            $wpdb->query("UPDATE $warranties_table SET package_id = warranty_package_id WHERE package_id = 0 OR package_id IS NULL");
        }
    }
}
