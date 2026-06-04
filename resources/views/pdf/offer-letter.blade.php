<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Offer of Employment</title>
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
        .candidate-details {
            margin-bottom: 30px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 15px;
        }
        .candidate-details table {
            width: 100%;
            border-collapse: collapse;
        }
        .candidate-details td {
            border: none;
            padding: 4px 8px;
        }
        .candidate-details td.label {
            font-weight: bold;
            color: #64748b;
            width: 150px;
        }
        .highlight-box {
            background-color: #eff6ff;
            border-left: 4px solid #3b82f6;
            padding: 12px 16px;
            margin-top: 15px;
            margin-bottom: 15px;
            border-radius: 0 8px 8px 0;
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
                    <div class="company-name">Techsprout AI Labs</div>
                    <div style="font-size: 11px; color: #64748b;">Human Resources Division</div>
                </td>
                <td style="text-align: right;">
                    <!-- Logo placeholder or company banner -->
                    <div style="font-size: 14px; font-weight: bold; color: #3b82f6;">OFFICIAL LETTER</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="date">
        Date: {{ \Carbon\Carbon::parse($letter_date ?? now())->format('F d, Y') }}
    </div>

    <div class="candidate-details">
        <table>
            <tr>
                <td class="label">Candidate Name:</td>
                <td>{{ $candidate->candidate_name }}</td>
            </tr>
            <tr>
                <td class="label">Email Address:</td>
                <td>{{ $candidate->email }}</td>
            </tr>
            <tr>
                <td class="label">Proposed Position:</td>
                <td>{{ $candidate->position }}</td>
            </tr>
            <tr>
                <td class="label">Department:</td>
                <td>{{ $candidate->department }}</td>
            </tr>
            <tr>
                <td class="label">Proposed CTC:</td>
                <td>₹{{ number_format((float) ($candidate->ctc ?? 0), 2) }} / Year</td>
            </tr>
        </table>
    </div>

    <div class="letter-title">
        Letter of Offer
    </div>

    <p>Dear {{ $candidate->candidate_name }},</p>

    <p>
        Following our recent discussions, we are pleased to offer you the position of <strong>{{ $candidate->position }}</strong> in the <strong>{{ $candidate->department }}</strong> department at Techsprout AI Labs. We are excited about the prospect of you joining our team and contributing to our mutual success.
    </p>

    @if(!empty($content))
        <p>{!! nl2br(e($content)) !!}</p>
    @else
        <p>
            Your employment will be subject to the terms and conditions outlined in the official employment agreement. Your scheduled start date will be <strong>{{ \Carbon\Carbon::parse($candidate->joining_date)->format('F d, Y') }}</strong>.
        </p>
    @endif

    <div class="highlight-box">
        <strong>Important Joining Instructions:</strong><br>
        Please bring copy of your PAN card, Aadhaar card, educational certificates, and previous experience/relieving letters on your date of joining for the onboarding document verification process.
    </div>

    <p>
        To accept this offer, please sign and return the duplicate copy of this letter within 3 business days, failing which this offer shall stand cancelled.
    </p>

    <div class="sign-off">
        <p>Yours sincerely,</p>
        <div style="font-weight: bold; color: #1e3a8a; margin-top: 40px;">Human Resources Team</div>
        <p style="font-size: 12px; color: #64748b; margin-top: 5px;">Techsprout AI Labs Pvt. Ltd.</p>
    </div>

    <div class="footer">
        This is a system-generated offer letter from Techsprout AI Labs.
    </div>
</body>
</html>
