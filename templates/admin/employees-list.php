<?php
/**
 * Employees List Template
 *
 * @package TASA_Payroll
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php _e('Employee Management', 'tasa-payroll'); ?></h1>

    <?php if (isset($_GET['updated'])) : ?>
        <div class="notice notice-success is-dismissible">
            <p><?php _e('Employee details saved successfully.', 'tasa-payroll'); ?></p>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['error']) && $_GET['error'] === 'invalid_user') : ?>
        <div class="notice notice-error is-dismissible">
            <p><?php _e('Invalid employee selected.', 'tasa-payroll'); ?></p>
        </div>
    <?php endif; ?>

    <form method="get" style="margin: 20px 0;">
        <input type="hidden" name="page" value="tasa-payroll-employees" />
        <p class="search-box">
            <input type="search" name="s" value="<?php echo esc_attr($search); ?>" placeholder="<?php _e('Search employees...', 'tasa-payroll'); ?>" />
            <input type="submit" class="button" value="<?php _e('Search', 'tasa-payroll'); ?>" />
        </p>
    </form>

    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th><?php _e('WP User ID', 'tasa-payroll'); ?></th>
                <th><?php _e('Employee', 'tasa-payroll'); ?></th>
                <th><?php _e('Employee ID', 'tasa-payroll'); ?></th>
                <th><?php _e('Base Salary', 'tasa-payroll'); ?></th>
                <th><?php _e('Phone Number', 'tasa-payroll'); ?></th>
                <th><?php _e('Actions', 'tasa-payroll'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($employees)) : ?>
                <?php foreach ($employees as $employee) : ?>
                    <?php
                    $display_employee_id = tasa_payroll_get_employee_display_id($employee->ID, (string) $employee->employee_id);
                    $formatted_phone = !empty($employee->phone_number) ? tasa_payroll_format_phone_number($employee->phone_number) : '-';
                    ?>
                    <tr>
                        <td><?php echo (int) $employee->ID; ?></td>
                        <td>
                            <?php echo esc_html($employee->display_name); ?>
                            <br><small><?php echo esc_html($employee->user_email); ?></small>
                        </td>
                        <td><?php echo esc_html($display_employee_id); ?></td>
                        <td>
                            <?php
                            if ($employee->base_salary !== null && (float) $employee->base_salary > 0) {
                                echo esc_html(number_format((float) $employee->base_salary, 2));
                            } else {
                                echo '-';
                            }
                            ?>
                        </td>
                        <td><?php echo esc_html($formatted_phone); ?></td>
                        <td>
                            <a href="<?php echo esc_url(admin_url('admin.php?page=tasa-payroll-employees&action=edit&user_id=' . (int) $employee->ID)); ?>" class="button button-small">
                                <?php _e('Edit', 'tasa-payroll'); ?>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr>
                    <td colspan="6" style="text-align: center; padding: 20px;">
                        <?php _e('No employees found.', 'tasa-payroll'); ?>
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
                    'current' => $paged,
                ));
                ?>
            </div>
        </div>
    <?php endif; ?>
</div>
