import fitz
import sys
import io

sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')

def dump_pdf(pdf_path, name):
    print(f"\n==================================================")
    print(f"DUMPING {name} ({pdf_path})")
    print(f"==================================================")
    if not fitz.open(pdf_path):
        print("Failed to open PDF")
        return
    doc = fitz.open(pdf_path)
    for i, page in enumerate(doc):
        print(f"\n--- Page {i+1} ---")
        text = page.get_text()
        print(text.strip())

dump_pdf(r"C:\Users\91756\Downloads\2026 - Internship Offer letter paid.pdf", "REFERENCE PDF")
dump_pdf(r"d:\internship\hr-panel\hr-portal-api\scratch\test_offer_free_intern_dynamic.pdf", "GENERATED FREE INTERN")
dump_pdf(r"d:\internship\hr-panel\hr-portal-api\scratch\test_offer_intern_dynamic.pdf", "GENERATED PAID INTERN")
dump_pdf(r"d:\internship\hr-panel\hr-portal-api\scratch\test_offer_full_time_dynamic.pdf", "GENERATED FULL TIME")
