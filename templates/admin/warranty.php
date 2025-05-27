<?php
/**
 * Admin Warranty Management Template
 *
 * @link       https://yourwebsite.com
 * @since      1.0.0
 *
 * @package    ZPOS
 * @subpackage ZPOS/templates/admin
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Ensure we have the warranty class
if (!class_exists('ZPOS_Warranty')) {
    require_once ZPOS_PLUGIN_DIR . 'includes/warranty.php';
}

$warranty_manager = new ZPOS_Warranty();
$packages = $warranty_manager->get_warranty_packages();
$recent_warranties = $warranty_manager->get_recent_warranties(10);

// Get basic stats
$stats = $warranty_manager->get_warranty_stats();
?>

<div class="wrap zpos-warranty-page">
    <h1 class="wp-heading-inline">
        <?php _e('Warranty Management', 'zpos'); ?>
    </h1>
    <a href="#" class="page-title-action zpos-add-package-btn">
        <?php _e('Add Warranty Package', 'zpos'); ?>
    </a>
    <a href="#" class="page-title-action zpos-add-warranty-btn">
        <?php _e('Register Warranty', 'zpos'); ?>
    </a>
    <hr class="wp-header-end">

    <!-- Warranty Stats Dashboard -->
    <div class="zpos-dashboard-stats">
        <div class="zpos-stat-card">
            <div class="stat-icon">
                <span class="dashicons dashicons-shield-alt"></span>
            </div>
            <div class="stat-content">
                <h3><?php echo number_format($stats['total_warranties']); ?></h3>
                <p><?php _e('Total Warranties', 'zpos'); ?></p>
            </div>
        </div>
        <div class="zpos-stat-card">
            <div class="stat-icon active">
                <span class="dashicons dashicons-yes-alt"></span>
            </div>
            <div class="stat-content">
                <h3><?php echo number_format($stats['active_warranties']); ?></h3>
                <p><?php _e('Active Warranties', 'zpos'); ?></p>
            </div>
        </div>
        <div class="zpos-stat-card">
            <div class="stat-icon warning">
                <span class="dashicons dashicons-clock"></span>
            </div>
            <div class="stat-content">
                <h3><?php echo number_format($stats['expiring_soon']); ?></h3>
                <p><?php _e('Expiring Soon', 'zpos'); ?></p>
            </div>
        </div>
        <div class="zpos-stat-card">
            <div class="stat-icon expired">
                <span class="dashicons dashicons-dismiss"></span>
            </div>
            <div class="stat-content">
                <h3><?php echo number_format($stats['expired_warranties']); ?></h3>
                <p><?php _e('Expired', 'zpos'); ?></p>
            </div>
        </div>
    </div>

    <!-- Warranty Management Tabs -->
    <div class="zpos-warranty-tabs">
        <nav class="nav-tab-wrapper">
            <a href="#warranties" class="nav-tab nav-tab-active" data-tab="warranties">
                <?php _e('Warranties', 'zpos'); ?>
            </a>
            <a href="#packages" class="nav-tab" data-tab="packages">
                <?php _e('Warranty Packages', 'zpos'); ?>
            </a>
            <a href="#reports" class="nav-tab" data-tab="reports">
                <?php _e('Reports', 'zpos'); ?>
            </a>
        </nav>

        <!-- Warranties Tab -->
        <div id="warranties-tab" class="tab-content active">
            <div class="zpos-table-header">
                <div class="zpos-filters">
                    <select id="warranty-status-filter">
                        <option value=""><?php _e('All Statuses', 'zpos'); ?></option>
                        <option value="active"><?php _e('Active', 'zpos'); ?></option>
                        <option value="expired"><?php _e('Expired', 'zpos'); ?></option>
                        <option value="claimed"><?php _e('Claimed', 'zpos'); ?></option>
                        <option value="cancelled"><?php _e('Cancelled', 'zpos'); ?></option>
                    </select>
                    <select id="warranty-package-filter">
                        <option value=""><?php _e('All Packages', 'zpos'); ?></option>
                        <?php foreach ($packages as $package): ?>
                        <option value="<?php echo esc_attr($package->id); ?>">
                            <?php echo esc_html($package->name); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <input type="text" id="warranty-search" placeholder="<?php _e('Search by serial number, customer...', 'zpos'); ?>">
                    <button type="button" class="button" id="warranty-filter-btn">
                        <?php _e('Filter', 'zpos'); ?>
                    </button>
                </div>
                <div class="zpos-actions">
                    <button type="button" class="button" id="warranty-export-btn">
                        <?php _e('Export', 'zpos'); ?>
                    </button>
                    <button type="button" class="button" id="warranty-send-notifications">
                        <?php _e('Send Expiry Notifications', 'zpos'); ?>
                    </button>
                </div>
            </div>

            <div class="zpos-table-container">
                <table class="wp-list-table widefat fixed striped" id="warranties-table">
                    <thead>
                        <tr>
                            <th class="check-column">
                                <input type="checkbox" id="select-all-warranties">
                            </th>
                            <th><?php _e('Serial Number', 'zpos'); ?></th>
                            <th><?php _e('Customer', 'zpos'); ?></th>
                            <th><?php _e('Product', 'zpos'); ?></th>
                            <th><?php _e('Package', 'zpos'); ?></th>
                            <th><?php _e('Start Date', 'zpos'); ?></th>
                            <th><?php _e('End Date', 'zpos'); ?></th>
                            <th><?php _e('Status', 'zpos'); ?></th>
                            <th><?php _e('Actions', 'zpos'); ?></th>
                        </tr>
                    </thead>
                    <tbody id="warranties-table-body">
                        <!-- Content loaded via AJAX -->
                    </tbody>
                </table>
            </div>

            <div class="zpos-pagination" id="warranties-pagination">
                <!-- Pagination loaded via AJAX -->
            </div>
        </div>

        <!-- Warranty Packages Tab -->
        <div id="packages-tab" class="tab-content">
            <div class="zpos-table-header">
                <div class="zpos-actions">
                    <button type="button" class="button button-primary zpos-add-package-btn">
                        <?php _e('Add New Package', 'zpos'); ?>
                    </button>
                </div>
            </div>

            <div class="zpos-table-container">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Name', 'zpos'); ?></th>
                            <th><?php _e('Duration', 'zpos'); ?></th>
                            <th><?php _e('Price', 'zpos'); ?></th>
                            <th><?php _e('Description', 'zpos'); ?></th>
                            <th><?php _e('Status', 'zpos'); ?></th>
                            <th><?php _e('Actions', 'zpos'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($packages as $package): ?>
                        <tr data-package-id="<?php echo esc_attr($package->id); ?>">
                            <td><strong><?php echo esc_html($package->name); ?></strong></td>
                            <td><?php echo esc_html($package->duration_months); ?> <?php _e('months', 'zpos'); ?></td>
                            <td><?php echo esc_html(number_format($package->price, 2)); ?></td>
                            <td><?php echo esc_html($package->description); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo esc_attr($package->status); ?>">
                                    <?php echo esc_html(ucfirst($package->status)); ?>
                                </span>
                            </td>
                            <td>
                                <button type="button" class="button button-small edit-package" 
                                        data-package-id="<?php echo esc_attr($package->id); ?>">
                                    <?php _e('Edit', 'zpos'); ?>
                                </button>
                                <button type="button" class="button button-small delete-package" 
                                        data-package-id="<?php echo esc_attr($package->id); ?>">
                                    <?php _e('Delete', 'zpos'); ?>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Reports Tab -->
        <div id="reports-tab" class="tab-content">
            <div class="zpos-report-filters">
                <label for="report-date-from"><?php _e('From:', 'zpos'); ?></label>
                <input type="date" id="report-date-from" value="<?php echo date('Y-m-01'); ?>">
                
                <label for="report-date-to"><?php _e('To:', 'zpos'); ?></label>
                <input type="date" id="report-date-to" value="<?php echo date('Y-m-t'); ?>">
                
                <button type="button" class="button" id="generate-warranty-report">
                    <?php _e('Generate Report', 'zpos'); ?>
                </button>
            </div>

            <div id="warranty-reports-container">
                <div class="zpos-report-section">
                    <h3><?php _e('Warranty Summary', 'zpos'); ?></h3>
                    <div id="warranty-summary-chart"></div>
                </div>

                <div class="zpos-report-section">
                    <h3><?php _e('Expiring Warranties', 'zpos'); ?></h3>
                    <div id="expiring-warranties-list"></div>
                </div>

                <div class="zpos-report-section">
                    <h3><?php _e('Most Used Packages', 'zpos'); ?></h3>
                    <div id="popular-packages-chart"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add/Edit Warranty Package Modal -->
<div id="warranty-package-modal" class="zpos-modal" style="display: none;">
    <div class="zpos-modal-content">
        <div class="zpos-modal-header">
            <h2 id="package-modal-title"><?php _e('Add Warranty Package', 'zpos'); ?></h2>
            <span class="zpos-modal-close">&times;</span>
        </div>
        <div class="zpos-modal-body">
            <form id="warranty-package-form">
                <input type="hidden" id="package-id" name="package_id">
                
                <div class="form-group">
                    <label for="package-name"><?php _e('Package Name', 'zpos'); ?> *</label>
                    <input type="text" id="package-name" name="name" required>
                </div>

                <div class="form-group">
                    <label for="package-duration"><?php _e('Duration (Months)', 'zpos'); ?> *</label>
                    <input type="number" id="package-duration" name="duration_months" min="1" max="120" required>
                </div>

                <div class="form-group">
                    <label for="package-price"><?php _e('Price', 'zpos'); ?></label>
                    <input type="number" id="package-price" name="price" step="0.01" min="0">
                </div>

                <div class="form-group">
                    <label for="package-description"><?php _e('Description', 'zpos'); ?></label>
                    <textarea id="package-description" name="description" rows="3"></textarea>
                </div>

                <div class="form-group">
                    <label for="package-status"><?php _e('Status', 'zpos'); ?></label>
                    <select id="package-status" name="status">
                        <option value="active"><?php _e('Active', 'zpos'); ?></option>
                        <option value="inactive"><?php _e('Inactive', 'zpos'); ?></option>
                    </select>
                </div>
            </form>
        </div>
        <div class="zpos-modal-footer">
            <button type="button" class="button button-secondary" id="cancel-package">
                <?php _e('Cancel', 'zpos'); ?>
            </button>
            <button type="button" class="button button-primary" id="save-package">
                <?php _e('Save Package', 'zpos'); ?>
            </button>
        </div>
    </div>
</div>

<!-- Add/Edit Warranty Modal -->
<div id="warranty-modal" class="zpos-modal" style="display: none;">
    <div class="zpos-modal-content">
        <div class="zpos-modal-header">
            <h2 id="warranty-modal-title"><?php _e('Register Warranty', 'zpos'); ?></h2>
            <span class="zpos-modal-close">&times;</span>
        </div>
        <div class="zpos-modal-body">
            <form id="warranty-form">
                <input type="hidden" id="warranty-id" name="warranty_id">
                
                <div class="form-group">
                    <label for="warranty-customer"><?php _e('Customer', 'zpos'); ?> *</label>
                    <select id="warranty-customer" name="customer_id" required>
                        <option value=""><?php _e('Select Customer', 'zpos'); ?></option>
                        <!-- Populated via AJAX -->
                    </select>
                </div>

                <div class="form-group">
                    <label for="warranty-product"><?php _e('Product', 'zpos'); ?> *</label>
                    <select id="warranty-product" name="product_id" required>
                        <option value=""><?php _e('Select Product', 'zpos'); ?></option>
                        <!-- Populated via AJAX -->
                    </select>
                </div>

                <div class="form-group">
                    <label for="warranty-package"><?php _e('Warranty Package', 'zpos'); ?> *</label>
                    <select id="warranty-package" name="package_id" required>
                        <option value=""><?php _e('Select Package', 'zpos'); ?></option>
                        <?php foreach ($packages as $package): ?>
                        <option value="<?php echo esc_attr($package->id); ?>">
                            <?php echo esc_html($package->name . ' (' . $package->duration_months . ' months)'); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="warranty-serial"><?php _e('Serial Number', 'zpos'); ?></label>
                    <div class="input-group">
                        <input type="text" id="warranty-serial" name="serial_number">
                        <button type="button" class="button" id="generate-serial">
                            <?php _e('Generate', 'zpos'); ?>
                        </button>
                    </div>
                </div>

                <div class="form-group">
                    <label for="warranty-purchase-date"><?php _e('Purchase Date', 'zpos'); ?> *</label>
                    <input type="date" id="warranty-purchase-date" name="purchase_date" required>
                </div>

                <div class="form-group">
                    <label for="warranty-notes"><?php _e('Notes', 'zpos'); ?></label>
                    <textarea id="warranty-notes" name="notes" rows="3"></textarea>
                </div>
            </form>
        </div>
        <div class="zpos-modal-footer">
            <button type="button" class="button button-secondary" id="cancel-warranty">
                <?php _e('Cancel', 'zpos'); ?>
            </button>
            <button type="button" class="button button-primary" id="save-warranty">
                <?php _e('Save Warranty', 'zpos'); ?>
            </button>
        </div>
    </div>
</div>

<!-- Warranty Details Modal -->
<div id="warranty-details-modal" class="zpos-modal" style="display: none;">
    <div class="zpos-modal-content">
        <div class="zpos-modal-header">
            <h2><?php _e('Warranty Details', 'zpos'); ?></h2>
            <span class="zpos-modal-close">&times;</span>
        </div>
        <div class="zpos-modal-body" id="warranty-details-content">
            <!-- Content loaded via AJAX -->
        </div>
        <div class="zpos-modal-footer">
            <button type="button" class="button button-secondary zpos-modal-close">
                <?php _e('Close', 'zpos'); ?>
            </button>
        </div>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Tab switching
    $('.nav-tab').on('click', function(e) {
        e.preventDefault();
        var tab = $(this).data('tab');
        
        $('.nav-tab').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');
        
        $('.tab-content').removeClass('active');
        $('#' + tab + '-tab').addClass('active');
    });

    // Load warranties table
    function loadWarrantiesTable(page = 1) {
        var filters = {
            status: $('#warranty-status-filter').val(),
            package_id: $('#warranty-package-filter').val(),
            search: $('#warranty-search').val(),
            page: page
        };

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'zpos_get_warranties',
                nonce: zpos_admin.nonce,
                filters: filters
            },
            success: function(response) {
                if (response.success) {
                    $('#warranties-table-body').html(response.data.html);
                    $('#warranties-pagination').html(response.data.pagination);
                }
            }
        });
    }

    // Initial load
    loadWarrantiesTable();

    // Filter warranties
    $('#warranty-filter-btn').on('click', function() {
        loadWarrantiesTable(1);
    });

    // Search on enter
    $('#warranty-search').on('keypress', function(e) {
        if (e.which === 13) {
            loadWarrantiesTable(1);
        }
    });

    // Add warranty package
    $('.zpos-add-package-btn').on('click', function() {
        $('#package-modal-title').text('<?php _e('Add Warranty Package', 'zpos'); ?>');
        $('#warranty-package-form')[0].reset();
        $('#package-id').val('');
        $('#warranty-package-modal').show();
    });

    // Add warranty
    $('.zpos-add-warranty-btn').on('click', function() {
        $('#warranty-modal-title').text('<?php _e('Register Warranty', 'zpos'); ?>');
        $('#warranty-form')[0].reset();
        $('#warranty-id').val('');
        loadCustomersAndProducts();
        $('#warranty-modal').show();
    });

    // Save warranty package
    $('#save-package').on('click', function() {
        var formData = new FormData($('#warranty-package-form')[0]);
        formData.append('action', 'zpos_save_warranty_package');
        formData.append('nonce', zpos_admin.nonce);

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    $('#warranty-package-modal').hide();
                    location.reload(); // Refresh to show new package
                } else {
                    alert(response.data);
                }
            }
        });
    });

    // Save warranty
    $('#save-warranty').on('click', function() {
        var formData = new FormData($('#warranty-form')[0]);
        formData.append('action', 'zpos_save_warranty');
        formData.append('nonce', zpos_admin.nonce);

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    $('#warranty-modal').hide();
                    loadWarrantiesTable();
                } else {
                    alert(response.data);
                }
            }
        });
    });

    // Generate serial number
    $('#generate-serial').on('click', function() {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'zpos_generate_serial_number',
                nonce: zpos_admin.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#warranty-serial').val(response.data);
                }
            }
        });
    });

    // Load customers and products for warranty form
    function loadCustomersAndProducts() {
        // Load customers
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'zpos_get_customers_list',
                nonce: zpos_admin.nonce
            },
            success: function(response) {
                if (response.success) {
                    var options = '<option value=""><?php _e('Select Customer', 'zpos'); ?></option>';
                    $.each(response.data, function(index, customer) {
                        options += '<option value="' + customer.id + '">' + customer.name + '</option>';
                    });
                    $('#warranty-customer').html(options);
                }
            }
        });

        // Load products
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'zpos_get_products_list',
                nonce: zpos_admin.nonce
            },
            success: function(response) {
                if (response.success) {
                    var options = '<option value=""><?php _e('Select Product', 'zpos'); ?></option>';
                    $.each(response.data, function(index, product) {
                        options += '<option value="' + product.id + '">' + product.name + '</option>';
                    });
                    $('#warranty-product').html(options);
                }
            }
        });
    }

    // Modal close handlers
    $('.zpos-modal-close, #cancel-package, #cancel-warranty').on('click', function() {
        $('.zpos-modal').hide();
    });

    // Export warranties
    $('#warranty-export-btn').on('click', function() {
        var filters = {
            status: $('#warranty-status-filter').val(),
            package_id: $('#warranty-package-filter').val(),
            search: $('#warranty-search').val()
        };

        window.location.href = ajaxurl + '?' + $.param({
            action: 'zpos_export_warranties',
            nonce: zpos_admin.nonce,
            filters: JSON.stringify(filters)
        });
    });

    // Send expiry notifications
    $('#warranty-send-notifications').on('click', function() {
        $(this).prop('disabled', true).text('<?php _e('Sending...', 'zpos'); ?>');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'zpos_send_warranty_notifications',
                nonce: zpos_admin.nonce
            },
            success: function(response) {
                if (response.success) {
                    alert('<?php _e('Notifications sent successfully!', 'zpos'); ?>');
                } else {
                    alert(response.data);
                }
            },
            complete: function() {
                $('#warranty-send-notifications').prop('disabled', false).text('<?php _e('Send Expiry Notifications', 'zpos'); ?>');
            }
        });
    });

    // Generate warranty report
    $('#generate-warranty-report').on('click', function() {
        var dateFrom = $('#report-date-from').val();
        var dateTo = $('#report-date-to').val();

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'zpos_generate_warranty_report',
                nonce: zpos_admin.nonce,
                date_from: dateFrom,
                date_to: dateTo
            },
            success: function(response) {
                if (response.success) {
                    $('#warranty-reports-container').html(response.data.html);
                }
            }
        });
    });
});
</script>

<style>
.zpos-warranty-page .zpos-dashboard-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin: 20px 0;
}

.zpos-stat-card {
    background: #fff;
    border: 1px solid #e1e1e1;
    border-radius: 4px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 15px;
}

.zpos-stat-card .stat-icon {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f1f1f1;
}

.zpos-stat-card .stat-icon.active {
    background: #46b450;
    color: white;
}

.zpos-stat-card .stat-icon.warning {
    background: #ffb900;
    color: white;
}

.zpos-stat-card .stat-icon.expired {
    background: #dc3232;
    color: white;
}

.zpos-warranty-tabs .tab-content {
    display: none;
    padding: 20px 0;
}

.zpos-warranty-tabs .tab-content.active {
    display: block;
}

.zpos-table-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    flex-wrap: wrap;
    gap: 10px;
}

.zpos-filters {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.zpos-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.status-badge {
    padding: 4px 8px;
    border-radius: 3px;
    font-size: 12px;
    font-weight: bold;
    text-transform: uppercase;
}

.status-active {
    background: #46b450;
    color: white;
}

.status-expired {
    background: #dc3232;
    color: white;
}

.status-claimed {
    background: #00a0d2;
    color: white;
}

.status-cancelled {
    background: #666;
    color: white;
}

.zpos-modal {
    position: fixed;
    z-index: 100000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
}

.zpos-modal-content {
    background-color: #fefefe;
    margin: 5% auto;
    border: 1px solid #888;
    width: 90%;
    max-width: 600px;
    border-radius: 4px;
}

.zpos-modal-header {
    padding: 20px;
    border-bottom: 1px solid #e1e1e1;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.zpos-modal-close {
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
}

.zpos-modal-body {
    padding: 20px;
}

.zpos-modal-footer {
    padding: 20px;
    border-top: 1px solid #e1e1e1;
    text-align: right;
}

.form-group {
    margin-bottom: 15px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
}

.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 3px;
}

.input-group {
    display: flex;
    gap: 5px;
}

.input-group input {
    flex: 1;
}

.zpos-report-filters {
    display: flex;
    gap: 15px;
    align-items: center;
    margin-bottom: 20px;
    flex-wrap: wrap;
}

.zpos-report-section {
    margin-bottom: 30px;
    background: #fff;
    border: 1px solid #e1e1e1;
    border-radius: 4px;
    padding: 20px;
}

@media (max-width: 768px) {
    .zpos-table-header {
        flex-direction: column;
        align-items: stretch;
    }
    
    .zpos-filters,
    .zpos-actions {
        justify-content: center;
    }
    
    .zpos-modal-content {
        width: 95%;
        margin: 2% auto;
    }
}
</style>
