jQuery(document).ready(function($) {
    var wrapper = $('#oa-tfp-variation-wrapper');
    if (!wrapper.length) return;

    var form = $('form.variations_form');
    if (!form.length) return;
    
    var attributes = {};
    
    // Build attribute list
    wrapper.find('.oa-tfp-variation-group').each(function() {
        var attr = $(this).data('attribute_name');
        attributes[attr] = [];
        $(this).find('.oa-tfp-option').each(function() {
            attributes[attr].push($(this).data('value'));
        });
    });

    // Add Reset Button
    var $resetBtn = $('<button type="button" class="oa-tfp-reset" aria-label="Reset variations"><span class="oa-tfp-reset-icon" style="display:inline-block;vertical-align:middle;margin-top:-3px;margin-right:5px;line-height:0;"><svg width="12" height="12" viewBox="0 0 277.63 308.91" xmlns="http://www.w3.org/2000/svg"><path d="M138.81,0l-68.05,56.53,1.08,2.94,66.97,48.53v-35c41.38-.47,80.06,27.74,92.26,67.24,23.82,77.08-49.32,147.82-125.51,120.51-57.14-20.48-80.83-89.01-49.88-141.12l-33.45-23.58c-1.32-.37-1.66.75-2.28,1.6-4.02,5.51-10.19,19.85-12.43,26.57-37.51,112.51,71.79,218.45,182.54,174.54,71.29-28.27,106.54-110.22,77.22-181.72-21.14-51.54-72.49-86.08-128.47-86.03V0Z"/></svg></span>Reset</button>');
    if (!$('.oa-tfp-reset').length) {
        $('.woocommerce-variation-add-to-cart').after($resetBtn);
    }

    // Update button states based on current selection
    function updateButtons() {
        wrapper.find('.oa-tfp-option').each(function() {
            var btn = $(this);
            var attr = btn.data('attribute_name');
            var val = btn.data('value');
            var select = form.find('select[name="attribute_' + attr + '"]');

            if (select.val() === val) {
                btn.addClass('selected');
            } else {
                btn.removeClass('selected');
            }
        });
    }
    
    // Wrap quantity and add to cart button
    function wrapAddToCartElements() {
        var quantity = $('.quantity.buttons-added');
        var addToCartButton = $('.single_add_to_cart_button.button');
        var quoteButton = $('.oa-tfp-quote-button');
        
        if (quantity.length && addToCartButton.length && !quantity.closest('.oa-tfp-add-to-cart-container').length) {
            var container = $('<div class="oa-tfp-add-to-cart-container"></div>');
            quantity.wrap(container);
            addToCartButton.appendTo(quantity.parent());
            if (quoteButton.length) {
                quoteButton.appendTo(quantity.parent());
            }
        }
    }
    
    // Set swatch heights
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
    
    // Initial setup
    wrapAddToCartElements();
    updateButtons();
    setSwatchHeights();
    $(window).on('resize', setSwatchHeights);

    // Button click handler - MINIMAL, just update select and trigger change
    wrapper.on('click', '.oa-tfp-option', function(e) {
        var btn = $(this);
        var attr = btn.data('attribute_name');
        var val = btn.data('value');
        var select = form.find('select[name="attribute_' + attr + '"]');
        
        // Update selection
        btn.closest('.oa-tfp-variation-group').find('.oa-tfp-option').removeClass('selected');
        btn.addClass('selected');
        select.val(val).trigger('change');
    });
    
    // Keyboard accessibility
    wrapper.on('keydown', '.oa-tfp-option', function(e) {
        if (e.key === 'Enter' || e.key === ' ') {
            $(this).trigger('click');
            e.preventDefault();
        }
    });

    // Update button states when WooCommerce changes variations
    form.on('found_variation reset_data', function() {
        updateButtons();
        setTimeout(wrapAddToCartElements, 100);
    });

    // Reset button handler
    $(document).on('click', '.oa-tfp-reset', function() {
        form[0].reset();
        form.find('select').val('').trigger('change');
        updateButtons();
        $('.oa-tfp-custom-subtotal').hide();
    });
});

