<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

use App\Models\OnboardingRequest;
use App\Models\OffboardingRequest;
use App\Models\Payroll;
use App\Services\DocumentService;

echo "--- TESTING DYNAMIC PDF GENERATION ---\n\n";

// 1. Offer Letters
$onboardingTypes = ['free_intern', 'intern', 'full_time'];
foreach ($onboardingTypes as $type) {
    $onboardingRequest = OnboardingRequest::where('onboarding_type', $type)->first() ?? OnboardingRequest::first();
    if ($onboardingRequest) {
        // Temporarily set type to test
        $originalType = $onboardingRequest->onboarding_type;
        $onboardingRequest->onboarding_type = $type;

        echo "Testing Offer Letter for type '{$type}' with Candidate '{$onboardingRequest->candidate_name}'...\n";
        
        $templateName = match ($type) {
            'free_intern' => 'free_internship_offer_letter',
            'intern'      => 'paid_internship_offer_letter',
            default       => 'full_time_offer_letter',
        };

        $letterDate = \Carbon\Carbon::now();
        $joiningDate = \Carbon\Carbon::parse($onboardingRequest->joining_date);

        $variables = [
            'candidate' => $onboardingRequest,
            'candidate_name' => $onboardingRequest->candidate_name,
            'position' => $onboardingRequest->position,
            'duration' => $onboardingRequest->duration ?? '3 months',
            'joining_date' => $joiningDate->format('d/m/Y'),
            'letter_date' => $letterDate->format('d-F Y'),
            'stipend' => number_format((float)($onboardingRequest->ctc ?? 0)),
            'acceptance_date' => $letterDate->copy()->addDays(2)->format('d-m-Y'),
        ];

        try {
            $pdf = DocumentService::render($templateName, $variables);
            $destPath = __DIR__ . "/test_offer_{$type}_dynamic.pdf";
            file_put_contents($destPath, $pdf->output());
            echo "SUCCESS: Saved to {$destPath}\n";
        } catch (\Exception $e) {
            echo "FAILED for type '{$type}': " . $e->getMessage() . "\n";
        }

        // Restore original type
        $onboardingRequest->onboarding_type = $originalType;
    } else {
        echo "No onboarding request found to test for type '{$type}'.\n";
    }
}

// 2. Exit/Relieving Letter
$offboarding = OffboardingRequest::with(['employee.department', 'employee.designation'])->first();
if ($offboarding) {
    $employee = $offboarding->employee;
    echo "\nTesting Exit/Relieving Letter for Employee '{$employee->full_name}'...\n";
    
    $salutation = 'Mr.';
    if (isset($employee->gender) && strtolower($employee->gender) === 'female') {
        $salutation = 'Ms.';
    }
    
    $empFullName = ($employee->first_name ?? '') . ' ' . ($employee->last_name ?? '');
    $joiningDateFormatted = $employee->joining_date ? \Carbon\Carbon::parse($employee->joining_date)->format('d-M-Y') : '';
    $lastDayFormatted = $offboarding->last_working_day ? \Carbon\Carbon::parse($offboarding->last_working_day)->format('d-M-Y') : '';
    
    $companyName = \App\Models\CompanySetting::getValue('company_name') ?? 'Techsprout AI Labs Pvt. Ltd';
    $companyLogo = \App\Models\CompanySetting::getValue('company_logo') ?? null;
    $designationName = $employee->designation->name ?? ($employee->designation->title ?? '-');

    $variables = [
        'offboarding' => $offboarding,
        'employee' => $employee,
        'salutation' => $salutation,
        'employee_name' => $empFullName,
        'company_name' => $companyName,
        'company_logo' => $companyLogo,
        'designation' => $designationName,
        'joining_date' => $joiningDateFormatted,
        'last_working_day' => $lastDayFormatted,
        'employee_code' => $employee->employee_code ?? '-',
        'date' => \Carbon\Carbon::now()->format('d-M-Y'),
    ];

    try {
        $pdf = DocumentService::render('exit_relieving_letter', $variables);
        $destPath = __DIR__ . "/test_exit_dynamic.pdf";
        file_put_contents($destPath, $pdf->output());
        echo "SUCCESS: Saved to {$destPath}\n";
    } catch (\Exception $e) {
        echo "FAILED for Exit/Relieving: " . $e->getMessage() . "\n";
    }
} else {
    echo "\nNo offboarding request found to test.\n";
}

// 3. Payslip
$payroll = Payroll::with(['employee.department', 'salaryStructure', 'items'])->first();
if ($payroll) {
    echo "\nTesting Payslip for Employee '{$payroll->employee->full_name}'...\n";
    
    try {
        $variables = DocumentService::getPayslipVariables($payroll, $payroll->employee);
        $pdf = DocumentService::render('monthly_payslip_template', $variables);
        $destPath = __DIR__ . "/test_payslip_dynamic.pdf";
        file_put_contents($destPath, $pdf->output());
        echo "SUCCESS: Saved to {$destPath}\n";
    } catch (\Exception $e) {
        echo "FAILED for Payslip: " . $e->getMessage() . "\n";
    }
} else {
    echo "\nNo payroll record found to test.\n";
}
