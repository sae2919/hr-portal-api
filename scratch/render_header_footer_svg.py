import fitz
import os

svg_path = r"C:\Users\91756\.gemini\antigravity-ide\brain\7d3c3efd-0e98-4091-b648-313ef626c834\header_footer_only.svg"
png_path = r"C:\Users\91756\.gemini\antigravity-ide\brain\7d3c3efd-0e98-4091-b648-313ef626c834\header_footer_only.png"

if os.path.exists(svg_path):
    doc = fitz.open(svg_path)
    page = doc[0]
    pix = page.get_pixmap(dpi=150)
    pix.save(png_path)
    print(f"Rendered SVG to PNG: {png_path}")
else:
    print(f"Error: {svg_path} does not exist.")
