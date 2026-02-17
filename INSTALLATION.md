# TASA Payroll Plugin - Installation Guide

## Prerequisites

- WordPress 5.0 or higher
- PHP 7.4 or higher
- Composer (for installing dependencies)

## Step-by-Step Installation

### 1. Install the Plugin

The plugin is already in your WordPress plugins directory at:
```
/wp-content/plugins/tasa-payroll/
```

### 2. Install TCPDF Library

Navigate to the plugin directory and install dependencies using Composer:

```bash
cd /Users/sagorchowdhuri/Local\ Sites/tasa/app/public/wp-content/plugins/tasa-payroll
composer install
```

This will install the TCPDF library required for PDF generation.

### 3. Activate the Plugin

1. Log in to your WordPress admin panel
2. Go to **Plugins** > **Installed Plugins**
3. Find "TASA Payroll Management"
4. Click **Activate**

### 4. Configure Company Settings

1. In WordPress admin, go to **Payroll** > **Settings**
2. Configure the following:
   - **Company Name**: Enter your company name (e.g., "Tasa Elegance")
   - **Company Address**: Enter your full address (e.g., "319/1, Jessore Road, Nagerbazar, Dumdum, Near Diamond Plaza Kolkata, 700074 India")
   - **Company Logo**: Upload your company logo
3. Click **Save Settings**

### 5. Create Employee Payroll Page

1. Go to **Pages** > **Add New**
2. Create a new page (e.g., "My Payroll")
3. Add the shortcode: `[tasa_payroll]`
4. Publish the page
5. Share this page URL with employees

## Usage

### For Administrators

#### Adding Payroll Records

1. Go to **Payroll** > **Add New**
2. Fill in the form:
   - Select **Employee** from dropdown
   - Select **Month** and **Year**
   - **Total Working Days** will auto-calculate based on the month
   - Enter **Days Absent** (if any)
   - Enter **Monthly Salary** (base salary)
   - Enter **Bonus** (if any)
   - Enter **Income Tax** deduction
   - Enter **Provident Fund** deduction
3. The system will auto-calculate:
   - Per Day Salary = Monthly Salary ÷ Total Working Days
   - Final Salary = (Working Days - Absent) × Per Day Salary + Bonus - Income Tax - Provident Fund
4. Click **Add Payroll**

#### Viewing All Records

1. Go to **Payroll** > **All Records**
2. View all payroll entries
3. Use the search box to find specific employees
4. Click **Edit** to modify a record
5. Click **Delete** to remove a record

### For Employees

1. Navigate to the payroll page (created in step 5 above)
2. Log in to your WordPress account
3. View your payroll history
4. Use filters to view specific months/years
5. Click **Download PDF** to get your salary slip

## Features

### Auto-Calculations

The plugin automatically calculates:
- Total working days based on selected month/year
- Per day salary (Monthly Salary ÷ Total Working Days)
- Final net salary (Gross Earnings - Deductions)

### PDF Salary Slip

The PDF includes:
- Company logo and details (from settings)
- Employee information
- Pay period and date
- Attendance summary (Paid Days, LOP Days)
- Earnings breakdown (Basic, Bonus)
- Deductions breakdown (Income Tax, Provident Fund)
- Gross earnings and total deductions
- Net payable amount
- Amount in words (Indian Rupees format)

### Security Features

- Only administrators can add/edit/delete payroll records
- Employees can only view their own payroll data
- All forms use WordPress nonces for CSRF protection
- Data is sanitized and validated before saving
- Unique constraint on user/month/year (prevents duplicates)

## Troubleshooting

### TCPDF Not Found Error

If you see "TCPDF library is not installed" when downloading PDF:

1. Make sure you ran `composer install` in the plugin directory
2. Check that the `vendor` folder exists in the plugin directory
3. Verify that `vendor/tecnickcom/tcpdf` exists

### PDF Not Downloading

1. Check browser console for JavaScript errors
2. Verify the user has permission to view the payroll record
3. Check WordPress debug log for PHP errors

### Calculations Not Working

1. Clear browser cache
2. Check that JavaScript is enabled
3. Verify that the admin.js file is loaded (check browser developer tools)

## Database

The plugin creates a custom table: `wp_tasa_payroll`

Table structure:
- id (primary key)
- user_id (WordPress user ID)
- month (1-12)
- year
- total_working_days
- days_absent
- monthly_salary
- per_day_salary
- bonus
- income_tax
- provident_fund
- final_salary
- created_at
- updated_at
- created_by

## Uninstallation

To completely remove the plugin:

1. Deactivate the plugin
2. Delete the plugin files
3. Manually drop the database table if needed:
   ```sql
   DROP TABLE wp_tasa_payroll;
   ```

## Support

For issues or questions, please contact your system administrator.

