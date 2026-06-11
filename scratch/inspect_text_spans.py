import fitz

def inspect_spans(pdf_path, name):
    print(f"\n--- SPANS FOR: {name} ---")
    doc = fitz.open(pdf_path)
    page = doc[0]
    blocks = page.get_text("dict")["blocks"]
    
    # Extract all spans
    spans = []
    for b in blocks:
        if "lines" in b:
            for l in b["lines"]:
                for s in l["spans"]:
                    spans.append(s)
                    
    # Sort spans top-to-bottom, left-to-right
    spans.sort(key=lambda s: (round(s["bbox"][1], 1), round(s["bbox"][0], 1)))
    
    for s in spans:
        print(f"BBox: ({s['bbox'][0]:.1f}, {s['bbox'][1]:.1f}, {s['bbox'][2]:.1f}, {s['bbox'][3]:.1f}) | "
              f"Font: {s['font']} | Size: {s['size']:.1f} | Text: {repr(s['text'])}")

inspect_spans(r"C:\Users\91756\Downloads\Payslip Template.pdf", "Full-time Reference")
