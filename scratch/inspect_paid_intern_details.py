import fitz
import os

pdf_path_ref = r"C:\Users\91756\Downloads\2026 - Internship Offer letter paid.pdf"
pdf_path_gen = r"d:\internship\hr-panel\hr-portal-api\scratch\test_offer_intern_dynamic.pdf"
out_path = r"d:\internship\hr-panel\hr-portal-api\scratch\inspect_paid_intern_details.txt"

with open(out_path, "w", encoding="utf-8") as out_f:
    def inspect_pdf(pdf_path, name):
        out_f.write(f"\n================ INSPECTING: {name} ({pdf_path}) ================\n")
        doc = fitz.open(pdf_path)
        for p_idx, page in enumerate(doc):
            out_f.write(f"--- Page {p_idx+1} ---\n")
            blocks = page.get_text("dict")["blocks"]
            for b_idx, b in enumerate(blocks):
                if "lines" not in b:
                    continue
                out_f.write(f"  Block {b_idx} | bbox: ({b['bbox'][0]:.1f}, {b['bbox'][1]:.1f}, {b['bbox'][2]:.1f}, {b['bbox'][3]:.1f})\n")
                for l_idx, l in enumerate(b["lines"]):
                    for s_idx, s in enumerate(l["spans"]):
                        out_f.write(f"    Span | Font: {s['font']} | Size: {s['size']:.1f} | bbox: ({s['bbox'][0]:.1f}, {s['bbox'][1]:.1f}, {s['bbox'][2]:.1f}, {s['bbox'][3]:.1f}) | Text: {repr(s['text'])}\n")

    inspect_pdf(pdf_path_ref, "Reference Paid Internship")
    inspect_pdf(pdf_path_gen, "Our Generated Paid Internship")

print(f"Inspection complete. Output saved to {out_path}")
