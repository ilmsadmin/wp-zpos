<?php
/**
 * Test data creation script for ZPOS
 * 
 * This script creates sample products for testing purposes.
 * Run this script once to populate the database with test data.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    // WordPress environment
    require_once('../../../wp-config.php');
}

global $wpdb;

// Create some test categories first
$categories_table = $wpdb->prefix . 'zpos_product_categories';
$products_table = $wpdb->prefix . 'zpos_products';

// Sample categories
$categories = array(
    array('name' => 'Electronics', 'description' => 'Electronic devices and accessories'),
    array('name' => 'Clothing', 'description' => 'Apparel and fashion items'),
    array('name' => 'Food & Beverage', 'description' => 'Food and drink items'),
    array('name' => 'Books', 'description' => 'Books and reading materials'),
);

$category_ids = array();

foreach ($categories as $category) {
    $existing = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM $categories_table WHERE name = %s",
        $category['name']
    ));
    
    if (!$existing) {
        $wpdb->insert(
            $categories_table,
            array(
                'name' => $category['name'],
                'description' => $category['description'],
                'status' => 'active'
            )
        );
        $category_ids[$category['name']] = $wpdb->insert_id;
    } else {
        $category_ids[$category['name']] = $existing;
    }
}

// Sample products
$products = array(
    array(
        'name' => 'Laptop Computer',
        'description' => 'High-performance laptop for work and gaming',
        'price' => 999.99,
        'sale_price' => 899.99,
        'sku' => 'LAPTOP-001',
        'barcode' => '1234567890123',
        'category_id' => $category_ids['Electronics'],
        'stock_quantity' => 10,
        'status' => 'active'
    ),
    array(
        'name' => 'Wireless Mouse',
        'description' => 'Ergonomic wireless mouse with long battery life',
        'price' => 29.99,
        'sku' => 'MOUSE-001',
        'barcode' => '1234567890124',
        'category_id' => $category_ids['Electronics'],
        'stock_quantity' => 50,
        'status' => 'active'
    ),
    array(
        'name' => 'T-Shirt',
        'description' => 'Comfortable cotton t-shirt',
        'price' => 19.99,
        'sku' => 'TSHIRT-001',
        'barcode' => '1234567890125',
        'category_id' => $category_ids['Clothing'],
        'stock_quantity' => 100,
        'status' => 'active'
    ),
    array(
        'name' => 'Coffee Mug',
        'description' => 'Ceramic coffee mug with company logo',
        'price' => 9.99,
        'sku' => 'MUG-001',
        'barcode' => '1234567890126',
        'category_id' => $category_ids['Food & Beverage'],
        'stock_quantity' => 25,
        'status' => 'active'
    ),
    array(
        'name' => 'Programming Book',
        'description' => 'Learn programming with this comprehensive guide',
        'price' => 49.99,
        'sale_price' => 39.99,
        'sku' => 'BOOK-001',
        'barcode' => '1234567890127',
        'category_id' => $category_ids['Books'],
        'stock_quantity' => 15,
        'status' => 'active'
    ),
);

$inserted_count = 0;

foreach ($products as $product) {
    // Check if product already exists
    $existing = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM $products_table WHERE sku = %s",
        $product['sku']
    ));
    
    if (!$existing) {
        $result = $wpdb->insert($products_table, $product);
        if ($result) {
            $inserted_count++;
        }
    }
}

echo "Test data creation completed!\n";
echo "Categories created: " . count($category_ids) . "\n";
echo "Products inserted: " . $inserted_count . "\n";

// Also check if tables exist
$tables_exist = array();
$required_tables = array('zpos_products', 'zpos_product_categories');

foreach ($required_tables as $table) {
    $table_name = $wpdb->prefix . $table;
    $exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name));
    $tables_exist[$table] = ($exists === $table_name);
}

echo "\nTable status:\n";
foreach ($tables_exist as $table => $exists) {
    echo "- $table: " . ($exists ? 'EXISTS' : 'MISSING') . "\n";
}

if (!$tables_exist['zpos_products'] || !$tables_exist['zpos_product_categories']) {
    echo "\nWARNING: Some required tables are missing!\n";
    echo "Please activate the ZPOS plugin to create the required tables.\n";
}
?>
