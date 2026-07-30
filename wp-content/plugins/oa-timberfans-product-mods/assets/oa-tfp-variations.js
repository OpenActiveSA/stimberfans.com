jQuery(document).ready(function($) {
    // Wait for WooCommerce to fully initialize first
    setTimeout(function() {
        initOaTfpVariations();
    }, 200);
});

function initOaTfpVariations() {
    var $ = jQuery;
    var wrapper = $('#oa-tfp-variation-wrapper');
    if (!wrapper.length) return;

    var form = $('form.variations_form');
    if (!form.length) return;
    
    // Check if WooCommerce variation form is initialized
    var wcVariationForm = form.data('wc-variation-form');
    if (typeof console !== 'undefined' && window.location.search.indexOf('debug_variations') > -1) {
        console.log('WC Variation Form initialized:', !!wcVariationForm);
        console.log('Form data:', form.data());
    }
    
    var variations = form.data('product_variations');
    var ajaxMode = (variations === false);
    var attributes = {};
    
    // Build attribute list
    wrapper.find('.oa-tfp-variation-group').each(function() {
        var attr = $(this).data('attribute_name');
        attributes[attr] = [];
        $(this).find('.oa-tfp-option').each(function() {
            attributes[attr].push($(this).data('value'));
        });
    });

    // Add Reset Button (move it after .woocommerce-variation-add-to-cart)
    var $resetBtn = $('<button type="button" class="oa-tfp-reset" aria-label="Reset variations"><span class="oa-tfp-reset-icon" style="display:inline-block;vertical-align:middle;margin-top:-3px;margin-right:5px;line-height:0;"><svg width="12" height="12" viewBox="0 0 277.63 308.91" xmlns="http://www.w3.org/2000/svg"><path d="M138.81,0l-68.05,56.53,1.08,2.94,66.97,48.53v-35c41.38-.47,80.06,27.74,92.26,67.24,23.82,77.08-49.32,147.82-125.51,120.51-57.14-20.48-80.83-89.01-49.88-141.12l-33.45-23.58c-1.32-.37-1.66.75-2.28,1.6-4.02,5.51-10.19,19.85-12.43,26.57-37.51,112.51,71.79,218.45,182.54,174.54,71.29-28.27,106.54-110.22,77.22-181.72-21.14-51.54-72.49-86.08-128.47-86.03V0Z"/></svg></span>Reset</button>');
    if (!$('.oa-tfp-reset').length) {
        $('.woocommerce-variation-add-to-cart').after($resetBtn);
    }

    // Accessibility: ARIA roles
    wrapper.attr('role', 'group');
    wrapper.find('.oa-tfp-variation-group').attr('role', 'group');
    wrapper.find('.oa-tfp-option').attr('role', 'button').attr('tabindex', '0');

    // Helper: Get current selection
    function getCurrentSelection() {
        var sel = {};
        for (var attr in attributes) {
            // Fix: read from select[name="attribute_" + attr]
            var val = form.find('select[name="attribute_' + attr + '"]').val();
            if (val && val.length > 0) sel[attr] = val;
        }
        return sel;
    }



    // Update button states
    function updateButtons() {
        var current = getCurrentSelection();
        
        wrapper.find('.oa-tfp-option').each(function() {
            var btn = $(this);
            var attr = btn.data('attribute_name');
            var val = btn.data('value');
            var select = form.find('select[name="attribute_' + attr + '"]');
            // Find the label element next to the button
            var label = btn.closest('.oa-tfp-item').find('.oa-tfp-label-text, .oa-tfp-timber-label-text, .oa-tfp-metal-label-text').first();

            // Remove aria-disabled but keep visual styling classes
            btn.attr('aria-disabled', 'false');

            if (select.val() === val) {
                btn.addClass('selected');
            } else {
                btn.removeClass('selected');
            }

            // Add visual styling for unavailable options but keep them selectable
            if (!ajaxMode) {
                // Check if this option combination exists and is out of stock
                var isOutOfStock = false;
                var testSelection = $.extend({}, current);
                testSelection[attr] = val;
                
                // Check if we have a complete selection for this attribute
                var hasCompleteSelection = true;
                for (var testAttr in attributes) {
                    if (!testSelection[testAttr]) {
                        hasCompleteSelection = false;
                        break;
                    }
                }
                
                if (hasCompleteSelection) {
                    var foundVariation = variations.find(function(variation) {
                        for (var testAttr in testSelection) {
                            var attrKey = 'attribute_' + testAttr;
                            if (variation.attributes[attrKey] !== testSelection[testAttr]) {
                                return false;
                            }
                        }
                        return true;
                    });
                    
                    if (foundVariation && foundVariation.is_in_stock === false) {
                        isOutOfStock = true;
                    }
                }
                
                if (isOutOfStock) {
                    btn.addClass('disabled unavailable');
                    label.addClass('disabled unavailable');
                } else {
                    btn.removeClass('disabled unavailable');
                    label.removeClass('disabled unavailable');
                }
            } else {
                // AJAX mode: check if <option> is disabled
                var option = select.find('option[value="' + val + '"]');
                if (option.length && option.prop('disabled')) {
                    btn.addClass('disabled unavailable');
                    label.addClass('disabled unavailable');
                } else {
                    btn.removeClass('disabled unavailable');
                    label.removeClass('disabled unavailable');
                }
            }
        });
        
        // Add or remove 'empty' class for each attribute group
        wrapper.find('.oa-tfp-variation-group').each(function() {
            var group = $(this);
            var attr = group.data('attribute_name');
            var select = form.find('select[name="attribute_' + attr + '"]');
            if (select.length && (!select.val() || select.val() === '')) {
                group.addClass('empty');
            } else {
                group.removeClass('empty');
            }
        });
    }


    
    // Wrap quantity and add to cart button in container
    function wrapAddToCartElements() {
        var quantity = $('.quantity.buttons-added');
        var addToCartButton = $('.single_add_to_cart_button.button');
        var quoteButton = $('.oa-tfp-quote-button');
        
        // Only wrap if both elements exist and aren't already wrapped
        if (quantity.length && addToCartButton.length && !quantity.closest('.oa-tfp-add-to-cart-container').length) {
            // Create container
            var container = $('<div class="oa-tfp-add-to-cart-container"></div>');
            
            // Move quantity and button into container
            quantity.wrap(container);
            addToCartButton.appendTo(quantity.parent());
            
            // Also move quote button into container if it exists
            if (quoteButton.length) {
                quoteButton.appendTo(quantity.parent());
            }
        }
    }
    
    // Hide the WooCommerce selects via JavaScript instead of CSS
    // This ensures they're visible during WC initialization
    form.find('.variations, select[name^="attribute_"], label[for^="attribute_"], .reset_variations').css({
        'position': 'absolute',
        'left': '-9999px',
        'opacity': '0',
        'pointer-events': 'none',
        'height': '0',
        'overflow': 'hidden'
    });
    
    // Initial state
    wrapAddToCartElements();
    updateButtons();

    // On swatch click, set select and trigger AJAX variation change
    wrapper.on('click', '.oa-tfp-option', function(e) {
        var btn = $(this);
        var attr = btn.data('attribute_name');
        var val = btn.data('value');
        var select = form.find('select[name="attribute_' + attr + '"]');
        
        // Debug logging
        if (typeof console !== 'undefined' && window.location.search.indexOf('debug_variations') > -1) {
            console.log('OA TFP: Button clicked:', attr, '=', val);
            console.log('OA TFP: Select element found:', select.length);
            console.log('OA TFP: Select current value:', select.val());
        }
        
        // Radio button behavior - only one option per group can be selected
        btn.closest('.oa-tfp-variation-group').find('.oa-tfp-option').removeClass('selected');
        btn.addClass('selected');
        select.val(val);
        
        if (typeof console !== 'undefined' && window.location.search.indexOf('debug_variations') > -1) {
            console.log('OA TFP: Select new value:', select.val());
        }
        
        // Trigger change and let WooCommerce's variation form handle everything
        select.trigger('change');
        
        if (typeof console !== 'undefined' && window.location.search.indexOf('debug_variations') > -1) {
            console.log('OA TFP: Change event triggered');
        }
    });

    // Keyboard accessibility
    wrapper.on('keydown', '.oa-tfp-option', function(e) {
        // Removed disabled/unavailable check to allow selection of all options
        if (e.key === 'Enter' || e.key === ' ') {
            $(this).trigger('click');
            e.preventDefault();
        }
    });

    // On variation change (triggered by WooCommerce)
    form.on('found_variation', function(e, variation) {
        // Debug logging
        if (typeof console !== 'undefined' && window.location.search.indexOf('debug_variations') > -1) {
            console.log('OA TFP: found_variation event');
            if (variation && variation.image) {
                console.log('Image URL:', variation.image.url || variation.image.src);
            }
        }
        updateButtons();
    });
    
    form.on('reset_data', function() {
        updateButtons();
    });

    // Reset button
    $(document).on('click', '.oa-tfp-reset', function() {
        form[0].reset();
        form.find('select').val('').trigger('change');
        updateButtons();
        
        // Clear custom subtotal on reset
        $('.oa-tfp-custom-subtotal').hide();
        $('.oa-tfp-subtotal-amount').html('');
    });

    

    

    

    
    // Utility: Set swatch heights for timber (square) and metal (rectangle)
    function setSwatchHeights() {
        $('.oa-tfp-timber-option').each(function() {
            var w = $(this).outerWidth();
            $(this).css('height', w + 'px');
        });
        $('.oa-tfp-metal-option').each(function() {
            var w = $(this).outerWidth();
            $(this).css('height', (w / 2) + 'px');
        });
    }
    setSwatchHeights();
    $(window).on('resize', setSwatchHeights);
} 