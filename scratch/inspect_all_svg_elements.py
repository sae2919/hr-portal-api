import xml.etree.ElementTree as ET

svg_path = r"C:\Users\91756\.gemini\antigravity-ide\brain\7d3c3efd-0e98-4091-b648-313ef626c834\page_1.svg"
tree = ET.parse(svg_path)
root = tree.getroot()

ns = {'svg': 'http://www.w3.org/2000/svg'}

# Look at all path, rect, g elements
print("Listing interesting SVG elements:")
for i, elem in enumerate(root.iter()):
    tag_name = elem.tag.split('}')[-1]
    if tag_name in ['path', 'rect', 'g']:
        fill = elem.get('fill')
        stroke = elem.get('stroke')
        style = elem.get('style')
        transform = elem.get('transform')
        d = elem.get('d')
        
        # Check if there is any style or fill containing blue/cyan
        has_color = fill or stroke or style
        # Print the style block if found
        pass
        
# Let's print style block and classes of paths
style_block = root.find('.//svg:style', ns)
if style_block is not None:
    print("=== STYLE BLOCK ===")
    print(style_block.text)

print("\n=== PATHS WITH CLASS OR COLOR ===")
for i, elem in enumerate(root.iter()):
    tag_name = elem.tag.split('}')[-1]
    if tag_name == 'path':
        cls = elem.get('class')
        fill = elem.get('fill')
        stroke = elem.get('stroke')
        transform = elem.get('transform')
        d = elem.get('d')
        clip_path = elem.get('clip-path')
        
        if cls or fill or stroke or clip_path:
            print(f"[{tag_name}] index={i} class={cls} fill={fill} stroke={stroke} clip-path={clip_path} transform={transform}")
            if d:
                print(f"  d='{d[:100]}...'")
            print("-" * 50)
