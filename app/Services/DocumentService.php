<?php

namespace App\Services;

use App\Models\MailTemplate;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;

class DocumentService
{
    /**
     * Render a document template to a PDF instance.
     *
     * @param string $templateName
     * @param array $variables
     * @return \Barryvdh\DomPDF\PDF
     */
    public static function render(string $templateName, array $variables = [])
    {
        $template = MailTemplate::where('template_name', $templateName)
                                ->where('active_status', 1)
                                ->first();

        if (!$template) {
            throw new \Exception("Document template '{$templateName}' not found or inactive.");
        }

        // Compile the body using Blade::render
        try {
            $compiledBody = Blade::render($template->body, $variables);
        } catch (\Exception $e) {
            Log::error("Failed to compile document template '{$templateName}': " . $e->getMessage());
            throw $e;
        }

        // Build final HTML document with style
        $style = $template->style ?? '';
        
        // Dynamically resolve local font paths
        $fontsDirUrl = 'file:///' . str_replace('\\', '/', storage_path('fonts/'));
        $style = str_replace('local-font://', $fontsDirUrl, $style);
        
        $html = "<!DOCTYPE html>
<html>
<head>
    <meta charset='utf-8'>
    <style>
        {$style}
    </style>
</head>
<body>
    {$compiledBody}
</body>
</html>";

        // Load into PDF using Puppeteer
        $tempHtmlFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'pdf_html_' . uniqid() . '.html';
        $tempPdfFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'pdf_out_' . uniqid() . '.pdf';
        
        file_put_contents($tempHtmlFile, $html);
        
        $process = new \Symfony\Component\Process\Process([
            'node',
            base_path('app/Scripts/puppeteer-pdf-generator.js'),
            $tempHtmlFile,
            $tempPdfFile
        ]);
        
        $process->setTimeout(60);
        $process->run();
        
        if (!$process->isSuccessful()) {
            @unlink($tempHtmlFile);
            @unlink($tempPdfFile);
            throw new \Exception("Puppeteer PDF generation failed: " . $process->getErrorOutput());
        }
        
        $pdfBytes = file_get_contents($tempPdfFile);
        
        @unlink($tempHtmlFile);
        @unlink($tempPdfFile);
        
        return new \App\Services\PuppeteerPdfWrapper($pdfBytes);
    }

    /**
     * Compile and return all variables needed to render a payslip.
     *
     * @param mixed $payroll
     * @param mixed|null $employee
     * @return array
     */
    public static function getPayslipVariables($payroll, $employee = null)
    {
        if (!$employee) {
            $employee = $payroll->employee;
        }

        $companyName = \App\Models\CompanySetting::where('key', 'company_name')->value('value') ?? 'Techsprout AI Labs';
        
        // Resolve company logo path locally
        $logoUrl = \App\Models\CompanySetting::where('key', 'company_logo')->value('value');
        $logoPath = null;
        if ($logoUrl) {
            $parsed = parse_url($logoUrl);
            $path = $parsed['path'] ?? '';
            if ($path) {
                $resolvedPath = public_path(substr($path, 1));
                if (file_exists($resolvedPath)) {
                    $logoPath = $resolvedPath;
                }
            }
        }
        
        // Address fallback
        $companyAddress = "8-2-293/82/A/787/1/4F/1, Road No36,4thFloor,JubileeHills,Hyderabad, Shaikpet, Telangana, India, 500033";
        
        // Month translation
        $monthName = \DateTime::createFromFormat('!m', $payroll->month)->format('F');
        
        // Employee bank details
        $bankName = $employee->bank_name ?? 'State Bank of India';
        $bankAcc = $employee->bank_account_number ?? 'xxxxxxxxxx';
        $maskedAcc = strlen($bankAcc) > 4 
            ? str_repeat('x', strlen($bankAcc) - 4) . substr($bankAcc, -4) 
            : $bankAcc;
            
        $panNum = $employee->pan_number ?? 'xxxxxxxxxx';
        $maskedPan = strlen($panNum) > 4 
            ? str_repeat('x', strlen($panNum) - 4) . substr($panNum, -4) 
            : $panNum;
            
        // Master structure rates
        $structure = $payroll->salaryStructure;
        $masterBasic = $structure->basic_salary ?? 0;
        $masterHra = $structure->hra ?? 0;
        $masterAllowances = $structure->allowances ?? 0;
        $masterBonus = $structure->bonus ?? 0;
        $masterGross = $structure->gross_salary ?? 0;

        // Actual earnings details
        $actualBasic = $payroll->items->where('type', 'earning')->where('name', 'Stipend')->first()?->amount
            ?? $payroll->items->where('type', 'earning')->where('name', 'Basic Salary')->first()?->amount 
            ?? $payroll->items->where('type', 'earning')->filter(fn($i) => str_contains(strtolower($i->name), 'basic'))->first()?->amount
            ?? $payroll->basic_salary ?? 0;
        
        $actualHra = $payroll->items->where('type', 'earning')->where('name', 'HRA')->first()?->amount
            ?? $payroll->items->where('type', 'earning')->filter(fn($i) => str_contains(strtolower($i->name), 'hra'))->first()?->amount
            ?? 0;
            
        $actualAllowances = $payroll->items->where('type', 'earning')->where('name', 'Allowances')->first()?->amount
            ?? $payroll->items->where('type', 'earning')->filter(fn($i) => str_contains(strtolower($i->name), 'allowance'))->first()?->amount
            ?? 0;

        $actualBonus = $payroll->items->where('type', 'earning')->where('name', 'Bonus')->first()?->amount
            ?? $payroll->items->where('type', 'earning')->filter(fn($i) => str_contains(strtolower($i->name), 'bonus'))->first()?->amount
            ?? 0;
            
        $isIntern = ($employee->employment_type ?? null) === 'intern';

        if ($isIntern) {
            $leftRows = [
                ['label' => 'Stipend', 'master' => $masterBasic, 'actual' => $actualBasic],
            ];
        } else {
            // Earning rows
            $leftRows = [
                ['label' => 'Basic', 'master' => $masterBasic, 'actual' => $actualBasic],
                ['label' => 'HRA', 'master' => $masterHra, 'actual' => $actualHra],
                ['label' => 'Special Allowance', 'master' => $masterAllowances, 'actual' => $actualAllowances],
            ];
            
            if ($masterBonus > 0 || $actualBonus > 0) {
                $leftRows[] = ['label' => 'Bonus', 'master' => $masterBonus, 'actual' => $actualBonus];
            }
        }
        
        // Dynamic earnings
        $knownEarningNames = ['basic salary', 'basic', 'hra', 'allowances', 'special allowance', 'bonus', 'stipend'];
        foreach ($payroll->items->where('type', 'earning') as $item) {
            $lowerName = strtolower($item->name);
            $isKnown = false;
            foreach ($knownEarningNames as $kn) {
                if (str_contains($lowerName, $kn)) {
                    $isKnown = true;
                    break;
                }
            }
            if (!$isKnown) {
                $leftRows[] = ['label' => $item->name, 'master' => 0, 'actual' => $item->amount];
            }
        }

        // Deduction rows
        $lopDeduction = (float)($payroll->lop_deduction ?? 0);
        $rightRows = [];

        // Always show LOP Deduction first if there are LOP days
        if ($lopDeduction > 0) {
            $rightRows[] = ['label' => 'LOP', 'actual' => $lopDeduction];
        }

        if (!$isIntern) {
            foreach ($payroll->items->where('type', 'deduction') as $item) {
                $label = $item->name;
                if (str_contains(strtolower($label), 'prof') || str_contains(strtolower($label), 'professional')) {
                    $label = 'Prof Tax';
                }
                $rightRows[] = ['label' => $label, 'actual' => $item->amount];
            }
            
            if (empty($rightRows)) {
                $rightRows[] = ['label' => 'Prof Tax', 'actual' => 0];
            }
        } else {
            // For interns with no LOP, keep a blank row for alignment
            if ($lopDeduction == 0) {
                $rightRows[] = ['label' => '', 'actual' => null];
            }
        }

        // Row balancing
        $maxRows = max(count($leftRows), count($rightRows));
        while (count($leftRows) < $maxRows) {
            $leftRows[] = ['label' => '', 'master' => null, 'actual' => null];
        }
        while (count($rightRows) < $maxRows) {
            $rightRows[] = ['label' => '', 'actual' => null];
        }

        $netPayWords = self::numberToWords($payroll->net_salary);

        return [
            'payroll' => $payroll,
            'employee' => $employee,
            'companyName' => $companyName,
            'logoPath' => $logoPath,
            'companyAddress' => $companyAddress,
            'monthName' => $monthName,
            'bankName' => $bankName,
            'maskedAcc' => $maskedAcc,
            'maskedPan' => $maskedPan,
            'masterBasic' => $masterBasic,
            'masterHra' => $masterHra,
            'masterAllowances' => $masterAllowances,
            'masterBonus' => $masterBonus,
            'masterGross' => $masterGross,
            'leftRows' => $leftRows,
            'rightRows' => $rightRows,
            'maxRows' => $maxRows,
            'netPayWords' => $netPayWords,
            'lopDeduction' => $lopDeduction,
        ];
    }

    private static function numberToWords($number)
    {
        $amount = (int) $number;
        if ($amount == 0) {
            return "Rupees Zero Only";
        }
        
        $thousands = array('', 'Thousand', 'Lakh', 'Crore');
        $ones = array(
            '', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine', 'Ten',
            'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen'
        );
        $tens = array('', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety');
        
        $words = [];
        
        $hundreds = $amount % 1000;
        $amount = (int) ($amount / 1000);
        
        $segments = [];
        $segments[] = $hundreds; // 0: hundreds
        
        while ($amount > 0) {
            $segments[] = $amount % 100;
            $amount = (int) ($amount / 100);
        }
        
        $numSegments = count($segments);
        for ($i = $numSegments - 1; $i >= 0; $i--) {
            $val = $segments[$i];
            if ($val == 0) {
                continue;
            }
            
            $segmentWords = "";
            if ($i == 0) {
                $h = (int) ($val / 100);
                $tens_ones = $val % 100;
                
                if ($h > 0) {
                    $segmentWords .= $ones[$h] . " Hundred ";
                }
                if ($tens_ones > 0) {
                    if ($h > 0) {
                        $segmentWords .= "and ";
                    }
                    if ($tens_ones < 20) {
                        $segmentWords .= $ones[$tens_ones] . " ";
                    } else {
                        $t = (int) ($val % 100 / 10);
                        $o = $val % 10;
                        $segmentWords .= $tens[$t] . " " . $ones[$o] . " ";
                    }
                }
            } else {
                if ($val < 20) {
                    $segmentWords .= $ones[$val] . " ";
                } else {
                    $t = (int) ($val / 10);
                    $o = $val % 10;
                    $segmentWords .= $tens[$t] . " " . $ones[$o] . " ";
                }
                $segmentWords .= $thousands[$i] . " ";
            }
            
            $words[] = trim($segmentWords);
        }
        
        return "Rupees " . implode(' ', $words) . " Only";
    }
}
