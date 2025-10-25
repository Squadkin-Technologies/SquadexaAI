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
        console.log('SquadexaAI: CSV upload initializing');
        
        var $form = $(config.formSelector);
        var $submitButton = $(config.submitButtonSelector);
        var $fileInput = $(config.fileInputSelector);
        
        if (!$form.length) {
            console.error('Form not found:', config.formSelector);
            return;
        }
        
        // Validate file
        var validateFile = function () {
            var file = $fileInput[0].files[0];
            
            if (!file) {
                alert({
                    title: $t('Error'),
                    content: $t('Please select a file to upload.')
                });
                return false;
            }

            // Check file extension
            var fileName = file.name;
            var fileExt = fileName.substring(fileName.lastIndexOf('.') + 1).toLowerCase();
            
            if (fileExt !== 'csv') {
                alert({
                    title: $t('Error'),
                    content: $t('Please select a valid CSV file.')
                });
                return false;
            }

            // Check file size
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
        
        // Handle form submission
        $form.on('submit', function (e) {
            if (!validateFile()) {
                e.preventDefault();
                return false;
            }
            
            // Show loading state
            $submitButton.prop('disabled', true).addClass('disabled');
            $submitButton.find('span').text($t('Uploading...'));
            
            // Let form submit normally for file upload
            return true;
        });
        
        // File input change event
        $fileInput.on('change', function () {
            var fileName = $(this).val().split('\\').pop();
            if (fileName) {
                console.log('Selected file:', fileName);
            }
        });
        
        console.log('CSV Upload Form initialized successfully');
    };
});

