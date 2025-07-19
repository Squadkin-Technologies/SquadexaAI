define([
    'Magento_Ui/js/form/components/group',
    'uiRegistry'
], function (Group, registry) {
    'use strict';

    return Group.extend({
        defaults: {
            attributeDetailsUrl: '',
            dynamicRowsName: 'custom_attributes'
        },

        /**
         * Initialize component
         */
        initialize: function () {
            this._super();
            this.initializeCustomAttributes();
            return this;
        },

        /**
         * Initialize custom attributes functionality
         */
        initializeCustomAttributes: function () {
            var self = this;
            
            // Wait for dynamic rows to initialize
            setTimeout(function () {
                self.setupDynamicRowsHandling();
            }, 500);
        },

        /**
         * Setup dynamic rows handling
         */
        setupDynamicRowsHandling: function () {
            var dynamicRows = this.getDynamicRows();
            
            if (dynamicRows) {
                // Pass attribute details URL to dynamic rows
                dynamicRows.attributeDetailsUrl = this.attributeDetailsUrl;
                
                // Listen for new rows being added
                dynamicRows.on('childrenUpdated', this.onRowsUpdated.bind(this));
            }
        },

        /**
         * Get dynamic rows component
         */
        getDynamicRows: function () {
            var parentName = this.parentName;
            var dynamicRowsPath = parentName + '.' + this.dynamicRowsName;
            return registry.get(dynamicRowsPath);
        },

        /**
         * Handle rows updated event
         */
        onRowsUpdated: function () {
            var dynamicRows = this.getDynamicRows();
            
            if (dynamicRows && dynamicRows.elems) {
                dynamicRows.elems().forEach(function (row) {
                    if (row.attributeDetailsUrl !== this.attributeDetailsUrl) {
                        row.attributeDetailsUrl = this.attributeDetailsUrl;
                    }
                }.bind(this));
            }
        },

        /**
         * Get all attribute data from dynamic rows
         */
        getAllAttributeData: function () {
            var dynamicRows = this.getDynamicRows();
            var attributeData = [];
            
            if (dynamicRows && dynamicRows.elems) {
                dynamicRows.elems().forEach(function (row) {
                    if (row.getCurrentAttributeData) {
                        var data = row.getCurrentAttributeData();
                        if (data && data.attribute_code && data.value) {
                            attributeData.push(data);
                        }
                    }
                });
            }
            
            return attributeData;
        },

        /**
         * Set attribute data to dynamic rows
         */
        setAttributeData: function (attributeData) {
            if (!attributeData || !Array.isArray(attributeData)) {
                return;
            }

            var dynamicRows = this.getDynamicRows();
            
            if (!dynamicRows) {
                return;
            }

            var self = this;
            
            // Clear existing rows
            dynamicRows.clear();
            
            // Add new rows for each attribute
            attributeData.forEach(function (data, index) {
                dynamicRows.addChild();
                
                // Set data after a short delay to ensure row is initialized
                setTimeout(function () {
                    var rows = dynamicRows.elems();
                    if (rows && rows[index] && rows[index].setAttributeData) {
                        rows[index].setAttributeData(data);
                    }
                }, 100 * (index + 1));
            });
        },

        /**
         * Validate all attribute rows
         */
        validateAllRows: function () {
            var dynamicRows = this.getDynamicRows();
            var errors = [];
            var validData = [];
            
            if (dynamicRows && dynamicRows.elems) {
                dynamicRows.elems().forEach(function (row, index) {
                    if (row.validateRow) {
                        var validation = row.validateRow();
                        if (!validation.valid) {
                            errors.push('Row ' + (index + 1) + ': ' + validation.message);
                        } else {
                            validData.push(validation.data);
                        }
                    }
                });
            }
            
            return {
                valid: errors.length === 0,
                errors: errors,
                data: validData
            };
        },

        /**
         * Add new attribute row
         */
        addAttributeRow: function () {
            var dynamicRows = this.getDynamicRows();
            
            if (dynamicRows) {
                dynamicRows.addChild();
            }
        },

        /**
         * Clear all attribute rows
         */
        clearAllRows: function () {
            var dynamicRows = this.getDynamicRows();
            
            if (dynamicRows) {
                dynamicRows.clear();
            }
        },

        /**
         * Get attribute count
         */
        getAttributeCount: function () {
            var dynamicRows = this.getDynamicRows();
            
            if (dynamicRows && dynamicRows.elems) {
                return dynamicRows.elems().length;
            }
            
            return 0;
        }
    });
}); 