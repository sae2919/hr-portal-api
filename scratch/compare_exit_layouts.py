import fitz

def get_layout(pdf_path):
    doc = fitz.open(pdf_path)
    page = doc[0]
    blocks = page.get_text("blocks")
    # Sort blocks primarily by top Y coordinate, then left X
    blocks.sort(key=lambda b: (round(b[1], 1), round(b[0], 1)))
    res = []
    for b in blocks:
        # Ignore header/footer address text in the flow coordinate comparison
        txt = b[4].strip()
        if "Manjeera" in txt or "JNTU" in txt or "techsprout.ai" in txt:
            continue
        res.append((b[0], b[1], b[2], b[3], txt))
    return res

ref = get_layout(r"C:\Users\91756\Downloads\Experience & Relieving letter.pdf")
gen = get_layout(r"d:\internship\hr-panel\hr-portal-api\scratch\test_exit_dynamic.pdf")

print("--- REFERENCE LAYOUT ---")
for b in ref:
    print(f"Y={b[1]:.1f} | {repr(b[4])}")

print("\n--- GENERATED LAYOUT ---")
for b in gen:
    print(f"Y={b[1]:.1f} | {repr(b[4])}")
