import fitz

def inspect_pdf(pdf_path, name):
    print(f"\n================ INSPECTING: {name} ({pdf_path}) ================")
    doc = fitz.open(pdf_path)
    page = doc[0]
    
    # Extract structural text spans
    blocks = page.get_text("dict")["blocks"]
    for b_idx, b in enumerate(blocks):
        if "lines" not in b:
            continue
        print(f"Block {b_idx} | bbox: ({b['bbox'][0]:.1f}, {b['bbox'][1]:.1f}, {b['bbox'][2]:.1f}, {b['bbox'][3]:.1f})")
        for l_idx, l in enumerate(b["lines"]):
            for s_idx, s in enumerate(l["spans"]):
                print(f"  Span | Font: {s['font']} | Size: {s['size']:.1f} | bbox: ({s['bbox'][0]:.1f}, {s['bbox'][1]:.1f}, {s['bbox'][2]:.1f}, {s['bbox'][3]:.1f}) | Text: {repr(s['text'])}")

inspect_pdf(r"C:\Users\91756\Downloads\Experience & Relieving letter.pdf", "Reference Relieving Letter")
inspect_pdf(r"d:\internship\hr-panel\hr-portal-api\scratch\test_exit_dynamic.pdf", "Our Generated Relieving Letter")
