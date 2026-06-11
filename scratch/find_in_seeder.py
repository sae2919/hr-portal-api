with open(r"d:\internship\hr-panel\hr-portal-api\database\seeders\MailTemplateSeeder.php", "r", encoding="utf-8") as f:
    code = f.read()

import re

# Let's find all occurrences of "Vishwanath" or "regards" case-insensitively
matches = []
for m in re.finditer(r'(?i)(regards|vishwanath|founder)', code):
    # Find start and end line
    start_pos = m.start()
    line_num = code.count('\n', 0, start_pos) + 1
    # print context of 3 lines before and after
    line_start = code.rfind('\n', 0, start_pos) + 1
    line_end = code.find('\n', start_pos)
    if line_end == -1:
        line_end = len(code)
    line_content = code[line_start:line_end].strip()
    matches.append((line_num, line_content))

print(f"Found {len(matches)} occurrences:")
for num, content in matches[:50]:
    print(f"Line {num}: {content}")
