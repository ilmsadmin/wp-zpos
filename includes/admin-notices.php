<?php
/**
 * Display admin notice about the database fix
 *
 * @package ZPOS
 * @since 1.0.0
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Class to handle admin notices
 */
class ZPOS_Admin_Notices {
    
    /**
     * Initialize
     */
    public function __construct() {
        add_action('admin_notices', array($this, 'display_db_fix_notice'));
    }
    
    /**
     * Display notice about database fix
     */
    public function display_db_fix_notice() {
        // Only show on ZPOS pages
        $screen = get_current_screen();
        if (!$screen || strpos($screen->id, 'zpos') === false) {
            return;
        }
        
        // Check if notice has been dismissed
        if (get_option('zpos_db_fix_notice_dismissed')) {
            return;
        }
        
        // Check if sale_price column exists - if it does, we don't need to show this notice
        global $wpdb;
        $products_table = $wpdb->prefix . 'zpos_products';
        $columns = $wpdb->get_col("DESCRIBE $products_table");
        if (in_array('sale_price', $columns)) {
            // Column exists, no need to show the notice
            update_option('zpos_db_fix_notice_dismissed', true);
            return;
        }
        
        ?>
        <div class="notice notice-warning is-dismissible zpos-db-fix-notice">
            <p>
                <strong><?php _e('ZPOS Database Fix Required', 'zpos'); ?></strong>
                <?php _e('There is a missing column in your ZPOS database that may cause errors when saving products.', 'zpos'); ?>
                <a href="<?php echo admin_url('admin.php?page=zpos&action=fix_db&_wpnonce=' . wp_create_nonce('zpos_fix_db')); ?>" class="button button-small">
                    <?php _e('Fix Now', 'zpos'); ?>
                </a>
            </p>
        </div>
        <script type="text/javascript">
            jQuery(document).ready(function($) {
                $(document).on('click', '.zpos-db-fix-notice .notice-dismiss', function() {
                    $.ajax({
                        url: ajaxurl,
                        data: {
                            action: 'zpos_dismiss_db_fix_notice',
                            nonce: '<?php echo wp_create_nonce('zpos_dismiss_notice'); ?>'
                        }
                    });
                });
            });
        </script>
        <?php
    }
}

// Initialize the notices
new ZPOS_Admin_Notices();

// AJAX handler to dismiss the notice
add_action('wp_ajax_zpos_dismiss_db_fix_notice', function() {
    check_ajax_referer('zpos_dismiss_notice', 'nonce');
    update_option('zpos_db_fix_notice_dismissed', true);
    wp_die();
});

// Handle the fix action
add_action('admin_init', function() {
    if (isset($_GET['page']) && $_GET['page'] === 'zpos' && 
        isset($_GET['action']) && $_GET['action'] === 'fix_db' && 
        isset($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'], 'zpos_fix_db')) {
        
        global $wpdb;
        $products_table = $wpdb->prefix . 'zpos_products';
        
        // Check if sale_price column exists
        $columns = $wpdb->get_col("DESCRIBE $products_table");
        if (!in_array('sale_price', $columns)) {
            // Add the missing column
            $wpdb->query("ALTER TABLE $products_table ADD COLUMN sale_price decimal(10,2) DEFAULT NULL AFTER price");
            
            // Show success message
            add_action('admin_notices', function() {
                echo '<div class="notice notice-success is-dismissible"><p>';
                _e('ZPOS database has been successfully updated!', 'zpos');
                echo '</p></div>';
            });
        }
        
        // Dismiss the notice
        update_option('zpos_db_fix_notice_dismissed', true);
        
        // Redirect back to ZPOS dashboard
        wp_redirect(admin_url('admin.php?page=zpos&db_fixed=1'));
        exit;
    }
});
