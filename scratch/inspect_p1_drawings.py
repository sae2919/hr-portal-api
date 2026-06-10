import fitz  # PyMuPDF
import sys
import io

sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')

pdf_path = r"C:\Users\91756\Downloads\2026 - Internship Offer letter paid.pdf"
doc = fitz.open(pdf_path)

page = doc[0]
drawings = page.get_drawings()
print(f"Page 1 has {len(drawings)} drawing paths.")

for idx, dwg in enumerate(drawings):
    # Filter for drawings at the top (y < 150) or bottom (y > 700)
    rect = dwg['rect']
    if rect.y1 < 150 or rect.y0 > 700:
        print(f"Drawing #{idx+1}: type={dwg['type']}, rect={rect}, fill={dwg.get('fill')}, color={dwg.get('color')}, items={dwg.get('items')}")
