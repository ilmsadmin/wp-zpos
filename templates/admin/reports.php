<?php
/**
 * Admin Reports Template
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

// Initialize reports instance
if (!class_exists('ZPOS_Reports')) {
    require_once plugin_dir_path(dirname(__FILE__)) . 'includes/reports.php';
}

$reports = new ZPOS_Reports();

// Get current date range (default: current month)
$start_date = isset($_GET['start_date']) ? sanitize_text_field($_GET['start_date']) : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? sanitize_text_field($_GET['end_date']) : date('Y-m-d');
$period = isset($_GET['period']) ? sanitize_text_field($_GET['period']) : 'daily';

// Get report data
$revenue_summary = $reports->get_revenue_summary($start_date, $end_date);
$best_products = $reports->get_best_selling_products($start_date, $end_date, 5);
$profit_data = $reports->get_profit_loss_report($start_date, $end_date);
?>

<div class="wrap zpos-reports">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-chart-bar"></span>
        <?php _e('Reports', 'zpos'); ?>
    </h1>

    <!-- Date Range Filter -->
    <div class="zpos-reports-header">
        <div class="zpos-date-filters">
            <form method="get" class="date-filter-form">
                <input type="hidden" name="page" value="zpos-reports">
                
                <div class="filter-group">
                    <label for="period"><?php _e('Period:', 'zpos'); ?></label>
                    <select name="period" id="period">
                        <option value="daily" <?php selected($period, 'daily'); ?>><?php _e('Daily', 'zpos'); ?></option>
                        <option value="weekly" <?php selected($period, 'weekly'); ?>><?php _e('Weekly', 'zpos'); ?></option>
                        <option value="monthly" <?php selected($period, 'monthly'); ?>><?php _e('Monthly', 'zpos'); ?></option>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="start_date"><?php _e('From:', 'zpos'); ?></label>
                    <input type="date" name="start_date" id="start_date" value="<?php echo esc_attr($start_date); ?>">
                </div>

                <div class="filter-group">
                    <label for="end_date"><?php _e('To:', 'zpos'); ?></label>
                    <input type="date" name="end_date" id="end_date" value="<?php echo esc_attr($end_date); ?>">
                </div>

                <div class="filter-group">
                    <button type="submit" class="button button-primary">
                        <span class="dashicons dashicons-search"></span>
                        <?php _e('Apply Filter', 'zpos'); ?>
                    </button>
                </div>

                <div class="filter-group">
                    <button type="button" class="button" id="quick-today">
                        <?php _e('Today', 'zpos'); ?>
                    </button>
                    <button type="button" class="button" id="quick-week">
                        <?php _e('This Week', 'zpos'); ?>
                    </button>
                    <button type="button" class="button" id="quick-month">
                        <?php _e('This Month', 'zpos'); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="zpos-reports-summary">
        <div class="summary-cards">
            <div class="summary-card revenue">
                <div class="card-icon">
                    <span class="dashicons dashicons-money-alt"></span>
                </div>
                <div class="card-content">
                    <h3><?php _e('Total Revenue', 'zpos'); ?></h3>
                    <div class="card-value">
                        <?php echo wc_price($revenue_summary->total_revenue ?? 0); ?>
                    </div>
                    <div class="card-meta">
                        <?php printf(__('%s orders', 'zpos'), number_format($revenue_summary->total_orders ?? 0)); ?>
                    </div>
                </div>
            </div>

            <div class="summary-card profit">
                <div class="card-icon">
                    <span class="dashicons dashicons-chart-line"></span>
                </div>
                <div class="card-content">
                    <h3><?php _e('Gross Profit', 'zpos'); ?></h3>
                    <div class="card-value">
                        <?php echo wc_price($profit_data['gross_profit'] ?? 0); ?>
                    </div>
                    <div class="card-meta">
                        <?php printf(__('%.1f%% margin', 'zpos'), $profit_data['profit_margin'] ?? 0); ?>
                    </div>
                </div>
            </div>

            <div class="summary-card average">
                <div class="card-icon">
                    <span class="dashicons dashicons-calculator"></span>
                </div>
                <div class="card-content">
                    <h3><?php _e('Avg Order Value', 'zpos'); ?></h3>
                    <div class="card-value">
                        <?php echo wc_price($revenue_summary->avg_order_value ?? 0); ?>
                    </div>
                    <div class="card-meta">
                        <?php printf(__('%s avg', 'zpos'), wc_price($revenue_summary->avg_order_value ?? 0)); ?>
                    </div>
                </div>
            </div>

            <div class="summary-card discount">
                <div class="card-icon">
                    <span class="dashicons dashicons-tag"></span>
                </div>
                <div class="card-content">
                    <h3><?php _e('Total Discounts', 'zpos'); ?></h3>
                    <div class="card-value">
                        <?php echo wc_price($revenue_summary->total_discount ?? 0); ?>
                    </div>
                    <div class="card-meta">
                        <?php 
                        $discount_percentage = $revenue_summary->total_revenue > 0 ? 
                            ($revenue_summary->total_discount / $revenue_summary->total_revenue) * 100 : 0;
                        printf(__('%.1f%% of revenue', 'zpos'), $discount_percentage); 
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="zpos-reports-charts">
        <div class="chart-container">
            <div class="chart-header">
                <h2><?php _e('Revenue & Orders Chart', 'zpos'); ?></h2>
                <div class="chart-controls">
                    <button type="button" class="button chart-type-btn active" data-type="line">
                        <span class="dashicons dashicons-chart-line"></span>
                        <?php _e('Line', 'zpos'); ?>
                    </button>
                    <button type="button" class="button chart-type-btn" data-type="bar">
                        <span class="dashicons dashicons-chart-bar"></span>
                        <?php _e('Bar', 'zpos'); ?>
                    </button>
                </div>
            </div>
            <canvas id="revenue-chart" width="400" height="200"></canvas>
        </div>
    </div>

    <!-- Detailed Reports Section -->
    <div class="zpos-reports-details">
        <div class="reports-tabs">
            <nav class="nav-tab-wrapper">
                <a href="#revenue-report" class="nav-tab nav-tab-active"><?php _e('Revenue Report', 'zpos'); ?></a>
                <a href="#products-report" class="nav-tab"><?php _e('Best Selling Products', 'zpos'); ?></a>
                <a href="#profit-report" class="nav-tab"><?php _e('Profit/Loss Report', 'zpos'); ?></a>
            </nav>

            <!-- Revenue Report Tab -->
            <div id="revenue-report" class="tab-content active">
                <div class="tab-header">
                    <h3><?php _e('Revenue Report', 'zpos'); ?></h3>
                    <button type="button" class="button export-btn" data-type="revenue">
                        <span class="dashicons dashicons-download"></span>
                        <?php _e('Export CSV', 'zpos'); ?>
                    </button>
                </div>
                <div class="report-table-container">
                    <table id="revenue-table" class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php _e('Period', 'zpos'); ?></th>
                                <th><?php _e('Orders', 'zpos'); ?></th>
                                <th><?php _e('Revenue', 'zpos'); ?></th>
                                <th><?php _e('Discount', 'zpos'); ?></th>
                                <th><?php _e('Avg Order Value', 'zpos'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="5" class="loading-row">
                                    <div class="spinner is-active"></div>
                                    <?php _e('Loading data...', 'zpos'); ?>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Products Report Tab -->
            <div id="products-report" class="tab-content">
                <div class="tab-header">
                    <h3><?php _e('Best Selling Products', 'zpos'); ?></h3>
                    <div class="tab-controls">
                        <label for="products-limit"><?php _e('Show:', 'zpos'); ?></label>
                        <select id="products-limit">
                            <option value="10"><?php _e('Top 10', 'zpos'); ?></option>
                            <option value="25"><?php _e('Top 25', 'zpos'); ?></option>
                            <option value="50"><?php _e('Top 50', 'zpos'); ?></option>
                        </select>
                        <button type="button" class="button export-btn" data-type="products">
                            <span class="dashicons dashicons-download"></span>
                            <?php _e('Export CSV', 'zpos'); ?>
                        </button>
                    </div>
                </div>
                <div class="report-table-container">
                    <table id="products-table" class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php _e('Product', 'zpos'); ?></th>
                                <th><?php _e('Price', 'zpos'); ?></th>
                                <th><?php _e('Quantity Sold', 'zpos'); ?></th>
                                <th><?php _e('Revenue', 'zpos'); ?></th>
                                <th><?php _e('Orders', 'zpos'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="5" class="loading-row">
                                    <div class="spinner is-active"></div>
                                    <?php _e('Loading data...', 'zpos'); ?>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Profit Report Tab -->
            <div id="profit-report" class="tab-content">
                <div class="tab-header">
                    <h3><?php _e('Profit/Loss Report', 'zpos'); ?></h3>
                    <button type="button" class="button export-btn" data-type="profit">
                        <span class="dashicons dashicons-download"></span>
                        <?php _e('Export CSV', 'zpos'); ?>
                    </button>
                </div>
                <div class="profit-report-grid">
                    <div class="profit-card">
                        <h4><?php _e('Revenue Breakdown', 'zpos'); ?></h4>
                        <div class="profit-item">
                            <span class="label"><?php _e('Total Revenue:', 'zpos'); ?></span>
                            <span class="value positive"><?php echo wc_price($profit_data['total_revenue']); ?></span>
                        </div>
                        <div class="profit-item">
                            <span class="label"><?php _e('Total Discounts:', 'zpos'); ?></span>
                            <span class="value negative">-<?php echo wc_price($profit_data['total_discount']); ?></span>
                        </div>
                        <div class="profit-item total">
                            <span class="label"><?php _e('Net Revenue:', 'zpos'); ?></span>
                            <span class="value"><?php echo wc_price($profit_data['net_revenue']); ?></span>
                        </div>
                    </div>

                    <div class="profit-card">
                        <h4><?php _e('Cost & Profit', 'zpos'); ?></h4>
                        <div class="profit-item">
                            <span class="label"><?php _e('Total Cost:', 'zpos'); ?></span>
                            <span class="value negative"><?php echo wc_price($profit_data['total_cost']); ?></span>
                        </div>
                        <div class="profit-item">
                            <span class="label"><?php _e('Gross Profit:', 'zpos'); ?></span>
                            <span class="value <?php echo $profit_data['gross_profit'] >= 0 ? 'positive' : 'negative'; ?>">
                                <?php echo wc_price($profit_data['gross_profit']); ?>
                            </span>
                        </div>
                        <div class="profit-item">
                            <span class="label"><?php _e('Profit Margin:', 'zpos'); ?></span>
                            <span class="value <?php echo $profit_data['profit_margin'] >= 0 ? 'positive' : 'negative'; ?>">
                                <?php printf('%.1f%%', $profit_data['profit_margin']); ?>
                            </span>
                        </div>
                    </div>

                    <div class="profit-card">
                        <h4><?php _e('Performance Metrics', 'zpos'); ?></h4>
                        <div class="profit-item">
                            <span class="label"><?php _e('Total Orders:', 'zpos'); ?></span>
                            <span class="value"><?php echo number_format($profit_data['total_orders']); ?></span>
                        </div>
                        <div class="profit-item">
                            <span class="label"><?php _e('Avg Order Value:', 'zpos'); ?></span>
                            <span class="value"><?php echo wc_price($revenue_summary->avg_order_value ?? 0); ?></span>
                        </div>
                        <div class="profit-item">
                            <span class="label"><?php _e('Profit per Order:', 'zpos'); ?></span>
                            <span class="value">
                                <?php 
                                $profit_per_order = $profit_data['total_orders'] > 0 ? 
                                    $profit_data['gross_profit'] / $profit_data['total_orders'] : 0;
                                echo wc_price($profit_per_order); 
                                ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Initialize reports system
    ZPOS_Reports.init();
});
</script>

<style>
/* Reports specific styles */
.zpos-reports {
    margin: 20px 0;
}

.zpos-reports h1 {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 20px;
}

.zpos-reports-header {
    background: #fff;
    padding: 20px;
    margin-bottom: 20px;
    border: 1px solid #ddd;
    border-radius: 8px;
}

.date-filter-form {
    display: flex;
    align-items: center;
    gap: 15px;
    flex-wrap: wrap;
}

.filter-group {
    display: flex;
    align-items: center;
    gap: 8px;
}

.filter-group label {
    font-weight: 600;
    margin: 0;
}

.filter-group input,
.filter-group select {
    margin: 0;
}

.summary-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.summary-card {
    background: #fff;
    padding: 20px;
    border-radius: 8px;
    border: 1px solid #ddd;
    display: flex;
    align-items: center;
    gap: 15px;
    transition: transform 0.2s ease;
}

.summary-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.summary-card.revenue { border-left: 4px solid #00a32a; }
.summary-card.profit { border-left: 4px solid #0073aa; }
.summary-card.average { border-left: 4px solid #ff6900; }
.summary-card.discount { border-left: 4px solid #dc3232; }

.card-icon {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
}

.revenue .card-icon { background: rgba(0, 163, 42, 0.1); color: #00a32a; }
.profit .card-icon { background: rgba(0, 115, 170, 0.1); color: #0073aa; }
.average .card-icon { background: rgba(255, 105, 0, 0.1); color: #ff6900; }
.discount .card-icon { background: rgba(220, 50, 50, 0.1); color: #dc3232; }

.card-content h3 {
    margin: 0 0 5px 0;
    font-size: 14px;
    color: #666;
    font-weight: 600;
}

.card-value {
    font-size: 24px;
    font-weight: bold;
    color: #333;
    margin-bottom: 5px;
}

.card-meta {
    font-size: 12px;
    color: #999;
}

.chart-container {
    background: #fff;
    padding: 20px;
    border: 1px solid #ddd;
    border-radius: 8px;
    margin-bottom: 30px;
}

.chart-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.chart-header h2 {
    margin: 0;
    font-size: 18px;
}

.chart-controls {
    display: flex;
    gap: 5px;
}

.chart-type-btn {
    display: flex;
    align-items: center;
    gap: 5px;
    padding: 8px 12px;
}

.chart-type-btn.active {
    background: #0073aa;
    color: #fff;
    border-color: #0073aa;
}

.reports-tabs {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
}

.tab-content {
    padding: 20px;
    display: none;
}

.tab-content.active {
    display: block;
}

.tab-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.tab-header h3 {
    margin: 0;
}

.tab-controls {
    display: flex;
    align-items: center;
    gap: 10px;
}

.report-table-container {
    overflow-x: auto;
}

.loading-row {
    text-align: center;
    padding: 40px !important;
}

.loading-row .spinner {
    float: none;
    margin: 0 10px 0 0;
}

.profit-report-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
}

.profit-card {
    background: #f9f9f9;
    padding: 20px;
    border-radius: 8px;
    border: 1px solid #eee;
}

.profit-card h4 {
    margin: 0 0 15px 0;
    color: #333;
    font-size: 16px;
}

.profit-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 0;
    border-bottom: 1px solid #eee;
}

.profit-item:last-child {
    border-bottom: none;
}

.profit-item.total {
    border-top: 2px solid #ddd;
    font-weight: bold;
    font-size: 16px;
}

.profit-item .label {
    color: #666;
}

.profit-item .value {
    font-weight: 600;
}

.profit-item .value.positive {
    color: #00a32a;
}

.profit-item .value.negative {
    color: #dc3232;
}

@media (max-width: 768px) {
    .date-filter-form {
        flex-direction: column;
        align-items: stretch;
    }
    
    .filter-group {
        justify-content: space-between;
    }
    
    .summary-cards {
        grid-template-columns: 1fr;
    }
    
    .tab-header {
        flex-direction: column;
        gap: 15px;
        align-items: stretch;
    }
}
</style>
