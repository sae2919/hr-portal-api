import xml.etree.ElementTree as ET

svg_path = r"C:\Users\91756\.gemini\antigravity-ide\brain\7d3c3efd-0e98-4091-b648-313ef626c834\page_1.svg"
tree = ET.parse(svg_path)
root = tree.getroot()

ns = {'svg': 'http://www.w3.org/2000/svg'}
parent_map = {c: p for p in root.iter() for c in p}

print("Checking opacities for sky-blue (#0496ff) paths:")
for i, elem in enumerate(root.iter()):
    tag = elem.tag.split('}')[-1]
    if tag == 'path':
        fill = elem.get('fill', '')
        if fill == '#0496ff':
            opacity = elem.get('opacity')
            fill_opacity = elem.get('fill-opacity')
            style = elem.get('style', '')
            
            # check parent opacities too
            parent_opacities = []
            curr = elem
            while curr in parent_map:
                parent = parent_map[curr]
                po = parent.get('opacity')
                pfo = parent.get('fill-opacity')
                pstyle = parent.get('style', '')
                if po: parent_opacities.append(f"opacity={po}")
                if pfo: parent_opacities.append(f"fill-opacity={pfo}")
                if 'opacity' in pstyle: parent_opacities.append(pstyle)
                curr = parent
                
            print(f"Path index {i}: opacity={opacity} fill-opacity={fill_opacity} style='{style}'")
            print("  Parent opacities: " + ", ".join(parent_opacities))
            print("-" * 50)
