/**
 * Modern eCommerce & Amazon-Style Interactions
 */
(function($) {
    'use strict';

    $(document).ready(function() {

        // ==========================================
        // 1. Toast Notification Helper
        // ==========================================
        window.showModernToast = function(message, actionText, actionUrl) {
            var $toast = $('#modern-toast-notification');
            if ($toast.length === 0) {
                $('body').append(
                    '<div id="modern-toast-notification" class="modern-toast">' +
                        '<i class="fa fa-check-circle"></i>' +
                        '<span class="toast-message"></span>' +
                        '<a href="#" class="toast-btn" style="display:none;"></a>' +
                    '</div>'
                );
                $toast = $('#modern-toast-notification');
            }

            $toast.find('.toast-message').html(message);
            if (actionText && actionUrl) {
                $toast.find('.toast-btn').text(actionText).attr('href', actionUrl).show();
            } else {
                $toast.find('.toast-btn').hide();
            }

            $toast.addClass('show');

            if (window._toastTimeout) {
                clearTimeout(window._toastTimeout);
            }
            window._toastTimeout = setTimeout(function() {
                $toast.removeClass('show');
            }, 3500);
        };

        // ==========================================
        // 2. Instant Live Autocomplete Search
        // ==========================================
        var searchTimer = null;
        var $searchInput = $('#modern-search-input');
        var $suggestionsBox = $('#search-suggestions-box');
        var $catSelect = $('#modern-search-category');

        if ($searchInput.length > 0) {
            $searchInput.on('keyup focus', function() {
                var query = $.trim($(this).val());
                var catId = $catSelect.val() || 0;

                if (query.length < 2) {
                    $suggestionsBox.hide().empty();
                    return;
                }

                clearTimeout(searchTimer);
                searchTimer = setTimeout(function() {
                    $.ajax({
                        url: 'api-search.php',
                        type: 'GET',
                        dataType: 'json',
                        data: { q: query, category: catId },
                        success: function(res) {
                            if (res && res.results && res.results.length > 0) {
                                var html = '';
                                $.each(res.results, function(idx, item) {
                                    html += '<a href="' + item.url + '" class="search-suggestion-item">' +
                                        '<img src="assets/uploads/' + item.photo + '" alt="">' +
                                        '<div class="item-info">' +
                                            '<div class="item-title">' + item.title + '</div>' +
                                            '<div class="item-cat">' + item.category + '</div>' +
                                        '</div>' +
                                        '<div class="item-price">$' + item.price + '</div>' +
                                    '</a>';
                                });
                                html += '<div style="padding: 10px; background: #fafafa; text-align: center; font-size: 13px; font-weight: 600;">' +
                                            '<a href="search-result.php?search_text=' + encodeURIComponent(query) + '" style="color: #007185;">View all matching results &rarr;</a>' +
                                        '</div>';
                                $suggestionsBox.html(html).show();
                            } else {
                                $suggestionsBox.html(
                                    '<div style="padding: 16px; text-align: center; color: #777; font-size: 13px;">' +
                                        '<i class="fa fa-search"></i> No products found for "<strong>' + query + '</strong>"' +
                                    '</div>'
                                ).show();
                            }
                        }
                    });
                }, 220);
            });

            // Close suggestions on outside click
            $(document).on('click', function(e) {
                if (!$(e.target).closest('.search-form-wrapper').length) {
                    $suggestionsBox.hide();
                }
            });

            // Handle Escape key
            $searchInput.on('keydown', function(e) {
                if (e.keyCode === 27) {
                    $suggestionsBox.hide();
                }
            });
        }

        // ==========================================
        // 3. Wishlist Toggle Interaction
        // ==========================================
        $(document).on('click', '.btn-wishlist-toggle', function(e) {
            e.preventDefault();
            var $btn = $(this);
            var productId = $btn.data('id');

            if (!productId) return;

            $.ajax({
                url: 'api-wishlist.php',
                type: 'POST',
                dataType: 'json',
                data: { action: 'toggle', product_id: productId },
                success: function(res) {
                    if (res && res.success) {
                        if (res.in_wishlist) {
                            $btn.addClass('active').find('i').removeClass('fa-heart-o').addClass('fa-heart');
                            showModernToast('<i class="fa fa-heart" style="color:#e4144d;"></i> ' + res.message, 'View Wishlist', 'wishlist.php');
                        } else {
                            $btn.removeClass('active').find('i').removeClass('fa-heart').addClass('fa-heart-o');
                            showModernToast('<i class="fa fa-info-circle"></i> ' + res.message, 'View Wishlist', 'wishlist.php');
                        }

                        // Update all wishlist badge counters
                        $('.wishlist-counter-bubble').text(res.count);
                    }
                }
            });
        });

        // ==========================================
        // 4. Product Quick View Modal Trigger
        // ==========================================
        $(document).on('click', '.btn-quickview', function(e) {
            e.preventDefault();
            var productId = $(this).data('id');
            if (!productId) return;

            var $modal = $('#quickViewModal');
            if ($modal.length === 0) return;

            var $modalBody = $modal.find('.modal-body');
            $modalBody.html(
                '<div style="padding: 40px; text-align: center;">' +
                    '<i class="fa fa-spinner fa-spin fa-2x" style="color: #febd69;"></i>' +
                    '<p style="margin-top: 10px; color: #666;">Loading product details...</p>' +
                '</div>'
            );

            $modal.modal('show');

            $.ajax({
                url: 'api-quickview.php',
                type: 'GET',
                data: { id: productId },
                success: function(data) {
                    $modalBody.html(data);
                },
                error: function() {
                    $modalBody.html('<div class="alert alert-danger">Error loading product details.</div>');
                }
            });
        });

        // ==========================================
        // 5. Real-Time Cart Quantity (+ / -) Auto-Update
        // ==========================================
        var cartUpdateTimers = {};

        function syncCartQty(rowId, newQty, pid, $input, $counter) {
            var $row = $('#cart-row-' + rowId);
            var $totalEl = $('#row-total-' + rowId);
            var unitPrice = parseFloat($input.data('unit-price')) || 0;
            var $statusPill = $('#auto-save-status');
            var $statusText = $('#auto-save-text');

            // Visual feedback: state updating
            if ($statusPill.length) {
                $statusPill.find('.pulse-dot').css('background', '#eab308');
                $statusText.text('Updating total...');
            }

            // Row total pulse animation
            $totalEl.addClass('price-pulse');
            setTimeout(function() {
                $totalEl.removeClass('price-pulse');
            }, 500);

            // Debounced AJAX request to avoid flood
            if (cartUpdateTimers[rowId]) {
                clearTimeout(cartUpdateTimers[rowId]);
            }

            cartUpdateTimers[rowId] = setTimeout(function() {
                $.ajax({
                    url: 'api/cart-update-qty.php',
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'update_qty',
                        row_id: rowId,
                        qty: newQty,
                        product_id: pid
                    },
                    success: function(res) {
                        if (res && res.status === 'success') {
                            // Update row total
                            $totalEl.text(res.row_total_formatted);

                            // Sync actual qty if bounded by stock
                            if (res.qty !== newQty) {
                                $input.val(res.qty);
                            }

                            // Update button disabled states
                            $counter.find('.btn-qty-minus').prop('disabled', res.qty <= 1);
                            $counter.find('.btn-qty-plus').prop('disabled', res.qty >= res.stock_max);

                            // Update Subtotal
                            var $subtotal = $('#summary-subtotal-val');
                            $subtotal.text(res.subtotal_formatted).addClass('price-pulse');
                            setTimeout(function() { $subtotal.removeClass('price-pulse'); }, 500);

                            // Update Promo/Coupon row
                            var $discRow = $('#summary-discount-row');
                            var $discVal = $('#summary-discount-val');
                            if (res.has_coupon) {
                                $discRow.show();
                                $discVal.text(res.discount_formatted);
                                if (res.coupon_code) {
                                    $('#summary-coupon-code').text(res.coupon_code);
                                }
                            } else {
                                $discRow.hide();
                            }

                            // Update Final Total
                            var $finalTotal = $('#summary-final-total-val');
                            $finalTotal.text(res.final_total_formatted).addClass('price-pulse');
                            setTimeout(function() { $finalTotal.removeClass('price-pulse'); }, 500);

                            // Update Dual Currency text
                            var $dualEl = $('#summary-dual-currency');
                            if ($dualEl.length) {
                                if (res.currency === 'KHR') {
                                    $dualEl.html('<span>(' + res.final_total_usd + ' USD)</span>');
                                } else {
                                    $dualEl.html('<span>(≈ ' + res.final_total_khr + ')</span>');
                                }
                            }

                            // Update items count badges
                            var countText = res.cart_item_count + (res.cart_item_count === 1 ? ' item' : ' items');
                            $('#cart-top-count-pill').text(countText);
                            $('#summary-items-count').text(countText);

                            // Update Header Cart badge & total
                            var $headerBubble = $('.cart-counter-bubble');
                            $headerBubble.text(res.cart_item_count).addClass('animated rubberBand');
                            setTimeout(function() { $headerBubble.removeClass('animated rubberBand'); }, 600);

                            var $headerTotal = $('.cart-total-text');
                            if ($headerTotal.length) {
                                $headerTotal.html('<span class="top-line" style="display: block; font-size: 10px; color: #ccc; line-height: 1;">Cart</span>' + res.header_cart_total);
                            }

                            // Auto save status indicator
                            if ($statusPill.length) {
                                $statusPill.find('.pulse-dot').css('background', '#16a34a');
                                $statusText.text('Auto-Saved');
                            }

                            // Stock warning tooltip/toast
                            var $stockWarn = $('#stock-warning-' + rowId);
                            if (res.warning) {
                                $stockWarn.text(res.warning).show();
                                showModernToast('<i class="fa fa-exclamation-triangle" style="color:#eab308;"></i> ' + res.warning);
                            } else {
                                $stockWarn.hide();
                            }
                        } else if (res && res.status === 'error') {
                            showModernToast('<i class="fa fa-times-circle" style="color:#ef4444;"></i> ' + res.message);
                            if ($statusPill.length) {
                                $statusText.text('Sync error');
                            }
                        }
                    },
                    error: function() {
                        if ($statusPill.length) {
                            $statusPill.find('.pulse-dot').css('background', '#ef4444');
                            $statusText.text('Sync error');
                        }
                    }
                });
            }, 180);
        }

        // Global handler for Cart Quantity Change (+/-)
        window.changeCartQty = function(btn, delta) {
            var $counter = $(btn).closest('.modern-qty-counter');
            var $input = $counter.find('.cart-qty-input');
            var rowId = $counter.data('row-id');
            var pid = $counter.data('pid');
            var stockMax = parseInt($counter.data('stock')) || 999;
            var currentQty = parseInt($input.val()) || 1;

            var newQty = currentQty + delta;
            if (newQty < 1) newQty = 1;
            if (newQty > stockMax) {
                newQty = stockMax;
                if (delta > 0) {
                    showModernToast('<i class="fa fa-info-circle"></i> Maximum available stock (' + stockMax + ') reached.');
                }
            }

            if (newQty === currentQty) return;

            // Instant smooth UI update
            $input.val(newQty);
            $counter.find('.btn-qty-minus').prop('disabled', newQty <= 1);
            $counter.find('.btn-qty-plus').prop('disabled', newQty >= stockMax);

            syncCartQty(rowId, newQty, pid, $input, $counter);
        };

        // Cart Quantity Plus (+) - Dedicated Single Listener
        $(document).on('click', '.btn-qty-plus', function(e) {
            e.preventDefault();
            e.stopPropagation();
            window.changeCartQty(this, 1);
        });

        // Cart Quantity Minus (-) - Dedicated Single Listener
        $(document).on('click', '.btn-qty-minus', function(e) {
            e.preventDefault();
            e.stopPropagation();
            window.changeCartQty(this, -1);
        });

        // Cart Direct Input
        $(document).on('change input', '.cart-qty-input', function(e) {
            var $input = $(this);
            var $counter = $input.closest('.modern-qty-counter');
            var rowId = $counter.data('row-id');
            var pid = $counter.data('pid');
            var stockMax = parseInt($counter.data('stock')) || 999;
            var rawVal = parseInt($input.val()) || 1;

            if (rawVal < 1) rawVal = 1;
            if (rawVal > stockMax) {
                rawVal = stockMax;
                showModernToast('<i class="fa fa-info-circle"></i> Only ' + stockMax + ' items in stock.');
            }
            $input.val(rawVal);
            $counter.find('.btn-qty-minus').prop('disabled', rawVal <= 1);
            $counter.find('.btn-qty-plus').prop('disabled', rawVal >= stockMax);
            syncCartQty(rowId, rawVal, pid, $input, $counter);
        });

        // Smooth AJAX Item Removal in Cart
        $(document).on('click', '.btn-ajax-remove', function(e) {
            e.preventDefault();
            var $btn = $(this);
            var rowId = $btn.data('row-id');
            var pid = $btn.closest('tr').data('product-id');
            var itemName = $btn.data('name') || 'this item';
            var $row = $('#cart-row-' + rowId);

            if (!confirm('Are you sure you want to remove "' + itemName + '" from your shopping cart?')) {
                return;
            }

            $row.css('opacity', '0.4').css('pointer-events', 'none');

            $.ajax({
                url: 'api/cart-update-qty.php',
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'remove_item',
                    row_id: rowId,
                    product_id: pid
                },
                success: function(res) {
                    if (res && res.status === 'empty') {
                        window.location.reload();
                    } else if (res && res.status === 'success') {
                        $row.fadeOut(300, function() {
                            $(this).remove();
                            // Re-index remaining rows without touching any content in td cells!
                            $('#modernCartTable tbody tr').each(function(idx) {
                                var newRowId = idx + 1;
                                var $tr = $(this);
                                $tr.attr('id', 'cart-row-' + newRowId).attr('data-row-id', newRowId);
                                $tr.find('.modern-qty-counter').attr('data-row-id', newRowId).data('row-id', newRowId);
                                $tr.find('.cart-qty-input').attr('data-row-id', newRowId).data('row-id', newRowId);
                                $tr.find('.cart-row-total').attr('id', 'row-total-' + newRowId);
                                $tr.find('.stock-warning-tag').attr('id', 'stock-warning-' + newRowId);
                                $tr.find('.btn-ajax-remove').attr('data-row-id', newRowId).data('row-id', newRowId);
                            });
                        });

                        // Update totals
                        $('#summary-subtotal-val').text(res.subtotal_formatted).addClass('price-pulse');
                        if (res.has_coupon) {
                            $('#summary-discount-row').show();
                            $('#summary-discount-val').text(res.discount_formatted);
                        } else {
                            $('#summary-discount-row').hide();
                        }
                        $('#summary-final-total-val').text(res.final_total_formatted).addClass('price-pulse');

                        var $dualEl = $('#summary-dual-currency');
                        if ($dualEl.length) {
                            $dualEl.html('<span>(≈ ' + res.final_total_khr + ')</span>');
                        }

                        var countText = res.cart_item_count + (res.cart_item_count === 1 ? ' item' : ' items');
                        $('#cart-top-count-pill').text(countText);
                        $('#summary-items-count').text(countText);

                        $('.cart-counter-bubble').text(res.cart_item_count);
                        $('.cart-total-text').html('<span class="top-line" style="display: block; font-size: 10px; color: #ccc; line-height: 1;">Cart</span>' + res.header_cart_total);

                        showModernToast('<i class="fa fa-trash" style="color:#ef4444;"></i> Item removed from cart.');
                    } else {
                        window.location.href = $btn.attr('href');
                    }
                },
                error: function() {
                    window.location.href = $btn.attr('href');
                }
            });
        });

        // ==========================================
        // 6. Product Detail Page Quantity (+ / -) & Live Total
        // ==========================================
        function updateProductDetailTotal(qty, unitPrice, maxStock) {
            $('.btn-product-qty-minus').prop('disabled', qty <= 1);
            $('.btn-product-qty-plus').prop('disabled', qty >= maxStock);

            var total = qty * unitPrice;
            var $totalEl = $('#detail_live_total');

            if ($totalEl.length) {
                $totalEl.addClass('price-pulse');
                // Format total: if Khmer Riel active, calculate approx
                var isKHR = (window._currentCurrency === 'KHR' || document.body.innerHTML.indexOf(' ៛') > -1);
                var formatted = '$' + total.toFixed(2);
                if (isKHR) {
                    var khr = Math.round((total * 4100) / 100) * 100;
                    formatted = khr.toLocaleString() + ' ៛';
                }
                $totalEl.text(formatted);
                setTimeout(function() {
                    $totalEl.removeClass('price-pulse');
                }, 400);
            }
        }

        $(document).on('click', '.btn-product-qty-plus', function(e) {
            e.preventDefault();
            var $input = $('#detail_p_qty');
            var maxStock = parseInt($input.attr('max')) || 999;
            var currentVal = parseInt($input.val()) || 1;
            var unitPrice = parseFloat($input.data('unit-price')) || 0;

            if (currentVal >= maxStock) {
                showModernToast('<i class="fa fa-info-circle"></i> Maximum available stock (' + maxStock + ') reached.');
                return;
            }

            var newVal = currentVal + 1;
            $input.val(newVal);
            updateProductDetailTotal(newVal, unitPrice, maxStock);
        });

        $(document).on('click', '.btn-product-qty-minus', function(e) {
            e.preventDefault();
            var $input = $('#detail_p_qty');
            var maxStock = parseInt($input.attr('max')) || 999;
            var currentVal = parseInt($input.val()) || 1;
            var unitPrice = parseFloat($input.data('unit-price')) || 0;

            if (currentVal <= 1) return;

            var newVal = currentVal - 1;
            $input.val(newVal);
            updateProductDetailTotal(newVal, unitPrice, maxStock);
        });

        $(document).on('change input', '#detail_p_qty', function(e) {
            var $input = $(this);
            var maxStock = parseInt($input.attr('max')) || 999;
            var currentVal = parseInt($input.val()) || 1;
            var unitPrice = parseFloat($input.data('unit-price')) || 0;

            if (currentVal < 1) currentVal = 1;
            if (currentVal > maxStock) currentVal = maxStock;
            $input.val(currentVal);
            updateProductDetailTotal(currentVal, unitPrice, maxStock);
        });

        // ==========================================
        // 7. Modern Checkout Payment Tiles Interactive Sync
        // ==========================================
        $(document).on('click', '.payment-tile', function() {
            var method = $(this).data('method');
            $('.payment-tile').removeClass('active').css({'border-color': '#e2e8f0', 'background': '#fafbfc'});
            if (method === 'Bakong KHQR') {
                $(this).addClass('active').css({'border-color': '#e1251b', 'background': '#fff5f5'});
            } else {
                $(this).addClass('active').css({'border-color': '#ff9900', 'background': '#fffaf0'});
            }
            var $sel = $('#advFieldsStatus');
            if ($sel.length && $sel.val() !== method) {
                $sel.val(method).trigger('change');
            }
        });

        $(document).on('change', '#advFieldsStatus', function() {
            var val = $(this).val();
            $('.payment-tile').removeClass('active').css({'border-color': '#e2e8f0', 'background': '#fafbfc'});
            if (val) {
                var activeColor = (val === 'Bakong KHQR') ? '#e1251b' : '#ff9900';
                var activeBg = (val === 'Bakong KHQR') ? '#fff5f5' : '#fffaf0';
                $('.payment-tile[data-method="' + val + '"]').addClass('active').css({'border-color': activeColor, 'background': activeBg});
            }
        });

    });

})(jQuery);

