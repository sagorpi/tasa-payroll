<?php
/**
 * Employee Edit Template
 *
 * @package TASA_Payroll
 */

if (!defined('ABSPATH')) {
    exit;
}

$current_base_salary = $employee_detail ? (float) $employee_detail->base_salary : 0;
$current_employee_id = $employee_detail && !empty($employee_detail->employee_id) ? $employee_detail->employee_id : '';
$current_phone_number = $employee_detail && !empty($employee_detail->phone_number) ? $employee_detail->phone_number : '';
?>

<div class="wrap">
    <h1><?php _e('Edit Employee Details', 'tasa-payroll'); ?></h1>

    <?php if (isset($_GET['error']) && $_GET['error'] === 'invalid_salary') : ?>
        <div class="notice notice-error is-dismissible">
            <p><?php _e('Base salary must be a positive number.', 'tasa-payroll'); ?></p>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['error']) && $_GET['error'] === 'save_failed') : ?>
        <div class="notice notice-error is-dismissible">
            <p><?php _e('Failed to save employee details.', 'tasa-payroll'); ?></p>
        </div>
    <?php endif; ?>

    <table class="form-table">
        <tr>
            <th><?php _e('Employee Name', 'tasa-payroll'); ?></th>
            <td><?php echo esc_html($user->display_name); ?></td>
        </tr>
        <tr>
            <th><?php _e('Email', 'tasa-payroll'); ?></th>
            <td><?php echo esc_html($user->user_email); ?></td>
        </tr>
        <tr>
            <th><?php _e('WordPress User ID', 'tasa-payroll'); ?></th>
            <td><?php echo (int) $user->ID; ?></td>
        </tr>
    </table>

    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" id="tasa-employee-form">
        <input type="hidden" name="action" value="tasa_save_employee" />
        <input type="hidden" name="user_id" value="<?php echo (int) $user->ID; ?>" />
        <?php wp_nonce_field('tasa_save_employee', 'tasa_employee_nonce'); ?>

        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="base_salary"><?php _e('Base Salary', 'tasa-payroll'); ?> <span class="required">*</span></label>
                </th>
                <td>
                    <input type="number" name="base_salary" id="base_salary" value="<?php echo esc_attr(number_format($current_base_salary, 2, '.', '')); ?>" min="0.01" step="0.01" class="regular-text" required />
                    <p class="description"><?php _e('Default monthly salary used to auto-fill payroll forms.', 'tasa-payroll'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="employee_id"><?php _e('Employee ID', 'tasa-payroll'); ?></label>
                </th>
                <td>
                    <input type="text" name="employee_id" id="employee_id" value="<?php echo esc_attr($current_employee_id); ?>" class="regular-text" maxlength="100" />
                    <p class="description"><?php _e('Optional custom employee identifier (shown in payroll records and salary slips).', 'tasa-payroll'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="phone_number"><?php _e('Phone Number', 'tasa-payroll'); ?></label>
                </th>
                <td>
                    <input type="text" name="phone_number" id="phone_number" value="<?php echo esc_attr(tasa_payroll_format_phone_number($current_phone_number)); ?>" class="regular-text" maxlength="30" />
                    <p class="description"><?php _e('Optional employee contact number.', 'tasa-payroll'); ?></p>
                </td>
            </tr>
        </table>

        <p class="submit">
            <input type="submit" class="button button-primary" value="<?php esc_attr_e('Save Employee Details', 'tasa-payroll'); ?>" />
            <a href="<?php echo esc_url(admin_url('admin.php?page=tasa-payroll-employees')); ?>" class="button">
                <?php _e('Back to Employees', 'tasa-payroll'); ?>
            </a>
        </p>
    </form>
</div>
