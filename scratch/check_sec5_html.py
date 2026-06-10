html_path = r"d:\internship\hr-panel\hr-portal-api\scratch\rendered_intern.html"
with open(html_path, "r", encoding="utf-8") as f:
    html = f.read()

# Let's search for "5. Confidentiality" and print 500 characters before and after
pos = html.find("5. Confidentiality")
if pos != -1:
    print("=== HTML around Section 5 ===")
    print(html[pos-300:pos+300])
else:
    print("Section 5 not found in HTML!")
