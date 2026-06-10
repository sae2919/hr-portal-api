import fitz

pdf_path = r"C:\Users\91756\Downloads\2026 - Internship Offer letter paid.pdf"
doc = fitz.open(pdf_path)
page = doc[0]
svg_text = page.get_svg_image()

with open(r"C:\Users\91756\.gemini\antigravity-ide\brain\7d3c3efd-0e98-4091-b648-313ef626c834\page_1.svg", "w", encoding="utf-8") as f:
    f.write(svg_text)

print("Saved SVG to artifacts directory.")
