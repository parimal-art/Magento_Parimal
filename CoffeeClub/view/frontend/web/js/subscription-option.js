define([
    'jquery',
    'domReady!'
], function ($) {
    'use strict';

    return function () {
        var form = document.getElementById('coffeeclub-subscription-form');
        if (!form) {
            return;
        }

        var parentIn = form.querySelector('input[name="parent_product_id"]');
        var childIn  = form.querySelector('input[name="child_product_id"]');
        var btn      = form.querySelector('button[type="submit"]');
        var hint     = form.querySelector('.coffeeclub-variant-hint');

        if (!parentIn || !childIn || !btn) {
            console.log('[CoffeeClub] form is not configurable — skipping');
            return;
        }

        var parentId = parseInt(parentIn.value, 10) || 0;
        var map      = window.coffeeclubChildMap || {};

        console.log('[CoffeeClub] init — parent =', parentId,
            '| map size =', Object.keys(map).length);

        /**
         * Read the currently selected swatch options from the DOM.
         * Returns { "size": "5", "color": "10", ... }
         */
        function readSelectedOptions() {
            var result = {};
            var productForm = document.getElementById('product_addtocart_form');
            if (!productForm) {
                return result;
            }

            // Swatch-style attributes (what Luma and most themes emit).
            productForm.querySelectorAll('.swatch-attribute').forEach(function (attrEl) {
                var code = attrEl.getAttribute('attribute-code')
                    || attrEl.getAttribute('data-attribute-code');
                if (!code) {
                    return;
                }
                var sel = attrEl.querySelector('.swatch-option.selected');
                if (!sel) {
                    return;
                }
                var optId = sel.getAttribute('option-id')
                    || sel.getAttribute('data-option-id');
                if (optId) {
                    result[code] = String(optId);
                }
            });

            // Dropdown-style attributes (fallback).
            productForm.querySelectorAll('select.super-attribute-select').forEach(function (sel) {
                // name="super_attribute[143]" → attribute id 143
                var m = (sel.getAttribute('name') || '').match(/super_attribute\[(\d+)\]/);
                if (m && sel.value) {
                    // We store dropdowns by attribute id prefixed so we can tell them apart.
                    result['__id_' + m[1]] = String(sel.value);
                }
            });

            return result;
        }

        /**
         * Build the same normalized key PHP used and look up the child product.
         */
        function resolveChildId() {
            var selected = readSelectedOptions();
            var codes = Object.keys(selected).filter(function (k) {
                return k.indexOf('__id_') !== 0;
            });
            codes.sort();

            if (!codes.length) {
                return 0;
            }

            var parts = [];
            for (var i = 0; i < codes.length; i++) {
                parts.push(codes[i] + ':' + selected[codes[i]]);
            }
            var key = parts.join('|');
            var id = parseInt(map[key], 10) || 0;

            if (id !== parentId && id > 0) {
                return id;
            }
            return 0;
        }

        var last = null;
        function sync() {
            var id = resolveChildId();
            if (id === last) {
                return;
            }
            last = id;

            if (id > 0) {
                childIn.value = id;
                btn.disabled = false;
                if (hint) { hint.style.display = 'none'; }
                console.log('[CoffeeClub] ✅ variant captured:', id);
            } else {
                childIn.value = '';
                btn.disabled = true;
                if (hint) { hint.style.display = ''; }
                console.log('[CoffeeClub] ⏳ no complete variant yet');
            }
        }

        // Any click or change on the page re-evaluates the selection.
        document.addEventListener('click',  function () { setTimeout(sync, 50); setTimeout(sync, 250); }, true);
        document.addEventListener('change', function () { setTimeout(sync, 50); setTimeout(sync, 250); }, true);

        // Safety net.
        setInterval(sync, 400);

        // Never submit without a valid child ID.
        form.addEventListener('submit', function (e) {
            var v = parseInt(childIn.value, 10) || 0;
            console.log('[CoffeeClub] submit — child_product_id =', v);
            if (v <= 0) {
                e.preventDefault();
                alert('Please select a product variant before starting a subscription.');
            }
        });

        sync();
    };
});
