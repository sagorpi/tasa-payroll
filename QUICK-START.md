# TASA Payroll - Quick Start Guide

## 🚀 Get Started in 5 Minutes

### Step 1: Activate the Plugin (1 minute)

1. Go to WordPress Admin → **Plugins** → **Installed Plugins**
2. Find "TASA Payroll Management"
3. Click **Activate**

✅ The plugin is now active! The database table has been created automatically.

### Step 2: Configure Company Settings (2 minutes)

1. Go to **Payroll** → **Settings**
2. Enter your company details:
   ```
   Company Name: Tasa Elegance
   Company Address: 319/1, Jessore Road, Nagerbazar, Dumdum, 
                    Near Diamond Plaza Kolkata, 700074 India
   ```
3. Click **Upload Logo** and select your company logo
4. Click **Save Settings**

✅ Your company branding is now configured!

### Step 3: Add Your First Payroll Entry (2 minutes)

1. Go to **Payroll** → **Add New**
2. Fill in the form:
   - **Employee**: Select an employee
   - **Month**: September
   - **Year**: 2025
   - **Days Absent**: 1
   - **Monthly Salary**: 6766.00
   - **Bonus**: 430.00
   - **Income Tax**: 0.00
   - **Provident Fund**: 0.00

3. Watch the auto-calculations:
   - Total Working Days: 30 (auto-filled)
   - Per Day Salary: 225.53 (auto-calculated)
   - Final Salary: 7,196.00 (auto-calculated)

4. Click **Add Payroll**

✅ Your first payroll entry is created!

### Step 4: Create Employee Portal Page (30 seconds)

1. Go to **Pages** → **Add New**
2. Title: "My Payroll"
3. Add this shortcode in the content:
   ```
   [tasa_payroll]
   ```
4. Click **Publish**
5. Copy the page URL

✅ Employees can now access their payroll at this URL!

### Step 5: Test PDF Download (30 seconds)

1. Log in as the employee (or stay as admin)
2. Visit the "My Payroll" page
3. Click **Download PDF** on any payroll record
4. Check the downloaded PDF

✅ PDF salary slip is working!

---

## 📋 Common Tasks

### Add Monthly Payroll for All Employees

1. Go to **Payroll** → **Add New**
2. For each employee:
   - Select employee
   - Select current month/year
   - Enter attendance and salary details
   - Click **Add Payroll**

### Edit a Payroll Entry

1. Go to **Payroll** → **All Records**
2. Find the record
3. Click **Edit**
4. Make changes
5. Click **Update Payroll**

### Search for Employee Records

1. Go to **Payroll** → **All Records**
2. Use the search box
3. Type employee name or email
4. Click **Search**

### View Employee Payroll (as Admin)

1. Go to **Payroll** → **All Records**
2. View all employee records
3. Or search for specific employee

### View Your Payroll (as Employee)

1. Visit the "My Payroll" page
2. Use filters to find specific months
3. Download PDF salary slips

---

## 🎯 Key Features at a Glance

| Feature | Location | Description |
|---------|----------|-------------|
| Add Payroll | Payroll → Add New | Create new payroll entries |
| View All | Payroll → All Records | See all payroll records |
| Settings | Payroll → Settings | Configure company details |
| Employee Portal | [tasa_payroll] shortcode | Frontend view for employees |
| PDF Download | Employee Portal | Generate salary slips |

---

## 💡 Pro Tips

### Auto-Calculations
- Total working days auto-fills when you select month/year
- Per day salary calculates as you type monthly salary
- Final salary updates automatically with all changes

### Preventing Duplicates
- System prevents duplicate entries for same employee/month/year
- Edit existing records instead of creating new ones

### PDF Customization
- Upload your logo in Settings for branded salary slips
- Logo appears on all PDF downloads

### Employee Access
- Employees only see their own records
- Admins see all records
- No special configuration needed

---

## 🔧 Troubleshooting

### PDF Not Downloading?
- TCPDF library is already installed ✅
- Check browser pop-up blocker
- Try different browser

### Calculations Not Working?
- Clear browser cache
- Refresh the page
- Check JavaScript is enabled

### Can't See Payroll Menu?
- Make sure you're logged in as Administrator
- Check plugin is activated

---

## 📞 Need Help?

Refer to these detailed guides:
- **INSTALLATION.md** - Complete installation instructions
- **FEATURES.md** - Full feature documentation
- **README.md** - General plugin information

---

## ✨ Example Payroll Entry

Here's a complete example matching your PDF sample:

```
Employee: Sonali Das
Month: September
Year: 2025
Total Working Days: 30 (auto)
Days Absent: 1
Monthly Salary: 6,766.00
Bonus: 430.00
Income Tax: 0.00
Provident Fund: 0.00

Auto-Calculated:
Per Day Salary: 225.53
Days Present: 29
Final Salary: 7,196.00
```

This will generate a PDF exactly like your sample!

---

**🎉 You're all set! Start managing payroll efficiently with TASA Payroll Management Plugin.**

