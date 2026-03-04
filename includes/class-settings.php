<?php
/**
 * Settings Class
 *
 * @package TASA_Payroll
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * TASA Payroll Settings Class
 */
class TASA_Payroll_Settings {
    
    /**
     * Single instance
     */
    private static $instance = null;
    
    /**
     * Get instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        add_action('admin_menu', array($this, 'add_settings_page'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
    }
    
    /**
     * Add settings page to admin menu
     */
    public function add_settings_page() {
        add_submenu_page(
            'tasa-payroll',
            __('Payroll Settings', 'tasa-payroll'),
            __('Settings', 'tasa-payroll'),
            'manage_options',
            'tasa-payroll-settings',
            array($this, 'render_settings_page')
        );
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        register_setting('tasa_payroll_settings_group', 'tasa_payroll_settings', array($this, 'sanitize_settings'));
        
        // Company Information Section
        add_settings_section(
            'tasa_payroll_company_section',
            __('Company Information', 'tasa-payroll'),
            array($this, 'company_section_callback'),
            'tasa-payroll-settings'
        );
        
        add_settings_field(
            'company_name',
            __('Company Name', 'tasa-payroll'),
            array($this, 'company_name_callback'),
            'tasa-payroll-settings',
            'tasa_payroll_company_section'
        );
        
        add_settings_field(
            'company_address',
            __('Company Address', 'tasa-payroll'),
            array($this, 'company_address_callback'),
            'tasa-payroll-settings',
            'tasa_payroll_company_section'
        );
        
        add_settings_field(
            'company_logo',
            __('Company Logo', 'tasa-payroll'),
            array($this, 'company_logo_callback'),
            'tasa-payroll-settings',
            'tasa_payroll_company_section'
        );
    }
    
    /**
     * Enqueue scripts
     */
    public function enqueue_scripts($hook) {
        $current_page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : '';
        $is_settings_page = ('tasa-payroll-settings' === $current_page) || ('tasa-payroll_page_tasa-payroll-settings' === $hook);

        if (!$is_settings_page) {
            return;
        }
        
        wp_enqueue_media();
        wp_enqueue_script(
            'tasa-payroll-settings',
            TASA_PAYROLL_PLUGIN_URL . 'assets/js/settings.js',
            array('jquery'),
            TASA_PAYROLL_VERSION,
            true
        );
    }
    
    /**
     * Sanitize settings
     */
    public function sanitize_settings($input) {
        $sanitized = array();
        
        if (isset($input['company_name'])) {
            $sanitized['company_name'] = sanitize_text_field($input['company_name']);
        }
        
        if (isset($input['company_address'])) {
            $sanitized['company_address'] = sanitize_textarea_field($input['company_address']);
        }
        
        if (isset($input['company_logo'])) {
            $sanitized['company_logo'] = esc_url_raw($input['company_logo']);
        }
        
        return $sanitized;
    }
    
    /**
     * Company section callback
     */
    public function company_section_callback() {
        echo '<p>' . __('Configure your company information for salary slips.', 'tasa-payroll') . '</p>';
    }
    
    /**
     * Company name field callback
     */
    public function company_name_callback() {
        $settings = get_option('tasa_payroll_settings');
        $value = isset($settings['company_name']) ? $settings['company_name'] : '';
        echo '<input type="text" name="tasa_payroll_settings[company_name]" value="' . esc_attr($value) . '" class="regular-text" />';
    }
    
    /**
     * Company address field callback
     */
    public function company_address_callback() {
        $settings = get_option('tasa_payroll_settings');
        $value = isset($settings['company_address']) ? $settings['company_address'] : '';
        echo '<textarea name="tasa_payroll_settings[company_address]" rows="4" class="large-text">' . esc_textarea($value) . '</textarea>';
    }

    /**
     * Company logo field callback
     */
    public function company_logo_callback() {
        $settings = get_option('tasa_payroll_settings');
        $value = isset($settings['company_logo']) ? $settings['company_logo'] : '';
        ?>
        <div class="tasa-logo-upload">
            <input type="hidden" name="tasa_payroll_settings[company_logo]" id="company_logo" value="<?php echo esc_attr($value); ?>" />
            <button type="button" class="button tasa-upload-logo-button">
                <?php _e('Upload Logo', 'tasa-payroll'); ?>
            </button>
            <button type="button" class="button tasa-remove-logo-button" <?php echo empty($value) ? 'style="display:none;"' : ''; ?>>
                <?php _e('Remove Logo', 'tasa-payroll'); ?>
            </button>
            <div class="tasa-logo-preview" style="margin-top: 10px;">
                <?php if (!empty($value)) : ?>
                    <img src="<?php echo esc_url($value); ?>" style="max-width: 200px; height: auto;" />
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Render settings page
     */
    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        // Check if settings were saved
        if (isset($_GET['settings-updated'])) {
            add_settings_error(
                'tasa_payroll_messages',
                'tasa_payroll_message',
                __('Settings Saved', 'tasa-payroll'),
                'updated'
            );
        }

        settings_errors('tasa_payroll_messages');
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form action="options.php" method="post">
                <?php
                settings_fields('tasa_payroll_settings_group');
                do_settings_sections('tasa-payroll-settings');
                submit_button(__('Save Settings', 'tasa-payroll'));
                ?>
            </form>
        </div>
        <?php
    }
}
