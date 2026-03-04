<?php
/**
 * Plugin Name: TASA Payroll Management
 * Plugin URI: https://example.com/tasa-payroll
 * Description: A comprehensive payroll management system for WordPress with admin management, employee portal, and PDF salary slips.
 * Version: 1.1.2
 * Author: TASA
 * Author URI: https://example.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: tasa-payroll
 * Domain Path: /languages
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('TASA_PAYROLL_VERSION', '1.1.2');
define('TASA_PAYROLL_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('TASA_PAYROLL_PLUGIN_URL', plugin_dir_url(__FILE__));
define('TASA_PAYROLL_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main TASA Payroll Class
 */
class TASA_Payroll {
    
    /**
     * Single instance of the class
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
        $this->includes();
        $this->init_hooks();
    }
    
    /**
     * Include required files
     */
    private function includes() {
        require_once TASA_PAYROLL_PLUGIN_DIR . 'includes/class-database.php';
        require_once TASA_PAYROLL_PLUGIN_DIR . 'includes/class-admin.php';
        require_once TASA_PAYROLL_PLUGIN_DIR . 'includes/class-settings.php';
        require_once TASA_PAYROLL_PLUGIN_DIR . 'includes/class-frontend.php';
        require_once TASA_PAYROLL_PLUGIN_DIR . 'includes/class-pdf-generator.php';
        require_once TASA_PAYROLL_PLUGIN_DIR . 'includes/functions.php';
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        add_action('plugins_loaded', array($this, 'init'));
    }
    
    /**
     * Initialize plugin
     */
    public function init() {
        // Load text domain
        load_plugin_textdomain('tasa-payroll', false, dirname(TASA_PAYROLL_PLUGIN_BASENAME) . '/languages');

        // Ensure database schema is up to date.
        if (get_option('tasa_payroll_db_version') !== TASA_PAYROLL_VERSION) {
            TASA_Payroll_Database::create_tables();
        }
        
        // Initialize classes
        TASA_Payroll_Database::get_instance();
        TASA_Payroll_Admin::get_instance();
        TASA_Payroll_Settings::get_instance();
        TASA_Payroll_Frontend::get_instance();
        TASA_Payroll_PDF_Generator::get_instance();
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        TASA_Payroll_Database::create_tables();
        
        // Set default options
        $default_settings = array(
            'company_name' => get_bloginfo('name'),
            'company_address' => '',
            'company_logo' => '',
        );
        
        if (!get_option('tasa_payroll_settings')) {
            add_option('tasa_payroll_settings', $default_settings);
        }
        
        flush_rewrite_rules();
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        flush_rewrite_rules();
    }
}

/**
 * Initialize the plugin
 */
function tasa_payroll() {
    return TASA_Payroll::get_instance();
}

// Start the plugin
tasa_payroll();
