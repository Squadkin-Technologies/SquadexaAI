/**
 * Copyright © 2024 Squadkin. All rights reserved.
 */
define([
    'jquery',
    'mage/translate',
    'Magento_Ui/js/modal/modal',
    'Magento_Ui/js/modal/alert'
], function ($, $t, modal, alert) {
    'use strict';

    var mappingConfig = {
        fieldId: null,
        attributes: [],
        existingMappings: {},
        mappings: {},

        /**
         * Initialize
         */
        init: function (config) {
            this.fieldId = config.fieldId;
            this.attributes = config.attributes || [];
            this.existingMappings = config.existingMappings || {};
            this.mappings = $.extend({}, this.existingMappings);

            this.bindEvents();
            this.validateMappings();
            this.updateHiddenField();
        },

        /**
         * Bind events
         */
        bindEvents: function () {
            var self = this;

            // Handle attribute selection change - auto-save to config field
            $(document).on('change', '.attribute-select', function () {
                var aiField = $(this).data('ai-field');
                var selectedAttribute = $(this).val();
                
                if (selectedAttribute) {
                    self.mappings[aiField] = selectedAttribute;
                } else {
                    delete self.mappings[aiField];
                }
                
                self.validateMappings();
                self.updateHiddenField();
            });
        },

        /**
         * Validate mappings (prevent duplicate attribute selection)
         */
        validateMappings: function () {
            var usedAttributes = {};
            var duplicates = [];

            $('.attribute-select').each(function () {
                var aiField = $(this).data('ai-field');
                var selectedAttribute = $(this).val();
                
                if (selectedAttribute) {
                    if (usedAttributes[selectedAttribute]) {
                        duplicates.push({
                            field: aiField,
                            attribute: selectedAttribute,
                            existingField: usedAttributes[selectedAttribute]
                        });
                        $(this).addClass('error');
                    } else {
                        usedAttributes[selectedAttribute] = aiField;
                        $(this).removeClass('error');
                    }
                } else {
                    $(this).removeClass('error');
                }
            });

            // Show warning if duplicates found
            if (duplicates.length > 0) {
                var warningMsg = $t('Warning: The following attributes are mapped to multiple AI fields:');
                duplicates.forEach(function (dup) {
                    warningMsg += '\n- ' + dup.attribute + ' (mapped to both "' + dup.field + '" and "' + dup.existingField + '")';
                });
                
                // You can show a notification here if needed
                console.warn(warningMsg);
            }
        },

        /**
         * Update hidden field with current mappings and sync to main config field
         */
        updateHiddenField: function () {
            var jsonValue = JSON.stringify(this.mappings);
            var self = this;
            
            // Update hidden JSON field
            $('#' + this.fieldId + '_json').val(jsonValue);
            
            // Update the main config field (hidden input) - this will be saved when "Save Config" is clicked
            var mainField = $('#' + this.fieldId);
            if (mainField.length > 0) {
                mainField.val(jsonValue);
            } else {
                // Try alternative selectors
                mainField = $('input[name*="[default_mapping_rules]"]').not('#' + this.fieldId + '_json');
                if (mainField.length > 0) {
                    mainField.val(jsonValue);
                }
            }
        }
    };

    // Return function for x-magento-init
    return function (config) {
        mappingConfig.init(config);
        return mappingConfig;
    };
});
