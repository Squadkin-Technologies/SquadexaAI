define([
    'Magento_Ui/js/form/components/group',
    'uiRegistry'
], function (Group, registry) {
    'use strict';

    return Group.extend({
        defaults: {
            currentAttributeType: null,
            valueFieldsMap: {
                'text': 'value',
                'textarea': 'value_textarea',
                'select': 'value_select',
                'multiselect': 'value_multiselect',
                'boolean': 'value_boolean',
                'date': 'value_date',
                'price': 'value',
                'weight': 'value'
            }
        },

        /**
         * Initialize component
         */
        initialize: function () {
            this._super();
            this.initializeValueFields();
            return this;
        },

        /**
         * Initialize value fields
         */
        initializeValueFields: function () {
            var self = this;
            
            // Hide all value fields initially except the default one
            setTimeout(function () {
                self.hideAllValueFields();
                self.showDefaultValueField();
            }, 100);
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
                if (field && field.visible) {
                    field.visible(false);
                }
            }.bind(this));
        },

        /**
         * Show default value field
         */
        showDefaultValueField: function () {
            var field = this.getValueField('value');
            if (field && field.visible) {
                field.visible(true);
            }
        },

        /**
         * Show appropriate value field based on attribute type
         */
        showValueFieldForType: function (attributeType) {
            this.currentAttributeType = attributeType;
            this.hideAllValueFields();
            
            var fieldName = this.valueFieldsMap[attributeType] || 'value';
            var field = this.getValueField(fieldName);
            
            if (field && field.visible) {
                field.visible(true);
            }
        },

        /**
         * Get value field by name
         */
        getValueField: function (fieldName) {
            return registry.get(this.name + '.' + fieldName);
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
                if (field && field.visible && field.visible()) {
                    return field;
                }
            }
            
            return null;
        },

        /**
         * Get current value from visible field
         */
        getCurrentValue: function () {
            var visibleField = this.getVisibleValueField();
            return visibleField ? visibleField.value() : '';
        },

        /**
         * Set value to visible field
         */
        setCurrentValue: function (value) {
            var visibleField = this.getVisibleValueField();
            if (visibleField) {
                visibleField.value(value);
            }
        },

        /**
         * Clear all value fields
         */
        clearAllValues: function () {
            var fields = [
                'value', 'value_select', 'value_multiselect', 
                'value_boolean', 'value_date', 'value_textarea'
            ];
            
            fields.forEach(function (fieldName) {
                var field = this.getValueField(fieldName);
                if (field && field.value) {
                    field.value('');
                }
            }.bind(this));
        },

        /**
         * Update field options for select/multiselect fields
         */
        updateFieldOptions: function (options) {
            var selectField = this.getValueField('value_select');
            var multiselectField = this.getValueField('value_multiselect');
            
            if (selectField && selectField.setOptions) {
                var selectOptions = options.slice();
                selectOptions.unshift({
                    value: '',
                    label: '-- Please Select Value --'
                });
                selectField.setOptions(selectOptions);
            }
            
            if (multiselectField && multiselectField.setOptions) {
                multiselectField.setOptions(options);
            }
        },

        /**
         * Validate current value
         */
        validateValue: function () {
            var visibleField = this.getVisibleValueField();
            
            if (!visibleField) {
                return {
                    valid: false,
                    message: 'No value field is visible'
                };
            }

            var value = visibleField.value();
            
            if (!value || value === '') {
                return {
                    valid: false,
                    message: 'Please enter a value'
                };
            }

            return {
                valid: true,
                value: value
            };
        }
    });
}); 