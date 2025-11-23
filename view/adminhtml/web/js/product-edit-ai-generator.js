/**
 * Copyright © 2024 Squadkin. All rights reserved.
 */
define([
    'jquery',
    'Magento_Ui/js/modal/modal',
    'mage/url',
    'mage/translate',
    'mage/loader',
    'uiRegistry'
], function ($, modal, urlBuilder, $t, loader, registry) {
    'use strict';

    return {
        config: null,
        modalInstance: null,
        currentMappedData: null,
        descriptionUpdateStatus: {
            attempted: false,
            updated: false
        },

        /**
         * Initialize the AI generator
         */
        init: function (config) {
            this.config = config;
            this.createModal();
            this.attachButtonHandler();
        },

        /**
         * Create modal instance
         */
        createModal: function () {
            var self = this;
            var modalElement = $('#squadexa-ai-generator-modal');

            if (modalElement.length && !modalElement.data('mageModal')) {
                var modalOptions = {
                    type: 'popup',
                    responsive: true,
                    innerScroll: true,
                    title: $t('Generate/Edit Squadexa AI Data'),
                    buttons: [] // Buttons will be managed dynamically via updateModalButtons
                };

                this.modalInstance = modal(modalOptions, modalElement);
                
                // Initialize buttons for input step
                this.updateModalButtons('input');
            }
        },

        /**
         * Attach click handler to button
         */
        attachButtonHandler: function () {
            var self = this;
            
            // Wait for page to load, then find and attach to our button
            $(document).on('click', '[data-ui-id="squadexa-ai-generate-button"]', function (e) {
                e.preventDefault();
                self.openModal();
            });

            // Also support programmatic opening
            $(document).ready(function () {
                // If button exists, attach handler
                var button = $('[data-ui-id="squadexa-ai-generate-button"]');
                if (button.length) {
                    button.on('click', function (e) {
                        e.preventDefault();
                        self.openModal();
                    });
                }
            });
        },

        /**
         * Open modal
         */
        openModal: function () {
            var modalElement = $('#squadexa-ai-generator-modal');
            if (modalElement.length) {
                modalElement.modal('openModal');
                this.clearMessages();
                this.resetToInputStep();
            }
        },

        /**
         * Reset modal to input step
         */
        resetToInputStep: function () {
            $('#squadexa-ai-input-section').show();
            $('#squadexa-ai-review-section').hide();
            $('#squadexa-ai-description-copy-section').hide();
            // Update modal buttons
            this.updateModalButtons('input');
        },

        /**
         * Show review step
         */
        showReviewStep: function () {
            $('#squadexa-ai-input-section').hide();
            $('#squadexa-ai-review-section').show();
            // Update modal buttons
            this.updateModalButtons('review');
        },

        /**
         * Update modal buttons based on step
         */
        updateModalButtons: function (step) {
            var modalElement = $('#squadexa-ai-generator-modal');
            var modalWrapper = modalElement.closest('.modal-popup');
            var self = this;

            // Ensure modal wrapper exists
            if (!modalWrapper.length) {
                // Try to find it another way
                modalWrapper = $('.modal-popup').has(modalElement);
                if (!modalWrapper.length) {
                    // Modal might not be fully initialized yet, try again after a short delay
                    setTimeout(function() {
                        self.updateModalButtons(step);
                    }, 100);
                    return;
                }
            }

            // Remove existing action buttons (including any with data-dismiss attribute)
            modalWrapper.find('.modal-footer .action-secondary, .modal-footer .action-primary, .modal-footer button[data-dismiss]').remove();
            // Also remove any buttons that might have been added by Magento's modal system
            modalWrapper.find('.modal-footer button').remove();

            if (step === 'input') {
                // Input step buttons
                var footer = modalWrapper.find('.modal-footer');
                if (footer.length === 0) {
                    footer = $('<div class="modal-footer"></div>');
                    modalWrapper.find('.modal-content').append(footer);
                }
                
                footer.html(
                    '<button type="button" class="action-secondary" id="squadexa-cancel-btn">' + $t('Cancel') + '</button>' +
                    '<button type="button" class="action-primary" id="squadexa-generate-btn">' + $t('Generate AI Data') + '</button>'
                );
                
                // Attach handlers - use off() first to remove any existing handlers
                footer.find('#squadexa-cancel-btn').off('click').on('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    var $modal = $('#squadexa-ai-generator-modal');
                    if ($modal.length && $modal.data('mageModal')) {
                        $modal.modal('closeModal');
                    }
                });
                
                footer.find('#squadexa-generate-btn').off('click').on('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    self.generateAndApplyAiData();
                });
            } else if (step === 'review') {
                // Review step buttons
                var footer = modalWrapper.find('.modal-footer');
                if (footer.length === 0) {
                    footer = $('<div class="modal-footer"></div>');
                    modalWrapper.find('.modal-content').append(footer);
                }
                
                footer.html(
                    '<button type="button" class="action-secondary" id="squadexa-back-btn">' + $t('Back') + '</button>' +
                    '<button type="button" class="action-primary" id="squadexa-apply-btn">' + $t('Apply to Product') + '</button>'
                );
                
                // Attach handlers
                footer.find('#squadexa-back-btn').on('click', function () {
                    self.resetToInputStep();
                });
                
                footer.find('#squadexa-apply-btn').on('click', function () {
                    if (self.currentMappedData) {
                        self.applyMappedDataToForm(self.currentMappedData);
                    }
                });
            } else if (step === 'description-copy') {
                // Description copy step buttons
                var footer = modalWrapper.find('.modal-footer');
                if (footer.length === 0) {
                    footer = $('<div class="modal-footer"></div>');
                    modalWrapper.find('.modal-content').append(footer);
                }
                
                footer.html(
                    '<button type="button" class="action-primary" id="squadexa-close-after-copy-btn">' + $t('Close') + '</button>'
                );
                
                // Attach handler
                footer.find('#squadexa-close-after-copy-btn').on('click', function () {
                    $('#squadexa-ai-generator-modal').modal('closeModal');
                });
            }
        },

        /**
         * Clear messages
         */
        clearMessages: function () {
            $('#squadexa-ai-generator-message').empty();
        },

        /**
         * Show message
         */
        showMessage: function (message, type) {
            type = type || 'error';
            var messageHtml = '<div class="message message-' + type + ' ' + type + '">' +
                '<div data-ui-id="message-' + type + '">' + message + '</div>' +
                '</div>';
            $('#squadexa-ai-generator-message').html(messageHtml);
        },

        /**
         * Clear messages
         */
        clearMessages: function () {
            $('#squadexa-ai-generator-message').html('');
        },

        /**
         * Generate AI data (existing flow - saves to database)
         * Keep this for backward compatibility and other flows
         */
        generateAiData: function () {
            var self = this;
            var productName = $('#squadexa_ai_product_name').val().trim();
            var primaryKeywords = $('#squadexa_ai_primary_keywords').val().trim();
            var secondaryKeywords = $('#squadexa_ai_secondary_keywords').val().trim();
            var includePricing = $('#squadexa_ai_include_pricing').is(':checked');

            // Validation - Product Name is required
            if (!productName) {
                this.showMessage($t('Product name is required.'), 'error');
                $('#squadexa_ai_product_name').focus();
                return;
            }

            // Validation - Primary Keywords is required
            if (!primaryKeywords) {
                this.showMessage($t('Primary keywords are required.'), 'error');
                $('#squadexa_ai_primary_keywords').focus();
                return;
            }

            // Show loading
            this.showMessage($t('Generating AI data... Please wait.'), 'notice');
            $('body').loader('show');

            // Prepare data
            var requestData = {
                product_name: productName,
                primary_keywords: primaryKeywords,
                secondary_keywords: secondaryKeywords || '',
                include_pricing: includePricing ? 1 : 0,
                product_id: this.config.productId
            };

            // Make AJAX request to existing controller (saves to database)
            $.ajax({
                url: urlBuilder.build('squadkin_squadexaai/product/generateAiData'),
                type: 'POST',
                data: requestData,
                dataType: 'json',
                showLoader: true,
                success: function (response) {
                    $('body').loader('hide');
                    
                    if (response.success) {
                        self.showMessage(
                            $t('AI data generated successfully! You can now review and apply it to the product.'),
                            'success'
                        );
                        
                        // If response contains AI product ID, we can optionally update the product
                        if (response.ai_product_id) {
                            // Show option to apply to product
                            setTimeout(function () {
                                if (confirm($t('Do you want to apply the generated AI data to this product now?'))) {
                                    self.applyAiDataToProduct(response.ai_product_id);
                                }
                            }, 1000);
                        }
                    } else {
                        self.showMessage(
                            response.message || $t('An error occurred while generating AI data.'),
                            'error'
                        );
                    }
                },
                error: function (xhr, status, error) {
                    $('body').loader('hide');
                    var errorMessage = $t('An error occurred while generating AI data.');
                    
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }
                    
                    self.showMessage(errorMessage, 'error');
                }
            });
        },

        /**
         * Generate and Apply AI Data (NEW FLOW - no database save, applies directly to form)
         * This is the new flow for product edit page
         */
        generateAndApplyAiData: function () {
            var self = this;
            var productName = $('#squadexa_ai_product_name').val().trim();
            var primaryKeywords = $('#squadexa_ai_primary_keywords').val().trim();
            var secondaryKeywords = $('#squadexa_ai_secondary_keywords').val().trim();
            var includePricing = $('#squadexa_ai_include_pricing').is(':checked');

            // Validation - Product Name is required
            if (!productName) {
                this.showMessage($t('Product name is required.'), 'error');
                $('#squadexa_ai_product_name').focus();
                return;
            }

            // Validation - Primary Keywords is required
            if (!primaryKeywords) {
                this.showMessage($t('Primary keywords are required.'), 'error');
                $('#squadexa_ai_primary_keywords').focus();
                return;
            }

            // Show loading
            this.showMessage($t('Generating AI data... Please wait.'), 'notice');
            $('body').loader('show');

            // Prepare data
            var requestData = {
                product_name: productName,
                primary_keywords: primaryKeywords,
                secondary_keywords: secondaryKeywords || '',
                include_pricing: includePricing ? 1 : 0,
                product_id: this.config.productId
            };

            // Make AJAX request to new controller (no database save)
            // Build URL correctly - extract base URL and secret key from current page
            var BASE_URL = window.location.href;
            var urlPath = 'squadkin_squadexaai/product/generateAndApplyAiData';
            var ajaxUrl = '';
            
            // Extract secret key from current URL
            var keyMatch = BASE_URL.match(/\/key\/([^\/\?]+)/);
            var secretKey = keyMatch ? keyMatch[1] : '';
            
            if (secretKey) {
                // Build URL with secret key
                var baseUrl = window.location.origin;
                ajaxUrl = baseUrl + '/admin/' + urlPath + '/key/' + secretKey + '/';
            } else {
                // Fallback to urlBuilder
                ajaxUrl = urlBuilder.build(urlPath);
            }
            
            
            $.ajax({
                url: ajaxUrl,
                type: 'POST',
                data: $.extend(requestData, {
                    form_key: $('input[name="form_key"]').val(),
                    isAjax: true
                }),
                dataType: 'json',
                showLoader: true,
                success: function (response) {
                    $('body').loader('hide');
                    
                    if (response.success && response.mapped_data) {
                        // Clear loading message
                        self.clearMessages();
                        
                        if (response.raw_data && response.raw_data.pricing && (!response.mapped_data.price || response.mapped_data.price === '')) {
                            var fallbackPrice = self.extractPriceFromPricing(response.raw_data.pricing);
                            if (fallbackPrice) {
                                response.mapped_data.price = fallbackPrice;
                            }
                        }
                        // Store mapped data for later use
                        self.currentMappedData = response.mapped_data;
                        // Show review section in same modal
                        self.showReviewInModal(response.mapped_data, response.raw_data);
                    } else {
                        self.showMessage(
                            response.message || $t('An error occurred while generating AI data.'),
                            'error'
                        );
                    }
                },
                error: function (xhr, status, error) {
                    $('body').loader('hide');
                    var errorMessage = $t('An error occurred while generating AI data.');
                    
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    } else if (xhr.status === 404) {
                        errorMessage = $t('Controller not found. Please check the URL configuration.');
                    }
                    
                    self.showMessage(errorMessage, 'error');
                }
            });
        },

        /**
         * Apply AI data to product (existing flow - uses saved AI product ID)
         */
        applyAiDataToProduct: function (aiProductId) {
            var self = this;
            
            $('body').loader('show');
            
            $.ajax({
                url: urlBuilder.build('squadkin_squadexaai/product/applyAiData'),
                type: 'POST',
                data: {
                    product_id: this.config.productId,
                    ai_product_id: aiProductId,
                    form_key: $('input[name="form_key"]').val()
                },
                dataType: 'json',
                showLoader: true,
                success: function (response) {
                    $('body').loader('hide');
                    
                    if (response.success) {
                        self.showMessage(
                            response.message || $t('AI data has been applied to the product successfully!'),
                            'success'
                        );
                        
                        // Reload page after 2 seconds to show updated data
                        setTimeout(function () {
                            window.location.reload();
                        }, 2000);
                    } else {
                        self.showMessage(
                            response.message || $t('An error occurred while applying AI data.'),
                            'error'
                        );
                    }
                },
                error: function () {
                    $('body').loader('hide');
                    self.showMessage(
                        $t('An error occurred while applying AI data.'),
                        'error'
                    );
                }
            });
        },

        /**
         * Show review section in the same modal
         */
        showReviewInModal: function (mappedData, rawData) {
            var self = this;
            
            // Clear any existing messages (including loading message)
            this.clearMessages();
            
            var reviewContent = '<div class="admin__scope-old">';
            reviewContent += '<div class="admin__data-grid-wrap" style="max-height: 400px; overflow-y: auto;">';
            reviewContent += '<h3 style="margin-top: 0;">' + $t('Review Generated AI Data') + '</h3>';
            reviewContent += '<p class="note">' + $t('Please review the generated data before applying it to the product form. All mapped fields will be applied.') + '</p>';
            reviewContent += '<table class="data-grid" style="width: 100%; border-collapse: collapse;">';
            reviewContent += '<thead><tr style="background-color: #f5f5f5;"><th style="width: 30%; padding: 10px; border: 1px solid #ddd;">' + $t('Field') + '</th><th style="width: 70%; padding: 10px; border: 1px solid #ddd;">' + $t('Value') + '</th></tr></thead>';
            reviewContent += '<tbody>';
            
            // Show all mapped data in natural order - no truncation, show full values
            var hasData = false;
            Object.keys(mappedData).forEach(function (attributeCode) {
                var value = mappedData[attributeCode];
                if (value !== null && value !== '') {
                    hasData = true;
                    // Show full value - no truncation
                    var displayValue = String(value);
                    // For long text, use a scrollable div
                    var cellStyle = 'style="padding: 10px; border: 1px solid #ddd; vertical-align: top;';
                    if (displayValue.length > 200) {
                        cellStyle += ' max-height: 150px; overflow-y: auto; word-wrap: break-word;';
                    } else {
                        cellStyle += ' word-wrap: break-word;';
                    }
                    cellStyle += '"';
                    
                    reviewContent += '<tr>';
                    reviewContent += '<td style="padding: 10px; border: 1px solid #ddd; vertical-align: top; background-color: #fafafa;"><strong>' + self.formatAttributeCode(attributeCode) + '</strong></td>';
                    reviewContent += '<td ' + cellStyle + '>' + self.escapeHtml(displayValue) + '</td>';
                    reviewContent += '</tr>';
                }
            });
            
            // If no mapped data, show message
            if (!hasData) {
                reviewContent += '<tr><td colspan="2" style="padding: 20px; text-align: center;" class="message message-warning">' + $t('No mapped data available to display.') + '</td></tr>';
            }
            
            reviewContent += '</tbody></table>';
            
            // Show summary
            if (hasData) {
                reviewContent += '<div style="margin-top: 15px; padding: 10px; background-color: #f0f0f0; border-radius: 4px;">';
                reviewContent += '<strong>' + $t('Summary:') + '</strong> ' + Object.keys(mappedData).length + ' ' + $t('field(s) will be applied to the product form.');
                reviewContent += '</div>';
            }
            
            reviewContent += '</div></div>';

            // Show pricing data if available (even if not mapped)
            if (rawData && rawData.pricing) {
                reviewContent += '<div class="admin__data-grid-wrap" style="margin-top: 20px;">';
                reviewContent += '<h3>' + $t('Pricing Suggestions') + '</h3>';
                reviewContent += '<p class="note">' + $t('These prices come from Squadexa AI. They will be applied only if mapped to Magento attributes.') + '</p>';
                reviewContent += '<table class="data-grid" style="width: 100%; border-collapse: collapse;">';
                reviewContent += '<thead><tr style="background-color: #f5f5f5;"><th style="padding: 10px; border: 1px solid #ddd;">' + $t('Currency') + '</th><th style="padding: 10px; border: 1px solid #ddd;">' + $t('Min Price') + '</th><th style="padding: 10px; border: 1px solid #ddd;">' + $t('Max Price') + '</th></tr></thead>';
                reviewContent += '<tbody>';

                Object.keys(rawData.pricing).forEach(function (currency) {
                    var pricingRow = rawData.pricing[currency];
                    if (pricingRow && (pricingRow.min_price || pricingRow.max_price)) {
                        var minPrice = pricingRow.min_price !== undefined ? pricingRow.min_price : '';
                        var maxPrice = pricingRow.max_price !== undefined ? pricingRow.max_price : '';
                        reviewContent += '<tr>';
                        reviewContent += '<td style="padding: 10px; border: 1px solid #ddd;">' + currency + '</td>';
                        reviewContent += '<td style="padding: 10px; border: 1px solid #ddd;">' + minPrice + '</td>';
                        reviewContent += '<td style="padding: 10px; border: 1px solid #ddd;">' + maxPrice + '</td>';
                        reviewContent += '</tr>';
                    }
                });

                reviewContent += '</tbody></table></div>';
            }
            
            // Update review section content
            $('#squadexa-ai-review-content').html(reviewContent);
            
            // Switch to review step
            this.showReviewStep();
        },

        /**
         * Format attribute code for display
         */
        formatAttributeCode: function (code) {
            return code.replace(/_/g, ' ').replace(/\b\w/g, function (l) {
                return l.toUpperCase();
            });
        },

        /**
         * Escape HTML
         */
        escapeHtml: function (text) {
            var map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, function (m) {
                return map[m];
            });
        },

        /**
         * Apply mapped data to product form
         */
        applyMappedDataToForm: function (mappedData) {
            var self = this;
            var dataSourceName = 'product_form.product_form_data_source';
            var formName = 'product_form';
            
            require(['uiRegistry'], function (registry) {
                registry.get(dataSourceName, function (dataSource) {
                    if (!dataSource) {
                        return;
                    }
                    
                    // Get the product ID key (for existing products it's the ID, for new it might be 'new' or empty)
                    var productId = null;
                    if (dataSource.data) {
                        var keys = Object.keys(dataSource.data);
                        if (keys.length > 0) {
                            productId = keys[0];
                        }
                    }
                    
                    // If no product ID found, use the config product ID or 'new'
                    if (!productId) {
                        productId = self.config.productId > 0 ? self.config.productId.toString() : 'new';
                    }
                    
                    var successCount = 0;
                    var failCount = 0;
                    var pendingCount = 0;
                    
                    // Apply each mapped field to the form - same approach as product-form-ai-data-loader.js
                    Object.keys(mappedData).forEach(function (attributeCode) {
                        var value = mappedData[attributeCode];
                        
                        if (value === null || value === '') {
                            return; // Skip empty values
                        }
                        
                        var setSuccess = false;
                        
                        // Method 1: Set via data source set() method with different paths
                        if (dataSource.set) {
                            var paths = [
                                productId + '.product.' + attributeCode,
                                productId + '.' + attributeCode,
                                'data.' + productId + '.product.' + attributeCode,
                                'data.' + productId + '.' + attributeCode
                            ];
                            
                            paths.forEach(function (path) {
                                try {
                                    dataSource.set(path, value);
                                    setSuccess = true;
                                } catch (e) {
                                    // Try next path
                                }
                            });
                        }
                        
                        // Method 2: Direct data manipulation
                        if (!setSuccess && dataSource.data) {
                            if (!dataSource.data[productId]) {
                                dataSource.data[productId] = {};
                            }
                            if (!dataSource.data[productId]['product']) {
                                dataSource.data[productId]['product'] = {};
                            }
                            
                            dataSource.data[productId]['product'][attributeCode] = value;
                            setSuccess = true;
                        }
                        
                        // Method 3: Try setting via field component (async)
                        if (!setSuccess) {
                            var fieldPath = formName + '.' + attributeCode;
                            registry.get(fieldPath, function (field) {
                                if (field) {
                                    try {
                                        if (field.setValue) {
                                            field.setValue(value);
                                            successCount++;
                                        } else if (typeof field.value === 'function') {
                                            field.value(value);
                                            successCount++;
                                        } else {
                                            field.value = value;
                                            successCount++;
                                        }
                                    } catch (e) {
                                        failCount++;
                                    }
                                } else {
                                    failCount++;
                                }
                            });
                            pendingCount++;
                        } else {
                            successCount++;
                        }
                    });
                    
                    // Wait a bit for async field operations, then verify and handle description
                    var verifyAndHandleDescription = function () {
                        // Trigger data update
                        if (dataSource.trigger) {
                            dataSource.trigger('data.update');
                        }
                        
                        // Update description using new working logic (with dataSource and MutationObserver)
                        if (mappedData.description) {
                            self.descriptionUpdateStatus.attempted = true;
                            // Pass dataSource and productId to use the new working logic
                            self.updateDescriptionContent(mappedData.description, dataSource, productId);
                            // Note: updateDescriptionContent now uses async MutationObserver, so we can't rely on immediate return
                            // We'll verify after a longer delay to allow Page Builder to initialize
                        }
                        
                        if (mappedData.short_description) {
                            self.updateShortDescriptionContent(mappedData.short_description);
                        }
                        
                        // Double check if description is actually applied
                        // Wait longer (5 seconds) to allow MutationObserver to find and update Page Builder
                        if (mappedData.description) {
                            setTimeout(function () {
                                var isDescriptionApplied = self.verifyDescriptionApplied(mappedData.description, dataSource, productId);
                                
                                if (!isDescriptionApplied) {
                                    // Description not applied - show manual copy view as fallback
                                    self.showDescriptionManualCopyView(mappedData.description);
                                } else {
                                    // All fields applied successfully
                                    self.showMessage(
                                        $t('AI data has been applied to the product form successfully!'),
                                        'success'
                                    );
                                    // Close modal after 1.5 seconds
                                    setTimeout(function () {
                                        $('#squadexa-ai-generator-modal').modal('closeModal');
                                    }, 1500);
                                }
                            }, 5000); // Wait 5 seconds to allow MutationObserver to work
                        } else {
                            // No description to verify - close normally
                            self.showMessage(
                                $t('AI data has been applied to the product form successfully!'),
                                'success'
                            );
                            setTimeout(function () {
                                $('#squadexa-ai-generator-modal').modal('closeModal');
                            }, 1500);
                        }
                    };
                    
                    if (pendingCount > 0) {
                        setTimeout(function () {
                            verifyAndHandleDescription();
                        }, 2000);
                    } else {
                        verifyAndHandleDescription();
                    }
                });
            });
        },

        /**
         * Update description content for textarea/WYSIWYG/Page Builder
         * Uses the same working logic as product-form-ai-data-loader.js
         * Sets data in dataSource first, then uses MutationObserver to detect Page Builder
         */
        updateDescriptionContent: function (value, dataSource, productId) {
            var self = this;
            
            // Get dataSource and productId if not provided
            if (!dataSource || !productId) {
                require(['uiRegistry'], function (registry) {
                    registry.get('product_form.product_form_data_source', function (ds) {
                        if (ds && ds.data) {
                            var keys = Object.keys(ds.data);
                            productId = keys.length > 0 ? keys[0] : 'product';
                            dataSource = ds;
                            self._updateDescriptionWithDataSource(value, dataSource, productId);
                        }
                    });
                });
                return false; // Will be updated asynchronously
            }
            
            return this._updateDescriptionWithDataSource(value, dataSource, productId);
        },

        /**
         * Internal method to update description using dataSource and MutationObserver
         */
        _updateDescriptionWithDataSource: function (value, dataSource, productId) {
            var self = this;
            
            
            // First, set description in dataSource immediately (before Page Builder initializes)
            if (dataSource && dataSource.data) {
                if (!dataSource.data[productId]) {
                    dataSource.data[productId] = {};
                }
                if (!dataSource.data[productId].product) {
                    dataSource.data[productId].product = {};
                }
                dataSource.data[productId].product.description = value;
                
                // Also set via dataSource.set() to trigger change detection
                if (dataSource.set) {
                    try {
                        dataSource.set(productId + '.product.description', value);
                        dataSource.set('data.' + productId + '.product.description', value);
                    } catch (e) {
                    }
                }
            }
            
            // Also try immediate DOM updates (for non-Page Builder cases)
            var selectors = [
                '#description',
                'textarea[name="product[description]"]',
                'textarea[name="product[product][description]"]'
            ];

            selectors.forEach(function (selector) {
                var $field = $(selector);
                if ($field.length) {
                    $field.val(value).trigger('change').trigger('input');
                }
            });

            if (window.tinyMCE && typeof window.tinyMCE.get === 'function') {
                var editor = window.tinyMCE.get('description');
                if (editor) {
                    editor.setContent(value);
                }
            }
            
            // Use MutationObserver to detect when Page Builder initializes in DOM
            var pageBuilderFound = false;
            var observerAttempts = 0;
            var maxObserverAttempts = 60; // 30 seconds total (60 * 500ms)
            
            // Function to try updating Page Builder when found
            var tryUpdatePageBuilder = function() {
                if (pageBuilderFound) {
                    return; // Already updated
                }
                
                observerAttempts++;
                
                // Look for Page Builder elements in DOM
                var $descriptionContainer = $('.admin__field[data-index="description"]');
                if (!$descriptionContainer.length) {
                    $descriptionContainer = $('#description').closest('.admin__field-control').closest('.admin__field');
                }
                
                if ($descriptionContainer.length) {
                    // Check for Page Builder stage, iframe, or source textarea
                    var $pageBuilderStage = $descriptionContainer.find('[data-role="pagebuilder-stage"]');
                    var $pageBuilderIframe = $descriptionContainer.find('iframe');
                    var $pageBuilderTextarea = $descriptionContainer.find('textarea[data-role="source"]');
                    
                    if ($pageBuilderStage.length || $pageBuilderIframe.length || $pageBuilderTextarea.length) {
                        pageBuilderFound = true;
                        
                        // Get description value from dataSource
                        var descriptionValue = value;
                        if (dataSource && dataSource.data && dataSource.data[productId]) {
                            descriptionValue = dataSource.data[productId].product.description || value;
                        }
                        
                        // Update Page Builder source textarea if available
                        if ($pageBuilderTextarea.length) {
                            $pageBuilderTextarea.val(descriptionValue);
                            $pageBuilderTextarea.trigger('change').trigger('input').trigger('keyup');
                        }
                        
                        // Force dataSource update and trigger Page Builder to read it
                        if (dataSource && dataSource.set) {
                            dataSource.set(productId + '.product.description', descriptionValue);
                            if (dataSource.trigger) {
                                dataSource.trigger('data.update');
                                dataSource.trigger('update');
                            }
                        }
                        
                        // Try UI Registry to update the field
                        require(['uiRegistry'], function (registry) {
                            registry.get('product_form.description', function (field) {
                                if (field && field.setValue) {
                                    field.setValue(descriptionValue);
                                    if (field.trigger) {
                                        field.trigger('value');
                                        field.trigger('update');
                                    }
                                }
                            });
                        });
                        
                        
                        // Mark as updated for verification
                        self.descriptionUpdateStatus.updated = true;
                    }
                }
                
                // Continue checking if not found yet
                if (!pageBuilderFound && observerAttempts < maxObserverAttempts) {
                    setTimeout(tryUpdatePageBuilder, 500);
                } else if (!pageBuilderFound) {
                }
            };
            
            // Use MutationObserver to watch for Page Builder elements
            if (window.MutationObserver) {
                var observer = new MutationObserver(function(mutations) {
                    if (!pageBuilderFound) {
                        tryUpdatePageBuilder();
                    }
                });
                
                // Observe the document body for changes
                observer.observe(document.body, {
                    childList: true,
                    subtree: true,
                    attributes: false
                });
            }
            
            // Start checking after a short delay
            setTimeout(tryUpdatePageBuilder, 2000);
            
            return true; // Return true to indicate we're trying
        },

        updateShortDescriptionContent: function (value) {
            var updated = false;
            var selectors = [
                '#short_description',
                'textarea[name="product[short_description]"]',
                'textarea[name="product[product][short_description]"]'
            ];

            selectors.forEach(function (selector) {
                var $field = $(selector);
                if ($field.length) {
                    $field.val(value).trigger('change');
                    updated = true;
                }
            });

            if (window.tinyMCE && typeof window.tinyMCE.get === 'function') {
                var editor = window.tinyMCE.get('short_description');
                if (editor) {
                    editor.setContent(value);
                    updated = true;
                }
            }

            return updated;
        },

        /**
         * Verify if description is actually applied to the form (double check)
         * This checks the actual visible/editable content, not just data source
         */
        verifyDescriptionApplied: function (expectedValue, dataSource, productId) {
            var self = this;
            var isApplied = false;
            var expectedValueTrimmed = expectedValue.trim();
            
            
            // First, check if Page Builder is active for description field
            var isPageBuilderActive = false;
            var $descriptionContainer = $('#description').closest('.admin__field-control');
            if ($descriptionContainer.length) {
                // Check if Page Builder stage exists
                var $pageBuilderStage = $descriptionContainer.find('[data-role="pagebuilder-stage"]');
                if ($pageBuilderStage.length > 0) {
                    isPageBuilderActive = true;
                    
                    // For Page Builder, check the actual stage content
                    // Page Builder stores content in a specific format, check if it contains our text
                    var pageBuilderContent = $pageBuilderStage.html() || '';
                    // Also check hidden textarea that Page Builder uses
                    var $pageBuilderTextarea = $descriptionContainer.find('textarea[data-role="source"]');
                    if ($pageBuilderTextarea.length) {
                        var pbTextareaValue = $pageBuilderTextarea.val() || '';
                        // Check if our expected text is in the Page Builder content
                        if (pbTextareaValue.indexOf(expectedValueTrimmed) !== -1 || 
                            pbTextareaValue.replace(/<[^>]*>/g, '').trim() === expectedValueTrimmed) {
                            isApplied = true;
                        }
                    }
                }
            }
            
            // Method 1: Check DOM textareas (most reliable for non-Page Builder)
            if (!isApplied) {
                var selectors = [
                    '#description',
                    'textarea[name="product[description]"]',
                    'textarea[name="product[product][description]"]'
                ];
                
                selectors.forEach(function (selector) {
                    var $field = $(selector);
                    if ($field.length) {
                        var fieldValue = $field.val() || '';
                        var fieldValueTrimmed = fieldValue.trim();
                        
                        // For HTML content, compare both with and without HTML tags
                        var fieldValueTextOnly = fieldValueTrimmed.replace(/<[^>]*>/g, '').trim();
                        var expectedValueTextOnly = expectedValueTrimmed.replace(/<[^>]*>/g, '').trim();
                        
                        if (fieldValueTrimmed === expectedValueTrimmed || 
                            fieldValueTextOnly === expectedValueTextOnly ||
                            fieldValueTrimmed.indexOf(expectedValueTrimmed) !== -1) {
                            isApplied = true;
                            return false; // Break forEach
                        }
                    }
                });
            }
            
            // Method 2: Check TinyMCE editor (if active)
            if (!isApplied && window.tinyMCE && typeof window.tinyMCE.get === 'function') {
                var editor = window.tinyMCE.get('description');
                if (editor && !editor.isHidden()) {
                    var editorContent = editor.getContent() || '';
                    var editorContentTrimmed = editorContent.trim();
                    var editorTextOnly = editorContentTrimmed.replace(/<[^>]*>/g, '').trim();
                    var expectedTextOnly = expectedValueTrimmed.replace(/<[^>]*>/g, '').trim();
                    
                    if (editorContentTrimmed === expectedValueTrimmed || 
                        editorTextOnly === expectedTextOnly ||
                        editorContentTrimmed.indexOf(expectedValueTrimmed) !== -1) {
                        isApplied = true;
                    } else {
                    }
                }
            }
            
            // Method 3: Check via UI registry (last resort, but less reliable)
            if (!isApplied) {
                try {
                    require(['uiRegistry'], function (registry) {
                        registry.get('product_form.description', function (field) {
                            if (field) {
                                var fieldValue = null;
                                if (typeof field.value === 'function') {
                                    fieldValue = field.value();
                                } else if (field.value !== undefined) {
                                    fieldValue = field.value;
                                }
                                
                                if (fieldValue) {
                                    var fieldValueTrimmed = String(fieldValue).trim();
                                    var fieldTextOnly = fieldValueTrimmed.replace(/<[^>]*>/g, '').trim();
                                    var expectedTextOnly = expectedValueTrimmed.replace(/<[^>]*>/g, '').trim();
                                    
                                    if (fieldValueTrimmed === expectedValueTrimmed || 
                                        fieldTextOnly === expectedTextOnly) {
                                        isApplied = true;
                                    }
                                }
                            }
                        });
                    });
                } catch (e) {
                }
            }
            
            // For Page Builder, if we couldn't verify, assume it's NOT applied
            // because Page Builder requires special handling
            if (isPageBuilderActive && !isApplied) {
                isApplied = false;
            }
            
            // Final check: If data source has it but visible fields don't, it's NOT applied
            // This prevents false positives from data source checks
            var dataSourceHasValue = false;
            if (dataSource && dataSource.data && dataSource.data[productId]) {
                var productData = dataSource.data[productId];
                var descriptionValue = null;
                
                if (productData.product && productData.product.description) {
                    descriptionValue = productData.product.description;
                } else if (productData.description) {
                    descriptionValue = productData.description;
                }
                
                if (descriptionValue && descriptionValue.trim() === expectedValueTrimmed) {
                    dataSourceHasValue = true;
                }
            }
            
            // Only consider it applied if BOTH data source AND visible field have it
            // OR if we verified it in a visible field (which is more reliable)
            if (dataSourceHasValue && !isApplied) {
                isApplied = false;
            }
            
            return isApplied;
        },

        /**
         * Show description manual copy view
         */
        showDescriptionManualCopyView: function (descriptionText) {
            var self = this;
            
            // Hide review section, show description copy view
            $('#squadexa-ai-review-section').hide();
            
            // Create description copy section if it doesn't exist
            if ($('#squadexa-ai-description-copy-section').length === 0) {
                $('#squadexa-ai-generator-modal').append('<div id="squadexa-ai-description-copy-section" style="display:none;"></div>');
            }
            
            var copyContent = '<div class="admin__scope-old">';
            copyContent += '<div class="messages"></div>';
            copyContent += '<div style="padding: 20px;">';
            copyContent += '<h3 style="margin-top: 0; color: #e02b27;">' + $t('Description Could Not Be Applied Automatically') + '</h3>';
            copyContent += '<p class="note" style="margin-bottom: 20px;">';
            copyContent += $t('The description field uses Page Builder and could not be automatically updated. Please copy the description text below and paste it manually into the product description field.');
            copyContent += '</p>';
            copyContent += '<div class="admin__field">';
            copyContent += '<label class="admin__field-label"><span>' + $t('Description Text') + '</span></label>';
            copyContent += '<div class="admin__field-control">';
            copyContent += '<textarea id="squadexa-description-copy-textarea" readonly style="width: 100%; min-height: 200px; padding: 10px; border: 1px solid #ccc; font-family: monospace; resize: vertical;">' + self.escapeHtml(descriptionText) + '</textarea>';
            copyContent += '</div>';
            copyContent += '</div>';
            copyContent += '<div style="margin-top: 15px;">';
            copyContent += '<button type="button" class="action-primary" id="squadexa-copy-description-btn">' + $t('Copy to Clipboard') + '</button>';
            copyContent += '<span id="squadexa-copy-success-msg" style="margin-left: 10px; color: #008000; display: none;">' + $t('✓ Copied!') + '</span>';
            copyContent += '</div>';
            copyContent += '</div></div>';
            
            $('#squadexa-ai-description-copy-section').html(copyContent).show();
            
            // Update modal buttons
            this.updateModalButtons('description-copy');
            
            // Attach copy button handler
            $('#squadexa-copy-description-btn').on('click', function () {
                var textarea = document.getElementById('squadexa-description-copy-textarea');
                if (textarea) {
                    textarea.select();
                    textarea.setSelectionRange(0, 99999); // For mobile devices
                    
                    try {
                        document.execCommand('copy');
                        $('#squadexa-copy-success-msg').fadeIn().delay(2000).fadeOut();
                    } catch (err) {
                        // Fallback: select text for manual copy
                        textarea.focus();
                        textarea.select();
                    }
                }
            });
        },

        /**
         * Extract a single price value from pricing suggestions
         */
        extractPriceFromPricing: function (pricingData) {
            if (!pricingData || typeof pricingData !== 'object') {
                return '';
            }

            var preferredCurrencies = ['USD', 'CAD', 'EUR', 'GBP'];
            var priceValue = '';

            preferredCurrencies.some(function (currency) {
                if (pricingData[currency]) {
                    var row = pricingData[currency];
                    if (row.min_price !== undefined) {
                        priceValue = row.min_price;
                        return true;
                    }
                    if (row.max_price !== undefined) {
                        priceValue = row.max_price;
                        return true;
                    }
                }
                return false;
            });

            if (!priceValue) {
                Object.keys(pricingData).some(function (currency) {
                    var row = pricingData[currency];
                    if (row && row.min_price !== undefined) {
                        priceValue = row.min_price;
                        return true;
                    }
                    if (row && row.max_price !== undefined) {
                        priceValue = row.max_price;
                        return true;
                    }
                    return false;
                });
            }

            return priceValue;
        }
    };
});

