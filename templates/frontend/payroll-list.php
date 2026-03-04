<?php
/**
 * Frontend Payroll List Template
 *
 * @package TASA_Payroll
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

$current_user = wp_get_current_user();
?>

<div class="tasa-payroll-wrapper">
    <div class="tasa-payroll-header">
        <h2><?php _e('My Payroll Records', 'tasa-payroll'); ?></h2>
        <p><?php printf(__('Welcome, %s', 'tasa-payroll'), esc_html($current_user->display_name)); ?></p>
        <p>
            <?php _e('Employee ID:', 'tasa-payroll'); ?>
            <strong><?php echo esc_html($employee_display_id); ?></strong>
            <?php if (!empty($employee_phone)) : ?>
                | <?php _e('Phone:', 'tasa-payroll'); ?> <strong><?php echo esc_html($employee_phone); ?></strong>
            <?php endif; ?>
        </p>
    </div>
    
    <div class="tasa-payroll-filters">
        <form method="get" class="tasa-filter-form">
            <?php
            // Preserve other query parameters
            foreach ($_GET as $key => $value) {
                if ($key !== 'filter_month' && $key !== 'filter_year') {
                    echo '<input type="hidden" name="' . esc_attr($key) . '" value="' . esc_attr($value) . '" />';
                }
            }
            ?>
            
            <div class="filter-group">
                <label for="filter_month"><?php _e('Month:', 'tasa-payroll'); ?></label>
                <select name="filter_month" id="filter_month">
                    <option value=""><?php _e('All Months', 'tasa-payroll'); ?></option>
                    <?php for ($m = 1; $m <= 12; $m++) : ?>
                        <option value="<?php echo $m; ?>" <?php selected($filter_month, $m); ?>>
                            <?php echo date('F', mktime(0, 0, 0, $m, 1)); ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>
            
            <div class="filter-group">
                <label for="filter_year"><?php _e('Year:', 'tasa-payroll'); ?></label>
                <select name="filter_year" id="filter_year">
                    <option value=""><?php _e('All Years', 'tasa-payroll'); ?></option>
                    <?php foreach ($available_years as $year) : ?>
                        <option value="<?php echo $year; ?>" <?php selected($filter_year, $year); ?>>
                            <?php echo $year; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <button type="submit" class="tasa-btn tasa-btn-primary">
                <?php _e('Filter', 'tasa-payroll'); ?>
            </button>
            
            <?php if ($filter_month || $filter_year) : ?>
                <a href="<?php echo esc_url(remove_query_arg(array('filter_month', 'filter_year'))); ?>" class="tasa-btn tasa-btn-secondary">
                    <?php _e('Clear Filters', 'tasa-payroll'); ?>
                </a>
            <?php endif; ?>
        </form>
    </div>
    
    <?php if (!empty($payrolls)) : ?>
        <div class="tasa-payroll-list">
            <?php foreach ($payrolls as $payroll) : ?>
                <?php
                $month_name = date('F', mktime(0, 0, 0, $payroll->month, 1));
                $days_present = $payroll->total_working_days - $payroll->days_absent;
                $bonus_amount = isset($payroll->bonus) ? (float) $payroll->bonus : 0;
                $gross_earnings = ($days_present * $payroll->per_day_salary) + $bonus_amount;
                $total_deductions = $payroll->income_tax + $payroll->provident_fund;
                ?>
                
                <div class="tasa-payroll-card">
                    <div class="payroll-card-header">
                        <h3><?php echo esc_html($month_name . ' ' . $payroll->year); ?></h3>
                        <div class="payroll-card-actions">
                            <a href="<?php echo wp_nonce_url(admin_url('admin-ajax.php?action=tasa_download_payslip&payroll_id=' . $payroll->id), 'tasa_payroll_frontend_nonce', 'nonce'); ?>" 
                               class="tasa-btn tasa-btn-download" 
                               target="_blank">
                                <span class="dashicons dashicons-download"></span>
                                <?php _e('Download PDF', 'tasa-payroll'); ?>
                            </a>
                        </div>
                    </div>
                    
                    <div class="payroll-card-body">
                        <div class="payroll-summary">
                            <div class="summary-item summary-highlight">
                                <span class="summary-label"><?php _e('Total Net Pay', 'tasa-payroll'); ?></span>
                                <span class="summary-value">₹<?php echo number_format($payroll->final_salary, 2); ?></span>
                            </div>
                        </div>
                        
                        <div class="payroll-details">
                            <div class="details-section">
                                <h4><?php _e('Attendance', 'tasa-payroll'); ?></h4>
                                <div class="detail-row">
                                    <span><?php _e('Total Working Days:', 'tasa-payroll'); ?></span>
                                    <span><?php echo esc_html($payroll->total_working_days); ?></span>
                                </div>
                                <div class="detail-row">
                                    <span><?php _e('Days Present:', 'tasa-payroll'); ?></span>
                                    <span><?php echo esc_html($days_present); ?></span>
                                </div>
                                <div class="detail-row">
                                    <span><?php _e('Days Absent:', 'tasa-payroll'); ?></span>
                                    <span><?php echo esc_html($payroll->days_absent); ?></span>
                                </div>
                            </div>
                            
                            <div class="details-section">
                                <h4><?php _e('Earnings', 'tasa-payroll'); ?></h4>
                                <div class="detail-row">
                                    <span><?php _e('Basic Salary:', 'tasa-payroll'); ?></span>
                                    <span>₹<?php echo number_format($payroll->monthly_salary, 2); ?></span>
                                </div>
                                <div class="detail-row">
                                    <span><?php _e('Per Day Salary:', 'tasa-payroll'); ?></span>
                                    <span>₹<?php echo number_format($payroll->per_day_salary, 2); ?></span>
                                </div>
                                <?php if ($bonus_amount > 0) : ?>
                                    <div class="detail-row">
                                        <span><?php _e('Bonus:', 'tasa-payroll'); ?></span>
                                        <span>₹<?php echo number_format($bonus_amount, 2); ?></span>
                                    </div>
                                <?php endif; ?>
                                <div class="detail-row detail-total">
                                    <span><?php _e('Gross Earnings:', 'tasa-payroll'); ?></span>
                                    <span>₹<?php echo number_format($gross_earnings, 2); ?></span>
                                </div>
                            </div>
                            
                            <div class="details-section">
                                <h4><?php _e('Deductions', 'tasa-payroll'); ?></h4>
                                <div class="detail-row">
                                    <span><?php _e('Income Tax:', 'tasa-payroll'); ?></span>
                                    <span>₹<?php echo number_format($payroll->income_tax, 2); ?></span>
                                </div>
                                <div class="detail-row">
                                    <span><?php _e('Provident Fund:', 'tasa-payroll'); ?></span>
                                    <span>₹<?php echo number_format($payroll->provident_fund, 2); ?></span>
                                </div>
                                <div class="detail-row detail-total">
                                    <span><?php _e('Total Deductions:', 'tasa-payroll'); ?></span>
                                    <span>₹<?php echo number_format($total_deductions, 2); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else : ?>
        <div class="tasa-payroll-empty">
            <p><?php _e('No payroll records found.', 'tasa-payroll'); ?></p>
        </div>
    <?php endif; ?>
</div>
