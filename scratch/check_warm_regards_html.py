html_path = r"d:\internship\hr-panel\hr-portal-api\scratch\rendered_intern.html"
with open(html_path, "r", encoding="utf-8") as f:
    html = f.read()

# Let's search for "Warm regards" and print 500 characters before it
pos = html.find("Warm regards")
if pos != -1:
    print("=== HTML before Warm regards ===")
    print(html[pos-500:pos+200])
else:
    print("Warm regards not found in HTML!")
