<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        /* ✅ FIX 1: DejaVu Sans supports ₹ rupee symbol */
        body { font-family: DejaVu Sans, sans-serif; font-size: 13px; color: #333; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h2 { margin: 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #ccc; padding: 8px 12px; }
        th { background: #f5f5f5; text-align: left; }
        .total { font-weight: bold; background: #eef; }
    </style>
</head>
<body>
    <div class="header">
        <h2>{{ config('app.name') }}</h2>
        {{-- ✅ FIX 2: Show proper month name and year --}}
        <p>Payslip for {{ DateTime::createFromFormat('!m', $payroll->month)->format('F') }} {{ $payroll->year }}</p>
    </div>

    <table>
        {{-- ✅ FIX 3: Employee uses first_name + last_name, not ->name --}}
        <tr><th>Employee Name</th><td>{{ $employee->first_name }} {{ $employee->last_name }}</td></tr>
        <tr><th>Department</th><td>{{ $employee->department->name ?? '-' }}</td></tr>
        <tr><th>Designation</th><td>{{ $employee->designation->name ?? '-' }}</td></tr>
        <tr><th>Email</th><td>{{ $employee->email }}</td></tr>
    </table>

    <table style="margin-top: 20px;">
        <tr>
            <th>Earnings</th><th>Amount</th>
            <th>Deductions</th><th>Amount</th>
        </tr>
        <tr>
            {{-- ✅ FIX 4: Use total_deductions not deductions --}}
            <td>Gross Salary</td><td>&#8377;{{ number_format($payroll->gross_salary, 2) }}</td>
            <td>Total Deductions</td><td>&#8377;{{ number_format($payroll->total_deductions, 2) }}</td>
        </tr>
        <tr class="total">
            <td colspan="2">Net Salary</td>
            <td colspan="2">&#8377;{{ number_format($payroll->net_salary, 2) }}</td>
        </tr>
    </table>

    <p style="margin-top: 30px; font-size: 11px; color: #888;">
        This is a system-generated payslip. No signature required.
    </p>
</body>
</html>