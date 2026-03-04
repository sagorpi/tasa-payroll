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
        // Load Composer autoload if available.
        if (file_exists(TASA_PAYROLL_PLUGIN_DIR . 'vendor/autoload.php')) {
            require_once TASA_PAYROLL_PLUGIN_DIR . 'vendor/autoload.php';
        }
    }
    
    /**
     * Generate payslip PDF
     */
    public function generate_payslip($payroll, $args = array()) {
        // Check if any supported PDF engine is available.
        $has_mpdf = class_exists('\Mpdf\Mpdf');
        $has_tcpdf = class_exists('TCPDF');
        if (!$has_mpdf && !$has_tcpdf) {
            wp_die(__('No supported PDF library found. Install mPDF (composer require mpdf/mpdf) or TCPDF (composer require tecnickcom/tcpdf).', 'tasa-payroll'));
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

        $custom_css = isset($args['custom_css']) ? (string) $args['custom_css'] : '';
        $output_mode = isset($args['output_mode']) ? strtoupper((string) $args['output_mode']) : 'D';

        if (!in_array($output_mode, array('I', 'D'), true)) {
            $output_mode = 'D';
        }

        // Generate HTML content
        $html = $this->get_payslip_html($payroll, $user, $company_name, $company_address, $company_logo, $month_name, $days_present, $gross_earnings, $total_deductions, $employee_display_id, $employee_phone, $custom_css);

        // Output PDF
        $filename = 'payslip_' . $user->user_login . '_' . $month_name . '_' . $payroll->year . '.pdf';

        if ($has_mpdf) {
            // Create temporary folder for mPDF cache files.
            $upload_dir = wp_upload_dir();
            $temp_dir = trailingslashit($upload_dir['basedir']) . 'tasa-payroll-mpdf-temp';
            if (!file_exists($temp_dir)) {
                wp_mkdir_p($temp_dir);
            }

            $pdf = new \Mpdf\Mpdf(array(
                'mode' => 'utf-8',
                'format' => 'A4',
                'margin_left' => 15,
                'margin_right' => 15,
                'margin_top' => 15,
                'margin_bottom' => 15,
                'tempDir' => $temp_dir,
            ));

            $pdf->SetCreator('TASA Payroll');
            $pdf->SetAuthor($company_name);
            $pdf->SetTitle('Payslip - ' . $month_name . ' ' . $payroll->year);
            $pdf->SetSubject('Salary Slip');
            $pdf->WriteHTML($html);

            $destination = ('I' === $output_mode)
                ? \Mpdf\Output\Destination::INLINE
                : \Mpdf\Output\Destination::DOWNLOAD;
            $pdf->Output($filename, $destination);
            return;
        }

        // Fallback to TCPDF if mPDF is not yet installed.
        $pdf = new \TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetCreator('TASA Payroll');
        $pdf->SetAuthor($company_name);
        $pdf->SetTitle('Payslip - ' . $month_name . ' ' . $payroll->year);
        $pdf->SetSubject('Salary Slip');
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetAutoPageBreak(true, 15);
        $pdf->AddPage();
        $pdf->SetFont('helvetica', '', 10);
        $pdf->writeHTML($html, true, false, true, false, '');
        $pdf->Output($filename, ('I' === $output_mode) ? 'I' : 'D');
    }

    /**
     * Get default payslip CSS (used for PDF and live preview editor).
     */
    public function get_default_payslip_css() {
        return <<<'CSS'
            body { font-family: helvetica, sans-serif; color: #1f2937; font-size: 10px; background: #eff1f4; }
            .sheet { background: #eff1f4; padding: 8px; }
            .header-table { width: 100%; border-collapse: collapse; }
            .header-table td { vertical-align: middle; padding: 0; }
            .logo { max-width: 72px; max-height: 72px; display: block; margin: 0; }
            .company-name { font-size: 18px; font-weight: bold; color: #1f2937; margin: 0 0 2px 0; }
            .company-address { font-size: 8.8px; line-height: 1.45; color: #5b6470; }
            .month-title { text-align: right; }
            .month-title .label { font-size: 9px; color: #6a7380; }
            .month-title .value { font-size: 15px; font-weight: bold; color: #111827; margin-top: 2px; }
            .divider { border-top: 1px solid #bcc7d5; margin: 8px 0 10px 0; }

            .summary-wrapper { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
            .summary-wrapper td { vertical-align: top; }
            .block-title { font-size: 10px; font-weight: bold; color: #5b6470; letter-spacing: 0.3px; margin-bottom: 6px; text-transform: uppercase; }

            .employee-table { width: 100%; border-collapse: collapse; }
            .employee-table td { padding: 6px 2px; font-size: 9.8px; line-height: 1.4; }
            .employee-table .label { width: 48%; color: #5b6470; font-weight: bold; }
            .employee-table .sep { width: 4%; text-align: center; color: #5b6470; }
            .employee-table .value { width: 48%; color: #111827; font-weight: 600; }

            .net-card-table { width: 100%; border-collapse: separate; border-spacing: 0; border: 1px solid #b6c0cc; border-radius: 10px; background: #f2f4f6; }
            .net-card-head td { background: #d6e6db; border-bottom: 1px dashed #bcc7d5; padding: 12px 14px 11px; border-top-left-radius: 10px; border-top-right-radius: 10px; }
            .net-card-amount { font-size: 22px; font-weight: bold; color: #111827; }
            .net-card-label { font-size: 10px; color: #55725f; font-weight: bold; margin-top: 2px; }
            .net-card-meta-wrap { padding: 12px 14px; background: #f2f4f6; border-bottom-left-radius: 10px; border-bottom-right-radius: 10px; }
            .net-card-meta { width: 100%; border-collapse: collapse; }
            .net-card-meta td { padding: 5px 0; font-size: 9.8px; }
            .net-card-meta .k { color: #5b6470; font-weight: bold; width: 54%; }
            .net-card-meta .sep { width: 8%; text-align: center; color: #5b6470; }
            .net-card-meta .v { color: #111827; font-weight: bold; width: 38%; text-align: left; }

            .earnings-box { border: 1px solid #b9c4d1; margin-bottom: 10px; background: #f3f5f7; }
            .earnings-table { width: 100%; border-collapse: collapse; }
            .earnings-table th { padding: 9px 10px; font-size: 10px; text-align: left; color: #3f4650; border-bottom: 1px dashed #bcc7d5; background: #f3f5f7; }
            .earnings-table td { padding: 10px 10px; font-size: 10px; border-bottom: 1px solid #dfe4ea; background: #f7f8fa; }
            .earnings-table tr:last-child td { border-bottom: none; }
            .earnings-table .amount { text-align: right; font-weight: bold; color: #111827; }
            .earnings-table .total-row td { background: #e6e9ee; font-weight: bold; }

            .payable-table { width: 100%; border-collapse: collapse; border: 1px solid #b9c4d1; margin-bottom: 10px; background: #f6f7f9; }
            .payable-table td { padding: 10px 12px; }
            .payable-left .title { font-size: 12px; font-weight: bold; color: #111827; margin-bottom: 2px; }
            .payable-left .sub { font-size: 9.8px; color: #5b6470; }
            .payable-right { width: 30%; background: #d6e6db; text-align: right; font-size: 18px; font-weight: bold; color: #111827; }

            .amount-words { text-align: center; font-size: 10px; color: #5b6470; margin: 8px 0 10px 0; }
            .amount-words strong { color: #111827; }

            .footer-line { border-top: 1px solid #bcc7d5; margin-top: 8px; padding-top: 16px; text-align: center; font-size: 8.8px; color: #727b87; }
CSS;
    }

    /**
     * Get payslip HTML fragment for live preview page.
     */
    public function get_payslip_preview_fragment($payroll) {
        $user = get_userdata($payroll->user_id);
        if (!$user) {
            return '<p>User not found.</p>';
        }

        $settings = get_option('tasa_payroll_settings', array());
        $company_name = isset($settings['company_name']) ? $settings['company_name'] : get_bloginfo('name');
        $company_address = isset($settings['company_address']) ? $settings['company_address'] : '';
        $company_logo = isset($settings['company_logo']) ? $settings['company_logo'] : '';

        $month_name = date('F', mktime(0, 0, 0, $payroll->month, 1));
        $days_present = $payroll->total_working_days - $payroll->days_absent;
        $gross_earnings = ($days_present * $payroll->per_day_salary) + $payroll->bonus;
        $total_deductions = $payroll->income_tax + $payroll->provident_fund;

        $db = TASA_Payroll_Database::get_instance();
        $employee_detail = $db->get_employee_detail($payroll->user_id);
        $custom_employee_id = $employee_detail && !empty($employee_detail->employee_id) ? $employee_detail->employee_id : '';
        $employee_display_id = tasa_payroll_get_employee_display_id($payroll->user_id, $custom_employee_id);
        $employee_phone = $employee_detail && !empty($employee_detail->phone_number) ? tasa_payroll_format_phone_number($employee_detail->phone_number) : '';

        return $this->get_payslip_html(
            $payroll,
            $user,
            $company_name,
            $company_address,
            $company_logo,
            $month_name,
            $days_present,
            $gross_earnings,
            $total_deductions,
            $employee_display_id,
            $employee_phone
        );
    }
    
    /**
     * Get payslip HTML template
     */
    private function get_payslip_html($payroll, $user, $company_name, $company_address, $company_logo, $month_name, $days_present, $gross_earnings, $total_deductions, $employee_display_id, $employee_phone, $custom_css = '') {
        
        // Convert amount to words
        $amount_in_words = $this->convert_number_to_words($payroll->final_salary);
        $bonus_amount = isset($payroll->bonus) ? (float) $payroll->bonus : 0;
        
        ob_start();
        ?>
        <style>
            <?php echo ($custom_css !== '') ? $custom_css : $this->get_default_payslip_css(); ?>
        </style>

        <div class="sheet">
            <table class="header-table" cellpadding="0" cellspacing="0">
                <tr>
                    <td style="width: 74%;" valign="middle">
                        <table style="width: 100%; border-collapse: collapse;" cellpadding="0" cellspacing="0">
                            <tr>
                                <?php if ($company_logo) : ?>
                                    <td style="width: 78px; text-align: left; padding-left: 0;" valign="middle" align="left">
                                        <img src="<?php echo esc_url($company_logo); ?>" class="logo" align="left" />
                                    </td>
                                    <td style="padding-left: 10px;" valign="middle">
                                <?php else : ?>
                                    <td valign="middle">
                                <?php endif; ?>
                                        <div class="company-name"><?php echo esc_html($company_name); ?></div>
                                        <?php if (!empty($company_address)) : ?>
                                            <div class="company-address"><?php echo nl2br(esc_html($company_address)); ?></div>
                                        <?php endif; ?>
                                    </td>
                            </tr>
                        </table>
                    </td>
                    <td style="width: 26%;" class="month-title" valign="middle">
                        <div class="label">Payslip For the Month</div>
                        <div class="value"><?php echo esc_html($month_name . ' ' . $payroll->year); ?></div>
                    </td>
                </tr>
            </table>

            <div class="divider"></div>

            <table class="summary-wrapper" cellpadding="0" cellspacing="0">
                <tr>
                    <td style="width: 62%; padding-right: 12px;">
                        <div class="block-title">Employee Summary</div>
                        <table class="employee-table" cellpadding="0" cellspacing="0">
                            <tr>
                                <td class="label">Employee Name</td>
                                <td class="sep">:</td>
                                <td class="value"><?php echo esc_html($user->display_name); ?></td>
                            </tr>
                            <tr>
                                <td class="label">Employee ID</td>
                                <td class="sep">:</td>
                                <td class="value"><?php echo esc_html($employee_display_id); ?></td>
                            </tr>
                            <tr>
                                <td class="label">Pay Period</td>
                                <td class="sep">:</td>
                                <td class="value"><?php echo esc_html($month_name . ' ' . $payroll->year); ?></td>
                            </tr>
                            <tr>
                                <td class="label">Pay Date</td>
                                <td class="sep">:</td>
                                <td class="value"><?php echo esc_html(date_i18n('d/m/Y')); ?></td>
                            </tr>
                            <?php if (!empty($employee_phone)) : ?>
                                <tr>
                                    <td class="label">Phone Number</td>
                                    <td class="sep">:</td>
                                    <td class="value"><?php echo esc_html($employee_phone); ?></td>
                                </tr>
                            <?php endif; ?>
                        </table>
                    </td>
                    <td style="width: 38%;">
                        <table class="net-card-table" cellpadding="0" cellspacing="0">
                            <tr class="net-card-head">
                                <td>
                                    <div class="net-card-amount">Rs.<?php echo number_format($payroll->final_salary, 2); ?></div>
                                    <div class="net-card-label">Total Net Pay</div>
                                </td>
                            </tr>
                            <tr>
                                <td class="net-card-meta-wrap">
                                    <table class="net-card-meta" cellpadding="0" cellspacing="0">
                                        <tr>
                                            <td class="k">Paid Days</td>
                                            <td class="sep">:</td>
                                            <td class="v"><?php echo esc_html($days_present); ?></td>
                                        </tr>
                                        <tr>
                                            <td class="k">LOP Days</td>
                                            <td class="sep">:</td>
                                            <td class="v"><?php echo esc_html($payroll->days_absent); ?></td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>

            <div class="earnings-box">
                <table class="earnings-table" cellpadding="0" cellspacing="0">
                    <tr>
                        <th style="width: 38%;">EARNINGS</th>
                        <th style="width: 12%;" class="amount">AMOUNT</th>
                        <th style="width: 38%;">DEDUCTIONS</th>
                        <th style="width: 12%;" class="amount">AMOUNT</th>
                    </tr>
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
                    <?php if ($bonus_amount > 0) : ?>
                        <tr>
                            <td>Bonus</td>
                            <td class="amount">Rs.<?php echo number_format($bonus_amount, 2); ?></td>
                            <td></td>
                            <td class="amount"></td>
                        </tr>
                    <?php endif; ?>
                    <tr class="total-row">
                        <td>Gross Earnings</td>
                        <td class="amount">Rs.<?php echo number_format($gross_earnings, 2); ?></td>
                        <td>Total Deductions</td>
                        <td class="amount">Rs.<?php echo number_format($total_deductions, 2); ?></td>
                    </tr>
                </table>
            </div>

            <table class="payable-table" cellpadding="0" cellspacing="0">
                <tr>
                    <td class="payable-left">
                        <div class="title">TOTAL NET PAYABLE</div>
                        <div class="sub">Gross Earnings - Total Deductions</div>
                    </td>
                    <td class="payable-right">Rs.<?php echo number_format($payroll->final_salary, 2); ?></td>
                </tr>
            </table>

            <div class="amount-words">
                Amount In Words : <strong><?php echo esc_html($amount_in_words); ?></strong>
            </div>

            <div class="footer-line">-- This is a system-generated document. --</div>
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
