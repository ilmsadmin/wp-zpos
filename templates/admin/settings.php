<?php
/**
 * Admin Settings Template
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

// Initialize settings instance
if (!class_exists('ZPOS_Settings')) {
    require_once plugin_dir_path(dirname(__FILE__)) . 'includes/settings.php';
}

$settings_handler = new ZPOS_Settings();
$settings = $settings_handler->get_settings();
$currencies = $settings_handler->get_currencies();
$timezones = $settings_handler->get_timezones();

// Get current tab
$current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'general';
?>

<div class="wrap zpos-settings">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-admin-settings"></span>
        <?php _e('ZPOS Settings', 'zpos'); ?>
    </h1>

    <!-- Settings Navigation Tabs -->
    <nav class="nav-tab-wrapper wp-clearfix">
        <a href="?page=zpos-settings&tab=general" class="nav-tab <?php echo $current_tab === 'general' ? 'nav-tab-active' : ''; ?>">
            <span class="dashicons dashicons-admin-generic"></span>
            <?php _e('General', 'zpos'); ?>
        </a>
        <a href="?page=zpos-settings&tab=woocommerce" class="nav-tab <?php echo $current_tab === 'woocommerce' ? 'nav-tab-active' : ''; ?>">
            <span class="dashicons dashicons-cart"></span>
            <?php _e('WooCommerce', 'zpos'); ?>
        </a>
        <a href="?page=zpos-settings&tab=inventory" class="nav-tab <?php echo $current_tab === 'inventory' ? 'nav-tab-active' : ''; ?>">
            <span class="dashicons dashicons-products"></span>
            <?php _e('Inventory', 'zpos'); ?>
        </a>
        <a href="?page=zpos-settings&tab=interface" class="nav-tab <?php echo $current_tab === 'interface' ? 'nav-tab-active' : ''; ?>">
            <span class="dashicons dashicons-admin-appearance"></span>
            <?php _e('Interface', 'zpos'); ?>
        </a>
        <a href="?page=zpos-settings&tab=advanced" class="nav-tab <?php echo $current_tab === 'advanced' ? 'nav-tab-active' : ''; ?>">
            <span class="dashicons dashicons-admin-tools"></span>
            <?php _e('Advanced', 'zpos'); ?>
        </a>
    </nav>

    <form id="zpos-settings-form" method="post">
        <?php wp_nonce_field('zpos_settings_save', 'zpos_settings_nonce'); ?>

        <!-- General Settings Tab -->
        <div id="general-tab" class="settings-tab <?php echo $current_tab === 'general' ? 'active' : ''; ?>">
            <div class="settings-section">
                <h2><?php _e('Store Information', 'zpos'); ?></h2>
                <table class="form-table">
                    <tbody>
                        <tr>
                            <th scope="row">
                                <label for="store_name"><?php _e('Store Name', 'zpos'); ?></label>
                            </th>
                            <td>
                                <input type="text" id="store_name" name="settings[store_name]" value="<?php echo esc_attr($settings['store_name']); ?>" class="regular-text" />
                                <p class="description"><?php _e('The name of your store.', 'zpos'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="store_address"><?php _e('Store Address', 'zpos'); ?></label>
                            </th>
                            <td>
                                <textarea id="store_address" name="settings[store_address]" rows="3" class="large-text"><?php echo esc_textarea($settings['store_address']); ?></textarea>
                                <p class="description"><?php _e('Your store physical address.', 'zpos'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="store_city"><?php _e('City', 'zpos'); ?></label>
                            </th>
                            <td>
                                <input type="text" id="store_city" name="settings[store_city]" value="<?php echo esc_attr($settings['store_city']); ?>" class="regular-text" />
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="store_country"><?php _e('Country', 'zpos'); ?></label>
                            </th>
                            <td>
                                <select id="store_country" name="settings[store_country]">
                                    <option value="VN" <?php selected($settings['store_country'], 'VN'); ?>><?php _e('Vietnam', 'zpos'); ?></option>
                                    <option value="US" <?php selected($settings['store_country'], 'US'); ?>><?php _e('United States', 'zpos'); ?></option>
                                    <option value="GB" <?php selected($settings['store_country'], 'GB'); ?>><?php _e('United Kingdom', 'zpos'); ?></option>
                                    <option value="AU" <?php selected($settings['store_country'], 'AU'); ?>><?php _e('Australia', 'zpos'); ?></option>
                                    <option value="CA" <?php selected($settings['store_country'], 'CA'); ?>><?php _e('Canada', 'zpos'); ?></option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="store_phone"><?php _e('Phone Number', 'zpos'); ?></label>
                            </th>
                            <td>
                                <input type="tel" id="store_phone" name="settings[store_phone]" value="<?php echo esc_attr($settings['store_phone']); ?>" class="regular-text" />
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="store_email"><?php _e('Email Address', 'zpos'); ?></label>
                            </th>
                            <td>
                                <input type="email" id="store_email" name="settings[store_email]" value="<?php echo esc_attr($settings['store_email']); ?>" class="regular-text" />
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="settings-section">
                <h2><?php _e('Currency & Locale', 'zpos'); ?></h2>
                <table class="form-table">
                    <tbody>
                        <tr>
                            <th scope="row">
                                <label for="currency"><?php _e('Currency', 'zpos'); ?></label>
                            </th>
                            <td>
                                <select id="currency" name="settings[currency]">
                                    <?php foreach ($currencies as $code => $currency): ?>
                                        <option value="<?php echo esc_attr($code); ?>" <?php selected($settings['currency'], $code); ?>>
                                            <?php echo esc_html($currency['name']) . ' (' . esc_html($currency['symbol']) . ')'; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="currency_position"><?php _e('Currency Position', 'zpos'); ?></label>
                            </th>
                            <td>
                                <select id="currency_position" name="settings[currency_position]">
                                    <option value="left" <?php selected($settings['currency_position'], 'left'); ?>><?php _e('Left ($99)', 'zpos'); ?></option>
                                    <option value="right" <?php selected($settings['currency_position'], 'right'); ?>><?php _e('Right (99$)', 'zpos'); ?></option>
                                    <option value="left_space" <?php selected($settings['currency_position'], 'left_space'); ?>><?php _e('Left with space ($ 99)', 'zpos'); ?></option>
                                    <option value="right_space" <?php selected($settings['currency_position'], 'right_space'); ?>><?php _e('Right with space (99 $)', 'zpos'); ?></option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="number_decimals"><?php _e('Number of Decimals', 'zpos'); ?></label>
                            </th>
                            <td>
                                <input type="number" id="number_decimals" name="settings[number_decimals]" value="<?php echo esc_attr($settings['number_decimals']); ?>" min="0" max="4" class="small-text" />
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="thousand_separator"><?php _e('Thousands Separator', 'zpos'); ?></label>
                            </th>
                            <td>
                                <input type="text" id="thousand_separator" name="settings[thousand_separator]" value="<?php echo esc_attr($settings['thousand_separator']); ?>" class="small-text" />
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="decimal_separator"><?php _e('Decimal Separator', 'zpos'); ?></label>
                            </th>
                            <td>
                                <input type="text" id="decimal_separator" name="settings[decimal_separator]" value="<?php echo esc_attr($settings['decimal_separator']); ?>" class="small-text" />
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="timezone"><?php _e('Timezone', 'zpos'); ?></label>
                            </th>
                            <td>
                                <select id="timezone" name="settings[timezone]">
                                    <?php foreach ($timezones as $value => $label): ?>
                                        <option value="<?php echo esc_attr($value); ?>" <?php selected($settings['timezone'], $value); ?>>
                                            <?php echo esc_html($label); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- WooCommerce Settings Tab -->
        <div id="woocommerce-tab" class="settings-tab <?php echo $current_tab === 'woocommerce' ? 'active' : ''; ?>">
            <div class="settings-section">
                <h2><?php _e('WooCommerce Integration', 'zpos'); ?></h2>
                
                <div class="woocommerce-status">
                    <div class="status-card">
                        <div class="status-header">
                            <h3><?php _e('Connection Status', 'zpos'); ?></h3>
                            <button type="button" class="button" id="test-woocommerce">
                                <span class="dashicons dashicons-update"></span>
                                <?php _e('Test Connection', 'zpos'); ?>
                            </button>
                        </div>
                        <div class="status-content" id="woocommerce-status">
                            <div class="spinner is-active"></div>
                            <?php _e('Testing connection...', 'zpos'); ?>
                        </div>
                    </div>
                </div>

                <table class="form-table">
                    <tbody>
                        <tr>
                            <th scope="row">
                                <label for="woocommerce_enabled"><?php _e('Enable WooCommerce Sync', 'zpos'); ?></label>
                            </th>
                            <td>
                                <label class="switch">
                                    <input type="checkbox" id="woocommerce_enabled" name="settings[woocommerce_enabled]" value="1" <?php checked($settings['woocommerce_enabled']); ?> />
                                    <span class="slider"></span>
                                </label>
                                <p class="description"><?php _e('Enable synchronization with WooCommerce.', 'zpos'); ?></p>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <div class="sync-options" id="sync-options" style="<?php echo $settings['woocommerce_enabled'] ? '' : 'display: none;'; ?>">
                    <h3><?php _e('Sync Options', 'zpos'); ?></h3>
                    <table class="form-table">
                        <tbody>
                            <tr>
                                <th scope="row"><?php _e('Sync Products', 'zpos'); ?></th>
                                <td>
                                    <label class="switch">
                                        <input type="checkbox" name="settings[sync_products]" value="1" <?php checked($settings['sync_products']); ?> />
                                        <span class="slider"></span>
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Sync Customers', 'zpos'); ?></th>
                                <td>
                                    <label class="switch">
                                        <input type="checkbox" name="settings[sync_customers]" value="1" <?php checked($settings['sync_customers']); ?> />
                                        <span class="slider"></span>
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Sync Orders', 'zpos'); ?></th>
                                <td>
                                    <label class="switch">
                                        <input type="checkbox" name="settings[sync_orders]" value="1" <?php checked($settings['sync_orders']); ?> />
                                        <span class="slider"></span>
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Auto Sync', 'zpos'); ?></th>
                                <td>
                                    <label class="switch">
                                        <input type="checkbox" name="settings[auto_sync]" value="1" <?php checked($settings['auto_sync']); ?> />
                                        <span class="slider"></span>
                                    </label>
                                    <p class="description"><?php _e('Automatically sync data at regular intervals.', 'zpos'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="sync_interval"><?php _e('Sync Interval', 'zpos'); ?></label>
                                </th>
                                <td>
                                    <select id="sync_interval" name="settings[sync_interval]">
                                        <option value="hourly" <?php selected($settings['sync_interval'], 'hourly'); ?>><?php _e('Hourly', 'zpos'); ?></option>
                                        <option value="daily" <?php selected($settings['sync_interval'], 'daily'); ?>><?php _e('Daily', 'zpos'); ?></option>
                                        <option value="weekly" <?php selected($settings['sync_interval'], 'weekly'); ?>><?php _e('Weekly', 'zpos'); ?></option>
                                    </select>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <div class="manual-sync">
                        <h3><?php _e('Manual Sync', 'zpos'); ?></h3>
                        <p><?php _e('Manually synchronize data with WooCommerce.', 'zpos'); ?></p>
                        <div class="sync-buttons">
                            <button type="button" class="button" data-sync="products">
                                <span class="dashicons dashicons-products"></span>
                                <?php _e('Sync Products', 'zpos'); ?>
                            </button>
                            <button type="button" class="button" data-sync="customers">
                                <span class="dashicons dashicons-groups"></span>
                                <?php _e('Sync Customers', 'zpos'); ?>
                            </button>
                            <button type="button" class="button" data-sync="orders">
                                <span class="dashicons dashicons-cart"></span>
                                <?php _e('Sync Orders', 'zpos'); ?>
                            </button>
                            <button type="button" class="button button-primary" data-sync="all">
                                <span class="dashicons dashicons-update"></span>
                                <?php _e('Sync All', 'zpos'); ?>
                            </button>
                        </div>
                        <div id="sync-results" class="sync-results"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Inventory Settings Tab -->
        <div id="inventory-tab" class="settings-tab <?php echo $current_tab === 'inventory' ? 'active' : ''; ?>">
            <div class="settings-section">
                <h2><?php _e('Stock Management', 'zpos'); ?></h2>
                <table class="form-table">
                    <tbody>
                        <tr>
                            <th scope="row">
                                <label for="low_stock_threshold"><?php _e('Low Stock Threshold', 'zpos'); ?></label>
                            </th>
                            <td>
                                <input type="number" id="low_stock_threshold" name="settings[low_stock_threshold]" value="<?php echo esc_attr($settings['low_stock_threshold']); ?>" min="0" class="small-text" />
                                <p class="description"><?php _e('Alert when stock reaches this level.', 'zpos'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="out_of_stock_threshold"><?php _e('Out of Stock Threshold', 'zpos'); ?></label>
                            </th>
                            <td>
                                <input type="number" id="out_of_stock_threshold" name="settings[out_of_stock_threshold]" value="<?php echo esc_attr($settings['out_of_stock_threshold']); ?>" min="0" class="small-text" />
                                <p class="description"><?php _e('Consider item out of stock at this level.', 'zpos'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Enable Stock Alerts', 'zpos'); ?></th>
                            <td>
                                <label class="switch">
                                    <input type="checkbox" name="settings[enable_stock_alerts]" value="1" <?php checked($settings['enable_stock_alerts']); ?> />
                                    <span class="slider"></span>
                                </label>
                                <p class="description"><?php _e('Show alerts for low stock products.', 'zpos'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Auto Reduce Stock', 'zpos'); ?></th>
                            <td>
                                <label class="switch">
                                    <input type="checkbox" name="settings[auto_reduce_stock]" value="1" <?php checked($settings['auto_reduce_stock']); ?> />
                                    <span class="slider"></span>
                                </label>
                                <p class="description"><?php _e('Automatically reduce stock when orders are placed.', 'zpos'); ?></p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Interface Settings Tab -->
        <div id="interface-tab" class="settings-tab <?php echo $current_tab === 'interface' ? 'active' : ''; ?>">
            <div class="settings-section">
                <h2><?php _e('Interface Customization', 'zpos'); ?></h2>
                <table class="form-table">
                    <tbody>
                        <tr>
                            <th scope="row">
                                <label for="theme_color"><?php _e('Theme Color', 'zpos'); ?></label>
                            </th>
                            <td>
                                <input type="color" id="theme_color" name="settings[theme_color]" value="<?php echo esc_attr($settings['theme_color']); ?>" />
                                <p class="description"><?php _e('Primary color for the interface.', 'zpos'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="dashboard_layout"><?php _e('Dashboard Layout', 'zpos'); ?></label>
                            </th>
                            <td>
                                <select id="dashboard_layout" name="settings[dashboard_layout]">
                                    <option value="grid" <?php selected($settings['dashboard_layout'], 'grid'); ?>><?php _e('Grid Layout', 'zpos'); ?></option>
                                    <option value="list" <?php selected($settings['dashboard_layout'], 'list'); ?>><?php _e('List Layout', 'zpos'); ?></option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="items_per_page"><?php _e('Items Per Page', 'zpos'); ?></label>
                            </th>
                            <td>
                                <input type="number" id="items_per_page" name="settings[items_per_page]" value="<?php echo esc_attr($settings['items_per_page']); ?>" min="10" max="100" class="small-text" />
                                <p class="description"><?php _e('Number of items to show per page in listings.', 'zpos'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Enable Dark Mode', 'zpos'); ?></th>
                            <td>
                                <label class="switch">
                                    <input type="checkbox" name="settings[enable_dark_mode]" value="1" <?php checked($settings['enable_dark_mode']); ?> />
                                    <span class="slider"></span>
                                </label>
                                <p class="description"><?php _e('Enable dark mode interface.', 'zpos'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Show Dashboard Widgets', 'zpos'); ?></th>
                            <td>
                                <label class="switch">
                                    <input type="checkbox" name="settings[show_dashboard_widgets]" value="1" <?php checked($settings['show_dashboard_widgets']); ?> />
                                    <span class="slider"></span>
                                </label>
                                <p class="description"><?php _e('Show widget cards on dashboard.', 'zpos'); ?></p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Advanced Settings Tab -->
        <div id="advanced-tab" class="settings-tab <?php echo $current_tab === 'advanced' ? 'active' : ''; ?>">
            <div class="settings-section">
                <h2><?php _e('System Settings', 'zpos'); ?></h2>
                <table class="form-table">
                    <tbody>
                        <tr>
                            <th scope="row"><?php _e('Enable Logging', 'zpos'); ?></th>
                            <td>
                                <label class="switch">
                                    <input type="checkbox" name="settings[enable_logging]" value="1" <?php checked($settings['enable_logging']); ?> />
                                    <span class="slider"></span>
                                </label>
                                <p class="description"><?php _e('Enable system logging for debugging.', 'zpos'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="log_level"><?php _e('Log Level', 'zpos'); ?></label>
                            </th>
                            <td>
                                <select id="log_level" name="settings[log_level]">
                                    <option value="error" <?php selected($settings['log_level'], 'error'); ?>><?php _e('Error', 'zpos'); ?></option>
                                    <option value="warning" <?php selected($settings['log_level'], 'warning'); ?>><?php _e('Warning', 'zpos'); ?></option>
                                    <option value="info" <?php selected($settings['log_level'], 'info'); ?>><?php _e('Info', 'zpos'); ?></option>
                                    <option value="debug" <?php selected($settings['log_level'], 'debug'); ?>><?php _e('Debug', 'zpos'); ?></option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Enable Cache', 'zpos'); ?></th>
                            <td>
                                <label class="switch">
                                    <input type="checkbox" name="settings[cache_enabled]" value="1" <?php checked($settings['cache_enabled']); ?> />
                                    <span class="slider"></span>
                                </label>
                                <p class="description"><?php _e('Enable caching for better performance.', 'zpos'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="cache_duration"><?php _e('Cache Duration (seconds)', 'zpos'); ?></label>
                            </th>
                            <td>
                                <input type="number" id="cache_duration" name="settings[cache_duration]" value="<?php echo esc_attr($settings['cache_duration']); ?>" min="300" class="regular-text" />
                                <p class="description"><?php _e('How long to cache data (minimum 300 seconds).', 'zpos'); ?></p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="settings-section">
                <h2><?php _e('System Tools', 'zpos'); ?></h2>
                <div class="system-tools">
                    <div class="tool-item">
                        <h3><?php _e('Setup Wizard', 'zpos'); ?></h3>
                        <p><?php _e('Re-run the initial setup wizard to reconfigure ZPOS.', 'zpos'); ?></p>
                        <button type="button" class="button" id="rerun-wizard">
                            <span class="dashicons dashicons-admin-tools"></span>
                            <?php _e('Re-run Setup Wizard', 'zpos'); ?>
                        </button>
                    </div>

                    <div class="tool-item">
                        <h3><?php _e('Reset Settings', 'zpos'); ?></h3>
                        <p><?php _e('Reset all settings to their default values.', 'zpos'); ?></p>
                        <button type="button" class="button button-secondary" id="reset-settings">
                            <span class="dashicons dashicons-undo"></span>
                            <?php _e('Reset to Defaults', 'zpos'); ?>
                        </button>
                    </div>

                    <div class="tool-item">
                        <h3><?php _e('System Information', 'zpos'); ?></h3>
                        <p><?php _e('View system information and plugin status.', 'zpos'); ?></p>
                        <button type="button" class="button" id="view-system-info">
                            <span class="dashicons dashicons-info"></span>
                            <?php _e('View System Info', 'zpos'); ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Settings Actions -->
        <div class="settings-actions">
            <button type="submit" class="button button-primary button-large">
                <span class="dashicons dashicons-saved"></span>
                <?php _e('Save Settings', 'zpos'); ?>
            </button>
            <button type="button" class="button button-large" id="preview-changes">
                <span class="dashicons dashicons-visibility"></span>
                <?php _e('Preview Changes', 'zpos'); ?>
            </button>
        </div>
    </form>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Initialize settings system
    ZPOS_Settings.init();
});
</script>

<style>
/* Settings specific styles */
.zpos-settings {
    margin: 20px 0;
}

.zpos-settings h1 {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 20px;
}

.nav-tab {
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.settings-tab {
    display: none;
    background: #fff;
    padding: 20px;
    border: 1px solid #ddd;
    border-top: none;
}

.settings-tab.active {
    display: block;
}

.settings-section {
    margin-bottom: 40px;
}

.settings-section h2 {
    border-bottom: 1px solid #ddd;
    padding-bottom: 10px;
    margin-bottom: 20px;
}

.settings-section h3 {
    margin-top: 30px;
    margin-bottom: 15px;
}

/* Toggle Switch Styles */
.switch {
    position: relative;
    display: inline-block;
    width: 50px;
    height: 24px;
}

.switch input {
    opacity: 0;
    width: 0;
    height: 0;
}

.slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #ccc;
    transition: .4s;
    border-radius: 24px;
}

.slider:before {
    position: absolute;
    content: "";
    height: 18px;
    width: 18px;
    left: 3px;
    bottom: 3px;
    background-color: white;
    transition: .4s;
    border-radius: 50%;
}

input:checked + .slider {
    background-color: #0073aa;
}

input:checked + .slider:before {
    transform: translateX(26px);
}

/* WooCommerce Status */
.woocommerce-status {
    margin-bottom: 30px;
}

.status-card {
    background: #f9f9f9;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
}

.status-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.status-header h3 {
    margin: 0;
}

.status-content {
    display: flex;
    align-items: center;
    gap: 10px;
}

.status-content .spinner {
    float: none;
    margin: 0;
}

/* Sync Options */
.sync-options {
    border-top: 1px solid #ddd;
    padding-top: 20px;
    margin-top: 20px;
}

.manual-sync {
    margin-top: 30px;
    padding: 20px;
    background: #f9f9f9;
    border-radius: 8px;
}

.sync-buttons {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    margin-bottom: 20px;
}

.sync-results {
    padding: 15px;
    border-radius: 8px;
    margin-top: 15px;
    display: none;
}

.sync-results.success {
    background: #d4edda;
    border: 1px solid #c3e6cb;
    color: #155724;
}

.sync-results.error {
    background: #f8d7da;
    border: 1px solid #f5c6cb;
    color: #721c24;
}

/* System Tools */
.system-tools {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
}

.tool-item {
    background: #f9f9f9;
    padding: 20px;
    border-radius: 8px;
    border: 1px solid #ddd;
}

.tool-item h3 {
    margin: 0 0 10px 0;
    color: #333;
}

.tool-item p {
    margin-bottom: 15px;
    color: #666;
}

/* Settings Actions */
.settings-actions {
    position: sticky;
    bottom: 0;
    background: #fff;
    padding: 20px;
    border-top: 1px solid #ddd;
    margin: 20px -20px -20px -20px;
    display: flex;
    gap: 15px;
    align-items: center;
}

.button-large {
    padding: 12px 24px;
    height: auto;
    font-size: 14px;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

/* Responsive */
@media (max-width: 768px) {
    .status-header {
        flex-direction: column;
        gap: 15px;
        align-items: stretch;
    }
    
    .sync-buttons {
        flex-direction: column;
    }
    
    .system-tools {
        grid-template-columns: 1fr;
    }
    
    .settings-actions {
        flex-direction: column;
        align-items: stretch;
    }
}
</style>
