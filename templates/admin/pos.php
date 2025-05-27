<?php
/**
 * POS admin template
 *
 * @link       https://yourwebsite.com
 * @since      1.0.0
 *
 * @package    ZPOS
 * @subpackage ZPOS/templates/admin
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Ensure we have the POS class
if (!class_exists('ZPOS_POS')) {
    require_once ZPOS_PLUGIN_DIR . 'includes/pos.php';
}

$pos = new ZPOS_POS();

// Get currency settings
$currency_symbol = get_option('zpos_currency_symbol', '$');
$currency_position = get_option('zpos_currency_position', 'left');
?>

<div class="wrap zpos-pos-page">
    <h1 class="wp-heading-inline"><?php _e('Point of Sale', 'zpos'); ?></h1>
    
    <div class="zpos-pos-container">
        <!-- Left Panel - Products -->
        <div class="zpos-pos-panel zpos-products-panel">
            <div class="panel-header">
                <h3><?php _e('Products', 'zpos'); ?></h3>
                <div class="product-search-container">
                    <input type="text" id="product-search" class="regular-text" placeholder="<?php _e('Search products, SKU, or barcode...', 'zpos'); ?>">
                    <select id="category-filter" class="regular-text">
                        <option value=""><?php _e('All Categories', 'zpos'); ?></option>
                        <!-- Categories loaded via AJAX -->
                    </select>
                </div>
            </div>
            
            <div class="products-grid" id="products-grid">
                <div class="loading-products">
                    <p><?php _e('Loading products...', 'zpos'); ?></p>
                </div>
            </div>
            
            <div class="products-pagination">
                <button type="button" id="load-more-products" class="button"><?php _e('Load More', 'zpos'); ?></button>
            </div>
        </div>

        <!-- Center Panel - Cart -->
        <div class="zpos-pos-panel zpos-cart-panel">
            <div class="panel-header">
                <h3><?php _e('Cart', 'zpos'); ?></h3>
                <div class="cart-actions">
                    <button type="button" id="clear-cart" class="button button-secondary" title="<?php _e('Clear Cart', 'zpos'); ?>">
                        <span class="dashicons dashicons-trash"></span>
                    </button>
                    <button type="button" id="hold-order" class="button button-secondary" title="<?php _e('Hold Order', 'zpos'); ?>">
                        <span class="dashicons dashicons-clock"></span>
                    </button>
                    <button type="button" id="recall-order" class="button button-secondary" title="<?php _e('Recall Order', 'zpos'); ?>">
                        <span class="dashicons dashicons-backup"></span>
                    </button>
                </div>
            </div>
            
            <div class="cart-items" id="cart-items">
                <div class="empty-cart">
                    <p><?php _e('Cart is empty', 'zpos'); ?></p>
                    <p><small><?php _e('Add products to start a sale', 'zpos'); ?></small></p>
                </div>
            </div>
            
            <!-- Cart Totals -->
            <div class="cart-totals">
                <div class="discount-section">
                    <h4><?php _e('Discount', 'zpos'); ?></h4>
                    <div class="discount-controls">
                        <select id="discount-type">
                            <option value=""><?php _e('No Discount', 'zpos'); ?></option>
                            <option value="percentage"><?php _e('Percentage (%)', 'zpos'); ?></option>
                            <option value="fixed"><?php _e('Fixed Amount', 'zpos'); ?></option>
                        </select>
                        <input type="number" id="discount-value" placeholder="0" min="0" step="0.01">
                        <button type="button" id="apply-discount" class="button"><?php _e('Apply', 'zpos'); ?></button>
                    </div>
                </div>
                
                <div class="totals-display">
                    <div class="total-line">
                        <span><?php _e('Subtotal:', 'zpos'); ?></span>
                        <span id="cart-subtotal"><?php echo $currency_symbol; ?>0.00</span>
                    </div>
                    <div class="total-line discount-line" id="discount-line" style="display: none;">
                        <span><?php _e('Discount:', 'zpos'); ?></span>
                        <span id="cart-discount">-<?php echo $currency_symbol; ?>0.00</span>
                    </div>
                    <div class="total-line">
                        <span><?php _e('Tax:', 'zpos'); ?></span>
                        <span id="cart-tax"><?php echo $currency_symbol; ?>0.00</span>
                    </div>
                    <div class="total-line total-amount">
                        <span><?php _e('Total:', 'zpos'); ?></span>
                        <span id="cart-total"><?php echo $currency_symbol; ?>0.00</span>
                    </div>
                </div>
            </div>
            
            <!-- Payment Section -->
            <div class="payment-section">
                <h4><?php _e('Payment', 'zpos'); ?></h4>
                <div class="payment-methods">
                    <label>
                        <input type="radio" name="payment_method" value="cash" checked>
                        <?php _e('Cash', 'zpos'); ?>
                    </label>
                    <label>
                        <input type="radio" name="payment_method" value="card">
                        <?php _e('Card', 'zpos'); ?>
                    </label>
                    <label>
                        <input type="radio" name="payment_method" value="bank_transfer">
                        <?php _e('Bank Transfer', 'zpos'); ?>
                    </label>
                    <label>
                        <input type="radio" name="payment_method" value="other">
                        <?php _e('Other', 'zpos'); ?>
                    </label>
                </div>
                
                <div class="payment-buttons">
                    <button type="button" id="checkout-btn" class="button button-primary button-large" disabled>
                        <span class="dashicons dashicons-money-alt"></span>
                        <?php _e('Complete Sale', 'zpos'); ?>
                    </button>
                </div>
            </div>
        </div>

        <!-- Right Panel - Customer Info -->
        <div class="zpos-pos-panel zpos-customer-panel">
            <div class="panel-header">
                <h3><?php _e('Customer', 'zpos'); ?></h3>
                <button type="button" id="add-customer" class="button button-secondary">
                    <span class="dashicons dashicons-plus"></span>
                    <?php _e('New', 'zpos'); ?>
                </button>
            </div>
            
            <div class="customer-search">
                <input type="text" id="customer-search" class="regular-text" placeholder="<?php _e('Search customers...', 'zpos'); ?>">
                <div class="customer-search-results" id="customer-search-results"></div>
            </div>
            
            <div class="selected-customer" id="selected-customer">
                <div class="no-customer">
                    <p><?php _e('No customer selected', 'zpos'); ?></p>
                    <p><small><?php _e('Sales will be recorded as walk-in customer', 'zpos'); ?></small></p>
                </div>
            </div>
            
            <!-- Order Notes -->
            <div class="order-notes">
                <h4><?php _e('Order Notes', 'zpos'); ?></h4>
                <textarea id="order-notes" placeholder="<?php _e('Add notes for this order...', 'zpos'); ?>"></textarea>
            </div>
        </div>
    </div>
</div>

<!-- Product Details Modal -->
<div id="product-details-modal" class="zpos-modal" style="display: none;">
    <div class="zpos-modal-content">
        <div class="zpos-modal-header">
            <h2><?php _e('Product Details', 'zpos'); ?></h2>
            <span class="zpos-modal-close">&times;</span>
        </div>
        <div class="zpos-modal-body" id="product-details-content">
            <!-- Product details will be loaded here -->
        </div>
    </div>
</div>

<!-- Add Customer Modal -->
<div id="add-customer-modal" class="zpos-modal" style="display: none;">
    <div class="zpos-modal-content">
        <div class="zpos-modal-header">
            <h2><?php _e('Add New Customer', 'zpos'); ?></h2>
            <span class="zpos-modal-close">&times;</span>
        </div>
        <div class="zpos-modal-body">
            <form id="add-customer-form">
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Name', 'zpos'); ?> <span class="required">*</span></th>
                        <td><input type="text" id="customer-name" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Email', 'zpos'); ?></th>
                        <td><input type="email" id="customer-email" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Phone', 'zpos'); ?></th>
                        <td><input type="tel" id="customer-phone" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Address', 'zpos'); ?></th>
                        <td>
                            <input type="text" id="customer-address-1" class="regular-text" placeholder="<?php _e('Address Line 1', 'zpos'); ?>"><br>
                            <input type="text" id="customer-address-2" class="regular-text" placeholder="<?php _e('Address Line 2', 'zpos'); ?>" style="margin-top: 5px;">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('City', 'zpos'); ?></th>
                        <td><input type="text" id="customer-city" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('State', 'zpos'); ?></th>
                        <td><input type="text" id="customer-state" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Postal Code', 'zpos'); ?></th>
                        <td><input type="text" id="customer-postal" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Country', 'zpos'); ?></th>
                        <td><input type="text" id="customer-country" class="regular-text"></td>
                    </tr>
                </table>
                
                <div class="zpos-modal-footer">
                    <button type="button" class="button button-secondary zpos-modal-close"><?php _e('Cancel', 'zpos'); ?></button>
                    <button type="submit" class="button button-primary"><?php _e('Add Customer', 'zpos'); ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Hold Order Modal -->
<div id="hold-order-modal" class="zpos-modal" style="display: none;">
    <div class="zpos-modal-content">
        <div class="zpos-modal-header">
            <h2><?php _e('Hold Order', 'zpos'); ?></h2>
            <span class="zpos-modal-close">&times;</span>
        </div>
        <div class="zpos-modal-body">
            <form id="hold-order-form">
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Hold Name', 'zpos'); ?></th>
                        <td>
                            <input type="text" id="hold-name" class="regular-text" placeholder="<?php _e('Enter a name for this hold...', 'zpos'); ?>">
                            <p class="description"><?php _e('Optional. A default name will be generated if left empty.', 'zpos'); ?></p>
                        </td>
                    </tr>
                </table>
                
                <div class="zpos-modal-footer">
                    <button type="button" class="button button-secondary zpos-modal-close"><?php _e('Cancel', 'zpos'); ?></button>
                    <button type="submit" class="button button-primary"><?php _e('Hold Order', 'zpos'); ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Recall Order Modal -->
<div id="recall-order-modal" class="zpos-modal" style="display: none;">
    <div class="zpos-modal-content">
        <div class="zpos-modal-header">
            <h2><?php _e('Recall Held Order', 'zpos'); ?></h2>
            <span class="zpos-modal-close">&times;</span>
        </div>
        <div class="zpos-modal-body">
            <div id="held-orders-list">
                <!-- Held orders will be loaded here -->
            </div>
        </div>
    </div>
</div>

<!-- Receipt Modal -->
<div id="receipt-modal" class="zpos-modal" style="display: none;">
    <div class="zpos-modal-content zpos-modal-large">
        <div class="zpos-modal-header">
            <h2><?php _e('Order Receipt', 'zpos'); ?></h2>
            <div class="receipt-actions">
                <button type="button" id="print-receipt" class="button button-primary">
                    <span class="dashicons dashicons-printer"></span>
                    <?php _e('Print', 'zpos'); ?>
                </button>
                <button type="button" id="email-receipt" class="button button-secondary">
                    <span class="dashicons dashicons-email"></span>
                    <?php _e('Email', 'zpos'); ?>
                </button>
            </div>
            <span class="zpos-modal-close">&times;</span>
        </div>
        <div class="zpos-modal-body">
            <div id="receipt-content">
                <!-- Receipt content will be loaded here -->
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Initialize POS when DOM is ready
    if (typeof ZPOSAdmin !== 'undefined') {
        ZPOSAdmin.initPOS();
    }
});
</script>

<style>
/* POS Layout */
.zpos-pos-page {
    margin-right: 0;
}

.zpos-pos-container {
    display: grid;
    grid-template-columns: 1fr 400px 300px;
    gap: 20px;
    margin-top: 20px;
    height: calc(100vh - 160px);
    min-height: 600px;
}

.zpos-pos-panel {
    background: #fff;
    border: 1px solid #ccd0d4;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
    display: flex;
    flex-direction: column;
}

.panel-header {
    padding: 15px 20px;
    border-bottom: 1px solid #eee;
    background: #f9f9f9;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.panel-header h3 {
    margin: 0;
    font-size: 16px;
    font-weight: 600;
}

/* Products Panel */
.zpos-products-panel {
    min-height: 0;
}

.product-search-container {
    display: flex;
    gap: 10px;
    align-items: center;
}

.product-search-container input,
.product-search-container select {
    margin: 0;
}

.products-grid {
    flex: 1;
    padding: 20px;
    overflow-y: auto;
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 15px;
    align-content: start;
}

.product-card {
    border: 1px solid #ddd;
    border-radius: 6px;
    padding: 10px;
    text-align: center;
    cursor: pointer;
    transition: all 0.2s ease;
    background: #fff;
}

.product-card:hover {
    border-color: #0073aa;
    box-shadow: 0 2px 8px rgba(0,115,170,0.1);
    transform: translateY(-1px);
}

.product-card.out-of-stock {
    opacity: 0.6;
    cursor: not-allowed;
}

.product-card .product-image {
    width: 60px;
    height: 60px;
    margin: 0 auto 10px;
    background: #f9f9f9;
    border-radius: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
}

.product-card .product-image img {
    max-width: 100%;
    max-height: 100%;
    object-fit: cover;
}

.product-card .product-name {
    font-weight: 600;
    font-size: 12px;
    margin-bottom: 5px;
    line-height: 1.3;
    color: #333;
}

.product-card .product-sku {
    font-size: 11px;
    color: #666;
    margin-bottom: 5px;
}

.product-card .product-price {
    font-weight: bold;
    color: #0073aa;
    font-size: 14px;
}

.product-card .product-stock {
    font-size: 10px;
    color: #666;
    margin-top: 5px;
}

.loading-products {
    grid-column: 1 / -1;
    text-align: center;
    padding: 40px;
    color: #666;
}

.products-pagination {
    padding: 15px 20px;
    border-top: 1px solid #eee;
    text-align: center;
}

/* Cart Panel */
.zpos-cart-panel {
    overflow: hidden;
}

.cart-actions {
    display: flex;
    gap: 5px;
}

.cart-actions .button {
    padding: 5px 8px;
    min-height: auto;
}

.cart-items {
    flex: 1;
    padding: 15px;
    overflow-y: auto;
    min-height: 200px;
}

.empty-cart {
    text-align: center;
    color: #666;
    padding: 40px 20px;
}

.cart-item {
    display: flex;
    align-items: center;
    padding: 10px 0;
    border-bottom: 1px solid #eee;
}

.cart-item:last-child {
    border-bottom: none;
}

.cart-item-image {
    width: 40px;
    height: 40px;
    background: #f9f9f9;
    border-radius: 4px;
    margin-right: 10px;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
}

.cart-item-image img {
    max-width: 100%;
    max-height: 100%;
    object-fit: cover;
}

.cart-item-details {
    flex: 1;
    min-width: 0;
}

.cart-item-name {
    font-weight: 600;
    font-size: 13px;
    line-height: 1.3;
    margin-bottom: 2px;
}

.cart-item-sku {
    font-size: 11px;
    color: #666;
    margin-bottom: 5px;
}

.cart-item-price {
    font-size: 12px;
    color: #0073aa;
    font-weight: 600;
}

.cart-item-controls {
    display: flex;
    align-items: center;
    gap: 5px;
}

.quantity-controls {
    display: flex;
    align-items: center;
    border: 1px solid #ddd;
    border-radius: 3px;
}

.quantity-btn {
    width: 24px;
    height: 24px;
    border: none;
    background: #f9f9f9;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    font-weight: bold;
}

.quantity-btn:hover {
    background: #e9e9e9;
}

.quantity-input {
    width: 40px;
    text-align: center;
    border: none;
    height: 24px;
    font-size: 12px;
}

.remove-item {
    color: #dc3232;
    cursor: pointer;
    font-size: 16px;
    margin-left: 5px;
}

.remove-item:hover {
    color: #a00;
}

/* Cart Totals */
.cart-totals {
    border-top: 1px solid #eee;
    padding: 15px;
}

.discount-section h4,
.payment-section h4 {
    margin: 0 0 10px 0;
    font-size: 14px;
    font-weight: 600;
}

.discount-controls {
    display: flex;
    gap: 5px;
    margin-bottom: 15px;
}

.discount-controls select,
.discount-controls input {
    margin: 0;
    flex: 1;
}

.discount-controls input {
    max-width: 80px;
}

.totals-display {
    margin-bottom: 15px;
}

.total-line {
    display: flex;
    justify-content: space-between;
    margin-bottom: 8px;
    font-size: 13px;
}

.total-line.total-amount {
    border-top: 1px solid #ddd;
    padding-top: 8px;
    margin-top: 8px;
    font-weight: bold;
    font-size: 16px;
}

.discount-line {
    color: #dc3232;
}

/* Payment Section */
.payment-methods {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 8px;
    margin-bottom: 15px;
}

.payment-methods label {
    display: flex;
    align-items: center;
    font-size: 13px;
    cursor: pointer;
}

.payment-methods input[type="radio"] {
    margin-right: 6px;
}

.payment-buttons {
    text-align: center;
}

#checkout-btn {
    width: 100%;
    padding: 12px;
    font-size: 14px;
    font-weight: 600;
}

#checkout-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

/* Customer Panel */
.customer-search {
    padding: 15px;
    border-bottom: 1px solid #eee;
    position: relative;
}

.customer-search-results {
    position: absolute;
    top: 100%;
    left: 15px;
    right: 15px;
    background: #fff;
    border: 1px solid #ddd;
    border-top: none;
    max-height: 200px;
    overflow-y: auto;
    z-index: 10;
    display: none;
}

.customer-result {
    padding: 10px;
    border-bottom: 1px solid #eee;
    cursor: pointer;
}

.customer-result:hover {
    background: #f9f9f9;
}

.customer-result:last-child {
    border-bottom: none;
}

.customer-result-name {
    font-weight: 600;
    margin-bottom: 2px;
}

.customer-result-info {
    font-size: 12px;
    color: #666;
}

.selected-customer {
    padding: 15px;
    flex: 1;
}

.no-customer {
    text-align: center;
    color: #666;
    padding: 20px;
}

.customer-info {
    background: #f9f9f9;
    border-radius: 6px;
    padding: 15px;
}

.customer-info h4 {
    margin: 0 0 10px 0;
    font-size: 14px;
}

.customer-info p {
    margin: 5px 0;
    font-size: 12px;
}

.customer-actions {
    margin-top: 10px;
    text-align: center;
}

.order-notes {
    border-top: 1px solid #eee;
    padding: 15px;
}

.order-notes textarea {
    width: 100%;
    min-height: 80px;
    resize: vertical;
}

/* Responsive Design */
@media (max-width: 1400px) {
    .zpos-pos-container {
        grid-template-columns: 1fr 350px 280px;
    }
}

@media (max-width: 1200px) {
    .zpos-pos-container {
        grid-template-columns: 1fr 320px 260px;
        height: auto;
        min-height: auto;
    }
    
    .zpos-pos-panel {
        min-height: 400px;
    }
}

@media (max-width: 1024px) {
    .zpos-pos-container {
        grid-template-columns: 1fr;
        gap: 15px;
    }
    
    .zpos-pos-panel {
        min-height: 300px;
    }
    
    .products-grid {
        grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
        gap: 10px;
    }
}

@media (max-width: 768px) {
    .zpos-pos-container {
        margin-top: 10px;
        gap: 10px;
    }
    
    .panel-header {
        padding: 10px 15px;
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
    
    .product-search-container {
        width: 100%;
        flex-direction: column;
    }
    
    .products-grid {
        grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
        padding: 15px;
    }
    
    .discount-controls {
        flex-direction: column;
    }
    
    .payment-methods {
        grid-template-columns: 1fr;
    }
}

/* Modal Enhancements for POS */
.receipt-actions {
    display: flex;
    gap: 10px;
}

.held-order-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    margin-bottom: 10px;
    cursor: pointer;
}

.held-order-item:hover {
    background: #f9f9f9;
}

.held-order-info h4 {
    margin: 0 0 5px 0;
}

.held-order-info p {
    margin: 0;
    font-size: 12px;
    color: #666;
}

.held-order-total {
    font-weight: bold;
    color: #0073aa;
}

/* Print Styles */
@media print {
    .zpos-modal-header,
    .zpos-modal-footer {
        display: none !important;
    }
    
    .zpos-modal-content {
        box-shadow: none !important;
        border: none !important;
        margin: 0 !important;
        max-width: none !important;
        width: auto !important;
    }
}
</style>
