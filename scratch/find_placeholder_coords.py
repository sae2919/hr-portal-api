import fitz
import os

pdf_files = [
    "scratch/test_exit_dynamic.pdf"
]

keywords = [
    "Guntuku Akanksha",
    "HR Manager",
    "1011",
    "29-Oct-2025",
    "11-Jun-2026"
]

for pdf_path in pdf_files:
    if not os.path.exists(pdf_path):
        print(f"File {pdf_path} not found.\n")
        continue
    print(f"=== SEARCHING IN: {pdf_path} ===")
    doc = fitz.open(pdf_path)
    page = doc[0]
    for kw in keywords:
        rects = page.search_for(kw)
        if rects:
            for r in rects:
                pt_to_mm = 25.4 / 72.0
                x_mm = r.x0 * pt_to_mm
                y_mm = r.y0 * pt_to_mm
                w_mm = (r.x1 - r.x0) * pt_to_mm
                h_mm = (r.y1 - r.y0) * pt_to_mm
                safe_kw = kw.encode('ascii', 'backslashreplace').decode('ascii')
                print(f"  Keyword: {safe_kw}")
                print(f"    MM: x={x_mm:.2f}, y={y_mm:.2f}, w={w_mm:.2f}, h={h_mm:.2f}")
        else:
            pass
    print()

