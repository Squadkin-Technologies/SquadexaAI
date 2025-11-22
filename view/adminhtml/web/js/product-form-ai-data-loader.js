/**
 * Copyright © 2024 Squadkin. All rights reserved.
 */
define([
    'jquery',
    'uiRegistry',
    'Magento_Ui/js/modal/modal',
    'mage/translate',
    'mage/url'
], function ($, registry, modal, $t, urlBuilder) {
    'use strict';

    var AiDataLoader = {
        maxRetries: 10,
        retryDelay: 500,
        currentRetry: 0,
        aiData: null,
        dataSourceName: 'product_form.product_form_data_source',
        formName: 'product_form',

        /**
         * Initialize the AI data loader
         */
        init: function () {
            console.log('[AI Data Loader] Initializing...');
            console.log('[AI Data Loader] Current URL:', window.location.href);
            console.log('[AI Data Loader] Search params:', window.location.search);
            
            // Check if ai_data is in URL (this is the AI product ID)
            // Try query parameters first
            var urlParams = new URLSearchParams(window.location.search);
            var aiProductId = urlParams.get('ai_data');
            
            // If not in query params, check the full URL path
            if (!aiProductId) {
                var fullUrl = window.location.href;
                var aiDataMatch = fullUrl.match(/[?&]ai_data=([^&\/]+)/);
                if (aiDataMatch) {
                    aiProductId = decodeURIComponent(aiDataMatch[1]);
                }
            }
            
            // Also check if it's in the path (Magento sometimes uses path params)
            if (!aiProductId) {
                var pathMatch = window.location.pathname.match(/ai_data\/([^\/]+)/);
                if (pathMatch) {
                    aiProductId = decodeURIComponent(pathMatch[1]);
                }
            }
            
            if (!aiProductId) {
                console.log('[AI Data Loader] No ai_data parameter found in URL');
                console.log('[AI Data Loader] Full URL:', window.location.href);
                console.log('[AI Data Loader] Pathname:', window.location.pathname);
                console.log('[AI Data Loader] Search:', window.location.search);
                return;
            }

            console.log('[AI Data Loader] Found ai_data parameter (AI Product ID):', aiProductId);
            
            // Store AI product ID in form data source so it's available in POST data
            this.storeAiProductId(parseInt(aiProductId, 10));
            
            // Get product type from URL (check both query and path)
            var productType = urlParams.get('type') || 'simple';
            if (productType === 'simple') {
                var typeMatch = window.location.pathname.match(/type\/([^\/]+)/);
                if (typeMatch) {
                    productType = decodeURIComponent(typeMatch[1]);
                }
            }
            
            console.log('[AI Data Loader] Product type:', productType);
            
            // Fetch mapped data from backend
            this.fetchMappedData(parseInt(aiProductId, 10), productType);
        },

        /**
         * Store AI product ID in form data source
         */
        storeAiProductId: function (aiProductId) {
            var self = this;
            console.log('[AI Data Loader] Storing AI product ID in form:', aiProductId);
            
            // Wait for data source to be available
            registry.get(this.dataSourceName, function (dataSource) {
                if (dataSource) {
                    // Get product ID key
                    var productId = null;
                    if (dataSource.data) {
                        var keys = Object.keys(dataSource.data);
                        if (keys.length > 0) {
                            productId = keys[0];
                        } else {
                            productId = 'new';
                        }
                    } else {
                        productId = 'new';
                    }
                    
                    // Initialize data structure if needed
                    if (!dataSource.data[productId]) {
                        dataSource.data[productId] = {};
                    }
                    if (!dataSource.data[productId]['product']) {
                        dataSource.data[productId]['product'] = {};
                    }
                    if (!dataSource.data[productId]['product']['general']) {
                        dataSource.data[productId]['product']['general'] = {};
                    }
                    
                    // Store AI product ID in multiple places to ensure it's captured
                    dataSource.data[productId]['product']['general']['ai_product_id'] = aiProductId;
                    dataSource.data[productId]['product']['general']['ai_data'] = aiProductId;
                    
                    // Also set directly in product data
                    if (dataSource.set) {
                        dataSource.set('data.' + productId + '.product.general.ai_product_id', aiProductId);
                        dataSource.set('data.' + productId + '.product.general.ai_data', aiProductId);
                    }
                    
                    console.log('[AI Data Loader] AI product ID stored in form data source');
                } else {
                    // Retry if data source not ready
                    setTimeout(function() {
                        self.storeAiProductId(aiProductId);
                    }, 500);
                }
            });
        },

        /**
         * Fetch mapped AI data from backend
         */
        fetchMappedData: function (aiProductId, productType) {
            var self = this;
            
            console.log('[AI Data Loader] Fetching mapped data from backend...', {
                aiProductId: aiProductId,
                productType: productType
            });

            var urlPath = 'squadkin_squadexaai/aiproduct/getMappedData';
            var url;
            
            // Extract secret key from current URL
            var secretKey = null;
            var currentUrl = window.location.href;
            var keyMatch = currentUrl.match(/\/key\/([^\/\?]+)/);
            if (keyMatch) {
                secretKey = keyMatch[1];
                console.log('[AI Data Loader] Extracted secret key from URL');
            }
            
            // Build URL with proper admin key
            if (secretKey) {
                // Extract base URL (everything up to /admin/)
                var baseUrlMatch = currentUrl.match(/^(https?:\/\/[^\/]+)/);
                var baseUrl = baseUrlMatch ? baseUrlMatch[1] : window.location.origin;
                url = baseUrl + '/admin/' + urlPath + '/key/' + secretKey + '/';
                console.log('[AI Data Loader] Built URL with secret key:', url);
            } else if (typeof BASE_URL !== 'undefined') {
                var baseUrlMatch = BASE_URL.match(/^(https?:\/\/[^\/]+)\/admin\//);
                var keyMatch2 = BASE_URL.match(/\/key\/([^\/]+)/);
                
                if (baseUrlMatch && keyMatch2) {
                    var baseUrl2 = baseUrlMatch[1];
                    var secretKey2 = keyMatch2[1];
                    url = baseUrl2 + '/admin/' + urlPath + '/key/' + secretKey2 + '/';
                } else {
                    url = urlBuilder.build(urlPath);
                }
            } else {
                url = urlBuilder.build(urlPath);
                if (url.indexOf('squadkin_squadexaai') === -1) {
                    url = window.location.origin + '/admin/' + urlPath + '/';
                }
            }
            
            console.log('[AI Data Loader] Final AJAX URL:', url);

            // Get form key if available
            var formKey = '';
            var formKeyInput = $('input[name="form_key"]');
            if (formKeyInput.length > 0) {
                formKey = formKeyInput.val();
            } else if (typeof FORM_KEY !== 'undefined') {
                formKey = FORM_KEY;
            }
            
            console.log('[AI Data Loader] Form key:', formKey ? 'Found' : 'Not found');
            
            $.ajax({
                url: url,
                type: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                data: {
                    ai_data: aiProductId,
                    type: productType,
                    isAjax: true,
                    form_key: formKey
                },
                dataType: 'json',
                showLoader: true,
                success: function (response) {
                    console.log('[AI Data Loader] Mapped data received:', response);
                    if (response && response.success && response.data) {
                        self.aiData = response.data;
                        console.log('[AI Data Loader] AI data loaded:', {
                            attributes_count: Object.keys(self.aiData).length,
                            attributes: Object.keys(self.aiData)
                        });
                        // Wait for form to be ready and then set data
                        self.waitForFormAndSetData();
                    } else {
                        console.error('[AI Data Loader] Error in response:', response);
                        var errorMsg = response && response.message ? response.message : 'Failed to load AI data';
                        alert(errorMsg);
                    }
                },
                error: function (xhr, status, error) {
                    console.error('[AI Data Loader] AJAX error fetching mapped data:', error);
                    console.error('[AI Data Loader] HTTP Status:', xhr.status);
                    console.error('[AI Data Loader] Response Text:', xhr.responseText ? xhr.responseText.substring(0, 500) : 'No response');
                    alert('Failed to load AI product data. Please try again.');
                }
            });
        },

        /**
         * Wait for form to be ready and set AI data
         */
        waitForFormAndSetData: function () {
            var self = this;
            
            // Check if data source is available
            registry.get(this.dataSourceName, function (dataSource) {
                if (dataSource && dataSource.data) {
                    console.log('[AI Data Loader] Data source found, checking if form is ready...');
                    self.checkFormReady();
                } else {
                    console.log('[AI Data Loader] Data source not ready yet, retrying...');
                    self.retryWaitForForm();
                }
            });
        },

        /**
         * Retry waiting for form
         */
        retryWaitForForm: function () {
            var self = this;
            
            if (this.currentRetry >= this.maxRetries) {
                console.warn('[AI Data Loader] Max retries reached, showing popup instead');
                this.showFallbackPopup();
                return;
            }

            this.currentRetry++;
            console.log('[AI Data Loader] Retry attempt ' + this.currentRetry + ' of ' + this.maxRetries);
            
            setTimeout(function () {
                self.waitForFormAndSetData();
            }, this.retryDelay);
        },

        /**
         * Check if form is ready and set data
         */
        checkFormReady: function () {
            var self = this;
            var fieldsToSet = [];
            var fieldsChecked = 0;
            var totalFields = Object.keys(this.aiData).length;

            if (totalFields === 0) {
                console.log('[AI Data Loader] No fields to set');
                return;
            }

            console.log('[AI Data Loader] Checking form fields readiness...');

            // Get data source to check if form is loaded
            registry.get(this.dataSourceName, function (dataSource) {
                if (!dataSource || !dataSource.data) {
                    console.log('[AI Data Loader] Data source not ready, retrying...');
                    if (self.currentRetry < self.maxRetries) {
                        self.currentRetry++;
                        setTimeout(function () {
                            self.checkFormReady();
                        }, self.retryDelay);
                    } else {
                        console.warn('[AI Data Loader] Data source not ready after max retries');
                        self.showFallbackPopup();
                    }
                    return;
                }

                // Get product ID key
                var productId = null;
                var keys = Object.keys(dataSource.data);
                if (keys.length > 0) {
                    productId = keys[0];
                } else {
                    productId = 'new';
                }

                console.log('[AI Data Loader] Product ID key:', productId);
                console.log('[AI Data Loader] Data source structure:', Object.keys(dataSource.data[productId] || {}));

                // Prepare all fields to set (we'll try to set them even if not found in registry)
                Object.keys(self.aiData).forEach(function (attributeCode) {
                    fieldsToSet.push({
                        code: attributeCode,
                        value: self.aiData[attributeCode],
                        productId: productId
                    });
                });

                // Try to set values now
                console.log('[AI Data Loader] Attempting to set ' + fieldsToSet.length + ' fields...');
                self.setFormValues(fieldsToSet);
            });
        },

        /**
         * Set form values via data source
         */
        setFormValues: function (fieldsToSet) {
            var self = this;
            var successCount = 0;
            var failCount = 0;
            var pendingCount = 0;

            console.log('[AI Data Loader] Setting values for ' + fieldsToSet.length + ' fields...');

            // Get data source first
            registry.get(this.dataSourceName, function (dataSource) {
                if (!dataSource) {
                    console.error('[AI Data Loader] Data source not found');
                    self.showFallbackPopup();
                    return;
                }

                // Get the product ID key (for new products it might be 'new' or empty)
                var productId = null;
                if (dataSource.data) {
                    var keys = Object.keys(dataSource.data);
                    if (keys.length > 0) {
                        productId = keys[0];
                    }
                }

                if (!productId) {
                    // Try to get from form or use 'new'
                    productId = 'new';
                }

                console.log('[AI Data Loader] Using product ID key:', productId);

                // Set values via data source - try multiple approaches
                fieldsToSet.forEach(function (fieldInfo) {
                    try {
                        var value = fieldInfo.value;
                        var attributeCode = fieldInfo.code;
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
                                    console.log('[AI Data Loader] Set value via dataSource.set(' + path + ') for ' + attributeCode + ':', value);
                                    setSuccess = true;
                                } catch (e) {
                                    // Try next path
                                }
                            });
                        }

                        // Method 2: Direct data manipulation
                        if (!setSuccess && dataSource.data && dataSource.data[productId]) {
                            // Try different data structures
                            if (dataSource.data[productId].product) {
                                dataSource.data[productId].product[attributeCode] = value;
                                console.log('[AI Data Loader] Set value via data[].product[] for ' + attributeCode + ':', value);
                                setSuccess = true;
                            } else {
                                dataSource.data[productId][attributeCode] = value;
                                console.log('[AI Data Loader] Set value via data[] for ' + attributeCode + ':', value);
                                setSuccess = true;
                            }
                        }

                        // Method 3: Try setting via field component (async)
                        if (!setSuccess) {
                            var fieldPath = self.formName + '.' + attributeCode;
                            registry.get(fieldPath, function (field) {
                                if (field) {
                                    try {
                                        if (field.setValue) {
                                            field.setValue(value);
                                            console.log('[AI Data Loader] Set value via field.setValue for ' + attributeCode + ':', value);
                                            successCount++;
                                        } else if (typeof field.value === 'function') {
                                            field.value(value);
                                            console.log('[AI Data Loader] Set value via field.value() for ' + attributeCode + ':', value);
                                            successCount++;
                                        } else {
                                            field.value = value;
                                            console.log('[AI Data Loader] Set value via field.value property for ' + attributeCode + ':', value);
                                            successCount++;
                                        }
                                    } catch (e) {
                                        console.warn('[AI Data Loader] Error setting field ' + attributeCode + ':', e);
                                        failCount++;
                                    }
                                } else {
                                    console.warn('[AI Data Loader] Field component not found for ' + attributeCode);
                                    failCount++;
                                }
                            });
                            pendingCount++;
                        } else {
                            successCount++;
                        }
                    } catch (e) {
                        console.error('[AI Data Loader] Error setting value for ' + fieldInfo.code + ':', e);
                        failCount++;
                    }
                });

                // Wait a bit for async field operations, then handle description/short_description specifically
                var handleDescriptionFields = function () {
                    // CRITICAL: Set description in dataSource FIRST, before Page Builder initializes
                    // This way Page Builder will read it when it initializes
                    var descriptionValue = self.aiData.description;
                    var shortDescriptionValue = self.aiData.short_description;
                    
                    if (descriptionValue && dataSource) {
                        console.log('[AI Data Loader] Setting description in dataSource FIRST (before Page Builder init)...');
                        // Set in dataSource immediately so Page Builder reads it on init
                        if (!dataSource.data[productId]) {
                            dataSource.data[productId] = {};
                        }
                        if (!dataSource.data[productId].product) {
                            dataSource.data[productId].product = {};
                        }
                        dataSource.data[productId].product.description = descriptionValue;
                        
                        // Also set via dataSource.set() to trigger change detection
                        if (dataSource.set) {
                            try {
                                dataSource.set(productId + '.product.description', descriptionValue);
                                dataSource.set('data.' + productId + '.product.description', descriptionValue);
                            } catch (e) {
                                console.warn('[AI Data Loader] Error setting description via dataSource.set():', e);
                            }
                        }
                        console.log('[AI Data Loader] ✓ Description set in dataSource - Page Builder should read this on init');
                    }
                    
                    // Trigger data update
                    if (dataSource.trigger) {
                        dataSource.trigger('data.update');
                    }
                    
                    // Now update description and short_description via DOM as fallback (for Page Builder support)
                    if (descriptionValue) {
                        console.log('[AI Data Loader] Updating description field with Page Builder support...');
                        self.updateDescriptionContent(descriptionValue, dataSource, productId);
                    }
                    
                    if (shortDescriptionValue) {
                        console.log('[AI Data Loader] Updating short_description field...');
                        self.updateShortDescriptionContent(shortDescriptionValue);
                    }
                };
                
                if (pendingCount > 0) {
                    setTimeout(function () {
                        console.log('[AI Data Loader] Values set - Success: ' + successCount + ', Failed: ' + failCount);
                        handleDescriptionFields();
                        
                        setTimeout(function () {
                            if (failCount > 0 || successCount === 0) {
                                console.warn('[AI Data Loader] Some values could not be set, showing popup for manual entry');
                                self.showFallbackPopup();
                            } else {
                                console.log('[AI Data Loader] All values set successfully!');
                                self.showSuccessMessage();
                            }
                        }, 1000);
                    }, 2000);
                } else {
                    console.log('[AI Data Loader] Values set - Success: ' + successCount + ', Failed: ' + failCount);
                    handleDescriptionFields();
                    
                    setTimeout(function () {
                        if (failCount > 0 || successCount === 0) {
                            console.warn('[AI Data Loader] Some values could not be set, showing popup for manual entry');
                            self.showFallbackPopup();
                        } else {
                            console.log('[AI Data Loader] All values set successfully!');
                            self.showSuccessMessage();
                        }
                    }, 1000);
                }
            });
        },

        /**
         * Update description content for textarea/WYSIWYG/Page Builder
         * Uses MutationObserver to detect when Page Builder initializes
         */
        updateDescriptionContent: function (value, dataSource, productId) {
            var self = this;
            var descriptionApplied = false;
            
            console.log('[AI Data Loader] Setting description in dataSource and waiting for Page Builder to initialize...');
            
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
                        console.warn('[AI Data Loader] Error setting description via dataSource.set():', e);
                    }
                }
            }
            
            // Function to verify if description was actually applied
            var verifyDescriptionApplied = function() {
                if (descriptionApplied) {
                    return true;
                }
                
                // Check dataSource
                if (dataSource && dataSource.data && dataSource.data[productId]) {
                    var dataSourceValue = dataSource.data[productId].product.description || '';
                    if (dataSourceValue && dataSourceValue.trim() === value.trim()) {
                        // Check if Page Builder has it
                        var $descriptionContainer = $('.admin__field[data-index="description"]');
                        if (!$descriptionContainer.length) {
                            $descriptionContainer = $('#description').closest('.admin__field-control').closest('.admin__field');
                        }
                        
                        if ($descriptionContainer.length) {
                            var $pageBuilderTextarea = $descriptionContainer.find('textarea[data-role="source"]');
                            if ($pageBuilderTextarea.length) {
                                var pbValue = $pageBuilderTextarea.val() || '';
                                if (pbValue.trim() === value.trim() || pbValue.indexOf(value.trim()) !== -1) {
                                    descriptionApplied = true;
                                    return true;
                                }
                            }
                        }
                    }
                }
                return false;
            };
            
            // Use MutationObserver to detect when Page Builder initializes in DOM
            var pageBuilderFound = false;
            var observerAttempts = 0;
            var maxObserverAttempts = 80; // 40 seconds total (80 * 500ms)
            var updateAttempts = 0;
            var maxUpdateAttempts = 10; // Try updating up to 10 times even after finding Page Builder
            
            // Function to try updating Page Builder when found
            var tryUpdatePageBuilder = function() {
                if (descriptionApplied && verifyDescriptionApplied()) {
                    return; // Already applied and verified
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
                        if (!pageBuilderFound) {
                            console.log('[AI Data Loader] Page Builder found! Updating description...');
                            pageBuilderFound = true;
                        }
                        
                        // Get description value from dataSource
                        var descriptionValue = value;
                        if (dataSource && dataSource.data && dataSource.data[productId]) {
                            descriptionValue = dataSource.data[productId].product.description || value;
                        }
                        
                        updateAttempts++;
                        
                        // Method 1: Update Page Builder source textarea if available
                        if ($pageBuilderTextarea.length) {
                            $pageBuilderTextarea.val(descriptionValue);
                            $pageBuilderTextarea.trigger('change').trigger('input').trigger('keyup').trigger('blur');
                            
                            // Also try setting it multiple times to ensure it sticks
                            setTimeout(function() {
                                $pageBuilderTextarea.val(descriptionValue);
                                $pageBuilderTextarea.trigger('change').trigger('input');
                            }, 100);
                        }
                        
                        // Method 2: Force dataSource update and trigger Page Builder to read it
                        if (dataSource && dataSource.set) {
                            dataSource.set(productId + '.product.description', descriptionValue);
                            if (dataSource.trigger) {
                                dataSource.trigger('data.update');
                                dataSource.trigger('update');
                                dataSource.trigger('reload');
                            }
                        }
                        
                        // Method 3: Try UI Registry to update the field
                        require(['uiRegistry'], function (registry) {
                            registry.get('product_form.description', function (field) {
                                if (field) {
                                    if (field.setValue) {
                                        field.setValue(descriptionValue);
                                    }
                                    if (field.set) {
                                        field.set('value', descriptionValue);
                                    }
                                    if (field.trigger) {
                                        field.trigger('value');
                                        field.trigger('update');
                                        field.trigger('data');
                                    }
                                    
                                    // Try to find Page Builder in the field
                                    if (field.content_type && field.content_type.pagebuilder) {
                                        var pb = field.content_type.pagebuilder;
                                        if (pb.setValue) {
                                            pb.setValue(descriptionValue);
                                        }
                                    }
                                }
                            });
                        });
                        
                        // Method 4: Try window.PageBuilder if available
                        if (window.PageBuilder) {
                            try {
                                if (typeof window.PageBuilder.setContent === 'function') {
                                    window.PageBuilder.setContent('description', descriptionValue);
                                }
                                if (typeof window.PageBuilder.updateContent === 'function') {
                                    window.PageBuilder.updateContent('description', descriptionValue);
                                }
                            } catch (e) {
                                // Ignore errors
                            }
                        }
                        
                        // Verify if it was applied
                        setTimeout(function() {
                            if (verifyDescriptionApplied()) {
                                descriptionApplied = true;
                                console.log('[AI Data Loader] ✓ Description successfully applied to Page Builder!');
                            } else if (updateAttempts < maxUpdateAttempts) {
                                // Try again
                                setTimeout(tryUpdatePageBuilder, 1000);
                            }
                        }, 500);
                    }
                }
                
                // Continue checking if not found yet or not applied yet
                if ((!pageBuilderFound || !descriptionApplied) && observerAttempts < maxObserverAttempts) {
                    setTimeout(tryUpdatePageBuilder, 500);
                } else if (!pageBuilderFound) {
                    console.log('[AI Data Loader] Page Builder not found. Description is in dataSource and will show on page refresh.');
                } else if (!descriptionApplied) {
                    console.log('[AI Data Loader] Page Builder found but description not applied. It may appear after page refresh.');
                }
            };
            
            // Use MutationObserver to watch for Page Builder elements
            if (window.MutationObserver) {
                var observer = new MutationObserver(function(mutations) {
                    if (!descriptionApplied) {
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
            
            // Start checking immediately and periodically
            setTimeout(tryUpdatePageBuilder, 1000);
            setTimeout(tryUpdatePageBuilder, 3000);
            setTimeout(tryUpdatePageBuilder, 5000);
            setTimeout(tryUpdatePageBuilder, 8000);
            
            return true;
        },

        /**
         * Update short description content
         */
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
                    $field.val(value).trigger('change').trigger('input');
                    console.log('[AI Data Loader] Set short_description via DOM selector: ' + selector);
                    updated = true;
                }
            });

            if (window.tinyMCE && typeof window.tinyMCE.get === 'function') {
                var editor = window.tinyMCE.get('short_description');
                if (editor) {
                    editor.setContent(value);
                    console.log('[AI Data Loader] Set short_description via TinyMCE editor');
                    updated = true;
                }
            }
            
            // Try to update via UI registry
            setTimeout(function () {
                require(['uiRegistry'], function (registry) {
                    registry.get('product_form.short_description', function (field) {
                        if (field) {
                            try {
                                if (field.setValue) {
                                    field.setValue(value);
                                } else if (field.value !== undefined) {
                                    if (typeof field.value === 'function') {
                                        field.value(value);
                                    } else {
                                        field.value = value;
                                    }
                                }
                                
                                if (field.trigger) {
                                    field.trigger('value');
                                }
                            } catch (e) {
                                console.warn('[AI Data Loader] Error setting short_description via field:', e);
                            }
                        }
                    });
                });
            }, 500);

            return updated;
        },

        /**
         * Show success message
         */
        showSuccessMessage: function () {
            var message = $t('AI product data has been automatically filled into the form.');
            $('body').notification('clear').notification('add', {
                message: message,
                level: 'success'
            });
        },

        /**
         * Show fallback popup for manual data entry
         */
        showFallbackPopup: function () {
            console.log('[AI Data Loader] Showing fallback popup');
            
            var self = this;
            var popupContent = '<div id="ai-data-popup" style="display: none;">' +
                '<div class="admin__scope-old" style="padding: 20px;">' +
                '<h2>' + $t('AI Product Data Available') + '</h2>' +
                '<p>' + $t('Some fields could not be automatically filled. Click the button below to fill all available fields.') + '</p>' +
                '<div style="margin-top: 20px;">' +
                '<button id="apply-ai-data-btn" class="action-primary" type="button">' +
                '<span>' + $t('Apply AI Data to Form') + '</span>' +
                '</button>' +
                '</div>' +
                '</div>' +
                '</div>';

            // Remove existing popup if any
            $('#ai-data-popup').remove();
            
            // Add popup to body
            $('body').append(popupContent);

            var popupElement = $('#ai-data-popup');
            var popupOptions = {
                type: 'popup',
                modalClass: 'ai-data-popup',
                responsive: true,
                innerScroll: true,
                title: $t('AI Product Data'),
                buttons: [{
                    text: $t('Close'),
                    class: 'action-secondary',
                    click: function () {
                        this.closeModal();
                    }
                }]
            };

            modal(popupOptions, popupElement);
            popupElement.modal('openModal');

            // Handle apply button click
            $('#apply-ai-data-btn').on('click', function () {
                self.applyAiDataManually();
                popupElement.modal('closeModal');
            });
        },

        /**
         * Apply AI data manually
         */
        applyAiDataManually: function () {
            console.log('[AI Data Loader] Applying AI data manually...');
            
            var self = this;
            var appliedCount = 0;

            // Get data source and product ID for description handling
            registry.get(this.dataSourceName, function (dataSource) {
                var productId = null;
                if (dataSource && dataSource.data) {
                    var keys = Object.keys(dataSource.data);
                    if (keys.length > 0) {
                        productId = keys[0];
                    } else {
                        productId = 'new';
                    }
                } else {
                    productId = 'new';
                }

                Object.keys(self.aiData).forEach(function (attributeCode) {
                    var value = self.aiData[attributeCode];
                    
                    // Special handling for description and short_description
                    if (attributeCode === 'description') {
                        if (self.updateDescriptionContent(value, dataSource, productId)) {
                            appliedCount++;
                        }
                    } else if (attributeCode === 'short_description') {
                        if (self.updateShortDescriptionContent(value)) {
                            appliedCount++;
                        }
                    } else {
                        // Regular field handling
                        var fieldPath = self.formName + '.' + attributeCode;

                        registry.get(fieldPath, function (field) {
                            if (field) {
                                try {
                                    if (field.setValue) {
                                        field.setValue(value);
                                        appliedCount++;
                                    } else if (field.value) {
                                        if (typeof field.value === 'function') {
                                            field.value(value);
                                        } else {
                                            field.value = value;
                                        }
                                        appliedCount++;
                                    }
                                } catch (e) {
                                    console.error('[AI Data Loader] Error applying ' + attributeCode + ':', e);
                                }
                            }
                        });
                    }
                });

                console.log('[AI Data Loader] Manually applied ' + appliedCount + ' fields');
                
                if (appliedCount > 0) {
                    $('body').notification('clear').notification('add', {
                        message: $t('Applied AI data to ' + appliedCount + ' field(s).'),
                        level: 'success'
                    });
                }
            });
        }
    };

    // Initialize automatically when module is loaded
    // Only run on product new/edit pages
    if (window.location.pathname.indexOf('/catalog/product/') !== -1) {
        console.log('[AI Data Loader] Product page detected, initializing...');
        
        // Wait for DOM and Magento UI components to initialize
        $(document).ready(function () {
            setTimeout(function () {
                AiDataLoader.init();
            }, 1000);
        });
    }

    return AiDataLoader;
});

