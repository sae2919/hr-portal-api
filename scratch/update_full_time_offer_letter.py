import re
import os

seeder_path = r"d:\internship\hr-panel\hr-portal-api\database\seeders\MailTemplateSeeder.php"
scratch_dir = r"d:\internship\hr-panel\hr-portal-api\scratch"

with open(seeder_path, "r", encoding="utf-8") as f:
    code = f.read()

# 1. Read the base64 files
with open(os.path.join(scratch_dir, "header_base64.txt"), "r", encoding="utf-8") as f:
    header_base64 = f.read().strip()

with open(os.path.join(scratch_dir, "footer_base64.txt"), "r", encoding="utf-8") as f:
    footer_base64 = f.read().strip()

with open(os.path.join(scratch_dir, "signature_base64.txt"), "r", encoding="utf-8") as f:
    signature_base64 = f.read().strip()

# 2. Extract logo base64 from code
# Let's search for the logo base64 inside free_internship_offer_letter body
pos_free = code.find("'template_name' => 'free_internship_offer_letter'")
if pos_free == -1:
    pos_free = code.find('"template_name' + '" => "free_internship_offer_letter"')

logo_match = re.search(r'data:image/jpeg;base64,([a-zA-Z0-9+/=\s\r\n]+)', code[pos_free:pos_free+50000])
if not logo_match:
    raise ValueError("Could not find logo base64 in free_internship_offer_letter body!")

logo_base64 = re.sub(r'\s+', '', logo_match.group(1))
print("Successfully extracted logo base64 (length={})".format(len(logo_base64)))

# 3. Construct the HTML body for full_time_offer_letter
full_time_body = f"""<div class="header-bg"><img src="data:image/png;base64,{header_base64}" style="width: 100%; height: 100%; display: block;" /></div>
    <div class="footer-bg"><img src="data:image/png;base64,{footer_base64}" style="width: 100%; height: 100%; display: block;" /></div>

    <div class="header">
        <table>
            <tr>
                <td style="text-align: left;">
                    <img src="data:image/jpeg;base64,{logo_base64}" style="width: 238pt; height: 76.5pt; display: block;" />
                </td>
                <td class="company-address">
                    <span style="font-weight: bold; display: block; margin-bottom: 3px;">Techsprout AI Labs Pvt. Ltd</span>
                    501, Manjeera Majestic Commercial,<br>
                    JNTU Road, KPHB, Hyderabad.<br>
                    <a href="https://www.techsprout.ai">www.techsprout.ai</a>
                </td>
            </tr>
        </table>
    </div>

    <div style="text-align: right; font-family: \\'Inter\\', sans-serif; font-size: 11pt; font-weight: bold; color: #28326e; margin-top: 10px; margin-bottom: 5px;">
        {{{{ strtoupper(\\\\Carbon\\\\Carbon::parse($letter_date ?? now())->format(\\'d-M Y\\')) }}}}
    </div>
    <div style="font-family: \\'Inter\\', sans-serif; font-size: 11pt; font-weight: bold; color: #28326e; margin-bottom: 15px; text-align: left;">
        DEAR {{{{$candidate_name}}}}
    </div>

    <div class="paragraph">
        We are pleased to extend to you an offer of employment with <strong>Techsprout AI Labs Pvt. Ltd.</strong> for the position of <strong>{{{{$position}}}}</strong>, reporting at our office located in Hyderabad.
    </div>

    <div style="background-color: #f1f5f9; border-radius: 8px; padding: 15px; margin-top: 15px; margin-bottom: 15px; font-family: \\'Inter\\', sans-serif; font-size: 11pt;">
        <table style="width: 100%; border-collapse: collapse;">
            <tr><td style="padding: 4px 0; font-weight: bold; color: #475569; width: 180px;">Candidate Name:</td><td style="padding: 4px 0; color: #0f172a; font-weight: bold;">{{{{ $candidate_name }}}}</td></tr>
            <tr><td style="padding: 4px 0; font-weight: bold; color: #475569;">Proposed Position:</td><td style="padding: 4px 0; color: #0f172a; font-weight: bold;">{{{{ $position }}}}</td></tr>
            <tr><td style="padding: 4px 0; font-weight: bold; color: #475569;">Proposed CTC:</td><td style="padding: 4px 0; color: #0f172a; font-weight: bold;">&#8377;{{{{ $stipend }}}} / Year</td></tr>
        </table>
    </div>

    <div class="section-title">1. Letter of Offer</div>
    <div class="paragraph">
        Your employment will be subject to the terms and conditions outlined in the official employment agreement. Your scheduled start date will be <strong>{{{{ $joining_date }}}}</strong>.
    </div>

    <div class="section-title">2. Notice Period &amp; Probation</div>
    <div class="paragraph">
        You will be on a probation period of 6 months from your date of joining. During this period, either party may terminate the employment by providing 15 days\\' written notice. Upon successful completion of the probation period, the notice period will be 30 days.
    </div>

    <div style="background-color: #eff6ff; border-left: 4px solid #3b82f6; padding: 12px 16px; margin: 15px 0; border-radius: 0 8px 8px 0; font-family: \\'Inter\\', sans-serif;">
        <strong>Important Joining Instructions:</strong><br>
        Please bring a copy of your PAN card, Aadhaar card, educational certificates, and previous experience/relieving letters on your date of joining for the onboarding document verification process.
    </div>

    <div class="section-title">3. Acceptance of Offer</div>
    <div class="paragraph">
        Please sign and return a scanned copy of this letter by <strong>{{{{ \\\\Carbon\\\\Carbon::parse($letter_date ?? now())->addDays(2)->format(\\'d-m-Y\\') }}}}</strong> to confirm your acceptance. Failure to do so within the given timeframe will render this employment offer void.
    </div>

    <div class="paragraph" style="margin-top:20px;">
        We look forward to welcoming you to Techsprout AI Labs Pvt. Ltd. and are excited about the value you will bring to our growing team.
    </div>

    <div class="signature-section">
        <p style="margin-bottom:8px; font-weight: bold;">Warm regards,</p>
        <div style="margin-bottom: 8px;">
            <img src="data:image/png;base64,{signature_base64}" style="width: 120pt; height: 37.5pt; display: block;" />
        </div>
        <strong style="color: #000000;">Vishwanath Srirangam</strong><br>
        <span style="color: #000000;">Founder &amp; CEO</span><br>
        <div class="address-block">
            Techsprout AI Labs Pvt. Ltd.<br>
            501, Manjeera Majestic Commercial,<br>
            KPHB, Hyderabad<br>
            <a href="https://www.techsprout.ai">www.techsprout.ai</a>
        </div>
    </div>"""

# 4. Construct style block (exactly identical to paid internship)
pos_paid = code.find("'template_name' => 'paid_internship_offer_letter'")
style_start = code.find("'style' => '", pos_paid)
if style_start == -1:
    raise ValueError("Could not find style in paid_internship_offer_letter!")

style_end = code.find("',\n                'active_status' => 1,", style_start)
if style_end == -1:
    style_end = code.find("',\r\n                'active_status' => 1,", style_start)

full_time_style = code[style_start + 12:style_end]
print("Using style of paid template (length={})".format(len(full_time_style)))

# 5. Find full_time_offer_letter in code and replace body & style
pos_ft = code.find("'template_name' => 'full_time_offer_letter'")
if pos_ft == -1:
    raise ValueError("full_time_offer_letter not found in seeder!")

# We need to find body and style keys for full_time_offer_letter
ft_body_start = code.find("'body' => '", pos_ft)
ft_body_end = code.find("',\n                'style' => '", ft_body_start)
old_body = code[ft_body_start + 11:ft_body_end]

ft_style_start = code.find("'style' => '", ft_body_end)
ft_style_end = code.find("',\n                'active_status' => 1,", ft_style_start)
old_style = code[ft_style_start + 12:ft_style_end]

print("Replacing body & style for full_time_offer_letter...")
new_code = code[:ft_body_start + 11] + full_time_body + code[ft_body_end:ft_style_start + 12] + full_time_style + code[ft_style_end:]

# Write back to MailTemplateSeeder.php
with open(seeder_path, "w", encoding="utf-8") as f:
    f.write(new_code)

print("MailTemplateSeeder.php updated successfully!")
