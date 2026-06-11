import fitz

doc = fitz.open(r"C:\Users\91756\Downloads\Payslip Template.pdf")
page = doc[0]
html_content = page.get_text("html")

with open(r"d:\internship\hr-panel\hr-portal-api\scratch\ref_payslip_html.html", "w", encoding="utf-8") as f:
    f.write(html_content)

print("Saved HTML representation of reference payslip to scratch/ref_payslip_html.html")
