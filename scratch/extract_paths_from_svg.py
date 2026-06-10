import xml.etree.ElementTree as ET

svg_path = r"C:\Users\91756\.gemini\antigravity-ide\brain\7d3c3efd-0e98-4091-b648-313ef626c834\page_1.svg"

# Parse SVG XML
tree = ET.parse(svg_path)
root = tree.getroot()

# SVG namespace is usually in the tag, e.g. {http://www.w3.org/2000/svg}path
ns = {'svg': 'http://www.w3.org/2000/svg'}

print("Extracting elements with style/fill/stroke:")
paths_found = 0
for elem in root.findall('.//svg:path', ns):
    d = elem.get('d')
    style = elem.get('style', '')
    fill = elem.get('fill', '')
    stroke = elem.get('stroke', '')
    transform = elem.get('transform', '')
    
    paths_found += 1
    if paths_found <= 30:
        print(f"Path {paths_found}: d={d[:50]}...")
        print(f"  style: {style}")
        print(f"  fill: {fill}")
        print(f"  stroke: {stroke}")
        print(f"  transform: {transform}")
        print("-" * 50)
        print(f"Path d: {d}")
        print(f"  style: {style}")
        print(f"  fill: {fill}")
        print(f"  transform: {transform}")
        print("-" * 50)
