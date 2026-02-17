# TASA Payroll Management Plugin

A comprehensive WordPress payroll management system with admin management, employee portal, and PDF salary slips.

## Features

### Admin Features
- Custom admin interface to add and manage payroll records
- Add payroll entries with:
  - User/Employee selection (dropdown of WordPress users)
  - Month and Year selector
  - Total working days (auto-calculated based on selected month)
  - Number of days absent
  - Monthly salary (base amount)
  - Per day salary (auto-calculated)
  - Bonus amount
  - Income Tax deduction
  - Provident Fund deduction
  - Final salary (auto-calculated)
- View all payroll records with search and pagination
- Edit and delete payroll records

### Employee/User Features
- Frontend interface via shortcode `[tasa_payroll]`
- View personal payroll history
- Filter by month/year
- Download salary slip as PDF

### PDF Salary Slip
- Professional salary slip design
- Company logo, name, and address (configurable)
- Employee details and ID
- Attendance summary (working days, present, absent)
- Earnings breakdown (Basic, Bonus)
- Deductions breakdown (Income Tax, Provident Fund)
- Gross earnings and net pay
- Amount in words (Indian Rupees)
- System-generated timestamp

## Installation

1. Upload the `tasa-payroll` folder to `/wp-content/plugins/`
2. Install TCPDF library (see below)
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Configure company settings under Payroll > Settings
5. Add the shortcode `[tasa_payroll]` to any page for employee access

## Installing TCPDF Library

This plugin requires TCPDF for PDF generation. Install it using Composer:

```bash
cd wp-content/plugins/tasa-payroll
composer require tecnickcom/tcpdf
```

Or manually download TCPDF from https://github.com/tecnickcom/TCPDF and extract it to:
```
wp-content/plugins/tasa-payroll/lib/tcpdf/
```

## Usage

### For Administrators

1. Go to **Payroll > Add New** in WordPress admin
2. Select employee, month, and year
3. Enter salary details (monthly salary, bonus, deductions)
4. The system auto-calculates:
   - Total working days based on month
   - Per day salary
   - Final net salary
5. Click "Add Payroll" to save

### For Employees

1. Navigate to the page with `[tasa_payroll]` shortcode
2. View your payroll history
3. Filter by month/year
4. Click "Download PDF" to get salary slip

## Configuration

### Company Settings

Go to **Payroll > Settings** to configure:
- **Company Name**: Your organization name
- **Company Address**: Full address for salary slips
- **Company Logo**: Upload logo (appears on PDF)

## Shortcode

Use `[tasa_payroll]` to display the employee payroll interface on any page or post.

## Security

- Only administrators can add/edit/delete payroll records
- Employees can only view their own payroll data
- All forms use WordPress nonces for CSRF protection
- Data is sanitized and validated before saving

## Support

For issues or questions, please contact your system administrator.

## Changelog

### Version 1.0.0
- Initial release
- Admin payroll management
- Employee portal
- PDF salary slip generation
- Company settings configuration

