<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Offer Letter</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 40px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #2563eb;
            padding-bottom: 20px;
        }
        .company-name {
            font-size: 24px;
            font-weight: bold;
            color: #1e40af;
        }
        .letter-title {
            font-size: 20px;
            font-weight: bold;
            margin: 30px 0;
            text-align: center;
        }
        .date {
            text-align: right;
            margin-bottom: 30px;
        }
        .candidate-details {
            margin-bottom: 30px;
        }
        .content {
            margin: 30px 0;
        }
        .signature {
            margin-top: 50px;
            padding-top: 20px;
            border-top: 1px solid #ccc;
        }
        .footer {
            margin-top: 50px;
            text-align: center;
            font-size: 12px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-name">HR Portal</div>
        <div>Management System</div>
        <div>8-2-293/82/A/787/1/4F/1, Jubilee Hills, Hyderabad</div>
    </div>
    
    <div class="date">
        Date: {{ \Carbon\Carbon::parse($letter_date)->format('d F Y') }}
    </div>
    
    <div class="candidate-details">
        <strong>To,</strong><br>
        {{ $candidate->candidate_name }}<br>
        {{ $candidate->email }}<br>
        @if($candidate->phone){{ $candidate->phone }}@endif
    </div>
    
    <div class="letter-title">
        Offer of Employment
    </div>
    
    <div class="content">
        <p>Dear {{ $candidate->candidate_name }},</p>
        
        <p>We are pleased to offer you the position of <strong>{{ $candidate->position }}</strong> in the <strong>{{ $candidate->department }}</strong> department at HR Portal.</p>
        
        @if($content)
            {!! nl2br(e($content)) !!}
        @else
            <p>Your joining date will be <strong>{{ \Carbon\Carbon::parse($candidate->joining_date)->format('d F Y') }}</strong>.</p>
            
            <p>Your compensation package includes:</p>
            <ul>
                <li>Annual CTC: ₹{{ number_format($candidate->ctc, 2) }}</li>
                <li>Medical Insurance coverage</li>
                <li>Performance-based bonuses</li>
                <li>Learning and development opportunities</li>
            </ul>
            
            <p>Please find attached the detailed terms and conditions of your employment.</p>
        @endif
        
        <p>We look forward to welcoming you to our team and wish you a successful career with us.</p>
        
        <p>Sincerely,</p>
        <p><strong>HR Team</strong><br>HR Portal</p>
    </div>
    
    <div class="signature">
        <p><em>This is a system-generated offer letter.</em></p>
    </div>
    
    <div class="footer">
        <p>HR Portal - Management System | www.hrportal.com</p>
    </div>
</body>
</html>