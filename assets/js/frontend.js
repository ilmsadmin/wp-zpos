/*!
 * ZPOS Frontend JavaScript
 * Handles warranty check functionality and frontend interactions
 */

(function($) {
    'use strict';

    // Safety check for localized variables
    if (typeof zpos_frontend_vars === 'undefined') {
        console.error('ZPOS Frontend: zpos_frontend_vars is not defined.');
        return;
    }

    // Global ZPOS Frontend object
    window.ZPOSFrontend = {
        init: function() {
            this.bindEvents();
            this.initTemplates();
        },

        bindEvents: function() {
            // Warranty form submission
            $(document).on('submit', '#zpos-warranty-form', this.handleWarrantySubmit);
            
            // Input formatting
            $(document).on('input', '#warranty_phone', this.formatPhoneNumber);
            $(document).on('input', '#warranty_serial', this.formatSerialNumber);
            
            // Clear errors on input
            $(document).on('input', '.zpos-input', this.clearInputError);
        },

        initTemplates: function() {
            // Initialize Handlebars helpers if available
            if (typeof Handlebars !== 'undefined') {
                Handlebars.registerHelper('eq', function(a, b) {
                    return a === b;
                });
            }
        },

        handleWarrantySubmit: function(e) {
            e.preventDefault();
            
            const $form = $(this);
            const $button = $form.find('.zpos-btn');
            const $results = $('#zpos-warranty-results');
            
            // Get form data
            const phone = $('#warranty_phone').val().trim();
            const serial = $('#warranty_serial').val().trim();
            
            // Validate input
            if (!phone && !serial) {
                ZPOSFrontend.showError('invalid_input');
                ZPOSFrontend.highlightEmptyFields();
                return false;
            }
            
            // Clear previous errors
            ZPOSFrontend.clearErrors();
            
            // Show loading state
            ZPOSFrontend.setLoadingState(true, $button, $form);
            
            // Make AJAX request
            $.ajax({
                url: zpos_frontend_vars.ajax_url,
                type: 'POST',
                data: {
                    action: 'zpos_check_warranty_frontend',
                    nonce: zpos_frontend_vars.nonce,
                    phone: phone,
                    serial: serial
                },
                success: function(response) {
                    ZPOSFrontend.setLoadingState(false, $button, $form);
                    
                    if (response.success) {
                        ZPOSFrontend.displayResults(response.data, $results);
                    } else {
                        ZPOSFrontend.displayNoResults(response.data || 'no_results', $results);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('ZPOS Warranty Check Error:', error);
                    ZPOSFrontend.setLoadingState(false, $button, $form);
                    ZPOSFrontend.showError('error');
                }
            });
            
            return false;
        },

        formatPhoneNumber: function() {
            let value = $(this).val().replace(/\D/g, '');
            
            // Simple phone formatting (can be customized)
            if (value.length >= 10) {
                value = value.replace(/(\d{3})(\d{3})(\d{4})/, '$1-$2-$3');
            }
            
            $(this).val(value);
        },

        formatSerialNumber: function() {
            // Convert to uppercase for consistency
            const value = $(this).val().toUpperCase();
            $(this).val(value);
        },

        setLoadingState: function(loading, $button, $form) {
            if (loading) {
                $button.addClass('loading').prop('disabled', true);
                $form.addClass('loading');
            } else {
                $button.removeClass('loading').prop('disabled', false);
                $form.removeClass('loading');
            }
        },

        displayResults: function(data, $container) {
            const warranties = data.warranties || [];
            const count = data.count || 0;
            
            if (warranties.length === 0) {
                this.displayNoResults('no_results', $container);
                return;
            }
            
            // Build results HTML
            let html = '<div class="zpos-results-header">';
            html += '<h3><span class="dashicons dashicons-yes-alt"></span>';
            html += zpos_frontend_vars.messages.warranty_found || 'Warranty Information Found';
            html += '</h3>';
            html += '<p class="zpos-results-count">';
            
            if (count > 1) {
                html += 'Found ' + count + ' warranty records';
            } else {
                html += 'Found 1 warranty record';
            }
            
            html += '</p></div>';
            
            html += '<div class="zpos-warranties-list">';
            
            warranties.forEach(function(warranty) {
                html += ZPOSFrontend.buildWarrantyCard(warranty);
            });
            
            html += '</div>';
            
            html += '<div class="zpos-results-footer">';
            html += '<p class="zpos-disclaimer">';
            html += 'For warranty claims or technical support, please contact our customer service team with your warranty information.';
            html += '</p></div>';
            
            $container.html(html).slideDown(300);
            
            // Scroll to results
            $('html, body').animate({
                scrollTop: $container.offset().top - 50
            }, 500);
        },

        displayNoResults: function(messageKey, $container) {
            const message = zpos_frontend_vars.messages[messageKey] || zpos_frontend_vars.messages.no_results;
            
            let html = '<div class="zpos-no-results">';
            html += '<div class="zpos-no-results-icon">';
            html += '<span class="dashicons dashicons-search"></span>';
            html += '</div>';
            html += '<h3>No Warranty Found</h3>';
            html += '<p>' + message + '</p>';
            
            html += '<div class="zpos-suggestions">';
            html += '<h4>Please check:</h4>';
            html += '<ul>';
            html += '<li>The phone number or serial number is entered correctly</li>';
            html += '<li>The product was purchased from our store</li>';
            html += '<li>The warranty was properly registered at the time of purchase</li>';
            html += '</ul>';
            html += '</div>';
            
            html += '<div class="zpos-contact-support">';
            html += '<p>Still having trouble? Contact our customer support team for assistance.</p>';
            html += '<button type="button" class="zpos-btn zpos-btn-primary" onclick="zposContactSupport()">';
            html += '<span class="dashicons dashicons-email-alt"></span>';
            html += 'Contact Support';
            html += '</button>';
            html += '</div>';
            
            html += '</div>';
            
            $container.html(html).slideDown(300);
            
            // Scroll to results
            $('html, body').animate({
                scrollTop: $container.offset().top - 50
            }, 500);
        },

        buildWarrantyCard: function(warranty) {
            let html = '<div class="zpos-warranty-card">';
            
            // Header
            html += '<div class="zpos-warranty-header">';
            html += '<div class="zpos-product-info">';
            html += '<h4 class="zpos-product-name">' + warranty.product_name + '</h4>';
            
            if (warranty.sku) {
                html += '<span class="zpos-product-sku">SKU: ' + warranty.sku + '</span>';
            }
            
            html += '<span class="zpos-serial-number">SN: ' + warranty.serial_number + '</span>';
            html += '</div>';
            
            html += '<div class="zpos-warranty-status zpos-status-' + warranty.status_class + '">';
            html += '<span class="zpos-status-badge">' + warranty.status_text + '</span>';
            html += '</div>';
            html += '</div>';
            
            // Details
            html += '<div class="zpos-warranty-details">';
            html += '<div class="zpos-detail-row">';
            html += '<span class="zpos-detail-label">Warranty Package:</span>';
            html += '<span class="zpos-detail-value">' + warranty.package_name + '</span>';
            html += '</div>';
            
            html += '<div class="zpos-detail-row">';
            html += '<span class="zpos-detail-label">Start Date:</span>';
            html += '<span class="zpos-detail-value">' + warranty.start_date + '</span>';
            html += '</div>';
            
            html += '<div class="zpos-detail-row">';
            html += '<span class="zpos-detail-label">End Date:</span>';
            html += '<span class="zpos-detail-value">' + warranty.end_date + '</span>';
            html += '</div>';
            
            if (warranty.days_remaining > 0) {
                html += '<div class="zpos-detail-row zpos-days-remaining">';
                html += '<span class="zpos-detail-label">Days Remaining:</span>';
                html += '<span class="zpos-detail-value zpos-highlight">' + warranty.days_remaining + ' days</span>';
                html += '</div>';
            }
            
            html += '</div>';
            
            // Actions (for active warranties)
            if (warranty.status_class === 'active') {
                html += '<div class="zpos-warranty-actions">';
                html += '<button type="button" class="zpos-btn zpos-btn-secondary zpos-btn-small" onclick="zposContactSupport(\'' + warranty.id + '\')">';
                html += '<span class="dashicons dashicons-email-alt"></span>';
                html += 'Contact Support';
                html += '</button>';
                html += '</div>';
            }
            
            html += '</div>';
            
            return html;
        },

        showError: function(messageKey) {
            const message = zpos_frontend_vars.messages[messageKey] || 'An error occurred';
            
            // Remove existing error messages
            $('.zpos-error-message').remove();
            
            // Add error message
            const errorHtml = '<div class="zpos-error-message">' +
                '<span class="dashicons dashicons-warning"></span>' +
                message +
                '</div>';
            
            $('.zpos-form-actions').before(errorHtml);
            
            // Auto-remove after 5 seconds
            setTimeout(function() {
                $('.zpos-error-message').fadeOut(300, function() {
                    $(this).remove();
                });
            }, 5000);
        },

        clearErrors: function() {
            $('.zpos-error-message').remove();
            $('.zpos-input').removeClass('error');
        },

        clearInputError: function() {
            $(this).removeClass('error');
        },

        highlightEmptyFields: function() {
            const $phone = $('#warranty_phone');
            const $serial = $('#warranty_serial');
            
            if (!$phone.val().trim() && !$serial.val().trim()) {
                $phone.addClass('error');
                $serial.addClass('error');
            }
        }
    };

    // Global functions for template callbacks
    window.zposContactSupport = function(warrantyId) {
        // This can be customized to open a contact form, mailto link, or support ticket
        const subject = warrantyId ? 
            'Warranty Support Request - ID: ' + warrantyId :
            'Warranty Support Request';
        
        const body = warrantyId ?
            'Hello, I need assistance with my warranty (ID: ' + warrantyId + ').' :
            'Hello, I need assistance with warranty lookup.';
        
        // Try to use a mailto link (can be customized)
        const email = 'support@yourstore.com'; // This should be configurable
        const mailtoLink = 'mailto:' + email + '?subject=' + encodeURIComponent(subject) + '&body=' + encodeURIComponent(body);
        
        window.location.href = mailtoLink;
    };

    // Initialize when document is ready
    $(document).ready(function() {
        ZPOSFrontend.init();
    });

})(jQuery);
