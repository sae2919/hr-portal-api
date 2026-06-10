import re

html_path = r"d:\internship\hr-panel\hr-portal-api\scratch\rendered_intern.html"
with open(html_path, "r", encoding="utf-8") as f:
    html = f.read()

# Find all occurrences of page-break and print 200 characters before and after
for i, m in enumerate(re.finditer(r'class="page-break"', html)):
    start = max(0, m.start() - 150)
    end = min(len(html), m.end() + 250)
    print(f"\n--- Occurrence #{i+1} ---")
    print(html[start:end])
