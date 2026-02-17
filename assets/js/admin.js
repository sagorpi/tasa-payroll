jQuery(document).ready(function($) {

    function applyEmployeeMeta(baseSalary, employeeId) {
        var isEditMode = $('input[name="payroll_id"]').length > 0;
        var parsedBase = parseFloat(baseSalary) || 0;
        var normalizedEmployeeId = employeeId || '-';

        $('#selected_employee_id').text(normalizedEmployeeId);

        if (!isEditMode) {
            if (parsedBase > 0) {
                $('#monthly_salary').val(parsedBase.toFixed(2));
            } else {
                $('#monthly_salary').val('');
            }
        }
    }

    function fetchEmployeeMeta(userId) {
        // Apply option metadata instantly, then refresh from DB via AJAX.
        updateSelectedEmployeeMeta();

        if (!userId) {
            applyEmployeeMeta(0, '-');
            calculateSalaries();
            return;
        }

        $.post(
            tasaPayroll.ajaxurl,
            {
                action: 'tasa_get_employee_detail',
                nonce: tasaPayroll.nonce,
                user_id: userId
            }
        ).done(function(response) {
            if (response && response.success && response.data) {
                applyEmployeeMeta(response.data.base_salary, response.data.employee_id);
            } else {
                updateSelectedEmployeeMeta();
            }
            calculateSalaries();
        }).fail(function() {
            updateSelectedEmployeeMeta();
            calculateSalaries();
        });
    }

    function updateSelectedEmployeeMeta() {
        var selectedOption = $('#user_id option:selected');
        if (!selectedOption.length) {
            return;
        }

        var employeeId = selectedOption.data('employee-id') || '-';
        var baseSalary = selectedOption.data('base-salary');
        applyEmployeeMeta(baseSalary, employeeId);
    }
    
    /**
     * Calculate total working days based on month and year
     */
    function calculateWorkingDays() {
        var month = parseInt($('#month').val());
        var year = parseInt($('#year').val());
        
        if (month && year) {
            // Get number of days in month
            var daysInMonth = new Date(year, month, 0).getDate();
            $('#total_working_days').val(daysInMonth);
            
            // Trigger calculation of per day salary
            calculateSalaries();
        }
    }
    
    /**
     * Calculate per day salary and final salary
     */
    function calculateSalaries() {
        var totalWorkingDays = parseFloat($('#total_working_days').val()) || 0;
        var daysAbsent = parseFloat($('#days_absent').val()) || 0;
        var monthlySalary = parseFloat($('#monthly_salary').val()) || 0;
        var bonus = parseFloat($('#bonus').val()) || 0;
        var incomeTax = parseFloat($('#income_tax').val()) || 0;
        var providentFund = parseFloat($('#provident_fund').val()) || 0;
        
        if (totalWorkingDays > 0 && monthlySalary > 0) {
            // Calculate per day salary
            var perDaySalary = monthlySalary / totalWorkingDays;
            $('#per_day_salary').val(perDaySalary.toFixed(2));
            
            // Calculate final salary
            var daysPresent = totalWorkingDays - daysAbsent;
            var finalSalary = (daysPresent * perDaySalary) + bonus - incomeTax - providentFund;
            $('#final_salary').val(finalSalary.toFixed(2));
        } else {
            $('#per_day_salary').val('0.00');
            $('#final_salary').val('0.00');
        }
    }
    
    // Event listeners for auto-calculation
    $('#month, #year').on('change', function() {
        calculateWorkingDays();
    });

    $('#user_id').on('change', function() {
        fetchEmployeeMeta($(this).val());
    });
    
    $('#total_working_days, #days_absent, #monthly_salary, #bonus, #income_tax, #provident_fund').on('input change', function() {
        calculateSalaries();
    });
    
    // Initial calculation on page load
    if ($('#month').val() && $('#year').val()) {
        calculateWorkingDays();
    } else {
        calculateSalaries();
    }

    fetchEmployeeMeta($('#user_id').val());

    // Normalize phone number while typing in employee edit form.
    $('#phone_number').on('input', function() {
        var raw = $(this).val();
        var normalized = raw.replace(/[^\d+\-\s()]/g, '');
        $(this).val(normalized);
    });
    
    /**
     * Form validation
     */
    $('#tasa-payroll-form').on('submit', function(e) {
        var userId = $('#user_id').val();
        var month = $('#month').val();
        var year = $('#year').val();
        var totalWorkingDays = $('#total_working_days').val();
        var monthlySalary = $('#monthly_salary').val();
        
        if (!userId || !month || !year || !totalWorkingDays || !monthlySalary) {
            e.preventDefault();
            alert('Please fill in all required fields.');
            return false;
        }
        
        var daysAbsent = parseFloat($('#days_absent').val()) || 0;
        var totalDays = parseFloat(totalWorkingDays);
        
        if (daysAbsent > totalDays) {
            e.preventDefault();
            alert('Days absent cannot be greater than total working days.');
            return false;
        }

        var baseSalary = parseFloat($('#base_salary').val()) || 0;
        if ($('#base_salary').length && baseSalary <= 0) {
            e.preventDefault();
            alert('Base salary must be a positive number.');
            return false;
        }
        
        return true;
    });
});
