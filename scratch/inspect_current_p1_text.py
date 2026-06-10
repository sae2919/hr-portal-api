import fitz  # PyMuPDF
import sys
import io

sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')

pdf_path = r"d:\internship\hr-panel\hr-portal-api\scratch\test_offer_intern_dynamic.pdf"
doc = fitz.open(pdf_path)

page = doc[0]
text_instances = page.get_text("blocks")
print("Generated PDF Page 1 Text Blocks and their y-coordinates:")
for block in text_instances:
    rect = block[:4]
    text = block[4].strip().replace("\n", " ")
    if len(text) > 0:
        print(f"  y0={rect[1]:.1f}, y1={rect[3]:.1f} | Text: '{text[:120]}'")
