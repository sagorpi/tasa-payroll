# TASA Payroll Management Plugin - Features Overview

## Complete Feature List

### 1. Admin Dashboard & Management

#### Payroll Records Management
- **Add New Payroll**: Dedicated interface to create payroll entries
- **View All Records**: Comprehensive list view with pagination
- **Edit Records**: Modify existing payroll entries
- **Delete Records**: Remove payroll entries with confirmation
- **Search Functionality**: Search employees by name or email
- **Duplicate Prevention**: System prevents duplicate entries for same user/month/year

#### Admin Menu Structure
```
Payroll (Main Menu)
├── All Records
├── Add New
└── Settings
```

### 2. Payroll Entry Fields

#### Employee Information
- **Employee Selection**: Dropdown of all WordPress users
- **Employee ID**: Auto-generated from WordPress user ID

#### Time Period
- **Month Selector**: Dropdown with all 12 months
- **Year Selector**: Range from 5 years ago to next year
- **Total Working Days**: Auto-calculated based on selected month/year

#### Attendance
- **Days Absent**: Supports decimal values (e.g., 0.5 for half day)
- **Days Present**: Auto-calculated (Total Days - Absent)
- **LOP Days**: Loss of Pay days (same as absent days)

#### Earnings
- **Monthly Salary (Basic)**: Base salary amount
- **Per Day Salary**: Auto-calculated (Monthly Salary ÷ Total Working Days)
- **Bonus**: Additional earnings (Quantitative Goal bonus)
- **House Rent Allowance**: Placeholder for future use (currently Rs.0.00)

#### Deductions
- **Income Tax**: Tax deduction amount
- **Provident Fund**: PF deduction amount
- **Total Deductions**: Auto-calculated sum

#### Final Calculation
- **Gross Earnings**: (Days Present × Per Day Salary) + Bonus
- **Final Salary (Net Pay)**: Gross Earnings - Total Deductions

### 3. Auto-Calculations

The plugin automatically calculates:

1. **Total Working Days**
   - Calculated when month/year is selected
   - Uses actual days in the selected month

2. **Per Day Salary**
   - Formula: Monthly Salary ÷ Total Working Days
   - Updates in real-time as you type

3. **Final Salary**
   - Formula: (Working Days - Absent) × Per Day Salary + Bonus - Income Tax - Provident Fund
   - Updates automatically when any field changes

### 4. Employee Portal (Frontend)

#### Shortcode: `[tasa_payroll]`

Features:
- **Login Required**: Only logged-in users can access
- **Personal Records Only**: Users see only their own payroll data
- **Month/Year Filters**: Filter records by specific month or year
- **Clear Filters**: Reset filters to view all records
- **Responsive Design**: Works on desktop, tablet, and mobile

#### Display Information
- Employee name and welcome message
- Payroll cards with:
  - Month and year
  - Total net pay (highlighted)
  - Paid days and LOP days
  - Attendance details
  - Earnings breakdown
  - Deductions breakdown
  - Gross earnings and total deductions

### 5. PDF Salary Slip Generation

#### Design Features (Based on Tasa Elegance Template)
- **Professional Layout**: Clean, modern design
- **Company Branding**: Logo, name, and address
- **Color-Coded Sections**: Visual hierarchy with colors
- **Responsive Tables**: Earnings and deductions side-by-side

#### PDF Contents
1. **Header Section**
   - Company logo (left)
   - Company name and address (left)
   - "Payslip For the Month" title (right)
   - Month and year (right)

2. **Employee Summary**
   - Employee Name
   - Employee ID (4-digit padded)
   - Pay Period
   - Pay Date (generation date)

3. **Net Pay Highlight Box**
   - Total Net Pay (large, prominent)
   - Paid Days
   - LOP Days

4. **Earnings & Deductions Table**
   - Side-by-side comparison
   - Basic salary
   - House Rent Allowance
   - Bonus (Quantitative Goal)
   - Income Tax
   - Provident Fund
   - Gross Earnings total
   - Total Deductions total

5. **Final Net Payable**
   - Highlighted box with net amount
   - Formula explanation

6. **Amount in Words**
   - Indian Rupees format
   - Example: "Indian Rupee Seven Thousand One Hundred Ninety-Six Only"

7. **Footer**
   - "This is a system-generated document"

### 6. Company Settings

#### Configuration Options
- **Company Name**: Displayed on PDF salary slips
- **Company Address**: Full address for salary slips
- **Company Logo**: Upload and manage logo image

#### Logo Management
- Upload button with media library integration
- Preview uploaded logo
- Remove logo option
- Supports all standard image formats

### 7. Security Features

#### Access Control
- **Admin Only**: Only users with 'manage_options' capability can:
  - Add payroll records
  - Edit payroll records
  - Delete payroll records
  - Access settings
- **User Restrictions**: Regular users can only:
  - View their own payroll records
  - Download their own salary slips

#### Data Protection
- **Nonce Verification**: All forms use WordPress nonces
- **CSRF Protection**: Prevents cross-site request forgery
- **Data Sanitization**: All inputs are sanitized before saving
- **Data Validation**: Server-side validation of all fields
- **SQL Injection Prevention**: Uses prepared statements

### 8. Database Structure

#### Custom Table: `wp_tasa_payroll`
- Optimized for payroll data
- Indexed for fast queries
- Unique constraint on user_id + month + year
- Timestamps for audit trail

#### Fields
- id (Primary Key)
- user_id (Foreign Key to WordPress users)
- month (1-12)
- year (4-digit)
- total_working_days
- days_absent (decimal)
- monthly_salary (decimal)
- per_day_salary (decimal)
- bonus (decimal)
- income_tax (decimal)
- provident_fund (decimal)
- final_salary (decimal)
- created_at (timestamp)
- updated_at (timestamp)
- created_by (admin user ID)

### 9. User Experience Features

#### Real-Time Calculations
- JavaScript-powered auto-calculations
- No page refresh needed
- Instant feedback on changes

#### Form Validation
- Required field indicators (red asterisk)
- Client-side validation before submit
- Server-side validation for security
- User-friendly error messages

#### Responsive Design
- Mobile-friendly admin interface
- Touch-friendly buttons and inputs
- Adaptive layouts for all screen sizes

#### Search & Filter
- Admin: Search by employee name/email
- Frontend: Filter by month and year
- Pagination for large datasets

### 10. Technical Specifications

#### Requirements
- WordPress 5.0+
- PHP 7.4+
- MySQL 5.6+
- TCPDF library (installed via Composer)

#### Technologies Used
- PHP (WordPress standards)
- MySQL (Custom tables)
- JavaScript/jQuery (Auto-calculations)
- CSS3 (Responsive design)
- TCPDF (PDF generation)
- HTML5 (Templates)

#### Performance
- Efficient database queries
- Indexed tables for fast lookups
- Minimal JavaScript footprint
- Optimized CSS delivery

### 11. Future Enhancement Possibilities

- Export to Excel/CSV
- Bulk payroll import
- Email salary slips automatically
- Multiple allowances (HRA, DA, etc.)
- Tax calculation automation
- Payroll reports and analytics
- Employee self-service portal
- Leave management integration
- Attendance system integration
- Multi-currency support

