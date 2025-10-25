/**
 * Copyright © 2024 Squadkin. All rights reserved.
 * See COPYING.txt for license details.
 * 
 * CSV Upload Form Handler
 */

define([
    'jquery',
    'jquery/ui',
    'mage/translate',
    'Magento_Ui/js/modal/alert'
], function ($, jqueryui, $t, alert) {
    'use strict';

    $.widget('mage.csvUploadForm', {
        options: {
            formSelector: '#csv-upload-form',
            submitButtonSelector: '#csv-upload-button',
            fileInputSelector: '#csv_file',
            maxFileSize: 10 // MB
        },

        /**
         * Widget initialization
         * @private
         */
        _create: function () {
            console.log('SquadexaAI: CSV upload widget created');
            this._bind();
        },

        /**
         * Bind events
         * @private
         */
        _bind: function () {
            var self = this;
            
            // Bind form submit event
            this.element.on('submit', $.proxy(this._handleSubmit, this));
            
            // Add file input change event
            $(this.options.fileInputSelector).on('change', function () {
                var fileName = $(this).val().split('\\').pop();
                if (fileName) {
                    console.log('Selected file:', fileName);
                }
            });
            
            console.log('CSV Upload Form initialized');
        },

        /**
         * Handle form submission
         * @private
         */
        _handleSubmit: function (e) {
            if (!this._validateFile()) {
                e.preventDefault();
                return false;
            }
            
            // Show loading state
            var $submitButton = $(this.options.submitButtonSelector);
            $submitButton.prop('disabled', true).addClass('disabled');
            $submitButton.find('span').text($t('Uploading...'));
            
            // Let form submit normally for file upload
            return true;
        },

        /**
         * Validate file
         * @private
         */
        _validateFile: function () {
            var $fileInput = $(this.options.fileInputSelector);
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
            if (fileSizeMB > this.options.maxFileSize) {
                alert({
                    title: $t('Error'),
                    content: $t('File size must be less than %1 MB.').replace('%1', this.options.maxFileSize)
                });
                return false;
            }

            return true;
        }
    });

    return $.mage.csvUploadForm;
});
