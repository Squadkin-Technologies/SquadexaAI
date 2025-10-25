/**
 * Copyright © 2024 Squadkin. All rights reserved.
 * See COPYING.txt for license details.
 * 
 * Product Generation Initializer
 */

define([
    'jquery',
    'Squadkin_SquadexaAI/js/product-generation/single-form',
    'Squadkin_SquadexaAI/js/product-generation/csv-upload'
], function ($, singleFormWidget, csvUploadWidget) {
    'use strict';

    return function (config) {
        console.log('SquadexaAI: Initializer called with config:', config);
        console.log('Single form widget:', singleFormWidget);
        console.log('CSV upload widget:', csvUploadWidget);
        
        // Initialize Single Product Form Widget
        if (config.singleForm && $(config.singleForm.formSelector).length) {
            console.log('Attempting to initialize single form widget...');
            console.log('Widget available?', typeof $.mage.singleProductForm);
            $(config.singleForm.formSelector).singleProductForm(config.singleForm);
            console.log('Single Product Form widget initialized');
        }
        
        // Initialize CSV Upload Form Widget
        if (config.csvForm && $(config.csvForm.formSelector).length) {
            console.log('Attempting to initialize CSV upload widget...');
            console.log('Widget available?', typeof $.mage.csvUploadForm);
            $(config.csvForm.formSelector).csvUploadForm(config.csvForm);
            console.log('CSV Upload Form widget initialized');
        }
    };
});

