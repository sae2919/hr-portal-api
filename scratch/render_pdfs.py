import fitz  # PyMuPDF
import os

artifacts_dir = r"C:\Users\91756\.gemini\antigravity-ide\brain\7d3c3efd-0e98-4091-b648-313ef626c834"

def render_pdf(pdf_path, prefix):
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

# Render reference
render_pdf(r"C:\Users\91756\Downloads\2026 - Internship Offer letter paid.pdf", "reference")

# Render current output
render_pdf(r"C:\Users\91756\Downloads\Offer_Letter_Ryagati Venkatesh.pdf", "current")
