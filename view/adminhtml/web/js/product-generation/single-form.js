/**
 * Copyright © 2024 Squadkin. All rights reserved.
 * See COPYING.txt for license details.
 * 
 * Single Product Generation Form Handler
 */

define([
    'jquery',
    'jquery/ui',
    'mage/translate',
    'Magento_Ui/js/modal/alert',
    'mage/validation',
    'uiRegistry'
], function ($, jqueryui, $t, alert, validation, registry) {
    'use strict';

    $.widget('mage.singleProductForm', {
        options: {
            formSelector: '#single-product-form',
            submitButtonSelector: '#generate-single-button',
            resultContainerSelector: '#single-product-result',
            generateUrl: '',
            formKey: ''
        },

        /**
         * Widget initialization
         * @private
         */
        _create: function () {
            console.log('SquadexaAI: Single form widget created with URL:', this.options.generateUrl);
            this._initValidation();
            this._bind();
        },

        /**
         * Initialize form validation
         * @private
         */
        _initValidation: function () {
            $(this.options.formSelector).validation();
        },

        /**
         * Bind events
         * @private
         */
        _bind: function () {
            var self = this;
            
            // Prevent default form submission
            this.element.on('submit', $.proxy(this._handleSubmit, this));
            
            console.log('Single Product Form initialized');
        },

        /**
         * Handle form submission
         * @private
         */
        _handleSubmit: function (e) {
            e.preventDefault();
            e.stopPropagation();
            
            var $form = $(this.options.formSelector);
            var $submitButton = $(this.options.submitButtonSelector);
            var $resultContainer = $(this.options.resultContainerSelector);

            // Validate form
            if (!$form.valid()) {
                return false;
            }

            // Get form data
            var formData = {
                form_key: this.options.formKey,
                product_name: $form.find('#product_name').val(),
                primary_keywords: $form.find('#primary_keywords').val(),
                secondary_keywords: $form.find('#secondary_keywords').val() || '',
                include_pricing: $form.find('#include_pricing').is(':checked') ? 1 : 0
            };

            console.log('Submitting form data:', formData);

            // Show loading state
            $submitButton.prop('disabled', true).addClass('disabled');
            var originalText = $submitButton.find('span').text();
            $submitButton.find('span').text($t('Generating...'));
            $resultContainer.empty();

            // Submit via AJAX
            $.ajax({
                url: this.options.generateUrl,
                type: 'POST',
                data: formData,
                dataType: 'json',
                showLoader: true,
                success: function (response) {
                    if (response.success) {
                        // Display result
                        var html = '<div class="message message-success success">' +
                            '<div><strong>' + $t('Product Generated Successfully!') + '</strong></div>' +
                            '<div class="product-preview">';
                        
                        if (response.data && response.data.name) {
                            html += '<p><strong>' + $t('Name:') + '</strong> ' + response.data.name + '</p>';
                        }
                        if (response.data && response.data.sku) {
                            html += '<p><strong>' + $t('SKU:') + '</strong> ' + response.data.sku + '</p>';
                        }
                        if (response.data && response.data.price) {
                            html += '<p><strong>' + $t('Price:') + '</strong> $' + response.data.price + '</p>';
                        }
                        
                        html += '</div></div>';
                        $resultContainer.html(html);
                        
                        // Show success message
                        alert({
                            title: $t('Success'),
                            content: response.message
                        });
                        
                        // Trigger grid refresh
                        var provider = registry.get('squadkin_squadexaai_generatedcsv_listing.squadkin_squadexaai_generatedcsv_listing_data_source');
                        if (provider && provider.reload) {
                            provider.reload();
                        }
                        
                        // Reset form
                        $form[0].reset();
                    } else {
                        alert({
                            title: $t('Error'),
                            content: response.message || $t('An error occurred while generating the product.')
                        });
                    }
                },
                error: function (xhr, status, error) {
                    console.error('AJAX Error:', error);
                    alert({
                        title: $t('Error'),
                        content: $t('An error occurred while communicating with the server.')
                    });
                },
                complete: function () {
                    $submitButton.prop('disabled', false).removeClass('disabled');
                    $submitButton.find('span').text(originalText);
                }
            });
            
            return false;
        }
    });

    return $.mage.singleProductForm;
});
