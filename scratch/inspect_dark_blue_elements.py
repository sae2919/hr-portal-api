import xml.etree.ElementTree as ET

svg_path = r"C:\Users\91756\.gemini\antigravity-ide\brain\7d3c3efd-0e98-4091-b648-313ef626c834\page_1.svg"
tree = ET.parse(svg_path)
root = tree.getroot()

ns = {'svg': 'http://www.w3.org/2000/svg'}
parent_map = {c: p for p in root.iter() for c in p}

print("Dark Blue (#28326e) Elements on Page 1:")
for i, elem in enumerate(root.iter()):
    tag = elem.tag.split('}')[-1]
    fill = elem.get('fill', '')
    stroke = elem.get('stroke', '')
    d = elem.get('d', '')
    
    if fill == '#28326e' or stroke == '#28326e':
        # Trace hierarchy
        hierarchy = []
        curr = elem
        while curr in parent_map:
            parent = parent_map[curr]
            p_tag = parent.tag.split('}')[-1]
            p_id = parent.get('id')
            p_clip = parent.get('clip-path')
            p_info = p_tag
            if p_id: p_info += f" id={p_id}"
            if p_clip: p_info += f" clip-path={p_clip}"
            hierarchy.append(p_info)
            curr = parent
            
        print(f"Index {i}: <{tag}> d='{d[:60]}...' transform='{elem.get('transform')}' fill='{fill}' stroke='{stroke}'")
        print("  Hierarchy: " + " -> ".join(hierarchy))
        print("-" * 50)
