/**
 * Copyright © 2024 Squadkin. All rights reserved.
 */
define([
    'jquery',
    'uiRegistry'
], function ($, registry) {
    'use strict';

    console.log('[AI Product Actions] Mixin loaded');

    return function (ActionsColumn) {
        console.log('[AI Product Actions] Extending ActionsColumn');

        return ActionsColumn.extend({
            /**
             * Creates handler for the provided action if it's required.
             * Override to ensure our create_product action gets a handler.
             *
             * @param {Object} action - Action object.
             * @returns {Function|Undefined}
             */
            getActionHandler: function (action) {
                console.log('[AI Product Actions] getActionHandler called', {
                    action: action,
                    actionIndex: action ? action.index : 'N/A'
                });
                
                // Handle our create_product action
                if (action && action.index === 'create_product') {
                    console.log('[AI Product Actions] Creating custom handler for create_product');
                    var rowIndex = action.rowIndex;
                    var self = this;
                    
                    // Return a function that matches Magento's pattern
                    // We don't need to handle the event - just call applyAction and return false
                    // Knockout will handle preventing default navigation when we return false
                    return function() {
                        console.log('[AI Product Actions] Custom handler executed for create_product');
                        console.log('[AI Product Actions] Arguments count:', arguments.length);
                        
                        // Call applyAction - this is what matters
                        self.applyAction('create_product', rowIndex);
                        
                        // Return false to prevent default navigation (Knockout will respect this)
                        // This is sufficient - we don't need to call preventDefault manually
                        return false;
                    };
                }
                
                // Handle our update_product action
                if (action && action.index === 'update_product') {
                    console.log('[AI Product Actions] Creating custom handler for update_product');
                    var rowIndex = action.rowIndex;
                    var self = this;
                    
                    return function() {
                        console.log('[AI Product Actions] Custom handler executed for update_product');
                        self.applyAction('update_product', rowIndex);
                        return false;
                    };
                }
                
                // Use default behavior for other actions
                var result = this._super(action);
                console.log('[AI Product Actions] getActionHandler result:', result);
                return result;
            },

            /**
             * Checks if specified action requires a handler function.
             * Override to ensure our create_product action uses handler.
             *
             * @param {String} actionIndex - Actions' identifier.
             * @param {Number} rowIndex - Index of a row.
             * @returns {Boolean}
             */
            isHandlerRequired: function (actionIndex, rowIndex) {
                // Force handler for our create_product and update_product actions
                if (actionIndex === 'create_product' || actionIndex === 'update_product') {
                    console.log('[AI Product Actions] isHandlerRequired: true for', actionIndex);
                    return true;
                }
                
                // Use default behavior for other actions
                var result = this._super(actionIndex, rowIndex);
                console.log('[AI Product Actions] isHandlerRequired:', result, 'for action:', actionIndex);
                return result;
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
                console.log('[AI Product Actions] applyAction called', {
                    actionIndex: actionIndex,
                    rowIndex: rowIndex
                });

                // Handle our custom "create_product" action
                if (actionIndex === 'create_product') {
                    console.log('[AI Product Actions] Handling create_product action');
                    
                    var action = this.getAction(rowIndex, actionIndex);
                    console.log('[AI Product Actions] Action object:', action);
                    console.log('[AI Product Actions] Action recordId:', action ? action.recordId : 'N/A');
                    
                    var recordId = null;
                    
                    // Try multiple ways to get the aiproduct_id
                    // Method 1: From action.recordId (primary method - this is set in AiProductActions.php)
                    if (action && action.recordId) {
                        recordId = action.recordId;
                        console.log('[AI Product Actions] Got recordId from action.recordId:', recordId);
                    }
                    
                    // Method 2: From row data (rows is an array property, not a function)
                    if (!recordId && this.rows && Array.isArray(this.rows) && this.rows[rowIndex]) {
                        var row = this.rows[rowIndex];
                        console.log('[AI Product Actions] Row object:', row);
                        if (row && row.aiproduct_id) {
                            recordId = row.aiproduct_id;
                            console.log('[AI Product Actions] Found aiproduct_id from row:', recordId);
                        }
                    }
                    
                    // Method 3: From data source using row index (fallback)
                    if (!recordId && this.source && this.source.data && this.source.data.items) {
                        var item = this.source.data.items[rowIndex];
                        console.log('[AI Product Actions] Item from dataSource:', item);
                        if (item && item.aiproduct_id) {
                            recordId = item.aiproduct_id;
                            console.log('[AI Product Actions] Found aiproduct_id from dataSource:', recordId);
                        }
                    }
                    
                    var aiproductId = parseInt(recordId, 10);
                    console.log('[AI Product Actions] Final recordId:', recordId);
                    console.log('[AI Product Actions] Parsed aiproductId:', aiproductId);

                    if (isNaN(aiproductId) || !aiproductId) {
                        console.error('[AI Product Actions] Invalid aiproductId. Original recordId:', recordId);
                        console.error('[AI Product Actions] Row index:', rowIndex);
                        console.error('[AI Product Actions] Action:', action);
                        alert('Unable to determine AI Product ID. Please refresh the page and try again.');
                        return this;
                    }

                    // Check if modal is available
                    if (window.createProductFromAiModal) {
                        console.log('[AI Product Actions] Modal found on window, opening...');
                        console.log('[AI Product Actions] Modal object:', window.createProductFromAiModal);
                        try {
                            if (typeof window.createProductFromAiModal.open === 'function') {
                                window.createProductFromAiModal.open(aiproductId);
                                console.log('[AI Product Actions] Modal open() called successfully');
                            } else {
                                console.error('[AI Product Actions] Modal open() method not found');
                            }
                        } catch (e) {
                            console.error('[AI Product Actions] Error opening modal:', e);
                            console.error('[AI Product Actions] Error stack:', e.stack);
                        }
                    } else {
                        console.warn('[AI Product Actions] Modal not found on window, trying to load...');
                        // Try to load the modal
                        var self = this;
                        require(['Squadkin_SquadexaAI/js/create-product-modal'], function (modal) {
                            console.log('[AI Product Actions] Modal loaded via require');
                            console.log('[AI Product Actions] Modal object:', modal);
                            window.createProductFromAiModal = modal;
                            try {
                                if (typeof modal.open === 'function') {
                                    modal.open(aiproductId);
                                    console.log('[AI Product Actions] Modal open() called after require');
                                } else {
                                    console.error('[AI Product Actions] Modal open() method not found after require');
                                }
                            } catch (e) {
                                console.error('[AI Product Actions] Error opening modal after require:', e);
                                console.error('[AI Product Actions] Error stack:', e.stack);
                            }
                        }, function (err) {
                            console.error('[AI Product Actions] Failed to load modal module:', err);
                        });
                    }
                    return this;
                }
                
                // Handle our custom "update_product" action
                // Reuse the same flow as "create product" - redirect to product edit page with ai_data parameter
                if (actionIndex === 'update_product') {
                    console.log('[AI Product Actions] Handling update_product action');
                    
                    var action = this.getAction(rowIndex, actionIndex);
                    var recordId = null;
                    var magentoProductId = null;
                    
                    // Try multiple ways to get the aiproduct_id and magento_product_id
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
                    
                    // Fallback: try action object
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
                    
                    // Confirm update
                    if (!confirm('You will be redirected to the product edit page where you can review and apply the latest AI-generated data. Continue?')) {
                        return this;
                    }
                    
                    // Redirect to product edit page with ai_data parameter (same as create flow)
                    require(['mage/url'], function (urlBuilder) {
                        // Extract base URL and secret key from current page
                        var currentUrl = window.location.href;
                        var baseUrlMatch = currentUrl.match(/^(https?:\/\/[^\/]+)/);
                        var baseUrl = baseUrlMatch ? baseUrlMatch[1] : window.location.origin;
                        
                        // Extract secret key from current URL
                        var keyMatch = currentUrl.match(/\/key\/([^\/\?]+)/);
                        var secretKey = keyMatch ? keyMatch[1] : '';
                        
                        // Build absolute URL to product edit page
                        var editUrl;
                        if (secretKey) {
                            editUrl = baseUrl + '/admin/catalog/product/edit/id/' + magentoProductId + '/key/' + secretKey + '/';
                        } else {
                            // Fallback: use urlBuilder (might be relative, but better than nothing)
                            editUrl = urlBuilder.build('catalog/product/edit', {
                                id: magentoProductId
                            });
                            // If it's relative, make it absolute
                            if (editUrl.indexOf('http') !== 0) {
                                editUrl = baseUrl + editUrl;
                            }
                        }
                        
                        // Add ai_data as query parameter
                        var separator = editUrl.indexOf('?') !== -1 ? '&' : '?';
                        var redirectUrl = editUrl + separator + 'ai_data=' + encodeURIComponent(aiproductId);
                        
                        console.log('[AI Product Actions] Redirecting to product edit page:', redirectUrl);
                        console.log('[AI Product Actions] Magento Product ID:', magentoProductId);
                        console.log('[AI Product Actions] AI Product ID:', aiproductId);
                        
                        // Redirect to product edit page
                        // The existing product-form-ai-data-loader.js will automatically load the AI data
                        window.location.href = redirectUrl;
                    });
                    
                    return this;
                }

                // For other actions, use the default behavior
                console.log('[AI Product Actions] Using default applyAction for:', actionIndex);
                return this._super(actionIndex, rowIndex);
            }
        });
    };
});

