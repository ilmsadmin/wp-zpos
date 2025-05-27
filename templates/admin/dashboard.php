<?php
/**
 * Dashboard Template
 * 
 * @package ZPOS
 * @subpackage Templates/Admin
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="zpos-admin-wrap">
    <div class="zpos-header">
        <h1 class="zpos-title">
            <span class="zpos-icon dashicons dashicons-store"></span>
            ZPOS Dashboard
        </h1>
        <div class="zpos-header-actions">
            <button type="button" class="button button-primary" id="refresh-dashboard">
                <span class="dashicons dashicons-update"></span>
                Refresh
            </button>
        </div>
    </div>

    <div class="zpos-dashboard-grid">
        <!-- Statistics Cards -->
        <div class="zpos-stat-card" id="total-products-card">
            <div class="zpos-stat-icon">
                <span class="dashicons dashicons-products"></span>
            </div>
            <div class="zpos-stat-content">
                <h3 class="zpos-stat-number" id="total-products">-</h3>
                <p class="zpos-stat-label">Total Products</p>
            </div>
        </div>

        <div class="zpos-stat-card" id="total-customers-card">
            <div class="zpos-stat-icon">
                <span class="dashicons dashicons-groups"></span>
            </div>
            <div class="zpos-stat-content">
                <h3 class="zpos-stat-number" id="total-customers">-</h3>
                <p class="zpos-stat-label">Total Customers</p>
            </div>
        </div>

        <div class="zpos-stat-card" id="total-orders-card">
            <div class="zpos-stat-icon">
                <span class="dashicons dashicons-cart"></span>
            </div>
            <div class="zpos-stat-content">
                <h3 class="zpos-stat-number" id="total-orders">-</h3>
                <p class="zpos-stat-label">Total Orders</p>
            </div>
        </div>

        <div class="zpos-stat-card" id="total-revenue-card">
            <div class="zpos-stat-icon">
                <span class="dashicons dashicons-money-alt"></span>
            </div>
            <div class="zpos-stat-content">
                <h3 class="zpos-stat-number" id="total-revenue">-</h3>
                <p class="zpos-stat-label">Total Revenue</p>
            </div>
        </div>

        <!-- Revenue Chart -->
        <div class="zpos-chart-card">
            <div class="zpos-card-header">
                <h3>Revenue Overview</h3>
                <select id="revenue-period" class="zpos-chart-period">
                    <option value="7">Last 7 Days</option>
                    <option value="30" selected>Last 30 Days</option>
                    <option value="90">Last 90 Days</option>
                </select>
            </div>
            <div class="zpos-card-content">
                <canvas id="revenue-chart" width="400" height="200"></canvas>
            </div>
        </div>

        <!-- Products Chart -->
        <div class="zpos-chart-card">
            <div class="zpos-card-header">
                <h3>Product Sales</h3>
                <select id="products-period" class="zpos-chart-period">
                    <option value="7">Last 7 Days</option>
                    <option value="30" selected>Last 30 Days</option>
                    <option value="90">Last 90 Days</option>
                </select>
            </div>
            <div class="zpos-card-content">
                <canvas id="products-chart" width="400" height="200"></canvas>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="zpos-activity-card">
            <div class="zpos-card-header">
                <h3>Recent Activity</h3>
                <button type="button" class="button button-small" id="refresh-activity">
                    <span class="dashicons dashicons-update"></span>
                </button>
            </div>
            <div class="zpos-card-content">
                <div id="recent-activity" class="zpos-activity-list">
                    <div class="zpos-loading">Loading...</div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="zpos-actions-card">
            <div class="zpos-card-header">
                <h3>Quick Actions</h3>
            </div>
            <div class="zpos-card-content">
                <div class="zpos-quick-actions">
                    <a href="<?php echo admin_url('admin.php?page=zpos-products'); ?>" class="zpos-quick-action">
                        <span class="dashicons dashicons-plus-alt"></span>
                        Add Product
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=zpos-orders'); ?>" class="zpos-quick-action">
                        <span class="dashicons dashicons-list-view"></span>
                        View Orders
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=zpos-customers'); ?>" class="zpos-quick-action">
                        <span class="dashicons dashicons-admin-users"></span>
                        Manage Customers
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=zpos-reports'); ?>" class="zpos-quick-action">
                        <span class="dashicons dashicons-chart-bar"></span>
                        View Reports
                    </a>
                </div>
            </div>
        </div>

        <!-- System Status -->
        <div class="zpos-status-card">
            <div class="zpos-card-header">
                <h3>System Status</h3>
            </div>
            <div class="zpos-card-content">
                <div class="zpos-status-item">
                    <span class="zpos-status-label">Plugin Version:</span>
                    <span class="zpos-status-value"><?php echo ZPOS_VERSION; ?></span>
                </div>
                <div class="zpos-status-item">
                    <span class="zpos-status-label">WordPress Version:</span>
                    <span class="zpos-status-value"><?php echo get_bloginfo('version'); ?></span>
                </div>
                <div class="zpos-status-item">
                    <span class="zpos-status-label">PHP Version:</span>
                    <span class="zpos-status-value"><?php echo PHP_VERSION; ?></span>
                </div>
                <div class="zpos-status-item">
                    <span class="zpos-status-label">Database:</span>
                    <span class="zpos-status-value" id="db-status">Checking...</span>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Initialize dashboard when DOM is ready
    if (typeof ZPOSAdmin !== 'undefined') {
        ZPOSAdmin.initDashboard();
    }
});
</script>