import xml.etree.ElementTree as ET

svg_path = r"C:\Users\91756\.gemini\antigravity-ide\brain\7d3c3efd-0e98-4091-b648-313ef626c834\page_1.svg"
tree = ET.parse(svg_path)
root = tree.getroot()

ns = {'svg': 'http://www.w3.org/2000/svg'}

# Let's find all path elements, and filter those that represent the header/footer
# We know the header has y near 0/negative, footer has y near 800/900.
# We will create a new SVG document containing just these paths, preserving their coordinate system and transformations.
header_footer_svg = ET.Element('svg', {
    'xmlns': 'http://www.w3.org/2000/svg',
    'width': '595.5',
    'height': '842.25',
    'viewBox': '0 0 595.5 842.25'
})

# Copy defs (for clipPaths)
defs = root.find('.//svg:defs', ns)
if defs is not None:
    header_footer_svg.append(defs)

# Copy all paths that have color (fill or stroke) or use a clipPath
for elem in root.findall('.//svg:path', ns):
    fill = elem.get('fill', '')
    stroke = elem.get('stroke', '')
    clip_path = elem.get('clip-path', '')
    
    # We want to copy paths that form the decorative header/footer
    # Let's check if they have blue colors or are clipped by clipPath
    is_decorative = False
    if fill in ['#0496ff', '#28326e'] or stroke in ['#0496ff', '#28326e']:
        is_decorative = True
    elif 'url(#clip_' in clip_path:
        is_decorative = True
        
    if is_decorative:
        header_footer_svg.append(elem)

# Save the new SVG
out_path = r"C:\Users\91756\.gemini\antigravity-ide\brain\7d3c3efd-0e98-4091-b648-313ef626c834\header_footer_only.svg"
ET.ElementTree(header_footer_svg).write(out_path, encoding='utf-8', xml_declaration=True)
print(f"Saved header/footer SVG to: {out_path}")
