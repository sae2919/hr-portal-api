import xml.etree.ElementTree as ET

svg_path = r"C:\Users\91756\.gemini\antigravity-ide\brain\7d3c3efd-0e98-4091-b648-313ef626c834\header_footer_only.svg"
tree = ET.parse(svg_path)
root = tree.getroot()

# Traverse all elements recursively
count = 0
for elem in root.iter():
    # Strip namespace prefix to check tag name
    tag = elem.tag.split('}')[-1]
    fill = elem.get('fill')
    stroke = elem.get('stroke')
    if fill or stroke:
        count += 1
        print(f"Element #{count}: tag='{tag}', id='{elem.get('id')}', fill='{fill}', stroke='{stroke}', transform='{elem.get('transform')}'")
        d = elem.get('d')
        if d:
            print(f"  d='{d[:120]}...'")
