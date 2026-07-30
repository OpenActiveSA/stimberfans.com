<?php
/**
 * OA Currency Converter Cron Class
 * 
 * Handles scheduled exchange rate updates
 */
class OA_CC_Cron {
    
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
        // Hook into the scheduled event
        add_action('oa_cc_daily_exchange_rate_update', array($this, 'update_exchange_rate'));
        
        // Add custom cron schedule if needed (daily is already available)
        add_filter('cron_schedules', array($this, 'add_custom_schedules'));
    }
    
    /**
     * Add custom cron schedules
     */
    public function add_custom_schedules($schedules) {
        // Daily schedule is already available, but we can add others if needed
        if (!isset($schedules['daily'])) {
            $schedules['daily'] = array(
                'interval' => 86400, // 24 hours
                'display' => __('Once Daily')
            );
        }
        
        return $schedules;
    }
    
    /**
     * Update exchange rate (called by cron)
     */
    public function update_exchange_rate() {
        $rate = OA_CC_API::update_exchange_rate();
        
        if ($rate !== false) {
            // Log success (optional - can be removed in production)
            error_log('OA Currency Converter: Exchange rate updated to ' . $rate);
        } else {
            // Log failure
            error_log('OA Currency Converter: Failed to update exchange rate');
        }
        
        return $rate;
    }
    
    /**
     * Manually trigger exchange rate update (for admin use)
     */
    public static function manual_update() {
        return OA_CC_API::update_exchange_rate();
    }
}
