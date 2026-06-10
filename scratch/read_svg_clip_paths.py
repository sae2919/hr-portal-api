with open(r"C:\Users\91756\.gemini\antigravity-ide\brain\7d3c3efd-0e98-4091-b648-313ef626c834\page_1.svg", "r", encoding="utf-8") as f:
    lines = f.readlines()

print("SVG Header (first 60 lines):")
for i in range(min(60, len(lines))):
    print(lines[i].strip())

print("\nSearching for clipPath or mask:")
for i, line in enumerate(lines):
    if "clipPath" in line or "mask" in line:
        print(f"Line {i+1}: {line.strip()[:150]}")
