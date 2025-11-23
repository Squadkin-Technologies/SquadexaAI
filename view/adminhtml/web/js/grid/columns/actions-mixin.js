/**
 * Copyright © 2024 Squadkin. All rights reserved.
 */
define([
    'jquery',
    'uiRegistry'
], function ($, registry) {
    'use strict';

    return function (ActionsColumn) {
        return ActionsColumn.extend({
            /**
             * Creates handler for the provided action if it's required.
             *
             * @param {Object} action - Action object.
             * @returns {Function|Undefined}
             */
            getActionHandler: function (action) {
                if (action && action.index === 'create_product') {
                    var rowIndex = action.rowIndex;
                    var self = this;
                    
                    return function() {
                        self.applyAction('create_product', rowIndex);
                        return false;
                    };
                }
                
                if (action && action.index === 'update_product') {
                    var rowIndex = action.rowIndex;
                    var self = this;
                    
                    return function() {
                        self.applyAction('update_product', rowIndex);
                        return false;
                    };
                }
                
                return this._super(action);
            },

            /**
             * Checks if specified action requires a handler function.
             *
             * @param {String} actionIndex - Actions' identifier.
             * @param {Number} rowIndex - Index of a row.
             * @returns {Boolean}
             */
            isHandlerRequired: function (actionIndex, rowIndex) {
                if (actionIndex === 'create_product' || actionIndex === 'update_product') {
                    return true;
                }
                
                return this._super(actionIndex, rowIndex);
            },

            /**
             * Applies specified action.
             * Intercept to handle our create_product action.
             *
             * @param {String} actionIndex - Actions' identifier.
             * @param {Number} rowIndex - Index of a row.
             * @returns {ActionsColumn} Chainable.
             */
            applyAction: function (actionIndex, rowIndex) {
                if (actionIndex === 'create_product') {
                    var action = this.getAction(rowIndex, actionIndex);
                    var recordId = null;
                    
                    if (action && action.recordId) {
                        recordId = action.recordId;
                    }
                    
                    if (!recordId && this.rows && Array.isArray(this.rows) && this.rows[rowIndex]) {
                        var row = this.rows[rowIndex];
                        if (row && row.aiproduct_id) {
                            recordId = row.aiproduct_id;
                        }
                    }
                    
                    if (!recordId && this.source && this.source.data && this.source.data.items) {
                        var item = this.source.data.items[rowIndex];
                        if (item && item.aiproduct_id) {
                            recordId = item.aiproduct_id;
                        }
                    }
                    
                    var aiproductId = parseInt(recordId, 10);

                    if (isNaN(aiproductId) || !aiproductId) {
                        alert('Unable to determine AI Product ID. Please refresh the page and try again.');
                        return this;
                    }

                    if (window.createProductFromAiModal) {
                        try {
                            if (typeof window.createProductFromAiModal.open === 'function') {
                                window.createProductFromAiModal.open(aiproductId);
                            }
                        } catch (e) {
                            // Silent fail
                        }
                    } else {
                        var self = this;
                        require(['Squadkin_SquadexaAI/js/create-product-modal'], function (modal) {
                            window.createProductFromAiModal = modal;
                            try {
                                if (typeof modal.open === 'function') {
                                    modal.open(aiproductId);
                                }
                            } catch (e) {
                                // Silent fail
                            }
                        });
                    }
                    return this;
                }
                
                if (actionIndex === 'update_product') {
                    var action = this.getAction(rowIndex, actionIndex);
                    var recordId = null;
                    var magentoProductId = null;
                    
                    var rowData = null;
                    if (this.rows && Array.isArray(this.rows) && this.rows[rowIndex]) {
                        rowData = this.rows[rowIndex];
                    } else if (this.source && this.source.data && this.source.data.items) {
                        rowData = this.source.data.items[rowIndex];
                    }
                    
                    if (rowData) {
                        if (rowData.aiproduct_id) {
                            recordId = rowData.aiproduct_id;
                        }
                        if (rowData.magento_product_id) {
                            magentoProductId = rowData.magento_product_id;
                        }
                    }
                    
                    if (!recordId && action && action.recordId) {
                        recordId = action.recordId;
                    }
                    
                    var aiproductId = parseInt(recordId, 10);
                    magentoProductId = parseInt(magentoProductId, 10) || null;
                    
                    if (isNaN(aiproductId) || !aiproductId) {
                        alert('Unable to determine AI Product ID. Please refresh the page and try again.');
                        return this;
                    }
                    
                    if (!magentoProductId || isNaN(magentoProductId)) {
                        alert('Unable to determine Magento Product ID. This product may not have been created in Magento yet.');
                        return this;
                    }
                    
                    if (!confirm('You will be redirected to the product edit page where you can review and apply the latest AI-generated data. Continue?')) {
                        return this;
                    }
                    
                    require(['mage/url'], function (urlBuilder) {
                        var currentUrl = window.location.href;
                        var baseUrlMatch = currentUrl.match(/^(https?:\/\/[^\/]+)/);
                        var baseUrl = baseUrlMatch ? baseUrlMatch[1] : window.location.origin;
                        
                        var keyMatch = currentUrl.match(/\/key\/([^\/\?]+)/);
                        var secretKey = keyMatch ? keyMatch[1] : '';
                        
                        var editUrl;
                        if (secretKey) {
                            editUrl = baseUrl + '/admin/catalog/product/edit/id/' + magentoProductId + '/key/' + secretKey + '/';
                        } else {
                            editUrl = urlBuilder.build('catalog/product/edit', {
                                id: magentoProductId
                            });
                            if (editUrl.indexOf('http') !== 0) {
                                editUrl = baseUrl + editUrl;
                            }
                        }
                        
                        var separator = editUrl.indexOf('?') !== -1 ? '&' : '?';
                        var redirectUrl = editUrl + separator + 'ai_data=' + encodeURIComponent(aiproductId);
                        
                        window.location.href = redirectUrl;
                    });
                    
                    return this;
                }

                return this._super(actionIndex, rowIndex);
            }
        });
    };
});

