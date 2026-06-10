import sys
import io
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')

with open(r"d:\internship\hr-panel\hr-portal-api\database\seeders\MailTemplateSeeder.php", "r", encoding="utf-8") as f:
    lines = f.readlines()

def print_around(line_num, label):
    print(f"\n=== {label} (line {line_num}) ===")
    start = max(0, line_num - 5)
    end = min(len(lines), line_num + 20)
    for i in range(start, end):
        line = lines[i].rstrip()
        # Limit very long lines (like base64 or long style/body content) to first 120 chars
        if len(line) > 120:
            line = line[:120] + " ... [TRUNCATED]"
        print(f"{i+1}: {line}")

print_around(319, "FREE INTERN")
print_around(535, "PAID INTERN")
print_around(750, "FULL TIME")
