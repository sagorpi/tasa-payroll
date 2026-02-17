<?php
/**
 * Helper Functions
 *
 * @package TASA_Payroll
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get month name from number
 */
function tasa_payroll_get_month_name($month) {
    return date('F', mktime(0, 0, 0, $month, 1));
}

/**
 * Get days in month
 */
function tasa_payroll_get_days_in_month($month, $year) {
    return cal_days_in_month(CAL_GREGORIAN, $month, $year);
}

/**
 * Format currency
 */
function tasa_payroll_format_currency($amount) {
    return '₹' . number_format($amount, 2);
}

/**
 * Get company settings
 */
function tasa_payroll_get_settings() {
    return get_option('tasa_payroll_settings', array(
        'company_name' => get_bloginfo('name'),
        'company_address' => '',
        'company_logo' => '',
    ));
}

/**
 * Check if user can view payroll
 */
function tasa_payroll_user_can_view($payroll_id) {
    if (!is_user_logged_in()) {
        return false;
    }
    
    $current_user_id = get_current_user_id();
    
    // Admins can view all
    if (current_user_can('manage_options')) {
        return true;
    }
    
    // Get payroll record
    $db = TASA_Payroll_Database::get_instance();
    $payroll = $db->get_payroll($payroll_id);
    
    if (!$payroll) {
        return false;
    }
    
    // User can only view their own
    return $payroll->user_id == $current_user_id;
}

/**
 * Build display employee ID with fallback to WordPress ID
 */
function tasa_payroll_get_employee_display_id($user_id, $custom_employee_id = '') {
    $custom_employee_id = trim((string) $custom_employee_id);

    if ($custom_employee_id !== '') {
        return $custom_employee_id;
    }

    return 'WP-' . str_pad((string) absint($user_id), 4, '0', STR_PAD_LEFT);
}

/**
 * Keep phone number chars limited to common phone symbols
 */
function tasa_payroll_sanitize_phone_number($phone_number) {
    $phone_number = trim((string) $phone_number);
    return preg_replace('/[^0-9\+\-\s\(\)]/', '', $phone_number);
}

/**
 * Format phone number for display
 */
function tasa_payroll_format_phone_number($phone_number) {
    $phone_number = tasa_payroll_sanitize_phone_number($phone_number);

    if ($phone_number === '') {
        return '';
    }

    $digits_only = preg_replace('/\D/', '', $phone_number);

    if (strlen($digits_only) === 10) {
        return sprintf('(%s) %s-%s', substr($digits_only, 0, 3), substr($digits_only, 3, 3), substr($digits_only, 6));
    }

    if (strlen($digits_only) === 11 && substr($digits_only, 0, 1) === '1') {
        return sprintf('+1 (%s) %s-%s', substr($digits_only, 1, 3), substr($digits_only, 4, 3), substr($digits_only, 7));
    }

    return $phone_number;
}
