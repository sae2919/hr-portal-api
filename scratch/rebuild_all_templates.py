import re
import os

seeder_path = r"d:\internship\hr-panel\hr-portal-api\database\seeders\MailTemplateSeeder.php"
scratch_dir = r"d:\internship\hr-panel\hr-portal-api\scratch"

original_seeder_path = os.path.join(scratch_dir, "seeder_original.php")
try:
    with open(original_seeder_path, "r", encoding="utf-8") as f:
        code = f.read()
except UnicodeDecodeError:
    with open(original_seeder_path, "r", encoding="utf-16") as f:
        code = f.read()

# 1. Read the base64 files
with open(os.path.join(scratch_dir, "header_base64.txt"), "r", encoding="utf-8") as f:
    header_base64 = f.read().strip()

with open(os.path.join(scratch_dir, "footer_base64.txt"), "r", encoding="utf-8") as f:
    footer_base64 = f.read().strip()

with open(os.path.join(scratch_dir, "signature_base64.txt"), "r", encoding="utf-8") as f:
    signature_base64 = f.read().strip()

# 2. Extract logo base64
pos_free = code.find("'template_name' => 'free_internship_offer_letter'")
logo_match = re.search(r'data:image/jpeg;base64,([a-zA-Z0-9+/=\s\r\n]+)', code[pos_free:pos_free+50000])
if not logo_match:
    raise ValueError("Could not find logo base64 in free_internship_offer_letter body!")
logo_base64 = re.sub(r'\s+', '', logo_match.group(1))
print(f"Extracted logo base64 (length={len(logo_base64)})")

# 3. Extract matter for the three templates
def extract_body_matter(template_name):
    pos = code.find(f"'{template_name}'")
    if pos == -1:
        raise ValueError(f"Template {template_name} not found!")
    
    body_start = code.find("'body' => '", pos)
    body_end = code.find("',\n                'style' => '", body_start)
    if body_end == -1:
        body_end = code.find("',\r\n                'style' => '", body_start)
    body = code[body_start + 11:body_end]
    
    # Clean trailing slash escapes if present from previous writes
    # Find start of date block
    date_pos = body.find("Carbon")
    if date_pos != -1:
        div_start = body.rfind("<div", 0, date_pos)
        if div_start == -1:
            div_start = body.find("<div", date_pos)
        matter = body[div_start:]
    else:
        # For templates without Carbon, we don't skip to a date block
        # We just want the matter after the header table
        header_start = body.find('<div class="header">')
        if header_start != -1:
            table_end = body.find("</table>", header_start)
            header_end = body.find("</div>", table_end)
            matter = body[header_end + 6:].strip()
        else:
            matter = body
    
    # Strip trailing content-body closing div if present
    open_divs = matter.count("<div")
    close_divs = matter.count("</div>")
    if close_divs > open_divs:
        last_div = matter.rfind("</div>")
        if last_div != -1:
            matter = matter[:last_div] + matter[last_div+6:]
            
    return matter.strip()

free_matter = extract_body_matter("free_internship_offer_letter")
# Replace format('d-F Y') with strtoupper(format('d-M Y'))
free_matter = re.sub(
    r'(\{\{\s*)([^\}]+format\(\\\'(?:d-F Y|d-M Y)\\\'\))(\s*\}\})',
    r'\1strtoupper(\2)\3',
    free_matter
)
free_matter = free_matter.replace("format(\\'d-F Y\\')", "format(\\'d-M Y\\')")

paid_matter = extract_body_matter("paid_internship_offer_letter")

# Derive full_time_matter by converting paid_matter (matching test_ft_matter.py logic)
full_time_matter = paid_matter

# 1. Section 1: Joining Date
full_time_matter = re.sub(
    r"and\s+your\s+internship\s+will\s+commence\s+from\s*<strong>\{\{\$joining_date\}\}</strong>\s*for\s+a\s+period\s+of\s*<strong>\{\{\$duration\}\}</strong>\.",
    "and your employment will commence from <strong>{{$joining_date}}</strong>.",
    full_time_matter
)
full_time_matter = re.sub(
    r"This\s+is\s+a\s+full-time\s+internship\s+engagement\s+with\s+Techsprout\s+AI\s+Labs\s+Pvt\.\s+Ltd\.\s+and\s+does\s+not\s+constitute\s*permanent\s+employment\.\s+Upon\s+successful\s+completion\s+of\s+the\s+internship\s+and\s+based\s+on\s+performance\s+and\s+business\s*requirements,\s+you\s+may\s+be\s+considered\s+for\s+a\s+full-time\s+role\.",
    "This is a permanent full-time employment engagement with Techsprout AI Labs Pvt. Ltd. and your employment will be subject to the terms and conditions outlined in the official employment agreement.",
    full_time_matter
)

# 2. Section 2: Stipend -> Salary
full_time_matter = re.sub(
    r"<div\s+class=\"section-title\">2\.\s+Stipend</div>",
    '<div class="section-title">2. Salary</div>',
    full_time_matter
)
full_time_matter = re.sub(
    r"You\s+will\s+receive\s+a\s+stipend\s+of\s+<strong>&#8377;\{\{\$stipend\}\}</strong>\s*per\s+month\s+during\s+the\s+internship\s+period\.\s+Applicable\s+statutory\s+deductions,\s+if\s+any,\s+will\s+be\s+made\s+as\s+per\s*prevailing\s+laws\.\s+The\s+stipend\s+structure\s+details\s+will\s+be\s+shared\s+separately\.",
    "You will receive a CTC of <strong>&#8377;{{$stipend}}</strong> per annum during your employment period. Applicable statutory deductions, if any, will be made as per prevailing laws. The salary structure details will be shared separately.",
    full_time_matter
)

# 3. Section 4: Notice Period & Termination
full_time_matter = re.sub(
    r"Either\s+party\s+may\s+terminate\s+the\s+internship\s+by\s+providing\s+7\s+days\\'\s+written\s+notice\.\s+Techsprout\s+AI\s+Labs\s+Pvt\.\s+Ltd\.\s+reserves\s+the\s+right\s+to\s+terminate\s+the\s+internship\s+immediately,\s+without\s+notice\s+or\s+compensation,\s+in\s+cases\s+of\s*misconduct,\s+breach\s+of\s+confidentiality,\s+falsification\s+of\s+documents,\s+violation\s+of\s+company\s+policies,\s+or\s*unsatisfactory\s+performance\.",
    "You will be on a probation period of 6 months from your date of joining. During this period, either party may terminate the employment by providing 15 days\\' written notice. Upon successful completion of the probation period, the notice period will be 30 days. Techsprout AI Labs Pvt. Ltd. reserves the right to terminate the employment immediately, without notice or compensation, in cases of misconduct, breach of confidentiality, falsification of documents, violation of company policies, or unsatisfactory performance.",
    full_time_matter
)

# 4. Section 6: Non-Compete & Professional Ethics (3 months -> 6 months)
full_time_matter = re.sub(
    r"During\s+your\s+internship\s+and\s+for\s+a\s+period\s+of\s+3\s+months\s+post\s+completion\s+of\s+the\s+internship,\s+you\s+shall\s+not\s+engage",
    "During your employment and for a period of 6 months post completion of the employment, you shall not engage",
    full_time_matter
)

# 5. Section 7: Termination of Engagement -> Termination of Employment
full_time_matter = re.sub(
    r"<div\s+class=\"section-title\">7\.\s+Termination\s+of\s+Engagement</div>",
    '<div class="section-title">7. Termination of Employment</div>',
    full_time_matter
)
full_time_matter = re.sub(
    r"Either\s+party\s+may\s+terminate\s+this\s+internship\s+with\s+7\s+days\\'\s+written\s+notice\s+or\s+stipend\s+in\s+lieu\s+of\s+such\s+notice\.\s*Techsprout\s+AI\s+Labs\s+Pvt\.\s+Ltd\.\s+reserves\s+the\s+right\s+to\s+terminate\s+the\s+internship,\s+without\s+notice\s+or\s+compensation,\s*immediately\s+in\s+cases\s+of\s+misconduct,\s+breach\s+of\s+confidentiality,\s+violation\s+of\s+company\s+policies,\s+or\s+actions\s*detrimental\s+to\s+the\s+organization\.",
    "Either party may terminate this employment with 30 days\\' written notice (or 15 days during probation) or salary in lieu of such notice. Techsprout AI Labs Pvt. Ltd. reserves the right to terminate the employment, without notice or compensation, immediately in cases of misconduct, breach of confidentiality, violation of company policies, or actions detrimental to the organization.",
    full_time_matter
)

# 6. Protect variable name
full_time_matter = re.sub(r'\{\{\s*\$stipend\s*\}\}', '__STIPEND_VAR__', full_time_matter)

# 7. General replacements
full_time_matter = full_time_matter.replace("This Internship offer", "This Employment offer")
full_time_matter = full_time_matter.replace("Your internship will be governed", "Your employment will be governed")
full_time_matter = full_time_matter.replace("during your internship", "during your employment")
full_time_matter = full_time_matter.replace("during the course of your internship", "during the course of your employment")
full_time_matter = full_time_matter.replace("post completion of the internship", "post completion of the employment")
full_time_matter = full_time_matter.replace("upon completion of the internship", "upon completion of the employment")
full_time_matter = full_time_matter.replace("or your internship shall", "or your employment shall")
full_time_matter = full_time_matter.replace("the internship offer will be considered withdrawn", "the employment offer will be considered withdrawn")
full_time_matter = full_time_matter.replace("future employment opportunities", "future growth and promotion opportunities")
full_time_matter = full_time_matter.replace("arising from this internship", "arising from this employment")
full_time_matter = full_time_matter.replace("render this internship offer void", "render this employment offer void")
full_time_matter = full_time_matter.replace("This internship offer is subject", "This employment offer is subject")
full_time_matter = full_time_matter.replace("withdrawal of this internship offer or termination of the internship without notice", "withdrawal of this employment offer or termination of the employment without notice")

# General words
full_time_matter = full_time_matter.replace("internship", "employment")
full_time_matter = full_time_matter.replace("Internship", "Employment")
full_time_matter = full_time_matter.replace("stipend", "salary")
full_time_matter = full_time_matter.replace("Stipend", "Salary")

# 8. Restore variable name
full_time_matter = full_time_matter.replace('__STIPEND_VAR__', '{{$stipend}}')



# 4. Clean up inline spacing styles in the extracted matters to match the reference PDF exactly
def clean_spacing(matter):
    # Fix date block inline styles to match reference Y=135.0
    matter = re.sub(
        r'margin-top:\s*\d+(?:px|pt)?;\s*margin-bottom:\s*\d+(?:px|pt)?;',
        'margin-top: 0pt; margin-bottom: 3.0pt;',
        matter
    )
    # Fix DEAR block inline styles
    matter = re.sub(
        r'margin-bottom:\s*(?:15|20)(?:px|pt);',
        'margin-bottom: 18.0pt;',
        matter
    )
    # Remove explicit page-break divs
    matter = matter.replace('<div class="page-break"></div>', '')
    matter = matter.replace('<div class="page-break" style="page-break-before: always;"></div>', '')
    
    # Replace DejaVu Sans with Inter for consistency, accounting for PHP seeder escaped quotes
    matter = matter.replace("font-family: 'DejaVu Sans';", "font-family: 'Inter';")
    matter = matter.replace("font-family:\"DejaVu Sans\";", "font-family:\"Inter\";")
    matter = matter.replace("font-family:DejaVu Sans;", "font-family:Inter;")
    matter = matter.replace("font-family: \\'DejaVu Sans\\';", "font-family: \\'Inter\\';")
    matter = matter.replace("font-family:\\'DejaVu Sans\\';", "font-family: \\'Inter\\';")
    matter = matter.replace("font-family: \\'Times New Roman\\', Times, serif;", "font-family: \\'Inter\\';")
    matter = matter.replace("font-family:\\'Times New Roman\\', Times, serif;", "font-family: \\'Inter\\';")
    
    # Remove font-weight: bold from date block in standard templates so it renders as regular weight (Inter-Regular)
    matter = matter.replace("font-weight: bold; color: #28326e; margin-top: 0pt;", "font-weight: normal; color: #28326e; margin-top: 0pt;")
    matter = matter.replace("font-weight: bold; color: #28326e; margin-top: 15.6pt;", "font-weight: normal; color: #28326e; margin-top: 0pt;")
    
    # Add page-break-before to Section 5 and Section 10 titles
    matter = re.sub(
        r'<div class="section-title"\s*(style="[^"]*")?>5\.\s*Confidentiality',
        r'<div class="section-title page-break-before" \1>5. Confidentiality',
        matter
    )
    matter = re.sub(
        r'<div class="section-title"\s*(style="[^"]*")?>10\.\s*Data\s+Security',
        r'<div class="section-title page-break-before" \1>10.  Data Security',
        matter
    )
    
    # Make sure we use exact double spaces for Page 3 section titles
    # Since we set white-space: pre-wrap in CSS, this will render correctly with standard spaces
    matter = matter.replace("10. Data Security &amp; IT Assets", "10.  Data Security &amp;  IT Assets")
    matter = matter.replace("12. Background Verification", "12.  Background Verification")
    matter = matter.replace("13. Acceptance of Offer", "13.  Acceptance of Offer")
    
    # Wrap Page 1 content in a div with left margin of 13.1pt (so left coordinate is 37 + 13.1 = 50.1pt)
    # The wrapper starts right before the DEAR line and ends right before the Section 5 title
    dear_pos = matter.find("DEAR")
    if dear_pos != -1:
        div_start = matter.rfind("<div", 0, dear_pos)
        if div_start != -1:
            matter = matter[:div_start] + '<div style="margin-left: 13.1pt;">' + matter[div_start:]
            
    # Now find where Section 5 title starts to close the Page 1 wrapper
    sec5_pos = matter.find("5. Confidentiality")
    if sec5_pos != -1:
        div_start = matter.rfind("<div", 0, sec5_pos)
        if div_start != -1:
            matter = matter[:div_start] + '</div>' + matter[div_start:]
            
    # Wrap Page 3 content in a div with right margin of 23.6pt (so right edge is 558.6 - 24 = 534.6pt)
    # The wrapper starts right before the Section 10 title and ends at the end of the matter
    sec10_pos = matter.find("10.  Data Security")
    if sec10_pos != -1:
        div_start = matter.rfind("<div", 0, sec10_pos)
        if div_start != -1:
            matter = matter[:div_start] + '<div style="margin-right: 23.6pt;">' + matter[div_start:] + '</div>'

    return matter

free_matter = clean_spacing(free_matter)
paid_matter = clean_spacing(paid_matter)

# Add 'internal' to Section 5 to match the reference paid internship offer letter exactly
free_matter = free_matter.replace("confidentiality of all data", "confidentiality of all internal data")
paid_matter = paid_matter.replace("confidentiality of all data", "confidentiality of all internal data")

full_time_matter = clean_spacing(full_time_matter)

print("Constructing exit_relieving_letter body matter...")
exit_matter = """<div class="letter-title">EXPERIENCE & RELIEVING LETTER</div>

<div style="text-align: right; font-family: 'Times New Roman', Times, serif; font-size: 14.4pt; margin-bottom: 0pt;">
    {{$date}}
</div>

<div style="text-align: left; font-family: 'Times New Roman', Times, serif; font-size: 14.4pt; margin-bottom: 5pt;">
    {{$salutation}} {{$employee_name}},
</div>

<div class="subject">
    Sub : Experience Cum Relieving Letter
</div>

<p class="paragraph">
    This is to certify that <strong>{{$salutation}} {{$employee_name}}</strong> was associated with Techsprout AI Labs Pvt Ltd as <strong>{{$designation}}</strong> from <strong>{{$joining_date}}</strong> to <strong>{{$last_working_day}}</strong>.
</p>

<p class="paragraph">
    During this tenure, the employee worked and carried out assigned responsibilities professionally.
</p>

<p class="paragraph">
    We hereby confirm that <strong>{{$salutation}} {{$employee_name}}</strong> has been formally relieved from their services with Techsprout AI Labs Pvt Ltd, effective <strong>{{$last_working_day}}</strong>. All responsibilities have been duly completed and cleared.
</p>

<div class="service-record-title">
    Your Service Record is as follows:
</div>

<table class="service-record-table">
    <tr>
        <td class="label">Employee ID:</td>
        <td>{{$employee_code}}</td>
    </tr>
    <tr>
        <td class="label">Name:</td>
        <td>{{$employee_name}}</td>
    </tr>
    <tr>
        <td class="label">First Designation:</td>
        <td>{{$designation}}</td>
    </tr>
    <tr>
        <td class="label">Last Designation:</td>
        <td>{{$designation}}</td>
    </tr>
    <tr>
        <td class="label">Date of Joining:</td>
        <td>{{$joining_date}}</td>
    </tr>
    <tr>
        <td class="label">Date of Leaving:</td>
        <td>{{$last_working_day}}</td>
    </tr>
</table>

<p class="paragraph">
    We wish them all the best for their future endeavors.
</p>

<div class="sign-off">
    __SIGNATURE_IMAGE_TAG__
    <div class="name">Vishwanath Srirangam</div>
    <div class="title">Founder & CEO</div>
    <div class="company">Techsprout AI Labs Pvt Ltd</div>
</div>"""

# 5. Define styles for the templates
# Intern templates (free & paid) use the exact metrics matching the reference PDF
common_style = """@font-face {
            font-family: 'Inter';
            src: url('local-font://inter_normal_5a66f8ceb794f00a7f54c797a21283b3.ttf') format('truetype');
            font-weight: normal;
            font-style: normal;
        }
        @font-face {
            font-family: 'Inter';
            src: url('local-font://inter_bold_02384cdf43e3d24b76c57c66dfd114bf.ttf') format('truetype');
            font-weight: bold;
            font-style: normal;
        }
        @font-face {
            font-family: 'Montserrat';
            src: url('local-font://montserrat_normal_95c77d2830d640f06faa8261ded6db33.ttf') format('truetype');
            font-weight: normal;
            font-style: normal;
        }
        @font-face {
            font-family: 'Montserrat';
            src: url('local-font://montserrat_bold_251805f22f376c685d401bff71e8e1d4.ttf') format('truetype');
            font-weight: bold;
            font-style: normal;
        }
        @page {
            size: A4;
            margin: 0;
        }
        body {
            font-family: "Inter", "Montserrat", "DejaVu Sans", sans-serif;
            font-size: 11pt;
            color: #000000;
            line-height: 1.48;
            margin: 0;
            padding: 0;
        }
        /* ── Repeating Background Graphics ── */
        .header-bg {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 22pt;
            z-index: -100;
        }
        .footer-bg {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            height: 28.4pt;
            z-index: -100;
        }
        /* ── Header ── */
        .header {
            position: fixed;
            top: 0;
            left: 37pt;
            right: 37pt;
            height: 119.6pt;
            border-bottom: 1.5pt solid #28326e;
        }
        .header table { width: 100%; border-collapse: collapse; }
        .header td   { padding: 0; vertical-align: top; }
        .company-address {
            font-size: 10pt;
            color: #28326e;
            text-align: right;
            line-height: 1.3;
        }
        .company-address a { color: #28326e; text-decoration: underline; font-weight: normal; }

        /* ── Print Container Table ── */
        .print-table {
            width: 100%;
            border-collapse: collapse;
        }
        .header-space {
            height: 135pt;
        }
        .footer-space {
            height: 60pt;
        }
        .content-cell {
            padding-left: 0;
            padding-right: 0;
        }

        /* ── Content Body Wrapper ── */
        .content-body {
            margin-left: 37pt;
            margin-right: 37pt;
        }

        /* ── Paragraphs and Sections ── */
        .paragraph    { margin-bottom: 16.0pt; text-align: justify; line-height: 1.48; }
        .section-title {
            font-size: 11pt;
            font-weight: bold;
            margin-top: 16.0pt;
            margin-bottom: 16.0pt;
            color: #000000;
            line-height: 1.48;
            white-space: pre-wrap;
        }
        .page-break   { page-break-after: always; }
        .page-break-before { page-break-before: always; padding-top: 10pt; }

        /* ── Signature ── */
        .signature-section { margin-top: 25pt; page-break-inside: avoid; }
        .address-block { font-size: 11pt; color: #000000; line-height: 1.4; margin-top: 0pt; }
        .address-block a { color: #000000; text-decoration: none; font-weight: normal; }"""

# Full-time template uses slightly tighter spacing to accommodate the longer Section 4 probation text on Page 1
full_time_style = """@font-face {
            font-family: 'Inter';
            src: url('local-font://inter_normal_5a66f8ceb794f00a7f54c797a21283b3.ttf') format('truetype');
            font-weight: normal;
            font-style: normal;
        }
        @font-face {
            font-family: 'Inter';
            src: url('local-font://inter_bold_02384cdf43e3d24b76c57c66dfd114bf.ttf') format('truetype');
            font-weight: bold;
            font-style: normal;
        }
        @font-face {
            font-family: 'Montserrat';
            src: url('local-font://montserrat_normal_95c77d2830d640f06faa8261ded6db33.ttf') format('truetype');
            font-weight: normal;
            font-style: normal;
        }
        @font-face {
            font-family: 'Montserrat';
            src: url('local-font://montserrat_bold_251805f22f376c685d401bff71e8e1d4.ttf') format('truetype');
            font-weight: bold;
            font-style: normal;
        }
        @page {
            size: A4;
            margin: 0;
        }
        body {
            font-family: "Inter", "Montserrat", "DejaVu Sans", sans-serif;
            font-size: 11pt;
            color: #000000;
            line-height: 1.48;
            margin: 0;
            padding: 0;
        }
        /* ── Repeating Background Graphics ── */
        .header-bg {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 22pt;
            z-index: -100;
        }
        .footer-bg {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            height: 28.4pt;
            z-index: -100;
        }
        /* ── Header ── */
        .header {
            position: fixed;
            top: 0;
            left: 37pt;
            right: 37pt;
            height: 119.6pt;
            border-bottom: 1.5pt solid #28326e;
        }
        .header table { width: 100%; border-collapse: collapse; }
        .header td   { padding: 0; vertical-align: top; }
        .company-address {
            font-size: 10pt;
            color: #28326e;
            text-align: right;
            line-height: 1.3;
        }
        .company-address a { color: #28326e; text-decoration: underline; font-weight: normal; }

        /* ── Print Container Table ── */
        .print-table {
            width: 100%;
            border-collapse: collapse;
        }
        .header-space {
            height: 135pt;
        }
        .footer-space {
            height: 60pt;
        }
        .content-cell {
            padding-left: 0;
            padding-right: 0;
        }

        /* ── Content Body Wrapper ── */
        .content-body {
            margin-left: 37pt;
            margin-right: 37pt;
        }

        /* ── Paragraphs and Sections ── */
        .paragraph    { margin-bottom: 16.0pt; text-align: justify; line-height: 1.48; }
        .section-title {
            font-size: 11pt;
            font-weight: bold;
            margin-top: 16.0pt;
            margin-bottom: 16.0pt;
            color: #000000;
            line-height: 1.48;
            white-space: pre-wrap;
        }
        .page-break   { page-break-after: always; }
        .page-break-before { page-break-before: always; padding-top: 10pt; }

        /* ── Signature ── */
        .signature-section { margin-top: 25pt; page-break-inside: avoid; }
        .address-block { font-size: 11pt; color: #000000; line-height: 1.4; margin-top: 0pt; }
        .address-block a { color: #000000; text-decoration: none; font-weight: normal; }"""

# Exit relieving template style inherits from common_style with layout specific additions
exit_style = """@font-face {
            font-family: 'Inter';
            src: url('local-font://inter_normal_5a66f8ceb794f00a7f54c797a21283b3.ttf') format('truetype');
            font-weight: normal;
            font-style: normal;
        }
        @font-face {
            font-family: 'Inter';
            src: url('local-font://inter_bold_02384cdf43e3d24b76c57c66dfd114bf.ttf') format('truetype');
            font-weight: bold;
            font-style: normal;
        }
        @font-face {
            font-family: 'Montserrat';
            src: url('local-font://montserrat_normal_95c77d2830d640f06faa8261ded6db33.ttf') format('truetype');
            font-weight: normal;
            font-style: normal;
        }
        @font-face {
            font-family: 'Montserrat';
            src: url('local-font://montserrat_bold_251805f22f376c685d401bff71e8e1d4.ttf') format('truetype');
            font-weight: bold;
            font-style: normal;
        }
        @page {
            size: A4;
            margin: 0;
        }
        body {
            font-family: "Times New Roman", Times, "DejaVu Serif", serif;
            font-size: 14.4pt;
            color: #000000;
            line-height: 1.4;
            margin: 0;
            padding: 0;
        }
        /* ── Repeating Background Graphics ── */
        .header-bg {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 22pt;
            z-index: -100;
        }
        .footer-bg {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            height: 28.4pt;
            z-index: -100;
        }
        /* ── Header ── */
        .header {
            position: fixed;
            top: 0;
            left: 37pt;
            right: 37pt;
            height: 119.6pt;
            border-bottom: 1.5pt solid #28326e;
            font-family: "Inter", "Montserrat", sans-serif;
        }
        .header table { width: 100%; border-collapse: collapse; }
        .header td   { padding: 0; vertical-align: top; }
        .company-address {
            font-family: "Inter", "Montserrat", sans-serif;
            font-size: 9.5pt;
            color: #28326e;
            text-align: right;
            line-height: 1.3;
        }
        .company-address a { color: #28326e; text-decoration: underline; font-weight: normal; }

        /* ── Print Container Table ── */
        .print-table {
            width: 100%;
            border-collapse: collapse;
        }
        .header-space {
            height: 120pt;
        }
        .footer-space {
            height: 50pt;
        }
        .content-cell {
            padding-left: 0;
            padding-right: 0;
        }

        /* ── Content Body Wrapper ── */
        .content-body {
            margin-left: 46.6pt;
            margin-right: 37pt;
        }

        /* ── Paragraphs and Sections ── */
        .paragraph    { margin-bottom: 12.0pt; text-align: justify; }
        
        /* ── Exit Relieving Specific Styles ── */
        .letter-title {
            font-family: "Times New Roman", Times, serif;
            font-size: 18pt;
            font-weight: bold;
            text-align: center;
            margin-top: 5pt;
            margin-bottom: 15pt;
            text-decoration: underline;
            color: #000000;
            letter-spacing: 0.5px;
        }
        .subject {
            font-weight: bold;
            margin-bottom: 8pt;
        }
        .service-record-title {
            font-weight: bold;
            text-decoration: underline;
            margin-top: 15pt;
            margin-bottom: 8pt;
        }
        .service-record-table {
            width: 100%;
            margin-left: 0pt;
            border-collapse: collapse;
            margin-bottom: 12pt;
        }
        .service-record-table td {
            border: none;
            padding: 3px 0;
            font-size: 14.4pt;
            line-height: 1.4;
        }
        .service-record-table td.label {
            font-weight: bold;
            width: 140pt;
            color: #000000;
        }
        .sign-off {
            margin-top: 20pt;
            line-height: 1.4;
            page-break-inside: avoid;
        }
        .sign-off .name {
            font-weight: bold;
            color: #000000;
        }
        .sign-off .title, .sign-off .company {
            color: #000000;
        }"""

payslip_body = """<!-- Company Branding Header Table -->
<table class="header-table">
    <tr>
        <td class="logo-cell">
            @if($logoPath)
                <img src="{{ $logoPath }}" class="logo" />
            @else
                <span style="font-size:24px; font-weight:bold; color:#1e3a8a;">TS</span>
            @endif
        </td>
        <td class="company-details-cell" style="padding-right: 60pt;">
            <div class="company-name">Techsprout AI Labs</div>
            <div class="company-address">8-2-293/82/A/787/1/4F/1, Road No36,4thFloor,JubileeHills,Hyderabad, Shaikpet, Telangana,India, 500033</div>
        </td>
    </tr>
</table>

<div class="payslip-title">
    Payslip for the month of {{ $monthName }} {{ $payroll->year }}
</div>

<!-- Employee Details Matrix -->
<table class="employee-details-table">
    <tr>
        <td class="col-cell" style="padding-right: 15pt;">
            <table style="width: 100%; border-collapse: collapse; border: none;">
                <tr>
                    <td class="info-label">Name:</td>
                    <td class="info-val">{{ $employee->first_name }} {{ $employee->last_name }}</td>
                </tr>
                <tr>
                    <td class="info-label">Joining Date:</td>
                    <td class="info-val">{{ $employee->joining_date ? $employee->joining_date->format('d M Y') : '-' }}</td>
                </tr>
                <tr>
                    <td class="info-label">Designation:</td>
                    <td class="info-val">{{ $employee->designation->title ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="info-label">Department:</td>
                    <td class="info-val">{{ $employee->department->name ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="info-label">Location:</td>
                    <td class="info-val">{{ $employee->city ?: 'Hyderabad' }}</td>
                </tr>
            </table>
        </td>
        <td class="col-cell" style="padding-left: 15pt;">
            <table style="width: 100%; border-collapse: collapse; border: none;">
                <tr>
                    <td class="info-label-right">Employee ID:</td>
                    <td class="info-val">{{ $employee->employee_code }}</td>
                </tr>
                <tr>
                    <td class="info-label-right">Bank Name:</td>
                    <td class="info-val">{{ $bankName }}</td>
                </tr>
                <tr>
                    <td class="info-label-right">Bank Account No:</td>
                    <td class="info-val">{{ $maskedAcc }}</td>
                </tr>
                <tr>
                    <td class="info-label-right">PAN Number:</td>
                    <td class="info-val">{{ $maskedPan }}</td>
                </tr>
                <tr>
                    <td class="info-label-right">Effective Work Days:</td>
                    <td class="info-val">{{ $payroll->present_days }}</td>
                </tr>
                <tr>
                    <td class="info-label-right">LOP:</td>
                    <td class="info-val">{{ (int)($payroll->lop_days ?? 0) }}</td>
                </tr>
            </table>
        </td>
    </tr>
</table>

<!-- Salary Breakdown Table -->
<table class="salary-table">
    <thead>
        <tr>
            <th style="width: 32%; text-align: left;">Earnings</th>
            <th style="width: 10%;">Master</th>
            <th style="width: 10%;">Actual</th>
            <th style="width: 35%; text-align: left;">Deductions</th>
            <th style="width: 13%;">Actual</th>
        </tr>
    </thead>
    <tbody>
        @for ($i = 0; $i < $maxRows; $i++)
            @php $left = $leftRows[$i]; $right = $rightRows[$i]; @endphp
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
        <tr class="total-row">
            @if(($employee->employment_type ?? null) === 'intern')
                <td>Total Earnings:INR.</td>
            @else
                <td>Total Earnings:INR.:</td>
            @endif
            <td class="amount">{{ number_format($masterGross, 2) }}</td>
            <td class="amount">{{ number_format($payroll->gross_salary, 2) }}</td>
            <td>Total Deductions:INR.</td>
            @if(($employee->employment_type ?? null) === 'intern')
                <td class="amount"></td>
            @else
                <td class="amount">{{ number_format($payroll->total_deductions + $lopDeduction, 2) }}</td>
            @endif
        </tr>
    </tbody>
</table>

<!-- Net Pay & Words -->
<div class="net-pay-section">
    <span class="net-pay-label">
        @if(($employee->employment_type ?? null) === 'intern')
            Net Pay for the month:
        @else
            Net Pay for the month
        @endif
    </span>
    <span class="net-pay-amount">{{ number_format($payroll->net_salary, 2) }}</span>
</div>
<div class="net-pay-words">({{ $netPayWords }})</div>

<!-- Bottom Separator Line -->
<div class="bottom-separator"></div>

<!-- Footer System Generated disclaimer -->
<div class="disclaimer-line">
    This is a system generated payslip and does not require a signature
</div>

<!-- Print Date -->
<div class="print-date-line">
    Print Date:{{ \Carbon\Carbon::now()->format('d M Y, h:i A') }}
</div>"""

payslip_style = """@page {
            size: A4;
            margin: 0;
        }
        body {
            font-family: 'Times New Roman', Times, 'DejaVu Serif', serif;
            font-size: 10pt;
            color: #000;
            line-height: 1.3;
            margin: 0 13pt;
            padding: 0;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
            border: none;
            margin-top: 24pt;
            margin-bottom: 2pt;
        }
        .header-table td {
            border: none;
            padding: 0;
        }
        .logo-cell {
            width: 120pt;
            vertical-align: top;
        }
        .logo {
            width: 120pt;
            height: 40pt;
            display: block;
        }
        .company-details-cell {
            text-align: center;
            vertical-align: top;
            padding-top: 5pt;
        }
        .company-name {
            font-size: 13pt;
            font-weight: bold;
            margin: 0 0 2pt 0;
            color: #000;
        }
        .company-address {
            font-size: 8.5pt;
            color: #000;
            margin: 0;
            line-height: 1.1;
        }
        .payslip-title {
            text-align: center;
            font-size: 11.3pt;
            font-weight: bold;
            margin: 0 0 5pt 0;
        }
        .employee-details-table {
            width: 100%;
            border-collapse: collapse;
            border: 0.75pt solid #000;
            margin-bottom: 4pt;
        }
        .employee-details-table td.col-cell {
            width: 50%;
            padding: 4pt 10pt;
            vertical-align: top;
            border: none;
        }
        .employee-details-table td.col-cell:first-child {
            border-right: 0.75pt solid #000;
        }
        .info-label {
            width: 35%;
            font-size: 10pt;
            padding: 0.8pt 0;
            text-align: left;
            line-height: 1.1;
        }
        .info-val {
            width: 65%;
            font-size: 10pt;
            padding: 0.8pt 0;
            text-align: left;
            line-height: 1.1;
        }
        .info-label-right {
            width: 45%;
            font-size: 9.8pt;
            padding: 0.8pt 0;
            text-align: left;
            line-height: 1.1;
        }
        .salary-table {
            width: 100%;
            border-collapse: collapse;
            border: 0.75pt solid #000;
            margin-bottom: 12pt;
        }
        .salary-table th {
            border-bottom: 0.75pt solid #000;
            border-left: none;
            padding: 7pt 8pt;
            font-weight: bold;
            font-size: 9pt;
            text-align: center;
        }
        .salary-table th:first-child {
            text-align: left;
        }
        .salary-table th:nth-child(4) {
            text-align: left;
            border-left: 0.75pt solid #000;
        }
        .salary-table td {
            border-left: none;
            padding: 3pt 8pt;
            font-size: 9pt;
            vertical-align: middle;
            line-height: 1.1;
        }
        .salary-table td:nth-child(4) {
            border-left: 0.75pt solid #000;
        }
        .salary-table td.amount {
            text-align: right;
        }
        .salary-table tr.total-row td {
            border-top: 0.75pt solid #000;
            font-weight: bold;
            padding: 4pt 8pt;
        }
        .net-pay-section {
            margin-top: 12pt;
            font-size: 10pt;
            line-height: 1.2;
        }
        .net-pay-label {
            font-size: 10pt;
        }
        .net-pay-amount {
            font-size: 9pt;
            font-weight: bold;
            margin-left: 5pt;
        }
        .net-pay-words {
            font-size: 9pt;
            font-weight: bold;
            font-style: italic;
            margin-top: 10pt;
            line-height: 1.2;
        }
        .bottom-separator {
            height: 0.75pt;
            background-color: #000000;
            margin-top: 10pt;
            margin-bottom: 2pt;
        }
        .disclaimer-line {
            text-align: center;
            font-size: 8pt;
            color: #000000;
            line-height: 1.2;
        }
        .print-date-line {
            position: absolute;
            bottom: 20pt;
            left: 18pt;
            font-size: 10pt;
            color: #000000;
        }"""

# 6. Build the template HTML body helper
def build_body_html(logo, header, footer, matter, is_exit=False):
    if is_exit:
        addr_block = f"""<div style="display: inline-block; text-align: left; font-family: 'Times New Roman', Times, serif; font-size: 10pt; color: #28326e; line-height: 1.3;">
                        <span style="font-family: 'Montserrat', sans-serif; font-weight: bold; display: block; margin-bottom: 3px;">Techsprout AI Labs Pvt. Ltd</span>
                        501, Manjeera Majestic Commercial,<br>
                        JNTU Road, KPHB, Hyderabad.<br>
                        <a href="https://www.techsprout.ai" style="color: #28326e; text-decoration: underline;">www.techsprout.ai</a>
                    </div>"""
    else:
        addr_block = f"""<div style="display: inline-block; text-align: left; font-family: 'Inter', sans-serif; font-size: 10pt; color: #28326e; line-height: 1.3;">
                        <span style="font-family: 'Montserrat', sans-serif; font-weight: bold; display: block; margin-bottom: 3px;">Techsprout AI Labs Pvt. Ltd</span>
                        501, Manjeera Majestic Commercial,<br>
                        JNTU Road, KPHB, Hyderabad.<br>
                        <a href="https://www.techsprout.ai" style="color: #28326e; text-decoration: underline;">www.techsprout.ai</a>
                    </div>"""
                    
    return f"""<div class="header-bg"><img src="data:image/png;base64,{header}" style="width: 100%; height: 100%; display: block;" /></div>
    <div class="footer-bg"><img src="data:image/png;base64,{footer}" style="width: 100%; height: 100%; display: block;" /></div>

    <div class="header">
        <table style="width: 100%; border-collapse: collapse; margin-top: 42.6pt; padding-top: 0pt;">
            <tr>
                <td style="text-align: left; padding: 0; vertical-align: top;">
                    <img src="data:image/jpeg;base64,{logo}" style="width: 238pt; height: 76.5pt; display: block;" />
                </td>
                <td class="company-address" style="text-align: right; padding: 0; padding-top: 0pt; vertical-align: top;">
                    {addr_block}
                </td>
            </tr>
        </table>
    </div>

    <table class="print-table">
        <thead>
            <tr>
                <td><div class="header-space"></div></td>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="content-cell">
                    <div class="content-body">
                        {matter}
                    </div>
                </td>
            </tr>
        </tbody>
    </table>"""

# 7. Update Seeder File Content
# We will do replacement for each template.
# Let's write a parser that scans the code and replaces the body and style for all three templates.
def replace_template_in_code(current_code, template_name, new_body, new_style):
    pos = current_code.find(f"'{template_name}'")
    if pos == -1:
        raise ValueError(f"Template {template_name} not found in code!")
        
    body_start = current_code.find("'body' => '", pos)
    if body_start == -1:
        raise ValueError(f"body_start not found for {template_name}")
        
    body_end = current_code.find("',\n                'style' => '", body_start)
    if body_end == -1:
        body_end = current_code.find("',\r\n                'style' => '", body_start)
    if body_end == -1:
        raise ValueError(f"body_end not found for {template_name}")
    
    style_start = current_code.find("'style' => '", body_end)
    if style_start == -1:
        raise ValueError(f"style_start not found for {template_name}")
        
    style_end = current_code.find("',\n                'active_status' => 1,", style_start)
    if style_end == -1:
        style_end = current_code.find("',\r\n                'active_status' => 1,", style_start)
    if style_end == -1:
        raise ValueError(f"style_end not found for {template_name}")
        
    # Replace body and style
    # Ensure quotes inside are correctly escaped for PHP single quote string
    # We will do this carefully. Since new_body and new_style are already formatted, we just insert them.
    # Note that in PHP seeder, single quotes are escaped: \'
    # We should make sure we escape single quotes in body if they are not already escaped.
    def php_escape(text):
        # We need to escape single quotes, but avoid double escaping
        # Let's search for any ' that is not preceded by a backslash \
        text_escaped = ""
        for i, char in enumerate(text):
            if char == "'":
                if i > 0 and text[i-1] == "\\":
                    text_escaped += char
                else:
                    text_escaped += "\\'"
            else:
                text_escaped += char
        return text_escaped

    escaped_body = php_escape(new_body)
    escaped_style = php_escape(new_style)
    
    res = current_code[:body_start + 11] + escaped_body + current_code[body_end:style_start + 12] + escaped_style + current_code[style_end:]
    return res

new_code = code
print("Rebuilding free_internship_offer_letter...")
new_code = replace_template_in_code(
    new_code,
    "free_internship_offer_letter",
    build_body_html(logo_base64, header_base64, footer_base64, free_matter),
    common_style
)

print("Rebuilding paid_internship_offer_letter...")
new_code = replace_template_in_code(
    new_code,
    "paid_internship_offer_letter",
    build_body_html(logo_base64, header_base64, footer_base64, paid_matter),
    common_style
)

print("Rebuilding full_time_offer_letter...")
new_code = replace_template_in_code(
    new_code,
    "full_time_offer_letter",
    build_body_html(logo_base64, header_base64, footer_base64, full_time_matter),
    full_time_style
)

print("Rebuilding exit_relieving_letter...")
exit_matter_with_sig = exit_matter.replace(
    "__SIGNATURE_IMAGE_TAG__",
    f'<div style="margin-bottom: 8px;"><img src="data:image/png;base64,{signature_base64}" style="width: 120pt; height: 37.5pt; display: block;" /></div>'
)
new_code = replace_template_in_code(
    new_code,
    "exit_relieving_letter",
    build_body_html(logo_base64, header_base64, footer_base64, exit_matter_with_sig, is_exit=True),
    exit_style
)

print("Rebuilding monthly_payslip_template...")
new_code = replace_template_in_code(
    new_code,
    "monthly_payslip_template",
    payslip_body,
    payslip_style
)

with open(seeder_path, "w", encoding="utf-8") as f:
    f.write(new_code)

print("MailTemplateSeeder.php successfully updated with exact reference design layout!")
