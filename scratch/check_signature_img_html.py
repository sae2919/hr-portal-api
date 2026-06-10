html_path = r"d:\internship\hr-panel\hr-portal-api\scratch\rendered_intern.html"
with open(html_path, "r", encoding="utf-8") as f:
    html = f.read()

# Let's search for "Warm regards" and grab the text immediately following it, showing the img tag
pos = html.find("Warm regards")
if pos != -1:
    print("=== HTML after Warm regards (first 600 chars) ===")
    print(html[pos:pos+600])
