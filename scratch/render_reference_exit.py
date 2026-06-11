import fitz  # PyMuPDF
import os

pdf_path = r"C:\Users\91756\Downloads\Experience & Relieving letter.pdf"
artifacts_dir = r"C:\Users\91756\.gemini\antigravity-ide\brain\7d3c3efd-0e98-4091-b648-313ef626c834"

if not os.path.exists(pdf_path):
    print(f"Error: {pdf_path} does not exist.")
else:
    doc = fitz.open(pdf_path)
    print(f"Rendering reference PDF ({len(doc)} pages)...")
    for i, page in enumerate(doc):
        pix = page.get_pixmap(dpi=150)
        out_path = os.path.join(artifacts_dir, f"reference_exit_page_{i+1}.png")
        pix.save(out_path)
        print(f"Saved {out_path}")
    print("Done!")
