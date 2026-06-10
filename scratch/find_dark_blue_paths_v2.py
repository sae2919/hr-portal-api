import xml.etree.ElementTree as ET

svg_path = r"C:\Users\91756\.gemini\antigravity-ide\brain\7d3c3efd-0e98-4091-b648-313ef626c834\page_1.svg"
tree = ET.parse(svg_path)
root = tree.getroot()

ns = {'svg': 'http://www.w3.org/2000/svg'}

print("All non-use elements with fill or stroke = `#28326e`:")
count = 0
for elem in root.iter():
    tag = elem.tag.split('}')[-1]
    if tag != 'use':
        fill = elem.get('fill', '')
        stroke = elem.get('stroke', '')
        if fill == '#28326e' or stroke == '#28326e':
            count += 1
            d = elem.get('d', '')
            transform = elem.get('transform', '')
            clip_path = elem.get('clip-path', '')
            print(f"[{tag}] idx={count} fill={fill} stroke={stroke} clip-path={clip_path} transform={transform}")
            if d:
                print(f"  d='{d[:100]}...'")
            print("-" * 50)
