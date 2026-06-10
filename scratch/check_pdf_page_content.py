import fitz
import os
import sys

# Configure stdout to print UTF-8
sys.stdout.reconfigure(encoding='utf-8')

scratch_dir = r"d:\internship\hr-panel\hr-portal-api\scratch"
pdf_path = os.path.join(scratch_dir, "test_offer_intern_dynamic.pdf")

doc = fitz.open(pdf_path)
for i, page in enumerate(doc):
    text = page.get_text()
    print(f"--- PAGE {i+1} ---")
    print(text.strip())
    print("-" * 30)
