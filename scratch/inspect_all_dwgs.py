import fitz  # PyMuPDF
import sys
import io

sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')

pdf_path = r"C:\Users\91756\Downloads\2026 - Internship Offer letter paid.pdf"
doc = fitz.open(pdf_path)

page = doc[0]
drawings = page.get_drawings()
print(f"Total drawings on Page 1: {len(drawings)}")

for idx, dwg in enumerate(drawings):
    print(f"\nDrawing #{idx+1}:")
    for k, v in dwg.items():
        if k == 'items':
            print(f"  items: {v}")
        elif k not in ['clip', 'layer']:
            print(f"  {k}: {v}")
