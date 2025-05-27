/**
 * Setup Wizard JavaScript for ZPOS
 *
 * @package ZPOS
 * @since   1.0.0
 */

(function($) {
    'use strict';

    /**
     * ZPOS Setup Wizard object
     */
    var ZPOSSetupWizard = {
        
        /**
         * Current step
         */
        currentStep: 1,
        
        /**
         * Total steps
         */
        totalSteps: 4,
        
        /**
         * Form data
         */
        formData: {},
        
        /**
         * Initialize the wizard
         */
        init: function() {
            this.bindEvents();
            this.updateProgress();
            this.checkWooCommerce();
        },
        
        /**
         * Bind events
         */
        bindEvents: function() {
            var self = this;
            
            // Next button click
            $(document).on('click', '.zpos-btn-next', function(e) {
                e.preventDefault();
                self.nextStep();
            });
            
            // Previous button click
            $(document).on('click', '.zpos-btn-prev', function(e) {
                e.preventDefault();
                self.prevStep();
            });
            
            // Skip wizard
            $(document).on('click', '.zpos-skip-wizard', function(e) {
                e.preventDefault();
                self.skipWizard();
            });
            
            // Form input changes
            $(document).on('change', '.zpos-wizard-content input, .zpos-wizard-content select, .zpos-wizard-content textarea', function() {
                self.collectFormData();
            });
            
            // Sync option selection
            $(document).on('click', '.zpos-sync-option', function(e) {
                $('.zpos-sync-option').removeClass('selected');
                $(this).addClass('selected');
                
                var syncType = $(this).data('sync-type');
                $('#woo_sync_type').val(syncType);
                self.collectFormData();
            });
            
            // Complete setup button
            $(document).on('click', '.zpos-btn-complete', function(e) {
                e.preventDefault();
                self.completeSetup();
            });
        },
        
        /**
         * Move to next step
         */
        nextStep: function() {
            if (this.validateCurrentStep()) {
                if (this.currentStep < this.totalSteps) {
                    this.currentStep++;
                    this.showStep(this.currentStep);
                    this.updateProgress();
                    
                    // Special handling for step 4 (summary)
                    if (this.currentStep === 4) {
                        this.generateSummary();
                    }
                }
            }
        },
        
        /**
         * Move to previous step
         */
        prevStep: function() {
            if (this.currentStep > 1) {
                this.currentStep--;
                this.showStep(this.currentStep);
                this.updateProgress();
            }
        },
        
        /**
         * Show specific step
         */
        showStep: function(step) {
            $('.zpos-wizard-step').removeClass('active');
            $('.zpos-wizard-step[data-step="' + step + '"]').addClass('active');
            
            // Update navigation buttons
            this.updateNavigation();
            
            // Scroll to top
            $('.zpos-setup-wizard').animate({
                scrollTop: 0
            }, 300);
        },
        
        /**
         * Update progress bar and steps
         */
        updateProgress: function() {
            var progressPercent = ((this.currentStep - 1) / (this.totalSteps - 1)) * 100;
            $('.zpos-progress-fill').css('width', progressPercent + '%');
            
            // Update progress steps
            $('.zpos-progress-step').each(function(index) {
                var stepNum = index + 1;
                var $step = $(this);
                
                $step.removeClass('active completed');
                
                if (stepNum < this.currentStep) {
                    $step.addClass('completed');
                    $step.find('.zpos-step-number').html('<i class="dashicons dashicons-yes-alt"></i>');
                } else if (stepNum === this.currentStep) {
                    $step.addClass('active');
                    $step.find('.zpos-step-number').text(stepNum);
                } else {
                    $step.find('.zpos-step-number').text(stepNum);
                }
            }.bind(this));
        },
        
        /**
         * Update navigation buttons
         */
        updateNavigation: function() {
            var $prevBtn = $('.zpos-btn-prev');
            var $nextBtn = $('.zpos-btn-next');
            var $completeBtn = $('.zpos-btn-complete');
            
            // Previous button
            if (this.currentStep === 1) {
                $prevBtn.hide();
            } else {
                $prevBtn.show();
            }
            
            // Next/Complete button
            if (this.currentStep === this.totalSteps) {
                $nextBtn.hide();
                $completeBtn.show();
            } else {
                $nextBtn.show();
                $completeBtn.hide();
            }
        },
        
        /**
         * Validate current step
         */
        validateCurrentStep: function() {
            var $currentStep = $('.zpos-wizard-step[data-step="' + this.currentStep + '"]');
            var isValid = true;
            var errors = [];
            
            // Remove existing errors
            $currentStep.find('.zpos-field-error').remove();
            $currentStep.find('.zpos-form-input, .zpos-form-select').removeClass('error');
            
            switch (this.currentStep) {
                case 1:
                    // Welcome step - no validation needed
                    break;
                    
                case 2:
                    // WooCommerce sync validation
                    var syncType = $('#woo_sync_type').val();
                    if (!syncType) {
                        errors.push('Please select a WooCommerce sync option.');
                        isValid = false;
                    }
                    break;
                    
                case 3:
                    // Basic configuration validation
                    var requiredFields = ['store_name', 'store_currency', 'store_timezone'];
                    
                    requiredFields.forEach(function(field) {
                        var $field = $('#' + field);
                        var value = $field.val().trim();
                        
                        if (!value) {
                            $field.addClass('error');
                            $field.after('<div class="zpos-field-error">This field is required.</div>');
                            isValid = false;
                        }
                    });
                    
                    // Validate email if provided
                    var email = $('#store_email').val().trim();
                    if (email && !this.isValidEmail(email)) {
                        $('#store_email').addClass('error');
                        $('#store_email').after('<div class="zpos-field-error">Please enter a valid email address.</div>');
                        isValid = false;
                    }
                    break;
                    
                case 4:
                    // Confirmation step - no validation needed
                    break;
            }
            
            if (!isValid) {
                this.showError(errors.length > 0 ? errors[0] : 'Please fix the errors before continuing.');
            }
            
            return isValid;
        },
        
        /**
         * Collect form data from current step
         */
        collectFormData: function() {
            var $form = $('.zpos-wizard-content');
            var data = {};
            
            $form.find('input, select, textarea').each(function() {
                var $field = $(this);
                var name = $field.attr('name');
                var type = $field.attr('type');
                
                if (name) {
                    if (type === 'checkbox') {
                        data[name] = $field.is(':checked');
                    } else if (type === 'radio') {
                        if ($field.is(':checked')) {
                            data[name] = $field.val();
                        }
                    } else {
                        data[name] = $field.val();
                    }
                }
            });
            
            this.formData = $.extend(this.formData, data);
        },
        
        /**
         * Generate summary for step 4
         */
        generateSummary: function() {
            this.collectFormData();
            
            var $summary = $('.zpos-config-summary');
            var summaryHtml = '';
            
            var summaryItems = [
                { label: 'Store Name', value: this.formData.store_name || 'Not set' },
                { label: 'Store Email', value: this.formData.store_email || 'Not set' },
                { label: 'Currency', value: this.formData.store_currency || 'USD' },
                { label: 'Timezone', value: this.formData.store_timezone || 'UTC' },
                { label: 'WooCommerce Sync', value: this.getSyncTypeLabel(this.formData.woo_sync_type) },
                { label: 'Low Stock Threshold', value: this.formData.low_stock_threshold || '5' },
                { label: 'Enable Inventory Tracking', value: this.formData.enable_inventory ? 'Yes' : 'No' },
                { label: 'Enable Warranty System', value: this.formData.enable_warranty ? 'Yes' : 'No' }
            ];
            
            summaryItems.forEach(function(item) {
                summaryHtml += '<div class="zpos-summary-item">';
                summaryHtml += '<span class="zpos-summary-label">' + item.label + '</span>';
                summaryHtml += '<span class="zpos-summary-value">' + item.value + '</span>';
                summaryHtml += '</div>';
            });
            
            $summary.html(summaryHtml);
        },
        
        /**
         * Get sync type label
         */
        getSyncTypeLabel: function(type) {
            switch (type) {
                case 'full_sync':
                    return 'Full Synchronization';
                case 'selective_sync':
                    return 'Selective Synchronization';
                case 'no_sync':
                    return 'No Synchronization';
                default:
                    return 'Not configured';
            }
        },
        
        /**
         * Complete setup
         */
        completeSetup: function() {
            this.collectFormData();
            
            var $completeBtn = $('.zpos-btn-complete');
            $completeBtn.addClass('zpos-loading').prop('disabled', true);
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'zpos_setup_wizard',
                    step: 'complete',
                    nonce: zpos_wizard_vars.nonce,
                    form_data: this.formData
                },
                success: function(response) {
                    if (response.success) {
                        this.showSuccess('Setup completed successfully! Redirecting...');
                        setTimeout(function() {
                            window.location.href = response.data.redirect_url || admin_url('admin.php?page=zpos');
                        }, 2000);
                    } else {
                        this.showError(response.data.message || 'Setup failed. Please try again.');
                        $completeBtn.removeClass('zpos-loading').prop('disabled', false);
                    }
                }.bind(this),
                error: function() {
                    this.showError('An error occurred. Please try again.');
                    $completeBtn.removeClass('zpos-loading').prop('disabled', false);
                }.bind(this)
            });
        },
        
        /**
         * Skip wizard
         */
        skipWizard: function() {
            if (confirm('Are you sure you want to skip the setup wizard? You can run it again later from the settings page.')) {
                window.location.href = admin_url('admin.php?page=zpos');
            }
        },
        
        /**
         * Check WooCommerce status
         */
        checkWooCommerce: function() {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'zpos_setup_wizard',
                    step: 'check_woocommerce',
                    nonce: zpos_wizard_vars.nonce
                },
                success: function(response) {
                    if (response.success) {
                        var wooStatus = response.data.woocommerce_status;
                        this.updateWooCommerceStatus(wooStatus);
                    }
                }.bind(this)
            });
        },
        
        /**
         * Update WooCommerce status display
         */
        updateWooCommerceStatus: function(status) {
            var $statusDiv = $('.woocommerce-status');
            var statusHtml = '';
            
            if (status.installed && status.active) {
                statusHtml = '<div class="zpos-alert zpos-alert-success">';
                statusHtml += '<strong>WooCommerce Detected!</strong> Version ' + status.version + ' is installed and active.';
                statusHtml += '</div>';
                
                // Enable sync options
                $('.zpos-sync-option[data-sync-type="no_sync"]').removeClass('selected');
                $('.zpos-sync-option[data-sync-type="full_sync"]').addClass('selected');
                $('#woo_sync_type').val('full_sync');
                
            } else if (status.installed && !status.active) {
                statusHtml = '<div class="zpos-alert zpos-alert-warning">';
                statusHtml += '<strong>WooCommerce Installed but Inactive!</strong> Please activate WooCommerce to enable synchronization.';
                statusHtml += '</div>';
                
            } else {
                statusHtml = '<div class="zpos-alert zpos-alert-info">';
                statusHtml += '<strong>WooCommerce Not Found!</strong> ZPOS can work independently or you can install WooCommerce later.';
                statusHtml += '</div>';
                
                // Select no sync option
                $('.zpos-sync-option').removeClass('selected');
                $('.zpos-sync-option[data-sync-type="no_sync"]').addClass('selected');
                $('#woo_sync_type').val('no_sync');
            }
            
            $statusDiv.html(statusHtml);
        },
        
        /**
         * Show error message
         */
        showError: function(message) {
            this.showNotice(message, 'error');
        },
        
        /**
         * Show success message
         */
        showSuccess: function(message) {
            this.showNotice(message, 'success');
        },
        
        /**
         * Show notice
         */
        showNotice: function(message, type) {
            var $notice = $('<div class="zpos-alert zpos-alert-' + type + '">' + message + '</div>');
            
            // Remove existing notices
            $('.zpos-wizard-content .zpos-alert').remove();
            
            // Add new notice
            $('.zpos-wizard-content .zpos-wizard-step.active').prepend($notice);
            
            // Auto remove after 5 seconds for success messages
            if (type === 'success') {
                setTimeout(function() {
                    $notice.fadeOut();
                }, 5000);
            }
            
            // Scroll to notice
            $notice[0].scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        },
        
        /**
         * Validate email address
         */
        isValidEmail: function(email) {
            var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email);
        }
    };
    
    /**
     * Initialize when document is ready
     */
    $(document).ready(function() {
        if ($('.zpos-setup-wizard').length) {
            ZPOSSetupWizard.init();
        }
    });
    
    /**
     * Handle browser back/forward buttons
     */
    $(window).on('beforeunload', function(e) {
        if ($('.zpos-setup-wizard').length && ZPOSSetupWizard.currentStep > 1) {
            var message = 'You have unsaved changes in the setup wizard. Are you sure you want to leave?';
            e.returnValue = message;
            return message;
        }
    });

})(jQuery);
