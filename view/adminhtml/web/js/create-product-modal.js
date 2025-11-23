/**
 * Copyright © 2024 Squadkin. All rights reserved.
 */
define([
    'jquery',
    'Magento_Ui/js/modal/modal',
    'Magento_Ui/js/modal/alert',
    'mage/url',
    'mage/translate'
], function ($, modal, alert, urlBuilder, $t) {
    'use strict';

    var CreateProductModal = {
        aiproductId: null,
        productTypes: [],
        modalElement: null,

        /**
         * Initialize
         */
        init: function () {
            var self = this;
            this.modalElement = $('#create-product-from-ai-modal');
            
            if (!this.modalElement.length) {
                return;
            }

            // Initialize modal
            var modalOptions = {
                type: 'popup',
                responsive: true,
                innerScroll: true,
                title: $t('Create Product from AI Data'),
                buttons: [{
                    text: $t('Cancel'),
                    class: 'action-secondary',
                    click: function () {
                        self.modalElement.modal('closeModal');
                    }
                }, {
                    text: $t('Create Product'),
                    class: 'action-primary',
                    click: function () {
                        self.createProduct();
                    }
                }]
            };

            if (!this.modalElement.data('mageModal')) {
                modal(modalOptions, this.modalElement);
            } else {
            }

            // Load product types
            this.loadProductTypes();
        },

        /**
         * Open modal
         */
        open: function (aiproductId) {
            var self = this;
            this.aiproductId = aiproductId;
            
            // Ensure modal element exists
            if (!this.modalElement || !this.modalElement.length) {
                this.modalElement = $('#create-product-from-ai-modal');
                if (!this.modalElement.length) {
                    // Try to wait a bit for DOM to be ready
                    setTimeout(function() {
                        self.modalElement = $('#create-product-from-ai-modal');
                        if (self.modalElement.length) {
                            self.init();
                            self.open(aiproductId);
                        } else {
                        }
                    }, 500);
                    return;
                }
                this.init();
            } else {
            }

            // Reset form
            $('#product-type-select').val('');

            // Populate product types
            this.populateProductTypes();

            // Open modal
            try {
                this.modalElement.modal('openModal');
            } catch (e) {
                // Try to reinitialize modal
                this.modalElement.data('mageModal', null);
                this.init();
                setTimeout(function() {
                    try {
                        self.modalElement.modal('openModal');
                    } catch (e2) {
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
                    if (response && response.success && response.redirect_url) {
                        window.location.href = response.redirect_url;
                    } else {
                        var errorMsg = response && response.message ? response.message : $t('An error occurred while creating the product');
                        alert({
                            title: $t('Error'),
                            content: errorMsg,
                            modalClass: 'alert'
                        });
                    }
                },
                error: function (xhr, status, error) {
                    
                    var errorMsg = $t('An error occurred while creating the product');
                    if (xhr.status === 404) {
                        errorMsg += ' (404 - Endpoint not found)';
                    } else if (xhr.status === 403) {
                        errorMsg += ' (403 - Access denied)';
                    } else if (xhr.status === 500) {
                        // Try to parse error message from response
                        try {
                            var responseData = JSON.parse(xhr.responseText);
                            if (responseData && responseData.message) {
                                errorMsg = responseData.message;
                            }
                        } catch (e) {
                            errorMsg += ' (500 - Server error)';
                        }
                    }
                    alert({
                        title: $t('Error'),
                        content: errorMsg,
                        modalClass: 'alert'
                    });
                }
            });
        }
    };

    // Expose immediately to window
    window.createProductFromAiModal = CreateProductModal;

    // Initialize on page load
    $(document).ready(function () {
        CreateProductModal.init();
    });

    // Also try to initialize immediately if DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            CreateProductModal.init();
        });
    } else {
        // DOM is already ready
        CreateProductModal.init();
    }

    return CreateProductModal;
});
