import re
import os

seeder_path = r"d:\internship\hr-panel\hr-portal-api\database\seeders\MailTemplateSeeder.php"
scratch_dir = r"d:\internship\hr-panel\hr-portal-api\scratch"
original_seeder_path = os.path.join(scratch_dir, "seeder_original.php")

with open(original_seeder_path, "r", encoding="utf-16") as f:
    code = f.read()

# 1. Read base64 files
with open(os.path.join(scratch_dir, "header_base64.txt"), "r", encoding="utf-8") as f:
    header_base64 = f.read().strip()

with open(os.path.join(scratch_dir, "footer_base64.txt"), "r", encoding="utf-8") as f:
    footer_base64 = f.read().strip()

# 2. Extract logo base64
pos_free = code.find("'template_name' => 'free_internship_offer_letter'")
logo_match = re.search(r'data:image/jpeg;base64,([a-zA-Z0-9+/=\s\r\n]+)', code[pos_free:pos_free+50000])
logo_base64 = re.sub(r'\s+', '', logo_match.group(1))

# 3. Extract matters
def extract_body_matter(template_name):
    pos = code.find(f"'{template_name}'")
    body_start = code.find("'body' => '", pos)
    body_end = code.find("',\n                'style' => '", body_start)
    if body_end == -1:
        body_end = code.find("',\r\n                'style' => '", body_start)
    body = code[body_start + 11:body_end]
    
    date_pos = body.find("Carbon")
    div_start = body.rfind("<div", 0, date_pos)
    if div_start == -1:
        div_start = body.find("<div", date_pos)
        
    matter = body[div_start:]
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

# Simple spacing cleans
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
    return f"""HEADER_BG_STUFF
    <div class="content-body">
        {matter}
    </div>
    FOOTER_BG_STUFF"""

def replace_template_in_code(current_code, template_name, new_body, new_style):
    pos = current_code.find(f"'{template_name}'")
    body_start = current_code.find("'body' => '", pos)
    body_end = current_code.find("',\n                'style' => '", body_start)
    if body_end == -1:
        body_end = current_code.find("',\r\n                'style' => '", body_start)
    
    style_start = current_code.find("'style' => '", body_end)
    style_end = current_code.find("',\n                'active_status' => 1,", style_start)
    if style_end == -1:
        style_end = current_code.find("',\r\n                'active_status' => 1,", style_start)
        
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

print("Initial exit body in code:")
pos = code.find("'exit_relieving_letter'")
b_s = code.find("'body' => '", pos)
b_e = code.find("',\n                'style' => '", b_s)
if b_e == -1: b_e = code.find("',\r\n                'style' => '", b_s)
print(f"Original len={b_e - (b_s + 11)}")

new_code = code
new_code = replace_template_in_code(new_code, "free_internship_offer_letter", "FREE", common_style)
pos = new_code.find("'exit_relieving_letter'")
b_s = new_code.find("'body' => '", pos)
b_e = new_code.find("',\n                'style' => '", b_s)
if b_e == -1: b_e = new_code.find("',\r\n                'style' => '", b_s)
print(f"After free: exit body len={b_e - (b_s + 11)}")

new_code = replace_template_in_code(new_code, "paid_internship_offer_letter", "PAID", common_style)
pos = new_code.find("'exit_relieving_letter'")
b_s = new_code.find("'body' => '", pos)
b_e = new_code.find("',\n                'style' => '", b_s)
if b_e == -1: b_e = new_code.find("',\r\n                'style' => '", b_s)
print(f"After paid: exit body len={b_e - (b_s + 11)}")

new_code = replace_template_in_code(new_code, "full_time_offer_letter", "FT", full_time_style)
pos = new_code.find("'exit_relieving_letter'")
b_s = new_code.find("'body' => '", pos)
b_e = new_code.find("',\n                'style' => '", b_s)
if b_e == -1: b_e = new_code.find("',\r\n                'style' => '", b_s)
print(f"After full_time: exit body len={b_e - (b_s + 11)}")

new_code = replace_template_in_code(new_code, "exit_relieving_letter", build_body_html("logo", "header", "footer", exit_matter), exit_style)
pos = new_code.find("'exit_relieving_letter'")
b_s = new_code.find("'body' => '", pos)
b_e = new_code.find("',\n                'style' => '", b_s)
if b_e == -1: b_e = new_code.find("',\r\n                'style' => '", b_s)
print(f"After exit replacement: exit body len={b_e - (b_s + 11)}")
print("Exit body content:")
print(new_code[b_s + 11:b_e])
