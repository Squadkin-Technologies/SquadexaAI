/**
 * Create Product Modal Widget
 * Opens a modal with form fields to create Magento products from AI generated data
 */
define([
    'jquery',
    'Magento_Ui/js/modal/modal',
    'mage/translate',
    'Magento_Ui/js/modal/alert',
    'mage/template',
    'text!Squadkin_SquadexaAI/template/product-create-form.html'
], function ($, modal, $t, alert, mageTemplate, formTemplate) {
    'use strict';

    $.widget('squadkin.createProductModal', {
        options: {
            csvId: null,
            generationType: 'csv',
            modalUrl: '',
            createUrl: ''
        },

        _create: function () {
            this._bind();
        },

        _bind: function () {
            var self = this;
            
            this.element.on('click', function (e) {
                e.preventDefault();
                self._openModal();
            });
        },

        _openModal: function () {
            var self = this;
            
            // Load product data via AJAX
            $.ajax({
                url: this.options.modalUrl,
                type: 'GET',
                dataType: 'json',
                showLoader: true,
                success: function (response) {
                    if (response.success) {
                        self._renderModal(response.products, response.generation_type);
                    } else {
                        alert({
                            title: $t('Error'),
                            content: response.message || $t('Failed to load product data.')
                        });
                    }
                },
                error: function () {
                    alert({
                        title: $t('Error'),
                        content: $t('An error occurred while loading product data.')
                    });
                }
            });
        },

        _renderModal: function (products, generationType) {
            var self = this;
            
            // Compile template
            var tmpl = mageTemplate(formTemplate);
            var modalHtml = tmpl({
                products: products,
                generationType: generationType,
                isSingle: generationType === 'single'
            });
            
            // Create modal container
            var $modalContainer = $('<div/>').html(modalHtml);
            
            // Initialize modal
            var modalOptions = {
                type: 'slide',
                responsive: true,
                innerScroll: true,
                title: generationType === 'single' ? $t('Create Product in Magento') : $t('Create Products in Magento'),
                buttons: [
                    {
                        text: $t('Cancel'),
                        class: 'action-secondary action-dismiss',
                        click: function () {
                            this.closeModal();
                        }
                    },
                    {
                        text: generationType === 'single' ? $t('Create Product') : $t('Create Products'),
                        class: 'action-primary action-accept',
                        click: function () {
                            self._submitForm($modalContainer, this);
                        }
                    }
                ]
            };
            
            var modalInstance = modal(modalOptions, $modalContainer);
            $modalContainer.modal('openModal');
        },

        _submitForm: function ($modalContainer, modalInstance) {
            var self = this;
            var $form = $modalContainer.find('#product-create-form');
            
            // Validate form
            if (!$form.valid()) {
                return false;
            }
            
            // Collect form data
            var formData = {
                csv_id: this.options.csvId,
                product: {}
            };
            
            // Serialize product data
            $form.find('.product-data-row').each(function () {
                var $row = $(this);
                var productId = $row.data('product-id');
                
                formData.product[productId] = {
                    sku: $row.find('[name$="[sku]"]').val(),
                    name: $row.find('[name$="[name]"]').val(),
                    price: $row.find('[name$="[price]"]').val(),
                    qty: $row.find('[name$="[qty]"]').val(),
                    weight: $row.find('[name$="[weight]"]').val(),
                    status: $row.find('[name$="[status]"]').val(),
                    visibility: $row.find('[name$="[visibility]"]').val(),
                    description: $row.find('[name$="[description]"]').val(),
                    short_description: $row.find('[name$="[short_description]"]').val(),
                    meta_title: $row.find('[name$="[meta_title]"]').val(),
                    meta_description: $row.find('[name$="[meta_description]"]').val(),
                    meta_keywords: $row.find('[name$="[meta_keywords]"]').val(),
                    url_key: $row.find('[name$="[url_key]"]').val(),
                    tax_class_id: $row.find('[name$="[tax_class_id]"]').val(),
                    special_price: $row.find('[name$="[special_price]"]').val()
                };
            });
            
            // Submit via AJAX
            $.ajax({
                url: this.options.createUrl,
                type: 'POST',
                data: formData,
                dataType: 'json',
                showLoader: true,
                success: function (response) {
                    if (response.success) {
                        alert({
                            title: $t('Success'),
                            content: response.message,
                            actions: {
                                always: function () {
                                    modalInstance.closeModal();
                                    // Reload grid
                                    window.location.reload();
                                }
                            }
                        });
                    } else {
                        alert({
                            title: $t('Error'),
                            content: response.message || $t('Failed to create products.')
                        });
                    }
                },
                error: function () {
                    alert({
                        title: $t('Error'),
                        content: $t('An error occurred while creating products.')
                    });
                }
            });
        }
    });

    return $.squadkin.createProductModal;
});

