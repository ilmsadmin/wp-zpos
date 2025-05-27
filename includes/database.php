<?php
/**
 * Database management functionality for ZPOS
 *
 * @link       https://yourwebsite.com
 * @since      1.0.0
 *
 * @package    ZPOS
 * @subpackage ZPOS/includes
 */

/**
 * Database management class.
 *
 * Handles all database operations including table creation,
 * schema updates, and data migrations.
 *
 * @package    ZPOS
 * @subpackage ZPOS/includes
 * @author     Your Name <your.email@example.com>
 */
class ZPOS_Database {    /**
     * Database version for schema management.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $db_version    Current database version.
     */
    private static $db_version = '1.1.0';

    /**
     * Initialize the database.
     *
     * @since    1.0.0
     */
    public static function init() {
        add_action('plugins_loaded', array(__CLASS__, 'check_version'));
    }    /**
     * Check if database needs updating.
     *
     * @since    1.0.0
     */
    public static function check_version() {
        if (get_option('zpos_db_version') !== self::$db_version) {
            self::create_tables();
            self::migrate_existing_tables();
            update_option('zpos_db_version', self::$db_version);
        }
    }

    /**
     * Migrate existing tables to new schema.
     *
     * @since    1.0.0
     */
    public static function migrate_existing_tables() {
        global $wpdb;
        
        $customers_table = $wpdb->prefix . 'zpos_customers';
        
        // Check if customers table exists and migrate schema
        if ($wpdb->get_var("SHOW TABLES LIKE '$customers_table'")) {
            $columns = $wpdb->get_col("DESCRIBE $customers_table");
            
            // Add missing address_line_1 column if it doesn't exist but billing_address_1 does
            if (!in_array('address_line_1', $columns)) {
                if (in_array('billing_address_1', $columns)) {
                    // Rename billing_address_1 to address_line_1
                    $wpdb->query("ALTER TABLE $customers_table CHANGE COLUMN billing_address_1 address_line_1 varchar(255)");
                } else {
                    // Add new column
                    $wpdb->query("ALTER TABLE $customers_table ADD COLUMN address_line_1 varchar(255) AFTER phone");
                }
            }
            
            // Add missing address_line_2 column if it doesn't exist but billing_address_2 does
            if (!in_array('address_line_2', $columns)) {
                if (in_array('billing_address_2', $columns)) {
                    // Rename billing_address_2 to address_line_2
                    $wpdb->query("ALTER TABLE $customers_table CHANGE COLUMN billing_address_2 address_line_2 varchar(255)");
                } else {
                    // Add new column
                    $wpdb->query("ALTER TABLE $customers_table ADD COLUMN address_line_2 varchar(255) AFTER address_line_1");
                }
            }
            
            // Add missing city column if it doesn't exist but billing_city does
            if (!in_array('city', $columns)) {
                if (in_array('billing_city', $columns)) {
                    $wpdb->query("ALTER TABLE $customers_table CHANGE COLUMN billing_city city varchar(100)");
                } else {
                    $wpdb->query("ALTER TABLE $customers_table ADD COLUMN city varchar(100) AFTER address_line_2");
                }
            }
            
            // Add missing state column if it doesn't exist but billing_state does
            if (!in_array('state', $columns)) {
                if (in_array('billing_state', $columns)) {
                    $wpdb->query("ALTER TABLE $customers_table CHANGE COLUMN billing_state state varchar(100)");
                } else {
                    $wpdb->query("ALTER TABLE $customers_table ADD COLUMN state varchar(100) AFTER city");
                }
            }
            
            // Add missing postal_code column if it doesn't exist but billing_postal_code does
            if (!in_array('postal_code', $columns)) {
                if (in_array('billing_postal_code', $columns)) {
                    $wpdb->query("ALTER TABLE $customers_table CHANGE COLUMN billing_postal_code postal_code varchar(20)");
                } else {
                    $wpdb->query("ALTER TABLE $customers_table ADD COLUMN postal_code varchar(20) AFTER state");
                }
            }
            
            // Add missing country column if it doesn't exist but billing_country does
            if (!in_array('country', $columns)) {
                if (in_array('billing_country', $columns)) {
                    $wpdb->query("ALTER TABLE $customers_table CHANGE COLUMN billing_country country varchar(100) DEFAULT 'VN'");
                } else {
                    $wpdb->query("ALTER TABLE $customers_table ADD COLUMN country varchar(100) DEFAULT 'VN' AFTER postal_code");
                }
            }
            
            // Add missing referral_code column
            if (!in_array('referral_code', $columns)) {
                $wpdb->query("ALTER TABLE $customers_table ADD COLUMN referral_code varchar(50) AFTER loyalty_points");
            }
            
            // Add missing columns that might not exist
            $missing_columns = array(
                'discount_percent' => 'ADD COLUMN discount_percent decimal(5,2) DEFAULT 0.00 AFTER customer_group',
                'credit_limit' => 'ADD COLUMN credit_limit decimal(10,2) DEFAULT 0.00 AFTER discount_percent',
                'orders_count' => 'ADD COLUMN orders_count int(11) DEFAULT 0 AFTER total_spent',
                'avatar_url' => 'ADD COLUMN avatar_url varchar(500) AFTER last_order_date',
                'wordpress_user_id' => 'ADD COLUMN wordpress_user_id int(11) DEFAULT NULL AFTER woocommerce_id',
                'loyalty_points' => 'ADD COLUMN loyalty_points int(11) DEFAULT 0 AFTER wordpress_user_id',
                'referred_by' => 'ADD COLUMN referred_by int(11) DEFAULT NULL AFTER referral_code',
                'tags' => 'ADD COLUMN tags text AFTER referred_by',
                'meta_data' => 'ADD COLUMN meta_data text AFTER tags'
            );
            
            foreach ($missing_columns as $column => $sql) {
                if (!in_array($column, $columns)) {
                    $wpdb->query("ALTER TABLE $customers_table $sql");
                }
            }
            
            // Rename order_count to orders_count if needed
            if (in_array('order_count', $columns) && !in_array('orders_count', $columns)) {
                $wpdb->query("ALTER TABLE $customers_table CHANGE COLUMN order_count orders_count int(11) DEFAULT 0");
            }
            
            // Add unique index for referral_code if it doesn't exist
            $indices = $wpdb->get_results("SHOW INDEX FROM $customers_table");
            $index_names = array();
            foreach ($indices as $index) {
                $index_names[] = $index->Key_name;
            }
            
            if (!in_array('idx_referral_code', $index_names)) {
                $wpdb->query("ALTER TABLE $customers_table ADD UNIQUE KEY idx_referral_code (referral_code)");
            }
        }
    }/**
     * Create all required database tables.
     *
     * @since    1.0.0
     */
    public static function create_tables() {
        self::create_categories_table();
        self::create_product_categories_table();
        self::create_products_table();
        self::create_customers_table();
        self::create_orders_table();
        self::create_order_items_table();
        self::create_inventory_table();
        self::create_warranty_packages_table();
        self::create_warranty_table();
        self::create_settings_table();
        
        // Initialize class instances to create their tables
        require_once ZPOS_PLUGIN_DIR . 'includes/products.php';
        require_once ZPOS_PLUGIN_DIR . 'includes/customers.php';
        require_once ZPOS_PLUGIN_DIR . 'includes/product-categories.php';
        
        $products = new ZPOS_Products();
        $products->create_table();
        
        $customers = new ZPOS_Customers();
        $customers->create_table();
        
        $categories = new ZPOS_Product_Categories();
        $categories->create_table();
    }

    /**
     * Create categories table.
     *
     * @since    1.0.0
     */
    public static function create_categories_table() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'zpos_categories';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id int(11) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            description text,
            parent_id int(11) DEFAULT 0,
            image_url varchar(500),
            status varchar(20) NOT NULL DEFAULT 'active',
            sort_order int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_parent (parent_id),
            KEY idx_status (status),
            KEY idx_sort (sort_order)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Create product categories table.
     *
     * @since    1.0.0
     */
    public static function create_product_categories_table() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'zpos_product_categories';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
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
    }

    /**
     * Create products table.
     *
     * @since    1.0.0
     */
    public static function create_products_table() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'zpos_products';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id int(11) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            description text,
            short_description text,
            price decimal(10,2) NOT NULL DEFAULT 0.00,
            sale_price decimal(10,2),
            cost decimal(10,2) NOT NULL DEFAULT 0.00,
            sku varchar(100),
            barcode varchar(100),
            category_id int(11),
            stock_quantity int(11) NOT NULL DEFAULT 0,
            manage_stock tinyint(1) NOT NULL DEFAULT 1,
            stock_status varchar(20) NOT NULL DEFAULT 'instock',
            weight decimal(8,2),
            dimensions varchar(255),
            image_url varchar(500),
            gallery text,
            status varchar(20) NOT NULL DEFAULT 'active',
            featured tinyint(1) NOT NULL DEFAULT 0,
            tax_status varchar(20) NOT NULL DEFAULT 'taxable',
            tax_class varchar(50),
            woocommerce_id int(11),
            sync_status varchar(20) DEFAULT 'none',
            last_sync datetime,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY idx_sku (sku),
            KEY idx_barcode (barcode),
            KEY idx_category (category_id),
            KEY idx_status (status),
            KEY idx_stock_status (stock_status),
            KEY idx_woocommerce (woocommerce_id),
            KEY idx_sync_status (sync_status),
            KEY idx_featured (featured)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Create customers table.
     *
     * @since    1.0.0
     */
    public static function create_customers_table() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'zpos_customers';
        $charset_collate = $wpdb->get_charset_collate();        $sql = "CREATE TABLE $table_name (
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
            UNIQUE KEY idx_email (email),
            UNIQUE KEY idx_referral_code (referral_code),
            KEY idx_phone (phone),
            KEY idx_full_name (first_name, last_name),
            KEY idx_customer_group (customer_group),
            KEY idx_status (status),
            KEY idx_woocommerce (woocommerce_id),
            KEY idx_wordpress_user (wordpress_user_id),
            KEY idx_total_spent (total_spent),
            KEY idx_orders_count (orders_count),
            KEY idx_loyalty_points (loyalty_points),
            KEY idx_referred_by (referred_by)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Create orders table.
     *
     * @since    1.0.0
     */
    public static function create_orders_table() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'zpos_orders';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id int(11) NOT NULL AUTO_INCREMENT,
            order_number varchar(50) NOT NULL,
            customer_id int(11),
            customer_name varchar(255),
            customer_email varchar(100),
            customer_phone varchar(20),
            billing_address text,
            shipping_address text,
            subtotal decimal(10,2) NOT NULL DEFAULT 0.00,
            tax_amount decimal(10,2) NOT NULL DEFAULT 0.00,
            tax_rate decimal(5,2) NOT NULL DEFAULT 0.00,
            discount_amount decimal(10,2) NOT NULL DEFAULT 0.00,
            discount_type varchar(20),
            shipping_amount decimal(10,2) NOT NULL DEFAULT 0.00,
            total_amount decimal(10,2) NOT NULL DEFAULT 0.00,
            payment_method varchar(50),
            payment_status varchar(20) NOT NULL DEFAULT 'pending',
            transaction_id varchar(100),
            order_status varchar(20) NOT NULL DEFAULT 'processing',
            order_type varchar(20) NOT NULL DEFAULT 'pos',
            currency varchar(10) NOT NULL DEFAULT 'USD',
            exchange_rate decimal(10,4) NOT NULL DEFAULT 1.0000,
            notes text,
            internal_notes text,
            refund_amount decimal(10,2) NOT NULL DEFAULT 0.00,
            refund_reason text,
            source varchar(20) NOT NULL DEFAULT 'pos',
            woocommerce_id int(11),
            sync_status varchar(20) DEFAULT 'none',
            last_sync datetime,
            created_by int(11),
            updated_by int(11),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY idx_order_number (order_number),
            KEY idx_customer (customer_id),
            KEY idx_customer_email (customer_email),
            KEY idx_customer_phone (customer_phone),
            KEY idx_payment_status (payment_status),
            KEY idx_order_status (order_status),
            KEY idx_order_type (order_type),
            KEY idx_source (source),
            KEY idx_woocommerce (woocommerce_id),
            KEY idx_sync_status (sync_status),
            KEY idx_created_by (created_by),
            KEY idx_date_range (created_at),
            KEY idx_total_amount (total_amount)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Create order items table.
     *
     * @since    1.0.0
     */
    public static function create_order_items_table() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'zpos_order_items';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id int(11) NOT NULL AUTO_INCREMENT,
            order_id int(11) NOT NULL,
            product_id int(11) NOT NULL,
            product_name varchar(255) NOT NULL,
            product_sku varchar(100),
            product_price decimal(10,2) NOT NULL DEFAULT 0.00,
            quantity int(11) NOT NULL DEFAULT 1,
            line_subtotal decimal(10,2) NOT NULL DEFAULT 0.00,
            line_tax decimal(10,2) NOT NULL DEFAULT 0.00,
            line_total decimal(10,2) NOT NULL DEFAULT 0.00,
            serial_number varchar(100),
            warranty_package_id int(11),
            warranty_start_date date,
            warranty_end_date date,
            notes text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_order (order_id),
            KEY idx_product (product_id),
            KEY idx_product_sku (product_sku),
            KEY idx_serial (serial_number),
            KEY idx_warranty_package (warranty_package_id),
            KEY idx_warranty_dates (warranty_start_date, warranty_end_date)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Create inventory table.
     *
     * @since    1.0.0
     */
    public static function create_inventory_table() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'zpos_inventory';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id int(11) NOT NULL AUTO_INCREMENT,
            product_id int(11) NOT NULL,
            quantity_on_hand int(11) NOT NULL DEFAULT 0,
            quantity_reserved int(11) NOT NULL DEFAULT 0,
            quantity_available int(11) NOT NULL DEFAULT 0,
            quantity_sold int(11) NOT NULL DEFAULT 0,
            low_stock_threshold int(11) NOT NULL DEFAULT 5,
            reorder_point int(11) NOT NULL DEFAULT 10,
            reorder_quantity int(11) NOT NULL DEFAULT 20,
            location varchar(100),
            cost_per_unit decimal(10,2) NOT NULL DEFAULT 0.00,
            last_cost decimal(10,2) NOT NULL DEFAULT 0.00,
            average_cost decimal(10,2) NOT NULL DEFAULT 0.00,
            total_value decimal(10,2) NOT NULL DEFAULT 0.00,
            last_purchase_date datetime,
            last_sale_date datetime,
            last_count_date datetime,
            status varchar(20) NOT NULL DEFAULT 'active',
            notes text,
            last_updated datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY idx_product (product_id),
            KEY idx_quantity_available (quantity_available),
            KEY idx_low_stock (low_stock_threshold),
            KEY idx_status (status),
            KEY idx_location (location)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Create warranty packages table.
     *
     * @since    1.0.0
     */
    public static function create_warranty_packages_table() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'zpos_warranty_packages';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id int(11) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            description text,
            duration_months int(11) NOT NULL,
            duration_days int(11) NOT NULL DEFAULT 0,
            price decimal(10,2) NOT NULL DEFAULT 0.00,
            terms_conditions text,
            coverage_details text,
            exclusions text,
            status varchar(20) NOT NULL DEFAULT 'active',
            is_default tinyint(1) NOT NULL DEFAULT 0,
            sort_order int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_status (status),
            KEY idx_is_default (is_default),
            KEY idx_sort (sort_order),
            KEY idx_duration (duration_months, duration_days)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Create warranty table.
     *
     * @since    1.0.0
     */
    public static function create_warranty_table() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'zpos_warranties';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id int(11) NOT NULL AUTO_INCREMENT,
            product_id int(11) NOT NULL,
            customer_id int(11) NOT NULL,
            order_id int(11),
            order_item_id int(11),
            warranty_package_id int(11) NOT NULL,
            serial_number varchar(100) NOT NULL,
            purchase_date date NOT NULL,
            warranty_start_date date NOT NULL,
            warranty_end_date date NOT NULL,
            activation_date datetime,
            registration_number varchar(100),
            status varchar(20) NOT NULL DEFAULT 'active',
            claim_count int(11) NOT NULL DEFAULT 0,
            last_claim_date datetime,
            repair_history text,
            replacement_history text,
            notes text,
            customer_notes text,
            technician_notes text,
            warranty_terms text,
            created_by int(11),
            activated_by int(11),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY idx_serial (serial_number),
            KEY idx_product (product_id),
            KEY idx_customer (customer_id),
            KEY idx_order (order_id),
            KEY idx_order_item (order_item_id),
            KEY idx_warranty_package (warranty_package_id),
            KEY idx_registration_number (registration_number),
            KEY idx_status (status),
            KEY idx_warranty_dates (warranty_start_date, warranty_end_date),
            KEY idx_purchase_date (purchase_date),
            KEY idx_created_by (created_by)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Create settings table.
     *
     * @since    1.0.0
     */
    public static function create_settings_table() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'zpos_settings';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id int(11) NOT NULL AUTO_INCREMENT,
            setting_group varchar(50) NOT NULL DEFAULT 'general',
            setting_key varchar(255) NOT NULL,
            setting_value longtext,
            setting_type varchar(20) NOT NULL DEFAULT 'string',
            is_serialized tinyint(1) NOT NULL DEFAULT 0,
            autoload varchar(20) NOT NULL DEFAULT 'yes',
            description text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY idx_setting_key (setting_key),
            KEY idx_setting_group (setting_group),
            KEY idx_autoload (autoload),
            KEY idx_setting_type (setting_type)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Backup all ZPOS data before deactivation.
     *
     * @since    1.0.0
     */    public static function backup_data() {
        global $wpdb;

        $backup_data = array();
        $tables = array(
            'zpos_categories',
            'zpos_products',
            'zpos_customers',
            'zpos_orders',
            'zpos_order_items',
            'zpos_inventory',
            'zpos_warranty_packages',
            'zpos_warranties',
            'zpos_settings'
        );

        foreach ($tables as $table) {
            $table_name = $wpdb->prefix . $table;
            $results = $wpdb->get_results("SELECT * FROM {$table_name}", ARRAY_A);
            if ($results) {
                $backup_data[$table] = $results;
            }
        }

        // Save backup as WordPress option
        if (!empty($backup_data)) {
            update_option('zpos_backup_data', $backup_data);
            update_option('zpos_backup_timestamp', current_time('mysql'));
        }

        return !empty($backup_data);
    }

    /**
     * Restore data from backup.
     *
     * @since    1.0.0
     */
    public static function restore_data() {
        $backup_data = get_option('zpos_backup_data');
        if (empty($backup_data)) {
            return false;
        }

        global $wpdb;

        foreach ($backup_data as $table => $data) {
            $table_name = $wpdb->prefix . $table;
            
            // Clear existing data
            $wpdb->query("TRUNCATE TABLE {$table_name}");
            
            // Insert backup data
            foreach ($data as $row) {
                $wpdb->insert($table_name, $row);
            }
        }

        return true;
    }

    /**
     * Get database statistics.
     *
     * @since    1.0.0
     * @return   array    Database statistics.
     */
    public static function get_database_stats() {
        global $wpdb;        $stats = array();
        $tables = array(
            'categories' => 'zpos_categories',
            'products' => 'zpos_products',
            'customers' => 'zpos_customers',
            'orders' => 'zpos_orders',
            'order_items' => 'zpos_order_items',
            'inventory' => 'zpos_inventory',
            'warranty_packages' => 'zpos_warranty_packages',
            'warranty' => 'zpos_warranties',
            'settings' => 'zpos_settings'
        );

        foreach ($tables as $key => $table) {
            $table_name = $wpdb->prefix . $table;
            $count = $wpdb->get_var("SELECT COUNT(*) FROM {$table_name}");
            $stats[$key] = intval($count);
        }

        return $stats;
    }

    /**
     * Check if all tables exist.
     *
     * @since    1.0.0
     * @return   bool    True if all tables exist.
     */    public static function tables_exist() {
        global $wpdb;

        $tables = array(
            'zpos_categories',
            'zpos_products',
            'zpos_customers',
            'zpos_orders',
            'zpos_order_items',
            'zpos_inventory',
            'zpos_warranty_packages',
            'zpos_warranties',
            'zpos_settings'
        );

        foreach ($tables as $table) {
            $table_name = $wpdb->prefix . $table;
            $exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name));
            if ($exists !== $table_name) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get table sizes.
     *
     * @since    1.0.0
     * @return   array    Table sizes in bytes.
     */
    public static function get_table_sizes() {
        global $wpdb;

        $sizes = array();        $tables = array(
            'zpos_categories',
            'zpos_products',
            'zpos_customers',
            'zpos_orders',
            'zpos_order_items',
            'zpos_inventory',
            'zpos_warranty_packages',
            'zpos_warranties',
            'zpos_settings'
        );

        foreach ($tables as $table) {
            $table_name = $wpdb->prefix . $table;
            $size = $wpdb->get_var($wpdb->prepare(
                "SELECT ROUND(((data_length + index_length) / 1024 / 1024), 2) AS 'size_mb' 
                FROM information_schema.TABLES 
                WHERE table_schema = %s AND table_name = %s",
                DB_NAME,
                $table_name
            ));
            $sizes[$table] = floatval($size);
        }

        return $sizes;
    }
}
