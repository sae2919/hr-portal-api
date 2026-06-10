import fitz  # PyMuPDF
import os

scratch_dir = r"d:\internship\hr-panel\hr-portal-api\scratch"
artifacts_dir = r"C:\Users\91756\.gemini\antigravity-ide\brain\7d3c3efd-0e98-4091-b648-313ef626c834"

def render_pdf_to_images(pdf_name, prefix):
    pdf_path = os.path.join(scratch_dir, pdf_name)
    if not os.path.exists(pdf_path):
        print(f"Error: {pdf_path} does not exist.")
        return
    
    doc = fitz.open(pdf_path)
    print(f"Rendering {pdf_path} ({len(doc)} pages)...")
    for i, page in enumerate(doc):
        pix = page.get_pixmap(dpi=150)
        out_path = os.path.join(artifacts_dir, f"{prefix}_page_{i+1}.png")
        pix.save(out_path)
        print(f"Saved {out_path}")

# Render the three newly generated offer letter PDFs
render_pdf_to_images("test_offer_free_intern_dynamic.pdf", "test_free_intern")
render_pdf_to_images("test_offer_intern_dynamic.pdf", "test_paid_intern")
render_pdf_to_images("test_offer_full_time_dynamic.pdf", "test_full_time")

print("Rendering complete!")
