from PIL import Image
import os

jpeg_path = r"d:\internship\hr-panel\hr-portal-web\public\logo.jpeg"
png_path = r"d:\internship\hr-panel\hr-portal-web\public\logo.png"
brand_png_path = r"d:\internship\hr-panel\hr-portal-web\public\logo-brand.png"

try:
    img = Image.open(jpeg_path)
    # Convert to RGB if it is CMYK or other modes
    if img.mode != 'RGBA':
        img = img.convert('RGBA')
    
    # Save as PNG
    img.save(png_path, "PNG")
    img.save(brand_png_path, "PNG")
    print("Logo successfully converted to real PNG!")
    print(f"PNG size: {os.path.getsize(png_path)} bytes")
    print(f"Brand PNG size: {os.path.getsize(brand_png_path)} bytes")
except Exception as e:
    print(f"Error converting logo: {e}")
