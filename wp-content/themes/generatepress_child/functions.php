<?php
/**
 * GeneratePress child theme functions and definitions.
 *
 * Add your custom PHP in this file.
 * Only edit this file if you have direct access to it on your server (to fix errors if they happen).
 */

 // Fix for missing oa_debug_class_loading function
function oa_debug_class_loading() {
    // Empty function to prevent fatal error
    return;
}

 // Replace WooCommerce default placeholder with a custom image in your theme
function custom_woocommerce_placeholder_img_src( $src ) {
    return get_stylesheet_directory_uri() . '/assets/images/product-placeholder.png';
}
add_filter( 'woocommerce_placeholder_img_src', 'custom_woocommerce_placeholder_img_src', 10 );

add_filter( 'woocommerce_placeholder_img', function( $image_html ) {
    $image_url = get_stylesheet_directory_uri() . '/assets/images/product-placeholder.png';
    return '<img src="' . esc_url( $image_url ) . '" alt="Placeholder" class="woocommerce-placeholder wp-post-image" />';
});

// Enqueue Owl Carousel assets (only once)
function gp_enqueue_owl_carousel() {
    static $done = false;
    if ( $done ) return;
    wp_enqueue_script('gp-owl-carousel','https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/owl.carousel.min.js',['jquery'],'2.3.4',true);
    wp_enqueue_style('gp-owl-carousel-style','https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.carousel.min.css',[],'2.3.4');
    wp_enqueue_style('gp-owl-carousel-theme','https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.theme.default.min.css',[],'2.3.4');
    $done = true;
}

// Currency widget
function highlight_current_currency_menu_item($classes, $item) {
    if (strpos($item->url, home_url()) !== false) {
        $classes[] = 'current-currency';
    }
    return $classes;
}
add_filter('nav_menu_css_class', 'highlight_current_currency_menu_item', 10, 2);

// Shortcode: [menu name="Footer Menu"]
function shortcode_menu( $atts ) {
    $atts = shortcode_atts( [
        'name' => '',
        'class' => 'shortcode-menu'
    ], $atts );

    return wp_nav_menu( [
        'menu' => $atts['name'],
        'container' => 'div',
        'container_class' => $atts['class'],
        'echo' => false
    ]);
}
add_shortcode( 'menu', 'shortcode_menu' );


// Output a simple currency switcher next to the Woo Mini Cart block
function gp_child_output_currency_switcher() {
    if ( is_admin() ) return;
    ?>
    <style>
    .gp-currency-switcher{display:inline-flex;align-items:center;margin-left:8px}
    .gp-currency-switcher select{font-size:12px;line-height:1.4;padding:2px 6px}
    </style>
    <script>
    (function(){
        function onReady(fn){if(document.readyState!=='loading'){fn()}else{document.addEventListener('DOMContentLoaded',fn)}}
        onReady(function(){
            var miniCart = document.querySelector('.wp-block-woocommerce-mini-cart, .wc-block-mini-cart');
            if(!miniCart || !miniCart.parentNode) return;
            // Avoid duplicates
            if(miniCart.nextElementSibling && miniCart.nextElementSibling.classList && miniCart.nextElementSibling.classList.contains('gp-currency-switcher')) return;

            var wrapper = document.createElement('div');
            wrapper.className = 'gp-currency-switcher';

            var select = document.createElement('select');
            select.setAttribute('aria-label','Currency');
            var optZAR = document.createElement('option'); optZAR.value = 'ZAR'; optZAR.textContent = 'ZAR';
            var optUSD = document.createElement('option'); optUSD.value = 'USD'; optUSD.textContent = 'USD';
            select.appendChild(optZAR); select.appendChild(optUSD);

            var path = window.location.pathname || '/';
            var inUSD = /^\/usd(\/|$)/i.test(path);
            select.value = inUSD ? 'USD' : 'ZAR';

            select.addEventListener('change', function(){
                var loc = new URL(window.location.href);
                var p = loc.pathname || '/';
                if(this.value === 'USD'){
                    if(!/^\/usd(\/|$)/i.test(p)){
                        // Ensure single slash when prefixing
                        var newPath = '/usd' + (p.charAt(0)==='/' ? '' : '/') + p;
                        loc.pathname = newPath.replace(/\/+/, '/');
                    }
                } else {
                    // Remove leading /usd segment
                    loc.pathname = p.replace(/^\/usd(\/)?/i, '/');
                }
                window.location.href = loc.toString();
            });

            wrapper.appendChild(select);
            miniCart.parentNode.insertBefore(wrapper, miniCart.nextSibling);
        });
    })();
    </script>
    <?php
}
// Disabled currency switcher dropdown
// add_action( 'wp_footer', 'gp_child_output_currency_switcher' );

// Disable zoom on product images
add_filter( 'woocommerce_single_product_zoom_enabled', '__return_false' );

// Blog: always show "Read article" for every post (excerpt or full content), regardless of length
add_action( 'generate_after_entry_content', 'gp_child_output_read_article_link', 5 );
function gp_child_output_read_article_link() {
    if ( ! is_main_query() || ! in_the_loop() ) {
        return;
    }
    if ( ! ( is_home() || is_archive() || is_search() ) ) {
        return;
    }
    $id = get_the_ID();
    if ( ! $id ) {
        return;
    }
    $title_attr = the_title_attribute( array( 'echo' => false ) );
    $aria      = sprintf(
        _x( 'Read more about %s', 'read more about post title', 'generatepress_child' ),
        the_title_attribute( array( 'echo' => false ) )
    );
    printf(
        '<p class="read-more-container"><a title="%1$s" class="read-more button" href="%2$s" aria-label="%3$s">%4$s</a></p>',
        esc_attr( $title_attr ),
        esc_url( get_permalink( $id ) ),
        esc_attr( $aria ),
        esc_html__( 'Read article', 'generatepress_child' )
    );
}

// On blog/archive: use "…" only in excerpt (no link inside excerpt); the link is output by the hook above
add_filter( 'excerpt_more', 'gp_child_excerpt_more_no_link', 20 );
function gp_child_excerpt_more_no_link( $more ) {
    if ( ! is_main_query() || ! in_the_loop() || ! ( is_home() || is_archive() || is_search() ) ) {
        return $more;
    }
    return ' …';
}

// Disable Gravity Forms quote email custom styling
// Remove the custom email styling filters after the plugin initializes
add_action( 'init', function() {
    global $wp_filter;
    
    // Remove gform_notification filter from OA_TFP_Gravity_Forms class
    if ( isset( $wp_filter['gform_notification'] ) ) {
        foreach ( $wp_filter['gform_notification']->callbacks as $priority => $callbacks ) {
            foreach ( $callbacks as $callback_id => $callback ) {
                if ( is_array( $callback['function'] ) && 
                     is_object( $callback['function'][0] ) && 
                     get_class( $callback['function'][0] ) === 'OA_TFP_Gravity_Forms' &&
                     $callback['function'][1] === 'add_email_styles' ) {
                    remove_filter( 'gform_notification', $callback['function'], $priority );
                }
            }
        }
    }
    
    // Remove gform_pre_send_email filter from OA_TFP_Gravity_Forms class
    if ( isset( $wp_filter['gform_pre_send_email'] ) ) {
        foreach ( $wp_filter['gform_pre_send_email']->callbacks as $priority => $callbacks ) {
            foreach ( $callbacks as $callback_id => $callback ) {
                if ( is_array( $callback['function'] ) && 
                     is_object( $callback['function'][0] ) && 
                     get_class( $callback['function'][0] ) === 'OA_TFP_Gravity_Forms' &&
                     $callback['function'][1] === 'inject_email_styles' ) {
                    remove_filter( 'gform_pre_send_email', $callback['function'], $priority );
                }
            }
        }
    }
}, 99 ); // High priority to run after plugins are loaded

/**
 * Gravity Forms: remove images from Fan options (nested form) section in one notification.
 * - "Admin Notification" = Fan options section has no images (rest of email unchanged).
 * - "Timber Fans Client" = keeps images.
 * Form ID 1 = Request a quote; nested Fan options form ID = 3.
 */
add_filter( 'gform_pre_send_email', 'gp_child_gf_remove_images_from_notification', 15, 4 );
function gp_child_gf_remove_images_from_notification( $email, $message_format, $notification, $entry ) {
	// Only Request a quote form (ID 1)
	if ( (int) rgar( $entry, 'form_id' ) !== 1 ) {
		return $email;
	}
	// Only the notification that should have no images in the Fan options part
	$notification_name_no_images = 'Admin Notification';
	if ( empty( $notification['name'] ) || $notification['name'] !== $notification_name_no_images ) {
		return $email;
	}
	// Only HTML emails
	if ( $message_format !== 'html' || empty( $email['message'] ) ) {
		return $email;
	}

	$email['message'] = gp_child_gf_strip_images_in_fan_options_section( $email['message'] );
	return $email;
}

/**
 * Remove <img> tags only inside the Fan options (nested) section of the email HTML.
 * Nested section is wrapped in a table with style containing "faebd2" (GP Nested Forms markup).
 */
function gp_child_gf_strip_images_in_fan_options_section( $html ) {
	if ( strpos( $html, '<img' ) === false ) {
		return $html;
	}
	if ( strpos( $html, 'faebd2' ) === false ) {
		return $html;
	}

	$libxml_prev = libxml_use_internal_errors( true );
	$doc = new DOMDocument();
	$doc->loadHTML( '<?xml encoding="UTF-8"><div id="gf-email-root">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
	libxml_use_internal_errors( $libxml_prev );

	$xpath = new DOMXPath( $doc );
	$tables = $xpath->query( '//table[contains(@style, "faebd2")]' );
	if ( ! $tables || $tables->length === 0 ) {
		return $html;
	}

	foreach ( $tables as $table ) {
		$imgs = $xpath->query( './/img', $table );
		if ( ! $imgs ) {
			continue;
		}
		for ( $i = $imgs->length - 1; $i >= 0; $i-- ) {
			$img = $imgs->item( $i );
			if ( $img && $img->parentNode ) {
				$img->parentNode->removeChild( $img );
			}
		}
	}

	$root = $doc->getElementById( 'gf-email-root' );
	if ( ! $root ) {
		return $html;
	}
	$out = '';
	foreach ( $root->childNodes as $child ) {
		$out .= $doc->saveHTML( $child );
	}
	return $out;
}

/**
 * ---------------------------------------------------------------------------
 * Google Fonts performance: font-display: swap + trim Montserrat weights.
 *
 * GeneratePress was loading all 18 Montserrat variants (100–900 + italics)
 * with display=auto, which blocks text rendering. We force display=swap and
 * keep only the weights the site actually uses.
 *
 * To change which weights load, edit $keep_weights below. Each number maps to:
 *   400 = Regular, 500 = Medium, 600 = SemiBold, 700 = Bold, 800 = ExtraBold.
 * Add italics with e.g. '700italic' if a design needs italic text.
 * ---------------------------------------------------------------------------
 */
add_filter( 'generate_google_font_display', 'gp_child_google_font_display', 99 );
function gp_child_google_font_display() {
	return 'swap';
}

add_filter( 'generate_typography_google_fonts', 'gp_child_trim_montserrat_weights', 99 );
function gp_child_trim_montserrat_weights( $fonts ) {
	if ( empty( $fonts ) ) {
		return $fonts;
	}

	// Weights to keep for Montserrat. Adjust to match your design.
	$keep_weights = array( '400', '500', '600', '700' );

	// GeneratePress joins multiple families with a pipe: "Montserrat:100,...|Other:400".
	$families = explode( '|', $fonts );

	foreach ( $families as $index => $family ) {
		// Only touch the Montserrat family; leave everything else untouched.
		if ( strpos( $family, 'Montserrat:' ) === 0 ) {
			$families[ $index ] = 'Montserrat:' . implode( ',', $keep_weights );
		}
	}

	return implode( '|', $families );
}

/**
 * ---------------------------------------------------------------------------
 * Gotham (self-hosted) fallback stack.
 *
 * The Font Library defines --gp-font--gotham as just "Gotham" with no fallback,
 * so while the font loads the browser shows Times (serif) — a jarring flash,
 * most visible on the non-bold part of the hero H1 ("ceiling fans").
 *
 * Gotham here is actually Gotham *Condensed*, so we fall back to Arial Narrow
 * (similar width) and then generic sans-serif. This removes the Times flash.
 * ---------------------------------------------------------------------------
 */
add_action( 'wp_head', 'gp_child_gotham_font_fallback', 100 );
function gp_child_gotham_font_fallback() {
	echo '<style id="gp-child-gotham-fallback">:root{--gp-font--gotham:"Gotham","Arial Narrow","Helvetica Neue",Arial,sans-serif !important;--gp-font-gotham:"Gotham","Arial Narrow","Helvetica Neue",Arial,sans-serif !important;}</style>' . "\n";
}

// Maintenance Mode
// Set to true to enable maintenance mode, false to disable
define( 'MAINTENANCE_MODE', false );

function show_maintenance_page() {
    // Allow admins to access the site even in maintenance mode
    if ( current_user_can( 'administrator' ) ) {
        return;
    }
    
    // Allow access to wp-admin and wp-login.php
    if ( is_admin() || strpos( $_SERVER['REQUEST_URI'], 'wp-login.php' ) !== false || strpos( $_SERVER['REQUEST_URI'], 'wp-admin' ) !== false ) {
        return;
    }
    
    // Load the maintenance page
    $maintenance_file = ABSPATH . 'maintenance.php';
    if ( file_exists( $maintenance_file ) ) {
        include $maintenance_file;
        exit;
    } else {
        // Fallback maintenance message if file doesn't exist
        wp_die( 
            '<h1>Site Under Maintenance</h1><p>We are currently performing scheduled maintenance. Please check back soon.</p>',
            'Site Under Maintenance',
            array( 'response' => 503 )
        );
    }
}

if ( defined( 'MAINTENANCE_MODE' ) && MAINTENANCE_MODE === true ) {
    add_action( 'template_redirect', 'show_maintenance_page', 1 );
}

/**
 * ---------------------------------------------------------------------------
 * After add-to-cart: open WooCommerce Blocks mini-cart instead of the notice.
 *
 * Cookie flag works with WP Rocket page cache. Cleared on first page view so
 * the drawer does not keep opening while browsing other pages.
 * ---------------------------------------------------------------------------
 */
add_filter( 'wc_add_to_cart_message_html', '__return_empty_string', 100 );

add_action( 'woocommerce_add_to_cart', 'gp_child_flag_open_mini_cart', 20 );
function gp_child_flag_open_mini_cart() {
	if ( headers_sent() ) {
		return;
	}
	wc_setcookie( 'gp_open_mini_cart', '1', time() + 60 );
}

add_action( 'wp_enqueue_scripts', 'gp_child_enqueue_open_mini_cart_script', 20 );
function gp_child_enqueue_open_mini_cart_script() {
	if ( is_admin() || ! function_exists( 'WC' ) ) {
		return;
	}

	$js = <<<'JS'
(function ($) {
	var openAttempted = false;

	function getCookie(name) {
		var match = document.cookie.match(new RegExp('(?:^|; )' + name.replace(/([.$?*|{}()[\]\\/+^])/g, '\\$1') + '=([^;]*)'));
		return match ? decodeURIComponent(match[1]) : '';
	}

	function clearOpenCartCookie() {
		var expires = 'expires=Thu, 01 Jan 1970 00:00:00 GMT';
		[
			'gp_open_mini_cart=; Max-Age=0; path=/',
			'gp_open_mini_cart=; ' + expires + '; path=/',
			'gp_open_mini_cart=; Max-Age=0; path=/; SameSite=Lax',
			'gp_open_mini_cart=; Max-Age=0; path=/; SameSite=Lax; Secure'
		].forEach(function (c) {
			document.cookie = c;
		});
	}

	function waitForMiniCartButton(timeoutMs) {
		return new Promise(function (resolve) {
			var start = Date.now();
			(function poll() {
				var btn = document.querySelector('.wc-block-mini-cart__button');
				if (btn) {
					resolve(btn);
					return;
				}
				if (Date.now() - start > timeoutMs) {
					resolve(null);
					return;
				}
				setTimeout(poll, 100);
			})();
		});
	}

	function refreshCartThen(callback) {
		if (typeof fetch !== 'function') {
			callback();
			return;
		}
		fetch('/wp-json/wc/store/v1/cart', {
			credentials: 'same-origin',
			headers: { 'Accept': 'application/json' }
		}).catch(function () { /* ignore */ }).finally(function () {
			callback();
		});
	}

	function openMiniCartOnce() {
		if (openAttempted) {
			return;
		}
		openAttempted = true;

		waitForMiniCartButton(8000).then(function (btn) {
			if (!btn) {
				return;
			}
			setTimeout(function () {
				refreshCartThen(function () {
					setTimeout(function () {
						btn.click();
					}, 150);
				});
			}, 300);
		});
	}

	function removeAddedToCartNotices() {
		$('.woocommerce-message').filter(function () {
			return /added to (your|the) cart/i.test($(this).text());
		}).remove();
	}

	function maybeOpenFromCookie() {
		var shouldOpen = getCookie('gp_open_mini_cart') === '1';
		// Always consume the cookie on first hit so other pages never reopen it.
		clearOpenCartCookie();
		if (!shouldOpen) {
			return;
		}
		// Only auto-open after add-to-cart on a product page (form reload flow).
		if (!document.body.classList.contains('single-product')) {
			return;
		}
		removeAddedToCartNotices();
		openMiniCartOnce();
	}

	$(document.body).on('added_to_cart', function () {
		clearOpenCartCookie();
		removeAddedToCartNotices();
		openAttempted = false;
		setTimeout(openMiniCartOnce, 200);
	});

	// Clear/consume cookie ASAP so it does not reopen on the next page.
	function boot() {
		if (!document.body) {
			return;
		}
		maybeOpenFromCookie();
	}
	if (document.body) {
		boot();
	} else {
		document.addEventListener('DOMContentLoaded', boot);
	}
	$(boot);
})(jQuery);
JS;

	wp_add_inline_script( 'jquery', $js, 'after' );
}

/**
 * ---------------------------------------------------------------------------
 * Front-end performance: dequeue assets that are not needed on the current page.
 *
 * Keeps WooCommerce Blocks mini-cart (header cart) everywhere.
 * Strips shipping / variation / addons / quote scripts off pages that only
 * display products (e.g. homepage carousels with no Add to cart).
 * ---------------------------------------------------------------------------
 */
add_action( 'wp_enqueue_scripts', 'gp_child_dequeue_unused_assets', 99999 );
add_action( 'wp_print_scripts', 'gp_child_dequeue_unused_assets', 99999 );
add_action( 'wp_print_styles', 'gp_child_dequeue_unused_assets', 99999 );

function gp_child_dequeue_unused_assets() {
	if ( is_admin() ) {
		return;
	}

	$is_cart_checkout = function_exists( 'is_cart' ) && ( is_cart() || is_checkout() || is_account_page() );
	$is_product       = function_exists( 'is_product' ) && is_product();
	$is_catalog       = function_exists( 'is_shop' ) && (
		is_shop() || is_product_taxonomy() || is_product_category() || is_product_tag()
	);

	// Shipping / checkout plugins: only on cart, checkout, account.
	if ( ! $is_cart_checkout ) {
		gp_child_dequeue_handles(
			array(
				// The Courier Guy
				'the-courier-guy',
				'the-courier-guy-main',
				'the-courier-guy-notice',
				'tcg-main',
				'tcg-notice',
				// ELEX DHL Express
				'elex-dhl-express',
				'dhl_cart_checkout_scripts',
				'elex-woo-dhl-express-shipping',
				// Datepicker often pulled in for shipping rate forms
				'jquery-ui-datepicker',
			),
			array(
				// Styles
				'the-courier-guy',
				'tcg-main',
				'elex-woo-dhl-express-shipping',
			)
		);

		gp_child_dequeue_by_src_contains(
			array(
				'/the-courier-guy/',
				'/elex-woo-dhl-express-shipping/',
				'/woocommerce-dhlexpress-services/',
			)
		);
	}

	// Single-product / quote tooling: not needed on homepage carousels.
	if ( ! $is_product ) {
		gp_child_dequeue_handles(
			array(
				'wc-add-to-cart-variation',
				'woocommerce-add-to-cart-variation',
				'jquery-blockui',
				'accounting',
				// Product Add-Ons
				'woocommerce-addons',
				'woocommerce-addons-validation',
				'pao-validation',
				'wc-product-addons',
				// Quote / Gravity Forms product mod (product pages)
				'oa-timberfans-gf-mod',
				'oa-tf-gf-mod',
			),
			array(
				'woocommerce-addons-css',
				'woocommerce-product-addons',
				'oa-timberfans-gf-mod',
			)
		);

		gp_child_dequeue_by_src_contains(
			array(
				'/woocommerce-product-addons/',
				'/add-to-cart-variation',
				'/oa-timberfans-gf-mod/',
			)
		);
	}

	// Classic add-to-cart JS: homepage products link through; keep on shop/catalog/product.
	if ( ! $is_product && ! $is_catalog && ! $is_cart_checkout ) {
		gp_child_dequeue_handles(
			array(
				'wc-add-to-cart',
				'woocommerce-add-to-cart',
			),
			array()
		);

		gp_child_dequeue_by_src_contains(
			array(
				'/frontend/add-to-cart.min.js',
				'/frontend/add-to-cart.js',
			)
		);
	}
}

/**
 * Dequeue/deregister known script and style handles.
 *
 * @param string[] $scripts Script handles.
 * @param string[] $styles  Style handles.
 */
function gp_child_dequeue_handles( $scripts, $styles = array() ) {
	foreach ( (array) $scripts as $handle ) {
		wp_dequeue_script( $handle );
		wp_deregister_script( $handle );
	}
	foreach ( (array) $styles as $handle ) {
		wp_dequeue_style( $handle );
		wp_deregister_style( $handle );
	}
}

/**
 * Dequeue any enqueued script/style whose src contains one of the needles.
 * Catches plugins that use unpredictable handles.
 *
 * @param string[] $needles Path fragments to match in src URLs.
 */
function gp_child_dequeue_by_src_contains( $needles ) {
	$needles = array_filter( array_map( 'strval', (array) $needles ) );
	if ( ! $needles ) {
		return;
	}

	$matches = static function ( $src ) use ( $needles ) {
		if ( ! $src ) {
			return false;
		}
		foreach ( $needles as $needle ) {
			if ( strpos( $src, $needle ) !== false ) {
				return true;
			}
		}
		return false;
	};

	global $wp_scripts, $wp_styles;

	if ( $wp_scripts instanceof WP_Scripts ) {
		foreach ( $wp_scripts->queue as $handle ) {
			$src = isset( $wp_scripts->registered[ $handle ]->src ) ? $wp_scripts->registered[ $handle ]->src : '';
			if ( $matches( $src ) ) {
				wp_dequeue_script( $handle );
				wp_deregister_script( $handle );
			}
		}
	}

	if ( $wp_styles instanceof WP_Styles ) {
		foreach ( $wp_styles->queue as $handle ) {
			$src = isset( $wp_styles->registered[ $handle ]->src ) ? $wp_styles->registered[ $handle ]->src : '';
			if ( $matches( $src ) ) {
				wp_dequeue_style( $handle );
				wp_deregister_style( $handle );
			}
		}
	}
}

