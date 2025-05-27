<?php
/**
 * ZPOS Categories Admin Template
 *
 * @package ZPOS
 * @since 1.0.0
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Get data passed from controller
$page_title = isset($data['categories']['page_title']) ? $data['categories']['page_title'] : __('Categories Management', 'zpos');
$nonce = isset($data['categories']['nonce']) ? $data['categories']['nonce'] : '';
$categories = isset($data['categories']['categories']) ? $data['categories']['categories'] : array();
$category_tree = isset($data['categories']['category_tree']) ? $data['categories']['category_tree'] : array();
$category_options = isset($data['categories']['category_options']) ? $data['categories']['category_options'] : '';
$error = isset($data['categories']['error']) ? $data['categories']['error'] : '';

// Current action
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$category_id = isset($_GET['category_id']) ? $_GET['category_id'] : null;
?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php echo esc_html($page_title); ?></h1>
    
    <?php if ($error): ?>
        <div class="notice notice-error">
            <p><strong>Error:</strong> <?php echo esc_html($error); ?></p>
        </div>
    <?php endif; ?>
    
    <?php if ($action === 'list'): ?>
        <a href="<?php echo admin_url('admin.php?page=zpos-categories&action=add'); ?>" class="page-title-action">
            <?php _e('Add New Category', 'zpos'); ?>
        </a>
    <?php endif; ?>

    <hr class="wp-header-end">

    <!-- Messages -->
    <div id="zpos-messages" style="display: none;">
        <div id="zpos-success-message" class="notice notice-success is-dismissible" style="display: none;">
            <p></p>
        </div>
        <div id="zpos-error-message" class="notice notice-error is-dismissible" style="display: none;">
            <p></p>
        </div>
    </div>

    <?php if ($action === 'list'): ?>
        <!-- Categories List -->
        <div class="zpos-categories-list">
            <!-- Categories Table -->
            <table class="wp-list-table widefat fixed striped table-view-list">
                <thead>
                    <tr>
                        <td id="cb" class="manage-column column-cb check-column">
                            <label class="screen-reader-text" for="cb-select-all-1"><?php _e('Select All', 'zpos'); ?></label>
                            <input id="cb-select-all-1" type="checkbox">
                        </td>
                        <th scope="col" class="manage-column column-name column-primary">
                            <?php _e('Name', 'zpos'); ?>
                        </th>
                        <th scope="col" class="manage-column column-description"><?php _e('Description', 'zpos'); ?></th>
                        <th scope="col" class="manage-column column-slug"><?php _e('Slug', 'zpos'); ?></th>
                        <th scope="col" class="manage-column column-parent"><?php _e('Parent', 'zpos'); ?></th>
                        <th scope="col" class="manage-column column-products"><?php _e('Products', 'zpos'); ?></th>
                        <th scope="col" class="manage-column column-status"><?php _e('Status', 'zpos'); ?></th>
                        <th scope="col" class="manage-column column-actions"><?php _e('Actions', 'zpos'); ?></th>
                    </tr>
                </thead>
                <tbody id="categories-list">
                    <?php if (!empty($categories)): ?>
                        <?php foreach ($categories as $category): ?>
                            <tr data-category-id="<?php echo esc_attr($category->id); ?>">
                                <th scope="row" class="check-column">
                                    <input type="checkbox" name="selected_categories[]" value="<?php echo esc_attr($category->id); ?>">
                                </th>
                                <td class="name column-name column-primary">
                                    <strong>
                                        <a class="row-title" href="<?php echo admin_url('admin.php?page=zpos-categories&action=edit&category_id=' . $category->id); ?>">
                                            <?php echo esc_html($category->name); ?>
                                        </a>
                                    </strong>
                                    <div class="row-actions">
                                        <span class="edit">
                                            <a href="<?php echo admin_url('admin.php?page=zpos-categories&action=edit&category_id=' . $category->id); ?>">
                                                <?php _e('Edit', 'zpos'); ?>
                                            </a> |
                                        </span>
                                        <span class="delete">
                                            <a href="#" class="delete-category" data-category-id="<?php echo esc_attr($category->id); ?>" data-category-name="<?php echo esc_attr($category->name); ?>">
                                                <?php _e('Delete', 'zpos'); ?>
                                            </a>
                                        </span>
                                    </div>
                                </td>
                                <td class="description column-description">
                                    <?php echo esc_html(wp_trim_words($category->description, 10)); ?>
                                </td>
                                <td class="slug column-slug">
                                    <code><?php echo esc_html($category->slug); ?></code>
                                </td>
                                <td class="parent column-parent">
                                    <?php if ($category->parent_id): ?>
                                        <?php
                                        // Find parent category name
                                        $parent = array_filter($categories, function($cat) use ($category) {
                                            return $cat->id == $category->parent_id;
                                        });
                                        $parent = reset($parent);
                                        echo $parent ? esc_html($parent->name) : __('Unknown', 'zpos');
                                        ?>
                                    <?php else: ?>
                                        <span class="na">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="products column-products">
                                    <span class="product-count" data-category-id="<?php echo esc_attr($category->id); ?>">
                                        <?php _e('Loading...', 'zpos'); ?>
                                    </span>
                                </td>
                                <td class="status column-status">
                                    <span class="status-<?php echo esc_attr($category->status); ?>">
                                        <?php echo esc_html(ucfirst($category->status)); ?>
                                    </span>
                                </td>
                                <td class="actions column-actions">
                                    <a href="<?php echo admin_url('admin.php?page=zpos-categories&action=edit&category_id=' . $category->id); ?>" class="button button-small">
                                        <?php _e('Edit', 'zpos'); ?>
                                    </a>
                                    <button type="button" class="button button-small delete-category" data-category-id="<?php echo esc_attr($category->id); ?>" data-category-name="<?php echo esc_attr($category->name); ?>">
                                        <?php _e('Delete', 'zpos'); ?>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr class="no-items">
                            <td class="colspanchange" colspan="8"><?php _e('No categories found.', 'zpos'); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- Bulk Actions -->
            <div class="tablenav bottom">
                <div class="alignleft actions bulkactions">
                    <select name="action2" id="bulk-action-selector-bottom">
                        <option value="-1"><?php _e('Bulk Actions', 'zpos'); ?></option>
                        <option value="delete"><?php _e('Delete', 'zpos'); ?></option>
                        <option value="activate"><?php _e('Activate', 'zpos'); ?></option>
                        <option value="deactivate"><?php _e('Deactivate', 'zpos'); ?></option>
                    </select>
                    <input type="submit" id="doaction2" class="button action" value="<?php esc_attr_e('Apply', 'zpos'); ?>">
                </div>
            </div>
        </div>

    <?php elseif ($action === 'add' || $action === 'edit'): ?>
        <!-- Add/Edit Category Form -->
        <div class="zpos-category-form">
            <form method="post" id="category-form">
                <input type="hidden" name="action" value="save_category">
                <?php if ($action === 'edit' && $category_id): ?>
                    <input type="hidden" name="category_id" value="<?php echo esc_attr($category_id); ?>">
                <?php endif; ?>

                <table class="form-table" role="presentation">
                    <tbody>
                        <tr>
                            <th scope="row">
                                <label for="category_name"><?php _e('Name', 'zpos'); ?> <span class="required">*</span></label>
                            </th>
                            <td>
                                <input name="name" type="text" id="category_name" value="" class="regular-text" required>
                                <p class="description"><?php _e('The name is how it appears on your site.', 'zpos'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="category_slug"><?php _e('Slug', 'zpos'); ?></label>
                            </th>
                            <td>
                                <input name="slug" type="text" id="category_slug" value="" class="regular-text">
                                <p class="description"><?php _e('The "slug" is the URL-friendly version of the name.', 'zpos'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="category_parent"><?php _e('Parent Category', 'zpos'); ?></label>
                            </th>
                            <td>
                                <select name="parent_id" id="category_parent" class="postform">
                                    <option value=""><?php _e('None', 'zpos'); ?></option>
                                    <?php echo $category_options; ?>
                                </select>
                                <p class="description"><?php _e('Categories can have a hierarchy.', 'zpos'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="category_description"><?php _e('Description', 'zpos'); ?></label>
                            </th>
                            <td>
                                <textarea name="description" id="category_description" rows="5" cols="50" class="large-text"></textarea>
                                <p class="description"><?php _e('The description is optional.', 'zpos'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="category_image"><?php _e('Category Image', 'zpos'); ?></label>
                            </th>
                            <td>
                                <input name="image_url" type="url" id="category_image" value="" class="regular-text">
                                <button type="button" class="button" id="upload-image-button"><?php _e('Choose Image', 'zpos'); ?></button>
                                <p class="description"><?php _e('Optional category image URL.', 'zpos'); ?></p>
                                <div id="image-preview" style="margin-top: 10px; display: none;">
                                    <img src="" alt="" style="max-width: 150px; height: auto;">
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="category_status"><?php _e('Status', 'zpos'); ?></label>
                            </th>
                            <td>
                                <select name="status" id="category_status">
                                    <option value="active"><?php _e('Active', 'zpos'); ?></option>
                                    <option value="inactive"><?php _e('Inactive', 'zpos'); ?></option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="category_sort_order"><?php _e('Sort Order', 'zpos'); ?></label>
                            </th>
                            <td>
                                <input name="sort_order" type="number" id="category_sort_order" value="0" class="small-text" min="0">
                                <p class="description"><?php _e('Categories are sorted by this field then name.', 'zpos'); ?></p>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <?php submit_button($action === 'edit' ? __('Update Category', 'zpos') : __('Add Category', 'zpos'), 'primary', 'submit', false); ?>
                <a href="<?php echo admin_url('admin.php?page=zpos-categories'); ?>" class="button"><?php _e('Cancel', 'zpos'); ?></a>
            </form>
        </div>
    <?php endif; ?>
</div>

<!-- Delete Confirmation Modal -->
<div id="delete-category-modal" style="display: none;">
    <div class="zpos-modal-overlay">
        <div class="zpos-modal">
            <h2><?php _e('Delete Category', 'zpos'); ?></h2>
            <p><?php _e('Are you sure you want to delete this category?', 'zpos'); ?></p>
            <p><strong id="category-name-to-delete"></strong></p>
            <div class="zpos-modal-actions">
                <button type="button" class="button button-primary" id="confirm-delete"><?php _e('Delete', 'zpos'); ?></button>
                <button type="button" class="button" id="cancel-delete"><?php _e('Cancel', 'zpos'); ?></button>
            </div>
        </div>
    </div>
</div>

<style>
.zpos-modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
}

.zpos-modal {
    background: white;
    padding: 20px;
    border-radius: 4px;
    max-width: 400px;
    width: 90%;
}

.zpos-modal h2 {
    margin-top: 0;
}

.zpos-modal-actions {
    text-align: right;
    margin-top: 20px;
}

.zpos-modal-actions .button {
    margin-left: 10px;
}

.status-active {
    color: #006600;
    font-weight: bold;
}

.status-inactive {
    color: #999;
}

.required {
    color: #d63638;
}

.product-count {
    font-weight: bold;
}
</style>

<script>
jQuery(document).ready(function($) {
    const nonce = '<?php echo esc_js($nonce); ?>';
    
    // Auto-generate slug from name
    $('#category_name').on('input', function() {
        const name = $(this).val();
        const slug = name.toLowerCase()
            .replace(/[^a-z0-9\s-]/g, '')
            .replace(/\s+/g, '-')
            .replace(/-+/g, '-')
            .trim('-');
        $('#category_slug').val(slug);
    });

    // Image upload
    $('#upload-image-button').on('click', function(e) {
        e.preventDefault();
        
        if (typeof wp !== 'undefined' && wp.media) {
            const mediaUploader = wp.media({
                title: '<?php _e('Choose Category Image', 'zpos'); ?>',
                button: {
                    text: '<?php _e('Choose Image', 'zpos'); ?>'
                },
                multiple: false
            });

            mediaUploader.on('select', function() {
                const attachment = mediaUploader.state().get('selection').first().toJSON();
                $('#category_image').val(attachment.url);
                $('#image-preview img').attr('src', attachment.url);
                $('#image-preview').show();
            });

            mediaUploader.open();
        }
    });

    // Show image preview if URL is entered manually
    $('#category_image').on('input', function() {
        const url = $(this).val();
        if (url) {
            $('#image-preview img').attr('src', url);
            $('#image-preview').show();
        } else {
            $('#image-preview').hide();
        }
    });

    // Category form submission
    $('#category-form').on('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        formData.append('action', 'zpos_save_category');
        formData.append('nonce', nonce);

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            beforeSend: function() {
                $('#submit').prop('disabled', true).text('<?php _e('Saving...', 'zpos'); ?>');
            },
            success: function(response) {
                if (response.success) {
                    showMessage(response.data.message, 'success');
                    setTimeout(function() {
                        window.location.href = '<?php echo admin_url('admin.php?page=zpos-categories'); ?>';
                    }, 2000);
                } else {
                    showMessage(response.data, 'error');
                    $('#submit').prop('disabled', false).text('<?php echo ($action === 'edit' ? __('Update Category', 'zpos') : __('Add Category', 'zpos')); ?>');
                }
            },
            error: function() {
                showMessage('<?php _e('An error occurred. Please try again.', 'zpos'); ?>', 'error');
                $('#submit').prop('disabled', false).text('<?php echo ($action === 'edit' ? __('Update Category', 'zpos') : __('Add Category', 'zpos')); ?>');
            }
        });
    });

    // Delete category
    let categoryToDelete = null;
    
    $('.delete-category').on('click', function(e) {
        e.preventDefault();
        categoryToDelete = {
            id: $(this).data('category-id'),
            name: $(this).data('category-name')
        };
        $('#category-name-to-delete').text(categoryToDelete.name);
        $('#delete-category-modal').show();
    });

    $('#cancel-delete').on('click', function() {
        $('#delete-category-modal').hide();
        categoryToDelete = null;
    });

    $('#confirm-delete').on('click', function() {
        if (!categoryToDelete) return;

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'zpos_delete_category',
                category_id: categoryToDelete.id,
                nonce: nonce
            },
            beforeSend: function() {
                $('#confirm-delete').prop('disabled', true).text('<?php _e('Deleting...', 'zpos'); ?>');
            },
            success: function(response) {
                $('#delete-category-modal').hide();
                if (response.success) {
                    showMessage(response.data, 'success');
                    $('tr[data-category-id="' + categoryToDelete.id + '"]').fadeOut();
                } else {
                    showMessage(response.data, 'error');
                }
                categoryToDelete = null;
                $('#confirm-delete').prop('disabled', false).text('<?php _e('Delete', 'zpos'); ?>');
            },
            error: function() {
                $('#delete-category-modal').hide();
                showMessage('<?php _e('An error occurred. Please try again.', 'zpos'); ?>', 'error');
                categoryToDelete = null;
                $('#confirm-delete').prop('disabled', false).text('<?php _e('Delete', 'zpos'); ?>');
            }
        });
    });

    // Load product counts for each category
    $('.product-count').each(function() {
        const $this = $(this);
        const categoryId = $this.data('category-id');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'zpos_get_category_product_count',
                category_id: categoryId,
                nonce: nonce
            },
            success: function(response) {
                if (response.success) {
                    $this.text(response.data + ' products');
                } else {
                    $this.text('0 products');
                }
            },
            error: function() {
                $this.text('—');
            }
        });
    });

    // Load category data for edit form
    <?php if ($action === 'edit' && $category_id): ?>
    $.ajax({
        url: ajaxurl,
        type: 'POST',
        data: {
            action: 'zpos_get_category',
            category_id: <?php echo intval($category_id); ?>,
            nonce: nonce
        },
        success: function(response) {
            if (response.success) {
                const category = response.data;
                $('#category_name').val(category.name);
                $('#category_slug').val(category.slug);
                $('#category_parent').val(category.parent_id || '');
                $('#category_description').val(category.description);
                $('#category_image').val(category.image_url);
                $('#category_status').val(category.status);
                $('#category_sort_order').val(category.sort_order);
                
                if (category.image_url) {
                    $('#image-preview img').attr('src', category.image_url);
                    $('#image-preview').show();
                }
            }
        }
    });
    <?php endif; ?>

    // Show messages
    function showMessage(message, type) {
        const $messageDiv = type === 'success' ? $('#zpos-success-message') : $('#zpos-error-message');
        $messageDiv.find('p').text(message);
        $messageDiv.show();
        $('#zpos-messages').show();
        
        // Auto-hide after 5 seconds
        setTimeout(function() {
            $messageDiv.fadeOut();
        }, 5000);
    }

    // Bulk actions
    $('#doaction2').on('click', function(e) {
        e.preventDefault();
        const action = $(this).siblings('select').val();
        const selectedCategories = $('input[name="selected_categories[]"]:checked').map(function() {
            return $(this).val();
        }).get();

        if (action === '-1' || selectedCategories.length === 0) {
            alert('<?php _e('Please select an action and at least one category.', 'zpos'); ?>');
            return;
        }

        if (action === 'delete') {
            if (!confirm('<?php _e('Are you sure you want to delete the selected categories?', 'zpos'); ?>')) {
                return;
            }
        }

        // Perform bulk action
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'zpos_bulk_category_action',
                bulk_action: action,
                category_ids: selectedCategories,
                nonce: nonce
            },
            success: function(response) {
                if (response.success) {
                    showMessage(response.data, 'success');
                    if (action === 'delete') {
                        selectedCategories.forEach(function(id) {
                            $('tr[data-category-id="' + id + '"]').fadeOut();
                        });
                    } else {
                        // Reload page to show status changes
                        location.reload();
                    }
                } else {
                    showMessage(response.data, 'error');
                }
            },
            error: function() {
                showMessage('<?php _e('An error occurred. Please try again.', 'zpos'); ?>', 'error');
            }
        });
    });

    // Select all checkbox
    $('#cb-select-all-1').on('change', function() {
        $('input[name="selected_categories[]"]').prop('checked', $(this).prop('checked'));
    });
});
</script>
