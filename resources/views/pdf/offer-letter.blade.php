@if($candidate->onboarding_type === 'free_intern')
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Free Internship Offer Letter</title>
    <style>
        @page {
            margin: 140px 50px 80px 50px;
        }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            color: #1e293b;
            line-height: 1.6;
        }
        .header {
            position: fixed;
            top: -110px;
            left: 0;
            right: 0;
            height: 90px;
            border-bottom: 2px solid #3b82f6;
            padding-bottom: 10px;
        }
        .header table {
            width: 100%;
            border-collapse: collapse;
        }
        .header td {
            padding: 0;
            vertical-align: middle;
        }
        .logo-box {
            background-color: #1e3a8a;
            color: white;
            width: 32px;
            height: 32px;
            line-height: 32px;
            text-align: center;
            border-radius: 6px;
            font-weight: bold;
            font-size: 16px;
            display: inline-block;
            margin-right: 8px;
        }
        .logo-text {
            font-size: 22px;
            font-weight: bold;
            color: #1e293b;
            display: inline-block;
            vertical-align: middle;
        }
        .company-address {
            font-size: 10px;
            color: #475569;
            text-align: right;
            line-height: 1.4;
        }
        .company-address a {
            color: #1e3a8a;
            text-decoration: none;
            font-weight: bold;
        }
        .page-break {
            page-break-after: always;
        }
        .meta-section {
            margin-top: 10px;
            margin-bottom: 20px;
        }
        .meta-section table {
            width: 100%;
        }
        .meta-section td {
            padding: 0;
            vertical-align: top;
        }
        .candidate-title {
            font-size: 13px;
            font-weight: bold;
            color: #1e3a8a;
        }
        .letter-date {
            text-align: right;
            font-size: 13px;
            font-weight: bold;
            color: #475569;
        }
        .section-title {
            font-size: 12px;
            font-weight: bold;
            margin-top: 15px;
            margin-bottom: 5px;
            color: #0f172a;
        }
        .paragraph {
            margin-bottom: 12px;
            text-align: justify;
        }
        .signature-section {
            margin-top: 30px;
        }
        .address-block {
            font-size: 11px;
            color: #475569;
            line-height: 1.4;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <!-- Repeating Header -->
    <div class="header">
        <table>
            <tr>
                <td>
                    <div class="logo-box">TS</div>
                    <div class="logo-text">Techsprout</div>
                </td>
                <td class="company-address">
                    <strong>Techsprout AI Labs Pvt. Ltd</strong><br>
                    501, Manjeera Majestic Commercial,<br>
                    JNTU Road, KPHB, Hyderabad.<br>
                    <a href="https://www.techsprout.ai" target="_blank">www.techsprout.ai</a>
                </td>
            </tr>
        </table>
    </div>

    <!-- Page 1 Content -->
    <div class="meta-section">
        <table>
            <tr>
                <td class="candidate-title">DEAR {{ strtoupper($candidate->candidate_name) }}</td>
                <td class="letter-date">{{ strtoupper(\Carbon\Carbon::parse($letter_date ?? now())->format('d-M Y')) }}</td>
            </tr>
        </table>
    </div>

    <div class="paragraph">
        We are pleased to extend to you a <strong>Free Internship Offer</strong> with <strong>Techsprout AI Labs Pvt. Ltd.</strong> for the position of {{ $candidate->position }}, reporting at our office located in Hyderabad.
    </div>

    <div class="paragraph">
        This offer is being made based on your selection through our evaluation process. This internship is unpaid in nature and is subject to the terms and conditions outlined below and contingent upon successful verification of your credentials and acceptance of the same.
    </div>

    <div class="section-title">1. Joining Date</div>
    <div class="paragraph">
        You will be designated as a <strong>{{ $candidate->position }}</strong>, and your internship will be effective from <strong>{{ \Carbon\Carbon::parse($candidate->joining_date)->format('d/m/Y') }}</strong>.
    </div>
    <div class="paragraph">
        This is a free internship engagement with Techsprout AI Labs Pvt. Ltd. and does not constitute employment. Upon successful completion of the internship and based on performance and business requirements, you may be considered for a full-time role.
    </div>

    <div class="section-title">2. Stipend</div>
    <div class="paragraph">
        This is a free internship program. No stipend, salary, or monetary compensation will be provided during the internship period. The internship is intended solely for learning, training, and practical exposure purposes.
    </div>

    <div class="section-title">3. Evaluation & Future Opportunities</div>
    <div class="paragraph">
        Your performance, conduct, and contribution will be evaluated on an ongoing basis as part of Techsprout AI Labs' evaluation process. Based on your performance and business requirements, you may be considered for future paid opportunities, role enhancements, or employment in accordance with company policies.
    </div>

    <div class="section-title">4. Notice Period & Termination</div>
    <div class="paragraph">
        Either party may terminate this free internship by providing 15 days' written notice. Techsprout AI Labs Pvt. Ltd. reserves the right to terminate your internship immediately, without notice, in cases of misconduct, breach of confidentiality, falsification of documents, gross negligence, or violation of company policies.
    </div>

    <div class="page-break"></div>

    <!-- Page 2 Content -->
    <div class="section-title" style="margin-top: 10px;">5. Confidentiality & Intellectual Property</div>
    <div class="paragraph">
        You shall maintain strict confidentiality of all internal data, business information, client details, software code, documentation, student information, and business strategies of Techsprout AI Labs during the course of your internship.
    </div>
    <div class="paragraph">
        All work products, materials, intellectual property, documentation, software, designs, or content developed by you during the course of your free internship shall remain the sole property of Techsprout AI Labs Pvt. Ltd.
    </div>

    <div class="section-title">6. Non-Compete & Professional Ethics</div>
    <div class="paragraph">
        During your free internship and for a period of 3 months post completion of the internship, you shall not engage in any activity, internship, assignment, or service that directly competes with the business interests or product offerings of Techsprout AI Labs Pvt. Ltd.
    </div>
    <div class="paragraph">
        You are expected to maintain the highest standards of professional conduct, ethical behavior, and communication with internal teams, clients, and stakeholders.
    </div>

    <div class="section-title">7. Termination of Engagement</div>
    <div class="paragraph">
        Either party may terminate this free internship with 15 days' written notice or stipend in lieu of such notice. Techsprout AI Labs Pvt. Ltd. reserves the right to terminate the internship, without notice or compensation, immediately in cases of misconduct, breach of confidentiality, violation of company policies, or actions detrimental to the organization.
    </div>

    <div class="section-title">8. Dispute Resolution</div>
    <div class="paragraph">
        Any dispute arising from this free internship shall be subject to arbitration in Hyderabad, governed by the laws of India. The decision of the appointed arbitrator shall be final and binding on both parties.
    </div>

    <div class="section-title">9. Acceptance of Offer</div>
    <div class="paragraph">
        Please sign and return a scanned copy of this letter by {{ \Carbon\Carbon::parse($letter_date ?? now())->addDays(1)->format('d-m-Y') }} to confirm your acceptance. Failure to do so within the given timeframe will render this free internship offer void.
    </div>

    <div class="page-break"></div>

    <!-- Page 3 Content -->
    <div class="section-title" style="margin-top: 10px;">10. Data Security & IT Assets</div>
    <div class="paragraph">
        If provided with a laptop, email access, or access to proprietary systems, you shall be responsible for maintaining data security and complying with the company's IT usage policies. All company-issued equipment must be returned in good condition upon completion of the internship.
    </div>

    <div class="section-title">11. Dispute Resolution s Jurisdiction</div>
    <div class="paragraph">
        Any dispute arising from this agreement or your free internship shall be subject to arbitration in Hyderabad, governed by the laws of India. The decision of the appointed arbitrator shall be final and binding on both parties.
    </div>

    <div class="section-title">12. Background Verification</div>
    <div class="paragraph">
        This free internship offer is subject to successful background verification, including educational qualifications, identity verification, and other records as required by company policy. Any material discrepancy may lead to withdrawal of this internship offer or termination of the internship without notice.
    </div>

    <div class="section-title">13. Acceptance of Offer</div>
    <div class="paragraph">
        Please sign and return a scanned copy of this letter by {{ \Carbon\Carbon::parse($letter_date ?? now())->addDays(1)->format('d/m/Y') }} to confirm your acceptance. If not received by this date, the free internship offer will be considered withdrawn.
    </div>

    <div class="paragraph" style="margin-top: 25px;">
        We look forward to welcoming you to Techsprout AI Labs Pvt. Ltd. and are excited about the value you will bring during your internship with our growing team.
    </div>

    <div class="signature-section">
        <p style="margin-bottom: 10px;">Warm regards,</p>
        <div style="font-family: 'DejaVu Sans', sans-serif; font-style: italic; color: #1e3a8a; font-size: 16px; margin-top: 15px; margin-bottom: 5px; text-decoration: underline; letter-spacing: 0.5px;">
            Vishwanath. S
        </div>
        <strong>Vishwanath Srirangam</strong><br>
        Founder & CEO<br>
        <div class="address-block">
            Techsprout AI Labs Pvt. Ltd.<br>
            501, Manjeera Majestic Commercial,<br>
            KPHB, Hyderabad<br>
            <a href="https://www.techsprout.ai" style="color: #1e3a8a; text-decoration: none; font-weight: bold;">www.techsprout.ai</a>
        </div>
    </div>
</body>
</html>
@else
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
@endif
