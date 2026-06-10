from PIL import Image, ImageDraw
import base64
import os

# A4 dimensions in points: 595.3 x 842.25
# Header height: 22 points
# Footer height: 28.4 points

scale = 4  # scale factor for high resolution (300 DPI equivalent)

def generate_header():
    w, h = int(595.5 * scale), int(22 * scale)
    img = Image.new("RGBA", (w, h), (0, 0, 0, 0))
    draw = ImageDraw.Draw(img)
    
    # Polygon 1 (Sky Blue, 25% opacity)
    p1 = [(0, 0), (234.56 * scale, 0), (208.88 * scale, 22 * scale), (0, 22 * scale)]
    draw.polygon(p1, fill=(4, 150, 255, 64))
    
    # Polygon 2 (Sky Blue, 100% opacity)
    p2 = [(0, 0), (203.34 * scale, 0), (177.66 * scale, 22 * scale), (0, 22 * scale)]
    draw.polygon(p2, fill=(4, 150, 255, 255))
    
    out_path = r"C:\Users\91756\.gemini\antigravity-ide\brain\7d3c3efd-0e98-4091-b648-313ef626c834\header_bg.png"
    img.save(out_path, "PNG")
    print(f"Saved header background to {out_path}")
    
    # Print base64
    with open(out_path, "rb") as image_file:
        encoded = base64.b64encode(image_file.read()).decode('utf-8')
    with open(r"d:\internship\hr-panel\hr-portal-api\scratch\header_base64.txt", "w") as f:
        f.write(encoded)

def generate_footer():
    w, h = int(595.5 * scale), int(28.4 * scale)
    img = Image.new("RGBA", (w, h), (0, 0, 0, 0))
    draw = ImageDraw.Draw(img)
    
    # Polygon 1 (Sky Blue, 25% opacity)
    p1 = [(246.71 * scale, 0), (595.5 * scale, 0), (595.5 * scale, 28.4 * scale), (213.79 * scale, 28.4 * scale)]
    draw.polygon(p1, fill=(4, 150, 255, 64))
    
    # Polygon 2 (Sky Blue, 100% opacity)
    p2 = [(325.06 * scale, 0), (595.5 * scale, 0), (595.5 * scale, 28.4 * scale), (292.14 * scale, 28.4 * scale)]
    draw.polygon(p2, fill=(4, 150, 255, 255))
    
    out_path = r"C:\Users\91756\.gemini\antigravity-ide\brain\7d3c3efd-0e98-4091-b648-313ef626c834\footer_bg.png"
    img.save(out_path, "PNG")
    print(f"Saved footer background to {out_path}")
    
    # Print base64
    with open(out_path, "rb") as image_file:
        encoded = base64.b64encode(image_file.read()).decode('utf-8')
    with open(r"d:\internship\hr-panel\hr-portal-api\scratch\footer_base64.txt", "w") as f:
        f.write(encoded)

generate_header()
generate_footer()
