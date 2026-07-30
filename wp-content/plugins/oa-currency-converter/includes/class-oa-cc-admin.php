<?php
/**
 * OA Currency Converter Admin Class
 * 
 * Handles admin settings and UI
 */
class OA_CC_Admin {
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->init_hooks();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_options_page(
            'Currency Converter Settings',
            'Currency Converter',
            'manage_options',
            'oa-currency-converter',
            array($this, 'render_settings_page')
        );
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        register_setting('oa_cc_settings', 'oa_cc_usd_to_zar_rate', array(
            'type' => 'float',
            'sanitize_callback' => 'floatval',
            'default' => 18.5
        ));
        
        register_setting('oa_cc_settings', 'oa_cc_fixer_api_key', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field'
        ));
        
        register_setting('oa_cc_settings', 'oa_cc_currencylayer_api_key', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field'
        ));
        
        register_setting('oa_cc_settings', 'oa_cc_exchange_rate_last_updated', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field'
        ));
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        if ($hook === 'settings_page_oa-currency-converter' || $hook === 'post.php') {
            wp_enqueue_script('jquery');
        }
    }
    
    /**
     * Render settings page
     */
    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Handle manual update
        if (isset($_POST['oa_cc_manual_update']) && check_admin_referer('oa_cc_manual_update')) {
            $rate = OA_CC_Cron::manual_update();
            if ($rate !== false) {
                echo '<div class="notice notice-success"><p>Exchange rate updated successfully to: ' . number_format($rate, 4) . ' ZAR per USD</p></div>';
            } else {
                echo '<div class="notice notice-error"><p>Failed to update exchange rate. Please check your API keys or try again later.</p></div>';
            }
        }
        
        $current_rate = get_option('oa_cc_usd_to_zar_rate', 18.5);
        $last_updated = get_option('oa_cc_exchange_rate_last_updated', '');
        $fixer_key = get_option('oa_cc_fixer_api_key', '');
        $currencylayer_key = get_option('oa_cc_currencylayer_api_key', '');
        
        ?>
        <div class="wrap">
            <h1>Currency Converter Settings</h1>
            
            <form method="post" action="options.php">
                <?php settings_fields('oa_cc_settings'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="oa_cc_usd_to_zar_rate">Current Exchange Rate</label>
                        </th>
                        <td>
                            <input type="number" 
                                   id="oa_cc_usd_to_zar_rate" 
                                   name="oa_cc_usd_to_zar_rate" 
                                   value="<?php echo esc_attr($current_rate); ?>" 
                                   step="0.0001" 
                                   min="0"
                                   class="regular-text">
                            <p class="description">
                                1 USD = <?php echo number_format($current_rate, 4); ?> ZAR
                                <?php if ($last_updated): ?>
                                    <br>Last updated: <?php echo date('Y-m-d H:i:s', strtotime($last_updated)); ?>
                                <?php else: ?>
                                    <br>Rate has not been updated automatically yet.
                                <?php endif; ?>
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="oa_cc_fixer_api_key">Fixer.io API Key</label>
                        </th>
                        <td>
                            <input type="text" 
                                   id="oa_cc_fixer_api_key" 
                                   name="oa_cc_fixer_api_key" 
                                   value="<?php echo esc_attr($fixer_key); ?>" 
                                   class="regular-text">
                            <p class="description">
                                Optional. Get your API key from <a href="https://fixer.io/" target="_blank">fixer.io</a>
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="oa_cc_currencylayer_api_key">Currencylayer API Key</label>
                        </th>
                        <td>
                            <input type="text" 
                                   id="oa_cc_currencylayer_api_key" 
                                   name="oa_cc_currencylayer_api_key" 
                                   value="<?php echo esc_attr($currencylayer_key); ?>" 
                                   class="regular-text">
                            <p class="description">
                                Optional. Get your API key from <a href="https://currencylayer.com/" target="_blank">currencylayer.com</a>
                            </p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>
            
            <hr>
            
            <h2>Manual Update</h2>
            <p>Click the button below to manually update the exchange rate from the API.</p>
            <form method="post" action="">
                <?php wp_nonce_field('oa_cc_manual_update'); ?>
                <input type="submit" 
                       name="oa_cc_manual_update" 
                       class="button button-secondary" 
                       value="Update Exchange Rate Now">
            </form>
            
            <hr>
            
            <h2>How It Works</h2>
            <ul>
                <li>Enter product prices in ZAR using the "ZAR Price Input" meta box on product edit pages</li>
                <li>Prices are automatically converted to USD using the current exchange rate</li>
                <li>USD prices are stored as the WooCommerce product price</li>
                <li>The exchange rate is updated daily via cronjob</li>
                <li>You can manually update the rate at any time using the button above</li>
                <li>The plugin tries multiple free APIs (exchangerate-api.com is used by default, no API key required)</li>
            </ul>
        </div>
        <?php
    }
}
