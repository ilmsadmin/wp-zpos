<?php
/**
 * ZPOS Customers Admin Template
 *
 * @package ZPOS
 * @since 1.0.0
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Initialize customers class
$customers = new ZPOS_Customers();

// Handle actions
$action = $_GET['action'] ?? 'list';
$customer_id = $_GET['customer_id'] ?? null;
$message = '';
$error = '';

// Handle form submissions
if ($_POST) {
    if (isset($_POST['bulk_action']) && isset($_POST['selected_customers'])) {
        // Bulk actions
        $bulk_action = sanitize_text_field($_POST['bulk_action']);
        $selected_ids = array_map('intval', $_POST['selected_customers']);
        
        switch ($bulk_action) {
            case 'delete':
                $deleted = 0;
                foreach ($selected_ids as $id) {
                    if ($customers->delete_customer($id)) {
                        $deleted++;
                    }
                }
                $message = sprintf(__('%d customers deleted successfully', 'zpos'), $deleted);
                break;
                
            case 'sync_woocommerce':
                foreach ($selected_ids as $id) {
                    $customers->sync_with_woocommerce($id);
                }
                $message = __('Selected customers synced with WooCommerce', 'zpos');
                break;
                
            case 'export':
                $export_result = $customers->export_to_csv(array(
                    'per_page' => -1,
                    'search' => $_GET['search'] ?? '',
                    'customer_group' => $_GET['customer_group'] ?? '',
                    'status' => $_GET['status'] ?? ''
                ));
                if (is_wp_error($export_result)) {
                    $error = $export_result->get_error_message();
                } else {
                    wp_redirect($export_result['file_url']);
                    exit;
                }
                break;
        }
    } elseif (isset($_POST['save_customer'])) {
        // Save customer
        $customer_data = $_POST;
        unset($customer_data['save_customer']);
        
        $result = $customers->save_customer($customer_data, $customer_id);
        if (is_wp_error($result)) {
            $error = $result->get_error_message();
        } else {
            $message = $customer_id ? __('Customer updated successfully', 'zpos') : __('Customer created successfully', 'zpos');
            if (!$customer_id) {
                wp_redirect(admin_url('admin.php?page=zpos-customers&action=edit&customer_id=' . $result));
                exit;
            }
        }
    } elseif (isset($_POST['import_woocommerce'])) {
        // Import from WooCommerce
        $result = $customers->import_from_woocommerce();
        if (is_wp_error($result)) {
            $error = $result->get_error_message();
        } else {
            $message = sprintf(__('Imported %d customers from WooCommerce', 'zpos'), $result['imported']);
            if (!empty($result['errors'])) {
                $error = implode(', ', $result['errors']);
            }
        }
    }
}

// Handle single customer deletion
if ($action === 'delete' && $customer_id) {
    $result = $customers->delete_customer($customer_id);
    if ($result) {
        $message = __('Customer deleted successfully', 'zpos');
    } else {
        $error = __('Failed to delete customer', 'zpos');
    }
    $action = 'list';
}

// Get current customer for edit
$current_customer = null;
if ($action === 'edit' && $customer_id) {
    $current_customer = $customers->get_customer($customer_id);
    if (!$current_customer) {
        $error = __('Customer not found', 'zpos');
        $action = 'list';
    }
}

// Get customer purchase history for view
$customer_orders = null;
if ($action === 'view' && $customer_id) {
    $current_customer = $customers->get_customer($customer_id);
    if ($current_customer) {
        $customer_orders = $customers->get_customer_orders($customer_id, 20);
    }
}

// Get customers list for list view
$customers_data = null;
if ($action === 'list') {
    $args = array(
        'per_page' => 20,
        'page' => $_GET['paged'] ?? 1,
        'search' => $_GET['search'] ?? '',
        'customer_group' => $_GET['customer_group'] ?? '',
        'status' => $_GET['status'] ?? ''
    );
    $customers_data = $customers->get_customers($args);
}

// Get customer groups for filters
$customer_groups = $customers->get_customer_groups();
?>

<div class="wrap">
    <h1 class="wp-heading-inline">
        <?php 
        switch ($action) {
            case 'view':
                printf(__('Customer: %s', 'zpos'), esc_html($current_customer->first_name . ' ' . $current_customer->last_name));
                break;
            case 'edit':
                _e('Edit Customer', 'zpos');
                break;
            case 'add':
                _e('Add New Customer', 'zpos');
                break;
            default:
                _e('Customers', 'zpos');
        }
        ?>
    </h1>
    
    <?php if ($action === 'list'): ?>
        <a href="<?php echo admin_url('admin.php?page=zpos-customers&action=add'); ?>" class="page-title-action">
            <?php _e('Add New Customer', 'zpos'); ?>
        </a>
    <?php elseif ($action === 'view'): ?>
        <a href="<?php echo admin_url('admin.php?page=zpos-customers&action=edit&customer_id=' . $customer_id); ?>" class="page-title-action">
            <?php _e('Edit Customer', 'zpos'); ?>
        </a>
    <?php endif; ?>
    
    <hr class="wp-header-end">
    
    <?php if ($message): ?>
        <div class="notice notice-success is-dismissible">
            <p><?php echo esc_html($message); ?></p>
        </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="notice notice-error is-dismissible">
            <p><?php echo esc_html($error); ?></p>
        </div>
    <?php endif; ?>
    
    <?php if ($action === 'list'): ?>
        <!-- Customers List View -->
        <form method="get">
            <input type="hidden" name="page" value="zpos-customers">
            <div class="tablenav top">
                <div class="alignleft actions">
                    <!-- Search -->
                    <input type="search" name="search" value="<?php echo esc_attr($_GET['search'] ?? ''); ?>" placeholder="<?php _e('Search customers...', 'zpos'); ?>">
                    
                    <!-- Customer Group Filter -->
                    <select name="customer_group">
                        <option value=""><?php _e('All Groups', 'zpos'); ?></option>
                        <?php foreach ($customer_groups as $group): ?>
                            <option value="<?php echo esc_attr($group); ?>" <?php selected($_GET['customer_group'] ?? '', $group); ?>>
                                <?php echo esc_html(ucfirst($group)); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    
                    <!-- Status Filter -->
                    <select name="status">
                        <option value=""><?php _e('All Statuses', 'zpos'); ?></option>
                        <option value="active" <?php selected($_GET['status'] ?? '', 'active'); ?>><?php _e('Active', 'zpos'); ?></option>
                        <option value="inactive" <?php selected($_GET['status'] ?? '', 'inactive'); ?>><?php _e('Inactive', 'zpos'); ?></option>
                    </select>
                    
                    <input type="submit" class="button" value="<?php _e('Filter', 'zpos'); ?>">
                </div>
                
                <div class="alignright actions">
                    <?php if (class_exists('WooCommerce')): ?>
                        <form method="post" style="display: inline-block;">
                            <input type="submit" name="import_woocommerce" class="button" value="<?php _e('Import from WooCommerce', 'zpos'); ?>">
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </form>
        
        <form method="post">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <td class="manage-column column-cb check-column">
                            <input type="checkbox" id="cb-select-all-1">
                        </td>
                        <th class="manage-column"><?php _e('Avatar', 'zpos'); ?></th>
                        <th class="manage-column column-primary"><?php _e('Name', 'zpos'); ?></th>
                        <th class="manage-column"><?php _e('Email', 'zpos'); ?></th>
                        <th class="manage-column"><?php _e('Phone', 'zpos'); ?></th>
                        <th class="manage-column"><?php _e('Group', 'zpos'); ?></th>
                        <th class="manage-column"><?php _e('Orders', 'zpos'); ?></th>
                        <th class="manage-column"><?php _e('Total Spent', 'zpos'); ?></th>
                        <th class="manage-column"><?php _e('Status', 'zpos'); ?></th>
                        <th class="manage-column"><?php _e('Date', 'zpos'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($customers_data['customers'])): ?>
                        <?php foreach ($customers_data['customers'] as $customer): ?>
                            <tr>
                                <th scope="row" class="check-column">
                                    <input type="checkbox" name="selected_customers[]" value="<?php echo $customer->id; ?>">
                                </th>
                                <td>
                                    <?php if ($customer->avatar_url): ?>
                                        <img src="<?php echo esc_url($customer->avatar_url); ?>" alt="" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
                                    <?php else: ?>
                                        <div style="width: 40px; height: 40px; background: #f0f0f0; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                            <span class="dashicons dashicons-admin-users" style="color: #ccc;"></span>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="column-primary">
                                    <strong>
                                        <a href="<?php echo admin_url('admin.php?page=zpos-customers&action=view&customer_id=' . $customer->id); ?>">
                                            <?php echo esc_html($customer->first_name . ' ' . $customer->last_name); ?>
                                        </a>
                                    </strong>
                                    <div class="row-actions">
                                        <span class="view">
                                            <a href="<?php echo admin_url('admin.php?page=zpos-customers&action=view&customer_id=' . $customer->id); ?>">
                                                <?php _e('View', 'zpos'); ?>
                                            </a> |
                                        </span>
                                        <span class="edit">
                                            <a href="<?php echo admin_url('admin.php?page=zpos-customers&action=edit&customer_id=' . $customer->id); ?>">
                                                <?php _e('Edit', 'zpos'); ?>
                                            </a> |
                                        </span>
                                        <span class="trash">
                                            <a href="<?php echo admin_url('admin.php?page=zpos-customers&action=delete&customer_id=' . $customer->id); ?>" 
                                               onclick="return confirm('<?php _e('Are you sure you want to delete this customer?', 'zpos'); ?>')">
                                                <?php _e('Delete', 'zpos'); ?>
                                            </a>
                                        </span>
                                    </div>
                                </td>
                                <td><?php echo esc_html($customer->email); ?></td>
                                <td><?php echo esc_html($customer->phone); ?></td>
                                <td>
                                    <span class="customer-group group-<?php echo esc_attr($customer->customer_group); ?>">
                                        <?php echo esc_html(ucfirst($customer->customer_group)); ?>
                                    </span>
                                </td>
                                <td><?php echo number_format($customer->orders_count); ?></td>
                                <td><?php echo wc_price($customer->total_spent); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $customer->status; ?>">
                                        <?php echo ucfirst($customer->status); ?>
                                    </span>
                                </td>
                                <td><?php echo date_i18n(get_option('date_format'), strtotime($customer->created_at)); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="10" class="no-items"><?php _e('No customers found.', 'zpos'); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            
            <?php if (!empty($customers_data['customers'])): ?>
                <div class="tablenav bottom">
                    <div class="alignleft actions bulkactions">
                        <select name="bulk_action">
                            <option value=""><?php _e('Bulk Actions', 'zpos'); ?></option>
                            <option value="delete"><?php _e('Delete', 'zpos'); ?></option>
                            <option value="export"><?php _e('Export to CSV', 'zpos'); ?></option>
                            <?php if (class_exists('WooCommerce')): ?>
                                <option value="sync_woocommerce"><?php _e('Sync with WooCommerce', 'zpos'); ?></option>
                            <?php endif; ?>
                        </select>
                        <input type="submit" class="button action" value="<?php _e('Apply', 'zpos'); ?>">
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($customers_data['total_pages'] > 1): ?>
                        <div class="tablenav-pages">
                            <?php
                            $pagination_args = array(
                                'base' => add_query_arg('paged', '%#%'),
                                'format' => '',
                                'current' => $customers_data['current_page'],
                                'total' => $customers_data['total_pages'],
                                'prev_text' => '‹',
                                'next_text' => '›'
                            );
                            echo paginate_links($pagination_args);
                            ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </form>
        
    <?php elseif ($action === 'view' && $current_customer): ?>
        <!-- Customer Details View -->
        <div id="poststuff">
            <div id="post-body" class="metabox-holder columns-2">
                <div id="post-body-content">
                    <!-- Customer Information -->
                    <div class="postbox">
                        <h2 class="hndle"><?php _e('Customer Information', 'zpos'); ?></h2>
                        <div class="inside">
                            <table class="form-table">
                                <tr>
                                    <th><?php _e('Name', 'zpos'); ?>:</th>
                                    <td><?php echo esc_html($current_customer->first_name . ' ' . $current_customer->last_name); ?></td>
                                </tr>
                                <tr>
                                    <th><?php _e('Email', 'zpos'); ?>:</th>
                                    <td><a href="mailto:<?php echo esc_attr($current_customer->email); ?>"><?php echo esc_html($current_customer->email); ?></a></td>
                                </tr>
                                <tr>
                                    <th><?php _e('Phone', 'zpos'); ?>:</th>
                                    <td><a href="tel:<?php echo esc_attr($current_customer->phone); ?>"><?php echo esc_html($current_customer->phone); ?></a></td>
                                </tr>
                                <tr>
                                    <th><?php _e('Address', 'zpos'); ?>:</th>
                                    <td>
                                        <?php 
                                        $address_parts = array_filter(array(
                                            $current_customer->address_line_1,
                                            $current_customer->address_line_2,
                                            $current_customer->city,
                                            $current_customer->state,
                                            $current_customer->postal_code,
                                            $current_customer->country
                                        ));
                                        echo esc_html(implode(', ', $address_parts));
                                        ?>
                                    </td>
                                </tr>
                                <tr>
                                    <th><?php _e('Customer Group', 'zpos'); ?>:</th>
                                    <td><?php echo esc_html(ucfirst($current_customer->customer_group)); ?></td>
                                </tr>
                                <tr>
                                    <th><?php _e('Status', 'zpos'); ?>:</th>
                                    <td>
                                        <span class="status-badge status-<?php echo $current_customer->status; ?>">
                                            <?php echo ucfirst($current_customer->status); ?>
                                        </span>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    
                    <!-- Purchase History -->
                    <div class="postbox">
                        <h2 class="hndle"><?php _e('Recent Orders', 'zpos'); ?></h2>
                        <div class="inside">
                            <?php if (!empty($customer_orders)): ?>
                                <table class="wp-list-table widefat striped">
                                    <thead>
                                        <tr>
                                            <th><?php _e('Order #', 'zpos'); ?></th>
                                            <th><?php _e('Date', 'zpos'); ?></th>
                                            <th><?php _e('Status', 'zpos'); ?></th>
                                            <th><?php _e('Total', 'zpos'); ?></th>
                                            <th><?php _e('Actions', 'zpos'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($customer_orders as $order): ?>
                                            <tr>
                                                <td>#<?php echo $order->id; ?></td>
                                                <td><?php echo date_i18n(get_option('date_format'), strtotime($order->created_at)); ?></td>
                                                <td>
                                                    <span class="status-badge status-<?php echo $order->status; ?>">
                                                        <?php echo ucfirst($order->status); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo wc_price($order->total_amount); ?></td>
                                                <td>
                                                    <a href="<?php echo admin_url('admin.php?page=zpos-sales&action=view&sale_id=' . $order->id); ?>" class="button button-small">
                                                        <?php _e('View', 'zpos'); ?>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php else: ?>
                                <p><?php _e('No orders found for this customer.', 'zpos'); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div id="postbox-container-1" class="postbox-container">
                    <!-- Customer Stats -->
                    <div class="postbox">
                        <h2 class="hndle"><?php _e('Customer Stats', 'zpos'); ?></h2>
                        <div class="inside">
                            <ul>
                                <li><strong><?php _e('Total Orders', 'zpos'); ?>:</strong> <?php echo number_format($current_customer->orders_count); ?></li>
                                <li><strong><?php _e('Total Spent', 'zpos'); ?>:</strong> <?php echo wc_price($current_customer->total_spent); ?></li>
                                <li><strong><?php _e('Average Order Value', 'zpos'); ?>:</strong> 
                                    <?php echo $current_customer->orders_count > 0 ? wc_price($current_customer->total_spent / $current_customer->orders_count) : wc_price(0); ?>
                                </li>
                                <li><strong><?php _e('Last Order', 'zpos'); ?>:</strong> 
                                    <?php echo $current_customer->last_order_date ? date_i18n(get_option('date_format'), strtotime($current_customer->last_order_date)) : __('Never', 'zpos'); ?>
                                </li>
                                <li><strong><?php _e('Loyalty Points', 'zpos'); ?>:</strong> <?php echo number_format($current_customer->loyalty_points); ?></li>
                                <li><strong><?php _e('Referral Code', 'zpos'); ?>:</strong> <?php echo esc_html($current_customer->referral_code); ?></li>
                            </ul>
                        </div>
                    </div>
                    
                    <!-- Quick Actions -->
                    <div class="postbox">
                        <h2 class="hndle"><?php _e('Quick Actions', 'zpos'); ?></h2>
                        <div class="inside">
                            <p>
                                <a href="<?php echo admin_url('admin.php?page=zpos-customers&action=edit&customer_id=' . $customer_id); ?>" class="button button-primary">
                                    <?php _e('Edit Customer', 'zpos'); ?>
                                </a>
                            </p>
                            <p>
                                <a href="<?php echo admin_url('admin.php?page=zpos-sales&action=add&customer_id=' . $customer_id); ?>" class="button">
                                    <?php _e('Create New Order', 'zpos'); ?>
                                </a>
                            </p>
                            <?php if (class_exists('WooCommerce')): ?>
                                <p>
                                    <button onclick="syncCustomerToWooCommerce(<?php echo $customer_id; ?>)" class="button">
                                        <?php _e('Sync to WooCommerce', 'zpos'); ?>
                                    </button>
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
    <?php elseif ($action === 'add' || $action === 'edit'): ?>
        <!-- Add/Edit Customer Form -->
        <form method="post" enctype="multipart/form-data">
            <div id="poststuff">
                <div id="post-body" class="metabox-holder columns-2">
                    <div id="post-body-content">
                        <!-- Personal Information -->
                        <div class="postbox">
                            <h2 class="hndle"><?php _e('Personal Information', 'zpos'); ?></h2>
                            <div class="inside">
                                <table class="form-table">
                                    <tr>
                                        <th scope="row">
                                            <label for="first_name"><?php _e('First Name', 'zpos'); ?> *</label>
                                        </th>
                                        <td>
                                            <input type="text" id="first_name" name="first_name" value="<?php echo esc_attr($current_customer->first_name ?? ''); ?>" class="regular-text" required>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">
                                            <label for="last_name"><?php _e('Last Name', 'zpos'); ?> *</label>
                                        </th>
                                        <td>
                                            <input type="text" id="last_name" name="last_name" value="<?php echo esc_attr($current_customer->last_name ?? ''); ?>" class="regular-text" required>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">
                                            <label for="email"><?php _e('Email', 'zpos'); ?></label>
                                        </th>
                                        <td>
                                            <input type="email" id="email" name="email" value="<?php echo esc_attr($current_customer->email ?? ''); ?>" class="regular-text">
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">
                                            <label for="phone"><?php _e('Phone', 'zpos'); ?></label>
                                        </th>
                                        <td>
                                            <input type="tel" id="phone" name="phone" value="<?php echo esc_attr($current_customer->phone ?? ''); ?>" class="regular-text">
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">
                                            <label for="date_of_birth"><?php _e('Date of Birth', 'zpos'); ?></label>
                                        </th>
                                        <td>
                                            <input type="date" id="date_of_birth" name="date_of_birth" value="<?php echo esc_attr($current_customer->date_of_birth ?? ''); ?>" class="regular-text">
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">
                                            <label for="gender"><?php _e('Gender', 'zpos'); ?></label>
                                        </th>
                                        <td>
                                            <select id="gender" name="gender">
                                                <option value=""><?php _e('Select Gender', 'zpos'); ?></option>
                                                <option value="male" <?php selected($current_customer->gender ?? '', 'male'); ?>><?php _e('Male', 'zpos'); ?></option>
                                                <option value="female" <?php selected($current_customer->gender ?? '', 'female'); ?>><?php _e('Female', 'zpos'); ?></option>
                                                <option value="other" <?php selected($current_customer->gender ?? '', 'other'); ?>><?php _e('Other', 'zpos'); ?></option>
                                            </select>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                        
                        <!-- Address Information -->
                        <div class="postbox">
                            <h2 class="hndle"><?php _e('Address Information', 'zpos'); ?></h2>
                            <div class="inside">
                                <table class="form-table">
                                    <tr>
                                        <th scope="row">
                                            <label for="address_line_1"><?php _e('Address Line 1', 'zpos'); ?></label>
                                        </th>
                                        <td>
                                            <input type="text" id="address_line_1" name="address_line_1" value="<?php echo esc_attr($current_customer->address_line_1 ?? ''); ?>" class="large-text">
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">
                                            <label for="address_line_2"><?php _e('Address Line 2', 'zpos'); ?></label>
                                        </th>
                                        <td>
                                            <input type="text" id="address_line_2" name="address_line_2" value="<?php echo esc_attr($current_customer->address_line_2 ?? ''); ?>" class="large-text">
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">
                                            <label for="city"><?php _e('City', 'zpos'); ?></label>
                                        </th>
                                        <td>
                                            <input type="text" id="city" name="city" value="<?php echo esc_attr($current_customer->city ?? ''); ?>" class="regular-text">
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">
                                            <label for="state"><?php _e('State/Province', 'zpos'); ?></label>
                                        </th>
                                        <td>
                                            <input type="text" id="state" name="state" value="<?php echo esc_attr($current_customer->state ?? ''); ?>" class="regular-text">
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">
                                            <label for="postal_code"><?php _e('Postal Code', 'zpos'); ?></label>
                                        </th>
                                        <td>
                                            <input type="text" id="postal_code" name="postal_code" value="<?php echo esc_attr($current_customer->postal_code ?? ''); ?>" class="regular-text">
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">
                                            <label for="country"><?php _e('Country', 'zpos'); ?></label>
                                        </th>
                                        <td>
                                            <select id="country" name="country">
                                                <option value="VN" <?php selected($current_customer->country ?? 'VN', 'VN'); ?>><?php _e('Vietnam', 'zpos'); ?></option>
                                                <option value="US" <?php selected($current_customer->country ?? '', 'US'); ?>><?php _e('United States', 'zpos'); ?></option>
                                                <option value="GB" <?php selected($current_customer->country ?? '', 'GB'); ?>><?php _e('United Kingdom', 'zpos'); ?></option>
                                                <!-- Add more countries as needed -->
                                            </select>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                        
                        <!-- Additional Information -->
                        <div class="postbox">
                            <h2 class="hndle"><?php _e('Additional Information', 'zpos'); ?></h2>
                            <div class="inside">
                                <table class="form-table">
                                    <tr>
                                        <th scope="row">
                                            <label for="notes"><?php _e('Notes', 'zpos'); ?></label>
                                        </th>
                                        <td>
                                            <textarea id="notes" name="notes" rows="5" class="large-text"><?php echo esc_textarea($current_customer->notes ?? ''); ?></textarea>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <div id="postbox-container-1" class="postbox-container">
                        <!-- Publish Box -->
                        <div class="postbox">
                            <h2 class="hndle"><?php _e('Save', 'zpos'); ?></h2>
                            <div class="inside">
                                <div class="submitbox">
                                    <div id="minor-publishing">
                                        <div class="misc-pub-section">
                                            <label for="status"><?php _e('Status:', 'zpos'); ?></label>
                                            <select id="status" name="status">
                                                <option value="active" <?php selected($current_customer->status ?? 'active', 'active'); ?>><?php _e('Active', 'zpos'); ?></option>
                                                <option value="inactive" <?php selected($current_customer->status ?? '', 'inactive'); ?>><?php _e('Inactive', 'zpos'); ?></option>
                                            </select>
                                        </div>
                                    </div>
                                    <div id="major-publishing-actions">
                                        <div id="publishing-action">
                                            <input type="submit" name="save_customer" class="button-primary" value="<?php echo $current_customer ? __('Update Customer', 'zpos') : __('Add Customer', 'zpos'); ?>">
                                        </div>
                                        <div class="clear"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Customer Group -->
                        <div class="postbox">
                            <h2 class="hndle"><?php _e('Customer Group', 'zpos'); ?></h2>
                            <div class="inside">
                                <select name="customer_group">
                                    <?php foreach ($customer_groups as $group): ?>
                                        <option value="<?php echo esc_attr($group); ?>" <?php selected($current_customer->customer_group ?? 'general', $group); ?>>
                                            <?php echo esc_html(ucfirst($group)); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Loyalty & Discounts -->
                        <div class="postbox">
                            <h2 class="hndle"><?php _e('Loyalty & Discounts', 'zpos'); ?></h2>
                            <div class="inside">
                                <table class="form-table">
                                    <tr>
                                        <th scope="row">
                                            <label for="discount_percent"><?php _e('Discount %', 'zpos'); ?></label>
                                        </th>
                                        <td>
                                            <input type="number" step="0.01" max="100" id="discount_percent" name="discount_percent" value="<?php echo esc_attr($current_customer->discount_percent ?? 0); ?>" class="small-text">
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">
                                            <label for="credit_limit"><?php _e('Credit Limit', 'zpos'); ?></label>
                                        </th>
                                        <td>
                                            <input type="number" step="0.01" id="credit_limit" name="credit_limit" value="<?php echo esc_attr($current_customer->credit_limit ?? 0); ?>" class="regular-text">
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">
                                            <label for="loyalty_points"><?php _e('Loyalty Points', 'zpos'); ?></label>
                                        </th>
                                        <td>
                                            <input type="number" id="loyalty_points" name="loyalty_points" value="<?php echo esc_attr($current_customer->loyalty_points ?? 0); ?>" class="regular-text">
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">
                                            <label for="referral_code"><?php _e('Referral Code', 'zpos'); ?></label>
                                        </th>
                                        <td>
                                            <input type="text" id="referral_code" name="referral_code" value="<?php echo esc_attr($current_customer->referral_code ?? ''); ?>" class="regular-text">
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
        
    <?php endif; ?>
</div>

<style>
.status-badge {
    padding: 2px 8px;
    border-radius: 3px;
    font-size: 11px;
    font-weight: bold;
    text-transform: uppercase;
}
.status-active {
    background: #d1e7dd;
    color: #0a3622;
}
.status-inactive {
    background: #f8d7da;
    color: #58151c;
}
.customer-group {
    padding: 2px 6px;
    border-radius: 3px;
    font-size: 11px;
    font-weight: bold;
}
.group-general {
    background: #e2e3e5;
    color: #383d41;
}
.group-vip {
    background: #ffeaa7;
    color: #6c5ce7;
}
.group-wholesale {
    background: #a8e6cf;
    color: #00b894;
}
.group-retail {
    background: #ddd6fe;
    color: #8b5cf6;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Select all checkbox
    $('#cb-select-all-1').change(function() {
        $('input[name="selected_customers[]"]').prop('checked', $(this).is(':checked'));
    });
});

<?php if (class_exists('WooCommerce')): ?>
function syncCustomerToWooCommerce(customerId) {
    if (confirm('<?php _e('Are you sure you want to sync this customer to WooCommerce?', 'zpos'); ?>')) {
        // AJAX call to sync customer
        jQuery.post(ajaxurl, {
            action: 'zpos_sync_customer_woocommerce',
            customer_id: customerId,
            nonce: '<?php echo wp_create_nonce('zpos_sync_customer'); ?>'
        }, function(response) {
            if (response.success) {
                alert('<?php _e('Customer synced successfully!', 'zpos'); ?>');
                location.reload();
            } else {
                alert('<?php _e('Failed to sync customer.', 'zpos'); ?>');
            }
        });
    }
}
<?php endif; ?>
</script>
