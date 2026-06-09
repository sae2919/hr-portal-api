{{-- ═══════════════════════════════════════════════════════════════
     FREE INTERNSHIP OFFER LETTER  (onboarding_type = free_intern)
     ═══════════════════════════════════════════════════════════════ --}}
@if($candidate->onboarding_type === 'free_intern')
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Free Internship Offer Letter – {{ $candidate->candidate_name }}</title>
    <style>
        @page {
            margin: 125px 37pt 60px 37pt;
        }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11pt;
            color: #000000;
            line-height: 1.45;
        }
        /* ── Fixed repeating header on every page ── */
        .header {
            position: fixed;
            top: -100px;
            left: 0;
            right: 0;
            height: 80px;
            border-bottom: 1.5pt solid #28326e;
            padding-bottom: 8px;
        }
        .header table { width: 100%; border-collapse: collapse; }
        .header td   { padding: 0; vertical-align: top; }
        .company-address {
            font-size: 10pt;
            color: #28326e;
            text-align: right;
            line-height: 1.35;
        }
        .company-address a { color: #28326e; text-decoration: none; font-weight: normal; }

        /* ── Body ── */
        .paragraph    { margin-bottom: 8px; text-align: justify; }
        .section-title {
            font-size: 11pt;
            font-weight: bold;
            margin-top: 12px;
            margin-bottom: 3px;
            color: #000000;
        }
        .page-break   { page-break-after: always; }

        /* ── Signature ── */
        .signature-section { margin-top: 20px; page-break-inside: avoid; }
        .address-block { font-size: 11pt; color: #000000; line-height: 1.35; margin-top: 8px; }
        .address-block a { color: #000000; text-decoration: none; font-weight: normal; }
    </style>
</head>
<body>

    {{-- ── Repeating page header ── --}}
    <div class="header">
        <table>
            <tr>
                <td style="text-align: left;">
                    <img src="data:image/jpeg;base64,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" style="width: 238pt; height: 76.5pt; display: block;" />
                </td>
                <td class="company-address">
                    <span style="font-weight: bold; display: block; margin-bottom: 3px;">Techsprout AI Labs Pvt. Ltd</span>
                    501, Manjeera Majestic Commercial,<br>
                    JNTU Road, KPHB, Hyderabad.<br>
                    <a href="https://www.techsprout.ai">www.techsprout.ai</a>
                </td>
            </tr>
        </table>
    </div>

    {{-- ══════════════════ PAGE 1 ══════════════════ --}}
    <div style="text-align: right; font-family: 'DejaVu Sans'; font-size: 11pt; font-weight: bold; color: #28326e; margin-top: 10px; margin-bottom: 5px;">
        {{ \Carbon\Carbon::parse($letter_date ?? now())->format('d-F Y') }}
    </div>
    <div style="font-family: 'DejaVu Sans'; font-size: 11pt; font-weight: bold; color: #28326e; margin-bottom: 15px; text-align: left;">
        DEAR {{ strtoupper($candidate->candidate_name) }}
    </div>

    <div class="paragraph">
        We are pleased to extend to you an <strong>Internship Offer</strong> with <strong>Techsprout AI Labs Pvt. Ltd.</strong>
        for the position of <strong>{{ $candidate->position }}</strong>, reporting at our office located in Hyderabad.
    </div>

    <div class="paragraph">
        This offer is being made based on your selection through our evaluation process. This internship is
        <strong>unpaid</strong> in nature and is subject to the terms and conditions outlined below and contingent upon
        successful verification of your credentials and acceptance of the same.
    </div>

    <div class="section-title">1. Joining Date  &amp;  Duration:</div>
    <div class="paragraph">
        You will be designated as a <strong>{{ $candidate->position }}</strong>, and your internship will commence on
        <strong>{{ \Carbon\Carbon::parse($candidate->joining_date)->format('d/m/Y') }}</strong>.
        The duration of this internship will be
        <strong>{{ $candidate->duration ?? '3 months' }}</strong> from the date of joining.
    </div>
    <div class="paragraph">
        This is an internship engagement with Techsprout AI Labs Pvt. Ltd. and does not constitute employment.
        Upon successful completion of the internship, you may be considered for a full-time role based on
        performance and business requirements.
    </div>

    <div class="section-title">2. Stipend</div>
    <div class="paragraph">
        There will be no stipend, salary, or monetary compensation provided during the internship period.
        The internship is intended solely for learning, training, and practical exposure purposes.
    </div>

    <div class="section-title">3. Evaluation &amp; Future Opportunities</div>
    <div class="paragraph">
        Your performance, conduct, and contribution will be evaluated on an ongoing basis as part of
        Techsprout AI Labs' evaluation process. Based on your performance and business requirements, you may be
        considered for future paid opportunities, role enhancements, or employment in accordance with company policies.
    </div>

    <div class="section-title">4. Notice Period &amp; Termination</div>
    <div class="paragraph">
        Either party may terminate this internship by providing 7 days' written notice. Techsprout AI Labs Pvt. Ltd.
        reserves the right to terminate your internship immediately, without notice, in cases of misconduct,
        breach of confidentiality, falsification of documents, gross negligence, or violation of company policies.
    </div>

    <div class="page-break"></div>

    {{-- ══════════════════ PAGE 2 ══════════════════ --}}
    <div class="section-title" style="margin-top:10px;">5. Confidentiality &amp; Intellectual Property</div>
    <div class="paragraph">
        You shall maintain strict confidentiality of all data, business information, client details,
        software code, documentation, student information, and business strategies of Techsprout AI Labs during
        the course of your internship.
    </div>
    <div class="paragraph">
        All work products, materials, intellectual property, documentation, software, designs, or content developed
        by you during the course of your internship shall remain the sole property of Techsprout AI Labs Pvt. Ltd.
    </div>

    <div class="section-title">6. Non-Compete &amp; Professional Ethics</div>
    <div class="paragraph">
        During your internship and for a period of 3 months post completion of the internship, you shall not engage
        in any activity, internship, assignment, or service that directly competes with the business interests or
        product offerings of Techsprout AI Labs Pvt. Ltd.
    </div>
    <div class="paragraph">
        You are expected to maintain the highest standards of professional conduct, ethical behavior, and
        communication with internal teams, clients, and stakeholders.
    </div>

    <div class="section-title">7. Termination of Engagement</div>
    <div class="paragraph">
        Either party may terminate this internship with 7 days' written notice or stipend in lieu of such notice.
        Techsprout AI Labs Pvt. Ltd. reserves the right to terminate the internship, without notice or compensation,
        immediately in cases of misconduct, breach of confidentiality, violation of company policies, or actions
        detrimental to the organization.
    </div>

    <div class="section-title">8. Dispute Resolution</div>
    <div class="paragraph">
        Any dispute arising from this internship shall be subject to arbitration in Hyderabad, governed by the
        laws of India. The decision of the appointed arbitrator shall be final and binding on both parties.
    </div>

    <div class="section-title">9. Acceptance of Offer</div>
    <div class="paragraph">
        Please sign and return a scanned copy of this letter by
        <strong>{{ \Carbon\Carbon::parse($letter_date ?? now())->addDays(2)->format('d-m-Y') }}</strong>
        to confirm your acceptance. Failure to do so within the given time frame will render this internship offer void.
    </div>

    <div class="page-break"></div>

    {{-- ══════════════════ PAGE 3 ══════════════════ --}}
    <div class="section-title" style="margin-top:10px;">10.  Data Security &amp;  IT Assets</div>
    <div class="paragraph">
        If provided with a laptop, email access, or access to proprietary systems, you shall be responsible for
        maintaining data security and complying with the company's IT usage policies. All company-issued equipment
        must be returned in good condition upon completion of the internship.
    </div>

    <div class="section-title">11. Dispute Resolution s Jurisdiction</div>
    <div class="paragraph">
        Any dispute arising from this agreement or your internship shall be subject to arbitration in Hyderabad,
        governed by the laws of India. The decision of the appointed arbitrator shall be final and binding on
        both parties.
    </div>

    <div class="section-title">12.  Background Verification</div>
    <div class="paragraph">
        This internship offer is subject to successful background verification, including educational qualifications,
        identity verification, and other records as required by company policy. Any material discrepancy may lead
        to withdrawal of this internship offer or termination of the internship without notice.
    </div>

    <div class="section-title">13.  Acceptance of Offer</div>
    <div class="paragraph">
        Please sign and return a scanned copy of this letter by
        <strong>{{ \Carbon\Carbon::parse($letter_date ?? now())->addDays(2)->format('d/m/Y') }}</strong>
        to confirm your acceptance. If not received by this date, the internship offer will be considered withdrawn.
    </div>

    <div class="paragraph" style="margin-top:20px;">
        We look forward to welcoming you to Techsprout AI Labs Pvt. Ltd. and are excited about the value you
        will bring to our growing team during your internship.
    </div>

    <div class="signature-section">
        <p style="margin-bottom:8px; font-weight: bold;">Warm regards,</p>
        <div style="margin-bottom: 8px;">
            <img src="data:image/png;base64,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" style="width: 120pt; height: 37.5pt; display: block;" />
        </div>
        <strong style="color: #000000;">Vishwanath Srirangam</strong><br>
        <span style="color: #000000;">Founder &amp; CEO</span><br>
        <div class="address-block">
            Techsprout AI Labs Pvt. Ltd.<br>
            501, Manjeera Majestic Commercial,<br>
            KPHB, Hyderabad<br>
            <a href="https://www.techsprout.ai">www.techsprout.ai</a>
        </div>
    </div>

</body>
</html>


{{-- ═══════════════════════════════════════════════════════════════
     PAID INTERNSHIP OFFER LETTER  (onboarding_type = intern)
     ═══════════════════════════════════════════════════════════════ --}}
@elseif($candidate->onboarding_type === 'intern')
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Internship Offer Letter – {{ $candidate->candidate_name }}</title>
    <style>
        @page {
            margin: 125px 37pt 60px 37pt;
        }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11pt;
            color: #000000;
            line-height: 1.45;
        }
        /* ── Fixed repeating header on every page ── */
        .header {
            position: fixed;
            top: -100px;
            left: 0;
            right: 0;
            height: 80px;
            border-bottom: 1.5pt solid #28326e;
            padding-bottom: 8px;
        }
        .header table { width: 100%; border-collapse: collapse; }
        .header td   { padding: 0; vertical-align: top; }
        .company-address {
            font-size: 10pt;
            color: #28326e;
            text-align: right;
            line-height: 1.35;
        }
        .company-address a { color: #28326e; text-decoration: none; font-weight: normal; }

        /* ── Body ── */
        .paragraph    { margin-bottom: 8px; text-align: justify; }
        .section-title {
            font-size: 11pt;
            font-weight: bold;
            margin-top: 12px;
            margin-bottom: 3px;
            color: #000000;
        }
        .page-break   { page-break-after: always; }

        /* ── Signature ── */
        .signature-section { margin-top: 25px; page-break-inside: avoid; }
        .address-block { font-size: 11pt; color: #000000; line-height: 1.4; margin-top: 10px; }
        .address-block a { color: #000000; text-decoration: none; font-weight: normal; }
    </style>
</head>
<body>

    {{-- ── Repeating page header ── --}}
    <div class="header">
        <table>
            <tr>
                <td style="text-align: left;">
                    <img src="data:image/jpeg;base64,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" style="width: 238pt; height: 76.5pt; display: block;" />
                </td>
                <td class="company-address">
                    <span style="font-weight: bold; display: block; margin-bottom: 3px;">Techsprout AI Labs Pvt. Ltd</span>
                    501, Manjeera Majestic Commercial,<br>
                    JNTU Road, KPHB, Hyderabad.<br>
                    <a href="https://www.techsprout.ai">www.techsprout.ai</a>
                </td>
            </tr>
        </table>
    </div>

    {{-- ══════════════════ PAGE 1 ══════════════════ --}}
    <div style="text-align: right; font-family: 'DejaVu Sans'; font-size: 11pt; font-weight: bold; color: #28326e; margin-top: 10px; margin-bottom: 5px;">
        {{ strtoupper(\Carbon\Carbon::parse($letter_date ?? now())->format('d-M Y')) }}
    </div>
    <div style="font-family: 'DejaVu Sans'; font-size: 11pt; font-weight: bold; color: #28326e; margin-bottom: 15px; text-align: left;">
        DEAR {{ strtoupper($candidate->candidate_name) }}
    </div>

    <div class="paragraph">
        We are pleased to extend to you an offer for the position at <strong>Techsprout AI Labs Pvt. Ltd.</strong>,
        reporting at our office located in Hyderabad.
    </div>
    <div class="paragraph">
        This Internship offer is made based on your selection process and is subject to the terms and conditions
        outlined below. Your internship will be governed by the policies and guidelines of Techsprout AI Labs Pvt. Ltd.
    </div>

    <div class="section-title">1. Joining Date</div>
    <div class="paragraph">
        You will be designated as a <strong>{{ $candidate->position }}</strong>, and your internship will commence from
        <strong>{{ \Carbon\Carbon::parse($candidate->joining_date)->format('d/m/Y') }}</strong>
        for a period of <strong>{{ $candidate->duration ?? '3 months' }}</strong>.
    </div>
    <div class="paragraph">
        This is a full-time internship engagement with Techsprout AI Labs Pvt. Ltd. and does not constitute
        permanent employment. Upon successful completion of the internship and based on performance and business
        requirements, you may be considered for a full-time role.
    </div>

    <div class="section-title">2. Stipend</div>
    <div class="paragraph">
        You will receive a stipend of <strong>&#8377;{{ number_format((float)($candidate->ctc ?? 0)) }}</strong>
        per month during the internship period. Applicable statutory deductions, if any, will be made as per
        prevailing laws. The stipend structure details will be shared separately.
    </div>

    <div class="section-title">3. Evaluation &amp; Future Opportunities</div>
    <div class="paragraph">
        Your performance, conduct, learning progress, and contribution during the internship will be evaluated
        periodically. Based on your overall performance and company requirements, Techsprout AI Labs Pvt. Ltd.
        may consider you for future employment opportunities.
    </div>

    <div class="section-title">4. Notice Period &amp; Termination</div>
    <div class="paragraph">
        Either party may terminate the internship by providing 7 days' written notice. Techsprout AI Labs Pvt. Ltd.
        reserves the right to terminate the internship immediately, without notice or compensation, in cases of
        misconduct, breach of confidentiality, falsification of documents, violation of company policies, or
        unsatisfactory performance.
    </div>

    <div class="page-break"></div>

    {{-- ══════════════════ PAGE 2 ══════════════════ --}}
    <div class="section-title" style="margin-top:10px;">5. Confidentiality &amp; Intellectual Property</div>
    <div class="paragraph">
        You shall maintain strict confidentiality of all data, business information, client details,
        software code, documentation, student information, and business strategies of Techsprout AI Labs during
        the course of your internship.
    </div>
    <div class="paragraph">
        All work products, materials, intellectual property, documentation, software, designs, or content developed
        by you during the course of your internship shall remain the sole property of Techsprout AI Labs Pvt. Ltd.
    </div>

    <div class="section-title">6. Non-Compete &amp; Professional Ethics</div>
    <div class="paragraph">
        During your internship and for a period of 3 months post completion of the internship, you shall not engage
        in any activity, internship, assignment, or service that directly competes with the business interests or
        product offerings of Techsprout AI Labs Pvt. Ltd.
    </div>
    <div class="paragraph">
        You are expected to maintain the highest standards of professional conduct, ethical behavior, and
        communication with internal teams, clients, and stakeholders.
    </div>

    <div class="section-title">7. Termination of Engagement</div>
    <div class="paragraph">
        Either party may terminate this internship with 7 days' written notice or stipend in lieu of such notice.
        Techsprout AI Labs Pvt. Ltd. reserves the right to terminate the internship, without notice or compensation,
        immediately in cases of misconduct, breach of confidentiality, violation of company policies, or actions
        detrimental to the organization.
    </div>

    <div class="section-title">8. Dispute Resolution</div>
    <div class="paragraph">
        Any dispute arising from this internship shall be subject to arbitration in Hyderabad, governed by the
        laws of India. The decision of the appointed arbitrator shall be final and binding on both parties.
    </div>

    <div class="section-title">9. Acceptance of Offer</div>
    <div class="paragraph">
        Please sign and return a scanned copy of this letter by
        <strong>{{ \Carbon\Carbon::parse($letter_date ?? now())->addDays(2)->format('d-m-Y') }}</strong>
        to confirm your acceptance. Failure to do so within the given timeframe will render this internship offer void.
    </div>

    <div class="page-break"></div>

    {{-- ══════════════════ PAGE 3 ══════════════════ --}}
    <div class="section-title" style="margin-top:10px;">10.  Data Security &amp;  IT Assets</div>
    <div class="paragraph">
        If provided with a laptop, email access, or access to proprietary systems, you shall be responsible for
        maintaining data security and complying with the company's IT usage policies. All company-issued equipment
        must be returned in good condition upon completion of the internship.
    </div>

    <div class="section-title">11. Dispute Resolution s Jurisdiction</div>
    <div class="paragraph">
        Any dispute arising from this agreement or your internship shall be subject to arbitration in Hyderabad,
        governed by the laws of India. The decision of the appointed arbitrator shall be final and binding on
        both parties.
    </div>

    <div class="section-title">12.  Background Verification</div>
    <div class="paragraph">
        This internship offer is subject to successful background verification, including educational qualifications,
        identity verification, and other records as required by company policy. Any material discrepancy may lead
        to withdrawal of this internship offer or termination of the internship without notice.
    </div>

    <div class="section-title">13.  Acceptance of Offer</div>
    <div class="paragraph">
        Please sign and return a scanned copy of this letter by
        <strong>{{ \Carbon\Carbon::parse($letter_date ?? now())->addDays(2)->format('d/m/Y') }}</strong>
        to confirm your acceptance. If not received by this date, the internship offer will be considered withdrawn.
    </div>

    <div class="paragraph" style="margin-top:20px;">
        We look forward to welcoming you to Techsprout AI Labs Pvt. Ltd. and are excited about the value you
        will bring during your internship with our growing team.
    </div>

    <div class="signature-section">
        <p style="margin-bottom:8px; font-weight: bold;">Warm regards,</p>
        <div style="margin-bottom: 8px;">
            <img src="data:image/png;base64,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" style="width: 120pt; height: 37.5pt; display: block;" />
        </div>
        <strong style="color: #000000;">Vishwanath Srirangam</strong><br>
        <span style="color: #000000;">Founder &amp; CEO</span><br>
        <div class="address-block">
            Techsprout AI Labs Pvt. Ltd.<br>
            501, Manjeera Majestic Commercial,<br>
            KPHB, Hyderabad<br>
            <a href="https://www.techsprout.ai">www.techsprout.ai</a>
        </div>
    </div>

</body>
</html>


{{-- ═══════════════════════════════════════════════════════════════
     FULL-TIME EMPLOYMENT OFFER LETTER  (onboarding_type = full_time)
     ═══════════════════════════════════════════════════════════════ --}}
@else
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Offer of Employment – {{ $candidate->candidate_name }}</title>
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
        .header table { width: 100%; border: none; }
        .header td    { border: none; padding: 0; }
        .company-name { font-size: 22px; font-weight: bold; color: #1e3a8a; }
        .letter-title {
            font-size: 18px;
            font-weight: bold;
            text-align: center;
            margin-bottom: 25px;
            text-transform: uppercase;
            color: #1e3a8a;
            letter-spacing: 0.5px;
        }
        .date         { margin-bottom: 20px; font-weight: 500; }
        .candidate-details {
            margin-bottom: 30px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 15px;
        }
        .candidate-details table { width: 100%; border-collapse: collapse; }
        .candidate-details td    { border: none; padding: 4px 8px; }
        .candidate-details td.label { font-weight: bold; color: #64748b; width: 150px; }
        .highlight-box {
            background-color: #eff6ff;
            border-left: 4px solid #3b82f6;
            padding: 12px 16px;
            margin-top: 15px;
            margin-bottom: 15px;
            border-radius: 0 8px 8px 0;
        }
        .sign-off  { margin-top: 40px; }
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
        <table><tr>
            <td>
                <div class="company-name">Techsprout AI Labs</div>
                <div style="font-size:11px;color:#64748b;">Human Resources Division</div>
            </td>
            <td style="text-align:right;">
                <div style="font-size:14px;font-weight:bold;color:#3b82f6;">OFFICIAL LETTER</div>
            </td>
        </tr></table>
    </div>

    <div class="date">Date: {{ \Carbon\Carbon::parse($letter_date ?? now())->format('F d, Y') }}</div>

    <div class="candidate-details">
        <table>
            <tr><td class="label">Candidate Name:</td><td>{{ $candidate->candidate_name }}</td></tr>
            <tr><td class="label">Email Address:</td> <td>{{ $candidate->email }}</td></tr>
            <tr><td class="label">Proposed Position:</td><td>{{ $candidate->position }}</td></tr>
            <tr><td class="label">Department:</td>    <td>{{ $candidate->department }}</td></tr>
            <tr>
                <td class="label">Proposed CTC:</td>
                <td>&#8377;{{ number_format((float)($candidate->ctc ?? 0), 2) }} / Year</td>
            </tr>
        </table>
    </div>

    <div class="letter-title">Letter of Offer</div>

    <p>Dear {{ $candidate->candidate_name }},</p>

    <p>
        Following our recent discussions, we are pleased to offer you the position of
        <strong>{{ $candidate->position }}</strong> in the <strong>{{ $candidate->department }}</strong>
        department at Techsprout AI Labs. We are excited about the prospect of you joining our team and
        contributing to our mutual success.
    </p>

    @if(!empty($content))
        <p>{!! nl2br(e($content)) !!}</p>
    @else
        <p>
            Your employment will be subject to the terms and conditions outlined in the official employment agreement.
            Your scheduled start date will be
            <strong>{{ \Carbon\Carbon::parse($candidate->joining_date)->format('F d, Y') }}.</strong>
        </p>
    @endif

    <div class="highlight-box">
        <strong>Important Joining Instructions:</strong><br>
        Please bring a copy of your PAN card, Aadhaar card, educational certificates, and previous
        experience/relieving letters on your date of joining for the onboarding document verification process.
    </div>

    <p>
        To accept this offer, please sign and return the duplicate copy of this letter within 3 business days,
        failing which this offer shall stand cancelled.
    </p>

    <div class="sign-off">
        <p>Yours sincerely,</p>
        <div style="font-weight:bold;color:#1e3a8a;margin-top:40px;">Human Resources Team</div>
        <p style="font-size:12px;color:#64748b;margin-top:5px;">Techsprout AI Labs Pvt. Ltd.</p>
    </div>

    <div class="footer">This is a system-generated offer letter from Techsprout AI Labs.</div>
</body>
</html>
@endif
