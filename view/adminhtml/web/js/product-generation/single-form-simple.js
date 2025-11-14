/**
 * Copyright © 2024 Squadkin. All rights reserved.
 * See COPYING.txt for license details.
 * 
 * Single Product Generation Form Handler - Simple Version
 */

define([
    'jquery',
    'mage/translate',
    'Magento_Ui/js/modal/alert',
    'mage/validation',
    'uiRegistry'
], function ($, $t, alert, validation, registry) {
    'use strict';

    return function (config) {
        console.log('SquadexaAI: Single form initializing with URL:', config.generateUrl);
        
        var $form = $(config.formSelector);
        var $submitButton = $(config.submitButtonSelector);
        var $resultContainer = $(config.resultContainerSelector);
        
        if (!$form.length) {
            console.error('Form not found:', config.formSelector);
            return;
        }
        
        // Initialize validation
        $form.validation();
        
        // Prevent default form submission
        $form.on('submit', function (e) {
            e.preventDefault();
            e.stopPropagation();
            
            console.log('Form submit intercepted');
            
            // Validate form
            if (!$form.valid()) {
                console.log('Form validation failed');
                return false;
            }
            
            // Get form data
            var formData = {
                form_key: config.formKey,
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
                url: config.generateUrl,
                type: 'POST',
                data: formData,
                dataType: 'json',
                showLoader: true,
                success: function (response) {
                    console.log('API Response:', response);
                    
                    if (response.success) {
                        // Show success message
                        alert({
                            title: $t('Success'),
                            content: response.message
                        });
                        
                        // Redirect to Generated CSV page after a short delay
                        if (response.redirect_url) {
                            setTimeout(function() {
                                window.location.href = response.redirect_url;
                            }, 1500);
                        } else {
                            // Fallback: redirect to Generated CSV page
                            setTimeout(function() {
                                window.location.href = config.generatedCsvUrl || 'squadkin_squadexaai/generatedcsv/index';
                            }, 1500);
                        }
                    } else {
                        alert({
                            title: $t('Error'),
                            content: response.message || $t('An error occurred while generating the product.')
                        });
                    }
                },
                error: function (xhr, status, error) {
                    console.error('AJAX Error:', error);
                    console.error('Response:', xhr.responseText);
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
        });
        
        console.log('Single Product Form initialized successfully');
    };
});

