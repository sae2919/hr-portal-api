import os

# Read base64 strings
with open(r"d:\internship\hr-panel\hr-portal-api\scratch\header_base64.txt", "r") as f:
    header_b64 = f.read().strip()

with open(r"d:\internship\hr-panel\hr-portal-api\scratch\footer_base64.txt", "r") as f:
    footer_b64 = f.read().strip()

# Read the test php file
test_php_path = r"d:\internship\hr-panel\hr-portal-api\scratch\test_svg_dompdf.php"
with open(test_php_path, "r", encoding="utf-8") as f:
    content = f.read()

# Replace SVG blocks with base64 img tags
svg_header_target = """    <div class='header-bg'>
        <svg width='100%' height='22' viewBox='0 0 595.5 22' preserveAspectRatio='none' style='display: block;'>
            <path d='M 0,0 L 146.7,0 L 124.8,22 L 0,22 Z' fill='#0496ff' fill-opacity='0.25' />
            <path d='M 0,0 L 115.4,0 L 93.6,22 L 0,22 Z' fill='#0496ff' fill-opacity='1.0' />
        </svg>
    </div>"""

img_header_replacement = f"""    <div class='header-bg'>
        <img src='data:image/png;base64,{header_b64}' style='width: 100%; height: 100%; display: block;' />
    </div>"""

content = content.replace(svg_header_target, img_header_replacement)

svg_footer_target = """    <div class='footer-bg'>
        <svg width='100%' height='28.4' viewBox='0 0 595.5 28.4' preserveAspectRatio='none' style='display: block;'>
            <path d='M 322.7,0 L 595.5,0 L 595.5,28.4 L 293.2,28.4 Z' fill='#0496ff' fill-opacity='0.25' />
            <path d='M 292.1,0 L 595.5,0 L 595.5,28.4 L 262.5,28.4 Z' fill='#0496ff' fill-opacity='1.0' />
        </svg>
    </div>"""

img_footer_replacement = f"""    <div class='footer-bg'>
        <img src='data:image/png;base64,{footer_b64}' style='width: 100%; height: 100%; display: block;' />
    </div>"""

content = content.replace(svg_footer_target, img_footer_replacement)

with open(test_php_path, "w", encoding="utf-8") as f:
    f.write(content)

print("Injected base64 images into test_svg_dompdf.php")
