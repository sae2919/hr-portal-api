<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Payslip</title>
    <style>
        body {
            font-family: 'Times New Roman', Times, 'DejaVu Serif', serif;
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
            width: 70%;
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
            text-align: center;
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
        $employee = $employee ?? $payroll->employee;
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
        $actualBasic = $payroll->items->where('type', 'earning')->where('name', 'Stipend')->first()?->amount
            ?? $payroll->items->where('type', 'earning')->where('name', 'Basic Salary')->first()?->amount 
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
            
        $isIntern = ($employee->employment_type ?? null) === 'intern';

        if ($isIntern) {
            $leftRows = [
                ['label' => 'Stipend', 'master' => $masterBasic, 'actual' => $actualBasic],
            ];
        } else {
            // Earning rows
            $leftRows = [
                ['label' => 'Basic', 'master' => $masterBasic, 'actual' => $actualBasic],
                ['label' => 'HRA', 'master' => $masterHra, 'actual' => $actualHra],
                ['label' => 'Special Allowance', 'master' => $masterAllowances, 'actual' => $actualAllowances],
            ];
            
            if ($masterBonus > 0 || $actualBonus > 0) {
                $leftRows[] = ['label' => 'Bonus', 'master' => $masterBonus, 'actual' => $actualBonus];
            }
        }
        
        // Dynamic earnings
        $knownEarningNames = ['basic salary', 'basic', 'hra', 'allowances', 'special allowance', 'bonus', 'stipend'];
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
        $lopDeduction = (float)($payroll->lop_deduction ?? 0);
        $rightRows = [];

        // Always show LOP Deduction first if there are LOP days
        if ($lopDeduction > 0) {
            $rightRows[] = ['label' => 'LOP', 'actual' => $lopDeduction];
        }

        if (!$isIntern) {
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
        } else {
            // For interns with no LOP, keep a blank row for alignment
            if ($lopDeduction == 0) {
                $rightRows[] = ['label' => '', 'actual' => null];
            }
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
            <td style="width: 15%; border: none;"></td>
        </tr>
    </table>

    <div class="payslip-title">
        Payslip for the month of {{ $monthName }} {{ $payroll->year }}
    </div>

    <!-- Employee Details Matrix -->
    <table class="employee-details-table">
        <tr>
            <td class="col-left">
                <table style="width: 100%; border-collapse: collapse; border: none;">
                    <tr>
                        <td style="width: 35%; padding: 2px 0; border: none; font-weight: normal; font-size: 11px;">Name:</td>
                        <td style="width: 65%; padding: 2px 0; border: none; font-weight: normal; font-size: 11px;">{{ $employee->first_name }} {{ $employee->last_name }}</td>
                    </tr>
                    <tr>
                        <td style="width: 35%; padding: 2px 0; border: none; font-weight: normal; font-size: 11px;">Joining Date:</td>
                        <td style="width: 65%; padding: 2px 0; border: none; font-weight: normal; font-size: 11px;">{{ $employee->joining_date ? $employee->joining_date->format('d M Y') : '-' }}</td>
                    </tr>
                    <tr>
                        <td style="width: 35%; padding: 2px 0; border: none; font-weight: normal; font-size: 11px;">Designation:</td>
                        <td style="width: 65%; padding: 2px 0; border: none; font-weight: normal; font-size: 11px;">{{ $employee->designation->title ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td style="width: 35%; padding: 2px 0; border: none; font-weight: normal; font-size: 11px;">Department:</td>
                        <td style="width: 65%; padding: 2px 0; border: none; font-weight: normal; font-size: 11px;">{{ $employee->department->name ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td style="width: 35%; padding: 2px 0; border: none; font-weight: normal; font-size: 11px;">Location:</td>
                        <td style="width: 65%; padding: 2px 0; border: none; font-weight: normal; font-size: 11px;">{{ $employee->city ?: 'Hyderabad' }}</td>
                    </tr>
                </table>
            </td>
            <td class="col-right">
                <table style="width: 100%; border-collapse: collapse; border: none;">
                    <tr>
                        <td style="width: 45%; padding: 2px 0; border: none; font-weight: normal; font-size: 11px;">Employee Code:</td>
                        <td style="width: 55%; padding: 2px 0; border: none; font-weight: normal; font-size: 11px;">{{ $employee->employee_code }}</td>
                    </tr>
                    <tr>
                        <td style="width: 45%; padding: 2px 0; border: none; font-weight: normal; font-size: 11px;">Bank Name:</td>
                        <td style="width: 55%; padding: 2px 0; border: none; font-weight: normal; font-size: 11px;">{{ $bankName }}</td>
                    </tr>
                    <tr>
                        <td style="width: 45%; padding: 2px 0; border: none; font-weight: normal; font-size: 11px;">Bank Account No:</td>
                        <td style="width: 55%; padding: 2px 0; border: none; font-weight: normal; font-size: 11px;">{{ $maskedAcc }}</td>
                    </tr>
                    <tr>
                        <td style="width: 45%; padding: 2px 0; border: none; font-weight: normal; font-size: 11px;">PAN Number:</td>
                        <td style="width: 55%; padding: 2px 0; border: none; font-weight: normal; font-size: 11px;">{{ $maskedPan }}</td>
                    </tr>
                    <tr>
                        <td style="width: 45%; padding: 2px 0; border: none; font-weight: normal; font-size: 11px;">Effective Work Days:</td>
                        <td style="width: 55%; padding: 2px 0; border: none; font-weight: normal; font-size: 11px;">{{ $payroll->present_days }}</td>
                    </tr>
                    <tr>
                        <td style="width: 45%; padding: 2px 0; border: none; font-weight: normal; font-size: 11px;">LOP:</td>
                        <td style="width: 55%; padding: 2px 0; border: none; font-weight: normal; font-size: 11px;">{{ (int)($payroll->lop_days ?? 0) }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- Salary Breakdown Table -->
    <table class="salary-table">
        <thead>
            <tr>
                <th style="width: 35%;">Earnings</th>
                <th style="width: 15%;">Master</th>
                <th style="width: 15%;">Actual</th>
                <th style="width: 20%;">Deductions</th>
                <th style="width: 15%;">Actual</th>
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
                <td class="amount">{{ number_format($payroll->total_deductions + $lopDeduction, 2) }}</td>
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