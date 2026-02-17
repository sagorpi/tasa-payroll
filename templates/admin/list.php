<?php
/**
 * Admin List Template
 *
 * @package TASA_Payroll
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php _e('Payroll Records', 'tasa-payroll'); ?></h1>
    <a href="<?php echo admin_url('admin.php?page=tasa-payroll-add'); ?>" class="page-title-action">
        <?php _e('Add New', 'tasa-payroll'); ?>
    </a>
    
    <?php if (isset($_GET['added'])) : ?>
        <div class="notice notice-success is-dismissible">
            <p><?php _e('Payroll record added successfully.', 'tasa-payroll'); ?></p>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_GET['updated'])) : ?>
        <div class="notice notice-success is-dismissible">
            <p><?php _e('Payroll record updated successfully.', 'tasa-payroll'); ?></p>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_GET['deleted'])) : ?>
        <div class="notice notice-success is-dismissible">
            <p><?php _e('Payroll record deleted successfully.', 'tasa-payroll'); ?></p>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_GET['error'])) : ?>
        <div class="notice notice-error is-dismissible">
            <p>
                <?php
                switch ($_GET['error']) {
                    case 'missing_fields':
                        _e('Please fill in all required fields.', 'tasa-payroll');
                        break;
                    case 'duplicate':
                        _e('A payroll record already exists for this user in the selected month/year.', 'tasa-payroll');
                        break;
                    case 'save_failed':
                        _e('Failed to save payroll record.', 'tasa-payroll');
                        break;
                    case 'delete_failed':
                        _e('Failed to delete payroll record.', 'tasa-payroll');
                        break;
                    default:
                        _e('An error occurred.', 'tasa-payroll');
                }
                ?>
            </p>
        </div>
    <?php endif; ?>
    
    <form method="get" style="margin: 20px 0;">
        <input type="hidden" name="page" value="tasa-payroll" />
        <p class="search-box">
            <input type="search" name="s" value="<?php echo esc_attr($search); ?>" placeholder="<?php _e('Search by employee name...', 'tasa-payroll'); ?>" />
            <input type="submit" class="button" value="<?php _e('Search', 'tasa-payroll'); ?>" />
        </p>
    </form>
    
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th><?php _e('ID', 'tasa-payroll'); ?></th>
                <th><?php _e('Employee', 'tasa-payroll'); ?></th>
                <th><?php _e('Employee ID', 'tasa-payroll'); ?></th>
                <th><?php _e('Month/Year', 'tasa-payroll'); ?></th>
                <th><?php _e('Working Days', 'tasa-payroll'); ?></th>
                <th><?php _e('Days Absent', 'tasa-payroll'); ?></th>
                <th><?php _e('Monthly Salary', 'tasa-payroll'); ?></th>
                <th><?php _e('Bonus', 'tasa-payroll'); ?></th>
                <th><?php _e('Income Tax', 'tasa-payroll'); ?></th>
                <th><?php _e('Provident Fund', 'tasa-payroll'); ?></th>
                <th><?php _e('Final Salary', 'tasa-payroll'); ?></th>
                <th><?php _e('Actions', 'tasa-payroll'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($payrolls)) : ?>
                <?php foreach ($payrolls as $payroll) : ?>
                    <?php
                    $user = get_userdata($payroll->user_id);
                    $detail = isset($employee_details[$payroll->user_id]) ? $employee_details[$payroll->user_id] : null;
                    $custom_employee_id = $detail && !empty($detail->employee_id) ? $detail->employee_id : '';
                    ?>
                    <tr>
                        <td><?php echo esc_html($payroll->id); ?></td>
                        <td>
                            <?php echo $user ? esc_html($user->display_name) : __('Unknown User', 'tasa-payroll'); ?>
                            <br><small><?php echo $user ? esc_html($user->user_email) : ''; ?></small>
                        </td>
                        <td><?php echo $user ? esc_html(tasa_payroll_get_employee_display_id($user->ID, $custom_employee_id)) : '-'; ?></td>
                        <td><?php echo esc_html(date('F Y', mktime(0, 0, 0, $payroll->month, 1, $payroll->year))); ?></td>
                        <td><?php echo esc_html($payroll->total_working_days); ?></td>
                        <td><?php echo esc_html($payroll->days_absent); ?></td>
                        <td><?php echo esc_html(number_format($payroll->monthly_salary, 2)); ?></td>
                        <td><?php echo esc_html(number_format($payroll->bonus, 2)); ?></td>
                        <td><?php echo esc_html(number_format($payroll->income_tax, 2)); ?></td>
                        <td><?php echo esc_html(number_format($payroll->provident_fund, 2)); ?></td>
                        <td><strong><?php echo esc_html(number_format($payroll->final_salary, 2)); ?></strong></td>
                        <td>
                            <a href="<?php echo admin_url('admin.php?page=tasa-payroll-add&id=' . $payroll->id); ?>" class="button button-small">
                                <?php _e('Edit', 'tasa-payroll'); ?>
                            </a>
                            <a href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=tasa_delete_payroll&id=' . $payroll->id), 'delete_payroll_' . $payroll->id); ?>" 
                               class="button button-small" 
                               onclick="return confirm('<?php _e('Are you sure you want to delete this record?', 'tasa-payroll'); ?>');">
                                <?php _e('Delete', 'tasa-payroll'); ?>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr>
                    <td colspan="12" style="text-align: center; padding: 20px;">
                        <?php _e('No payroll records found.', 'tasa-payroll'); ?>
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
    
    <?php if ($total_pages > 1) : ?>
        <div class="tablenav bottom">
            <div class="tablenav-pages">
                <?php
                echo paginate_links(array(
                    'base' => add_query_arg('paged', '%#%'),
                    'format' => '',
                    'prev_text' => __('&laquo;'),
                    'next_text' => __('&raquo;'),
                    'total' => $total_pages,
                    'current' => $paged
                ));
                ?>
            </div>
        </div>
    <?php endif; ?>
</div>
