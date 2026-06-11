import fitz  # PyMuPDF
import os

pdf_path_1 = r"C:\Users\91756\Downloads\Payslip Template.pdf"
pdf_path_2 = r"C:\Users\91756\Downloads\Payslip Template (1).pdf"
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

render_pdf(pdf_path_1, "payslip_ref_1")
render_pdf(pdf_path_2, "payslip_ref_2")
print("Done!")
