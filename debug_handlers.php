<?php
// Temporary debug script to check AJAX handlers
add_action('wp_ajax_debug_zpos_handlers', function() {
    global $wp_filter;
    
    $zpos_actions = array();
    
    foreach ($wp_filter as $action => $callbacks) {
        if (strpos($action, 'zpos_') !== false) {
            $zpos_actions[$action] = array_keys($callbacks->callbacks);
        }
    }
    
    wp_send_json_success($zpos_actions);
});

// Also add to admin footer to log handlers
add_action('admin_footer', function() {
    if (isset($_GET['page']) && strpos($_GET['page'], 'zpos') !== false) {
        ?>
        <script>
        console.log('=== ZPOS DEBUG ===');
        console.log('Current page:', '<?php echo esc_js($_GET['page'] ?? ''); ?>');
        console.log('zpos_admin_vars:', window.zpos_admin_vars);
        
        // Test AJAX connectivity
        jQuery.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'debug_zpos_handlers',
                nonce: '<?php echo wp_create_nonce('debug_zpos'); ?>'
            },
            success: function(response) {
                console.log('Registered ZPOS handlers:', response.data);
            },
            error: function(xhr, status, error) {
                console.error('AJAX test failed:', status, error);
            }
        });
        </script>
        <?php
    }
});
?>
