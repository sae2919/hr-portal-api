import re

def clean_html(text):
    text = re.sub(r'src="data:image/[^;]+;base64,[^"]+"', 'src="data:image/png;base64,[BASE64_PLACEHOLDER]"', text)
    text = re.sub(r'\s+', ' ', text)
    return text.strip()

with open(r"d:\internship\hr-panel\hr-portal-api\scratch\seeder_full_time_body.html", "r", encoding="utf-8") as f:
    full_body = clean_html(f.read())

with open(r"d:\internship\hr-panel\hr-portal-api\scratch\paid_internship_offer_letter_body.html", "r", encoding="utf-8") as f:
    paid_body = clean_html(f.read())

if full_body == paid_body:
    print("They are identical in the seeder file!")
else:
    print("They differ in the seeder file.")
