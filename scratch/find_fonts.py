import os

workspace = r"d:\internship\hr-panel"
for root, dirs, files in os.walk(workspace):
    for file in files:
        if file.lower().endswith(('.ttf', '.woff', '.woff2', '.otf')):
            print(os.path.join(root, file))
