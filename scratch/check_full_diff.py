import fitz
import difflib

ref_path = r"C:\Users\91756\Downloads\2026 - Internship Offer letter paid.pdf"
gen_path = r"d:\internship\hr-panel\hr-portal-api\scratch\test_offer_intern_dynamic.pdf"

ref_doc = fitz.open(ref_path)
gen_doc = fitz.open(gen_path)

print(f"Reference pages: {len(ref_doc)} | Generated pages: {len(gen_doc)}")

for page_num in range(3):
    print(f"\n================ PAGE {page_num+1} ================")
    
    def extract_lines(page):
        lines = []
        for b in page.get_text("dict")["blocks"]:
            if "lines" not in b:
                continue
            for l in b["lines"]:
                line_text = "".join(s["text"] for s in l["spans"])
                lines.append(line_text)
        return lines

    ref_lines = extract_lines(ref_doc[page_num])
    gen_lines = extract_lines(gen_doc[page_num])
    
    # Replace variable text with generic tokens for comparison
    def clean_line(line):
        line = line.replace("sai", "Mr./Ms.Name").replace("KISHORE", "Mr./Ms.Name")
        line = line.replace("SDE Intern", "Position")
        line = line.replace("15/06/2026", "Date").replace("Date", "Date")
        line = line.replace("3 months", "Duration").replace("Duration", "Duration")
        line = line.replace("stipend of ₹0", "stipend of ₹").replace("stipend of ₹", "stipend of ₹")
        line = line.replace("11-JUN 2026", "DateBlock").replace("25-FEB 2026", "DateBlock")
        line = line.replace("13-06-2026", "DateBlock2").replace("27-02-2026", "DateBlock2")
        line = line.replace("13/06/2026", "DateBlock3").replace("27/02/2026", "DateBlock3")
        line = line.replace("’", "'").replace("‘", "'").replace("“", '"').replace("”", '"')
        return line.strip()

    ref_clean = [clean_line(l) for l in ref_lines if l.strip()]
    gen_clean = [clean_line(l) for l in gen_lines if l.strip()]
    
    diff = list(difflib.unified_diff(ref_clean, gen_clean, fromfile="Reference", tofile="Generated"))
    if not diff:
        print("No text differences on this page!")
    else:
        for d in diff:
            print(d)
