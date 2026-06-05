<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('mail_templates')
            ->where('template_name', 'candidate_onboarding_welcome')
            ->update([
                'body' => '<p>Hello <strong>{{name}}</strong>,</p>
<p>Congratulations on your selection for the position of <strong>{{position}}</strong> in the <strong>{{department}}</strong> department! We are excited to welcome you to our team.</p>
<p>To begin your onboarding process, please click the button below to access your onboarding portal, where you can complete your details and upload the required documents.</p>
<div style="margin: 24px 0; text-align: center;">
  <a href="{{portal_link}}" style="background-color: #3b82f6; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: bold; display: inline-block;">Access Onboarding Portal</a>
</div>
<p style="color: #ef4444; font-weight: 500; font-size: 14px;">Please note: This secure access link will expire in 48 hours. Ensure that you submit your details and upload the required files within 48 hours.</p>
<p>Your joining date is scheduled for <strong>{{joining_date}}</strong>.</p>
<p style="margin-top: 32px; border-top: 1px solid #f1f5f9; padding-top: 20px;">Best regards,<br><strong>Techsprout HR Team</strong></p>'
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('mail_templates')
            ->where('template_name', 'candidate_onboarding_welcome')
            ->update([
                'body' => '<p>Hello <strong>{{name}}</strong>,</p>
<p>Congratulations on your selection for the position of <strong>{{position}}</strong> in the <strong>{{department}}</strong> department! We are excited to welcome you to our team.</p>
<p>To begin your onboarding process, please click the button below to access your onboarding portal, where you can complete your details and upload the required documents.</p>
<div style="margin: 24px 0; text-align: center;">
  <a href="{{portal_link}}" style="background-color: #3b82f6; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: bold; display: inline-block;">Access Onboarding Portal</a>
</div>
<p>Your joining date is scheduled for <strong>{{joining_date}}</strong>.</p>
<p style="margin-top: 32px; border-top: 1px solid #f1f5f9; padding-top: 20px;">Best regards,<br><strong>Techsprout HR Team</strong></p>'
            ]);
    }
};
