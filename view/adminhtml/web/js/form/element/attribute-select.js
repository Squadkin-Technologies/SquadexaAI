define([
    'Magento_Ui/js/form/element/select',
    'uiRegistry',
    'mage/url',
    'jquery'
], function (Select, registry, urlBuilder, $) {
    'use strict';

    return Select.extend({
        defaults: {
            attributeDetailsUrl: '',
            imports: {
                attributeDetailsUrl: '${ $.parentName }:attributeDetailsUrl'
            }
        },

        /**
         * Initialize component
         */
        initialize: function () {
            this._super();
            this.on('value', this.onAttributeChange.bind(this));
            return this;
        },

        /**
         * Handle attribute selection change
         */
        onAttributeChange: function (value) {
            if (!value) {
                this.hideAllValueFields();
                return;
            }

            this.fetchAttributeDetails(value);
        },

        /**
         * Fetch attribute details from server
         */
        fetchAttributeDetails: function (attributeCode) {
            var self = this;
            var url = this.attributeDetailsUrl || urlBuilder.build('squadkin_squadexaai/aiproduct/attributedetails');
            
            $.ajax({
                url: url,
                type: 'GET',
                data: {
                    attribute_code: attributeCode
                },
                dataType: 'json',
                showLoader: true,
                success: function (response) {
                    if (response.success && response.attribute) {
                        self.updateValueFields(response.attribute);
                    } else {
                        self.showDefaultValueField();
                    }
                },
                error: function (xhr, status, error) {
                    self.showDefaultValueField();
                }
            });
        },

        /**
         * Update value fields based on attribute type
         */
        updateValueFields: function (attribute) {
            var valueContainer = this.getValueContainer();
            
            if (!valueContainer) {
                return;
            }

            // Hide all value fields first
            this.hideAllValueFields();

            // Show appropriate field based on attribute type
            switch (attribute.type) {
                case 'select':
                    this.showSelectField(attribute);
                    break;
                case 'multiselect':
                    this.showMultiselectField(attribute);
                    break;
                case 'boolean':
                    this.showBooleanField();
                    break;
                case 'date':
                    this.showDateField();
                    break;
                case 'textarea':
                    this.showTextareaField();
                    break;
                case 'text':
                case 'price':
                case 'weight':
                default:
                    this.showDefaultValueField();
                    break;
            }

            // Update validation based on attribute requirements
            this.updateValidation(attribute);
        },

        /**
         * Get value container component
         */
        getValueContainer: function () {
            var containerName = this.parentName + '.value_container';
            return registry.get(containerName);
        },

        /**
         * Hide all value fields
         */
        hideAllValueFields: function () {
            var fields = [
                'value', 'value_select', 'value_multiselect', 
                'value_boolean', 'value_date', 'value_textarea'
            ];
            
            fields.forEach(function (fieldName) {
                var field = this.getValueField(fieldName);
                if (field) {
                    field.visible(false);
                }
            }.bind(this));
        },

        /**
         * Show default text input field
         */
        showDefaultValueField: function () {
            var field = this.getValueField('value');
            if (field) {
                field.visible(true);
            }
        },

        /**
         * Show select field with options
         */
        showSelectField: function (attribute) {
            var field = this.getValueField('value_select');
            if (field && attribute.options) {
                // Update options
                var options = attribute.options.map(function (option) {
                    return {
                        value: option.value,
                        label: option.label
                    };
                });
                
                // Add empty option
                options.unshift({
                    value: '',
                    label: '-- Please Select Value --'
                });
                
                field.setOptions(options);
                field.visible(true);
            } else {
                this.showDefaultValueField();
            }
        },

        /**
         * Show multiselect field with options
         */
        showMultiselectField: function (attribute) {
            var field = this.getValueField('value_multiselect');
            if (field && attribute.options) {
                // Update options
                var options = attribute.options.map(function (option) {
                    return {
                        value: option.value,
                        label: option.label
                    };
                });
                
                field.setOptions(options);
                field.visible(true);
            } else {
                this.showDefaultValueField();
            }
        },

        /**
         * Show boolean field
         */
        showBooleanField: function () {
            var field = this.getValueField('value_boolean');
            if (field) {
                field.visible(true);
            }
        },

        /**
         * Show date field
         */
        showDateField: function () {
            var field = this.getValueField('value_date');
            if (field) {
                field.visible(true);
            }
        },

        /**
         * Show textarea field
         */
        showTextareaField: function () {
            var field = this.getValueField('value_textarea');
            if (field) {
                field.visible(true);
            }
        },

        /**
         * Get value field by name
         */
        getValueField: function (fieldName) {
            var fieldPath = this.parentName + '.value_container.' + fieldName;
            return registry.get(fieldPath);
        },

        /**
         * Update validation based on attribute requirements
         */
        updateValidation: function (attribute) {
            var visibleField = this.getVisibleValueField();
            if (visibleField && attribute.required) {
                visibleField.validation = visibleField.validation || {};
                visibleField.validation['required-entry'] = true;
            }
        },

        /**
         * Get currently visible value field
         */
        getVisibleValueField: function () {
            var fields = [
                'value', 'value_select', 'value_multiselect', 
                'value_boolean', 'value_date', 'value_textarea'
            ];
            
            for (var i = 0; i < fields.length; i++) {
                var field = this.getValueField(fields[i]);
                if (field && field.visible()) {
                    return field;
                }
            }
            
            return null;
        }
    });
}); 