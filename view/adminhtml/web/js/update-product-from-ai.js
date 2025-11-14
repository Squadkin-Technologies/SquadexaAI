/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
define([
    'jquery',
    'uiComponent',
    'Magento_Ui/js/modal/modal',
    'mage/url',
    'mage/translate'
], function ($, Component, modal, urlBuilder, $t) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Squadkin_SquadexaAI/update-product-from-ai',
            modalOptions: {
                type: 'popup',
                responsive: true,
                innerScroll: true,
                title: $t('Update Product from AI'),
                buttons: [{
                    text: $t('Cancel'),
                    class: 'action-secondary',
                    click: function () {
                        this.closeModal();
                    }
                }, {
                    text: $t('Update Product'),
                    class: 'action-primary',
                    click: function () {
                        this.updateProduct();
                    }
                }]
            }
        },

        /**
         * Initialize
         */
        initialize: function () {
            this._super();
            this.createModal();
            return this;
        },

        /**
         * Create modal
         */
        createModal: function () {
            var self = this;
            var modalElement = $('#update-product-from-ai-modal');
            
            if (modalElement.length && !modalElement.data('mageModal')) {
                modal(this.modalOptions, modalElement);
            }
        },

        /**
         * Open modal
         */
        open: function (productId) {
            var self = this;
            var modalElement = $('#update-product-from-ai-modal');
            this.productId = productId;
            
            if (modalElement.length) {
                modalElement.modal('openModal');
                this.loadAiProducts();
            }
        },

        /**
         * Load AI products
         */
        loadAiProducts: function () {
            var self = this;
            $.ajax({
                url: urlBuilder.build('squadkin_squadexaai/aiproduct/index'),
                type: 'GET',
                dataType: 'json',
                showLoader: true,
                success: function (response) {
                    // This would need to be implemented as an API endpoint
                    // For now, we'll use a simpler approach
                }
            });
        },

        /**
         * Load AI product data
         */
        loadAiProductData: function (aiProductId) {
            var self = this;
            $.ajax({
                url: urlBuilder.build('squadkin_squadexaai/product/updateFromAi'),
                type: 'GET',
                data: {
                    id: this.productId,
                    ai_product_id: aiProductId,
                    action: 'get_data'
                },
                dataType: 'json',
                showLoader: true,
                success: function (response) {
                    if (response.success) {
                        self.aiProductData = response.ai_product;
                        self.renderAiProductForm();
                    }
                }
            });
        },

        /**
         * Render AI product form
         */
        renderAiProductForm: function () {
            // This would render the AI product data in an editable form
            // Implementation depends on the form structure
        },

        /**
         * Update product
         */
        updateProduct: function () {
            var self = this;
            var aiProductId = $('#ai-product-select').val();
            var mappingId = $('#mapping-profile-select').val();

            if (!aiProductId) {
                alert($t('Please select an AI product'));
                return;
            }

            // Get edited AI data from form
            var aiData = this.getAiDataFromForm();

            if (!confirm($t('Do you want to replace the existing product data with AI data?'))) {
                return;
            }

            $.ajax({
                url: urlBuilder.build('squadkin_squadexaai/product/updateFromAi'),
                type: 'POST',
                data: {
                    id: this.productId,
                    ai_product_id: aiProductId,
                    mapping_id: mappingId,
                    ai_data: JSON.stringify(aiData),
                    action: 'update',
                    form_key: $('input[name="form_key"]').val()
                },
                dataType: 'json',
                showLoader: true,
                success: function (response) {
                    if (response.success) {
                        alert(response.message || $t('Product has been updated successfully'));
                        location.reload();
                    } else {
                        alert(response.message || $t('An error occurred while updating the product'));
                    }
                },
                error: function () {
                    alert($t('An error occurred while updating the product'));
                }
            });
        },

        /**
         * Get AI data from form
         */
        getAiDataFromForm: function () {
            // Extract data from form fields
            return {
                product_name: $('#ai-product-name').val(),
                meta_title: $('#ai-meta-title').val(),
                meta_description: $('#ai-meta-description').val(),
                short_description: $('#ai-short-description').val(),
                description: $('#ai-description').val()
            };
        }
    });
});

