<?php
/**
 * Admin Dashboard Template for ZPOS
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

// Get dashboard data
$stats = isset($data['stats']) ? $data['stats'] : array();
$charts = isset($data['charts']) ? $data['charts'] : array();
?>

<div class="wrap zpos-admin-wrap">
    <div class="zpos-dashboard-header">
        <h1 class="zpos-dashboard-title">
            <span class="dashicons dashicons-store"></span>
            <?php esc_html_e('ZPOS Dashboard', 'zpos'); ?>
        </h1>
        
        <div class="zpos-dashboard-actions">
            <a href="<?php echo esc_url(admin_url('admin.php?page=zpos-pos')); ?>" class="zpos-btn zpos-btn-primary">
                <span class="dashicons dashicons-cart"></span>
                <?php esc_html_e('Open POS', 'zpos'); ?>
            </a>
            <button type="button" class="zpos-btn zpos-btn-secondary" id="zpos-refresh-dashboard">
                <span class="dashicons dashicons-update"></span>
                <?php esc_html_e('Refresh', 'zpos'); ?>
            </button>
        </div>
    </div>

    <!-- Dashboard Filters -->
    <div class="zpos-dashboard-filters">
        <div class="zpos-filter-row">
            <div class="zpos-filter-group">
                <label for="zpos-date-filter"><?php esc_html_e('Date Range:', 'zpos'); ?></label>
                <select id="zpos-date-filter">
                    <option value="today"><?php esc_html_e('Today', 'zpos'); ?></option>
                    <option value="yesterday"><?php esc_html_e('Yesterday', 'zpos'); ?></option>
                    <option value="week" selected><?php esc_html_e('This Week', 'zpos'); ?></option>
                    <option value="month"><?php esc_html_e('This Month', 'zpos'); ?></option>
                    <option value="year"><?php esc_html_e('This Year', 'zpos'); ?></option>
                    <option value="custom"><?php esc_html_e('Custom Range', 'zpos'); ?></option>
                </select>
            </div>
            
            <div class="zpos-date-inputs">
                <input type="date" id="zpos-start-date">
                <span><?php esc_html_e('to', 'zpos'); ?></span>
                <input type="date" id="zpos-end-date">
            </div>
        </div>
    </div>

    <!-- Dashboard Cards Grid -->
    <div class="zpos-dashboard-grid">
        <!-- Total Products Card -->
        <div class="zpos-stat-card products" data-stat="products">
            <div class="zpos-card-header">
                <h3 class="zpos-card-title"><?php esc_html_e('Total Products', 'zpos'); ?></h3>
                <div class="zpos-card-icon">
                    <span class="dashicons dashicons-products"></span>
                </div>
            </div>
            <div class="zpos-card-value">
                <?php echo esc_html(number_format($stats['total_products'] ?? 0)); ?>
            </div>
            <div class="zpos-card-change positive">
                <span class="dashicons dashicons-trending-up"></span>
                5.2%
            </div>
        </div>

        <!-- Total Customers Card -->
        <div class="zpos-stat-card customers" data-stat="customers">
            <div class="zpos-card-header">
                <h3 class="zpos-card-title"><?php esc_html_e('Total Customers', 'zpos'); ?></h3>
                <div class="zpos-card-icon">
                    <span class="dashicons dashicons-groups"></span>
                </div>
            </div>
            <div class="zpos-card-value">
                <?php echo esc_html(number_format($stats['total_customers'] ?? 0)); ?>
            </div>
            <div class="zpos-card-change positive">
                <span class="dashicons dashicons-trending-up"></span>
                12.1%
            </div>
        </div>

        <!-- Total Orders Card -->
        <div class="zpos-stat-card orders" data-stat="orders">
            <div class="zpos-card-header">
                <h3 class="zpos-card-title"><?php esc_html_e('Total Orders', 'zpos'); ?></h3>
                <div class="zpos-card-icon">
                    <span class="dashicons dashicons-clipboard"></span>
                </div>
            </div>
            <div class="zpos-card-value">
                <?php echo esc_html(number_format($stats['total_orders'] ?? 0)); ?>
            </div>
            <div class="zpos-card-change positive">
                <span class="dashicons dashicons-trending-up"></span>
                8.7%
            </div>
        </div>

        <!-- Total Revenue Card -->
        <div class="zpos-stat-card revenue" data-stat="revenue">
            <div class="zpos-card-header">
                <h3 class="zpos-card-title"><?php esc_html_e('Total Revenue', 'zpos'); ?></h3>
                <div class="zpos-card-icon">
                    <span class="dashicons dashicons-money-alt"></span>
                </div>
            </div>
            <div class="zpos-card-value">
                $<?php echo esc_html(number_format($stats['total_revenue'] ?? 0, 2)); ?>
            </div>
            <div class="zpos-card-change positive">
                <span class="dashicons dashicons-trending-up"></span>
                15.3%
            </div>
        </div>

        <!-- Today's Orders Card -->
        <div class="zpos-stat-card today-orders" data-stat="today_orders">
            <div class="zpos-card-header">
                <h3 class="zpos-card-title"><?php esc_html_e("Today's Orders", 'zpos'); ?></h3>
                <div class="zpos-card-icon">
                    <span class="dashicons dashicons-calendar-alt"></span>
                </div>
            </div>
            <div class="zpos-card-value">
                <?php echo esc_html(number_format($stats['today_orders'] ?? 0)); ?>
            </div>
            <div class="zpos-card-change neutral">
                <span class="dashicons dashicons-minus"></span>
                0%
            </div>
        </div>

        <!-- Today's Revenue Card -->
        <div class="zpos-stat-card today-revenue" data-stat="today_revenue">
            <div class="zpos-card-header">
                <h3 class="zpos-card-title"><?php esc_html_e("Today's Revenue", 'zpos'); ?></h3>
                <div class="zpos-card-icon">
                    <span class="dashicons dashicons-chart-line"></span>
                </div>
            </div>
            <div class="zpos-card-value">
                $<?php echo esc_html(number_format($stats['today_revenue'] ?? 0, 2)); ?>
            </div>
            <div class="zpos-card-change neutral">
                <span class="dashicons dashicons-minus"></span>
                0%
            </div>
        </div>

        <!-- Low Stock Alert Card -->
        <div class="zpos-stat-card low-stock" data-stat="low_stock">
            <div class="zpos-card-header">
                <h3 class="zpos-card-title"><?php esc_html_e('Low Stock Items', 'zpos'); ?></h3>
                <div class="zpos-card-icon">
                    <span class="dashicons dashicons-warning"></span>
                </div>
            </div>
            <div class="zpos-card-value">
                <?php echo esc_html(number_format($stats['low_stock_items'] ?? 0)); ?>
            </div>
            <div class="zpos-card-change">
                <a href="<?php echo esc_url(admin_url('admin.php?page=zpos-inventory')); ?>">
                    <?php esc_html_e('View Inventory', 'zpos'); ?>
                </a>
            </div>
        </div>

        <!-- Quick Actions Card -->
        <div class="zpos-stat-card quick-actions">
            <div class="zpos-card-header">
                <h3 class="zpos-card-title"><?php esc_html_e('Quick Actions', 'zpos'); ?></h3>
                <div class="zpos-card-icon">
                    <span class="dashicons dashicons-admin-tools"></span>
                </div>
            </div>
            <div class="zpos-quick-actions">
                <a href="<?php echo esc_url(admin_url('admin.php?page=zpos-products&action=add')); ?>" class="zpos-quick-action" data-action="add-product">
                    <span class="dashicons dashicons-plus"></span>
                    <?php esc_html_e('Add Product', 'zpos'); ?>
                </a>
                <a href="<?php echo esc_url(admin_url('admin.php?page=zpos-customers&action=add')); ?>" class="zpos-quick-action" data-action="add-customer">
                    <span class="dashicons dashicons-admin-users"></span>
                    <?php esc_html_e('Add Customer', 'zpos'); ?>
                </a>
                <a href="<?php echo esc_url(admin_url('admin.php?page=zpos-pos')); ?>" class="zpos-quick-action" data-action="new-order">
                    <span class="dashicons dashicons-cart"></span>
                    <?php esc_html_e('New Order', 'zpos'); ?>
                </a>
                <a href="<?php echo esc_url(admin_url('admin.php?page=zpos-reports')); ?>" class="zpos-quick-action">
                    <span class="dashicons dashicons-chart-bar"></span>
                    <?php esc_html_e('View Reports', 'zpos'); ?>
                </a>
            </div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="zpos-charts-section">
        <div class="zpos-charts-header">
            <h2 class="zpos-charts-title"><?php esc_html_e('Analytics', 'zpos'); ?></h2>
            <div class="zpos-chart-controls">
                <button type="button" class="zpos-chart-toggle active" data-chart="revenue" data-type="line">
                    <?php esc_html_e('Line', 'zpos'); ?>
                </button>
                <button type="button" class="zpos-chart-toggle" data-chart="revenue" data-type="bar">
                    <?php esc_html_e('Bar', 'zpos'); ?>
                </button>
            </div>
        </div>
        
        <div class="zpos-charts-grid">
            <div class="zpos-chart-container">
                <canvas id="zpos-revenue-chart"></canvas>
            </div>
            <div class="zpos-chart-container">
                <canvas id="zpos-products-chart"></canvas>
            </div>
        </div>
    </div>

    <!-- Recent Activity Section -->
    <div class="zpos-recent-activity">
        <div class="zpos-activity-header">
            <h2 class="zpos-activity-title"><?php esc_html_e('Recent Activity', 'zpos'); ?></h2>
            <a href="<?php echo esc_url(admin_url('admin.php?page=zpos-orders')); ?>" class="zpos-btn zpos-btn-secondary">
                <?php esc_html_e('View All Orders', 'zpos'); ?>
            </a>
        </div>
        
        <div class="zpos-activity-list">
            <!-- This will be populated via AJAX -->
            <div class="zpos-loading">
                <div class="zpos-spinner"></div>
                <?php esc_html_e('Loading recent activity...', 'zpos'); ?>
            </div>
        </div>
    </div>
</div>

<!-- Chart Data for JavaScript -->
<script type="text/javascript">
    window.zposChartData = {
        revenue: {
            labels: <?php echo wp_json_encode($charts['revenue']['labels'] ?? array()); ?>,
            datasets: [{
                label: '<?php esc_html_e('Revenue', 'zpos'); ?>',
                data: <?php echo wp_json_encode($charts['revenue']['data'] ?? array()); ?>,
                borderColor: '#0073aa',
                backgroundColor: 'rgba(0, 115, 170, 0.1)',
                tension: 0.4
            }]
        },
        topProducts: {
            labels: <?php echo wp_json_encode($charts['top_products']['labels'] ?? array()); ?>,
            datasets: [{
                data: <?php echo wp_json_encode($charts['top_products']['data'] ?? array()); ?>,
                backgroundColor: ['#0073aa', '#00a32a', '#ff8c00', '#8b5cf6', '#14b8a6'],
                borderWidth: 0
            }]
        }
    };
</script>
