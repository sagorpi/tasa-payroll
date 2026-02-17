<?php
/**
 * PDF Generator Class
 *
 * @package TASA_Payroll
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * TASA Payroll PDF Generator Class
 */
class TASA_Payroll_PDF_Generator {
    
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
        // Load TCPDF library if available
        if (!class_exists('TCPDF')) {
            // Try composer autoload
            if (file_exists(TASA_PAYROLL_PLUGIN_DIR . 'vendor/autoload.php')) {
                require_once TASA_PAYROLL_PLUGIN_DIR . 'vendor/autoload.php';
            }
            // Try manual installation
            elseif (file_exists(TASA_PAYROLL_PLUGIN_DIR . 'lib/tcpdf/tcpdf.php')) {
                require_once TASA_PAYROLL_PLUGIN_DIR . 'lib/tcpdf/tcpdf.php';
            }
        }
    }
    
    /**
     * Generate payslip PDF
     */
    public function generate_payslip($payroll) {
        // Check if TCPDF is available
        if (!class_exists('TCPDF')) {
            wp_die(__('TCPDF library is not installed. Please install it using Composer: composer require tecnickcom/tcpdf', 'tasa-payroll'));
        }

        // Get user data
        $user = get_userdata($payroll->user_id);
        if (!$user) {
            wp_die(__('User not found.', 'tasa-payroll'));
        }

        // Get company settings
        $settings = get_option('tasa_payroll_settings', array());
        $company_name = isset($settings['company_name']) ? $settings['company_name'] : get_bloginfo('name');
        $company_address = isset($settings['company_address']) ? $settings['company_address'] : '';
        $company_logo = isset($settings['company_logo']) ? $settings['company_logo'] : '';

        // Calculate values
        $month_name = date('F', mktime(0, 0, 0, $payroll->month, 1));
        $days_present = $payroll->total_working_days - $payroll->days_absent;
        $gross_earnings = ($days_present * $payroll->per_day_salary) + $payroll->bonus;
        $total_deductions = $payroll->income_tax + $payroll->provident_fund;

        $db = TASA_Payroll_Database::get_instance();
        $employee_detail = $db->get_employee_detail($payroll->user_id);
        $custom_employee_id = $employee_detail && !empty($employee_detail->employee_id) ? $employee_detail->employee_id : '';
        $employee_display_id = tasa_payroll_get_employee_display_id($payroll->user_id, $custom_employee_id);
        $employee_phone = $employee_detail && !empty($employee_detail->phone_number) ? tasa_payroll_format_phone_number($employee_detail->phone_number) : '';

        // Generate HTML content
        $html = $this->get_payslip_html($payroll, $user, $company_name, $company_address, $company_logo, $month_name, $days_present, $gross_earnings, $total_deductions, $employee_display_id, $employee_phone);

        // Create PDF
        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);

        // Set document information
        $pdf->SetCreator('TASA Payroll');
        $pdf->SetAuthor($company_name);
        $pdf->SetTitle('Payslip - ' . $month_name . ' ' . $payroll->year);
        $pdf->SetSubject('Salary Slip');

        // Remove default header/footer
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);

        // Set margins
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetAutoPageBreak(true, 15);

        // Add a page
        $pdf->AddPage();

        // Set font
        $pdf->SetFont('helvetica', '', 10);

        // Output HTML content
        $pdf->writeHTML($html, true, false, true, false, '');

        // Output PDF
        $filename = 'payslip_' . $user->user_login . '_' . $month_name . '_' . $payroll->year . '.pdf';
        $pdf->Output($filename, 'D');
    }
    
    /**
     * Get payslip HTML template
     */
    private function get_payslip_html($payroll, $user, $company_name, $company_address, $company_logo, $month_name, $days_present, $gross_earnings, $total_deductions, $employee_display_id, $employee_phone) {
        
        // Convert amount to words
        $amount_in_words = $this->convert_number_to_words($payroll->final_salary);
        
        ob_start();
        ?>
        <style>
            body { font-family: helvetica, sans-serif; }
            .header { margin-bottom: 20px; }
            .company-info { float: left; width: 60%; }
            .payslip-title { float: right; width: 35%; text-align: right; }
            .clear { clear: both; }
            .company-logo { max-width: 80px; margin-bottom: 10px; }
            .company-name { font-size: 18px; font-weight: bold; margin-bottom: 5px; }
            .company-address { font-size: 10px; color: #666; line-height: 1.4; }
            .payslip-month { font-size: 12px; font-weight: bold; }
            .payslip-date { font-size: 16px; font-weight: bold; color: #333; }
            .divider { border-top: 2px solid #333; margin: 15px 0; }
            .section-title { font-size: 12px; font-weight: bold; margin: 15px 0 10px 0; background: #f0f0f0; padding: 8px; }
            .info-table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
            .info-table td { padding: 6px 8px; font-size: 10px; }
            .info-table .label { font-weight: bold; width: 40%; }
            .salary-table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
            .salary-table th { background: #f0f0f0; padding: 8px; text-align: left; font-size: 11px; font-weight: bold; border: 1px solid #ddd; }
            .salary-table td { padding: 8px; font-size: 10px; border: 1px solid #ddd; }
            .salary-table .amount { text-align: right; }
            .salary-table .total-row { font-weight: bold; background: #f9f9f9; }
            .net-pay-box { background: #e8f5e9; border: 2px solid #4caf50; padding: 15px; text-align: center; margin: 20px 0; }
            .net-pay-label { font-size: 12px; font-weight: bold; color: #333; }
            .net-pay-amount { font-size: 20px; font-weight: bold; color: #2e7d32; margin: 5px 0; }
            .amount-words { font-size: 10px; font-style: italic; color: #666; margin-top: 10px; }
            .footer { text-align: center; font-size: 9px; color: #999; margin-top: 30px; border-top: 1px solid #ddd; padding-top: 10px; }
        </style>
        
        <div class="header">
            <div class="company-info">
                <?php if ($company_logo) : ?>
                    <img src="<?php echo esc_url($company_logo); ?>" class="company-logo" />
                <?php endif; ?>
                <div class="company-name"><?php echo esc_html($company_name); ?></div>
                <div class="company-address"><?php echo nl2br(esc_html($company_address)); ?></div>
            </div>
            <div class="payslip-title">
                <div class="payslip-month">Payslip For the Month</div>
                <div class="payslip-date"><?php echo esc_html($month_name . ' ' . $payroll->year); ?></div>
            </div>
            <div class="clear"></div>
        </div>
        
        <div class="divider"></div>
        
        <div class="section-title">EMPLOYEE SUMMARY</div>
        <table class="info-table">
            <tr>
                <td class="label">Employee Name</td>
                <td>: <?php echo esc_html($user->display_name); ?></td>
                <td class="label">Employee ID</td>
                <td>: <?php echo esc_html($employee_display_id); ?></td>
            </tr>
            <tr>
                <td class="label">Pay Period</td>
                <td>: <?php echo esc_html($month_name . ' ' . $payroll->year); ?></td>
                <td class="label">Pay Date</td>
                <td>: <?php echo date('d/m/Y'); ?></td>
            </tr>
            <?php if (!empty($employee_phone)) : ?>
            <tr>
                <td class="label">Phone Number</td>
                <td>: <?php echo esc_html($employee_phone); ?></td>
                <td class="label"></td>
                <td></td>
            </tr>
            <?php endif; ?>
        </table>

        <div class="net-pay-box">
            <div class="net-pay-label">Total Net Pay</div>
            <div class="net-pay-amount">Rs.<?php echo number_format($payroll->final_salary, 2); ?></div>
            <table class="info-table" style="margin-top: 15px;">
                <tr>
                    <td class="label">Paid Days</td>
                    <td>: <?php echo esc_html($days_present); ?></td>
                    <td class="label">LOP Days</td>
                    <td>: <?php echo esc_html($payroll->days_absent); ?></td>
                </tr>
            </table>
        </div>

        <table class="salary-table">
            <thead>
                <tr>
                    <th style="width: 50%;">EARNINGS</th>
                    <th style="width: 50%;" class="amount">AMOUNT</th>
                    <th style="width: 50%;">DEDUCTIONS</th>
                    <th style="width: 50%;" class="amount">AMOUNT</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Basic</td>
                    <td class="amount">Rs.<?php echo number_format($payroll->monthly_salary, 2); ?></td>
                    <td>Income Tax</td>
                    <td class="amount">Rs.<?php echo number_format($payroll->income_tax, 2); ?></td>
                </tr>
                <tr>
                    <td>House Rent Allowance</td>
                    <td class="amount">Rs.0.00</td>
                    <td>Provident Fund</td>
                    <td class="amount">Rs.<?php echo number_format($payroll->provident_fund, 2); ?></td>
                </tr>
                <tr>
                    <td>Bonus (Quantitative Goal)</td>
                    <td class="amount">Rs.<?php echo number_format($payroll->bonus, 2); ?></td>
                    <td></td>
                    <td class="amount"></td>
                </tr>
                <tr class="total-row">
                    <td>Gross Earnings</td>
                    <td class="amount">Rs.<?php echo number_format($gross_earnings, 2); ?></td>
                    <td>Total Deductions</td>
                    <td class="amount">Rs.<?php echo number_format($total_deductions, 2); ?></td>
                </tr>
            </tbody>
        </table>

        <div class="net-pay-box" style="background: #e8f5e9;">
            <div class="net-pay-label">TOTAL NET PAYABLE</div>
            <div class="net-pay-amount">Rs.<?php echo number_format($payroll->final_salary, 2); ?></div>
            <div style="font-size: 10px; margin-top: 5px; color: #666;">Gross Earnings - Total Deductions</div>
        </div>

        <div class="amount-words">
            <strong>Amount In Words :</strong> <?php echo esc_html($amount_in_words); ?>
        </div>

        <div class="footer">
            -- This is a system-generated document. --
        </div>
        <?php

        return ob_get_clean();
    }

    /**
     * Convert number to words (Indian Rupees)
     */
    private function convert_number_to_words($number) {
        $number = number_format($number, 2, '.', '');
        $amount_parts = explode('.', $number);
        $rupees = (int) $amount_parts[0];
        $paise = isset($amount_parts[1]) ? (int) $amount_parts[1] : 0;

        $words = '';

        if ($rupees > 0) {
            $words .= $this->number_to_words($rupees) . ' Rupee' . ($rupees > 1 ? 's' : '');
        }

        if ($paise > 0) {
            if ($rupees > 0) {
                $words .= ' and ';
            }
            $words .= $this->number_to_words($paise) . ' Paise';
        }

        if (empty($words)) {
            $words = 'Zero Rupees';
        }

        return ucfirst($words) . ' Only';
    }

    /**
     * Helper function to convert number to words
     */
    private function number_to_words($number) {
        $ones = array(
            0 => '', 1 => 'One', 2 => 'Two', 3 => 'Three', 4 => 'Four', 5 => 'Five',
            6 => 'Six', 7 => 'Seven', 8 => 'Eight', 9 => 'Nine', 10 => 'Ten',
            11 => 'Eleven', 12 => 'Twelve', 13 => 'Thirteen', 14 => 'Fourteen', 15 => 'Fifteen',
            16 => 'Sixteen', 17 => 'Seventeen', 18 => 'Eighteen', 19 => 'Nineteen'
        );

        $tens = array(
            0 => '', 2 => 'Twenty', 3 => 'Thirty', 4 => 'Forty', 5 => 'Fifty',
            6 => 'Sixty', 7 => 'Seventy', 8 => 'Eighty', 9 => 'Ninety'
        );

        $hundreds = array('', 'Thousand', 'Lakh', 'Crore');

        if ($number == 0) {
            return 'Zero';
        }

        $words = '';
        $level = 0;

        while ($number > 0) {
            $chunk = 0;

            if ($level == 0) {
                $chunk = $number % 1000;
                $number = floor($number / 1000);
            } else {
                $chunk = $number % 100;
                $number = floor($number / 100);
            }

            if ($chunk > 0) {
                $chunk_words = '';

                if ($level == 0) {
                    // Handle hundreds
                    $h = floor($chunk / 100);
                    if ($h > 0) {
                        $chunk_words .= $ones[$h] . ' Hundred ';
                    }
                    $chunk = $chunk % 100;
                }

                // Handle tens and ones
                if ($chunk < 20) {
                    $chunk_words .= $ones[$chunk];
                } else {
                    $t = floor($chunk / 10);
                    $o = $chunk % 10;
                    $chunk_words .= $tens[$t] . ' ' . $ones[$o];
                }

                $chunk_words = trim($chunk_words);

                if (!empty($chunk_words)) {
                    if ($level > 0) {
                        $chunk_words .= ' ' . $hundreds[$level];
                    }
                    $words = $chunk_words . ' ' . $words;
                }
            }

            $level++;
        }

        return trim($words);
    }
}
