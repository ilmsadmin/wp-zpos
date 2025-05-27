<?php
/**
 * Orders admin template
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
?>

<div class="wrap zpos-orders">
    <h1 class="wp-heading-inline"><?php _e('Orders', 'zpos'); ?></h1>
    
    <!-- Filters -->
    <div class="zpos-filters-container">
        <div class="zpos-filters">
            <select id="status-filter" class="regular-text">
                <option value=""><?php _e('All Statuses', 'zpos'); ?></option>
                <option value="pending"><?php _e('Pending', 'zpos'); ?></option>
                <option value="processing"><?php _e('Processing', 'zpos'); ?></option>
                <option value="completed"><?php _e('Completed', 'zpos'); ?></option>
                <option value="cancelled"><?php _e('Cancelled', 'zpos'); ?></option>
                <option value="refunded"><?php _e('Refunded', 'zpos'); ?></option>
            </select>
            
            <input type="date" id="date-from" class="regular-text" placeholder="<?php _e('From Date', 'zpos'); ?>">
            <input type="date" id="date-to" class="regular-text" placeholder="<?php _e('To Date', 'zpos'); ?>">
            
            <input type="text" id="search-orders" class="regular-text" placeholder="<?php _e('Search orders...', 'zpos'); ?>">
            
            <button type="button" id="filter-orders" class="button"><?php _e('Filter', 'zpos'); ?></button>
            <button type="button" id="reset-filters" class="button"><?php _e('Reset', 'zpos'); ?></button>
        </div>
        
        <div class="zpos-actions">
            <?php if (class_exists('WooCommerce')): ?>
            <button type="button" id="sync-woocommerce" class="button button-secondary">
                <span class="dashicons dashicons-update"></span>
                <?php _e('Sync WooCommerce', 'zpos'); ?>
            </button>
            <?php endif; ?>
            
            <button type="button" id="export-orders" class="button button-secondary">
                <span class="dashicons dashicons-download"></span>
                <?php _e('Export', 'zpos'); ?>
            </button>
        </div>
    </div>
    
    <!-- Orders Table -->
    <div class="zpos-table-container">
        <table class="wp-list-table widefat fixed striped" id="orders-table">
            <thead>
                <tr>
                    <th class="manage-column column-cb check-column">
                        <input type="checkbox" id="cb-select-all">
                    </th>
                    <th class="manage-column sortable" data-orderby="order_number">
                        <a href="#"><span><?php _e('Order #', 'zpos'); ?></span><span class="sorting-indicators"></span></a>
                    </th>
                    <th class="manage-column sortable" data-orderby="status">
                        <a href="#"><span><?php _e('Status', 'zpos'); ?></span><span class="sorting-indicators"></span></a>
                    </th>
                    <th class="manage-column sortable" data-orderby="customer_name">
                        <a href="#"><span><?php _e('Customer', 'zpos'); ?></span><span class="sorting-indicators"></span></a>
                    </th>
                    <th class="manage-column sortable" data-orderby="total_amount">
                        <a href="#"><span><?php _e('Total', 'zpos'); ?></span><span class="sorting-indicators"></span></a>
                    </th>
                    <th class="manage-column"><?php _e('Payment Method', 'zpos'); ?></th>
                    <th class="manage-column sortable" data-orderby="created_at">
                        <a href="#"><span><?php _e('Date', 'zpos'); ?></span><span class="sorting-indicators"></span></a>
                    </th>
                    <th class="manage-column"><?php _e('Actions', 'zpos'); ?></th>
                </tr>
            </thead>
            <tbody id="orders-tbody">
                <!-- Orders will be loaded here via AJAX -->
            </tbody>
        </table>
        
        <!-- Loading indicator -->
        <div id="orders-loading" class="zpos-loading" style="display: none;">
            <span class="spinner is-active"></span>
            <span><?php _e('Loading orders...', 'zpos'); ?></span>
        </div>
        
        <!-- No orders found -->
        <div id="no-orders" class="zpos-no-data" style="display: none;">
            <p><?php _e('No orders found.', 'zpos'); ?></p>
        </div>
    </div>
    
    <!-- Pagination -->
    <div class="zpos-pagination-container">
        <div class="tablenav bottom">
            <div class="alignleft actions bulkactions">
                <select id="bulk-action">
                    <option value="-1"><?php _e('Bulk Actions', 'zpos'); ?></option>
                    <option value="mark_processing"><?php _e('Mark as Processing', 'zpos'); ?></option>
                    <option value="mark_completed"><?php _e('Mark as Completed', 'zpos'); ?></option>
                    <option value="mark_cancelled"><?php _e('Mark as Cancelled', 'zpos'); ?></option>
                </select>
                <input type="submit" id="doaction" class="button action" value="<?php _e('Apply', 'zpos'); ?>">
            </div>
            
            <div class="tablenav-pages" id="pagination-container">
                <!-- Pagination will be loaded here -->
            </div>
        </div>
    </div>
</div>

<!-- Order Details Modal -->
<div id="order-details-modal" class="zpos-modal" style="display: none;">
    <div class="zpos-modal-content">
        <div class="zpos-modal-header">
            <h2><?php _e('Order Details', 'zpos'); ?></h2>
            <span class="zpos-modal-close">&times;</span>
        </div>
        <div class="zpos-modal-body" id="order-details-content">
            <!-- Order details will be loaded here -->
        </div>
    </div>
</div>

<!-- WooCommerce Sync Modal -->
<?php if (class_exists('WooCommerce')): ?>
<div id="wc-sync-modal" class="zpos-modal" style="display: none;">
    <div class="zpos-modal-content">
        <div class="zpos-modal-header">
            <h2><?php _e('Sync WooCommerce Orders', 'zpos'); ?></h2>
            <span class="zpos-modal-close">&times;</span>
        </div>
        <div class="zpos-modal-body">
            <form id="wc-sync-form">
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Date Range', 'zpos'); ?></th>
                        <td>
                            <input type="date" name="sync_date_from" id="sync-date-from" value="<?php echo date('Y-m-d', strtotime('-7 days')); ?>">
                            <span><?php _e('to', 'zpos'); ?></span>
                            <input type="date" name="sync_date_to" id="sync-date-to" value="<?php echo date('Y-m-d'); ?>">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Order Status', 'zpos'); ?></th>
                        <td>
                            <label><input type="checkbox" name="sync_status[]" value="pending" checked> <?php _e('Pending', 'zpos'); ?></label><br>
                            <label><input type="checkbox" name="sync_status[]" value="processing" checked> <?php _e('Processing', 'zpos'); ?></label><br>
                            <label><input type="checkbox" name="sync_status[]" value="completed" checked> <?php _e('Completed', 'zpos'); ?></label><br>
                            <label><input type="checkbox" name="sync_status[]" value="on-hold"> <?php _e('On Hold', 'zpos'); ?></label>
                        </td>
                    </tr>
                </table>
                
                <div class="zpos-modal-footer">
                    <button type="button" class="button button-secondary zpos-modal-close"><?php _e('Cancel', 'zpos'); ?></button>
                    <button type="submit" class="button button-primary"><?php _e('Sync Orders', 'zpos'); ?></button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<script type="text/javascript">
jQuery(document).ready(function($) {
    let currentPage = 1;
    let currentFilters = {};
    
    // Load orders on page load
    loadOrders();
    
    // Filter orders
    $('#filter-orders').on('click', function() {
        currentPage = 1;
        loadOrders();
    });
    
    // Reset filters
    $('#reset-filters').on('click', function() {
        $('#status-filter').val('');
        $('#date-from').val('');
        $('#date-to').val('');
        $('#search-orders').val('');
        currentPage = 1;
        loadOrders();
    });
    
    // Search orders
    $('#search-orders').on('keypress', function(e) {
        if (e.which === 13) {
            currentPage = 1;
            loadOrders();
        }
    });
    
    // Sort orders
    $('.sortable').on('click', function(e) {
        e.preventDefault();
        const orderby = $(this).data('orderby');
        const currentOrder = $(this).hasClass('asc') ? 'desc' : 'asc';
        
        $('.sortable').removeClass('asc desc');
        $(this).addClass(currentOrder);
        
        currentFilters.orderby = orderby;
        currentFilters.order = currentOrder;
        currentPage = 1;
        loadOrders();
    });
    
    // Load orders function
    function loadOrders() {
        $('#orders-loading').show();
        $('#orders-tbody').empty();
        $('#no-orders').hide();
        
        currentFilters = {
            status: $('#status-filter').val(),
            date_from: $('#date-from').val(),
            date_to: $('#date-to').val(),
            search: $('#search-orders').val(),
            page: currentPage,
            orderby: currentFilters.orderby || 'created_at',
            order: currentFilters.order || 'desc'
        };
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'zpos_get_orders',
                nonce: zpos_ajax.nonce,
                ...currentFilters
            },
            success: function(response) {
                $('#orders-loading').hide();
                
                if (response.success && response.data.orders.length > 0) {
                    displayOrders(response.data.orders);
                    displayPagination(response.data);
                } else {
                    $('#no-orders').show();
                }
            },
            error: function() {
                $('#orders-loading').hide();
                alert('<?php _e('Error loading orders', 'zpos'); ?>');
            }
        });
    }
    
    // Display orders in table
    function displayOrders(orders) {
        let html = '';
        
        orders.forEach(function(order) {
            const statusClass = 'status-' + order.status;
            const statusLabel = order.status.charAt(0).toUpperCase() + order.status.slice(1);
            
            html += `
                <tr>
                    <td class="check-column">
                        <input type="checkbox" name="order[]" value="${order.id}">
                    </td>
                    <td><strong><a href="#" class="view-order" data-order-id="${order.id}">#${order.order_number}</a></strong></td>
                    <td><span class="order-status ${statusClass}">${statusLabel}</span></td>
                    <td>
                        <strong>${order.customer_name}</strong><br>
                        <small>${order.customer_email}</small>
                    </td>
                    <td><strong>${order.total_amount} ${order.currency}</strong></td>
                    <td>${order.payment_method || '-'}</td>
                    <td>${new Date(order.created_at).toLocaleDateString()}</td>
                    <td>
                        <select class="order-status-update" data-order-id="${order.id}">
                            <option value="pending" ${order.status === 'pending' ? 'selected' : ''}><?php _e('Pending', 'zpos'); ?></option>
                            <option value="processing" ${order.status === 'processing' ? 'selected' : ''}><?php _e('Processing', 'zpos'); ?></option>
                            <option value="completed" ${order.status === 'completed' ? 'selected' : ''}><?php _e('Completed', 'zpos'); ?></option>
                            <option value="cancelled" ${order.status === 'cancelled' ? 'selected' : ''}><?php _e('Cancelled', 'zpos'); ?></option>
                            <option value="refunded" ${order.status === 'refunded' ? 'selected' : ''}><?php _e('Refunded', 'zpos'); ?></option>
                        </select>
                    </td>
                </tr>
            `;
        });
        
        $('#orders-tbody').html(html);
    }
    
    // Display pagination
    function displayPagination(data) {
        if (data.pages <= 1) {
            $('#pagination-container').empty();
            return;
        }
        
        let html = `
            <span class="displaying-num">${data.total} <?php _e('items', 'zpos'); ?></span>
            <span class="pagination-links">
        `;
        
        if (data.current_page > 1) {
            html += `<a class="first-page button" href="#" data-page="1">&laquo;</a>`;
            html += `<a class="prev-page button" href="#" data-page="${data.current_page - 1}">&lsaquo;</a>`;
        }
        
        html += `<span class="paging-input">
            <label for="current-page-selector" class="screen-reader-text"><?php _e('Current Page', 'zpos'); ?></label>
            <input class="current-page" id="current-page-selector" type="text" name="paged" value="${data.current_page}" size="1" aria-describedby="table-paging">
            <span class="tablenav-paging-text"> <?php _e('of', 'zpos'); ?> <span class="total-pages">${data.pages}</span></span>
        </span>`;
        
        if (data.current_page < data.pages) {
            html += `<a class="next-page button" href="#" data-page="${data.current_page + 1}">&rsaquo;</a>`;
            html += `<a class="last-page button" href="#" data-page="${data.pages}">&raquo;</a>`;
        }
        
        html += '</span>';
        
        $('#pagination-container').html(html);
    }
    
    // Pagination click handlers
    $(document).on('click', '.pagination-links a', function(e) {
        e.preventDefault();
        currentPage = parseInt($(this).data('page'));
        loadOrders();
    });
    
    // View order details
    $(document).on('click', '.view-order', function(e) {
        e.preventDefault();
        const orderId = $(this).data('order-id');
        loadOrderDetails(orderId);
    });
    
    // Load order details
    function loadOrderDetails(orderId) {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'zpos_get_order_details',
                nonce: zpos_ajax.nonce,
                order_id: orderId
            },
            success: function(response) {
                if (response.success) {
                    displayOrderDetails(response.data);
                    $('#order-details-modal').show();
                } else {
                    alert('<?php _e('Error loading order details', 'zpos'); ?>');
                }
            }
        });
    }
    
    // Display order details
    function displayOrderDetails(order) {
        let itemsHtml = '';
        if (order.items && order.items.length > 0) {
            order.items.forEach(function(item) {
                itemsHtml += `
                    <tr>
                        <td>${item.product_name}</td>
                        <td>${item.sku || '-'}</td>
                        <td>${item.quantity}</td>
                        <td>${item.price} ${order.currency}</td>
                        <td>${item.total} ${order.currency}</td>
                    </tr>
                `;
            });
        }
        
        const html = `
            <div class="order-details">
                <div class="order-info">
                    <h3><?php _e('Order Information', 'zpos'); ?></h3>
                    <p><strong><?php _e('Order Number:', 'zpos'); ?></strong> #${order.order_number}</p>
                    <p><strong><?php _e('Status:', 'zpos'); ?></strong> ${order.status}</p>
                    <p><strong><?php _e('Date:', 'zpos'); ?></strong> ${new Date(order.created_at).toLocaleDateString()}</p>
                    <p><strong><?php _e('Total:', 'zpos'); ?></strong> ${order.total_amount} ${order.currency}</p>
                </div>
                
                <div class="customer-info">
                    <h3><?php _e('Customer Information', 'zpos'); ?></h3>
                    <p><strong><?php _e('Name:', 'zpos'); ?></strong> ${order.customer_name}</p>
                    <p><strong><?php _e('Email:', 'zpos'); ?></strong> ${order.customer_email}</p>
                    <p><strong><?php _e('Phone:', 'zpos'); ?></strong> ${order.customer_phone || '-'}</p>
                </div>
                
                <div class="order-items">
                    <h3><?php _e('Order Items', 'zpos'); ?></h3>
                    <table class="wp-list-table widefat">
                        <thead>
                            <tr>
                                <th><?php _e('Product', 'zpos'); ?></th>
                                <th><?php _e('SKU', 'zpos'); ?></th>
                                <th><?php _e('Quantity', 'zpos'); ?></th>
                                <th><?php _e('Price', 'zpos'); ?></th>
                                <th><?php _e('Total', 'zpos'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            ${itemsHtml}
                        </tbody>
                    </table>
                </div>
            </div>
        `;
        
        $('#order-details-content').html(html);
    }
    
    // Update order status
    $(document).on('change', '.order-status-update', function() {
        const orderId = $(this).data('order-id');
        const newStatus = $(this).val();
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'zpos_update_order_status',
                nonce: zpos_ajax.nonce,
                order_id: orderId,
                status: newStatus
            },
            success: function(response) {
                if (response.success) {
                    loadOrders(); // Reload orders
                } else {
                    alert(response.data || '<?php _e('Error updating order status', 'zpos'); ?>');
                }
            }
        });
    });
    
    // Export orders
    $('#export-orders').on('click', function() {
        const filters = {
            status: $('#status-filter').val(),
            date_from: $('#date-from').val(),
            date_to: $('#date-to').val(),
            search: $('#search-orders').val()
        };
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'zpos_export_orders',
                nonce: zpos_ajax.nonce,
                ...filters
            },
            success: function(response) {
                if (response.success) {
                    // Create download link
                    const link = document.createElement('a');
                    link.href = response.data.file_url;
                    link.download = response.data.filename;
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                } else {
                    alert(response.data || '<?php _e('Error exporting orders', 'zpos'); ?>');
                }
            }
        });
    });
    
    // WooCommerce sync
    $('#sync-woocommerce').on('click', function() {
        $('#wc-sync-modal').show();
    });
    
    // WooCommerce sync form submit
    $('#wc-sync-form').on('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const syncData = {
            action: 'zpos_sync_woocommerce_orders',
            nonce: zpos_ajax.nonce,
            date_from: formData.get('sync_date_from'),
            date_to: formData.get('sync_date_to'),
            status: formData.getAll('sync_status[]')
        };
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: syncData,
            success: function(response) {
                $('#wc-sync-modal').hide();
                
                if (response.success) {
                    alert(`<?php _e('Synced', 'zpos'); ?> ${response.synced} <?php _e('orders out of', 'zpos'); ?> ${response.total}`);
                    loadOrders(); // Reload orders
                } else {
                    alert(response.message || '<?php _e('Error syncing orders', 'zpos'); ?>');
                }
            }
        });
    });
    
    // Modal close handlers
    $('.zpos-modal-close, .zpos-modal').on('click', function(e) {
        if (e.target === this) {
            $('.zpos-modal').hide();
        }
    });
});
</script>

<style>
.zpos-orders .zpos-filters-container {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin: 20px 0;
    padding: 15px;
    background: #fff;
    border: 1px solid #ccd0d4;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.zpos-filters {
    display: flex;
    gap: 10px;
    align-items: center;
}

.zpos-actions {
    display: flex;
    gap: 10px;
}

.zpos-table-container {
    background: #fff;
    border: 1px solid #ccd0d4;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.zpos-loading {
    text-align: center;
    padding: 40px;
}

.zpos-no-data {
    text-align: center;
    padding: 40px;
    color: #666;
}

.order-status {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 3px;
    font-size: 11px;
    font-weight: bold;
    text-transform: uppercase;
}

.order-status.status-pending {
    background: #ffb900;
    color: #fff;
}

.order-status.status-processing {
    background: #007cba;
    color: #fff;
}

.order-status.status-completed {
    background: #46b450;
    color: #fff;
}

.order-status.status-cancelled {
    background: #dc3232;
    color: #fff;
}

.order-status.status-refunded {
    background: #666;
    color: #fff;
}

.zpos-modal {
    display: none;
    position: fixed;
    z-index: 9999;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.4);
}

.zpos-modal-content {
    background-color: #fefefe;
    margin: 5% auto;
    padding: 0;
    border: 1px solid #888;
    width: 80%;
    max-width: 800px;
    border-radius: 4px;
}

.zpos-modal-header {
    padding: 15px 20px;
    background: #f1f1f1;
    border-bottom: 1px solid #ddd;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.zpos-modal-header h2 {
    margin: 0;
}

.zpos-modal-close {
    color: #aaa;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
}

.zpos-modal-close:hover {
    color: #000;
}

.zpos-modal-body {
    padding: 20px;
    max-height: 70vh;
    overflow-y: auto;
}

.zpos-modal-footer {
    padding: 15px 20px;
    border-top: 1px solid #ddd;
    text-align: right;
}

.order-details .order-info,
.order-details .customer-info,
.order-details .order-items {
    margin-bottom: 20px;
}

.order-details h3 {
    margin-top: 0;
    border-bottom: 1px solid #ddd;
    padding-bottom: 5px;
}
</style>
