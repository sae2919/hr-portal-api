<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Experience & Relieving Letter</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 13px;
            color: #1e293b;
            line-height: 1.6;
            padding: 30px;
        }
        .header {
            border-bottom: 2px solid #1e3a8a;
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
            vertical-align: top;
        }
        .company-name {
            font-size: 20px;
            font-weight: bold;
            color: #1e3a8a;
        }
        .company-address {
            font-size: 11px;
            color: #475569;
            line-height: 1.4;
        }
        .company-website {
            font-size: 11px;
            color: #1e3a8a;
            font-weight: bold;
            margin-top: 4px;
        }
        .letter-title {
            font-size: 16px;
            font-weight: bold;
            text-align: center;
            margin-top: 25px;
            margin-bottom: 25px;
            text-decoration: underline;
            color: #0f172a;
            letter-spacing: 0.5px;
        }
        .date {
            text-align: right;
            margin-bottom: 25px;
            font-weight: 500;
        }
        .salutation {
            margin-bottom: 5px;
            font-weight: bold;
        }
        .subject {
            font-weight: bold;
            margin-bottom: 25px;
        }
        .service-record-title {
            font-weight: bold;
            text-decoration: underline;
            margin-top: 30px;
            margin-bottom: 15px;
        }
        .service-record-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        .service-record-table td {
            border: none;
            padding: 4px 0;
            font-size: 13px;
        }
        .service-record-table td.label {
            font-weight: bold;
            width: 150px;
            color: #0f172a;
        }
        .sign-off {
            margin-top: 50px;
            line-height: 1.4;
        }
        .sign-off .name {
            font-weight: bold;
            color: #0f172a;
            margin-top: 40px;
        }
        .sign-off .title {
            color: #475569;
        }
        .sign-off .company {
            color: #475569;
            font-size: 12px;
        }
    </style>
</head>
<body>
    @php
        $salutation = 'Mr.';
        if (isset($employee->gender) && strtolower($employee->gender) === 'female') {
            $salutation = 'Ms.';
        }
        
        $empFullName = ($employee->first_name ?? '') . ' ' . ($employee->last_name ?? '');
        $joiningDateFormatted = $employee->joining_date ? \Carbon\Carbon::parse($employee->joining_date)->format('d-M-Y') : '';
        $lastDayFormatted = $offboarding->last_working_day ? \Carbon\Carbon::parse($offboarding->last_working_day)->format('d-M-Y') : '';
        $lastDayLeavingFormatted = $offboarding->last_working_day ? \Carbon\Carbon::parse($offboarding->last_working_day)->format('d-M-Y') : '';
    @endphp

    <div class="header">
        <table>
            <tr>
                <td>
                    @if($company_logo)
                        <img src="{{ $company_logo }}" style="max-height: 50px; max-width: 180px; object-fit: contain;" alt="Logo" />
                    @else
                        <div class="company-name">{{ $company_name }}</div>
                    @endif
                </td>
                <td style="text-align: right;">
                    <div style="font-weight: bold; color: #1e3a8a; font-size: 13px;">{{ $company_name }}</div>
                    <div class="company-address">
                        501, Manjeera Majestic Commercial,<br>
                        JNTU Road, KPHB, Hyderabad.
                    </div>
                    <div class="company-website">www.techsprout.ai</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="date">
        {{ \Carbon\Carbon::now()->format('d-M-Y') }}
    </div>

    <div class="salutation">
        {{ $salutation }} {{ $empFullName }},
    </div>
    
    <div class="subject">
        Sub : Experience Cum Relieving Letter
    </div>

    <p>
        This is to certify that <strong>{{ $salutation }} {{ $empFullName }}</strong> was associated with <strong>{{ $company_name }}</strong> as 
        <strong>{{ $employee->designation->name ?? '-' }}</strong> from <strong>{{ $joiningDateFormatted }}</strong> to <strong>{{ $lastDayFormatted }}</strong>.
    </p>

    <p>
        During this tenure, the employee worked and carried out assigned responsibilities professionally.
    </p>

    <p>
        We hereby confirm that <strong>{{ $salutation }} {{ $empFullName }}</strong> has been formally relieved from their services with 
        <strong>{{ $company_name }}</strong>, effective <strong>{{ $lastDayLeavingFormatted }}</strong>. All responsibilities have been duly completed and cleared.
    </p>

    <div class="service-record-title">
        Your Service Record is as follows:
    </div>

    <table class="service-record-table">
        <tr>
            <td class="label">Employee ID:</td>
            <td>{{ $employee->employee_code ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Name:</td>
            <td>{{ $empFullName }}</td>
        </tr>
        <tr>
            <td class="label">First Designation:</td>
            <td>{{ $employee->designation->name ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Last Designation:</td>
            <td>{{ $employee->designation->name ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Date of Joining:</td>
            <td>{{ $joiningDateFormatted }}</td>
        </tr>
        <tr>
            <td class="label">Date of Leaving:</td>
            <td>{{ $lastDayLeavingFormatted }}</td>
        </tr>
    </table>

    <p>
        We wish them all the best for their future endeavors.
    </p>

    <div class="sign-off">
        <p>Yours sincerely,</p>
        <div class="name">Vishwanath Srirangam</div>
        <div class="title">Founder & CEO</div>
        <div class="company">{{ $company_name }}</div>
    </div>
</body>
</html>
