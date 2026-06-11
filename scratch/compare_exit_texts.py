import fitz  # PyMuPDF

ref_path = r"C:\Users\91756\Downloads\Experience & Relieving letter.pdf"
gen_path = r"d:\internship\hr-panel\hr-portal-api\scratch\test_exit_dynamic.pdf"

def dump_text(pdf_path, name):
    print(f"--- Text for {name} ({pdf_path}) ---")
    doc = fitz.open(pdf_path)
    for i, page in enumerate(doc):
        print(f"Page {i+1}:")
        print(page.get_text())
    print("\n")

dump_text(ref_path, "Reference Relieving Letter")
dump_text(gen_path, "Our Generated Relieving Letter")
