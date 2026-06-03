<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Salary Revision Letter</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 13px;
            color: #1e293b;
            line-height: 1.6;
            padding: 30px;
        }
        .header {
            border-bottom: 2px solid #3b82f6;
            padding-bottom: 15px;
            margin-bottom: 30px;
        }
        .header table {
            width: 100%;
            border: none;
        }
        .header td {
            border: none;
            padding: 0;
        }
        .company-name {
            font-size: 22px;
            font-weight: bold;
            color: #1e3a8a;
        }
        .letter-title {
            font-size: 18px;
            font-weight: bold;
            text-align: center;
            margin-bottom: 25px;
            text-transform: uppercase;
            color: #1e3a8a;
            letter-spacing: 0.5px;
        }
        .date {
            margin-bottom: 20px;
            font-weight: 500;
        }
        .employee-details {
            margin-bottom: 30px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 15px;
        }
        .employee-details table {
            width: 100%;
            border-collapse: collapse;
        }
        .employee-details td {
            border: none;
            padding: 4px 8px;
        }
        .employee-details td.label {
            font-weight: bold;
            color: #64748b;
            width: 150px;
        }
        .comparison-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 25px;
            margin-bottom: 25px;
        }
        .comparison-table th, 
        .comparison-table td {
            border: 1px solid #cbd5e1;
            padding: 10px 12px;
            text-align: left;
        }
        .comparison-table th {
            background: #f1f5f9;
            font-weight: bold;
        }
        .comparison-table td.amount {
            text-align: right;
        }
        .total-row {
            font-weight: bold;
            background: #eff6ff;
        }
        .total-row td {
            border-top: 2px solid #3b82f6;
            border-bottom: 2px solid #3b82f6;
        }
        .sign-off {
            margin-top: 40px;
        }
        .footer {
            margin-top: 50px;
            text-align: center;
            font-size: 11px;
            color: #94a3b8;
            border-top: 1px solid #e2e8f0;
            padding-top: 15px;
        }
    </style>
</head>
<body>
    <div class="header">
        <table>
            <tr>
                <td>
                    <div class="company-name">{{ $company_name }}</div>
                    <div style="font-size: 11px; color: #64748b;">Human Resources Division</div>
                </td>
                <td style="text-align: right;">
                    @if($company_logo)
                        <img src="{{ $company_logo }}" style="max-height: 50px; max-width: 180px; object-contain;" alt="Logo" />
                    @endif
                </td>
            </tr>
        </table>
    </div>

    <div class="date">
        Date: {{ \Carbon\Carbon::now()->format('F d, Y') }}
    </div>

    <div class="employee-details">
        <table>
            <tr>
                <td class="label">Employee Name:</td>
                <td>{{ $employee->first_name }} {{ $employee->last_name }}</td>
            </tr>
            <tr>
                <td class="label">Employee Code:</td>
                <td>{{ $employee->employee_code }}</td>
            </tr>
            <tr>
                <td class="label">Department:</td>
                <td>{{ $employee->department->name ?? '-' }}</td>
            </tr>
            <tr>
                <td class="label">Designation:</td>
                <td>{{ $employee->designation->name ?? '-' }}</td>
            </tr>
        </table>
    </div>

    <div class="letter-title">
        Letter of Salary Revision
    </div>

    <p>Dear {{ $employee->first_name }},</p>

    <p>
        We are pleased to inform you that your compensation has been revised effective from 
        <strong>{{ \Carbon\Carbon::parse($revision->effective_date)->format('F d, Y') }}</strong>. 
        This revision is in recognition of your valuable contributions, commitment, and performance towards the organization.
    </p>

    <p>
        The details of your updated salary structure, along with a comparison against your previous structure, are provided in the table below:
    </p>

    <table class="comparison-table">
        <thead>
            <tr>
                <th>Salary Component</th>
                <th style="text-align: right;">Old Salary</th>
                <th style="text-align: right;">Revised Salary</th>
                <th style="text-align: right;">Increase (%)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Basic Salary</td>
                <td class="amount">₹{{ number_format($revision->old_basic_salary, 2) }}</td>
                <td class="amount">₹{{ number_format($revision->new_basic_salary, 2) }}</td>
                <td class="amount">
                    @if($revision->old_basic_salary > 0)
                        {{ round((($revision->new_basic_salary - $revision->old_basic_salary) / $revision->old_basic_salary) * 100, 1) }}%
                    @else
                        -
                    @endif
                </td>
            </tr>
            <tr>
                <td>HRA</td>
                <td class="amount">₹{{ number_format($revision->old_hra, 2) }}</td>
                <td class="amount">₹{{ number_format($revision->new_hra, 2) }}</td>
                <td class="amount">
                    @if($revision->old_hra > 0)
                        {{ round((($revision->new_hra - $revision->old_hra) / $revision->old_hra) * 100, 1) }}%
                    @else
                        -
                    @endif
                </td>
            </tr>
            <tr>
                <td>Allowances</td>
                <td class="amount">₹{{ number_format($revision->old_allowances, 2) }}</td>
                <td class="amount">₹{{ number_format($revision->new_allowances, 2) }}</td>
                <td class="amount">
                    @if($revision->old_allowances > 0)
                        {{ round((($revision->new_allowances - $revision->old_allowances) / $revision->old_allowances) * 100, 1) }}%
                    @else
                        -
                    @endif
                </td>
            </tr>
            <tr>
                <td>Bonus</td>
                <td class="amount">₹{{ number_format($revision->old_bonus, 2) }}</td>
                <td class="amount">₹{{ number_format($revision->new_bonus, 2) }}</td>
                <td class="amount">
                    @if($revision->old_bonus > 0)
                        {{ round((($revision->new_bonus - $revision->old_bonus) / $revision->old_bonus) * 100, 1) }}%
                    @else
                        -
                    @endif
                </td>
            </tr>
            <tr class="total-row">
                <td>Gross Salary (CTC)</td>
                <td class="amount">₹{{ number_format($revision->old_gross_salary, 2) }}</td>
                <td class="amount">₹{{ number_format($revision->new_gross_salary, 2) }}</td>
                <td class="amount">{{ $revision->increment_percentage }}%</td>
            </tr>
            <tr style="color: #64748b; font-size: 12px;">
                <td>Net Salary (Take-Home Approx)</td>
                <td class="amount">₹{{ number_format($revision->old_net_salary, 2) }}</td>
                <td class="amount">₹{{ number_format($revision->new_net_salary, 2) }}</td>
                <td class="amount">
                    @if($revision->old_net_salary > 0)
                        {{ round((($revision->new_net_salary - $revision->old_net_salary) / $revision->old_net_salary) * 100, 1) }}%
                    @else
                        -
                    @endif
                </td>
            </tr>
        </tbody>
    </table>

    <p>
        Note: The revised structures will apply to your monthly payout cycles from the effective date. All other terms and conditions of your employment contract remain unchanged.
    </p>

    <p>
        We thank you for your efforts and look forward to your continued contribution to the success of our company.
    </p>

    <div class="sign-off">
        <p>Yours sincerely,</p>
        <div style="font-weight: bold; color: #1e3a8a; margin-top: 40px;">Management Board</div>
        <p style="font-size: 12px; color: #64748b; margin-top: 5px;">{{ $company_name }}</p>
    </div>

    <div class="footer">
        This is a system-generated document. No physical signature is required.
    </div>
</body>
</html>
