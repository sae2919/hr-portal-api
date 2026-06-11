import fitz
import os

ref_path = r"C:\Users\91756\Downloads\2026 - Internship Offer letter paid.pdf"
gen_path = r"d:\internship\hr-panel\hr-portal-api\scratch\test_offer_intern_dynamic.pdf"
out_path = r"d:\internship\hr-panel\hr-portal-api\scratch\paid_internship_comparison.txt"

if not os.path.exists(ref_path):
    print(f"Error: Reference path {ref_path} does not exist.")
    exit(1)
if not os.path.exists(gen_path):
    print(f"Error: Generated path {gen_path} does not exist.")
    exit(1)

with open(out_path, "w", encoding="utf-8") as out_f:
    def dump_text_layout(pdf_path, label):
        out_f.write(f"\n================ {label} ================\n")
        doc = fitz.open(pdf_path)
        out_f.write(f"Total Pages: {len(doc)}\n")
        for i, page in enumerate(doc):
            out_f.write(f"--- Page {i+1} ---\n")
            blocks = page.get_text("blocks")
            # Sort blocks top-to-bottom, left-to-right
            blocks.sort(key=lambda b: (round(b[1], 1), round(b[0], 1)))
            for b in blocks:
                out_f.write(f"BBox: ({b[0]:.1f}, {b[1]:.1f}, {b[2]:.1f}, {b[3]:.1f}) | Text: {repr(b[4].strip())}\n")

    dump_text_layout(ref_path, "Reference Paid Internship Offer Letter")
    dump_text_layout(gen_path, "Generated Paid Internship Offer Letter")

print(f"Done! Saved comparison to {out_path}")
