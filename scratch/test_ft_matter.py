import re

with open(r"d:\internship\hr-panel\hr-portal-api\database\seeders\MailTemplateSeeder.php", "r", encoding="utf-8") as f:
    code = f.read()

# Extract paid_matter
pos_paid = code.find("'template_name' => 'paid_internship_offer_letter'")
body_start = code.find("'body' => '", pos_paid)
body_end = code.find("',\n                'style' => '", body_start)
body = code[body_start + 11:body_end]

date_pos = body.find("Carbon")
div_start = body.rfind("<div", 0, date_pos)
if div_start == -1:
    div_start = body.find("<div", date_pos)
paid_matter = body[div_start:]

# Apply transformations
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

with open("scratch/test_ft_matter_out.html", "w", encoding="utf-8") as out:
    out.write(full_time_matter)

print("Saved output to scratch/test_ft_matter_out.html")

# Verification
has_internship = "internship" in full_time_matter.lower()
has_stipend_word = "stipend" in full_time_matter.replace("{{$stipend}}", "").lower()

if has_internship:
    print("WARNING: 'internship' still found in the output!")
else:
    print("SUCCESS: No occurrences of 'internship' found!")

if has_stipend_word:
    print("WARNING: 'stipend' still found in the output (excluding variable name)!")
else:
    print("SUCCESS: No occurrences of 'stipend' (excluding variable name) found!")

# Check if {{$stipend}} is still intact
if "{{$stipend}}" in full_time_matter or "{{ $stipend }}" in full_time_matter:
    print("SUCCESS: Dynamic variable {{$stipend}} is correctly preserved!")
else:
    print("WARNING: Dynamic variable {{$stipend}} was NOT found or was renamed!")
