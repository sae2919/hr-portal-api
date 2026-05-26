<?php

namespace App\Mail;

use App\Models\Payroll;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Queue\SerializesModels;
// 💡 FIXED: Corrected package namespace import
use Barryvdh\DomPDF\Facade\Pdf; 

class EmployeePayslipMail extends Mailable
{
    use Queueable, SerializesModels;

    public $payroll;

    public function __construct(Payroll $payroll)
    {
        $this->payroll = $payroll;
    }

    public function envelope(): Envelope
    {
        $monthName = date("F", mktime(0, 0, 0, $this->payroll->month, 10));
        return new Envelope(
            subject: "Your Payslip for {$monthName} {$this->payroll->year}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.payslip', // Refers to resources/views/emails/payslip.blade.php
        );
    }

    public function attachments(): array
    {
        // 💡 Uses the corrected Barryvdh PDF generator class cleanly
        $pdf = Pdf::loadView('pdf.payslip', ['payroll' => $this->payroll]);
        
        $monthName = date("F", mktime(0, 0, 0, $this->payroll->month, 10));

        return [
            Attachment::fromData(fn () => $pdf->output(), "Payslip-{$monthName}-{$this->payroll->year}.pdf")
                ->withMime('application/pdf'),
        ];
    }
}