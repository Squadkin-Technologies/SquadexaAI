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
            template: 'Squadkin_SquadexaAI/create-product-modal',
            productTypes: [],
            attributeSets: [],
            mappingProfiles: [],
            aiproductId: null,
            modalOptions: {
                type: 'popup',
                responsive: true,
                innerScroll: true,
                title: $t('Create Product from AI'),
                buttons: [{
                    text: $t('Cancel'),
                    class: 'action-secondary',
                    click: function () {
                        $('#create-product-from-ai-modal').modal('closeModal');
                    }
                }, {
                    text: $t('Create Product'),
                    class: 'action-primary',
                    click: function () {
                        if (window.createProductFromAiModal) {
                            window.createProductFromAiModal.createProduct();
                        }
                    }
                }]
            }
        },

        /**
         * Initialize
         */
        initialize: function () {
            this._super();
            var self = this;
            
            // Expose globally for button clicks
            window.createProductFromAiModal = this;
            
            // Initialize observables
            this.productTypes = this.productTypes || [];
            this.attributeSets = this.attributeSets || [];
            this.mappingProfiles = this.mappingProfiles || [];
            
            return this;
        },

        /**
         * Create modal
         */
        createModal: function () {
            var self = this;
            var modalElement = $('#create-product-from-ai-modal');
            
            if (modalElement.length && !modalElement.data('mageModal')) {
                modal(this.modalOptions, modalElement);
            }
        },

        /**
         * Open modal
         */
        open: function (aiproductId) {
            var self = this;
            var modalElement = $('#create-product-from-ai-modal');
            this.aiproductId = aiproductId || this.aiproductId;
            
            if (modalElement.length) {
                // Initialize if not already done
                if (!modalElement.data('mageModal')) {
                    this.createModal();
                }
                
                // Load data and populate selects
                this.loadProductTypes();
                this.loadAttributeSets();
                this.loadMappingProfiles();
                
                // Populate product types select
                var productTypeSelect = $('#product-type-select');
                productTypeSelect.empty().append('<option value="">-- Please Select --</option>');
                $.each(this.productTypes, function(index, item) {
                    productTypeSelect.append('<option value="' + item.value + '">' + item.label + '</option>');
                });
                
                // Open modal
                modalElement.modal('openModal');
            }
        },

        /**
         * Load product types
         */
        loadProductTypes: function () {
            // Product types are typically static, but can be loaded from config
            if (!this.productTypes || this.productTypes.length === 0) {
                this.productTypes = [
                    { value: 'simple', label: $t('Simple Product') },
                    { value: 'configurable', label: $t('Configurable Product') },
                    { value: 'virtual', label: $t('Virtual Product') },
                    { value: 'downloadable', label: $t('Downloadable Product') },
                    { value: 'bundle', label: $t('Bundle Product') },
                    { value: 'grouped', label: $t('Grouped Product') }
                ];
            }
        },

        /**
         * Load attribute sets
         */
        loadAttributeSets: function () {
            var self = this;
            if (!this.attributeSets || this.attributeSets.length === 0) {
                $.ajax({
                    url: urlBuilder.build('squadkin_squadexaai/aiproduct/getAttributeSets'),
                    type: 'GET',
                    dataType: 'json',
                    showLoader: true,
                    success: function (response) {
                        if (response.success && response.attribute_sets) {
                            self.attributeSets = response.attribute_sets;
                            // Update select options
                            var select = $('#attribute-set-select');
                            select.empty().append('<option value="">-- Please Select --</option>');
                            $.each(response.attribute_sets, function(index, item) {
                                select.append('<option value="' + item.value + '">' + item.label + '</option>');
                            });
                        }
                    }
                });
            }
        },

        /**
         * Load mapping profiles
         */
        loadMappingProfiles: function () {
            // This would load from an API endpoint
            // For now, set empty array
            if (!this.mappingProfiles) {
                this.mappingProfiles = [];
            }
        },

        /**
         * Create product
         */
        createProduct: function () {
            var self = this;
            var productType = $('#product-type-select').val();
            var attributeSetId = $('#attribute-set-select').val();
            var mappingId = $('#mapping-profile-select').val();

            if (!productType) {
                alert($t('Please select a product type'));
                return;
            }

            $.ajax({
                url: urlBuilder.build('squadkin_squadexaai/aiproduct/createMagentoProduct'),
                type: 'POST',
                data: {
                    aiproduct_id: this.aiproductId,
                    product_type: productType,
                    attribute_set_id: attributeSetId,
                    mapping_id: mappingId,
                    form_key: $('input[name="form_key"]').val()
                },
                dataType: 'json',
                showLoader: true,
                success: function (response) {
                    if (response.success && response.redirect_url) {
                        window.location.href = response.redirect_url;
                    } else {
                        alert(response.message || $t('An error occurred while creating the product'));
                    }
                },
                error: function () {
                    alert($t('An error occurred while creating the product'));
                }
            });
        },

        /**
         * Get template
         */
        getTemplate: function () {
            return 'Squadkin_SquadexaAI/create-product-modal';
        }
    });
});

