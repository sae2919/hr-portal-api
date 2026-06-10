import fitz

doc = fitz.open("scratch/test_fixed_margins.pdf")
print(f"Total pages: {len(doc)}")
for i, page in enumerate(doc):
    print(f"\n--- PAGE {i+1} ---")
    blocks = page.get_text("blocks")
    for b in blocks:
        rect = b[:4]
        txt = b[4].strip().replace("\n", " ")
        if txt:
            print(f"[{rect[0]:.1f}, {rect[1]:.1f}, {rect[2]:.1f}, {rect[3]:.1f}] '{txt}'")
