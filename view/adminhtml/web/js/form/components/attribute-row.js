define([
    'Magento_Ui/js/dynamic-rows/record',
    'uiRegistry'
], function (Record, registry) {
    'use strict';

    return Record.extend({
        defaults: {
            attributeDetailsUrl: ''
        },

        /**
         * Initialize component
         */
        initialize: function () {
            this._super();
            this.initializeAttributeHandling();
            return this;
        },

        /**
         * Initialize attribute handling
         */
        initializeAttributeHandling: function () {
            var self = this;
            
            // Wait for child components to initialize
            setTimeout(function () {
                self.setupAttributeListeners();
            }, 100);
        },

        /**
         * Setup attribute change listeners
         */
        setupAttributeListeners: function () {
            var attributeSelect = this.getAttributeSelect();
            if (attributeSelect) {
                attributeSelect.attributeDetailsUrl = this.attributeDetailsUrl;
            }
        },

        /**
         * Get attribute select component
         */
        getAttributeSelect: function () {
            return registry.get(this.name + '.attribute_code');
        },

        /**
         * Get value container component
         */
        getValueContainer: function () {
            return registry.get(this.name + '.value_container');
        },

        /**
         * Get the current attribute data
         */
        getCurrentAttributeData: function () {
            var attributeSelect = this.getAttributeSelect();
            var valueContainer = this.getValueContainer();
            
            if (!attributeSelect || !valueContainer) {
                return null;
            }

            var attributeCode = attributeSelect.value();
            var value = this.getCurrentValue();

            return {
                attribute_code: attributeCode,
                value: value
            };
        },

        /**
         * Get current value from visible field
         */
        getCurrentValue: function () {
            var valueFields = [
                'value', 'value_select', 'value_multiselect', 
                'value_boolean', 'value_date', 'value_textarea'
            ];
            
            for (var i = 0; i < valueFields.length; i++) {
                var field = registry.get(this.name + '.value_container.' + valueFields[i]);
                if (field && field.visible && field.visible()) {
                    return field.value();
                }
            }
            
            return '';
        },

        /**
         * Set attribute data
         */
        setAttributeData: function (data) {
            if (!data || !data.attribute_code) {
                return;
            }

            var attributeSelect = this.getAttributeSelect();
            if (attributeSelect) {
                attributeSelect.value(data.attribute_code);
                
                // Trigger change to update value fields
                if (data.value) {
                    var self = this;
                    setTimeout(function () {
                        self.setCurrentValue(data.value);
                    }, 500); // Wait for attribute details to load
                }
            }
        },

        /**
         * Set current value to visible field
         */
        setCurrentValue: function (value) {
            var valueFields = [
                'value', 'value_select', 'value_multiselect', 
                'value_boolean', 'value_date', 'value_textarea'
            ];
            
            for (var i = 0; i < valueFields.length; i++) {
                var field = registry.get(this.name + '.value_container.' + valueFields[i]);
                if (field && field.visible && field.visible()) {
                    field.value(value);
                    break;
                }
            }
        },

        /**
         * Validate current row
         */
        validateRow: function () {
            var data = this.getCurrentAttributeData();
            
            if (!data || !data.attribute_code) {
                return {
                    valid: false,
                    message: 'Please select an attribute'
                };
            }

            if (!data.value) {
                return {
                    valid: false,
                    message: 'Please enter a value for the selected attribute'
                };
            }

            return {
                valid: true,
                data: data
            };
        }
    });
}); 