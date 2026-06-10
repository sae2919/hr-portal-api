import xml.etree.ElementTree as ET
import re

svg_path = r"C:\Users\91756\.gemini\antigravity-ide\brain\7d3c3efd-0e98-4091-b648-313ef626c834\header_footer_only.svg"
tree = ET.parse(svg_path)
root = tree.getroot()

ns = {'svg': 'http://www.w3.org/2000/svg'}

# Let's find all path elements and their details
print("SVG Elements and Styles:")
for i, path in enumerate(root.findall('.//svg:path', ns)):
    d = path.get('d')
    fill = path.get('fill')
    stroke = path.get('stroke')
    clip_path = path.get('clip-path')
    transform = path.get('transform')
    style = path.get('style')
    
    # Let's print if it has a fill color
    if fill or style:
        print(f"Path #{i+1}: fill={fill}, stroke={stroke}, clip-path={clip_path}, transform={transform}, style={style}")
        print(f"  d='{d[:120]}...'")
