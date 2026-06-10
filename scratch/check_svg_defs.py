with open(r"C:\Users\91756\.gemini\antigravity-ide\brain\7d3c3efd-0e98-4091-b648-313ef626c834\header_footer_only.svg", "r", encoding="utf-8") as f:
    svg = f.read()

import re
# Print all lines inside defs block
defs_match = re.search(r'<ns0:defs>(.*?)</ns0:defs>', svg, re.DOTALL)
if defs_match:
    print("Found defs content (first 1000 chars):")
    print(defs_match.group(1)[:1000])
else:
    print("No defs block found.")

# Let's search for any 'gradient' or 'color' in the file
gradient_lines = [line for line in svg.splitlines() if 'gradient' in line.lower()]
print(f"\nFound {len(gradient_lines)} lines with 'gradient':")
for line in gradient_lines[:10]:
    print(line[:120])
