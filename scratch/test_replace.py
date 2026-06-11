import re
import os

seeder_path = r"d:\internship\hr-panel\hr-portal-api\database\seeders\MailTemplateSeeder.php"
scratch_dir = r"d:\internship\hr-panel\hr-portal-api\scratch"
original_seeder_path = os.path.join(scratch_dir, "seeder_original.php")

with open(original_seeder_path, "r", encoding="utf-16") as f:
    code = f.read()

# 1. Read the base64 files
with open(os.path.join(scratch_dir, "header_base64.txt"), "r", encoding="utf-8") as f:
    header_base64 = f.read().strip()

with open(os.path.join(scratch_dir, "footer_base64.txt"), "r", encoding="utf-8") as f:
    footer_base64 = f.read().strip()

# 2. Extract logo base64
pos_free = code.find("'template_name' => 'free_internship_offer_letter'")
logo_match = re.search(r'data:image/jpeg;base64,([a-zA-Z0-9+/=\s\r\n]+)', code[pos_free:pos_free+50000])
if not logo_match:
    raise ValueError("Could not find logo base64 in free_internship_offer_letter body!")
logo_base64 = re.sub(r'\s+', '', logo_match.group(1))

def extract_body_matter(template_name):
    pos = code.find(f"'{template_name}'")
    if pos == -1:
        raise ValueError(f"Template {template_name} not found!")
    
    body_start = code.find("'body' => '", pos)
    body_end = code.find("',\n                'style' => '", body_start)
    if body_end == -1:
        body_end = code.find("',\r\n                'style' => '", body_start)
    if body_start == -1 or body_end == -1:
        raise ValueError(f"Body start/end not found for {template_name}!")
    body = code[body_start + 11:body_end]
    
    # Clean trailing slash escapes if present from previous writes
    date_pos = body.find("Carbon")
    div_start = body.rfind("<div", 0, date_pos)
    if div_start == -1:
        div_start = body.find("<div", date_pos)
        
    matter = body[div_start:]
    
    # Strip trailing content-body closing div if present
    open_divs = matter.count("<div")
    close_divs = matter.count("</div>")
    if close_divs > open_divs:
        last_div = matter.rfind("</div>")
        if last_div != -1:
            matter = matter[:last_div] + matter[last_div+6:]
            
    return matter.strip()

free_matter = extract_body_matter("free_internship_offer_letter")
free_matter = re.sub(
    r'(\{\{\s*)([^\}]+format\(\\\'(?:d-F Y|d-M Y)\\\'\))(\s*\}\})',
    r'\1strtoupper(\2)\3',
    free_matter
)
free_matter = free_matter.replace("format(\\'d-F Y\\')", "format(\\'d-M Y\\')")

paid_matter = extract_body_matter("paid_internship_offer_letter")

full_time_matter = paid_matter
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
full_time_matter = re.sub(
    r"Either\s+party\s+may\s+terminate\s+the\s+internship\s+by\s+providing\s+7\s+days\\'\s+written\s+notice\.\s+Techsprout\s+AI\s+Labs\s+Pvt\.\s+Ltd\.\s+reserves\s+the\s+right\s+to\s+terminate\s+the\s+internship\s+immediately,\s+without\s+notice\s+or\s+compensation,\s+in\s+cases\s+of\s*misconduct,\s+breach\s+of\s+confidentiality,\s+falsification\s+of\s+documents,\s+violation\s+of\s+company\s+policies,\s+or\s*unsatisfactory\s+performance\.",
    "You will be on a probation period of 6 months from your date of joining. During this period, either party may terminate the employment by providing 15 days\\' written notice. Upon successful completion of the probation period, the notice period will be 30 days. Techsprout AI Labs Pvt. Ltd. reserves the right to terminate the employment immediately, without notice or compensation, in cases of misconduct, breach of confidentiality, falsification of documents, violation of company policies, or unsatisfactory performance.",
    full_time_matter
)
full_time_matter = re.sub(
    r"During\s+your\s+internship\s+and\s+for\s+a\s+period\s+of\s+3\s+months\s+post\s+completion\s+of\s+the\s+internship,\s+you\s+shall\s+not\s+engage",
    "During your employment and for a period of 6 months post completion of the employment, you shall not engage",
    full_time_matter
)
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
full_time_matter = re.sub(r'\{\{\s*\$stipend\s*\}\}', '__STIPEND_VAR__', full_time_matter)

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
full_time_matter = full_time_matter.replace("internship", "employment")
full_time_matter = full_time_matter.replace("Internship", "Employment")
full_time_matter = full_time_matter.replace("stipend", "salary")
full_time_matter = full_time_matter.replace("Stipend", "Salary")
full_time_matter = full_time_matter.replace('__STIPEND_VAR__', '{{$stipend}}')

def clean_spacing(matter):
    matter = re.sub(
        r'margin-top:\s*\d+(?:px|pt)?;\s*margin-bottom:\s*\d+(?:px|pt)?;',
        'margin-top: 15.6pt; margin-bottom: 3.0pt;',
        matter
    )
    matter = re.sub(
        r'margin-bottom:\s*(?:15|20)(?:px|pt);',
        'margin-bottom: 18.0pt;',
        matter
    )
    matter = matter.replace('<div class="page-break"></div>', '')
    matter = matter.replace('<div class="page-break" style="page-break-before: always;"></div>', '')
    matter = matter.replace("font-family: 'DejaVu Sans';", "font-family: 'Inter';")
    matter = matter.replace("font-family:\"DejaVu Sans\";", "font-family:\"Inter\";")
    matter = matter.replace("font-family:DejaVu Sans;", "font-family:Inter;")
    
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
    return matter

free_matter = clean_spacing(free_matter)
paid_matter = clean_spacing(paid_matter)
full_time_matter = clean_spacing(full_time_matter)

exit_body_raw = extract_body_matter("exit_relieving_letter")
header_start = exit_body_raw.find('<div class="header">')
if header_start != -1:
    table_end = exit_body_raw.find("</table>", header_start)
    header_end = exit_body_raw.find("</div>", table_end)
    exit_matter = exit_body_raw[header_end + 6:].strip()
else:
    exit_matter = exit_body_raw

exit_matter = '<div class="letter-title">EXPERIENCE & RELIEVING LETTER</div>\n\n' + exit_matter
exit_matter = exit_matter.replace("<p>", '<p class="paragraph">')
exit_matter = exit_matter.replace("{{date}}", "{{$date}}")
exit_matter = exit_matter.replace("{{salutation}}", "{{$salutation}}")
exit_matter = exit_matter.replace("{{employee_name}}", "{{$employee_name}}")
exit_matter = exit_matter.replace("{{company_name}}", "{{$company_name}}")
exit_matter = exit_matter.replace("{{designation}}", "{{$designation}}")
exit_matter = exit_matter.replace("{{last_working_day}}", "{{$last_working_day}}")
exit_matter = exit_matter.replace("{{employee_code}}", "{{$employee_code}}")
exit_matter = exit_matter.replace("<p class=\"paragraph\">Yours sincerely,</p>", "")
exit_matter = exit_matter.replace("<p>Yours sincerely,</p>", "")
exit_matter = clean_spacing(exit_matter)

common_style = "/* common */"
full_time_style = "/* ft */"
exit_style = "/* exit */"

def build_body_html(logo, header, footer, matter):
    return f"LOGO:{logo[:10]}... HEADER:{header[:10]}... FOOTER:{footer[:10]}... MATTER:{matter}"

new_code = code

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
        
    def php_escape(text):
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

try:
    print("Rebuilding free...")
    new_code = replace_template_in_code(new_code, "free_internship_offer_letter", "FREE_BODY", common_style)
    print("Rebuilding paid...")
    new_code = replace_template_in_code(new_code, "paid_internship_offer_letter", "PAID_BODY", common_style)
    print("Rebuilding full_time...")
    new_code = replace_template_in_code(new_code, "full_time_offer_letter", "FT_BODY", full_time_style)
    print("Rebuilding exit...")
    new_code = replace_template_in_code(new_code, "exit_relieving_letter", "EXIT_BODY", exit_style)
    print("All success!")
except Exception as e:
    print("Error occurred:", e)
