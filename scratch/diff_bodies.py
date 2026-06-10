import difflib

with open("d:\\internship\\hr-panel\\hr-portal-api\\scratch\\free_body_clean.html", "r", encoding="utf-8") as f:
    free_clean = f.read()

with open("d:\\internship\\hr-panel\\hr-portal-api\\scratch\\paid_body_clean.html", "r", encoding="utf-8") as f:
    paid_clean = f.read()

# Let's break them down by tags or divs to see differences
# A simple line diff by splitting on tag boundaries might be very readable
import re
def split_by_tags(text):
    return [t.strip() for t in re.split(r'(<[^>]+>)', text) if t.strip()]

free_lines = split_by_tags(free_clean)
paid_lines = split_by_tags(paid_clean)

diff = difflib.unified_diff(free_lines, paid_lines, fromfile='free_clean', tofile='paid_clean', lineterm='')
for line in diff:
    print(line)
