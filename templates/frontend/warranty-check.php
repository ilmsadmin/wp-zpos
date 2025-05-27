<?php
/**
 * Frontend Warranty Check Template
 *
 * @link       https://yourwebsite.com
 * @since      1.0.0
 *
 * @package    ZPOS
 * @subpackage ZPOS/templates/frontend
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="zpos-warranty-check-wrapper">
    <div class="zpos-warranty-check-container">
        <header class="zpos-warranty-header">
            <h2 class="zpos-title"><?php _e('Warranty Status Check', 'zpos'); ?></h2>
            <p class="zpos-description">
                <?php _e('Check the warranty status of your purchased products by entering your phone number or product serial number below.', 'zpos'); ?>
            </p>
        </header>

        <div class="zpos-warranty-form-section">
            <form id="zpos-warranty-form" class="zpos-warranty-form">
                <div class="zpos-form-grid">
                    <div class="zpos-input-group">
                        <label for="warranty_phone" class="zpos-label">
                            <span class="dashicons dashicons-phone"></span>
                            <?php _e('Phone Number', 'zpos'); ?>
                        </label>
                        <input 
                            type="tel" 
                            id="warranty_phone" 
                            name="phone" 
                            placeholder="<?php _e('e.g., +1234567890', 'zpos'); ?>" 
                            class="zpos-input"
                        >
                        <small class="zpos-help-text">
                            <?php _e('Enter the phone number used during purchase', 'zpos'); ?>
                        </small>
                    </div>
                    
                    <div class="zpos-separator">
                        <span class="zpos-separator-text"><?php _e('OR', 'zpos'); ?></span>
                        <div class="zpos-separator-line"></div>
                    </div>
                    
                    <div class="zpos-input-group">
                        <label for="warranty_serial" class="zpos-label">
                            <span class="dashicons dashicons-admin-network"></span>
                            <?php _e('Serial Number', 'zpos'); ?>
                        </label>
                        <input 
                            type="text" 
                            id="warranty_serial" 
                            name="serial" 
                            placeholder="<?php _e('e.g., SN123456789', 'zpos'); ?>" 
                            class="zpos-input"
                        >
                        <small class="zpos-help-text">
                            <?php _e('Find this on your product label or receipt', 'zpos'); ?>
                        </small>
                    </div>
                </div>

                <div class="zpos-form-actions">
                    <button type="submit" class="zpos-btn zpos-btn-primary zpos-btn-large">
                        <span class="zpos-btn-icon">
                            <span class="dashicons dashicons-search"></span>
                        </span>
                        <span class="zpos-btn-text"><?php _e('Check Warranty', 'zpos'); ?></span>
                        <span class="zpos-spinner">
                            <span class="zpos-spinner-icon"></span>
                        </span>
                    </button>
                </div>
            </form>
        </div>

        <div id="zpos-warranty-results" class="zpos-warranty-results" style="display: none;">
            <!-- Results will be populated via AJAX -->
        </div>

        <div class="zpos-warranty-help">
            <h4><?php _e('Need Help?', 'zpos'); ?></h4>
            <ul>
                <li><?php _e('Make sure to enter the exact phone number or serial number associated with your purchase.', 'zpos'); ?></li>
                <li><?php _e('Serial numbers are case-sensitive. Please enter them exactly as shown on your product.', 'zpos'); ?></li>
                <li><?php _e('If you cannot find your warranty information, please contact our customer support.', 'zpos'); ?></li>
            </ul>
        </div>
    </div>
</div>

<!-- Warranty Results Template -->
<script type="text/template" id="zpos-warranty-results-template">
    <div class="zpos-results-header">
        <h3>
            <span class="dashicons dashicons-yes-alt"></span>
            <?php _e('Warranty Information Found', 'zpos'); ?>
        </h3>
        <p class="zpos-results-count">
            {{#if multiple}}
                <?php _e('Found {{count}} warranty records', 'zpos'); ?>
            {{else}}
                <?php _e('Found 1 warranty record', 'zpos'); ?>
            {{/if}}
        </p>
    </div>

    <div class="zpos-warranties-list">
        {{#each warranties}}
        <div class="zpos-warranty-card">
            <div class="zpos-warranty-header">
                <div class="zpos-product-info">
                    <h4 class="zpos-product-name">{{product_name}}</h4>
                    {{#if sku}}
                    <span class="zpos-product-sku">SKU: {{sku}}</span>
                    {{/if}}
                    <span class="zpos-serial-number">SN: {{serial_number}}</span>
                </div>
                <div class="zpos-warranty-status zpos-status-{{status_class}}">
                    <span class="zpos-status-badge">{{status_text}}</span>
                </div>
            </div>

            <div class="zpos-warranty-details">
                <div class="zpos-detail-row">
                    <span class="zpos-detail-label"><?php _e('Warranty Package:', 'zpos'); ?></span>
                    <span class="zpos-detail-value">{{package_name}}</span>
                </div>
                <div class="zpos-detail-row">
                    <span class="zpos-detail-label"><?php _e('Start Date:', 'zpos'); ?></span>
                    <span class="zpos-detail-value">{{start_date}}</span>
                </div>
                <div class="zpos-detail-row">
                    <span class="zpos-detail-label"><?php _e('End Date:', 'zpos'); ?></span>
                    <span class="zpos-detail-value">{{end_date}}</span>
                </div>
                {{#if days_remaining}}
                <div class="zpos-detail-row zpos-days-remaining">
                    <span class="zpos-detail-label"><?php _e('Days Remaining:', 'zpos'); ?></span>
                    <span class="zpos-detail-value zpos-highlight">{{days_remaining}} <?php _e('days', 'zpos'); ?></span>
                </div>
                {{/if}}
            </div>

            {{#if status_class 'eq' 'active'}}
            <div class="zpos-warranty-actions">
                <button type="button" class="zpos-btn zpos-btn-secondary zpos-btn-small" onclick="zposContactSupport('{{id}}')">
                    <span class="dashicons dashicons-email-alt"></span>
                    <?php _e('Contact Support', 'zpos'); ?>
                </button>
            </div>
            {{/if}}
        </div>
        {{/each}}
    </div>

    <div class="zpos-results-footer">
        <p class="zpos-disclaimer">
            <?php _e('For warranty claims or technical support, please contact our customer service team with your warranty information.', 'zpos'); ?>
        </p>
    </div>
</script>

<!-- No Results Template -->
<script type="text/template" id="zpos-no-results-template">
    <div class="zpos-no-results">
        <div class="zpos-no-results-icon">
            <span class="dashicons dashicons-search"></span>
        </div>
        <h3><?php _e('No Warranty Found', 'zpos'); ?></h3>
        <p><?php _e('We could not find any warranty information for the provided phone number or serial number.', 'zpos'); ?></p>
        
        <div class="zpos-suggestions">
            <h4><?php _e('Please check:', 'zpos'); ?></h4>
            <ul>
                <li><?php _e('The phone number or serial number is entered correctly', 'zpos'); ?></li>
                <li><?php _e('The product was purchased from our store', 'zpos'); ?></li>
                <li><?php _e('The warranty was properly registered at the time of purchase', 'zpos'); ?></li>
            </ul>
        </div>

        <div class="zpos-contact-support">
            <p><?php _e('Still having trouble? Contact our customer support team for assistance.', 'zpos'); ?></p>
            <button type="button" class="zpos-btn zpos-btn-primary" onclick="zposContactSupport()">
                <span class="dashicons dashicons-email-alt"></span>
                <?php _e('Contact Support', 'zpos'); ?>
            </button>
        </div>
    </div>
</script>
