import xml.etree.ElementTree as ET

svg_path = r"C:\Users\91756\.gemini\antigravity-ide\brain\7d3c3efd-0e98-4091-b648-313ef626c834\page_1.svg"
tree = ET.parse(svg_path)
root = tree.getroot()

ns = {'svg': 'http://www.w3.org/2000/svg'}

print("Non-use elements with `#28326e`:")
for i, elem in enumerate(root.iter()):
    tag = elem.tag.split('}')[-1]
    if tag in ['path', 'rect', 'polygon']:
        fill = elem.get('fill', '')
        stroke = elem.get('stroke', '')
        d = elem.get('d', '')
        
        if fill == '#28326e' or stroke == '#28326e':
            print(f"Index {i}: <{tag}> fill='{fill}' stroke='{stroke}' d='{d[:100]}...'")
