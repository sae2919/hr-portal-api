import xml.etree.ElementTree as ET
import re

svg_path = r"C:\Users\91756\.gemini\antigravity-ide\brain\7d3c3efd-0e98-4091-b648-313ef626c834\page_1.svg"
tree = ET.parse(svg_path)
root = tree.getroot()

ns = {'svg': 'http://www.w3.org/2000/svg'}

fills = set()
strokes = set()

# Inspect inline attributes
for elem in root.iter():
    f = elem.get('fill')
    s = elem.get('stroke')
    style = elem.get('style', '')
    
    if f: fills.add(f)
    if s: strokes.add(s)
    
    # search style for fill/stroke colors
    if style:
        f_match = re.search(r'fill:\s*([^;]+)', style)
        s_match = re.search(r'stroke:\s*([^;]+)', style)
        if f_match: fills.add(f_match.group(1).strip())
        if s_match: strokes.add(s_match.group(1).strip())

# Inspect style blocks
for style_block in root.findall('.//svg:style', ns):
    if style_block.text:
        # Find colors in css (hex or rgb)
        colors = re.findall(r'#\w{3,6}|rgb\([^)]+\)', style_block.text)
        for c in colors:
            fills.add(c)

print("Unique Fill Colors found:")
for f in sorted(list(fills)):
    print("  ", f)

print("\nUnique Stroke Colors found:")
for s in sorted(list(strokes)):
    print("  ", s)
