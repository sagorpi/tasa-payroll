<?php
/**
 * Admin Class
 *
 * @package TASA_Payroll
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * TASA Payroll Admin Class
 */
class TASA_Payroll_Admin {
    
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
        
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_post_tasa_save_payroll', array($this, 'save_payroll'));
        add_action('admin_post_tasa_delete_payroll', array($this, 'delete_payroll'));
        add_action('admin_post_tasa_preview_payslip', array($this, 'preview_payslip'));
        add_action('admin_post_tasa_save_employee', array($this, 'save_employee'));
        add_action('wp_ajax_tasa_get_employee_detail', array($this, 'ajax_get_employee_detail'));
        add_action('wp_ajax_tasa_build_payroll_preview', array($this, 'ajax_build_payroll_preview'));
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            __('TASA Payroll', 'tasa-payroll'),
            __('Payroll', 'tasa-payroll'),
            'manage_options',
            'tasa-payroll',
            array($this, 'render_list_page'),
            'dashicons-money-alt',
            30
        );
        
        add_submenu_page(
            'tasa-payroll',
            __('All Payroll Records', 'tasa-payroll'),
            __('All Records', 'tasa-payroll'),
            'manage_options',
            'tasa-payroll',
            array($this, 'render_list_page')
        );
        
        add_submenu_page(
            'tasa-payroll',
            __('Add New Payroll', 'tasa-payroll'),
            __('Add New', 'tasa-payroll'),
            'manage_options',
            'tasa-payroll-add',
            array($this, 'render_add_edit_page')
        );

        add_submenu_page(
            'tasa-payroll',
            __('Employee Management', 'tasa-payroll'),
            __('Employees', 'tasa-payroll'),
            'manage_options',
            'tasa-payroll-employees',
            array($this, 'render_employees_page')
        );
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_scripts($hook) {
        if (strpos($hook, 'tasa-payroll') === false) {
            return;
        }
        
        wp_enqueue_style(
            'tasa-payroll-admin',
            TASA_PAYROLL_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            TASA_PAYROLL_VERSION
        );
        
        wp_enqueue_script(
            'tasa-payroll-admin',
            TASA_PAYROLL_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            TASA_PAYROLL_VERSION,
            true
        );
        
        wp_localize_script('tasa-payroll-admin', 'tasaPayroll', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('tasa_payroll_nonce'),
        ));
    }
    
    /**
     * Render list page
     */
    public function render_list_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        
        // Get pagination parameters
        $paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $per_page = 20;
        $offset = ($paged - 1) * $per_page;
        $search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
        
        // Get payroll records
        $payrolls = $this->db->get_all_payrolls($per_page, $offset, $search);
        $employee_details = array();
        if (!empty($payrolls)) {
            $user_ids = wp_list_pluck($payrolls, 'user_id');
            $employee_details = $this->db->get_employee_details_by_user_ids($user_ids);
        }
        $total_items = $this->db->get_total_count($search);
        $total_pages = ceil($total_items / $per_page);
        
        include TASA_PAYROLL_PLUGIN_DIR . 'templates/admin/list.php';
    }
    
    /**
     * Render add/edit page
     */
    public function render_add_edit_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        
        $payroll = null;
        $is_edit = false;
        
        if (isset($_GET['id'])) {
            $id = intval($_GET['id']);
            $payroll = $this->db->get_payroll($id);
            $is_edit = true;
        }
        
        // Get all users
        $users = get_users(array('orderby' => 'display_name'));
        $employee_details = array();

        if (!empty($users)) {
            $user_ids = wp_list_pluck($users, 'ID');
            $employee_details = $this->db->get_employee_details_by_user_ids($user_ids);
        }

        include TASA_PAYROLL_PLUGIN_DIR . 'templates/admin/add-edit.php';
    }

    /**
     * Render employees list page
     */
    public function render_employees_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        $action = isset($_GET['action']) ? sanitize_key(wp_unslash($_GET['action'])) : '';
        if ($action === 'edit') {
            $this->render_employee_edit_page();
            return;
        }

        $paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $per_page = 20;
        $offset = ($paged - 1) * $per_page;
        $search = isset($_GET['s']) ? sanitize_text_field(wp_unslash($_GET['s'])) : '';

        $employees = $this->db->get_employees_with_details($per_page, $offset, $search);
        $total_items = $this->db->get_total_employee_count($search);
        $total_pages = max(1, (int) ceil($total_items / $per_page));

        include TASA_PAYROLL_PLUGIN_DIR . 'templates/admin/employees-list.php';
    }

    /**
     * Render employee edit page
     */
    public function render_employee_edit_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        $user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
        if ($user_id <= 0) {
            wp_die(__('Invalid user selected.', 'tasa-payroll'));
        }

        $user = get_userdata($user_id);
        if (!$user) {
            wp_die(__('User not found.', 'tasa-payroll'));
        }

        $employee_detail = $this->db->get_employee_detail($user_id);

        include TASA_PAYROLL_PLUGIN_DIR . 'templates/admin/employee-edit.php';
    }

    /**
     * Save payroll record
     */
    public function save_payroll() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to perform this action.'));
        }

        // Verify nonce
        if (!isset($_POST['tasa_payroll_nonce']) || !wp_verify_nonce($_POST['tasa_payroll_nonce'], 'tasa_save_payroll')) {
            wp_die(__('Security check failed.'));
        }

        // Sanitize and validate input
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        $month = isset($_POST['month']) ? intval($_POST['month']) : 0;
        $year = isset($_POST['year']) ? intval($_POST['year']) : 0;
        $total_working_days = isset($_POST['total_working_days']) ? intval($_POST['total_working_days']) : 0;
        $days_absent = isset($_POST['days_absent']) ? floatval($_POST['days_absent']) : 0;
        $monthly_salary = isset($_POST['monthly_salary']) ? floatval($_POST['monthly_salary']) : 0;
        $bonus = isset($_POST['bonus']) ? floatval($_POST['bonus']) : 0;
        $income_tax = isset($_POST['income_tax']) ? floatval($_POST['income_tax']) : 0;
        $provident_fund = isset($_POST['provident_fund']) ? floatval($_POST['provident_fund']) : 0;

        if ($monthly_salary <= 0 && $user_id > 0) {
            $employee_detail = $this->db->get_employee_detail($user_id);
            if ($employee_detail && (float) $employee_detail->base_salary > 0) {
                $monthly_salary = (float) $employee_detail->base_salary;
            }
        }

        // Validate required fields
        if (empty($user_id) || empty($month) || empty($year) || empty($total_working_days) || $monthly_salary <= 0) {
            wp_redirect(add_query_arg(array('error' => 'missing_fields'), wp_get_referer()));
            exit;
        }

        // Calculate per day salary and final salary
        $per_day_salary = $monthly_salary / $total_working_days;
        $days_present = $total_working_days - $days_absent;
        $final_salary = ($days_present * $per_day_salary) + $bonus - $income_tax - $provident_fund;

        $data = array(
            'user_id' => $user_id,
            'month' => $month,
            'year' => $year,
            'total_working_days' => $total_working_days,
            'days_absent' => $days_absent,
            'monthly_salary' => $monthly_salary,
            'per_day_salary' => $per_day_salary,
            'bonus' => $bonus,
            'income_tax' => $income_tax,
            'provident_fund' => $provident_fund,
            'final_salary' => $final_salary,
            'updated_at' => current_time('mysql'),
        );

        $id = isset($_POST['payroll_id']) ? intval($_POST['payroll_id']) : 0;

        if ($id > 0) {
            // Update existing record
            $result = $this->db->update_payroll($id, $data);
            $redirect_url = add_query_arg(array('page' => 'tasa-payroll', 'updated' => '1'), admin_url('admin.php'));
        } else {
            // Check if payroll already exists for this user/month/year
            if ($this->db->payroll_exists($user_id, $month, $year)) {
                wp_redirect(add_query_arg(array('error' => 'duplicate'), wp_get_referer()));
                exit;
            }

            // Insert new record
            $data['created_at'] = current_time('mysql');
            $data['created_by'] = get_current_user_id();

            $result = $this->db->insert_payroll($data);
            $redirect_url = add_query_arg(array('page' => 'tasa-payroll', 'added' => '1'), admin_url('admin.php'));
        }

        if ($result === false) {
            wp_redirect(add_query_arg(array('error' => 'save_failed'), wp_get_referer()));
        } else {
            wp_redirect($redirect_url);
        }

        exit;
    }

    /**
     * Save employee details
     */
    public function save_employee() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to perform this action.'));
        }

        if (
            !isset($_POST['tasa_employee_nonce']) ||
            !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['tasa_employee_nonce'])), 'tasa_save_employee')
        ) {
            wp_die(__('Security check failed.'));
        }

        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        $base_salary = isset($_POST['base_salary']) ? floatval($_POST['base_salary']) : 0;
        $employee_id = isset($_POST['employee_id']) ? sanitize_text_field(wp_unslash($_POST['employee_id'])) : '';
        $phone_number = isset($_POST['phone_number']) ? tasa_payroll_sanitize_phone_number(wp_unslash($_POST['phone_number'])) : '';

        if ($user_id <= 0 || !get_userdata($user_id)) {
            wp_redirect(add_query_arg(array('page' => 'tasa-payroll-employees', 'error' => 'invalid_user'), admin_url('admin.php')));
            exit;
        }

        if ($base_salary <= 0) {
            wp_redirect(
                add_query_arg(
                    array(
                        'page' => 'tasa-payroll-employees',
                        'action' => 'edit',
                        'user_id' => $user_id,
                        'error' => 'invalid_salary',
                    ),
                    admin_url('admin.php')
                )
            );
            exit;
        }

        $result = $this->db->upsert_employee_detail($user_id, $base_salary, $employee_id, $phone_number);

        if ($result === false) {
            wp_redirect(
                add_query_arg(
                    array(
                        'page' => 'tasa-payroll-employees',
                        'action' => 'edit',
                        'user_id' => $user_id,
                        'error' => 'save_failed',
                    ),
                    admin_url('admin.php')
                )
            );
            exit;
        }

        wp_redirect(add_query_arg(array('page' => 'tasa-payroll-employees', 'updated' => '1'), admin_url('admin.php')));
        exit;
    }

    /**
     * AJAX: Get employee details for payroll form auto-fill
     */
    public function ajax_get_employee_detail() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Unauthorized request.', 'tasa-payroll')), 403);
        }

        check_ajax_referer('tasa_payroll_nonce', 'nonce');

        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        if ($user_id <= 0 || !get_userdata($user_id)) {
            wp_send_json_error(array('message' => __('Invalid employee.', 'tasa-payroll')), 400);
        }

        $detail = $this->db->get_employee_detail($user_id);
        $custom_employee_id = $detail && !empty($detail->employee_id) ? $detail->employee_id : '';

        wp_send_json_success(array(
            'base_salary' => $detail ? (float) $detail->base_salary : 0,
            'employee_id' => tasa_payroll_get_employee_display_id($user_id, $custom_employee_id),
            'phone_number' => $detail && !empty($detail->phone_number) ? tasa_payroll_format_phone_number($detail->phone_number) : '',
        ));
    }

    /**
     * AJAX: Build a PDF preview URL from current payroll form values.
     */
    public function ajax_build_payroll_preview() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Unauthorized request.', 'tasa-payroll')), 403);
        }

        check_ajax_referer('tasa_payroll_nonce', 'nonce');

        $preview_payload = $this->get_payroll_preview_payload($_POST);
        $key = 'tasa_pdf_preview_' . get_current_user_id() . '_' . wp_generate_password(12, false, false);

        set_transient($key, array(
            'user_id' => get_current_user_id(),
            'payroll' => $preview_payload,
        ), 15 * MINUTE_IN_SECONDS);

        wp_send_json_success(array(
            'preview_url' => $this->get_pdf_preview_url(0, $key),
        ));
    }

    /**
     * Preview payslip as inline/download PDF for admin pages.
     */
    public function preview_payslip() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to perform this action.', 'tasa-payroll'));
        }

        if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'tasa_preview_payslip')) {
            wp_die(__('Security check failed.', 'tasa-payroll'));
        }

        $output_mode = (isset($_GET['inline']) && $_GET['inline'] === '1') ? 'I' : 'D';
        $payroll = null;

        if (!empty($_GET['preview_key'])) {
            $preview_key = sanitize_text_field(wp_unslash($_GET['preview_key']));
            $preview_data = get_transient($preview_key);

            if (
                is_array($preview_data) &&
                isset($preview_data['user_id'], $preview_data['payroll']) &&
                (int) $preview_data['user_id'] === get_current_user_id()
            ) {
                $payroll = (object) $preview_data['payroll'];
            }
        }

        if (!$payroll) {
            $payroll_id = isset($_GET['payroll_id']) ? intval($_GET['payroll_id']) : 0;
            $payroll = $this->get_preview_payroll_object($payroll_id);
        }

        $pdf_generator = TASA_Payroll_PDF_Generator::get_instance();
        $pdf_generator->generate_payslip($payroll, array(
            'output_mode' => $output_mode,
        ));
        exit;
    }

    /**
     * Build sanitized payroll-like payload from add/edit form request values.
     */
    private function get_payroll_preview_payload($source) {
        $source = is_array($source) ? wp_unslash($source) : array();
        $payroll_id = isset($source['payroll_id']) ? intval($source['payroll_id']) : 0;
        $fallback = $this->get_preview_payroll_object($payroll_id);

        $user_id = isset($source['user_id']) ? intval($source['user_id']) : (int) $fallback->user_id;
        if ($user_id <= 0 || !get_userdata($user_id)) {
            $user_id = (int) $fallback->user_id;
        }

        $month = isset($source['month']) ? intval($source['month']) : (int) $fallback->month;
        if ($month < 1 || $month > 12) {
            $month = (int) $fallback->month;
        }

        $year = isset($source['year']) ? intval($source['year']) : (int) $fallback->year;
        if ($year < 1970 || $year > 9999) {
            $year = (int) $fallback->year;
        }

        $total_working_days = isset($source['total_working_days']) ? intval($source['total_working_days']) : (int) $fallback->total_working_days;
        if ($total_working_days <= 0) {
            $total_working_days = (int) $fallback->total_working_days;
        }

        $days_absent = isset($source['days_absent']) ? floatval($source['days_absent']) : (float) $fallback->days_absent;
        if ($days_absent < 0) {
            $days_absent = 0;
        }
        if ($days_absent > $total_working_days) {
            $days_absent = (float) $total_working_days;
        }

        $monthly_salary = isset($source['monthly_salary']) ? floatval($source['monthly_salary']) : (float) $fallback->monthly_salary;
        if ($monthly_salary < 0) {
            $monthly_salary = 0;
        }

        $bonus = isset($source['bonus']) ? floatval($source['bonus']) : (float) $fallback->bonus;
        if ($bonus < 0) {
            $bonus = 0;
        }

        $income_tax = isset($source['income_tax']) ? floatval($source['income_tax']) : (float) $fallback->income_tax;
        if ($income_tax < 0) {
            $income_tax = 0;
        }

        $provident_fund = isset($source['provident_fund']) ? floatval($source['provident_fund']) : (float) $fallback->provident_fund;
        if ($provident_fund < 0) {
            $provident_fund = 0;
        }

        $per_day_salary = $total_working_days > 0 ? ($monthly_salary / $total_working_days) : 0;
        $days_present = max(0, $total_working_days - $days_absent);
        $final_salary = ($days_present * $per_day_salary) + $bonus - $income_tax - $provident_fund;

        return array(
            'id' => 0,
            'user_id' => $user_id,
            'month' => $month,
            'year' => $year,
            'total_working_days' => $total_working_days,
            'days_absent' => $days_absent,
            'monthly_salary' => $monthly_salary,
            'per_day_salary' => $per_day_salary,
            'bonus' => $bonus,
            'income_tax' => $income_tax,
            'provident_fund' => $provident_fund,
            'final_salary' => $final_salary,
        );
    }

    /**
     * Resolve a payroll object for preview/testing.
     */
    private function get_preview_payroll_object($payroll_id = 0) {
        $payroll = null;

        if ($payroll_id > 0) {
            $payroll = $this->db->get_payroll($payroll_id);
        }

        if (!$payroll) {
            $latest = $this->db->get_all_payrolls(1, 0);
            if (!empty($latest)) {
                $payroll = $latest[0];
            }
        }

        if ($payroll) {
            return $payroll;
        }

        $user_id = get_current_user_id();
        if ($user_id <= 0) {
            $fallback_users = get_users(array('number' => 1, 'fields' => 'ID'));
            $user_id = !empty($fallback_users) ? (int) $fallback_users[0] : 0;
        }

        if ($user_id <= 0) {
            wp_die(__('Please create at least one WordPress user to preview the payslip.', 'tasa-payroll'));
        }

        $month = (int) current_time('n');
        $year = (int) current_time('Y');
        $total_working_days = (int) cal_days_in_month(CAL_GREGORIAN, $month, $year);
        $days_absent = 2;
        $bonus = 2500;
        $income_tax = 1000;
        $provident_fund = 500;
        $employee_detail = $this->db->get_employee_detail($user_id);
        $monthly_salary = ($employee_detail && (float) $employee_detail->base_salary > 0) ? (float) $employee_detail->base_salary : 50000;
        $per_day_salary = $monthly_salary / $total_working_days;
        $days_present = $total_working_days - $days_absent;
        $final_salary = ($days_present * $per_day_salary) + $bonus - $income_tax - $provident_fund;

        return (object) array(
            'id' => 0,
            'user_id' => $user_id,
            'month' => $month,
            'year' => $year,
            'total_working_days' => $total_working_days,
            'days_absent' => $days_absent,
            'monthly_salary' => $monthly_salary,
            'per_day_salary' => $per_day_salary,
            'bonus' => $bonus,
            'income_tax' => $income_tax,
            'provident_fund' => $provident_fund,
            'final_salary' => $final_salary,
        );
    }

    /**
     * Build admin-post URL for PDF preview.
     */
    private function get_pdf_preview_url($payroll_id = 0, $preview_key = '') {
        $args = array(
            'action' => 'tasa_preview_payslip',
            'inline' => '1',
            '_wpnonce' => wp_create_nonce('tasa_preview_payslip'),
        );

        if ($payroll_id > 0) {
            $args['payroll_id'] = (int) $payroll_id;
        }

        if (!empty($preview_key)) {
            $args['preview_key'] = $preview_key;
        }

        return add_query_arg($args, admin_url('admin-post.php'));
    }

    /**
     * Delete payroll record
     */
    public function delete_payroll() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to perform this action.'));
        }

        // Verify nonce
        if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'delete_payroll_' . $_GET['id'])) {
            wp_die(__('Security check failed.'));
        }

        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;

        if ($id > 0) {
            $result = $this->db->delete_payroll($id);

            if ($result !== false) {
                $redirect_url = add_query_arg(array('page' => 'tasa-payroll', 'deleted' => '1'), admin_url('admin.php'));
            } else {
                $redirect_url = add_query_arg(array('page' => 'tasa-payroll', 'error' => 'delete_failed'), admin_url('admin.php'));
            }
        } else {
            $redirect_url = add_query_arg(array('page' => 'tasa-payroll', 'error' => 'invalid_id'), admin_url('admin.php'));
        }

        wp_redirect($redirect_url);
        exit;
    }
}
