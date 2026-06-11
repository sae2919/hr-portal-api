import fitz
import re
import difflib

ref_path = r"C:\Users\91756\Downloads\2026 - Internship Offer letter paid.pdf"
gen_path = r"d:\internship\hr-panel\hr-portal-api\scratch\test_offer_intern_dynamic.pdf"

ref_doc = fitz.open(ref_path)
gen_doc = fitz.open(gen_path)

ref_text = ""
for page in ref_doc:
    ref_text += page.get_text()

gen_text = ""
for page in gen_doc:
    gen_text += page.get_text()

def clean_text(text):
    # Standardize spaces and newlines
    text = text.replace("\r", "")
    text = re.sub(r"\s+", " ", text)
    # Standardize smart quotes to plain quotes
    text = text.replace("’", "'").replace("‘", "'").replace("“", '"').replace("”", '"')
    # Replace variable content from generated text with placeholders
    text = text.replace("DEAR sai", "DEAR KISHORE")
    text = text.replace("DEAR KISHORE", "DEAR Mr./Ms.Name")
    text = text.replace("designated as a SDE Intern", "designated as a Position")
    text = text.replace("designated as a Position", "designated as a Position")
    text = text.replace("commence from 15/06/2026", "commence from Date")
    text = text.replace("commence from Date", "commence from Date")
    text = text.replace("period of 3 months", "period of Duration")
    text = text.replace("period of Duration", "period of Duration")
    text = text.replace("stipend of ₹0", "stipend of ₹")
    text = text.replace("stipend of ₹", "stipend of ₹")
    text = text.replace("11-JUN 2026", "DateBlock")
    text = text.replace("25-FEB 2026", "DateBlock")
    text = text.replace("by 13-06-2026", "by DateBlock2")
    text = text.replace("by 27-02-2026", "by DateBlock2")
    text = text.replace("by 13/06/2026", "by DateBlock3")
    text = text.replace("by 27/02/2026", "by DateBlock3")
    return text.strip()

ref_clean = clean_text(ref_text)
gen_clean = clean_text(gen_text)

# Let's split by sentence or paragraph to do a clean diff
ref_words = ref_clean.split(" ")
gen_words = gen_clean.split(" ")

diff = list(difflib.ndiff(ref_words, gen_words))
mismatches = [d for d in diff if d.startswith("+ ") or d.startswith("- ")]

print("Total mismatches found:", len(mismatches))
for m in mismatches:
    print(m)
