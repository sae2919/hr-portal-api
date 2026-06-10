import sys

print("Python version:", sys.version)

libraries = ['fitz', 'pdf2image', 'pdfplumber', 'pypdf', 'PyPDF2', 'reportlab', 'PIL']
for lib in libraries:
    try:
        __import__(lib)
        print(f"Library '{lib}' is installed.")
    except ImportError:
        print(f"Library '{lib}' is NOT installed.")
