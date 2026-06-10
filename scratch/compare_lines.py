import fitz
import sys

# Reconfigure stdout for UTF-8
sys.stdout.reconfigure(encoding='utf-8')

ref_path = r"C:\Users\91756\Downloads\2026 - Internship Offer letter paid.pdf"
gen_path = r"d:\internship\hr-panel\hr-portal-api\scratch\test_offer_intern_dynamic.pdf"

ref_doc = fitz.open(ref_path)
gen_doc = fitz.open(gen_path)

print("=== PAGE 1 COMPARISON ===")
print(f"{'REFERENCE LINE':<60} | {'GENERATED LINE':<60}")
print("-" * 125)

# Extract spans with coordinates
def get_spans(page):
    spans = []
    blocks = page.get_text("dict")["blocks"]
    for block in blocks:
        if "lines" in block:
            for line in block["lines"]:
                # Merge spans in the same line
                text = "".join(s["text"] for s in line["spans"]).strip()
                if text:
                    bbox = line["bbox"]
                    spans.append((bbox, text))
    # Sort primarily by y-coord
    spans.sort(key=lambda s: s[0][1])
    return spans

ref_spans = get_spans(ref_doc[0])
gen_spans = get_spans(gen_doc[0])

max_len = max(len(ref_spans), len(gen_spans))
for i in range(max_len):
    ref_str = ""
    gen_str = ""
    if i < len(ref_spans):
        bbox, txt = ref_spans[i]
        ref_str = f"[{bbox[1]:.1f} to {bbox[3]:.1f}] '{txt}'"
    if i < len(gen_spans):
        bbox, txt = gen_spans[i]
        gen_str = f"[{bbox[1]:.1f} to {bbox[3]:.1f}] '{txt}'"
    print(f"{ref_str:<60} | {gen_str:<60}")
