import fitz  # PyMuPDF
import sys
import io

sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')

pdf_path = r"C:\Users\91756\Downloads\2026 - Internship Offer letter paid.pdf"
doc = fitz.open(pdf_path)

page = doc[1] # Page 2
text_instances = page.get_text("blocks")
print("Reference PDF Page 2 Text Blocks and their y-coordinates:")
for block in text_instances:
    rect = block[:4]
    text = block[4].strip().replace("\n", " ")
    if len(text) > 0:
        print(f"  y0={rect[1]:.1f}, y1={rect[3]:.1f} | Text: '{text[:120]}'")
