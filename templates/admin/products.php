<?php
/**
 * ZPOS Products Admin Template
 *
 * @package ZPOS
 * @since 1.0.0
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Initialize products class
$products = new ZPOS_Products();

// Handle actions
$action = $_GET['action'] ?? 'list';
$product_id = $_GET['product_id'] ?? null;
$message = '';
$error = '';

// Handle form submissions
if ($_POST) {
    if (isset($_POST['bulk_action']) && isset($_POST['selected_products'])) {
        // Bulk actions
        $bulk_action = sanitize_text_field($_POST['bulk_action']);
        $selected_ids = array_map('intval', $_POST['selected_products']);
        
        switch ($bulk_action) {
            case 'delete':
                $result = $products->bulk_delete($selected_ids);
                if ($result) {
                    $message = sprintf(__('%d products deleted successfully', 'zpos'), count($selected_ids));
                } else {
                    $error = __('Failed to delete products', 'zpos');
                }
                break;
                
            case 'sync_woocommerce':
                foreach ($selected_ids as $id) {
                    $products->sync_with_woocommerce($id);
                }
                $message = __('Selected products synced with WooCommerce', 'zpos');
                break;
        }
    } elseif (isset($_POST['save_product'])) {
        // Save product
        $product_data = $_POST;
        unset($product_data['save_product']);
        
        $result = $products->save_product($product_data, $product_id);
        if (is_wp_error($result)) {
            $error = $result->get_error_message();
        } else {
            $message = $product_id ? __('Product updated successfully', 'zpos') : __('Product created successfully', 'zpos');
            if (!$product_id) {
                wp_redirect(admin_url('admin.php?page=zpos-products&action=edit&product_id=' . $result));
                exit;
            }
        }
    } elseif (isset($_POST['import_woocommerce'])) {
        // Import from WooCommerce
        $result = $products->import_from_woocommerce();
        if (is_wp_error($result)) {
            $error = $result->get_error_message();
        } else {
            $message = sprintf(__('Imported %d products from WooCommerce', 'zpos'), $result['imported']);
            if (!empty($result['errors'])) {
                $error = implode(', ', $result['errors']);
            }
        }
    }
}

// Handle single product deletion
if ($action === 'delete' && $product_id) {
    $result = $products->delete_product($product_id);
    if ($result) {
        $message = __('Product deleted successfully', 'zpos');
    } else {
        $error = __('Failed to delete product', 'zpos');
    }
    $action = 'list';
}

// Get current product for edit
$current_product = null;
if ($action === 'edit' && $product_id) {
    $current_product = $products->get_product($product_id);
    if (!$current_product) {
        $error = __('Product not found', 'zpos');
        $action = 'list';
    }
}

// Get products list for list view
$products_data = null;
if ($action === 'list') {
    $args = array(
        'per_page' => 20,
        'page' => $_GET['paged'] ?? 1,
        'search' => $_GET['search'] ?? '',
        'category_id' => $_GET['category_id'] ?? '',
        'status' => $_GET['status'] ?? '',
        'stock_status' => $_GET['stock_status'] ?? ''
    );
    $products_data = $products->get_products($args);
}

// Get categories for filters
$categories = $products->get_categories();
?>

<div class="wrap">
    <h1 class="wp-heading-inline">
        <?php _e('Products', 'zpos'); ?>
    </h1>
    
    <?php if ($action === 'list'): ?>
        <a href="<?php echo admin_url('admin.php?page=zpos-products&action=add'); ?>" class="page-title-action">
            <?php _e('Add New Product', 'zpos'); ?>
        </a>
    <?php endif; ?>
    
    <hr class="wp-header-end">
    
    <?php if ($message): ?>
        <div class="notice notice-success is-dismissible">
            <p><?php echo esc_html($message); ?></p>
        </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="notice notice-error is-dismissible">
            <p><?php echo esc_html($error); ?></p>
        </div>
    <?php endif; ?>
    
    <?php if ($action === 'list'): ?>
        <!-- Products List View -->
        <form method="get">
            <input type="hidden" name="page" value="zpos-products">
            <div class="tablenav top">
                <div class="alignleft actions">
                    <!-- Search -->
                    <input type="search" name="search" value="<?php echo esc_attr($_GET['search'] ?? ''); ?>" placeholder="<?php _e('Search products...', 'zpos'); ?>">
                    
                    <!-- Category Filter -->
                    <select name="category_id">
                        <option value=""><?php _e('All Categories', 'zpos'); ?></option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category->id; ?>" <?php selected($_GET['category_id'] ?? '', $category->id); ?>>
                                <?php echo esc_html($category->name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    
                    <!-- Status Filter -->
                    <select name="status">
                        <option value=""><?php _e('All Statuses', 'zpos'); ?></option>
                        <option value="publish" <?php selected($_GET['status'] ?? '', 'publish'); ?>><?php _e('Published', 'zpos'); ?></option>
                        <option value="draft" <?php selected($_GET['status'] ?? '', 'draft'); ?>><?php _e('Draft', 'zpos'); ?></option>
                    </select>
                    
                    <!-- Stock Status Filter -->
                    <select name="stock_status">
                        <option value=""><?php _e('All Stock Status', 'zpos'); ?></option>
                        <option value="instock" <?php selected($_GET['stock_status'] ?? '', 'instock'); ?>><?php _e('In Stock', 'zpos'); ?></option>
                        <option value="outofstock" <?php selected($_GET['stock_status'] ?? '', 'outofstock'); ?>><?php _e('Out of Stock', 'zpos'); ?></option>
                        <option value="onbackorder" <?php selected($_GET['stock_status'] ?? '', 'onbackorder'); ?>><?php _e('On Backorder', 'zpos'); ?></option>
                    </select>
                    
                    <input type="submit" class="button" value="<?php _e('Filter', 'zpos'); ?>">
                </div>
                
                <div class="alignright actions">
                    <?php if (class_exists('WooCommerce')): ?>
                        <form method="post" style="display: inline-block;">
                            <input type="submit" name="import_woocommerce" class="button" value="<?php _e('Import from WooCommerce', 'zpos'); ?>">
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </form>
        
        <form method="post">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <td class="manage-column column-cb check-column">
                            <input type="checkbox" id="cb-select-all-1">
                        </td>
                        <th class="manage-column"><?php _e('Image', 'zpos'); ?></th>
                        <th class="manage-column column-primary"><?php _e('Name', 'zpos'); ?></th>
                        <th class="manage-column"><?php _e('SKU', 'zpos'); ?></th>
                        <th class="manage-column"><?php _e('Price', 'zpos'); ?></th>
                        <th class="manage-column"><?php _e('Stock', 'zpos'); ?></th>
                        <th class="manage-column"><?php _e('Category', 'zpos'); ?></th>
                        <th class="manage-column"><?php _e('Status', 'zpos'); ?></th>
                        <th class="manage-column"><?php _e('Date', 'zpos'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($products_data['products'])): ?>
                        <?php foreach ($products_data['products'] as $product): ?>
                            <tr>
                                <th scope="row" class="check-column">
                                    <input type="checkbox" name="selected_products[]" value="<?php echo $product->id; ?>">
                                </th>
                                <td>
                                    <?php if ($product->image_url): ?>
                                        <img src="<?php echo esc_url($product->image_url); ?>" alt="" style="width: 50px; height: 50px; object-fit: cover;">
                                    <?php else: ?>
                                        <div style="width: 50px; height: 50px; background: #f0f0f0; display: flex; align-items: center; justify-content: center;">
                                            <span class="dashicons dashicons-format-image" style="color: #ccc;"></span>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="column-primary">
                                    <strong>
                                        <a href="<?php echo admin_url('admin.php?page=zpos-products&action=edit&product_id=' . $product->id); ?>">
                                            <?php echo esc_html($product->name); ?>
                                        </a>
                                    </strong>
                                    <div class="row-actions">
                                        <span class="edit">
                                            <a href="<?php echo admin_url('admin.php?page=zpos-products&action=edit&product_id=' . $product->id); ?>">
                                                <?php _e('Edit', 'zpos'); ?>
                                            </a> |
                                        </span>
                                        <span class="trash">
                                            <a href="<?php echo admin_url('admin.php?page=zpos-products&action=delete&product_id=' . $product->id); ?>" 
                                               onclick="return confirm('<?php _e('Are you sure you want to delete this product?', 'zpos'); ?>')">
                                                <?php _e('Delete', 'zpos'); ?>
                                            </a>
                                        </span>
                                    </div>
                                </td>
                                <td><?php echo esc_html($product->sku); ?></td>
                                <td>
                                    <?php if ($product->sale_price && $product->sale_price < $product->price): ?>
                                        <del><?php echo wc_price($product->price); ?></del>
                                        <br><?php echo wc_price($product->sale_price); ?>
                                    <?php else: ?>
                                        <?php echo wc_price($product->price); ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($product->manage_stock): ?>
                                        <?php echo $product->stock_quantity; ?>
                                        <br><small class="stock-status <?php echo $product->stock_status; ?>">
                                            <?php echo ucfirst($product->stock_status); ?>
                                        </small>
                                    <?php else: ?>
                                        <small><?php _e('Not managed', 'zpos'); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php 
                                    $category = array_filter($categories, function($cat) use ($product) {
                                        return $cat->id == $product->category_id;
                                    });
                                    echo !empty($category) ? esc_html(reset($category)->name) : '—';
                                    ?>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo $product->status; ?>">
                                        <?php echo ucfirst($product->status); ?>
                                    </span>
                                </td>
                                <td><?php echo date_i18n(get_option('date_format'), strtotime($product->created_at)); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" class="no-items"><?php _e('No products found.', 'zpos'); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            
            <?php if (!empty($products_data['products'])): ?>
                <div class="tablenav bottom">
                    <div class="alignleft actions bulkactions">
                        <select name="bulk_action">
                            <option value=""><?php _e('Bulk Actions', 'zpos'); ?></option>
                            <option value="delete"><?php _e('Delete', 'zpos'); ?></option>
                            <?php if (class_exists('WooCommerce')): ?>
                                <option value="sync_woocommerce"><?php _e('Sync with WooCommerce', 'zpos'); ?></option>
                            <?php endif; ?>
                        </select>
                        <input type="submit" class="button action" value="<?php _e('Apply', 'zpos'); ?>">
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($products_data['total_pages'] > 1): ?>
                        <div class="tablenav-pages">
                            <?php
                            $pagination_args = array(
                                'base' => add_query_arg('paged', '%#%'),
                                'format' => '',
                                'current' => $products_data['current_page'],
                                'total' => $products_data['total_pages'],
                                'prev_text' => '‹',
                                'next_text' => '›'
                            );
                            echo paginate_links($pagination_args);
                            ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </form>
        
    <?php elseif ($action === 'add' || $action === 'edit'): ?>
        <!-- Add/Edit Product Form -->
        <form method="post" enctype="multipart/form-data">
            <div id="poststuff">
                <div id="post-body" class="metabox-holder columns-2">
                    <div id="post-body-content">
                        <!-- Main Product Data -->
                        <div class="postbox">
                            <h2 class="hndle"><?php _e('Product Data', 'zpos'); ?></h2>
                            <div class="inside">
                                <table class="form-table">
                                    <tr>
                                        <th scope="row">
                                            <label for="name"><?php _e('Product Name', 'zpos'); ?> *</label>
                                        </th>
                                        <td>
                                            <input type="text" id="name" name="name" value="<?php echo esc_attr($current_product->name ?? ''); ?>" class="regular-text" required>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">
                                            <label for="sku"><?php _e('SKU', 'zpos'); ?></label>
                                        </th>
                                        <td>
                                            <input type="text" id="sku" name="sku" value="<?php echo esc_attr($current_product->sku ?? ''); ?>" class="regular-text">
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">
                                            <label for="description"><?php _e('Description', 'zpos'); ?></label>
                                        </th>
                                        <td>
                                            <?php wp_editor($current_product->description ?? '', 'description', array('textarea_rows' => 5)); ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">
                                            <label for="short_description"><?php _e('Short Description', 'zpos'); ?></label>
                                        </th>
                                        <td>
                                            <textarea id="short_description" name="short_description" rows="3" class="large-text"><?php echo esc_textarea($current_product->short_description ?? ''); ?></textarea>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                        
                        <!-- Pricing -->
                        <div class="postbox">
                            <h2 class="hndle"><?php _e('Pricing', 'zpos'); ?></h2>
                            <div class="inside">
                                <table class="form-table">
                                    <tr>
                                        <th scope="row">
                                            <label for="price"><?php _e('Regular Price', 'zpos'); ?> *</label>
                                        </th>
                                        <td>
                                            <input type="number" step="0.01" id="price" name="price" value="<?php echo esc_attr($current_product->price ?? ''); ?>" class="regular-text" required>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">
                                            <label for="sale_price"><?php _e('Sale Price', 'zpos'); ?></label>
                                        </th>
                                        <td>
                                            <input type="number" step="0.01" id="sale_price" name="sale_price" value="<?php echo esc_attr($current_product->sale_price ?? ''); ?>" class="regular-text">
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">
                                            <label for="cost_price"><?php _e('Cost Price', 'zpos'); ?></label>
                                        </th>
                                        <td>
                                            <input type="number" step="0.01" id="cost_price" name="cost_price" value="<?php echo esc_attr($current_product->cost_price ?? ''); ?>" class="regular-text">
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                        
                        <!-- Inventory -->
                        <div class="postbox">
                            <h2 class="hndle"><?php _e('Inventory', 'zpos'); ?></h2>
                            <div class="inside">
                                <table class="form-table">
                                    <tr>
                                        <th scope="row">
                                            <label for="manage_stock"><?php _e('Manage Stock', 'zpos'); ?></label>
                                        </th>
                                        <td>
                                            <input type="checkbox" id="manage_stock" name="manage_stock" value="1" <?php checked($current_product->manage_stock ?? 1, 1); ?>>
                                            <label for="manage_stock"><?php _e('Enable stock management at product level', 'zpos'); ?></label>
                                        </td>
                                    </tr>
                                    <tr id="stock_quantity_row">
                                        <th scope="row">
                                            <label for="stock_quantity"><?php _e('Stock Quantity', 'zpos'); ?></label>
                                        </th>
                                        <td>
                                            <input type="number" id="stock_quantity" name="stock_quantity" value="<?php echo esc_attr($current_product->stock_quantity ?? 0); ?>" class="regular-text">
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">
                                            <label for="stock_status"><?php _e('Stock Status', 'zpos'); ?></label>
                                        </th>
                                        <td>
                                            <select id="stock_status" name="stock_status">
                                                <option value="instock" <?php selected($current_product->stock_status ?? 'instock', 'instock'); ?>><?php _e('In Stock', 'zpos'); ?></option>
                                                <option value="outofstock" <?php selected($current_product->stock_status ?? '', 'outofstock'); ?>><?php _e('Out of Stock', 'zpos'); ?></option>
                                                <option value="onbackorder" <?php selected($current_product->stock_status ?? '', 'onbackorder'); ?>><?php _e('On Backorder', 'zpos'); ?></option>
                                            </select>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">
                                            <label for="barcode"><?php _e('Barcode', 'zpos'); ?></label>
                                        </th>
                                        <td>
                                            <input type="text" id="barcode" name="barcode" value="<?php echo esc_attr($current_product->barcode ?? ''); ?>" class="regular-text">
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <div id="postbox-container-1" class="postbox-container">
                        <!-- Publish Box -->
                        <div class="postbox">
                            <h2 class="hndle"><?php _e('Publish', 'zpos'); ?></h2>
                            <div class="inside">
                                <div class="submitbox">
                                    <div id="minor-publishing">
                                        <div class="misc-pub-section">
                                            <label for="status"><?php _e('Status:', 'zpos'); ?></label>
                                            <select id="status" name="status">
                                                <option value="publish" <?php selected($current_product->status ?? 'publish', 'publish'); ?>><?php _e('Published', 'zpos'); ?></option>
                                                <option value="draft" <?php selected($current_product->status ?? '', 'draft'); ?>><?php _e('Draft', 'zpos'); ?></option>
                                            </select>
                                        </div>
                                        <div class="misc-pub-section">
                                            <input type="checkbox" id="featured" name="featured" value="1" <?php checked($current_product->featured ?? 0, 1); ?>>
                                            <label for="featured"><?php _e('Featured Product', 'zpos'); ?></label>
                                        </div>
                                    </div>
                                    <div id="major-publishing-actions">
                                        <div id="publishing-action">
                                            <input type="submit" name="save_product" class="button-primary" value="<?php echo $current_product ? __('Update Product', 'zpos') : __('Add Product', 'zpos'); ?>">
                                        </div>
                                        <div class="clear"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Category -->
                        <div class="postbox">
                            <h2 class="hndle"><?php _e('Category', 'zpos'); ?></h2>
                            <div class="inside">
                                <select name="category_id">
                                    <option value=""><?php _e('Select Category', 'zpos'); ?></option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category->id; ?>" <?php selected($current_product->category_id ?? '', $category->id); ?>>
                                            <?php echo esc_html($category->name); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Product Image -->
                        <div class="postbox">
                            <h2 class="hndle"><?php _e('Product Image', 'zpos'); ?></h2>
                            <div class="inside">
                                <div id="product-image-container">
                                    <?php if (!empty($current_product->image_url)): ?>
                                        <img src="<?php echo esc_url($current_product->image_url); ?>" style="max-width: 100%; height: auto;">
                                        <br><br>
                                    <?php endif; ?>
                                    <input type="file" name="product_image" accept="image/*">
                                    <input type="hidden" name="image_url" value="<?php echo esc_attr($current_product->image_url ?? ''); ?>">
                                    <p class="description"><?php _e('Upload a product image', 'zpos'); ?></p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Additional Options -->
                        <div class="postbox">
                            <h2 class="hndle"><?php _e('Additional Options', 'zpos'); ?></h2>
                            <div class="inside">
                                <table class="form-table">
                                    <tr>
                                        <th scope="row">
                                            <label for="weight"><?php _e('Weight', 'zpos'); ?></label>
                                        </th>
                                        <td>
                                            <input type="number" step="0.001" id="weight" name="weight" value="<?php echo esc_attr($current_product->weight ?? ''); ?>" class="small-text">
                                            <span class="description">kg</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">
                                            <label for="dimensions"><?php _e('Dimensions', 'zpos'); ?></label>
                                        </th>
                                        <td>
                                            <input type="text" id="dimensions" name="dimensions" value="<?php echo esc_attr($current_product->dimensions ?? ''); ?>" class="regular-text">
                                            <span class="description"><?php _e('L × W × H (cm)', 'zpos'); ?></span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">
                                            <label for="tax_status"><?php _e('Tax Status', 'zpos'); ?></label>
                                        </th>
                                        <td>
                                            <select id="tax_status" name="tax_status">
                                                <option value="taxable" <?php selected($current_product->tax_status ?? 'taxable', 'taxable'); ?>><?php _e('Taxable', 'zpos'); ?></option>
                                                <option value="none" <?php selected($current_product->tax_status ?? '', 'none'); ?>><?php _e('None', 'zpos'); ?></option>
                                            </select>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">
                                            <label for="tax_class"><?php _e('Tax Class', 'zpos'); ?></label>
                                        </th>
                                        <td>
                                            <input type="text" id="tax_class" name="tax_class" value="<?php echo esc_attr($current_product->tax_class ?? ''); ?>" class="regular-text">
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
        
    <?php endif; ?>
</div>

<style>
.status-badge {
    padding: 2px 8px;
    border-radius: 3px;
    font-size: 11px;
    font-weight: bold;
    text-transform: uppercase;
}
.status-publish {
    background: #d1e7dd;
    color: #0a3622;
}
.status-draft {
    background: #f8d7da;
    color: #58151c;
}
.stock-status.instock {
    color: #28a745;
}
.stock-status.outofstock {
    color: #dc3545;
}
.stock-status.onbackorder {
    color: #ffc107;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Toggle stock quantity field based on manage stock checkbox
    $('#manage_stock').change(function() {
        if ($(this).is(':checked')) {
            $('#stock_quantity_row').show();
        } else {
            $('#stock_quantity_row').hide();
        }
    }).trigger('change');
    
    // Select all checkbox
    $('#cb-select-all-1').change(function() {
        $('input[name="selected_products[]"]').prop('checked', $(this).is(':checked'));
    });
});
</script>
