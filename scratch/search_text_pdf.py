import fitz  # PyMuPDF
import sys
import io

sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')

pdf_path = r"C:\Users\91756\Downloads\2026 - Internship Offer letter paid.pdf"
doc = fitz.open(pdf_path)

for page_num in range(len(doc)):
    page = doc[page_num]
    print(f"\n=== Page {page_num + 1} ===")
    
    # Extract text words which lists coordinates for every single word
    words = page.get_text("words")
    # Let's print words to see where they are
    for w in words:
        text = w[4]
        rect = fitz.Rect(w[0], w[1], w[2], w[3])
        # If the word is DEAR, KISHORE, or contains date parts
        if any(x in text.upper() for x in ["DEAR", "KISHORE", "FEB", "2026", "SIGNATURE", "WARM", "REGARDS"]):
            print(f"  Word '{text}' at {rect}")
