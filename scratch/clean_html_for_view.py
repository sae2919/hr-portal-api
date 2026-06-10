import re

html_path = r"d:\internship\hr-panel\hr-portal-api\scratch\rendered_intern.html"
out_path = r"d:\internship\hr-panel\hr-portal-api\scratch\rendered_intern_clean.html"

with open(html_path, "r", encoding="utf-8") as f:
    html = f.read()

# Replace base64 data strings
clean_html = re.sub(r'data:image/[^;]+;base64,[a-zA-Z0-9+/=\s\r\n]+', '[BASE64_IMAGE]', html)

with open(out_path, "w", encoding="utf-8") as f:
    f.write(clean_html)

print("Saved clean HTML structure to", out_path)
