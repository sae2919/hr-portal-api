import xml.etree.ElementTree as ET

svg_path = r"C:\Users\91756\.gemini\antigravity-ide\brain\7d3c3efd-0e98-4091-b648-313ef626c834\page_1.svg"
tree = ET.parse(svg_path)
root = tree.getroot()

ns = {'svg': 'http://www.w3.org/2000/svg'}

print("Header/Footer SVG Elements analysis:")
# We want to look at paths, especially those at the top (y < 150) or bottom (y > 700)
for i, elem in enumerate(root.findall('.//svg:path', ns)):
    d = elem.get('d')
    fill = elem.get('fill', '')
    stroke = elem.get('stroke', '')
    style = elem.get('style', '')
    transform = elem.get('transform', '')
    
    # Analyze color
    color_info = f"fill='{fill}' stroke='{stroke}' style='{style}'"
    
    # We can inspect the coordinates in the path 'd' or rect bounding box
    # Let's extract the first command's coordinates from 'd'
    parts = d.split()
    if len(parts) > 1:
        first_cmd = parts[0]
        # e.g. M1673.2677 or M0
        try:
            # Parse the coordinates of the first point
            coord_str = first_cmd[1:]
            val = float(coord_str)
        except ValueError:
            val = None
            
        print(f"Path {i+1}: d='{d[:100]}...' {color_info} transform='{transform}'")
