<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payroll;
use Barryvdh\DomPDF\Facade\Pdf;

class PayslipController extends Controller
{
    public function download(Payroll $payroll)
    {
        $user = auth()->user();

        if ($user->role !== 'admin' && !$user->is_admin) {
            if (!$user->employee || $payroll->employee_id !== $user->employee->id) {
                abort(403, 'You are not authorized to view or download this payslip.');
            }
            if ($payroll->status !== 'paid') {
                abort(403, 'Payslip is not available for download until marked as paid.');
            }
        }

        $payroll->load([
            'employee.department'
        ]);

        $variables = \App\Services\DocumentService::getPayslipVariables($payroll);
        $pdf = \App\Services\DocumentService::render('monthly_payslip_template', $variables);

        return $pdf->download(
            'payslip-'.$payroll->id.'.pdf'
        );
    }
}