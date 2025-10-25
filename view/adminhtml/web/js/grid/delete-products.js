/**
 * Delete Products Widget
 * Deletes products from both AI table and Magento catalog
 */
define([
    'jquery',
    'Magento_Ui/js/modal/confirm',
    'mage/translate',
    'Magento_Ui/js/modal/alert'
], function ($, confirmation, $t, alert) {
    'use strict';

    $.widget('squadkin.deleteProducts', {
        options: {
            csvId: null,
            deleteUrl: ''
        },

        _create: function () {
            this._bind();
        },

        _bind: function () {
            var self = this;
            
            this.element.on('click', function (e) {
                e.preventDefault();
                self._confirmDelete();
            });
        },

        _confirmDelete: function () {
            var self = this;
            
            confirmation({
                title: $t('Delete Products'),
                content: $t('Are you sure you want to delete these products? This will remove them from both the AI generated products table and the Magento catalog. This action cannot be undone.'),
                actions: {
                    confirm: function () {
                        self._deleteProducts();
                    }
                }
            });
        },

        _deleteProducts: function () {
            var self = this;
            
            $.ajax({
                url: this.options.deleteUrl,
                type: 'POST',
                data: {
                    csv_id: this.options.csvId,
                    form_key: window.FORM_KEY
                },
                dataType: 'json',
                showLoader: true,
                success: function (response) {
                    if (response.success) {
                        alert({
                            title: $t('Success'),
                            content: response.message,
                            actions: {
                                always: function () {
                                    // Reload grid
                                    window.location.reload();
                                }
                            }
                        });
                    } else {
                        alert({
                            title: $t('Error'),
                            content: response.message || $t('Failed to delete products.')
                        });
                    }
                },
                error: function () {
                    alert({
                        title: $t('Error'),
                        content: $t('An error occurred while deleting products.')
                    });
                }
            });
        }
    });

    return $.squadkin.deleteProducts;
});

