<?php
/**
 * Frontend Class
 *
 * @package TASA_Payroll
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * TASA Payroll Frontend Class
 */
class TASA_Payroll_Frontend {
    
    /**
     * Single instance
     */
    private static $instance = null;
    
    /**
     * Database instance
     */
    private $db;
    
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
        $this->db = TASA_Payroll_Database::get_instance();
        
        add_shortcode('tasa_payroll', array($this, 'render_payroll_shortcode'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_tasa_download_payslip', array($this, 'download_payslip'));
    }
    
    /**
     * Enqueue frontend scripts and styles
     */
    public function enqueue_scripts() {
        if (is_user_logged_in()) {
            wp_enqueue_style(
                'tasa-payroll-frontend',
                TASA_PAYROLL_PLUGIN_URL . 'assets/css/frontend.css',
                array(),
                TASA_PAYROLL_VERSION
            );
            
            wp_enqueue_script(
                'tasa-payroll-frontend',
                TASA_PAYROLL_PLUGIN_URL . 'assets/js/frontend.js',
                array('jquery'),
                TASA_PAYROLL_VERSION,
                true
            );
            
            wp_localize_script('tasa-payroll-frontend', 'tasaPayrollFrontend', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('tasa_payroll_frontend_nonce'),
            ));
        }
    }
    
    /**
     * Render payroll shortcode
     */
    public function render_payroll_shortcode($atts) {
        if (!is_user_logged_in()) {
            return '<p>' . __('Please log in to view your payroll records.', 'tasa-payroll') . '</p>';
        }
        
        $user_id = get_current_user_id();
        $employee_detail = $this->db->get_employee_detail($user_id);
        $employee_display_id = tasa_payroll_get_employee_display_id(
            $user_id,
            $employee_detail && !empty($employee_detail->employee_id) ? $employee_detail->employee_id : ''
        );
        $employee_phone = $employee_detail && !empty($employee_detail->phone_number)
            ? tasa_payroll_format_phone_number($employee_detail->phone_number)
            : '';
        
        // Get filter parameters
        $filter_month = isset($_GET['filter_month']) ? intval($_GET['filter_month']) : null;
        $filter_year = isset($_GET['filter_year']) ? intval($_GET['filter_year']) : null;
        
        // Get payroll records
        $payrolls = $this->db->get_user_payrolls($user_id, $filter_month, $filter_year);
        
        // Get available years for filter
        $all_payrolls = $this->db->get_user_payrolls($user_id);
        $available_years = array();
        foreach ($all_payrolls as $p) {
            if (!in_array($p->year, $available_years)) {
                $available_years[] = $p->year;
            }
        }
        rsort($available_years);
        
        ob_start();
        include TASA_PAYROLL_PLUGIN_DIR . 'templates/frontend/payroll-list.php';
        return ob_get_clean();
    }
    
    /**
     * Download payslip PDF
     */
    public function download_payslip() {
        // Verify nonce
        if (!isset($_GET['nonce']) || !wp_verify_nonce($_GET['nonce'], 'tasa_payroll_frontend_nonce')) {
            wp_die(__('Security check failed.', 'tasa-payroll'));
        }
        
        if (!is_user_logged_in()) {
            wp_die(__('You must be logged in to download payslips.', 'tasa-payroll'));
        }
        
        $payroll_id = isset($_GET['payroll_id']) ? intval($_GET['payroll_id']) : 0;
        
        if (!$payroll_id) {
            wp_die(__('Invalid payroll ID.', 'tasa-payroll'));
        }
        
        // Get payroll record
        $payroll = $this->db->get_payroll($payroll_id);
        
        if (!$payroll) {
            wp_die(__('Payroll record not found.', 'tasa-payroll'));
        }
        
        // Verify user owns this payroll record
        $current_user_id = get_current_user_id();
        if ($payroll->user_id != $current_user_id && !current_user_can('manage_options')) {
            wp_die(__('You do not have permission to download this payslip.', 'tasa-payroll'));
        }
        
        // Generate and download PDF
        $pdf_generator = TASA_Payroll_PDF_Generator::get_instance();
        $pdf_generator->generate_payslip($payroll);
        
        exit;
    }
}
