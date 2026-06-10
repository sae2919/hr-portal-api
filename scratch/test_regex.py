import re

html_path = r"d:\internship\hr-panel\hr-portal-api\scratch\rendered_intern.html"
with open(html_path, "r", encoding="utf-8") as f:
    html = f.read()

# Let's test the Section 5 replacement
new_html = re.sub(
    r'<div class="section-title"\s*(style="[^"]*")?>5\.\s*Confidentiality',
    r'<div class="section-title page-break-before" \1>5. Confidentiality',
    html
)

# Let's test the Section 10 replacement
new_html = re.sub(
    r'<div class="section-title"\s*(style="[^"]*")?>10\.\s*Data\s+Security',
    r'<div class="section-title page-break-before" \1>10.  Data Security',
    new_html
)

pos5 = new_html.find("5. Confidentiality")
print("Section 5 Replaced:")
print(new_html[pos5-100:pos5+150])

pos10 = new_html.find("10.  Data Security")
print("\nSection 10 Replaced:")
print(new_html[pos10-100:pos10+150])
