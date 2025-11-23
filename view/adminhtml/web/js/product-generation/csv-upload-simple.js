/**
 * Copyright © 2024 Squadkin. All rights reserved.
 * See COPYING.txt for license details.
 * 
 * CSV Upload Form Handler - Simple Version
 */

define([
    'jquery',
    'mage/translate',
    'Magento_Ui/js/modal/alert'
], function ($, $t, alert) {
    'use strict';

    return function (config) {
        var $form = $(config.formSelector);
        var $submitButton = $(config.submitButtonSelector);
        var $fileInput = $(config.fileInputSelector);
        
        if (!$form.length) {
            return;
        }
        
        var validateFile = function () {
            var file = $fileInput[0].files[0];
            
            if (!file) {
                alert({
                    title: $t('Error'),
                    content: $t('Please select a file to upload.')
                });
                return false;
            }

            var fileName = file.name;
            var fileExt = fileName.substring(fileName.lastIndexOf('.') + 1).toLowerCase();
            
            if (fileExt !== 'csv') {
                alert({
                    title: $t('Error'),
                    content: $t('Please select a valid CSV file.')
                });
                return false;
            }

            var fileSizeMB = file.size / (1024 * 1024);
            if (fileSizeMB > config.maxFileSize) {
                alert({
                    title: $t('Error'),
                    content: $t('File size must be less than %1 MB.').replace('%1', config.maxFileSize)
                });
                return false;
            }

            return true;
        };
        
        $form.on('submit', function (e) {
            if (!validateFile()) {
                e.preventDefault();
                return false;
            }
            
            $submitButton.prop('disabled', true).addClass('disabled');
            $submitButton.find('span').text($t('Uploading...'));
            
            return true;
        });
    };
});

