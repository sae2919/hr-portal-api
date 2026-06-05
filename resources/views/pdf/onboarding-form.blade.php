<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Candidate Onboarding Details Form</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 13px;
            color: #1e293b;
            line-height: 1.6;
            padding: 20px;
        }
        .header {
            border-bottom: 2px solid #3b82f6;
            padding-bottom: 15px;
            margin-bottom: 25px;
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
        .form-title {
            font-size: 18px;
            font-weight: bold;
            text-align: center;
            margin-bottom: 25px;
            text-transform: uppercase;
            color: #1e3a8a;
            letter-spacing: 0.5px;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 10px;
        }
        .section-title {
            font-size: 14px;
            font-weight: bold;
            color: #1e3a8a;
            margin-top: 20px;
            margin-bottom: 10px;
            border-left: 3px solid #3b82f6;
            padding-left: 8px;
            background: #eff6ff;
            padding-top: 4px;
            padding-bottom: 4px;
        }
        .details-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        .details-table th, .details-table td {
            border: 1px solid #e2e8f0;
            padding: 8px 12px;
            text-align: left;
            vertical-align: top;
        }
        .details-table th {
            background-color: #f8fafc;
            font-weight: bold;
            color: #475569;
            width: 30%;
        }
        .details-table td {
            color: #0f172a;
        }
        .footer {
            margin-top: 40px;
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
                    <div class="company-name">Techsprout AI Labs</div>
                    <div style="font-size: 11px; color: #64748b;">Human Resources Division</div>
                </td>
                <td style="text-align: right; vertical-align: middle;">
                    <div style="font-size: 12px; font-weight: bold; color: #3b82f6;">CANDIDATE ONBOARDING FILE</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="form-title">Candidate Details Form Submission</div>

    <div class="section-title">1. Personal & Employment Information</div>
    <table class="details-table">
        <tr>
            <th>Full Name</th>
            <td>{{ $candidate->candidate_name }}</td>
        </tr>
        <tr>
            <th>Position / Role</th>
            <td>{{ $candidate->position }}</td>
        </tr>
        <tr>
            <th>Department</th>
            <td>{{ $candidate->department }}</td>
        </tr>
        <tr>
            <th>Target Joining Date</th>
            <td>{{ \Carbon\Carbon::parse($candidate->joining_date)->format('d-M-Y') }}</td>
        </tr>
        <tr>
            <th>Date of Birth</th>
            <td>{{ isset($details['dob']) ? \Carbon\Carbon::parse($details['dob'])->format('d-M-Y') : 'N/A' }}</td>
        </tr>
        <tr>
            <th>Gender</th>
            <td style="text-transform: capitalize;">{{ $details['gender'] ?? 'N/A' }}</td>
        </tr>
        <tr>
            <th>Contact Email</th>
            <td>{{ $candidate->email }}</td>
        </tr>
        <tr>
            <th>Contact Phone</th>
            <td>{{ $candidate->phone ?: ($details['phone'] ?? 'N/A') }}</td>
        </tr>
    </table>

    <div class="section-title">2. Identity & Tax Information</div>
    <table class="details-table">
        <tr>
            <th>PAN Number</th>
            <td style="text-transform: uppercase;">{{ $details['pan_number'] ?? 'N/A' }}</td>
        </tr>
        <tr>
            <th>Aadhaar Number</th>
            <td>{{ $details['aadhaar_number'] ?? 'N/A' }}</td>
        </tr>
        <tr>
            <th>Passport Number</th>
            <td style="text-transform: uppercase;">{{ $details['passport_number'] ?? 'N/A' }}</td>
        </tr>
        <tr>
            <th>Driving License</th>
            <td style="text-transform: uppercase;">{{ $details['driving_license'] ?? 'N/A' }}</td>
        </tr>
    </table>

    <div class="section-title">3. Address Information</div>
    <table class="details-table">
        <tr>
            <th>Address Details</th>
            <td>{!! nl2br(e($details['address'] ?? 'N/A')) !!}</td>
        </tr>
    </table>

    <div class="section-title">4. Bank Account Details</div>
    <table class="details-table">
        <tr>
            <th>Bank Name</th>
            <td>{{ $details['bank_name'] ?? 'N/A' }}</td>
        </tr>
        <tr>
            <th>Account Number</th>
            <td>{{ $details['bank_account_number'] ?? 'N/A' }}</td>
        </tr>
        <tr>
            <th>IFSC Code</th>
            <td style="text-transform: uppercase;">{{ $details['bank_ifsc'] ?? 'N/A' }}</td>
        </tr>
        <tr>
            <th>Branch Name</th>
            <td>{{ $details['bank_branch'] ?? 'N/A' }}</td>
        </tr>
    </table>

    <div class="footer">
        <p>Submitted electronically by candidate on {{ date('d-M-Y H:i:s') }} (IST)</p>
        <p>&copy; {{ date('Y') }} Techsprout. All rights reserved.</p>
    </div>
</body>
</html>
