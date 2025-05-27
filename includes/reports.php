<?php
/**
 * Reports System for ZPOS
 *
 * @link       https://yourwebsite.com
 * @since      1.0.0
 *
 * @package    ZPOS
 * @subpackage ZPOS/includes
 */

/**
 * Reports System class.
 *
 * Handles all reporting functionality for ZPOS including revenue reports,
 * best-selling products, profit/loss reports, and export functionality.
 *
 * @package    ZPOS
 * @subpackage ZPOS/includes
 * @author     Your Name <your.email@example.com>
 */
class ZPOS_Reports {

    /**
     * The database instance.
     *
     * @since    1.0.0
     * @access   private
     * @var      object    $db    Database instance
     */
    private $db;

    /**
     * Initialize the reports system.
     *
     * @since    1.0.0
     */
    public function __construct() {
        global $wpdb;
        $this->db = $wpdb;

        // Initialize AJAX handlers
        add_action('wp_ajax_zpos_get_revenue_report', array($this, 'ajax_get_revenue_report'));
        add_action('wp_ajax_zpos_get_products_report', array($this, 'ajax_get_products_report'));
        add_action('wp_ajax_zpos_get_profit_report', array($this, 'ajax_get_profit_report'));
        add_action('wp_ajax_zpos_export_report', array($this, 'ajax_export_report'));
    }

    /**
     * Get revenue report data.
     *
     * @since    1.0.0
     * @param    string    $period    Report period (daily, weekly, monthly)
     * @param    string    $start_date    Start date
     * @param    string    $end_date      End date
     * @return   array     Revenue report data
     */
    public function get_revenue_report($period = 'daily', $start_date = null, $end_date = null) {
        if (!$start_date) {
            $start_date = date('Y-m-01'); // First day of current month
        }
        if (!$end_date) {
            $end_date = date('Y-m-d'); // Today
        }

        $table_name = $this->db->prefix . 'zpos_orders';
        
        // Build date format based on period
        $date_format = '';
        $group_by = '';
        
        switch ($period) {
            case 'daily':
                $date_format = '%Y-%m-%d';
                $group_by = 'DATE(created_at)';
                break;
            case 'weekly':
                $date_format = '%Y-%u';
                $group_by = 'YEARWEEK(created_at)';
                break;
            case 'monthly':
                $date_format = '%Y-%m';
                $group_by = 'YEAR(created_at), MONTH(created_at)';
                break;
        }        $query = $this->db->prepare("
            SELECT 
                DATE_FORMAT(created_at, %s) as period,
                COUNT(*) as order_count,
                SUM(total_amount) as total_revenue,
                SUM(discount_amount) as total_discount,
                AVG(total_amount) as avg_order_value
            FROM {$table_name}
            WHERE created_at >= %s 
                AND created_at <= %s 
                AND order_status IN ('completed', 'processing')
            GROUP BY {$group_by}
            ORDER BY created_at ASC
        ", $date_format, $start_date, $end_date);

        $results = $this->db->get_results($query);

        return array(
            'data' => $results,
            'summary' => $this->get_revenue_summary($start_date, $end_date)
        );
    }

    /**
     * Get revenue summary.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date
     * @param    string    $end_date      End date
     * @return   array     Summary data
     */    public function get_revenue_summary($start_date, $end_date) {
        $table_name = $this->db->prefix . 'zpos_orders';

        $summary = $this->db->get_row($this->db->prepare("
            SELECT 
                COUNT(*) as total_orders,
                SUM(total_amount) as total_revenue,
                SUM(discount_amount) as total_discount,
                AVG(total_amount) as avg_order_value,
                MAX(total_amount) as highest_order,
                MIN(total_amount) as lowest_order
            FROM {$table_name}
            WHERE created_at >= %s 
                AND created_at <= %s 
                AND order_status IN ('completed', 'processing')
        ", $start_date, $end_date));

        return $summary;
    }

    /**
     * Get best-selling products report.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date
     * @param    string    $end_date      End date
     * @param    int       $limit         Number of products to return
     * @return   array     Best-selling products data
     */
    public function get_best_selling_products($start_date = null, $end_date = null, $limit = 10) {
        if (!$start_date) {
            $start_date = date('Y-m-01');
        }
        if (!$end_date) {
            $end_date = date('Y-m-d');
        }

        $orders_table = $this->db->prefix . 'zpos_orders';
        $order_items_table = $this->db->prefix . 'zpos_order_items';
        $products_table = $this->db->prefix . 'zpos_products';

        $query = $this->db->prepare("
            SELECT 
                p.id,
                p.name,
                p.price,
                SUM(oi.quantity) as total_quantity,
                SUM(oi.quantity * oi.price) as total_revenue,
                COUNT(DISTINCT o.id) as order_count
            FROM {$products_table} p
            JOIN {$order_items_table} oi ON p.id = oi.product_id            JOIN {$orders_table} o ON oi.order_id = o.id
            WHERE o.created_at >= %s 
                AND o.created_at <= %s 
                AND o.order_status IN ('completed', 'processing')
            GROUP BY p.id
            ORDER BY total_quantity DESC
            LIMIT %d
        ", $start_date, $end_date, $limit);

        return $this->db->get_results($query);
    }

    /**
     * Get profit/loss report.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date
     * @param    string    $end_date      End date
     * @return   array     Profit/loss data
     */
    public function get_profit_loss_report($start_date = null, $end_date = null) {
        if (!$start_date) {
            $start_date = date('Y-m-01');
        }
        if (!$end_date) {
            $end_date = date('Y-m-d');
        }

        $orders_table = $this->db->prefix . 'zpos_orders';
        $order_items_table = $this->db->prefix . 'zpos_order_items';
        $products_table = $this->db->prefix . 'zpos_products';        // Get revenue data
        $revenue_data = $this->db->get_row($this->db->prepare("
            SELECT 
                SUM(total_amount) as total_revenue,
                SUM(discount_amount) as total_discount,
                COUNT(*) as total_orders
            FROM {$orders_table}
            WHERE created_at >= %s 
                AND created_at <= %s 
                AND order_status IN ('completed', 'processing')
        ", $start_date, $end_date));        // Get cost data (assuming cost is stored in products table)
        $cost_data = $this->db->get_row($this->db->prepare("
            SELECT 
                SUM(oi.quantity * COALESCE(p.cost, p.price * 0.6)) as total_cost
            FROM {$order_items_table} oi
            JOIN {$orders_table} o ON oi.order_id = o.id
            JOIN {$products_table} p ON oi.product_id = p.id
            WHERE o.created_at >= %s 
                AND o.created_at <= %s 
                AND o.order_status IN ('completed', 'processing')
        ", $start_date, $end_date));

        $total_revenue = floatval($revenue_data->total_revenue ?? 0);
        $total_discount = floatval($revenue_data->total_discount ?? 0);
        $total_cost = floatval($cost_data->total_cost ?? 0);
        $net_revenue = $total_revenue - $total_discount;
        $gross_profit = $net_revenue - $total_cost;
        $profit_margin = $net_revenue > 0 ? ($gross_profit / $net_revenue) * 100 : 0;

        return array(
            'total_revenue' => $total_revenue,
            'total_discount' => $total_discount,
            'net_revenue' => $net_revenue,
            'total_cost' => $total_cost,
            'gross_profit' => $gross_profit,
            'profit_margin' => $profit_margin,
            'total_orders' => intval($revenue_data->total_orders ?? 0)
        );
    }

    /**
     * Get comparative data for charts.
     *
     * @since    1.0.0
     * @param    string    $period    Report period
     * @param    string    $start_date    Start date
     * @param    string    $end_date      End date
     * @return   array     Chart data
     */
    public function get_chart_data($period = 'daily', $start_date = null, $end_date = null) {
        $revenue_data = $this->get_revenue_report($period, $start_date, $end_date);
        
        $labels = array();
        $revenue = array();
        $orders = array();

        foreach ($revenue_data['data'] as $row) {
            $labels[] = $row->period;
            $revenue[] = floatval($row->total_revenue);
            $orders[] = intval($row->order_count);
        }

        return array(
            'labels' => $labels,
            'datasets' => array(
                array(
                    'label' => __('Revenue', 'zpos'),
                    'data' => $revenue,
                    'backgroundColor' => 'rgba(54, 162, 235, 0.2)',
                    'borderColor' => 'rgba(54, 162, 235, 1)',
                    'borderWidth' => 2,
                    'fill' => true
                ),
                array(
                    'label' => __('Orders', 'zpos'),
                    'data' => $orders,
                    'backgroundColor' => 'rgba(255, 99, 132, 0.2)',
                    'borderColor' => 'rgba(255, 99, 132, 1)',
                    'borderWidth' => 2,
                    'fill' => false,
                    'yAxisID' => 'y1'
                )
            )
        );
    }

    /**
     * Export report data to CSV.
     *
     * @since    1.0.0
     * @param    string    $type    Report type
     * @param    array     $data    Report data
     * @param    string    $filename    File name
     */
    public function export_to_csv($type, $data, $filename = null) {
        if (!$filename) {
            $filename = "zpos-{$type}-report-" . date('Y-m-d') . '.csv';
        }

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');

        switch ($type) {
            case 'revenue':
                fputcsv($output, array('Period', 'Orders', 'Revenue', 'Discount', 'Avg Order Value'));
                foreach ($data['data'] as $row) {
                    fputcsv($output, array(
                        $row->period,
                        $row->order_count,
                        $row->total_revenue,
                        $row->total_discount,
                        $row->avg_order_value
                    ));
                }
                break;

            case 'products':
                fputcsv($output, array('Product Name', 'Price', 'Quantity Sold', 'Revenue', 'Orders'));
                foreach ($data as $row) {
                    fputcsv($output, array(
                        $row->name,
                        $row->price,
                        $row->total_quantity,
                        $row->total_revenue,
                        $row->order_count
                    ));
                }
                break;

            case 'profit':
                fputcsv($output, array('Metric', 'Value'));
                fputcsv($output, array('Total Revenue', $data['total_revenue']));
                fputcsv($output, array('Total Discount', $data['total_discount']));
                fputcsv($output, array('Net Revenue', $data['net_revenue']));
                fputcsv($output, array('Total Cost', $data['total_cost']));
                fputcsv($output, array('Gross Profit', $data['gross_profit']));
                fputcsv($output, array('Profit Margin (%)', $data['profit_margin']));
                fputcsv($output, array('Total Orders', $data['total_orders']));
                break;
        }

        fclose($output);
        exit;
    }

    /**
     * AJAX handler for revenue report.
     *
     * @since    1.0.0
     */
    public function ajax_get_revenue_report() {
        check_ajax_referer('zpos_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'zpos'));
        }

        $period = sanitize_text_field($_POST['period'] ?? 'daily');
        $start_date = sanitize_text_field($_POST['start_date'] ?? '');
        $end_date = sanitize_text_field($_POST['end_date'] ?? '');

        $data = $this->get_revenue_report($period, $start_date, $end_date);

        wp_send_json_success($data);
    }

    /**
     * AJAX handler for products report.
     *
     * @since    1.0.0
     */
    public function ajax_get_products_report() {
        check_ajax_referer('zpos_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'zpos'));
        }

        $start_date = sanitize_text_field($_POST['start_date'] ?? '');
        $end_date = sanitize_text_field($_POST['end_date'] ?? '');
        $limit = intval($_POST['limit'] ?? 10);

        $data = $this->get_best_selling_products($start_date, $end_date, $limit);

        wp_send_json_success($data);
    }

    /**
     * AJAX handler for profit report.
     *
     * @since    1.0.0
     */
    public function ajax_get_profit_report() {
        check_ajax_referer('zpos_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'zpos'));
        }

        $start_date = sanitize_text_field($_POST['start_date'] ?? '');
        $end_date = sanitize_text_field($_POST['end_date'] ?? '');

        $data = $this->get_profit_loss_report($start_date, $end_date);

        wp_send_json_success($data);
    }

    /**
     * AJAX handler for report export.
     *
     * @since    1.0.0
     */
    public function ajax_export_report() {
        check_ajax_referer('zpos_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'zpos'));
        }

        $type = sanitize_text_field($_POST['type'] ?? 'revenue');
        $period = sanitize_text_field($_POST['period'] ?? 'daily');
        $start_date = sanitize_text_field($_POST['start_date'] ?? '');
        $end_date = sanitize_text_field($_POST['end_date'] ?? '');

        switch ($type) {
            case 'revenue':
                $data = $this->get_revenue_report($period, $start_date, $end_date);
                break;
            case 'products':
                $data = $this->get_best_selling_products($start_date, $end_date);
                break;
            case 'profit':
                $data = $this->get_profit_loss_report($start_date, $end_date);
                break;
            default:
                wp_die(__('Invalid report type.', 'zpos'));
        }

        $this->export_to_csv($type, $data);
    }
}
