<?php
/**
 * Admin Add/Edit Template
 *
 * @package TASA_Payroll
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

$current_month = $payroll ? $payroll->month : date('n');
$current_year = $payroll ? $payroll->year : date('Y');
?>

<div class="wrap">
    <h1><?php echo $is_edit ? __('Edit Payroll Record', 'tasa-payroll') : __('Add New Payroll Record', 'tasa-payroll'); ?></h1>
    
    <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" id="tasa-payroll-form">
        <input type="hidden" name="action" value="tasa_save_payroll" />
        <?php wp_nonce_field('tasa_save_payroll', 'tasa_payroll_nonce'); ?>
        
        <?php if ($is_edit) : ?>
            <input type="hidden" name="payroll_id" value="<?php echo esc_attr($payroll->id); ?>" />
        <?php endif; ?>
        
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="user_id"><?php _e('Employee', 'tasa-payroll'); ?> <span class="required">*</span></label>
                </th>
                <td>
                    <select name="user_id" id="user_id" class="regular-text" required <?php echo $is_edit ? 'disabled' : ''; ?>>
                        <option value=""><?php _e('Select Employee', 'tasa-payroll'); ?></option>
                        <?php foreach ($users as $user) : ?>
                            <?php
                            $detail = isset($employee_details[$user->ID]) ? $employee_details[$user->ID] : null;
                            $base_salary = $detail ? (float) $detail->base_salary : 0;
                            $custom_employee_id = $detail && !empty($detail->employee_id) ? $detail->employee_id : '';
                            $display_employee_id = tasa_payroll_get_employee_display_id($user->ID, $custom_employee_id);
                            ?>
                            <option value="<?php echo esc_attr($user->ID); ?>" <?php selected($payroll ? $payroll->user_id : 0, $user->ID); ?>
                                    data-base-salary="<?php echo esc_attr(number_format($base_salary, 2, '.', '')); ?>"
                                    data-employee-id="<?php echo esc_attr($display_employee_id); ?>">
                                <?php echo esc_html($user->display_name . ' [' . $display_employee_id . '] (' . $user->user_email . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="description">
                        <?php _e('Employee ID:', 'tasa-payroll'); ?> <span id="selected_employee_id"><?php echo $payroll ? esc_html(tasa_payroll_get_employee_display_id($payroll->user_id, isset($employee_details[$payroll->user_id]->employee_id) ? $employee_details[$payroll->user_id]->employee_id : '')) : '-'; ?></span>
                    </p>
                    <?php if ($is_edit) : ?>
                        <input type="hidden" name="user_id" value="<?php echo esc_attr($payroll->user_id); ?>" />
                    <?php endif; ?>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="month"><?php _e('Month', 'tasa-payroll'); ?> <span class="required">*</span></label>
                </th>
                <td>
                    <select name="month" id="month" required <?php echo $is_edit ? 'disabled' : ''; ?>>
                        <?php for ($m = 1; $m <= 12; $m++) : ?>
                            <option value="<?php echo $m; ?>" <?php selected($current_month, $m); ?>>
                                <?php echo date('F', mktime(0, 0, 0, $m, 1)); ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                    <?php if ($is_edit) : ?>
                        <input type="hidden" name="month" value="<?php echo esc_attr($payroll->month); ?>" />
                    <?php endif; ?>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="year"><?php _e('Year', 'tasa-payroll'); ?> <span class="required">*</span></label>
                </th>
                <td>
                    <select name="year" id="year" required <?php echo $is_edit ? 'disabled' : ''; ?>>
                        <?php for ($y = date('Y') - 5; $y <= date('Y') + 1; $y++) : ?>
                            <option value="<?php echo $y; ?>" <?php selected($current_year, $y); ?>>
                                <?php echo $y; ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                    <?php if ($is_edit) : ?>
                        <input type="hidden" name="year" value="<?php echo esc_attr($payroll->year); ?>" />
                    <?php endif; ?>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="total_working_days"><?php _e('Total Working Days', 'tasa-payroll'); ?> <span class="required">*</span></label>
                </th>
                <td>
                    <input type="number" name="total_working_days" id="total_working_days" 
                           value="<?php echo $payroll ? esc_attr($payroll->total_working_days) : ''; ?>" 
                           min="1" max="31" step="1" class="small-text" required />
                    <p class="description"><?php _e('Auto-calculated based on selected month/year', 'tasa-payroll'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="days_absent"><?php _e('Days Absent', 'tasa-payroll'); ?></label>
                </th>
                <td>
                    <input type="number" name="days_absent" id="days_absent" 
                           value="<?php echo $payroll ? esc_attr($payroll->days_absent) : '0'; ?>" 
                           min="0" step="0.5" class="small-text" />
                    <p class="description"><?php _e('Number of days the employee was absent', 'tasa-payroll'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="monthly_salary"><?php _e('Monthly Salary (Basic)', 'tasa-payroll'); ?> <span class="required">*</span></label>
                </th>
                <td>
                    <input type="number" name="monthly_salary" id="monthly_salary" 
                           value="<?php echo $payroll ? esc_attr($payroll->monthly_salary) : ''; ?>" 
                           min="0" step="0.01" class="regular-text" required />
                    <p class="description"><?php _e('Base monthly salary amount', 'tasa-payroll'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="per_day_salary"><?php _e('Per Day Salary', 'tasa-payroll'); ?></label>
                </th>
                <td>
                    <input type="text" id="per_day_salary" class="regular-text" readonly 
                           value="<?php echo $payroll ? esc_attr(number_format($payroll->per_day_salary, 2)) : '0.00'; ?>" />
                    <p class="description"><?php _e('Auto-calculated: Monthly Salary ÷ Total Working Days', 'tasa-payroll'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="bonus"><?php _e('Bonus', 'tasa-payroll'); ?></label>
                </th>
                <td>
                    <input type="number" name="bonus" id="bonus"
                           value="<?php echo $payroll ? esc_attr($payroll->bonus) : '0'; ?>"
                           min="0" step="0.01" class="regular-text" />
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="income_tax"><?php _e('Income Tax', 'tasa-payroll'); ?></label>
                </th>
                <td>
                    <input type="number" name="income_tax" id="income_tax"
                           value="<?php echo $payroll ? esc_attr($payroll->income_tax) : '0'; ?>"
                           min="0" step="0.01" class="regular-text" />
                    <p class="description"><?php _e('Tax deduction amount', 'tasa-payroll'); ?></p>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="provident_fund"><?php _e('Provident Fund', 'tasa-payroll'); ?></label>
                </th>
                <td>
                    <input type="number" name="provident_fund" id="provident_fund"
                           value="<?php echo $payroll ? esc_attr($payroll->provident_fund) : '0'; ?>"
                           min="0" step="0.01" class="regular-text" />
                    <p class="description"><?php _e('Provident fund deduction amount', 'tasa-payroll'); ?></p>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="final_salary"><?php _e('Final Salary (Net Pay)', 'tasa-payroll'); ?></label>
                </th>
                <td>
                    <input type="text" id="final_salary" class="regular-text" readonly
                           value="<?php echo $payroll ? esc_attr(number_format($payroll->final_salary, 2)) : '0.00'; ?>" />
                    <p class="description"><?php _e('Auto-calculated: (Working Days - Absent) × Per Day Salary + Bonus - Income Tax - Provident Fund', 'tasa-payroll'); ?></p>
                </td>
            </tr>
        </table>

        <p class="submit">
            <input type="submit" name="submit" id="submit" class="button button-primary"
                   value="<?php echo $is_edit ? __('Update Payroll', 'tasa-payroll') : __('Add Payroll', 'tasa-payroll'); ?>" />
            <a href="<?php echo admin_url('admin.php?page=tasa-payroll'); ?>" class="button">
                <?php _e('Cancel', 'tasa-payroll'); ?>
            </a>
        </p>
    </form>
</div>

<style>
    .required {
        color: red;
    }
    #tasa-payroll-form .form-table th {
        width: 250px;
    }
</style>
