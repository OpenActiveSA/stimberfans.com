<?php
/**
 * OA TFP Gravity Forms Email Styling Class
 * 
 * Handles custom styling for Gravity Forms email notifications
 */
class OA_TFP_Gravity_Forms {
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->init_hooks();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Add custom CSS to Gravity Forms notifications
        add_filter('gform_notification', array($this, 'add_email_styles'), 10, 3);
        
        // Alternative filter for pre-send modification
        add_filter('gform_pre_send_email', array($this, 'inject_email_styles'), 10, 4);
    }
    
    /**
     * Add email styles to notification
     * 
     * @param array $notification The notification object
     * @param array $form The form object
     * @param array $entry The entry object
     * @return array Modified notification
     */
    public function add_email_styles($notification, $form, $entry) {
        // Only modify HTML notifications
        if (isset($notification['message'])) {
            $notification['message'] = $this->wrap_with_styles($notification['message']);
        }
        
        return $notification;
    }
    
    /**
     * Inject email styles before sending
     * 
     * @param array $email The email array
     * @param string $message_format The message format (html or text)
     * @param array $notification The notification object
     * @param array $entry The entry object
     * @return array Modified email
     */
    public function inject_email_styles($email, $message_format, $notification, $entry) {
        // Only for HTML emails
        if ($message_format === 'html' && isset($email['message'])) {
            $email['message'] = $this->wrap_with_styles($email['message']);
        }
        
        return $email;
    }
    
    /**
     * Wrap email content with modern styles
     * 
     * @param string $message The email message content
     * @return string Modified message with styles
     */
    private function wrap_with_styles($message) {
        // Get the CSS content
        $css_file = OA_TFP_PLUGIN_PATH . 'assets/oa-tfp-gravity-forms-email.css';
        
        if (!file_exists($css_file)) {
            return $message;
        }
        
        $css = file_get_contents($css_file);
        
        // Remove @media queries and animations for better email client compatibility
        $css = $this->optimize_css_for_email($css);
        
        // Create the styled email template
        $styled_message = '
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Form Submission</title>
    <style type="text/css">
        ' . $css . '
    </style>
</head>
<body style="margin: 0; padding: 20px; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, \'Helvetica Neue\', Arial, sans-serif;">
    <div style="max-width: 800px; margin: 0 auto; background-color: #ffffff; padding: 0;">
        ' . $message . '
    </div>
</body>
</html>';
        
        return $styled_message;
    }
    
    /**
     * Optimize CSS for email clients
     * 
     * @param string $css The CSS content
     * @return string Optimized CSS
     */
    private function optimize_css_for_email($css) {
        // Remove comments
        $css = preg_replace('/\/\*.*?\*\//s', '', $css);
        
        // Remove @keyframes (not widely supported in email)
        $css = preg_replace('/@keyframes[^}]+\{[^}]+\}/s', '', $css);
        
        // Keep @media queries but make them more compatible
        // Most email clients support basic @media queries
        
        // Remove animation properties (not supported)
        $css = preg_replace('/animation:[^;]+;/i', '', $css);
        
        // Remove transition properties (limited support)
        $css = preg_replace('/transition:[^;]+;/i', '', $css);
        
        // Remove transform properties (limited support)
        $css = preg_replace('/transform:[^;]+;/i', '', $css);
        
        // Minify CSS (remove extra whitespace)
        $css = preg_replace('/\s+/', ' ', $css);
        $css = preg_replace('/\s*([{}:;,])\s*/', '$1', $css);
        
        return $css;
    }
    
    /**
     * Get inline styles as string for direct application
     * Useful for admin area previews
     * 
     * @return string CSS styles
     */
    public function get_inline_styles() {
        $css_file = OA_TFP_PLUGIN_PATH . 'assets/oa-tfp-gravity-forms-email.css';
        
        if (!file_exists($css_file)) {
            return '';
        }
        
        $css = file_get_contents($css_file);
        return $this->optimize_css_for_email($css);
    }
    
    /**
     * Add styles to admin notification preview
     */
    public function add_admin_notification_styles() {
        if (!is_admin()) {
            return;
        }
        
        $screen = get_current_screen();
        
        // Check if we're on a Gravity Forms notification page
        if ($screen && (strpos($screen->id, 'gf_') !== false || strpos($screen->id, 'gravityforms') !== false)) {
            wp_enqueue_style(
                'oa-tfp-gf-email-preview',
                OA_TFP_PLUGIN_URL . 'assets/oa-tfp-gravity-forms-email.css',
                array(),
                OA_TFP_PLUGIN_VERSION
            );
        }
    }
}


