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
                // Force handler for our create_product action
                if (actionIndex === 'create_product') {
                    console.log('[AI Product Actions] isHandlerRequired: true for create_product');
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

                // For other actions, use the default behavior
                console.log('[AI Product Actions] Using default applyAction for:', actionIndex);
                return this._super(actionIndex, rowIndex);
            }
        });
    };
});

