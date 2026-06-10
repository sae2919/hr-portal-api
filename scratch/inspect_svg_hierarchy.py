import xml.etree.ElementTree as ET

svg_path = r"C:\Users\91756\.gemini\antigravity-ide\brain\7d3c3efd-0e98-4091-b648-313ef626c834\page_1.svg"
tree = ET.parse(svg_path)
root = tree.getroot()

ns = {'svg': 'http://www.w3.org/2000/svg'}

# Helper to find parent elements
parent_map = {c: p for p in root.iter() for c in p}

print("Tracing SVG path element styling hierarchy:")
path_idx = 0
for elem in root.iter():
    tag = elem.tag.split('}')[-1]
    if tag == 'path':
        path_idx += 1
        d = elem.get('d', '')
        
        # Build hierarchy
        hierarchy = []
        curr = elem
        while curr in parent_map:
            parent = parent_map[curr]
            p_tag = parent.tag.split('}')[-1]
            p_id = parent.get('id')
            p_fill = parent.get('fill')
            p_stroke = parent.get('stroke')
            p_style = parent.get('style')
            p_clip = parent.get('clip-path')
            
            p_info = f"{p_tag}"
            if p_id: p_info += f" id={p_id}"
            if p_fill: p_info += f" fill={p_fill}"
            if p_stroke: p_info += f" stroke={p_stroke}"
            if p_clip: p_info += f" clip-path={p_clip}"
            
            hierarchy.append(p_info)
            curr = parent
            
        # We only care about decorative paths (which have color in their hierarchy)
        # Check if blue or cyan color is present in parent/self fill/stroke
        has_blue = False
        # Collect all fill/stroke in hierarchy and self
        fills = [elem.get('fill', '')]
        strokes = [elem.get('stroke', '')]
        curr = elem
        while curr in parent_map:
            parent = parent_map[curr]
            if parent.get('fill'): fills.append(parent.get('fill'))
            if parent.get('stroke'): strokes.append(parent.get('stroke'))
            curr = parent
            
        for f in fills + strokes:
            if f in ['#0496ff', '#28326e', 'rgb(0%,58.819997%,100%)', 'rgb(15.690002%,19.609997%,43.140003%)']:
                has_blue = True
                
        if has_blue:
            print(f"Path {path_idx}: d='{d[:60]}...'")
            print("  Self: " + f"fill='{elem.get('fill')}' stroke='{elem.get('stroke')}' clip-path='{elem.get('clip-path')}'")
            print("  Hierarchy: " + " -> ".join(hierarchy))
            print("-" * 50)
