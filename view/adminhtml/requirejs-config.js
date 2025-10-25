/**
 * Copyright © 2024 Squadkin. All rights reserved.
 * See COPYING.txt for license details.
 */

var config = {
    map: {
        '*': {
            singleProductForm: 'Squadkin_SquadexaAI/js/product-generation/single-form',
            csvUploadForm: 'Squadkin_SquadexaAI/js/product-generation/csv-upload'
        }
    },
    shim: {
        'Squadkin_SquadexaAI/js/product-generation/single-form': {
            deps: ['jquery', 'jquery/ui']
        },
        'Squadkin_SquadexaAI/js/product-generation/csv-upload': {
            deps: ['jquery', 'jquery/ui']
        }
    }
};
