import fitz  # PyMuPDF
import os
import sys

sys.stdout.reconfigure(encoding='utf-8')

scratch_dir = r"d:\internship\hr-panel\hr-portal-api\scratch"
templates = ["free_intern", "intern", "full_time"]

for temp in templates:
    pdf_path = os.path.join(scratch_dir, f"test_offer_{temp}_dynamic.pdf")
    if not os.path.exists(pdf_path):
        print(f"File not found: {pdf_path}")
        continue
    
    doc = fitz.open(pdf_path)
    print(f"\n==========================================")
    print(f"Template: {temp} | Total Pages: {len(doc)}")
    for page_num in range(len(doc)):
        page = doc[page_num]
        text_instances = page.get_text("blocks")
        max_y1 = 0
        last_text = ""
        for block in text_instances:
            rect = block[:4]
            text = block[4].strip().replace("\n", " ")
            if len(text) > 0:
                if rect[3] > max_y1:
                    max_y1 = rect[3]
                    last_text = text
        print(f"  Page {page_num+1} max_y1={max_y1:.1f} | Last text: '{last_text[:100]}'")
