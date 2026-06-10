import re

seeder_path = r"d:\internship\hr-panel\hr-portal-api\database\seeders\MailTemplateSeeder.php"
blade_path = r"d:\internship\hr-panel\hr-portal-api\resources\views\pdf\offer-letter.blade.php"

with open(seeder_path, "r", encoding="utf-8") as f:
    code = f.read()

# Helper to extract template details
def extract_template(template_name):
    pos = code.find(f"'{template_name}'")
    if pos == -1:
        raise ValueError(f"Template {template_name} not found!")
    
    body_start = code.find("'body' => '", pos)
    body_end = code.find("',\n                'style' => '", body_start)
    body = code[body_start + 11:body_end]
    
    style_start = code.find("'style' => '", body_end)
    style_end = code.find("',\n                'active_status' => 1,", style_start)
    if style_end == -1:
        style_end = code.find("',\r\n                'active_status' => 1,", style_start)
    style = code[style_start + 12:style_end]
    
    return body, style

# Extract the three templates
free_body, free_style = extract_template("free_internship_offer_letter")
paid_body, paid_style = extract_template("paid_internship_offer_letter")
full_time_body, full_time_style = extract_template("full_time_offer_letter")

# Convert php escaped single quotes back to normal single quotes for Blade view
def clean_escapes(text):
    # Unescape escaped single quotes \' -> '
    text = text.replace(r"\'", "'")
    text = text.replace(r"\\'", "'")
    text = text.replace(r"\\", "\\")
    return text

free_body = clean_escapes(free_body)
free_style = clean_escapes(free_style)
paid_body = clean_escapes(paid_body)
paid_style = clean_escapes(paid_style)
full_time_body = clean_escapes(full_time_body)
full_time_style = clean_escapes(full_time_style)

# Helper to replace DB variables with Blade view variables
def convert_to_blade_vars(html):
    # Replace candidate_name
    html = html.replace("{{$candidate_name}}", "{{ $candidate->candidate_name }}")
    html = html.replace("{{ $candidate_name }}", "{{ $candidate->candidate_name }}")
    
    # Replace position
    html = html.replace("{{$position}}", "{{ $candidate->position }}")
    html = html.replace("{{ $position }}", "{{ $candidate->position }}")
    
    # Replace joining_date
    html = html.replace("{{$joining_date}}", "{{ \\Carbon\\Carbon::parse($candidate->joining_date)->format('d/m/Y') }}")
    html = html.replace("{{ $joining_date }}", "{{ \\Carbon\\Carbon::parse($candidate->joining_date)->format('d/m/Y') }}")
    
    # Replace duration
    html = html.replace("{{$duration}}", "{{ $candidate->duration ?? '3 months' }}")
    html = html.replace("{{ $duration }}", "{{ $candidate->duration ?? '3 months' }}")
    
    # Replace stipend/ctc
    html = html.replace("{{$stipend}}", "{{ number_format((float)($candidate->ctc ?? 0)) }}")
    html = html.replace("{{ $stipend }}", "{{ number_format((float)($candidate->ctc ?? 0)) }}")
    
    # Replace acceptance_date
    html = html.replace("{{$acceptance_date}}", "{{ \\Carbon\\Carbon::parse($letter_date ?? now())->addDays(2)->format('d-m-Y') }}")
    
    return html

free_body = convert_to_blade_vars(free_body)
paid_body = convert_to_blade_vars(paid_body)
full_time_body = convert_to_blade_vars(full_time_body)

# Construct the new Blade file content
new_blade_content = f"""{{-- ═══════════════════════════════════════════════════════════════
     FREE INTERNSHIP OFFER LETTER  (onboarding_type = free_intern)
     ═══════════════════════════════════════════════════════════════ --}}
@if($candidate->onboarding_type === 'free_intern')
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Free Internship Offer Letter – {{{{ $candidate->candidate_name }}}}</title>
    <style>
        {free_style}
    </style>
</head>
<body>
    {free_body}
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
    <title>Internship Offer Letter – {{{{ $candidate->candidate_name }}}}</title>
    <style>
        {paid_style}
    </style>
</head>
<body>
    {paid_body}
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
    <title>Offer of Employment – {{{{ $candidate->candidate_name }}}}</title>
    <style>
        {full_time_style}
    </style>
</head>
<body>
    {full_time_body}
</body>
</html>
@endif
"""

with open(blade_path, "w", encoding="utf-8") as f:
    f.write(new_blade_content)

print("Synchronized resources/views/pdf/offer-letter.blade.php successfully!")
