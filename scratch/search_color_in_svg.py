with open(r"C:\Users\91756\.gemini\antigravity-ide\brain\7d3c3efd-0e98-4091-b648-313ef626c834\header_footer_only.svg", "r", encoding="utf-8") as f:
    svg = f.read()

import re
colors = re.findall(r'fill="([^"]+)"|stroke="([^"]+)"|color="([^"]+)"', svg)
unique_colors = set()
for c in colors:
    for val in c:
        if val:
            unique_colors.add(val)

print("Unique colors found in SVG:", unique_colors)

# Search for elements containing #28326e
lines = svg.splitlines()
for i, line in enumerate(lines):
    if "#28326e" in line:
        print(f"Line {i+1}: {line[:120]}...")
