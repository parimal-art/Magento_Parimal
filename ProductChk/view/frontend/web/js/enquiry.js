define([
    'jquery',
    'Magento_Ui/js/modal/modal',
    'mage/url',
    'mage/validation'
], function ($, modal, urlBuilder) {
    'use strict';

    return function (config, element) {
        var modalElement = $(config.modalSelector);
        var form = $(config.formSelector);
        var options = {
            type: 'popup',
            responsive: true,
            innerScroll: true,
            title: config.title || $.mage.__('Product Enquiry'),
            buttons: [
                {
                    text: $.mage.__('Submit Enquiry'),
                    class: 'action primary',
                    click: function () {
                        form.validation();
                        if (!form.validation('isValid')) {
                            return;
                        }
                        $('body').trigger('processStart');
                        var formData = {
                            form_key: $('input[name="form_key"]').val(),
                            enquiry: {
                                name: $('#enquiry-name').val(),
                                email: $('#enquiry-email').val(),
                                address: $('#enquiry-address').val(),
                                sku: $('#enquiry-sku').val(),
                                qty: $('#enquiry-qty').val()
                            }
                        };
                        $.ajax({
                            url: urlBuilder.build('productchk/enquiry/save'),
                            type: 'POST',
                            data: formData,
                            dataType: 'json',
                            success: function (response) {
                                if (response.success) {
                                    window.location.reload();
                                }
                            },
                            error: function () {
                                window.location.reload();
                            },
                            complete: function () {
                                $('body').trigger('processStop');
                            }
                        });
                    }
                }
            ]
        };

        modal(options, modalElement);

        $(element).on('click', function () {
            modalElement.modal('openModal');
        });

        modalElement.find('[data-role="qty-down"]').on('click', function () {
            var qtyInput = document.getElementById('enquiry-qty');
            if (qtyInput) {
                qtyInput.stepDown();
            }
        });

        modalElement.find('[data-role="qty-up"]').on('click', function () {
            var qtyInput = document.getElementById('enquiry-qty');
            if (qtyInput) {
                qtyInput.stepUp();
            }
        });
    };
});
