<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">

    <style>

        body {
            font-family: DejaVu Sans;
            padding: 30px;
            color: #1e293b;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        .title {
            font-size: 28px;
            font-weight: bold;
        }

        .subtitle {
            color: #64748b;
            margin-top: 5px;
        }

        .section {
            margin-bottom: 25px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        table th,
        table td {
            border: 1px solid #cbd5e1;
            padding: 10px;
            text-align: left;
        }

        table th {
            background: #f1f5f9;
        }

        .amount {
            text-align: right;
        }

        .net {
            font-size: 22px;
            font-weight: bold;
            color: #16a34a;
            text-align: right;
            margin-top: 20px;
        }

    </style>
</head>

<body>

    <div class="header">

        <div class="title">
            HR Portal
        </div>

        <div class="subtitle">
            Employee Payslip
        </div>

    </div>

    <div class="section">

        <table>

            <tr>
                <th>Employee</th>
                <td>{{ $payroll->employee->first_name }} {{ $payroll->employee->last_name }}</td>
            </tr>

            <tr>
                <th>Department</th>
                <td>{{ $payroll->employee->department->name ?? '-' }}</td>
            </tr>

            <tr>
                <th>Month</th>
                <td>{{ $payroll->month }}/{{ $payroll->year }}</td>
            </tr>

            <tr>
                <th>Status</th>
                <td>{{ ucfirst($payroll->status) }}</td>
            </tr>

        </table>

    </div>

    <div class="section">

        <table>

            <thead>

                <tr>
                    <th>Description</th>
                    <th class="amount">Amount</th>
                </tr>

            </thead>

            <tbody>

                <tr>
                    <td>Gross Salary</td>
                    <td class="amount">
                        ₹{{ number_format($payroll->gross_salary) }}
                    </td>
                </tr>

                <tr>
                    <td>Total Deductions</td>
                    <td class="amount">
                        ₹{{ number_format($payroll->total_deductions) }}
                    </td>
                </tr>

                <tr>
                    <td>Working Days</td>
                    <td class="amount">
                        {{ $payroll->working_days }}
                    </td>
                </tr>

                <tr>
                    <td>Present Days</td>
                    <td class="amount">
                        {{ $payroll->present_days }}
                    </td>
                </tr>

                <tr>
                    <td>Leave Days</td>
                    <td class="amount">
                        {{ $payroll->leave_days }}
                    </td>
                </tr>

            </tbody>

        </table>

    </div>

    <div class="net">
        Net Salary: ₹{{ number_format($payroll->net_salary) }}
    </div>

</body>
</html>