<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MailTemplate;

class MailTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $templates = [
            [
                'template_name' => 'leave_request_submitted',
                'subject' => 'New Leave Request Submitted - {{employee_name}} 📅',
                'body' => '<p>Hello <strong>{{name}}</strong>,</p>
<p>A new leave request has been submitted by <strong>{{employee_name}}</strong> and is awaiting your review.</p>

<div class="details-box">
  <table style="width: 100%; border-collapse: collapse;">
    <tr>
      <td style="padding: 6px 0; color: #334155; font-size: 14px;"><strong>Leave Type:</strong></td>
      <td style="padding: 6px 0; color: #334155; font-size: 14px;">{{leave_type}}</td>
    </tr>
    <tr>
      <td style="padding: 6px 0; color: #334155; font-size: 14px;"><strong>Dates:</strong></td>
      <td style="padding: 6px 0; color: #334155; font-size: 14px;">{{leave_dates}}</td>
    </tr>
    <tr>
      <td style="padding: 6px 0; color: #334155; font-size: 14px;"><strong>Duration:</strong></td>
      <td style="padding: 6px 0; color: #334155; font-size: 14px;"><span class="badge">{{days}} Days</span></td>
    </tr>
    <tr>
      <td style="padding: 6px 0; color: #334155; font-size: 14px;"><strong>Reason:</strong></td>
      <td style="padding: 6px 0; color: #334155; font-size: 14px;">{{reason}}</td>
    </tr>
  </table>
</div>

<p>Please log in to the HR Portal to take action on this request.</p>
<p style="margin-top: 32px; border-top: 1px solid #f1f5f9; padding-top: 20px;">Best regards,<br><strong>Techsprout HR System</strong></p>',
                'style' => '.details-box {
  background-color: #f1f5f9;
  border-radius: 12px;
  padding: 20px;
  margin: 24px 0;
}
.badge {
  background-color: #dbeafe;
  color: #1e40af;
  padding: 2px 8px;
  border-radius: 6px;
  font-weight: 600;
}',
                'active_status' => 1,
            ],
            [
                'template_name' => 'leave_request_approved',
                'subject' => 'Your leave request has been approved! 🎉',
                'body' => '<p>Hello <strong>{{name}}</strong>,</p>
<p>We are pleased to inform you that your leave request has been officially approved by the management team.</p>

<div class="details-box">
  <table style="width: 100%; border-collapse: collapse;">
    <tr>
      <td style="padding: 6px 0; color: #334155; font-size: 14px;"><strong>Leave Type:</strong></td>
      <td style="padding: 6px 0; color: #334155; font-size: 14px;">{{leave_type}}</td>
    </tr>
    <tr>
      <td style="padding: 6px 0; color: #334155; font-size: 14px;"><strong>Dates:</strong></td>
      <td style="padding: 6px 0; color: #334155; font-size: 14px;">{{leave_dates}}</td>
    </tr>
    <tr>
      <td style="padding: 6px 0; color: #334155; font-size: 14px;"><strong>Total Duration:</strong></td>
      <td style="padding: 6px 0; color: #334155; font-size: 14px;"><span class="badge">{{days}} Days</span></td>
    </tr>
  </table>
</div>

<p>Your team has been notified of your upcoming absence to ensure a smooth transition. Please make sure any pending handovers are completed before your start date.</p>
<p>Have a wonderful and restful time off!</p>
<p style="margin-top: 32px; border-top: 1px solid #f1f5f9; padding-top: 20px;">Best regards,<br><strong>Techsprout HR Team</strong></p>',
                'style' => '.details-box {
  background-color: #f1f5f9;
  border-radius: 12px;
  padding: 20px;
  margin: 24px 0;
}
.badge {
  background-color: #dbeafe;
  color: #1e40af;
  padding: 2px 8px;
  border-radius: 6px;
  font-weight: 600;
}',
                'active_status' => 1,
            ],
            [
                'template_name' => 'leave_request_rejected',
                'subject' => 'Leave Request Update: Rejected ❌',
                'body' => '<p>Hello <strong>{{name}}</strong>,</p>
<p>We regret to inform you that your leave request has been rejected.</p>

<div class="details-box">
  <table style="width: 100%; border-collapse: collapse;">
    <tr>
      <td style="padding: 6px 0; color: #334155; font-size: 14px;"><strong>Leave Type:</strong></td>
      <td style="padding: 6px 0; color: #334155; font-size: 14px;">{{leave_type}}</td>
    </tr>
    <tr>
      <td style="padding: 6px 0; color: #334155; font-size: 14px;"><strong>Dates:</strong></td>
      <td style="padding: 6px 0; color: #334155; font-size: 14px;">{{leave_dates}}</td>
    </tr>
    <tr>
      <td style="padding: 6px 0; color: #334155; font-size: 14px;"><strong>Reason for Rejection:</strong></td>
      <td style="padding: 6px 0; color: #334155; font-size: 14px;">{{rejection_reason}}</td>
    </tr>
  </table>
</div>

<p>If you have any questions, please reach out to your manager or HR department.</p>
<p style="margin-top: 32px; border-top: 1px solid #f1f5f9; padding-top: 20px;">Best regards,<br><strong>Techsprout HR Team</strong></p>',
                'style' => '.details-box {
  background-color: #fef2f2;
  border-left: 4px solid #ef4444;
  border-radius: 8px;
  padding: 20px;
  margin: 24px 0;
}',
                'active_status' => 1,
            ],
            [
                'template_name' => 'employee_payslip_delivery',
                'subject' => 'Payslip for {{month}} {{year}} 📄',
                'body' => '<p>Dear {{name}},</p>
<p>Please find attached your payslip for the month of {{month}} {{year}} for your reference.</p>
<p>Should you require any clarification or further assistance, please feel free to reach out.</p>
<p style="margin-top: 32px; border-top: 1px solid #f1f5f9; padding-top: 20px;">Best regards,<br>
<strong>Akanksha Guntuku</strong><br>
HR Manager<br>
Techsprout AI Labs Pvt Ltd</p>',
                'style' => '',
                'active_status' => 1,
            ],
            [
                'template_name' => 'candidate_offer_letter_delivery',
                'subject' => 'Offer of Employment - Techsprout AI Labs Pvt. Ltd. 🎉',
                'body' => '<p>Dear {{name}},</p>
<p>Greetings from Techsprout AI Labs Pvt. Ltd.</p>
<p>Please find attached your Employment Offer Letter for the position of <strong>{{position}}</strong> at our Hyderabad office. Your Employment will commence from <strong>{{joining_date}}</strong>, which includes 6 months of Probation Period.</p>
<p>We request that you kindly review the attached offer letter and confirm your acceptance by replying to this email at the earliest.</p>
<p>We are excited to have you onboard and look forward to working with you.</p>
<p style="margin-top: 32px; border-top: 1px solid #f1f5f9; padding-top: 20px;">Best regards,<br>
<strong>Akanksha Guntuku</strong><br>
HR Team<br>
Techsprout AI Labs Pvt. Ltd.</p>',
                'style' => '',
                'active_status' => 1,
            ],
            [
                'template_name' => 'candidate_offer_letter_transition',
                'subject' => 'Offer of Full-Time Employment (Intern Conversion) - Techsprout AI Labs Pvt. Ltd. 🎉',
                'body' => '<p>Dear {{name}},</p>
<p>Greetings from Techsprout AI Labs Pvt. Ltd.</p>
<p>We are pleased to inform you that, based on your performance and contribution during your internship, we would like to offer you a full-time position as a <strong>{{position}}</strong> with Techsprout AI Labs Pvt. Ltd. at our Hyderabad office.</p>
<p>Please find attached your Full-Time Offer Letter for your review. Your joining date for the full-time role will be <strong>{{joining_date}}</strong>. Kindly go through the offer letter and confirm your acceptance at the earliest.</p>
<p>We appreciate your efforts during Internship and are excited to have you continue your journey with us as a full-time employee of the team.</p>
<p>Wishing you great success ahead.</p>
<p style="margin-top: 32px; border-top: 1px solid #f1f5f9; padding-top: 20px;">Best regards,<br>
<strong>Akanksha Guntuku</strong><br>
HR Manager<br>
Techsprout AI Labs Pvt. Ltd.</p>',
                'style' => '',
                'active_status' => 1,
            ],
            [
                'template_name' => 'candidate_onboarding_welcome',
                'subject' => 'Welcome to Onboarding at Techsprout! 👋',
                'body' => '<p>Hello <strong>{{name}}</strong>,</p>
<p>Congratulations on your selection for the position of <strong>{{position}}</strong> in the <strong>{{department}}</strong> department! We are excited to welcome you to our team.</p>
<p>To begin your onboarding process, please click the button below to access your onboarding portal, where you can complete your details and upload the required documents.</p>
<div style="margin: 24px 0; text-align: center;">
  <a href="{{portal_link}}" style="background-color: #3b82f6; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: bold; display: inline-block;">Access Onboarding Portal</a>
</div>
<p>Your joining date is scheduled for <strong>{{joining_date}}</strong>.</p>
<p style="margin-top: 32px; border-top: 1px solid #f1f5f9; padding-top: 20px;">Best regards,<br><strong>Techsprout HR Team</strong></p>',
                'style' => '',
                'active_status' => 1,
            ],
            [
                'template_name' => 'candidate_onboarding_approved',
                'subject' => 'Onboarding Documents Verified and Approved! ✅',
                'body' => '<p>Hello <strong>{{name}}</strong>,</p>
<p>We are pleased to inform you that your onboarding documents and information have been successfully verified and approved.</p>
<p>Your official offer letter is being generated and will be sent to you shortly. We look forward to having you join us on <strong>{{joining_date}}</strong>.</p>
<p style="margin-top: 32px; border-top: 1px solid #f1f5f9; padding-top: 20px;">Best regards,<br><strong>Techsprout HR Team</strong></p>',
                'style' => '',
                'active_status' => 1,
            ],
            [
                'template_name' => 'candidate_onboarding_rejected',
                'subject' => 'Action Required: Onboarding Update ⚠️',
                'body' => '<p>Hello <strong>{{name}}</strong>,</p>
<p>Thank you for submitting your onboarding details. Upon review, we found that some information or documents require correction.</p>

<div class="rejection-box">
  <strong>Feedback / Reason:</strong><br>
  {{rejection_reason}}
</div>

<p>Please log in to your onboarding portal to review the feedback, update the required documents, and resubmit for approval.</p>
<p style="margin-top: 32px; border-top: 1px solid #f1f5f9; padding-top: 20px;">Best regards,<br><strong>Techsprout HR Team</strong></p>',
                'style' => '.rejection-box {
  background-color: #fff5f5;
  border-left: 4px solid #f56565;
  color: #c53030;
  padding: 15px;
  margin: 20px 0;
  border-radius: 4px;
}',
                'active_status' => 1,
            ],
            [
                'template_name' => 'employee_offboarding_complete',
                'subject' => 'Relieving and Exit Clearance Confirmation 📄',
                'body' => '<p>Hello <strong>{{name}}</strong>,</p>
<p>This email is to confirm that your exit process at the organization has been completed successfully.</p>
<p>We have successfully verified all departmental clearances, asset returns, and IT deactivations. Your Full & Final settlement has been processed. Your last working day was <strong>{{last_working_day}}</strong>.</p>

<p>Please find attached your official <strong>Exit Clearance & Relieving Letter PDF</strong>.</p>

<p>We thank you for your service and wish you the very best in your future career endeavors.</p>
<p style="margin-top: 32px; border-top: 1px solid #f1f5f9; padding-top: 20px;">Best regards,<br><strong>Techsprout HR Team</strong></p>',
                'style' => '',
                'active_status' => 1,
            ],
            [
                'template_name' => 'salary_revision_notice',
                'subject' => 'Important: Notification of Salary Structure Revision 📈',
                'body' => '<p>Hello <strong>{{name}}</strong>,</p>
<p>Following our performance appraisals and market benchmark reviews, we are excited to notify you of an adjustment to your compensation plan.</p>

<div class="increment-alert">
  <strong>Congratulations!</strong> You have received a <strong>+{{increment_percentage}}%</strong> revision.
</div>

<table class="salary-compare">
  <thead>
    <tr>
      <th>Salary Type</th>
      <th>Old Gross (Monthly)</th>
      <th>New Gross (Monthly)</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td><strong>Gross Amount</strong></td>
      <td><span class="old-val">₹{{old_gross}}</span></td>
      <td><span class="new-val">₹{{new_gross}}</span></td>
    </tr>
  </tbody>
</table>

<p>This revision is effective starting from <strong>{{effective_date}}</strong>. You will receive your official revised salary structure document in the mail shortly.</p>
<p>We want to thank you for your continuous dedication and hard work. Your contributions are highly valued here.</p>
<p style="margin-top: 32px; border-top: 1px solid #f1f5f9; padding-top: 20px;">Warm regards,<br><strong>Techsprout Finance & HR Team</strong></p>',
                'style' => '.increment-alert {
  background-color: #ecfdf5;
  border: 1px solid #a7f3d0;
  color: #065f46;
  border-radius: 12px;
  padding: 16px;
  margin: 20px 0;
  text-align: center;
  font-size: 15px;
}
.salary-compare {
  width: 100%;
  margin: 24px 0;
  border-collapse: collapse;
}
.salary-compare th, .salary-compare td {
  padding: 12px;
  text-align: left;
  border-bottom: 1px solid #f1f5f9;
}
.salary-compare th {
  background-color: #f8fafc;
  color: #64748b;
  font-size: 12px;
  text-transform: uppercase;
}
.old-val {
  color: #94a3b8;
  text-decoration: line-through;
}
.new-val {
  color: #10b981;
  font-weight: 700;
}',
                'active_status' => 1,
            ]
        ];

        foreach ($templates as $tmpl) {
            MailTemplate::updateOrCreate(
                ['template_name' => $tmpl['template_name']],
                [
                    'subject' => $tmpl['subject'],
                    'body' => $tmpl['body'],
                    'style' => $tmpl['style'],
                    'active_status' => $tmpl['active_status'],
                ]
            );
        }
    }
}
