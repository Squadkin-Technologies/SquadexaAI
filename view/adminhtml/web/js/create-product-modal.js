/**
 * Copyright © 2024 Squadkin. All rights reserved.
 */
define([
    'jquery',
    'Magento_Ui/js/modal/modal',
    'mage/url',
    'mage/translate'
], function ($, modal, urlBuilder, $t) {
    'use strict';

    var CreateProductModal = {
        aiproductId: null,
        productTypes: [],
        modalElement: null,

        /**
         * Initialize
         */
        init: function () {
            console.log('[Create Product Modal] init() called');
            var self = this;
            this.modalElement = $('#create-product-from-ai-modal');
            console.log('[Create Product Modal] Modal element search result:', this.modalElement.length);
            
            if (!this.modalElement.length) {
                console.error('[Create Product Modal] Modal element not found in DOM');
                return;
            }
            console.log('[Create Product Modal] Modal element found');

            // Initialize modal
            var modalOptions = {
                type: 'popup',
                responsive: true,
                innerScroll: true,
                title: $t('Create Product from AI'),
                buttons: [{
                    text: $t('Cancel'),
                    class: 'action-secondary',
                    click: function () {
                        console.log('[Create Product Modal] Cancel button clicked');
                        self.modalElement.modal('closeModal');
                    }
                }, {
                    text: $t('Create Product'),
                    class: 'action-primary',
                    click: function () {
                        console.log('[Create Product Modal] Create Product button clicked');
                        self.createProduct();
                    }
                }]
            };

            if (!this.modalElement.data('mageModal')) {
                console.log('[Create Product Modal] Initializing Magento modal');
                modal(modalOptions, this.modalElement);
                console.log('[Create Product Modal] Magento modal initialized');
            } else {
                console.log('[Create Product Modal] Modal already initialized');
            }

            // Load product types
            this.loadProductTypes();
        },

        /**
         * Open modal
         */
        open: function (aiproductId) {
            console.log('[Create Product Modal] open() called with aiproductId:', aiproductId);
            var self = this;
            this.aiproductId = aiproductId;
            console.log('[Create Product Modal] aiproductId set to:', this.aiproductId);
            
            // Ensure modal element exists
            if (!this.modalElement || !this.modalElement.length) {
                console.log('[Create Product Modal] Modal element not cached, searching in DOM...');
                this.modalElement = $('#create-product-from-ai-modal');
                if (!this.modalElement.length) {
                    console.error('[Create Product Modal] Modal element not found in DOM');
                    // Try to wait a bit for DOM to be ready
                    setTimeout(function() {
                        console.log('[Create Product Modal] Retrying to find modal element...');
                        self.modalElement = $('#create-product-from-ai-modal');
                        if (self.modalElement.length) {
                            console.log('[Create Product Modal] Modal element found on retry, initializing...');
                            self.init();
                            self.open(aiproductId);
                        } else {
                            console.error('[Create Product Modal] Modal element still not found after retry');
                        }
                    }, 500);
                    return;
                }
                console.log('[Create Product Modal] Modal element found, initializing...');
                this.init();
            } else {
                console.log('[Create Product Modal] Modal element already cached');
            }

            // Reset form
            $('#product-type-select').val('');

            // Populate product types
            this.populateProductTypes();

            // Open modal
            try {
                console.log('[Create Product Modal] Attempting to open modal...');
                this.modalElement.modal('openModal');
                console.log('[Create Product Modal] Modal openModal() called successfully');
            } catch (e) {
                console.error('[Create Product Modal] Error opening modal:', e);
                console.error('[Create Product Modal] Error stack:', e.stack);
                // Try to reinitialize modal
                console.log('[Create Product Modal] Reinitializing modal...');
                this.modalElement.data('mageModal', null);
                this.init();
                setTimeout(function() {
                    console.log('[Create Product Modal] Retrying to open modal after reinit...');
                    try {
                        self.modalElement.modal('openModal');
                        console.log('[Create Product Modal] Modal opened successfully after retry');
                    } catch (e2) {
                        console.error('[Create Product Modal] Error on retry:', e2);
                    }
                }, 100);
            }
        },

        /**
         * Load product types
         */
        loadProductTypes: function () {
            this.productTypes = [
                { value: 'simple', label: $t('Simple Product') },
                { value: 'configurable', label: $t('Configurable Product') },
                { value: 'virtual', label: $t('Virtual Product') },
                { value: 'downloadable', label: $t('Downloadable Product') },
                { value: 'bundle', label: $t('Bundle Product') },
                { value: 'grouped', label: $t('Grouped Product') }
            ];
        },

        /**
         * Populate product types dropdown
         */
        populateProductTypes: function () {
            var productTypeSelect = $('#product-type-select');
            productTypeSelect.empty().append('<option value="">-- Please Select --</option>');
            $.each(this.productTypes, function(index, item) {
                productTypeSelect.append('<option value="' + item.value + '">' + item.label + '</option>');
            });
        },

        /**
         * Create product
         */
        createProduct: function () {
            var productType = $('#product-type-select').val();

            if (!productType) {
                alert($t('Please select a product type'));
                return;
            }

            if (!this.aiproductId) {
                alert($t('AI Product ID is missing'));
                return;
            }

            // Build absolute URL - construct directly from BASE_URL to avoid path duplication
            var urlPath = 'squadkin_squadexaai/aiproduct/createMagentoProduct';
            var url;
            
            if (typeof BASE_URL !== 'undefined') {
                // BASE_URL format: https://domain.com/admin/squadkin_squadexaai/aiproduct/index/key/xxx/
                // Extract base URL up to /admin/ and get the secret key
                var baseUrlMatch = BASE_URL.match(/^(https?:\/\/[^\/]+)\/admin\//);
                var keyMatch = BASE_URL.match(/\/key\/([^\/]+)/);
                
                if (baseUrlMatch && keyMatch) {
                    var baseUrl = baseUrlMatch[1];
                    var secretKey = keyMatch[1];
                    // Construct: https://domain.com/admin/squadkin_squadexaai/aiproduct/createMagentoProduct/key/xxx/
                    url = baseUrl + '/admin/' + urlPath + '/key/' + secretKey + '/';
                } else {
                    // Fallback: use urlBuilder
                    url = urlBuilder.build(urlPath);
                }
            } else {
                // Fallback: use urlBuilder or construct manually
                url = urlBuilder.build(urlPath);
                // If still malformed, construct manually
                if (url.indexOf('squadkin_squadexaai/aiproduct/index') !== -1) {
                    var currentPath = window.location.pathname;
                    var keyMatch = currentPath.match(/\/key\/([^\/]+)/);
                    var secretKey = keyMatch ? keyMatch[1] : '';
                    url = window.location.origin + '/admin/' + urlPath + (secretKey ? '/key/' + secretKey + '/' : '');
                }
            }
            
            console.log('[Create Product Modal] Creating product...');
            console.log('[Create Product Modal] URL Path:', urlPath);
            console.log('[Create Product Modal] BASE_URL:', typeof BASE_URL !== 'undefined' ? BASE_URL : 'undefined');
            console.log('[Create Product Modal] Final URL:', url);
            console.log('[Create Product Modal] Data:', {
                aiproduct_id: this.aiproductId,
                product_type: productType
            });
            
            $.ajax({
                url: url,
                type: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                data: {
                    aiproduct_id: this.aiproductId,
                    product_type: productType,
                    form_key: $('input[name="form_key"]').val(),
                    isAjax: true
                },
                dataType: 'json',
                showLoader: true,
                success: function (response) {
                    console.log('[Create Product Modal] Response received:', response);
                    if (response && response.success && response.redirect_url) {
                        console.log('[Create Product Modal] Redirecting to:', response.redirect_url);
                        window.location.href = response.redirect_url;
                    } else {
                        var errorMsg = response && response.message ? response.message : $t('An error occurred while creating the product');
                        console.error('[Create Product Modal] Error in response:', response);
                        alert(errorMsg);
                    }
                },
                error: function (xhr, status, error) {
                    console.error('[Create Product Modal] AJAX error creating product:', error);
                    console.error('[Create Product Modal] HTTP Status:', xhr.status);
                    console.error('[Create Product Modal] Status Text:', xhr.statusText);
                    console.error('[Create Product Modal] Response Text:', xhr.responseText ? xhr.responseText.substring(0, 500) : 'No response');
                    console.error('[Create Product Modal] Request URL:', url);
                    
                    var errorMsg = $t('An error occurred while creating the product');
                    if (xhr.status === 404) {
                        errorMsg += ' (404 - Endpoint not found)';
                    } else if (xhr.status === 403) {
                        errorMsg += ' (403 - Access denied)';
                    } else if (xhr.status === 500) {
                        errorMsg += ' (500 - Server error)';
                    }
                    alert(errorMsg);
                }
            });
        }
    };

    // Expose immediately to window
    window.createProductFromAiModal = CreateProductModal;
    console.log('[Create Product Modal] Module loaded and exposed to window');

    // Initialize on page load
    $(document).ready(function () {
        console.log('[Create Product Modal] Document ready, initializing...');
        CreateProductModal.init();
    });

    // Also try to initialize immediately if DOM is ready
    if (document.readyState === 'loading') {
        console.log('[Create Product Modal] DOM still loading, waiting for DOMContentLoaded');
        document.addEventListener('DOMContentLoaded', function() {
            console.log('[Create Product Modal] DOMContentLoaded fired, initializing...');
            CreateProductModal.init();
        });
    } else {
        // DOM is already ready
        console.log('[Create Product Modal] DOM already ready, initializing immediately');
        CreateProductModal.init();
    }

    return CreateProductModal;
});
