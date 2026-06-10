<?php

namespace App\Services;

class PuppeteerPdfWrapper
{
    /**
     * @var string
     */
    protected $pdfBytes;

    /**
     * Create a new PuppeteerPdfWrapper instance.
     *
     * @param string $pdfBytes
     */
    public function __construct(string $pdfBytes)
    {
        $this->pdfBytes = $pdfBytes;
    }

    /**
     * Get the raw PDF binary content.
     *
     * @return string
     */
    public function output()
    {
        return $this->pdfBytes;
    }

    /**
     * Return a download response for the PDF.
     *
     * @param string $filename
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function download(string $filename = 'document.pdf')
    {
        return response()->streamDownload(function () {
            echo $this->pdfBytes;
        }, $filename, [
            'Content-Type' => 'application/pdf',
        ]);
    }

    /**
     * Return a stream (inline browser display) response for the PDF.
     *
     * @param string $filename
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function stream(string $filename = 'document.pdf')
    {
        return response()->stream(function () {
            echo $this->pdfBytes;
        }, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
        ]);
    }
}
