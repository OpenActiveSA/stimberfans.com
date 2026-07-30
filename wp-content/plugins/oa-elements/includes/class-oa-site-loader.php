<?php
/**
 * Site Loader Class
 *
 * @package OpenAgencyElements
 * @since 1.1.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * OA Site Loader Class
 */
class OA_Site_Loader {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action( 'wp_head', array( $this, 'render_loader_html' ) );
        add_action( 'wp_footer', array( $this, 'render_loader_script' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_loader_styles' ) );
    }
    
    /**
     * Render loader HTML in head
     */
    public function render_loader_html() {
        if ( ! oa_is_feature_enabled( 'oa_enable_site_loader' ) ) {
            return;
        }
        
        $bg_color = get_option( 'oa_site_loader_bg_color', '#ffffff' );
        $loader_icon = get_option( 'oa_site_loader_icon', '' );
        $spin_enabled = get_option( 'oa_site_loader_spin', false );
        
        ?>
        <div id="oa-site-loader" style="display: block;">
            <div class="oa-loader-background" style="background-color: <?php echo esc_attr( $bg_color ); ?>;">
                <div class="oa-loader-content">
                    <?php if ( ! empty( $loader_icon ) ) : ?>
                        <img src="<?php echo esc_url( $loader_icon ); ?>" alt="Loading..." class="oa-loader-icon<?php echo $spin_enabled ? ' oa-loader-spin' : ''; ?>" />
                    <?php else : ?>
                        <div class="oa-loader-spinner"></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render loader script in footer
     */
    public function render_loader_script() {
        if ( ! oa_is_feature_enabled( 'oa_enable_site_loader' ) ) {
            return;
        }
        
        ?>
        <script>
        jQuery(function($) {
            var $loader = $('#oa-site-loader');
            var $body = $('body');
            var loaderHidden = false;
            var minDisplayTime = 400; // Reduced minimum display time
            var startTime = Date.now();
            
            // Add body class immediately
            $body.addClass('oa-loader-active');
            
            function hideLoader() {
                if (loaderHidden) return;
                loaderHidden = true;
                
                var elapsedTime = Date.now() - startTime;
                var remainingTime = Math.max(0, minDisplayTime - elapsedTime);
                
                setTimeout(function() {
                    $loader.fadeOut(150); // Faster fade out
                    $body.removeClass('oa-loader-active');
                }, remainingTime);
            }
            
            // Hide loader as soon as DOM is ready (no additional delay)
            $(document).ready(function() {
                hideLoader();
            });
            
            // Fallback: hide loader after 1 second maximum (reduced from 2s)
            setTimeout(function() {
                if ($loader.is(':visible')) {
                    hideLoader();
                }
            }, 1000);
            
            // Also hide on window load as backup
            $(window).on('load', function() {
                hideLoader();
            });
        });
        </script>
        <?php
    }
    
    /**
     * Enqueue loader styles
     */
    public function enqueue_loader_styles() {
        if ( ! oa_is_feature_enabled( 'oa_enable_site_loader' ) ) {
            return;
        }
        
        wp_add_inline_style( 'oa-elements-styles', $this->get_loader_css() );
    }
    
    /**
     * Get loader CSS
     *
     * @return string
     */
    private function get_loader_css() {
        return '
        #oa-site-loader {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 999999;
        }
        
        .oa-loader-background {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .oa-loader-content {
            text-align: center;
        }
        
        .oa-loader-icon {
            max-width: 80px;
            max-height: 80px;
            animation: oa-loader-pulse 1.5s ease-in-out infinite;
        }
        
        .oa-loader-icon.oa-loader-spin {
            animation: oa-loader-spin 1s linear infinite;
        }
        
        .oa-loader-spinner {
            width: 50px;
            height: 50px;
            border: 3px solid rgba(0, 0, 0, 0.1);
            border-top: 3px solid #0073aa;
            border-radius: 50%;
            animation: oa-loader-spin 1s linear infinite;
            margin: 0 auto;
        }
        
        @keyframes oa-loader-spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        @keyframes oa-loader-pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        
        body.oa-loader-active {
            overflow: hidden;
        }
        ';
    }
} 