<?php
/**
 * Inventory admin template
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

<div class="wrap zpos-inventory">
    <h1 class="wp-heading-inline"><?php _e('Inventory Management', 'zpos'); ?></h1>
    
    <!-- Quick Stats -->
    <div class="zpos-stats-container">
        <div class="zpos-stat-card">
            <div class="zpos-stat-number" id="total-items">-</div>
            <div class="zpos-stat-label"><?php _e('Total Items', 'zpos'); ?></div>
        </div>
        <div class="zpos-stat-card low-stock">
            <div class="zpos-stat-number" id="low-stock-count">-</div>
            <div class="zpos-stat-label"><?php _e('Low Stock', 'zpos'); ?></div>
        </div>
        <div class="zpos-stat-card out-of-stock">
            <div class="zpos-stat-number" id="out-of-stock-count">-</div>
            <div class="zpos-stat-label"><?php _e('Out of Stock', 'zpos'); ?></div>
        </div>
        <div class="zpos-stat-card">
            <div class="zpos-stat-number" id="total-value">-</div>
            <div class="zpos-stat-label"><?php _e('Total Value', 'zpos'); ?></div>
        </div>
    </div>
    
    <!-- Filters and Actions -->
    <div class="zpos-filters-container">
        <div class="zpos-filters">
            <input type="text" id="search-inventory" class="regular-text" placeholder="<?php _e('Search products...', 'zpos'); ?>">
            
            <select id="category-filter" class="regular-text">
                <option value=""><?php _e('All Categories', 'zpos'); ?></option>
                <!-- Categories will be loaded dynamically -->
            </select>
            
            <label>
                <input type="checkbox" id="low-stock-filter">
                <?php _e('Low Stock Only', 'zpos'); ?>
            </label>
            
            <label>
                <input type="checkbox" id="out-of-stock-filter">
                <?php _e('Out of Stock Only', 'zpos'); ?>
            </label>
            
            <button type="button" id="filter-inventory" class="button"><?php _e('Filter', 'zpos'); ?></button>
            <button type="button" id="reset-filters" class="button"><?php _e('Reset', 'zpos'); ?></button>
        </div>
        
        <div class="zpos-actions">
            <button type="button" id="bulk-stock-update" class="button button-secondary">
                <span class="dashicons dashicons-edit"></span>
                <?php _e('Bulk Update', 'zpos'); ?>
            </button>
            
            <button type="button" id="view-movements" class="button button-secondary">
                <span class="dashicons dashicons-admin-generic"></span>
                <?php _e('Movements', 'zpos'); ?>
            </button>
            
            <button type="button" id="generate-report" class="button button-secondary">
                <span class="dashicons dashicons-chart-bar"></span>
                <?php _e('Report', 'zpos'); ?>
            </button>
            
            <button type="button" id="export-inventory" class="button button-secondary">
                <span class="dashicons dashicons-download"></span>
                <?php _e('Export', 'zpos'); ?>
            </button>
        </div>
    </div>
    
    <!-- Low Stock Alerts -->
    <div id="low-stock-alerts" class="zpos-alerts" style="display: none;">
        <div class="notice notice-warning">
            <h3><?php _e('Low Stock Alerts', 'zpos'); ?></h3>
            <div id="low-stock-list"></div>
            <button type="button" class="button button-small" id="dismiss-alerts"><?php _e('Dismiss', 'zpos'); ?></button>
        </div>
    </div>
    
    <!-- Inventory Table -->
    <div class="zpos-table-container">
        <table class="wp-list-table widefat fixed striped" id="inventory-table">
            <thead>
                <tr>
                    <th class="manage-column column-cb check-column">
                        <input type="checkbox" id="cb-select-all">
                    </th>
                    <th class="manage-column sortable" data-orderby="product_name">
                        <a href="#"><span><?php _e('Product', 'zpos'); ?></span><span class="sorting-indicators"></span></a>
                    </th>
                    <th class="manage-column"><?php _e('SKU', 'zpos'); ?></th>
                    <th class="manage-column"><?php _e('Category', 'zpos'); ?></th>
                    <th class="manage-column sortable" data-orderby="current_stock">
                        <a href="#"><span><?php _e('Stock', 'zpos'); ?></span><span class="sorting-indicators"></span></a>
                    </th>
                    <th class="manage-column"><?php _e('Threshold', 'zpos'); ?></th>
                    <th class="manage-column"><?php _e('Status', 'zpos'); ?></th>
                    <th class="manage-column sortable" data-orderby="unit_price">
                        <a href="#"><span><?php _e('Unit Price', 'zpos'); ?></span><span class="sorting-indicators"></span></a>
                    </th>
                    <th class="manage-column"><?php _e('Total Value', 'zpos'); ?></th>
                    <th class="manage-column"><?php _e('Actions', 'zpos'); ?></th>
                </tr>
            </thead>
            <tbody id="inventory-tbody">
                <!-- Inventory items will be loaded here via AJAX -->
            </tbody>
        </table>
        
        <!-- Loading indicator -->
        <div id="inventory-loading" class="zpos-loading" style="display: none;">
            <span class="spinner is-active"></span>
            <span><?php _e('Loading inventory...', 'zpos'); ?></span>
        </div>
        
        <!-- No items found -->
        <div id="no-inventory" class="zpos-no-data" style="display: none;">
            <p><?php _e('No inventory items found.', 'zpos'); ?></p>
        </div>
    </div>
    
    <!-- Pagination -->
    <div class="zpos-pagination-container">
        <div class="tablenav bottom">
            <div class="alignleft actions bulkactions">
                <select id="bulk-action">
                    <option value="-1"><?php _e('Bulk Actions', 'zpos'); ?></option>
                    <option value="adjust_stock"><?php _e('Adjust Stock', 'zpos'); ?></option>
                    <option value="set_threshold"><?php _e('Set Threshold', 'zpos'); ?></option>
                </select>
                <input type="submit" id="doaction" class="button action" value="<?php _e('Apply', 'zpos'); ?>">
            </div>
            
            <div class="tablenav-pages" id="pagination-container">
                <!-- Pagination will be loaded here -->
            </div>
        </div>
    </div>
</div>

<!-- Stock Update Modal -->
<div id="stock-update-modal" class="zpos-modal" style="display: none;">
    <div class="zpos-modal-content">
        <div class="zpos-modal-header">
            <h2><?php _e('Update Stock', 'zpos'); ?></h2>
            <span class="zpos-modal-close">&times;</span>
        </div>
        <div class="zpos-modal-body">
            <form id="stock-update-form">
                <input type="hidden" id="update-product-id">
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Product', 'zpos'); ?></th>
                        <td id="update-product-name"></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Current Stock', 'zpos'); ?></th>
                        <td id="update-current-stock"></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Adjustment', 'zpos'); ?></th>
                        <td>
                            <input type="number" id="stock-adjustment" class="regular-text" placeholder="<?php _e('Enter positive or negative number', 'zpos'); ?>">
                            <p class="description"><?php _e('Enter positive number to add stock, negative to remove', 'zpos'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Reason', 'zpos'); ?></th>
                        <td>
                            <textarea id="adjustment-reason" class="regular-text" rows="3" placeholder="<?php _e('Optional reason for adjustment', 'zpos'); ?>"></textarea>
                        </td>
                    </tr>
                </table>
                
                <div class="zpos-modal-footer">
                    <button type="button" class="button button-secondary zpos-modal-close"><?php _e('Cancel', 'zpos'); ?></button>
                    <button type="submit" class="button button-primary"><?php _e('Update Stock', 'zpos'); ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Bulk Stock Update Modal -->
<div id="bulk-stock-modal" class="zpos-modal" style="display: none;">
    <div class="zpos-modal-content">
        <div class="zpos-modal-header">
            <h2><?php _e('Bulk Stock Update', 'zpos'); ?></h2>
            <span class="zpos-modal-close">&times;</span>
        </div>
        <div class="zpos-modal-body">
            <form id="bulk-stock-form">
                <p><?php _e('Upload a CSV file to bulk update stock levels. The CSV should have columns: product_id, adjustment, reason', 'zpos'); ?></p>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('CSV File', 'zpos'); ?></th>
                        <td>
                            <input type="file" id="bulk-csv-file" accept=".csv">
                            <p class="description"><?php _e('Select a CSV file with stock adjustments', 'zpos'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('General Reason', 'zpos'); ?></th>
                        <td>
                            <input type="text" id="bulk-reason" class="regular-text" placeholder="<?php _e('Bulk stock adjustment', 'zpos'); ?>">
                        </td>
                    </tr>
                </table>
                
                <div class="zpos-modal-footer">
                    <button type="button" class="button button-secondary zpos-modal-close"><?php _e('Cancel', 'zpos'); ?></button>
                    <button type="submit" class="button button-primary"><?php _e('Process Updates', 'zpos'); ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Inventory Movements Modal -->
<div id="movements-modal" class="zpos-modal" style="display: none;">
    <div class="zpos-modal-content zpos-modal-large">
        <div class="zpos-modal-header">
            <h2><?php _e('Inventory Movements', 'zpos'); ?></h2>
            <span class="zpos-modal-close">&times;</span>
        </div>
        <div class="zpos-modal-body">
            <!-- Movement filters -->
            <div class="zpos-movement-filters">
                <select id="movement-type-filter">
                    <option value=""><?php _e('All Types', 'zpos'); ?></option>
                    <option value="manual"><?php _e('Manual', 'zpos'); ?></option>
                    <option value="sale"><?php _e('Sale', 'zpos'); ?></option>
                    <option value="purchase"><?php _e('Purchase', 'zpos'); ?></option>
                    <option value="adjustment"><?php _e('Adjustment', 'zpos'); ?></option>
                    <option value="bulk_adjustment"><?php _e('Bulk Adjustment', 'zpos'); ?></option>
                </select>
                
                <input type="date" id="movement-date-from">
                <input type="date" id="movement-date-to">
                
                <button type="button" id="filter-movements" class="button"><?php _e('Filter', 'zpos'); ?></button>
            </div>
            
            <!-- Movements table -->
            <table class="wp-list-table widefat fixed striped" id="movements-table">
                <thead>
                    <tr>
                        <th><?php _e('Date', 'zpos'); ?></th>
                        <th><?php _e('Product', 'zpos'); ?></th>
                        <th><?php _e('Type', 'zpos'); ?></th>
                        <th><?php _e('Change', 'zpos'); ?></th>
                        <th><?php _e('Before', 'zpos'); ?></th>
                        <th><?php _e('After', 'zpos'); ?></th>
                        <th><?php _e('Reason', 'zpos'); ?></th>
                    </tr>
                </thead>
                <tbody id="movements-tbody">
                    <!-- Movements will be loaded here -->
                </tbody>
            </table>
            
            <div id="movements-loading" class="zpos-loading" style="display: none;">
                <span class="spinner is-active"></span>
                <span><?php _e('Loading movements...', 'zpos'); ?></span>
            </div>
        </div>
    </div>
</div>

<!-- Inventory Report Modal -->
<div id="report-modal" class="zpos-modal" style="display: none;">
    <div class="zpos-modal-content zpos-modal-large">
        <div class="zpos-modal-header">
            <h2><?php _e('Inventory Report', 'zpos'); ?></h2>
            <span class="zpos-modal-close">&times;</span>
        </div>
        <div class="zpos-modal-body">
            <!-- Report filters -->
            <div class="zpos-report-filters">
                <label><?php _e('Date Range:', 'zpos'); ?></label>
                <input type="date" id="report-date-from" value="<?php echo date('Y-m-01'); ?>">
                <input type="date" id="report-date-to" value="<?php echo date('Y-m-d'); ?>">
                <button type="button" id="generate-report-btn" class="button button-primary"><?php _e('Generate Report', 'zpos'); ?></button>
            </div>
            
            <!-- Report content -->
            <div id="report-content">
                <!-- Report will be loaded here -->
            </div>
            
            <div id="report-loading" class="zpos-loading" style="display: none;">
                <span class="spinner is-active"></span>
                <span><?php _e('Generating report...', 'zpos'); ?></span>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    let currentPage = 1;
    let currentFilters = {};
    
    // Load inventory on page load
    loadInventory();
    loadLowStockAlerts();
    
    // Filter inventory
    $('#filter-inventory').on('click', function() {
        currentPage = 1;
        loadInventory();
    });
    
    // Reset filters
    $('#reset-filters').on('click', function() {
        $('#search-inventory').val('');
        $('#category-filter').val('');
        $('#low-stock-filter').prop('checked', false);
        $('#out-of-stock-filter').prop('checked', false);
        currentPage = 1;
        loadInventory();
    });
    
    // Search inventory
    $('#search-inventory').on('keypress', function(e) {
        if (e.which === 13) {
            currentPage = 1;
            loadInventory();
        }
    });
    
    // Sort inventory
    $('.sortable').on('click', function(e) {
        e.preventDefault();
        const orderby = $(this).data('orderby');
        const currentOrder = $(this).hasClass('asc') ? 'desc' : 'asc';
        
        $('.sortable').removeClass('asc desc');
        $(this).addClass(currentOrder);
        
        currentFilters.orderby = orderby;
        currentFilters.order = currentOrder;
        currentPage = 1;
        loadInventory();
    });
    
    // Load inventory function
    function loadInventory() {
        $('#inventory-loading').show();
        $('#inventory-tbody').empty();
        $('#no-inventory').hide();
        
        currentFilters = {
            search: $('#search-inventory').val(),
            category: $('#category-filter').val(),
            low_stock_only: $('#low-stock-filter').is(':checked'),
            out_of_stock_only: $('#out-of-stock-filter').is(':checked'),
            page: currentPage,
            orderby: currentFilters.orderby || 'product_name',
            order: currentFilters.order || 'asc'
        };
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'zpos_get_inventory',
                nonce: zpos_ajax.nonce,
                ...currentFilters
            },
            success: function(response) {
                $('#inventory-loading').hide();
                
                if (response.success && response.data.items.length > 0) {
                    displayInventory(response.data.items);
                    displayPagination(response.data);
                    updateStats(response.data);
                } else {
                    $('#no-inventory').show();
                }
            },
            error: function() {
                $('#inventory-loading').hide();
                alert('<?php _e('Error loading inventory', 'zpos'); ?>');
            }
        });
    }
    
    // Display inventory in table
    function displayInventory(items) {
        let html = '';
        
        items.forEach(function(item) {
            const statusClass = 'status-' + item.stock_status;
            const statusLabel = getStatusLabel(item.stock_status);
            const totalValue = (item.current_stock * item.unit_price).toFixed(2);
            
            html += `
                <tr>
                    <td class="check-column">
                        <input type="checkbox" name="item[]" value="${item.product_id}">
                    </td>
                    <td><strong>${item.product_name}</strong></td>
                    <td>${item.sku || '-'}</td>
                    <td>${item.category || '-'}</td>
                    <td><strong>${item.current_stock}</strong></td>
                    <td>
                        <input type="number" class="small-text threshold-input" 
                               data-product-id="${item.product_id}" 
                               value="${item.low_stock_threshold}" 
                               min="0">
                    </td>
                    <td><span class="stock-status ${statusClass}">${statusLabel}</span></td>
                    <td>$${parseFloat(item.unit_price).toFixed(2)}</td>
                    <td>$${totalValue}</td>
                    <td>
                        <button type="button" class="button button-small stock-adjust" 
                                data-product-id="${item.product_id}" 
                                data-product-name="${item.product_name}"
                                data-current-stock="${item.current_stock}">
                            <?php _e('Adjust', 'zpos'); ?>
                        </button>
                    </td>
                </tr>
            `;
        });
        
        $('#inventory-tbody').html(html);
    }
    
    // Get status label
    function getStatusLabel(status) {
        const labels = {
            'in_stock': '<?php _e('In Stock', 'zpos'); ?>',
            'low_stock': '<?php _e('Low Stock', 'zpos'); ?>',
            'out_of_stock': '<?php _e('Out of Stock', 'zpos'); ?>'
        };
        return labels[status] || status;
    }
    
    // Update stats
    function updateStats(data) {
        // These would be calculated from the server response
        // For now, we'll make separate AJAX calls
        // In production, you might want to include this in the main inventory response
    }
    
    // Load low stock alerts
    function loadLowStockAlerts() {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'zpos_get_low_stock_alerts',
                nonce: zpos_ajax.nonce
            },
            success: function(response) {
                if (response.success && response.data.length > 0) {
                    displayLowStockAlerts(response.data);
                }
            }
        });
    }
    
    // Display low stock alerts
    function displayLowStockAlerts(alerts) {
        let html = '<ul>';
        alerts.forEach(function(item) {
            html += `<li><strong>${item.product_name}</strong> - ${item.current_stock} remaining (threshold: ${item.low_stock_threshold})</li>`;
        });
        html += '</ul>';
        
        $('#low-stock-list').html(html);
        $('#low-stock-alerts').show();
    }
    
    // Dismiss alerts
    $('#dismiss-alerts').on('click', function() {
        $('#low-stock-alerts').hide();
    });
    
    // Stock adjustment
    $(document).on('click', '.stock-adjust', function() {
        const productId = $(this).data('product-id');
        const productName = $(this).data('product-name');
        const currentStock = $(this).data('current-stock');
        
        $('#update-product-id').val(productId);
        $('#update-product-name').text(productName);
        $('#update-current-stock').text(currentStock);
        $('#stock-adjustment').val('');
        $('#adjustment-reason').val('');
        
        $('#stock-update-modal').show();
    });
    
    // Stock update form
    $('#stock-update-form').on('submit', function(e) {
        e.preventDefault();
        
        const productId = $('#update-product-id').val();
        const adjustment = parseInt($('#stock-adjustment').val());
        const reason = $('#adjustment-reason').val();
        
        if (isNaN(adjustment)) {
            alert('<?php _e('Please enter a valid adjustment number', 'zpos'); ?>');
            return;
        }
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'zpos_update_stock',
                nonce: zpos_ajax.nonce,
                product_id: productId,
                quantity: adjustment,
                reason: reason
            },
            success: function(response) {
                $('#stock-update-modal').hide();
                
                if (response.success) {
                    loadInventory(); // Reload inventory
                } else {
                    alert(response.data || '<?php _e('Error updating stock', 'zpos'); ?>');
                }
            }
        });
    });
    
    // Threshold update
    $(document).on('change', '.threshold-input', function() {
        const productId = $(this).data('product-id');
        const threshold = parseInt($(this).val());
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'zpos_update_stock_threshold',
                nonce: zpos_ajax.nonce,
                product_id: productId,
                threshold: threshold
            },
            success: function(response) {
                if (!response.success) {
                    alert(response.data || '<?php _e('Error updating threshold', 'zpos'); ?>');
                }
            }
        });
    });
    
    // Export inventory
    $('#export-inventory').on('click', function() {
        const filters = {
            search: $('#search-inventory').val(),
            category: $('#category-filter').val(),
            low_stock_only: $('#low-stock-filter').is(':checked'),
            out_of_stock_only: $('#out-of-stock-filter').is(':checked')
        };
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'zpos_export_inventory',
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
                    alert(response.data || '<?php _e('Error exporting inventory', 'zpos'); ?>');
                }
            }
        });
    });
    
    // View movements
    $('#view-movements').on('click', function() {
        $('#movements-modal').show();
        loadMovements();
    });
    
    // Load movements
    function loadMovements() {
        $('#movements-loading').show();
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'zpos_get_inventory_movements',
                nonce: zpos_ajax.nonce,
                type: $('#movement-type-filter').val(),
                date_from: $('#movement-date-from').val(),
                date_to: $('#movement-date-to').val()
            },
            success: function(response) {
                $('#movements-loading').hide();
                
                if (response.success) {
                    displayMovements(response.data.movements);
                }
            }
        });
    }
    
    // Display movements
    function displayMovements(movements) {
        let html = '';
        
        movements.forEach(function(movement) {
            const changeClass = movement.quantity_change > 0 ? 'positive' : 'negative';
            const changeIcon = movement.quantity_change > 0 ? '+' : '';
            
            html += `
                <tr>
                    <td>${new Date(movement.created_at).toLocaleDateString()}</td>
                    <td>${movement.product_name} (${movement.sku})</td>
                    <td>${movement.type}</td>
                    <td class="${changeClass}">${changeIcon}${movement.quantity_change}</td>
                    <td>${movement.stock_before}</td>
                    <td>${movement.stock_after}</td>
                    <td>${movement.reason || '-'}</td>
                </tr>
            `;
        });
        
        $('#movements-tbody').html(html);
    }
    
    // Generate report
    $('#generate-report').on('click', function() {
        $('#report-modal').show();
    });
    
    $('#generate-report-btn').on('click', function() {
        $('#report-loading').show();
        $('#report-content').empty();
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'zpos_generate_inventory_report',
                nonce: zpos_ajax.nonce,
                date_from: $('#report-date-from').val(),
                date_to: $('#report-date-to').val()
            },
            success: function(response) {
                $('#report-loading').hide();
                
                if (response.success) {
                    displayReport(response.data);
                }
            }
        });
    });
    
    // Display report
    function displayReport(reportData) {
        const html = `
            <div class="zpos-report">
                <h3><?php _e('Summary', 'zpos'); ?></h3>
                <div class="report-summary">
                    <div class="summary-item">
                        <strong><?php _e('Total Items:', 'zpos'); ?></strong> ${reportData.summary.total_items}
                    </div>
                    <div class="summary-item">
                        <strong><?php _e('Total Value:', 'zpos'); ?></strong> $${parseFloat(reportData.summary.total_value || 0).toFixed(2)}
                    </div>
                    <div class="summary-item">
                        <strong><?php _e('Low Stock Items:', 'zpos'); ?></strong> ${reportData.summary.low_stock_count}
                    </div>
                    <div class="summary-item">
                        <strong><?php _e('Out of Stock Items:', 'zpos'); ?></strong> ${reportData.summary.out_of_stock_count}
                    </div>
                    <div class="summary-item">
                        <strong><?php _e('Movements (Period):', 'zpos'); ?></strong> ${reportData.summary.movements_count}
                    </div>
                </div>
            </div>
        `;
        
        $('#report-content').html(html);
    }
    
    // Modal close handlers
    $('.zpos-modal-close, .zpos-modal').on('click', function(e) {
        if (e.target === this) {
            $('.zpos-modal').hide();
        }
    });
    
    // Pagination
    $(document).on('click', '.pagination-links a', function(e) {
        e.preventDefault();
        currentPage = parseInt($(this).data('page'));
        loadInventory();
    });
    
    // Display pagination (reuse from orders)
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
});
</script>

<style>
.zpos-inventory .zpos-stats-container {
    display: flex;
    gap: 20px;
    margin: 20px 0;
}

.zpos-stat-card {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 20px;
    text-align: center;
    flex: 1;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.zpos-stat-card.low-stock {
    border-left: 4px solid #ffb900;
}

.zpos-stat-card.out-of-stock {
    border-left: 4px solid #dc3232;
}

.zpos-stat-number {
    font-size: 2em;
    font-weight: bold;
    color: #2271b1;
    margin-bottom: 5px;
}

.zpos-stat-label {
    color: #666;
    font-size: 0.9em;
}

.zpos-filters-container {
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
    gap: 15px;
    align-items: center;
}

.zpos-filters label {
    display: flex;
    align-items: center;
    gap: 5px;
}

.zpos-actions {
    display: flex;
    gap: 10px;
}

.zpos-alerts {
    margin: 20px 0;
}

.stock-status {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 3px;
    font-size: 11px;
    font-weight: bold;
    text-transform: uppercase;
}

.stock-status.status-in_stock {
    background: #46b450;
    color: #fff;
}

.stock-status.status-low_stock {
    background: #ffb900;
    color: #fff;
}

.stock-status.status-out_of_stock {
    background: #dc3232;
    color: #fff;
}

.threshold-input {
    width: 60px;
}

.zpos-modal-large .zpos-modal-content {
    width: 90%;
    max-width: 1200px;
}

.zpos-movement-filters {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
    padding: 15px;
    background: #f9f9f9;
    border: 1px solid #ddd;
}

.zpos-report-filters {
    display: flex;
    gap: 10px;
    align-items: center;
    margin-bottom: 20px;
    padding: 15px;
    background: #f9f9f9;
    border: 1px solid #ddd;
}

.report-summary {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-bottom: 20px;
}

.summary-item {
    padding: 10px;
    background: #f9f9f9;
    border: 1px solid #ddd;
    border-radius: 4px;
}

#movements-table .positive {
    color: #46b450;
    font-weight: bold;
}

#movements-table .negative {
    color: #dc3232;
    font-weight: bold;
}
</style>
