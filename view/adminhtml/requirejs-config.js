/**
 * Copyright © 2024 Squadkin. All rights reserved.
 * See COPYING.txt for license details.
 */

var config = {
    map: {
        '*': {
            singleProductForm: 'Squadkin_SquadexaAI/js/product-generation/single-form',
            csvUploadForm: 'Squadkin_SquadexaAI/js/product-generation/csv-upload',
            createProductFromAiModal: 'Squadkin_SquadexaAI/js/create-product-modal',
            updateProductFromAi: 'Squadkin_SquadexaAI/js/update-product-from-ai',
            fieldMappingConfig: 'Squadkin_SquadexaAI/js/field-mapping-config',
            productFormAiDataLoader: 'Squadkin_SquadexaAI/js/product-form-ai-data-loader'
        }
    },
    config: {
        mixins: {
            'Magento_Ui/js/grid/columns/actions': {
                'Squadkin_SquadexaAI/js/grid/columns/actions-mixin': true
            }
        }
    },
    shim: {
        'Squadkin_SquadexaAI/js/product-generation/single-form': {
            deps: ['jquery', 'jquery/ui']
        },
        'Squadkin_SquadexaAI/js/product-generation/csv-upload': {
            deps: ['jquery', 'jquery/ui']
        },
        'Squadkin_SquadexaAI/js/create-product-modal': {
            deps: ['jquery', 'Magento_Ui/js/modal/modal', 'mage/url', 'mage/translate']
        },
        'Squadkin_SquadexaAI/js/update-product-from-ai': {
            deps: ['jquery', 'uiComponent', 'Magento_Ui/js/modal/modal']
        }
    }
};
