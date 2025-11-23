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
        var $form = $(config.formSelector);
        var $submitButton = $(config.submitButtonSelector);
        var $resultContainer = $(config.resultContainerSelector);
        
        if (!$form.length) {
            return;
        }
        
        $form.validation();
        
        $form.on('submit', function (e) {
            e.preventDefault();
            e.stopPropagation();
            
            if (!$form.valid()) {
                return false;
            }
            
            var formData = {
                form_key: config.formKey,
                product_name: $form.find('#product_name').val(),
                primary_keywords: $form.find('#primary_keywords').val(),
                secondary_keywords: $form.find('#secondary_keywords').val() || '',
                include_pricing: $form.find('#include_pricing').is(':checked') ? 1 : 0
            };
            
            $submitButton.prop('disabled', true).addClass('disabled');
            var originalText = $submitButton.find('span').text();
            $submitButton.find('span').text($t('Generating...'));
            $resultContainer.empty();
            
            $.ajax({
                url: config.generateUrl,
                type: 'POST',
                data: formData,
                dataType: 'json',
                showLoader: true,
                success: function (response) {
                    if (response.success) {
                        alert({
                            title: $t('Success'),
                            content: response.message
                        });
                        
                        if (response.redirect_url) {
                            setTimeout(function() {
                                window.location.href = response.redirect_url;
                            }, 1500);
                        } else {
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
                error: function () {
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
    };
});

