<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Payslip</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #000;
            line-height: 1.4;
            padding: 10px;
        }
        
        .header-table {
            width: 100%;
            border-collapse: collapse;
            border: none;
            margin-bottom: 5px;
        }
        
        .header-table td {
            border: none;
            padding: 0;
        }
        
        .logo-cell {
            width: 15%;
            vertical-align: middle;
        }
        
        .logo {
            max-height: 45px;
            max-width: 120px;
        }
        
        .company-details-cell {
            width: 85%;
            text-align: center;
            vertical-align: middle;
        }
        
        .company-name {
            font-size: 16px;
            font-weight: bold;
            margin: 0 0 3px 0;
            color: #000;
        }
        
        .company-address {
            font-size: 9px;
            color: #333;
            margin: 0;
            line-height: 1.2;
        }
        
        .payslip-title {
            text-align: center;
            font-size: 13px;
            font-weight: bold;
            margin: 15px 0 10px 0;
            text-transform: capitalize;
        }
        
        .employee-details-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #777;
            margin-bottom: 20px;
        }
        
        .employee-details-table td {
            padding: 5px 8px;
            vertical-align: top;
            font-size: 11px;
        }
        
        .employee-details-table .col-left {
            width: 50%;
            border-right: 1px solid #777;
        }
        
        .employee-details-table .col-right {
            width: 50%;
        }
        
        .info-row {
            margin-bottom: 3px;
            clear: both;
        }
        
        .info-label {
            float: left;
            width: 35%;
            font-weight: normal;
        }
        
        .info-value {
            float: left;
            width: 65%;
        }
        
        .salary-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #777;
            margin-bottom: 20px;
        }
        
        .salary-table th {
            border: 1px solid #777;
            padding: 5px 8px;
            font-weight: bold;
            text-align: left;
            background-color: #fff;
        }
        
        .salary-table td {
            border: 1px solid #777;
            padding: 4px 8px;
            font-size: 10px;
            vertical-align: middle;
        }
        
        .salary-table .separator {
            width: 1px;
            padding: 0;
            background-color: #777;
        }
        
        .amount {
            text-align: right;
        }
        
        .net-pay-section {
            margin-top: 15px;
            font-size: 11px;
        }
        
        .net-pay-amount {
            font-size: 11px;
            font-weight: bold;
        }
        
        .net-pay-words {
            font-size: 11px;
            font-weight: bold;
            font-style: italic;
            margin-top: 5px;
        }
        
        .footer-line {
            border-top: 1px solid #777;
            margin-top: 25px;
            padding-top: 5px;
            text-align: center;
            font-size: 9px;
            color: #777;
        }
    </style>
</head>
<body>

    @php
        $companyName = \App\Models\CompanySetting::where('key', 'company_name')->value('value') ?? 'Techsprout AI Labs';
        
        // Resolve company logo path locally
        $logoUrl = \App\Models\CompanySetting::where('key', 'company_logo')->value('value');
        $logoPath = null;
        if ($logoUrl) {
            $parsed = parse_url($logoUrl);
            $path = $parsed['path'] ?? '';
            if ($path) {
                $resolvedPath = public_path(substr($path, 1));
                if (file_exists($resolvedPath)) {
                    $logoPath = $resolvedPath;
                }
            }
        }
        
        // Address fallback
        $companyAddress = "8-2-293/82/A/787/1/4F/1, Road No36,4thFloor,JubileeHills,Hyderabad, Shaikpet, Telangana, India, 500033";
        
        // Month translation
        $monthName = DateTime::createFromFormat('!m', $payroll->month)->format('F');
        
        // Employee bank details
        $bankName = $employee->bank_name ?? 'State Bank of India';
        $bankAcc = $employee->bank_account_number ?? 'xxxxxxxxxx';
        $maskedAcc = strlen($bankAcc) > 4 
            ? str_repeat('x', strlen($bankAcc) - 4) . substr($bankAcc, -4) 
            : $bankAcc;
            
        $panNum = $employee->pan_number ?? 'xxxxxxxxxx';
        $maskedPan = strlen($panNum) > 4 
            ? str_repeat('x', strlen($panNum) - 4) . substr($panNum, -4) 
            : $panNum;
            
        // Master structure rates
        $structure = $payroll->salaryStructure;
        $masterBasic = $structure->basic_salary ?? 0;
        $masterHra = $structure->hra ?? 0;
        $masterAllowances = $structure->allowances ?? 0;
        $masterBonus = $structure->bonus ?? 0;
        $masterGross = $structure->gross_salary ?? 0;

        // Actual earnings details
        $actualBasic = $payroll->items->where('type', 'earning')->where('name', 'Basic Salary')->first()?->amount 
            ?? $payroll->items->where('type', 'earning')->filter(fn($i) => str_contains(strtolower($i->name), 'basic'))->first()?->amount
            ?? $payroll->basic_salary ?? 0;
        
        $actualHra = $payroll->items->where('type', 'earning')->where('name', 'HRA')->first()?->amount
            ?? $payroll->items->where('type', 'earning')->filter(fn($i) => str_contains(strtolower($i->name), 'hra'))->first()?->amount
            ?? 0;
            
        $actualAllowances = $payroll->items->where('type', 'earning')->where('name', 'Allowances')->first()?->amount
            ?? $payroll->items->where('type', 'earning')->filter(fn($i) => str_contains(strtolower($i->name), 'allowance'))->first()?->amount
            ?? 0;

        $actualBonus = $payroll->items->where('type', 'earning')->where('name', 'Bonus')->first()?->amount
            ?? $payroll->items->where('type', 'earning')->filter(fn($i) => str_contains(strtolower($i->name), 'bonus'))->first()?->amount
            ?? 0;
            
        // Earning rows
        $leftRows = [
            ['label' => 'Basic', 'master' => $masterBasic, 'actual' => $actualBasic],
            ['label' => 'HRA', 'master' => $masterHra, 'actual' => $actualHra],
            ['label' => 'Special Allowance', 'master' => $masterAllowances, 'actual' => $actualAllowances],
        ];
        
        if ($masterBonus > 0 || $actualBonus > 0) {
            $leftRows[] = ['label' => 'Bonus', 'master' => $masterBonus, 'actual' => $actualBonus];
        }
        
        // Dynamic earnings
        $knownEarningNames = ['basic salary', 'basic', 'hra', 'allowances', 'special allowance', 'bonus'];
        foreach ($payroll->items->where('type', 'earning') as $item) {
            $lowerName = strtolower($item->name);
            $isKnown = false;
            foreach ($knownEarningNames as $kn) {
                if (str_contains($lowerName, $kn)) {
                    $isKnown = true;
                    break;
                }
            }
            if (!$isKnown) {
                $leftRows[] = ['label' => $item->name, 'master' => 0, 'actual' => $item->amount];
            }
        }

        // Deduction rows
        $rightRows = [];
        foreach ($payroll->items->where('type', 'deduction') as $item) {
            $label = $item->name;
            if (str_contains(strtolower($label), 'prof') || str_contains(strtolower($label), 'professional')) {
                $label = 'Prof Tax';
            }
            $rightRows[] = ['label' => $label, 'actual' => $item->amount];
        }
        
        if (empty($rightRows)) {
            $rightRows[] = ['label' => 'Prof Tax', 'actual' => 0];
        }

        // Row balancing
        $maxRows = max(count($leftRows), count($rightRows));
        while (count($leftRows) < $maxRows) {
            $leftRows[] = ['label' => '', 'master' => null, 'actual' => null];
        }
        while (count($rightRows) < $maxRows) {
            $rightRows[] = ['label' => '', 'actual' => null];
        }
        
        // Helper number to words converter
        if (!function_exists('numberToWords')) {
            function numberToWords($number) {
                $amount = (int) $number;
                if ($amount == 0) {
                    return "Rupees Zero Only";
                }
                
                $thousands = array('', 'Thousand', 'Lakh', 'Crore');
                $ones = array(
                    '', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine', 'Ten',
                    'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen'
                );
                $tens = array('', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety');
                
                $words = [];
                
                $hundreds = $amount % 1000;
                $amount = (int) ($amount / 1000);
                
                $segments = [];
                $segments[] = $hundreds; // 0: hundreds
                
                while ($amount > 0) {
                    $segments[] = $amount % 100;
                    $amount = (int) ($amount / 100);
                }
                
                $numSegments = count($segments);
                for ($i = $numSegments - 1; $i >= 0; $i--) {
                    $val = $segments[$i];
                    if ($val == 0) {
                        continue;
                    }
                    
                    $segmentWords = "";
                    if ($i == 0) {
                        $h = (int) ($val / 100);
                        $tens_ones = $val % 100;
                        
                        if ($h > 0) {
                            $segmentWords .= $ones[$h] . " Hundred ";
                        }
                        if ($tens_ones > 0) {
                            if ($h > 0) {
                                $segmentWords .= "and ";
                            }
                            if ($tens_ones < 20) {
                                $segmentWords .= $ones[$tens_ones] . " ";
                            } else {
                                $t = (int) ($val % 100 / 10);
                                $o = $val % 10;
                                $segmentWords .= $tens[$t] . " " . $ones[$o] . " ";
                            }
                        }
                    } else {
                        if ($val < 20) {
                            $segmentWords .= $ones[$val] . " ";
                        } else {
                            $t = (int) ($val / 10);
                            $o = $val % 10;
                            $segmentWords .= $tens[$t] . " " . $ones[$o] . " ";
                        }
                        $segmentWords .= $thousands[$i] . " ";
                    }
                    
                    $words[] = trim($segmentWords);
                }
                
                return "Rupees " . implode(' ', $words) . " Only";
            }
        }
        
        $netPayWords = numberToWords($payroll->net_salary);
    @endphp

    <!-- Company Branding Header Table -->
    <table class="header-table">
        <tr>
            <td class="logo-cell">
                @if($logoPath)
                    <img src="{{ $logoPath }}" alt="Logo" class="logo" />
                @else
                    <span style="font-size:24px; font-weight:bold; color:#1e3a8a;">TS</span>
                @endif
            </td>
            <td class="company-details-cell">
                <h1 class="company-name">{{ $companyName }}</h1>
                <p class="company-address">{{ $companyAddress }}</p>
            </td>
        </tr>
    </table>

    <div class="payslip-title">
        Payslip for the month of {{ $monthName }} {{ $payroll->year }}
    </div>

    <!-- Employee Details Matrix -->
    <table class="employee-details-table">
        <tr>
            <td class="col-left">
                <div class="info-row">
                    <span class="info-label">Name:</span>
                    <span class="info-value">{{ $employee->first_name }} {{ $employee->last_name }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Joining Date:</span>
                    <span class="info-value">{{ $employee->joining_date ? $employee->joining_date->format('d M Y') : '-' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Designation:</span>
                    <span class="info-value">{{ $employee->designation->title ?? '-' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Department:</span>
                    <span class="info-value">{{ $employee->department->name ?? '-' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Location:</span>
                    <span class="info-value">{{ $employee->city ?: 'Hyderabad' }}</span>
                </div>
            </td>
            <td class="col-right">
                <div class="info-row">
                    <span class="info-label">Employee ID:</span>
                    <span class="info-value">{{ $employee->employee_code }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Bank Name:</span>
                    <span class="info-value">{{ $bankName }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Bank Account No:</span>
                    <span class="info-value">{{ $maskedAcc }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">PAN Number:</span>
                    <span class="info-value">{{ $maskedPan }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Effective Work Days:</span>
                    <span class="info-value">{{ $payroll->present_days }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">LOP:</span>
                    <span class="info-value">{{ $payroll->lop_days ?? 0 }}</span>
                </div>
            </td>
        </tr>
    </table>

    <!-- Salary Breakdown Table -->
    <table class="salary-table">
        <thead>
            <tr>
                <th style="width: 35%;">Earnings</th>
                <th style="width: 15%; text-align: right;">Master</th>
                <th style="width: 15%; text-align: right;">Actual</th>
                <th style="width: 20%;">Deductions</th>
                <th style="width: 15%; text-align: right;">Actual</th>
            </tr>
        </thead>
        <tbody>
            @for ($i = 0; $i < $maxRows; $i++)
                @php
                    $left = $leftRows[$i];
                    $right = $rightRows[$i];
                @endphp
                <tr>
                    <!-- Earnings side -->
                    <td>{{ $left['label'] }}</td>
                    <td class="amount">{{ $left['master'] !== null ? number_format($left['master'], 2) : '' }}</td>
                    <td class="amount">{{ $left['actual'] !== null ? number_format($left['actual'], 2) : '' }}</td>
                    
                    <!-- Deductions side -->
                    <td>{{ $right['label'] }}</td>
                    <td class="amount">{{ $right['actual'] !== null ? number_format($right['actual'], 2) : '' }}</td>
                </tr>
            @endfor
            
            <!-- Totals row -->
            <tr style="background-color: #fff; font-weight: bold;">
                <td>Total Earnings:INR.:</td>
                <td class="amount">{{ number_format($masterGross, 2) }}</td>
                <td class="amount">{{ number_format($payroll->gross_salary, 2) }}</td>
                <td>Total Deductions:INR.</td>
                <td class="amount">{{ number_format($payroll->total_deductions, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <!-- Net Pay & Words -->
    <div class="net-pay-section">
        <div>Net Pay for the month&nbsp;&nbsp;<span class="net-pay-amount">{{ number_format($payroll->net_salary, 2) }}</span></div>
        <div class="net-pay-words">({{ $netPayWords }})</div>
    </div>

    <!-- Footer System Generated disclaimer -->
    <div class="footer-line">
        This is a system generated payslip and does not require a signature
    </div>

</body>
</html>