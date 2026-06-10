import fitz  # PyMuPDF
import sys
import io

sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')

pdf_path = r"C:\Users\91756\Downloads\2026 - Internship Offer letter paid.pdf"
doc = fitz.open(pdf_path)

page = doc[0]
drawings = page.get_drawings()

for idx, dwg in enumerate(drawings):
    rect = dwg['rect']
    if rect.y1 < 150 or rect.y0 > 700:
        print(f"\n=== Drawing #{idx+1} ===")
        for k, v in dwg.items():
            if k == 'items':
                print(f"  items: {v}")
            elif k == 'clip':
                print(f"  clip: {v}")
            else:
                print(f"  {k}: {v}")
