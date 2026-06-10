import puppeteer from 'puppeteer';
import fs from 'fs';
import process from 'process';
import path from 'path';

(async () => {
    try {
        const args = process.argv.slice(2);
        if (args.length < 2) {
            console.error("Usage: node puppeteer-pdf-generator.js <html_file_path> <pdf_file_path>");
            process.exit(1);
        }

        const htmlFilePath = args[0];
        const pdfFilePath = args[1];

        // Launch headless Chromium browser
        const browser = await puppeteer.launch({
            headless: 'new',
            args: [
                '--no-sandbox',
                '--disable-setuid-sandbox',
                '--disable-dev-shm-usage',
                '--font-render-hinting=none',
                '--allow-file-access-from-files'
            ]
        });

        const page = await browser.newPage();
        
        // Disable viewport restrictions for exact print scaling
        await page.emulateMediaType('print');
        
        // Load the HTML file via file:// URL to grant local filesystem access (for fonts)
        const absoluteHtmlPath = path.resolve(htmlFilePath);
        await page.goto('file://' + absoluteHtmlPath, { 
            waitUntil: 'networkidle0' 
        });

        // Print page to PDF using A4 size and zero default margin (respecting CSS @page margins)
        await page.pdf({
            path: pdfFilePath,
            format: 'A4',
            printBackground: true,
            margin: {
                top: 0,
                bottom: 0,
                left: 0,
                right: 0
            }
        });

        await browser.close();
        process.exit(0);
    } catch (err) {
        console.error("Puppeteer PDF generation failed:", err);
        process.exit(1);
    }
})();
