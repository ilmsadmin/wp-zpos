/**
 * ZPOS Admin Interface JavaScript
 * Handles dashboard interactions, charts, AJAX updates, and filters
 */

(function($) {
    'use strict';

    // Safety check for localized variables
    if (typeof zpos_admin === 'undefined') {
        console.error('ZPOS: zpos_admin is not defined. Admin scripts may not work properly.');
        return;
    }

    // Global ZPOS Admin object
    window.ZPOSAdmin = {
        charts: {},
        filters: {
            dateRange: 'today',
            startDate: null,
            endDate: null
        },
        
        init: function() {
            this.initDashboard();
            this.initFilters();
            this.initCharts();
            this.initActivityFeed();
            this.bindEvents();
        },

        /**
         * Initialize Dashboard Components
         */
        initDashboard: function() {
            this.loadDashboardStats();
            this.setupRefreshButton();
            this.initQuickActions();
        },

        /**
         * Initialize Date Filters
         */
        initFilters: function() {
            const $dateFilter = $('#zpos-date-filter');
            const $customInputs = $('.zpos-date-inputs');
            
            $dateFilter.on('change', function() {
                const value = $(this).val();
                ZPOSAdmin.filters.dateRange = value;
                
                if (value === 'custom') {
                    $customInputs.addClass('active');
                } else {
                    $customInputs.removeClass('active');
                    ZPOSAdmin.updateDashboard();
                }
            });
            
            // Handle custom date inputs
            $('.zpos-date-inputs input').on('change', function() {
                ZPOSAdmin.filters.startDate = $('#zpos-start-date').val();
                ZPOSAdmin.filters.endDate = $('#zpos-end-date').val();
                
                if (ZPOSAdmin.filters.startDate && ZPOSAdmin.filters.endDate) {
                    ZPOSAdmin.updateDashboard();
                }
            });
        },

        /**
         * Initialize Chart.js Charts
         */
        initCharts: function() {
            if (typeof Chart === 'undefined') {
                console.warn('Chart.js not loaded. Charts will not be displayed.');
                return;
            }

            // Set Chart.js defaults
            Chart.defaults.font.family = '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif';
            Chart.defaults.color = '#646970';
            
            this.initRevenueChart();
            this.initTopProductsChart();
            this.setupChartControls();
        },

        /**
         * Initialize Revenue Trend Chart
         */
        initRevenueChart: function() {
            const ctx = document.getElementById('zpos-revenue-chart');
            if (!ctx) return;

            const chartData = window.zposChartData?.revenue || {
                labels: [],
                datasets: [{
                    label: 'Revenue',
                    data: [],
                    borderColor: '#0073aa',
                    backgroundColor: 'rgba(0, 115, 170, 0.1)',
                    tension: 0.4
                }]
            };

            this.charts.revenue = new Chart(ctx, {
                type: 'line',
                data: chartData,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        intersect: false,
                        mode: 'index'
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            titleColor: '#fff',
                            bodyColor: '#fff',
                            borderColor: '#0073aa',
                            borderWidth: 1,
                            cornerRadius: 6,
                            callbacks: {
                                label: function(context) {
                                    return 'Revenue: $' + context.parsed.y.toLocaleString();
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                display: false
                            },
                            border: {
                                display: false
                            }
                        },
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            },
                            border: {
                                display: false
                            },
                            ticks: {
                                callback: function(value) {
                                    return '$' + value.toLocaleString();
                                }
                            }
                        }
                    }
                }
            });
        },

        /**
         * Initialize Top Products Doughnut Chart
         */
        initTopProductsChart: function() {
            const ctx = document.getElementById('zpos-products-chart');
            if (!ctx) return;

            const chartData = window.zposChartData?.topProducts || {
                labels: ['No Data'],
                datasets: [{
                    data: [1],
                    backgroundColor: ['#e2e4e7']
                }]
            };

            this.charts.topProducts = new Chart(ctx, {
                type: 'doughnut',
                data: chartData,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '60%',
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 15,
                                usePointStyle: true,
                                font: {
                                    size: 12
                                }
                            }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            titleColor: '#fff',
                            bodyColor: '#fff',
                            borderColor: '#0073aa',
                            borderWidth: 1,
                            cornerRadius: 6,
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.parsed || 0;
                                    return label + ': ' + value + ' sales';
                                }
                            }
                        }
                    }
                }
            });
        },

        /**
         * Setup Chart Control Buttons
         */
        setupChartControls: function() {
            $('.zpos-chart-toggle').on('click', function() {
                const $this = $(this);
                const chartType = $this.data('chart');
                const newType = $this.data('type');
                
                $this.addClass('active').siblings().removeClass('active');
                
                if (chartType === 'revenue' && ZPOSAdmin.charts.revenue) {
                    ZPOSAdmin.charts.revenue.config.type = newType;
                    ZPOSAdmin.charts.revenue.update();
                }
            });
        },

        /**
         * Initialize Activity Feed
         */
        initActivityFeed: function() {
            this.loadRecentActivity();
            
            // Auto-refresh activity every 5 minutes
            setInterval(() => {
                this.loadRecentActivity();
            }, 300000);
        },

        /**
         * Load Recent Activity via AJAX
         */
        loadRecentActivity: function() {
            const $activityList = $('.zpos-activity-list');
            
            $activityList.html('<div class="zpos-loading"><div class="zpos-spinner"></div>Loading recent activity...</div>');
                  $.ajax({
            url: zpos_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'zpos_get_recent_activity',
                nonce: zpos_admin.nonce,
                limit: 10
            },
                success: function(response) {
                    if (response.success) {
                        ZPOSAdmin.renderActivity(response.data);
                    } else {
                        $activityList.html('<div class="zpos-text-muted zpos-text-center">No recent activity found.</div>');
                    }
                },
                error: function() {
                    $activityList.html('<div class="zpos-text-error zpos-text-center">Error loading activity.</div>');
                }
            });
        },

        /**
         * Render Activity Items
         */
        renderActivity: function(activities) {
            const $activityList = $('.zpos-activity-list');
            let html = '';
            
            if (!activities || activities.length === 0) {
                html = '<div class="zpos-text-muted zpos-text-center">No recent activity found.</div>';
            } else {
                activities.forEach(activity => {
                    html += `
                        <div class="zpos-activity-item zpos-fade-in">
                            <div class="zpos-activity-icon ${activity.type}">
                                <span class="dashicons ${this.getActivityIcon(activity.type)}"></span>
                            </div>
                            <div class="zpos-activity-content">
                                <p class="zpos-activity-description">${activity.description}</p>
                                <span class="zpos-activity-time">${activity.time_ago}</span>
                            </div>
                            ${activity.amount ? `<div class="zpos-activity-amount">$${activity.amount}</div>` : ''}
                        </div>
                    `;
                });
            }
            
            $activityList.html(html);
        },

        /**
         * Get Icon for Activity Type
         */
        getActivityIcon: function(type) {
            const icons = {
                order: 'dashicons-cart',
                customer: 'dashicons-admin-users',
                product: 'dashicons-products',
                inventory: 'dashicons-admin-settings'
            };
            return icons[type] || 'dashicons-admin-generic';
        },        /**
         * Load Dashboard Statistics
         */
        loadDashboardStats: function() {
            $.ajax({
            url: zpos_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'zpos_get_dashboard_stats',
                nonce: zpos_admin.nonce,
                date_range: this.filters.dateRange,
                start_date: this.filters.startDate,
                end_date: this.filters.endDate
            },
                success: function(response) {
                    if (response.success) {
                        ZPOSAdmin.updateStatsCards(response.data);
                    }
                },
                error: function() {
                    console.error('Error loading dashboard statistics');
                }
            });
        },

        /**
         * Update Statistics Cards
         */
        updateStatsCards: function(stats) {
            // Update each stat card with animation
            Object.keys(stats).forEach(key => {
                const $card = $(`.zpos-stat-card[data-stat="${key}"]`);
                const $value = $card.find('.zpos-card-value');
                const $change = $card.find('.zpos-card-change');
                
                if ($value.length) {
                    // Animate value change
                    const currentValue = parseInt($value.text().replace(/[^0-9]/g, '')) || 0;
                    const newValue = stats[key].value || 0;
                    
                    if (currentValue !== newValue) {
                        this.animateValue($value[0], currentValue, newValue, 1000, stats[key].format);
                    }
                }
                
                if ($change.length && stats[key].change !== undefined) {
                    const change = stats[key].change;
                    const changeClass = change > 0 ? 'positive' : change < 0 ? 'negative' : 'neutral';
                    const changeIcon = change > 0 ? 'trending-up' : change < 0 ? 'trending-down' : 'minus';
                    
                    $change
                        .removeClass('positive negative neutral')
                        .addClass(changeClass)
                        .html(`<span class="dashicons dashicons-${changeIcon}"></span>${Math.abs(change)}%`);
                }
            });
        },

        /**
         * Animate Number Values
         */
        animateValue: function(element, start, end, duration, format) {
            const startTime = performance.now();
            
            const updateValue = (currentTime) => {
                const elapsed = currentTime - startTime;
                const progress = Math.min(elapsed / duration, 1);
                const current = Math.floor(start + (end - start) * progress);
                
                let displayValue = current.toLocaleString();
                if (format === 'currency') {
                    displayValue = '$' + displayValue;
                }
                
                element.textContent = displayValue;
                
                if (progress < 1) {
                    requestAnimationFrame(updateValue);
                }
            };
            
            requestAnimationFrame(updateValue);
        },

        /**
         * Update Dashboard with New Filters
         */
        updateDashboard: function() {
            this.loadDashboardStats();
            this.updateChartData();
            this.loadRecentActivity();
        },        /**
         * Update Chart Data via AJAX
         */
        updateChartData: function() {
            $.ajax({
            url: zpos_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'zpos_get_chart_data',
                nonce: zpos_admin.nonce,
                date_range: this.filters.dateRange,
                start_date: this.filters.startDate,
                end_date: this.filters.endDate
            },
                success: function(response) {
                    if (response.success) {
                        ZPOSAdmin.updateCharts(response.data);
                    }
                },
                error: function() {
                    console.error('Error loading chart data');
                }
            });
        },

        /**
         * Update Chart Instances
         */
        updateCharts: function(data) {
            // Update Revenue Chart
            if (this.charts.revenue && data.revenue) {
                this.charts.revenue.data = data.revenue;
                this.charts.revenue.update('active');
            }
            
            // Update Top Products Chart
            if (this.charts.topProducts && data.topProducts) {
                this.charts.topProducts.data = data.topProducts;
                this.charts.topProducts.update('active');
            }
        },

        /**
         * Setup Refresh Button
         */
        setupRefreshButton: function() {
            $('#zpos-refresh-dashboard').on('click', function(e) {
                e.preventDefault();
                
                const $btn = $(this);
                const originalText = $btn.text();
                
                $btn.prop('disabled', true).text('Refreshing...');
                
                ZPOSAdmin.updateDashboard();
                
                setTimeout(() => {
                    $btn.prop('disabled', false).text(originalText);
                }, 2000);
            });
        },

        /**
         * Initialize Quick Actions
         */
        initQuickActions: function() {
            $('.zpos-quick-action').on('click', function(e) {
                const action = $(this).data('action');
                
                if (action === 'new-order') {
                    // Open POS interface
                    window.open(zposAdmin.posUrl, '_blank');
                } else if (action === 'add-product') {
                    // Navigate to add product page
                    window.location.href = zposAdmin.addProductUrl;
                }
            });
        },

        /**
         * Bind Global Events
         */
        bindEvents: function() {
            // Handle window resize for charts
            $(window).on('resize', () => {
                Object.values(this.charts).forEach(chart => {
                    if (chart && typeof chart.resize === 'function') {
                        chart.resize();
                    }
                });
            });
            
            // Handle tab visibility change to pause/resume updates
            document.addEventListener('visibilitychange', () => {
                if (!document.hidden) {
                    // Page became visible, refresh data
                    this.updateDashboard();
                }
            });
        },

        /**
         * Utility Functions
         */
        utils: {
            /**
             * Format currency values
             */
            formatCurrency: function(amount) {
                return '$' + parseFloat(amount).toLocaleString('en-US', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            },

            /**
             * Format numbers with commas
             */
            formatNumber: function(num) {
                return parseInt(num).toLocaleString();
            },

            /**
             * Get relative time string
             */
            timeAgo: function(date) {
                const now = new Date();
                const diff = now - new Date(date);
                const seconds = Math.floor(diff / 1000);
                
                if (seconds < 60) return 'Just now';
                if (seconds < 3600) return Math.floor(seconds / 60) + ' minutes ago';
                if (seconds < 86400) return Math.floor(seconds / 3600) + ' hours ago';
                return Math.floor(seconds / 86400) + ' days ago';
            },

            /**
             * Show success notification
             */
            showSuccess: function(message) {
                this.showNotice(message, 'success');
            },

            /**
             * Show error notification
             */
            showError: function(message) {
                this.showNotice(message, 'error');
            },

            /**
             * Show admin notice
             */
            showNotice: function(message, type = 'info') {
                const $notice = $(`
                    <div class="notice notice-${type} is-dismissible zpos-fade-in">
                        <p>${message}</p>
                        <button type="button" class="notice-dismiss">
                            <span class="screen-reader-text">Dismiss this notice.</span>
                        </button>
                    </div>
                `);
                
                $('.zpos-admin-wrap').prepend($notice);
                
                // Auto-dismiss after 5 seconds
                setTimeout(() => {
                    $notice.fadeOut(() => $notice.remove());
                }, 5000);
                
                // Handle manual dismiss
                $notice.find('.notice-dismiss').on('click', function() {
                    $notice.fadeOut(() => $notice.remove());
                });
            }
        }
    };    // Initialize when document is ready
    $(document).ready(function() {
    // Only initialize on ZPOS admin pages
        if ($('.zpos-admin-wrap').length) {
            ZPOSAdmin.init();
        }
        
        // Initialize POS system on POS page
        if ($('.zpos-pos-page').length) {
            ZPOSAdmin.initPOS();
        }
    });

})(jQuery);

// POS System JavaScript
(function($) {
    'use strict';

    // Extend ZPOSAdmin with POS functionality
    window.ZPOSAdmin = window.ZPOSAdmin || {};
    
    $.extend(window.ZPOSAdmin, {
        pos: {
            cart: [],
            customer: null,
            discount: { type: 'none', value: 0 },
            currentPage: 1,
            searchTimeout: null
        },

        initPOS: function() {
            this.initProductSearch();
            this.initCustomerSearch();
            this.initCart();
            this.initModals();
            this.bindPOSEvents();
            this.loadInitialProducts();
        },

        initProductSearch: function() {
            const $searchInput = $('#product-search');
            const $categoryFilter = $('#category-filter');
            
            // Debounced search
            $searchInput.on('input', function() {
                clearTimeout(ZPOSAdmin.pos.searchTimeout);
                ZPOSAdmin.pos.searchTimeout = setTimeout(() => {
                    ZPOSAdmin.searchProducts();
                }, 300);
            });
            
            $categoryFilter.on('change', function() {
                ZPOSAdmin.searchProducts();
            });
            
            // Load categories
            this.loadCategories();
        },

        initCustomerSearch: function() {
            const $customerSearch = $('#customer-search');
            
            $customerSearch.on('input', function() {
                const query = $(this).val();
                if (query.length >= 2) {
                    ZPOSAdmin.searchCustomers(query);
                } else {
                    $('#customer-results').hide();
                }
            });
        },

        initCart: function() {
            this.updateCartDisplay();
        },

        initModals: function() {
            // Product details modal
            $(document).on('click', '.product-card', function() {
                const productId = $(this).data('product-id');
                ZPOSAdmin.showProductDetails(productId);
            });
            
            // Close modals
            $(document).on('click', '.modal-close, .modal-backdrop', function() {
                $('.modal').removeClass('active');
            });
        },

        bindPOSEvents: function() {
            // Add to cart
            $(document).on('click', '.add-to-cart-btn', function(e) {
                e.stopPropagation();
                const productId = $(this).closest('.product-card').data('product-id');
                ZPOSAdmin.addToCart(productId);
            });
            
            // Cart quantity controls
            $(document).on('click', '.qty-increase', function() {
                const index = $(this).data('index');
                ZPOSAdmin.updateCartQuantity(index, 1);
            });
            
            $(document).on('click', '.qty-decrease', function() {
                const index = $(this).data('index');
                ZPOSAdmin.updateCartQuantity(index, -1);
            });
            
            $(document).on('click', '.remove-item', function() {
                const index = $(this).data('index');
                ZPOSAdmin.removeFromCart(index);
            });
            
            // Discount controls
            $('#discount-type').on('change', function() {
                ZPOSAdmin.pos.discount.type = $(this).val();
                ZPOSAdmin.calculateCart();
            });
            
            $('#discount-value').on('input', function() {
                ZPOSAdmin.pos.discount.value = parseFloat($(this).val()) || 0;
                ZPOSAdmin.calculateCart();
            });
            
            // Checkout
            $('#checkout-btn').on('click', function() {
                ZPOSAdmin.processCheckout();
            });
            
            // Hold order
            $('#hold-order-btn').on('click', function() {
                ZPOSAdmin.holdOrder();
            });
            
            // Recall orders
            $('#recall-orders-btn').on('click', function() {
                ZPOSAdmin.showHeldOrders();
            });
        },

        loadInitialProducts: function() {
            this.searchProducts();
        },

        loadCategories: function() {
            $.ajax({
                url: zpos_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'zpos_get_categories',
                    nonce: zpos_admin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        const $select = $('#category-filter');
                        $select.empty().append('<option value="">' + zpos_admin.text.all_categories + '</option>');
                        
                        response.data.forEach(function(category) {
                            $select.append('<option value="' + category.id + '">' + category.name + '</option>');
                        });
                    }
                }
            });
        },

        searchProducts: function() {
            const search = $('#product-search').val();
            const categoryId = $('#category-filter').val();
            
            $.ajax({
                url: zpos_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'zpos_search_products',
                    search: search,
                    category_id: categoryId,
                    page: ZPOSAdmin.pos.currentPage,
                    nonce: zpos_admin.nonce
                },
                beforeSend: function() {
                    $('#products-grid').html('<div class="loading-products"><p>' + zpos_admin.text.loading + '</p></div>');
                },
                success: function(response) {
                    if (response.success) {
                        ZPOSAdmin.displayProducts(response.data.products);
                        ZPOSAdmin.updatePagination(response.data);
                    } else {
                        $('#products-grid').html('<div class="no-products"><p>' + zpos_admin.text.no_products + '</p></div>');
                    }
                }
            });
        },

        displayProducts: function(products) {
            const $grid = $('#products-grid');
            let html = '';
            
            products.forEach(function(product) {
                const stockClass = product.stock_quantity <= 0 ? 'out-of-stock' : '';
                const imageUrl = product.image_url || 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"%3E%3Crect width="100" height="100" fill="%23f0f0f0"/%3E%3Ctext x="50" y="50" text-anchor="middle" dy=".3em" fill="%23999"%3ENo Image%3C/text%3E%3C/svg%3E';
                
                html += `
                    <div class="product-card ${stockClass}" data-product-id="${product.id}">
                        <div class="product-image">
                            <img src="${imageUrl}" alt="${product.name}">
                            ${product.stock_quantity <= 0 ? '<div class="out-of-stock-overlay">Out of Stock</div>' : ''}
                        </div>
                        <div class="product-info">
                            <h4 class="product-name">${product.name}</h4>
                            <p class="product-sku">${product.sku}</p>
                            <p class="product-price">$${product.price}</p>
                            <p class="product-stock">Stock: ${product.stock_quantity}</p>
                        </div>
                        ${product.stock_quantity > 0 ? `<button class="add-to-cart-btn" data-product-id="${product.id}">Add to Cart</button>` : ''}
                    </div>
                `;
            });
            
            $grid.html(html);
        },

        updatePagination: function(data) {
            const $pagination = $('.products-pagination');
            
            // Clear existing pagination
            $pagination.empty();
            
            if (!data || !data.total_pages || data.total_pages <= 1) {
                return;
            }
            
            let paginationHtml = `
                <div class="pagination-info">
                    <span class="displaying-num">${data.total || 0} ${zpos_admin.text.items || 'items'}</span>
                </div>
                <div class="pagination-links">
            `;
            
            // Previous page button
            if (ZPOSAdmin.pos.currentPage > 1) {
                paginationHtml += `<button class="pagination-btn prev-page" data-page="${ZPOSAdmin.pos.currentPage - 1}">&lsaquo; ${zpos_admin.text.previous || 'Previous'}</button>`;
            }
            
            // Page numbers (show max 5 pages around current)
            const startPage = Math.max(1, ZPOSAdmin.pos.currentPage - 2);
            const endPage = Math.min(data.total_pages, ZPOSAdmin.pos.currentPage + 2);
            
            if (startPage > 1) {
                paginationHtml += `<button class="pagination-btn page-number" data-page="1">1</button>`;
                if (startPage > 2) {
                    paginationHtml += `<span class="pagination-dots">...</span>`;
                }
            }
            
            for (let i = startPage; i <= endPage; i++) {
                const activeClass = i === ZPOSAdmin.pos.currentPage ? 'active' : '';
                paginationHtml += `<button class="pagination-btn page-number ${activeClass}" data-page="${i}">${i}</button>`;
            }
            
            if (endPage < data.total_pages) {
                if (endPage < data.total_pages - 1) {
                    paginationHtml += `<span class="pagination-dots">...</span>`;
                }
                paginationHtml += `<button class="pagination-btn page-number" data-page="${data.total_pages}">${data.total_pages}</button>`;
            }
            
            // Next page button
            if (ZPOSAdmin.pos.currentPage < data.total_pages) {
                paginationHtml += `<button class="pagination-btn next-page" data-page="${ZPOSAdmin.pos.currentPage + 1}">${zpos_admin.text.next || 'Next'} &rsaquo;</button>`;
            }
            
            paginationHtml += '</div>';
            
            $pagination.html(paginationHtml);
            
            // Bind pagination click events
            $pagination.find('.pagination-btn').on('click', function(e) {
                e.preventDefault();
                const page = parseInt($(this).data('page'));
                if (page && page !== ZPOSAdmin.pos.currentPage) {
                    ZPOSAdmin.pos.currentPage = page;
                    ZPOSAdmin.searchProducts();
                }
            });
        },

        /**
         * Search customers via AJAX
         * @param {string} query - The search query
         */
        searchCustomers: function(query) {
            if (!query || query.length < 2) {
                $('#customer-results').hide();
                return;
            }
            
            $.ajax({
                url: zpos_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'zpos_search_customers',
                    search: query, // Backend expects 'search' parameter, not 'query'
                    limit: 10,
                    nonce: zpos_admin.nonce
                },
                success: function(response) {
                    if (response.success && response.data) {
                        ZPOSAdmin.displayCustomerResults(response.data);
                    } else {
                        $('#customer-results').hide();
                    }
                },
                error: function() {
                    $('#customer-results').hide();
                }
            });
        },

        /**
         * Display customer search results
         * @param {Array} customers - Array of customer objects
         */
        displayCustomerResults: function(customers) {
            const $results = $('#customer-results');
            
            if (!customers || customers.length === 0) {
                $results.hide();
                return;
            }
            
            let html = '<ul class="customer-list">';
            customers.forEach(function(customer) {
                html += `
                    <li class="customer-item" data-customer-id="${customer.id}">
                        <div class="customer-info">
                            <strong>${customer.name}</strong>
                            <span class="customer-email">${customer.email}</span>
                            ${customer.phone ? `<span class="customer-phone">${customer.phone}</span>` : ''}
                        </div>
                    </li>
                `;
            });
            html += '</ul>';
            
            $results.html(html).show();
            
            // Bind customer selection events
            $results.find('.customer-item').on('click', function() {
                const customerId = $(this).data('customer-id');
                const customerName = $(this).find('strong').text();
                
                ZPOSAdmin.selectCustomer(customerId, customerName);
                $results.hide();
                $('#customer-search').val(customerName);
            });
        },

        /**
         * Select a customer for the current transaction
         * @param {number} customerId - Customer ID
         * @param {string} customerName - Customer name
         */
        selectCustomer: function(customerId, customerName) {
            ZPOSAdmin.pos.selectedCustomer = {
                id: customerId,
                name: customerName
            };
            
            // Update UI to show selected customer
            $('#selected-customer').text(customerName).show();
            $('#customer-search').val('');
        },

        addToCart: function(productId) {
            $.ajax({
                url: zpos_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'zpos_get_product_details',
                    product_id: productId,
                    nonce: zpos_admin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        const product = response.data;
                        
                        // Check if product already in cart
                        const existingIndex = ZPOSAdmin.pos.cart.findIndex(item => item.id === product.id);
                        
                        if (existingIndex > -1) {
                            ZPOSAdmin.pos.cart[existingIndex].quantity++;
                        } else {
                            ZPOSAdmin.pos.cart.push({
                                id: product.id,
                                name: product.name,
                                sku: product.sku,
                                price: product.price,
                                quantity: 1,
                                stock_quantity: product.stock_quantity
                            });
                        }
                        
                        ZPOSAdmin.updateCartDisplay();
                        ZPOSAdmin.calculateCart();
                    }
                }
            });
        },

        updateCartQuantity: function(index, change) {
            if (ZPOSAdmin.pos.cart[index]) {
                const newQuantity = ZPOSAdmin.pos.cart[index].quantity + change;
                
                if (newQuantity <= 0) {
                    ZPOSAdmin.removeFromCart(index);
                } else if (newQuantity <= ZPOSAdmin.pos.cart[index].stock_quantity) {
                    ZPOSAdmin.pos.cart[index].quantity = newQuantity;
                    ZPOSAdmin.updateCartDisplay();
                    ZPOSAdmin.calculateCart();
                }
            }
        },

        removeFromCart: function(index) {
            ZPOSAdmin.pos.cart.splice(index, 1);
            ZPOSAdmin.updateCartDisplay();
            ZPOSAdmin.calculateCart();
        },

        updateCartDisplay: function() {
            const $cartItems = $('#cart-items');
            let html = '';
            
            if (ZPOSAdmin.pos.cart.length === 0) {
                html = '<div class="empty-cart"><p>' + zpos_admin.text.empty_cart + '</p></div>';
            } else {
                ZPOSAdmin.pos.cart.forEach(function(item, index) {
                    html += `
                        <div class="cart-item">
                            <div class="item-info">
                                <h4>${item.name}</h4>
                                <p class="item-sku">${item.sku}</p>
                                <p class="item-price">$${item.price}</p>
                            </div>
                            <div class="item-controls">
                                <div class="quantity-controls">
                                    <button class="qty-decrease" data-index="${index}">-</button>
                                    <span class="quantity">${item.quantity}</span>
                                    <button class="qty-increase" data-index="${index}">+</button>
                                </div>
                                <button class="remove-item" data-index="${index}">×</button>
                            </div>
                            <div class="item-total">$${(item.price * item.quantity).toFixed(2)}</div>
                        </div>
                    `;
                });
            }
            
            $cartItems.html(html);
            
            // Update cart badge
            $('#cart-count').text(ZPOSAdmin.pos.cart.length);
        },

        calculateCart: function() {
            if (ZPOSAdmin.pos.cart.length === 0) {
                ZPOSAdmin.updateCartTotals({
                    subtotal: 0,
                    discount_amount: 0,
                    tax_amount: 0,
                    total: 0
                });
                return;
            }
            
            $.ajax({
                url: zpos_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'zpos_calculate_cart',
                    cart_items: JSON.stringify(ZPOSAdmin.pos.cart),
                    discount_type: ZPOSAdmin.pos.discount.type,
                    discount_value: ZPOSAdmin.pos.discount.value,
                    tax_rate: $('#tax-rate').val() || 0,
                    nonce: zpos_admin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        ZPOSAdmin.updateCartTotals(response.data);
                    }
                }
            });
        },

        updateCartTotals: function(totals) {
            $('#subtotal-amount').text('$' + totals.subtotal);
            $('#discount-amount').text('$' + totals.discount_amount);
            $('#tax-amount').text('$' + totals.tax_amount);
            $('#total-amount').text('$' + totals.total);
            
            // Enable/disable checkout button
            $('#checkout-btn').prop('disabled', totals.total <= 0);
        },

        processCheckout: function() {
            if (ZPOSAdmin.pos.cart.length === 0) {
                alert(zpos_admin.text.empty_cart);
                return;
            }
            
            const paymentMethod = $('#payment-method').val();
            if (!paymentMethod) {
                alert('Please select a payment method');
                return;
            }
            
            $.ajax({
                url: zpos_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'zpos_create_order',
                    cart_items: JSON.stringify(ZPOSAdmin.pos.cart),
                    customer_id: ZPOSAdmin.pos.customer ? ZPOSAdmin.pos.customer.id : 0,
                    payment_method: paymentMethod,
                    discount_type: ZPOSAdmin.pos.discount.type,
                    discount_value: ZPOSAdmin.pos.discount.value,
                    tax_rate: $('#tax-rate').val() || 0,
                    notes: $('#order-notes').val(),
                    nonce: zpos_admin.nonce
                },
                beforeSend: function() {
                    $('#checkout-btn').prop('disabled', true).text('Processing...');
                },
                success: function(response) {
                    if (response.success) {
                        // Show receipt
                        ZPOSAdmin.showReceipt(response.data.order_id);
                        
                        // Clear cart
                        ZPOSAdmin.clearCart();
                        
                        alert('Order created successfully!');
                    } else {
                        alert('Error: ' + response.data);
                    }
                },
                complete: function() {
                    $('#checkout-btn').prop('disabled', false).text('Complete Sale');
                }
            });
        },

        clearCart: function() {
            ZPOSAdmin.pos.cart = [];
            ZPOSAdmin.pos.customer = null;
            ZPOSAdmin.pos.discount = { type: 'none', value: 0 };
            
            ZPOSAdmin.updateCartDisplay();
            ZPOSAdmin.calculateCart();
            
            // Reset forms
            $('#customer-search').val('');
            $('#discount-type').val('none');
            $('#discount-value').val('');
            $('#order-notes').val('');
            $('#customer-info').html('<p>No customer selected</p>');
        },

        showReceipt: function(orderId) {
            $.ajax({
                url: zpos_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'zpos_get_receipt',
                    order_id: orderId,
                    nonce: zpos_admin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $('#receipt-content').html(response.data.receipt_html);
                        $('#receipt-modal').addClass('active');
                    }
                }
            });
        }
    });

})(jQuery);

// Order Management System JavaScript
(function($) {
    'use strict';

    // Extend ZPOSAdmin with Order Management functionality
    window.ZPOSAdmin = window.ZPOSAdmin || {};
    
    $.extend(window.ZPOSAdmin, {
        orders: {
            currentPage: 1,
            totalPages: 1,
            currentFilters: {
                status: '',
                date_from: '',
                date_to: '',
                search: '',
                orderby: 'created_at',
                order: 'desc'
            },
            selectedOrders: []
        },

        /**
         * Initialize Order Management System
         */
        initOrders: function() {
            console.log('Initializing ZPOS Order Management System...');
            
            this.bindOrderEvents();
            this.loadOrders();
            this.initOrderFilters();
            this.initOrderActions();
        },

        /**
         * Bind order-related events
         */
        bindOrderEvents: function() {
            const self = this;
            
            // Search orders
            $('#search-orders').on('keyup', function() {
                clearTimeout(self.orders.searchTimer);
                self.orders.searchTimer = setTimeout(function() {
                    self.orders.currentFilters.search = $('#search-orders').val();
                    self.orders.currentPage = 1;
                    self.loadOrders();
                }, 500);
            });
            
            // Filter by status
            $('#status-filter').on('change', function() {
                self.orders.currentFilters.status = $(this).val();
                self.orders.currentPage = 1;
                self.loadOrders();
            });
            
            // Date filters
            $('#date-from, #date-to').on('change', function() {
                self.orders.currentFilters.date_from = $('#date-from').val();
                self.orders.currentFilters.date_to = $('#date-to').val();
                self.orders.currentPage = 1;
                self.loadOrders();
            });
            
            // Clear filters
            $('#clear-filters').on('click', function() {
                $('#search-orders, #status-filter, #date-from, #date-to').val('');
                self.orders.currentFilters = {
                    status: '',
                    date_from: '',
                    date_to: '',
                    search: '',
                    orderby: 'created_at',
                    order: 'desc'
                };
                self.orders.currentPage = 1;
                self.loadOrders();
            });
            
            // Sort columns
            $(document).on('click', '.sortable a', function(e) {
                e.preventDefault();
                const $th = $(this).closest('th');
                const orderby = $th.data('orderby');
                const currentOrder = $th.hasClass('asc') ? 'desc' : 'asc';
                
                $('.sortable').removeClass('asc desc');
                $th.addClass(currentOrder);
                
                self.orders.currentFilters.orderby = orderby;
                self.orders.currentFilters.order = currentOrder;
                self.orders.currentPage = 1;
                self.loadOrders();
            });
            
            // Select all orders
            $(document).on('change', '#cb-select-all', function() {
                const isChecked = $(this).is(':checked');
                $('.order-checkbox').prop('checked', isChecked);
                self.updateSelectedOrders();
            });
            
            // Individual order selection
            $(document).on('change', '.order-checkbox', function() {
                self.updateSelectedOrders();
            });
            
            // Bulk actions
            $('#doaction').on('click', function() {
                const action = $('#bulk-action').val();
                if (action !== '-1' && self.orders.selectedOrders.length > 0) {
                    self.performBulkAction(action);
                }
            });
            
            // Order details modal
            $(document).on('click', '.view-order', function(e) {
                e.preventDefault();
                const orderId = $(this).data('order-id');
                self.showOrderDetails(orderId);
            });
            
            // Update order status
            $(document).on('change', '.order-status-select', function() {
                const orderId = $(this).data('order-id');
                const newStatus = $(this).val();
                self.updateOrderStatus(orderId, newStatus);
            });
            
            // Export orders
            $('#export-orders').on('click', function() {
                self.exportOrders();
            });
            
            // WooCommerce sync
            $('#sync-woocommerce').on('click', function() {
                self.showWooCommerceSyncModal();
            });
            
            // Pagination
            $(document).on('click', '.page-numbers', function(e) {
                e.preventDefault();
                const page = $(this).data('page');
                if (page && page !== self.orders.currentPage) {
                    self.orders.currentPage = page;
                    self.loadOrders();
                }
            });
        },

        /**
         * Initialize order filters
         */
        initOrderFilters: function() {
            // Set default date range to today
            const today = new Date().toISOString().split('T')[0];
            $('#date-from').attr('max', today);
            $('#date-to').attr('max', today);
        },

        /**
         * Initialize order actions
         */
        initOrderActions: function() {
            // Add any additional action initializations here
            this.setupOrderModals();
        },

        /**
         * Setup order-related modals
         */
        setupOrderModals: function() {
            // Order details modal
            $('#order-details-modal .zpos-modal-close').on('click', function() {
                $('#order-details-modal').hide();
            });
            
            // WooCommerce sync modal
            $('#wc-sync-modal .zpos-modal-close').on('click', function() {
                $('#wc-sync-modal').hide();
            });
            
            // Close modals when clicking outside
            $('.zpos-modal').on('click', function(e) {
                if (e.target === this) {
                    $(this).hide();
                }
            });
        },

        /**
         * Load orders with current filters
         */
        loadOrders: function() {
            const self = this;
            
            $('#orders-loading').show();
            $('#orders-tbody').empty();
            $('#no-orders').hide();
            
            $.ajax({                url: zpos_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'zpos_get_orders',
                    nonce: zpos_admin.nonce,
                    page: self.orders.currentPage,
                    per_page: 20,
                    ...self.orders.currentFilters
                },
                success: function(response) {
                    $('#orders-loading').hide();
                    
                    if (response.success && response.data.orders.length > 0) {
                        self.displayOrders(response.data.orders);
                        self.displayPagination(response.data);
                    } else {
                        $('#no-orders').show();
                    }
                },
                error: function(xhr, status, error) {
                    $('#orders-loading').hide();
                    console.error('Error loading orders:', error);
                    self.showNotice('Error loading orders. Please try again.', 'error');
                }
            });
        },

        /**
         * Display orders in table
         */
        displayOrders: function(orders) {
            const self = this;
            let html = '';
            
            orders.forEach(function(order) {
                const statusClass = 'status-' + order.status;
                const statusLabel = order.status.charAt(0).toUpperCase() + order.status.slice(1);
                const orderDate = new Date(order.created_at).toLocaleDateString();
                const total = self.formatCurrency(order.total_amount);
                
                html += `
                    <tr>
                        <td class="check-column">
                            <input type="checkbox" class="order-checkbox" value="${order.id}">
                        </td>
                        <td><strong>#${order.order_number}</strong></td>
                        <td>
                            <span class="order-status ${statusClass}">
                                ${statusLabel}
                            </span>
                        </td>
                        <td>
                            <strong>${order.customer_name || 'Walk-in Customer'}</strong>
                            ${order.customer_email ? '<br><small>' + order.customer_email + '</small>' : ''}
                        </td>
                        <td><strong>${total}</strong></td>
                        <td>${order.payment_method || 'Cash'}</td>
                        <td>${orderDate}</td>
                        <td>
                            <button type="button" class="button button-small view-order" data-order-id="${order.id}">
                                View
                            </button>
                            <select class="order-status-select" data-order-id="${order.id}">
                                <option value="pending" ${order.status === 'pending' ? 'selected' : ''}>Pending</option>
                                <option value="processing" ${order.status === 'processing' ? 'selected' : ''}>Processing</option>
                                <option value="completed" ${order.status === 'completed' ? 'selected' : ''}>Completed</option>
                                <option value="cancelled" ${order.status === 'cancelled' ? 'selected' : ''}>Cancelled</option>
                            </select>
                        </td>
                    </tr>
                `;
            });
            
            $('#orders-tbody').html(html);
        },

        /**
         * Display pagination
         */
        displayPagination: function(data) {
            const self = this;
            this.orders.totalPages = data.total_pages;
            
            let paginationHtml = '';
            
            if (data.total_pages > 1) {
                paginationHtml += '<span class="displaying-num">' + data.total + ' items</span>';
                
                // Previous page
                if (this.orders.currentPage > 1) {
                    paginationHtml += `<a class="prev-page page-numbers" data-page="${this.orders.currentPage - 1}">‹</a>`;
                }
                
                // Page numbers
                for (let i = 1; i <= data.total_pages; i++) {
                    if (i === this.orders.currentPage) {
                        paginationHtml += `<span class="page-numbers current">${i}</span>`;
                    } else {
                        paginationHtml += `<a class="page-numbers" data-page="${i}">${i}</a>`;
                    }
                }
                
                // Next page
                if (this.orders.currentPage < data.total_pages) {
                    paginationHtml += `<a class="next-page page-numbers" data-page="${this.orders.currentPage + 1}">›</a>`;
                }
            }
            
            $('#pagination-container').html(paginationHtml);
        },

        /**
         * Update selected orders array
         */
        updateSelectedOrders: function() {
            this.orders.selectedOrders = [];
            $('.order-checkbox:checked').each(function() {
                this.orders.selectedOrders.push($(this).val());
            }.bind(this));
            
            // Update select all checkbox
            const totalCheckboxes = $('.order-checkbox').length;
            const checkedCheckboxes = $('.order-checkbox:checked').length;
            const selectAllCheckbox = $('#cb-select-all');
            
            if (checkedCheckboxes === 0) {
                selectAllCheckbox.prop('indeterminate', false).prop('checked', false);
            } else if (checkedCheckboxes === totalCheckboxes) {
                selectAllCheckbox.prop('indeterminate', false).prop('checked', true);
            } else {
                selectAllCheckbox.prop('indeterminate', true);
            }
        },

        /**
         * Perform bulk action on selected orders
         */
        performBulkAction: function(action) {
            const self = this;
            
            if (this.orders.selectedOrders.length === 0) {
                alert('Please select orders to perform bulk action.');
                return;
            }
            
            let newStatus = '';
            switch (action) {
                case 'mark_processing':
                    newStatus = 'processing';
                    break;
                case 'mark_completed':
                    newStatus = 'completed';
                    break;
                case 'mark_cancelled':
                    newStatus = 'cancelled';
                    break;
                default:
                    return;
            }
            
            if (confirm(`Are you sure you want to update ${this.orders.selectedOrders.length} orders to ${newStatus}?`)) {                $.ajax({
                    url: zpos_admin.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'zpos_bulk_update_order_status',
                        nonce: zpos_admin.nonce,
                        order_ids: this.orders.selectedOrders,
                        status: newStatus
                    },
                    success: function(response) {
                        if (response.success) {
                            self.showNotice('Orders updated successfully!', 'success');
                            self.loadOrders();
                        } else {
                            self.showNotice('Error updating orders: ' + response.data, 'error');
                        }
                    },
                    error: function() {
                        self.showNotice('Error updating orders. Please try again.', 'error');
                    }
                });
            }
        },

        /**
         * Show order details in modal
         */
        showOrderDetails: function(orderId) {
            const self = this;
              $.ajax({
                url: zpos_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'zpos_get_order_details',
                    nonce: zpos_admin.nonce,
                    order_id: orderId
                },
                success: function(response) {
                    if (response.success) {
                        $('#order-details-content').html(response.data.html);
                        $('#order-details-modal').show();
                    } else {
                        self.showNotice('Error loading order details: ' + response.data, 'error');
                    }
                },
                error: function() {
                    self.showNotice('Error loading order details. Please try again.', 'error');
                }
            });
        },

        /**
         * Update order status
         */
        updateOrderStatus: function(orderId, newStatus) {
            const self = this;
              $.ajax({
                url: zpos_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'zpos_update_order_status',
                    nonce: zpos_admin.nonce,
                    order_id: orderId,
                    status: newStatus
                },
                success: function(response) {
                    if (response.success) {
                        self.showNotice('Order status updated successfully!', 'success');
                        // Update the status display in the table
                        self.refreshOrderRow(orderId);
                    } else {
                        self.showNotice('Error updating order status: ' + response.data, 'error');
                    }
                },
                error: function() {
                    self.showNotice('Error updating order status. Please try again.', 'error');
                }
            });
        },

        /**
         * Export orders
         */
        exportOrders: function() {            const params = new URLSearchParams({
                action: 'zpos_export_orders',
                nonce: zpos_admin.nonce,
                ...this.orders.currentFilters
            });
            
            window.open(zpos_admin.ajax_url + '?' + params.toString());
        },

        /**
         * Show WooCommerce sync modal
         */
        showWooCommerceSyncModal: function() {
            $('#wc-sync-modal').show();
        },

        /**
         * Refresh specific order row
         */
        refreshOrderRow: function(orderId) {
            // This would typically reload just the specific row
            // For simplicity, we'll reload all orders
            this.loadOrders();
        },

        /**
         * Format currency display
         */
        formatCurrency: function(amount) {
            // This should use the actual currency settings from WordPress/ZPOS
            return '$' + parseFloat(amount).toFixed(2);
        },

        /**
         * Show notification message
         */
        showNotice: function(message, type) {
            type = type || 'info';
            
            // Remove existing notices
            $('.zpos-notice').remove();
            
            // Create new notice
            const notice = $(`
                <div class="notice notice-${type} is-dismissible zpos-notice">
                    <p>${message}</p>
                    <button type="button" class="notice-dismiss">
                        <span class="screen-reader-text">Dismiss this notice.</span>
                    </button>
                </div>
            `);
            
            // Insert notice
            $('.wrap').prepend(notice);
            
            // Auto-dismiss after 5 seconds
            setTimeout(function() {
                notice.fadeOut();
            }, 5000);
            
            // Manual dismiss
            notice.find('.notice-dismiss').on('click', function() {
                notice.fadeOut();
            });
        }
    });

})(jQuery);

// Reports Management System JavaScript
(function($) {
    'use strict';

    // Extend ZPOSAdmin with Reports functionality
    window.ZPOSAdmin = window.ZPOSAdmin || {};
    
    $.extend(window.ZPOSAdmin, {
        reports: {
            currentReport: null,
            reportData: null,
            charts: {}
        },

        /**
         * Initialize Reports Management System
         */
        initReports: function() {
            console.log('Initializing ZPOS Reports System...');
            
            this.bindReportEvents();
            this.initReportFilters();
            this.setupReportCharts();
        },

        /**
         * Bind report-related events
         */
        bindReportEvents: function() {
            const self = this;
            
            // Generate report button
            $('#generate-report').on('click', function() {
                const reportType = $('#report-type').val();
                const period = $('#report-period').val();
                const startDate = $('#report-start-date').val();
                const endDate = $('#report-end-date').val();
                
                self.generateReport(reportType, period, startDate, endDate);
            });
            
            // Export report button
            $('#export-report').on('click', function() {
                const format = $('#export-format').val();
                self.exportReport(format);
            });
            
            // Report type change
            $('#report-type').on('change', function() {
                const reportType = $(this).val();
                self.updateReportOptions(reportType);
            });
            
            // Period preset buttons
            $('.period-preset').on('click', function() {
                const period = $(this).data('period');
                self.setReportPeriod(period);
                $(this).addClass('active').siblings().removeClass('active');
            });
        },

        /**
         * Initialize report filters
         */
        initReportFilters: function() {
            // Set default date range (last 30 days)
            const today = new Date();
            const lastMonth = new Date(today.getTime() - (30 * 24 * 60 * 60 * 1000));
            
            $('#report-end-date').val(today.toISOString().split('T')[0]);
            $('#report-start-date').val(lastMonth.toISOString().split('T')[0]);
        },

        /**
         * Setup report charts
         */
        setupReportCharts: function() {
            if (typeof Chart === 'undefined') {
                console.warn('Chart.js not loaded. Report charts will not be displayed.');
                return;
            }
            
            // Revenue Chart
            const revenueCtx = document.getElementById('report-revenue-chart');
            if (revenueCtx) {
                this.reports.charts.revenue = new Chart(revenueCtx, {
                    type: 'line',
                    data: {
                        labels: [],
                        datasets: [{
                            label: 'Revenue',
                            data: [],
                            borderColor: '#0073aa',
                            backgroundColor: 'rgba(0, 115, 170, 0.1)',
                            tension: 0.4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: function(value) {
                                        return '$' + value.toLocaleString();
                                    }
                                }
                            }
                        }
                    }
                });
            }
            
            // Products Chart
            const productsCtx = document.getElementById('report-products-chart');
            if (productsCtx) {
                this.reports.charts.products = new Chart(productsCtx, {
                    type: 'bar',
                    data: {
                        labels: [],
                        datasets: [{
                            label: 'Units Sold',
                            data: [],
                            backgroundColor: '#00a32a',
                            borderColor: '#00a32a',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });
            }
        },

        /**
         * Generate report
         */
        generateReport: function(reportType, period, startDate, endDate) {
            const self = this;
            
            if (!reportType) {
                this.showNotice('Please select a report type.', 'error');
                return;
            }
            
            $('#generate-report').prop('disabled', true).text('Generating...');
            $('#report-content').html('<div class="zpos-loading"><div class="zpos-spinner"></div>Generating report...</div>');
            
            let ajaxAction = '';
            switch (reportType) {
                case 'revenue':
                    ajaxAction = 'zpos_get_revenue_report';
                    break;
                case 'products':
                    ajaxAction = 'zpos_get_products_report';
                    break;
                case 'profit':
                    ajaxAction = 'zpos_get_profit_report';
                    break;
                default:
                    this.showNotice('Invalid report type.', 'error');
                    return;
            }
            
            $.ajax({
                url: zpos_admin.ajax_url,
                type: 'POST',
                data: {
                    action: ajaxAction,
                    nonce: zpos_admin.nonce,
                    period: period,
                    start_date: startDate,
                    end_date: endDate
                },
                success: function(response) {
                    $('#generate-report').prop('disabled', false).text('Generate Report');
                    
                    if (response.success) {
                        self.reports.currentReport = reportType;
                        self.reports.reportData = response.data;
                        self.displayReport(response.data, reportType);
                        self.updateReportCharts(response.data, reportType);
                        $('#export-report').show();
                    } else {
                        $('#report-content').html('<div class="zpos-text-error">Error generating report: ' + (response.data || 'Unknown error') + '</div>');
                    }
                },
                error: function() {
                    $('#generate-report').prop('disabled', false).text('Generate Report');
                    $('#report-content').html('<div class="zpos-text-error">Error generating report. Please try again.</div>');
                }
            });
        },

        /**
         * Display report data
         */
        displayReport: function(data, reportType) {
            let html = '';
            
            switch (reportType) {
                case 'revenue':
                    html = this.renderRevenueReport(data);
                    break;
                case 'products':
                    html = this.renderProductsReport(data);
                    break;
                case 'profit':
                    html = this.renderProfitReport(data);
                    break;
            }
            
            $('#report-content').html(html);
        },

        /**
         * Render revenue report
         */
        renderRevenueReport: function(data) {
            let html = '<div class="report-summary">';
            html += '<h3>Revenue Report Summary</h3>';
            html += '<div class="report-stats">';
            html += `<div class="stat-item"><span class="stat-label">Total Revenue:</span><span class="stat-value">$${data.total_revenue}</span></div>`;
            html += `<div class="stat-item"><span class="stat-label">Total Orders:</span><span class="stat-value">${data.total_orders}</span></div>`;
            html += `<div class="stat-item"><span class="stat-label">Average Order Value:</span><span class="stat-value">$${data.avg_order_value}</span></div>`;
            html += '</div></div>';
            
            if (data.periods && data.periods.length > 0) {
                html += '<div class="report-table">';
                html += '<h4>Revenue by Period</h4>';
                html += '<table class="widefat"><thead><tr><th>Period</th><th>Orders</th><th>Revenue</th><th>Avg Order</th></tr></thead><tbody>';
                
                data.periods.forEach(period => {
                    html += `<tr>
                        <td>${period.period}</td>
                        <td>${period.order_count}</td>
                        <td>$${parseFloat(period.total_revenue).toFixed(2)}</td>
                        <td>$${parseFloat(period.avg_order_value).toFixed(2)}</td>
                    </tr>`;
                });
                
                html += '</tbody></table></div>';
            }
            
            return html;
        },

        /**
         * Render products report
         */
        renderProductsReport: function(data) {
            let html = '<div class="report-summary">';
            html += '<h3>Products Report Summary</h3>';
            html += '<div class="report-stats">';
            html += `<div class="stat-item"><span class="stat-label">Total Products Sold:</span><span class="stat-value">${data.total_quantity}</span></div>`;
            html += `<div class="stat-item"><span class="stat-label">Total Revenue:</span><span class="stat-value">$${data.total_revenue}</span></div>`;
            html += `<div class="stat-item"><span class="stat-label">Best Seller:</span><span class="stat-value">${data.top_product}</span></div>`;
            html += '</div></div>';
            
            if (data.products && data.products.length > 0) {
                html += '<div class="report-table">';
                html += '<h4>Top Selling Products</h4>';
                html += '<table class="widefat"><thead><tr><th>Product</th><th>Quantity Sold</th><th>Revenue</th><th>Avg Price</th></tr></thead><tbody>';
                
                data.products.forEach(product => {
                    html += `<tr>
                        <td>${product.product_name}</td>
                        <td>${product.quantity_sold}</td>
                        <td>$${parseFloat(product.revenue).toFixed(2)}</td>
                        <td>$${parseFloat(product.avg_price).toFixed(2)}</td>
                    </tr>`;
                });
                
                html += '</tbody></table></div>';
            }
            
            return html;
        },

        /**
         * Render profit report
         */
        renderProfitReport: function(data) {
            let html = '<div class="report-summary">';
            html += '<h3>Profit Report Summary</h3>';
            html += '<div class="report-stats">';
            html += `<div class="stat-item"><span class="stat-label">Gross Profit:</span><span class="stat-value">$${data.gross_profit}</span></div>`;
            html += `<div class="stat-item"><span class="stat-label">Net Profit:</span><span class="stat-value">$${data.net_profit}</span></div>`;
            html += `<div class="stat-item"><span class="stat-label">Profit Margin:</span><span class="stat-value">${data.profit_margin}%</span></div>`;
            html += '</div></div>';
            
            return html;
        },

        /**
         * Update report charts
         */
        updateReportCharts: function(data, reportType) {
            if (reportType === 'revenue' && this.reports.charts.revenue && data.periods) {
                const labels = data.periods.map(p => p.period);
                const revenues = data.periods.map(p => parseFloat(p.total_revenue));
                
                this.reports.charts.revenue.data.labels = labels;
                this.reports.charts.revenue.data.datasets[0].data = revenues;
                this.reports.charts.revenue.update();
            }
            
            if (reportType === 'products' && this.reports.charts.products && data.products) {
                const labels = data.products.slice(0, 10).map(p => p.product_name);
                const quantities = data.products.slice(0, 10).map(p => parseInt(p.quantity_sold));
                
                this.reports.charts.products.data.labels = labels;
                this.reports.charts.products.data.datasets[0].data = quantities;
                this.reports.charts.products.update();
            }
        },

        /**
         * Export report
         */
        exportReport: function(format) {
            if (!this.reports.currentReport || !this.reports.reportData) {
                this.showNotice('Please generate a report first.', 'error');
                return;
            }
            
            const params = new URLSearchParams({
                action: 'zpos_export_report',
                nonce: zpos_admin.nonce,
                report_type: this.reports.currentReport,
                format: format,
                data: JSON.stringify(this.reports.reportData)
            });
            
            window.open(zpos_admin.ajax_url + '?' + params.toString());
        },

        /**
         * Set report period preset
         */
        setReportPeriod: function(period) {
            const today = new Date();
            let startDate, endDate = today;
            
            switch (period) {
                case 'today':
                    startDate = today;
                    break;
                case 'yesterday':
                    startDate = new Date(today.getTime() - (24 * 60 * 60 * 1000));
                    endDate = startDate;
                    break;
                case 'week':
                    startDate = new Date(today.getTime() - (7 * 24 * 60 * 60 * 1000));
                    break;
                case 'month':
                    startDate = new Date(today.getTime() - (30 * 24 * 60 * 60 * 1000));
                    break;
                case 'quarter':
                    startDate = new Date(today.getTime() - (90 * 24 * 60 * 60 * 1000));
                    break;
                case 'year':
                    startDate = new Date(today.getTime() - (365 * 24 * 60 * 60 * 1000));
                    break;
            }
            
            $('#report-start-date').val(startDate.toISOString().split('T')[0]);
            $('#report-end-date').val(endDate.toISOString().split('T')[0]);
        },

        /**
         * Update report options based on type
         */
        updateReportOptions: function(reportType) {
            // Show/hide specific options based on report type
            $('.report-option').hide();
            $('.report-option.' + reportType).show();
        }
    });

})(jQuery);

// Settings Management System JavaScript
(function($) {
    'use strict';

    // Extend ZPOSAdmin with Settings functionality
    window.ZPOSAdmin = window.ZPOSAdmin || {};
    
    $.extend(window.ZPOSAdmin, {
        settings: {
            isDirty: false,
            originalSettings: null
        },

        /**
         * Initialize Settings Management System
         */
        initSettings: function() {
            console.log('Initializing ZPOS Settings System...');
            
            this.bindSettingsEvents();
            this.initSettingsValidation();
            this.checkUnsavedChanges();
            this.storeOriginalSettings();
        },

        /**
         * Bind settings-related events
         */
        bindSettingsEvents: function() {
            const self = this;
            
            // Save settings button
            $('#save-settings').on('click', function() {
                self.saveSettings();
            });
            
            // Reset settings button
            $('#reset-settings').on('click', function() {
                if (confirm('Are you sure you want to reset all settings to default values? This action cannot be undone.')) {
                    self.resetSettings();
                }
            });
            
            // Test WooCommerce connection
            $('#test-woocommerce').on('click', function() {
                self.testWooCommerceConnection();
            });
            
            // Sync WooCommerce data
            $('#sync-woocommerce').on('click', function() {
                self.syncWooCommerceData();
            });
            
            // Re-run setup wizard
            $('#rerun-wizard').on('click', function() {
                if (confirm('This will take you through the initial setup process again. Continue?')) {
                    self.rerunSetupWizard();
                }
            });
            
            // Mark form as dirty when any input changes
            $('.zpos-settings-form input, .zpos-settings-form select, .zpos-settings-form textarea').on('change input', function() {
                self.settings.isDirty = true;
                self.showUnsavedIndicator();
            });
            
            // Settings tabs
            $('.settings-tab').on('click', function() {
                const tab = $(this).data('tab');
                self.switchSettingsTab(tab);
            });
            
            // WooCommerce enable/disable toggle
            $('#woocommerce_enabled').on('change', function() {
                self.toggleWooCommerceSettings($(this).is(':checked'));
            });
        },

        /**
         * Initialize settings validation
         */
        initSettingsValidation: function() {
            // Real-time validation for specific fields
            $('#store_email').on('blur', function() {
                const email = $(this).val();
                if (email && !this.validateEmail(email)) {
                    this.showFieldError($(this), 'Please enter a valid email address.');
                } else {
                    this.clearFieldError($(this));
                }
            }.bind(this));
            
            $('#store_phone').on('blur', function() {
                const phone = $(this).val();
                if (phone && !this.validatePhone(phone)) {
                    this.showFieldError($(this), 'Please enter a valid phone number.');
                } else {
                    this.clearFieldError($(this));
                }
            }.bind(this));
        },

        /**
         * Store original settings for comparison
         */
        storeOriginalSettings: function() {
            const formData = {};
            $('.zpos-settings-form input, .zpos-settings-form select, .zpos-settings-form textarea').each(function() {
                const $field = $(this);
                const name = $field.attr('name');
                if (name) {
                    if ($field.attr('type') === 'checkbox') {
                        formData[name] = $field.is(':checked');
                    } else {
                        formData[name] = $field.val();
                    }
                }
            });
            this.settings.originalSettings = formData;
        },

        /**
         * Check for unsaved changes
         */
        checkUnsavedChanges: function() {
            const self = this;
            
            // Warn user about unsaved changes when leaving page
            $(window).on('beforeunload', function() {
                if (self.settings.isDirty) {
                    return 'You have unsaved changes. Are you sure you want to leave this page?';
                }
            });
        },

        /**
         * Show unsaved changes indicator
         */
        showUnsavedIndicator: function() {
            $('#save-settings').addClass('unsaved').text('Save Changes*');
            $('.settings-unsaved-notice').show();
        },

        /**
         * Hide unsaved changes indicator
         */
        hideUnsavedIndicator: function() {
            $('#save-settings').removeClass('unsaved').text('Save Settings');
            $('.settings-unsaved-notice').hide();
            this.settings.isDirty = false;
        },

        /**
         * Save settings
         */
        saveSettings: function() {
            const self = this;
            
            // Validate form before saving
            if (!this.validateSettingsForm()) {
                return;
            }
            
            const formData = {};
            $('.zpos-settings-form input, .zpos-settings-form select, .zpos-settings-form textarea').each(function() {
                const $field = $(this);
                const name = $field.attr('name');
                if (name) {
                    if ($field.attr('type') === 'checkbox') {
                        formData[name] = $field.is(':checked') ? '1' : '0';
                    } else {
                        formData[name] = $field.val();
                    }
                }
            });
            
            $('#save-settings').prop('disabled', true).text('Saving...');
            
            $.ajax({
                url: zpos_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'zpos_save_settings',
                    nonce: zpos_admin.nonce,
                    settings: formData
                },
                success: function(response) {
                    $('#save-settings').prop('disabled', false).text('Save Settings');
                    
                    if (response.success) {
                        self.showNotice('Settings saved successfully!', 'success');
                        self.hideUnsavedIndicator();
                        self.storeOriginalSettings();
                    } else {
                        self.showNotice('Error saving settings: ' + (response.data || 'Unknown error'), 'error');
                    }
                },
                error: function() {
                    $('#save-settings').prop('disabled', false).text('Save Settings');
                    self.showNotice('Error saving settings. Please try again.', 'error');
                }
            });
        },

        /**
         * Reset settings to defaults
         */
        resetSettings: function() {
            const self = this;
            
            $('#reset-settings').prop('disabled', true).text('Resetting...');
            
            $.ajax({
                url: zpos_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'zpos_reset_settings',
                    nonce: zpos_admin.nonce
                },
                success: function(response) {
                    $('#reset-settings').prop('disabled', false).text('Reset to Defaults');
                    
                    if (response.success) {
                        self.showNotice('Settings reset successfully! Page will reload in 2 seconds.', 'success');
                        setTimeout(function() {
                            window.location.reload();
                        }, 2000);
                    } else {
                        self.showNotice('Error resetting settings: ' + (response.data || 'Unknown error'), 'error');
                    }
                },
                error: function() {
                    $('#reset-settings').prop('disabled', false).text('Reset to Defaults');
                    self.showNotice('Error resetting settings. Please try again.', 'error');
                }
            });
        },

        /**
         * Test WooCommerce connection
         */
        testWooCommerceConnection: function() {
            const self = this;
            
            $('#test-woocommerce').prop('disabled', true).text('Testing...');
            
            $.ajax({
                url: zpos_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'zpos_test_woocommerce',
                    nonce: zpos_admin.nonce
                },
                success: function(response) {
                    $('#test-woocommerce').prop('disabled', false).text('Test Connection');
                    
                    if (response.success) {
                        self.showNotice('WooCommerce connection successful!', 'success');
                        $('#woocommerce-status').removeClass('error').addClass('success').text('Connected');
                    } else {
                        self.showNotice('WooCommerce connection failed: ' + (response.data || 'Unknown error'), 'error');
                        $('#woocommerce-status').removeClass('success').addClass('error').text('Not Connected');
                    }
                },
                error: function() {
                    $('#test-woocommerce').prop('disabled', false).text('Test Connection');
                    self.showNotice('Error testing WooCommerce connection. Please try again.', 'error');
                    $('#woocommerce-status').removeClass('success').addClass('error').text('Error');
                }
            });
        },

        /**
         * Sync WooCommerce data
         */
        syncWooCommerceData: function() {
            const self = this;
            
            $('#sync-woocommerce').prop('disabled', true).text('Syncing...');
            
            $.ajax({
                url: zpos_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'zpos_sync_woocommerce',
                    nonce: zpos_admin.nonce
                },
                success: function(response) {
                    $('#sync-woocommerce').prop('disabled', false).text('Sync Data');
                    
                    if (response.success) {
                        self.showNotice('WooCommerce data synced successfully!', 'success');
                        if (response.data.stats) {
                            const stats = response.data.stats;
                            self.showNotice(`Synced: ${stats.products} products, ${stats.customers} customers, ${stats.orders} orders`, 'info');
                        }
                    } else {
                        self.showNotice('Error syncing WooCommerce data: ' + (response.data || 'Unknown error'), 'error');
                    }
                },
                error: function() {
                    $('#sync-woocommerce').prop('disabled', false).text('Sync Data');
                    self.showNotice('Error syncing WooCommerce data. Please try again.', 'error');
                }
            });
        },

        /**
         * Re-run setup wizard
         */
        rerunSetupWizard: function() {
            $.ajax({
                url: zpos_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'zpos_rerun_wizard',
                    nonce: zpos_admin.nonce
                },
                success: function(response) {
                    if (response.success && response.data.redirect_url) {
                        window.location.href = response.data.redirect_url;
                    } else {
                        window.location.href = admin_url + 'admin.php?page=zpos-wizard';
                    }
                }
            });
        },

        /**
         * Switch settings tab
         */
        switchSettingsTab: function(tab) {
            $('.settings-tab').removeClass('active');
            $('.settings-tab[data-tab="' + tab + '"]').addClass('active');
            
            $('.settings-section').hide();
            $('#settings-' + tab).show();
        },

        /**
         * Toggle WooCommerce settings visibility
         */
        toggleWooCommerceSettings: function(enabled) {
            if (enabled) {
                $('.woocommerce-setting').show();
            } else {
                $('.woocommerce-setting').hide();
            }
        },

        /**
         * Validate settings form
         */
        validateSettingsForm: function() {
            let isValid = true;
            
            // Clear previous errors
            $('.field-error').remove();
            $('.form-field').removeClass('error');
            
            // Validate required fields
            $('.required input, .required select, .required textarea').each(function() {
                const $field = $(this);
                if (!$field.val().trim()) {
                    this.showFieldError($field, 'This field is required.');
                    isValid = false;
                }
            }.bind(this));
            
            // Validate email
            const email = $('#store_email').val();
            if (email && !this.validateEmail(email)) {
                this.showFieldError($('#store_email'), 'Please enter a valid email address.');
                isValid = false;
            }
            
            return isValid;
        },

        /**
         * Validate email address
         */
        validateEmail: function(email) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email);
        },

        /**
         * Validate phone number
         */
        validatePhone: function(phone) {
            const phoneRegex = /^[\+\-\s\(\)\d]+$/;
            return phoneRegex.test(phone);
        },

        /**
         * Show field error
         */
        showFieldError: function($field, message) {
            $field.closest('.form-field').addClass('error');



            $field.after('<div class="field-error">' + message + '</div>');
        },

        /**
         * Clear field error
         */
        clearFieldError: function($field) {
            $field.closest('.form-field').removeClass('error');
            $field.siblings('.field-error').remove();
        }
    });

})(jQuery);

// Initialize based on current page
(function($) {
    'use strict';
    
    // Safety check for required variables
    if (typeof zpos_admin === 'undefined') {
        console.error('ZPOS: zpos_admin is not defined. AJAX functionality may not work.');
        return;
    }
    
    if (typeof ZPOSAdmin === 'undefined') {
        console.error('ZPOS: ZPOSAdmin object is not defined. Plugin may not be loaded correctly.');
        return;
    }
    
    $(document).ready(function() {
        // Get current page from body class or URL
        const currentPage = $('body').attr('class');
        
        if (currentPage && currentPage.includes('zpos_page_zpos-reports')) {
            ZPOSAdmin.initReports();
        } else if (currentPage && currentPage.includes('zpos_page_zpos-settings')) {
            ZPOSAdmin.initSettings();
        } else if (currentPage && currentPage.includes('zpos_page_zpos-orders')) {
            ZPOSAdmin.initOrders();
        }
    });
    
})(jQuery);
