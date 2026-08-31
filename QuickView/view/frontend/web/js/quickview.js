define([
    'jquery',
    'Magento_Ui/js/modal/modal',
    'mage/url'
], function($, modal, urlBuilder) {
    'use strict';
    return function(config, element) {
        var options = {
            type: 'popup',
            responsive: true,
            innerScroll: true,
            title: 'Product Quick View',
            buttons: []
        };
        var $modal = $('#' + config.modalId);
        var popup = modal(options, $modal);
        $(element).on('click', function() {
            var productId = $(this).data('product-id');
            var ajaxUrl = urlBuilder.build('quickview/product/view');
            $modal.modal('openModal');
            $modal.find('.qv-name').text('Loading...');
            $modal.find('.qv-image').attr('src', '');
            $modal.find('.qv-sku, .qv-price, .qv-stock, .qv-short-description, .qv-description').empty();
            $modal.find('.qv-product-url').attr('href', '#');
            $.ajax({
                url: ajaxUrl,
                type: 'GET',
                dataType: 'json',
                data: { id: productId },
                success: function(response) {
                    if (response.success) {
                        var p = response.product;
                        $modal.find('.qv-image').attr('src', p.image);
                        $modal.find('.qv-name').text(p.name);
                        $modal.find('.qv-sku').text(p.sku);
                        $modal.find('.qv-price').text(p.price);
                        $modal.find('.qv-stock').text(p.stock_status);
                        $modal.find('.qv-short-description').html(p.short_description);
                        $modal.find('.qv-description').html(p.description);
                        $modal.find('.qv-product-url').attr('href', p.product_url);
                    } else {
                        $modal.find('.qv-name').text('Error loading product details.');
                    }
                },
                error: function() {
                    $modal.find('.qv-name').text('Failed to process request.');
                }
            });
        });
    };
});
