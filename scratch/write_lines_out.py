with open(r"d:\internship\hr-panel\hr-portal-api\database\seeders\MailTemplateSeeder.php", "r", encoding="utf-8") as f:
    lines = f.readlines()

with open("scratch/view_lines_out.txt", "w", encoding="utf-8") as out:
    for idx in range(420, 480):
        if idx < len(lines):
            out.write(f"{idx+1}: {lines[idx]}")
