import puppeteer from 'puppeteer';
import path from 'path';
import fs from 'fs';

(async () => {
    const html = `
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            @page {
                size: A4;
                margin: 115pt 37pt 60pt 37pt;
            }
            body {
                font-family: sans-serif;
                margin: 0;
                padding: 0;
            }
            .header {
                position: fixed;
                top: -95pt;
                left: 0;
                right: 0;
                height: 80pt;
                background-color: rgba(255, 0, 0, 0.3);
                border: 2px solid red;
            }
            .footer {
                position: fixed;
                bottom: -50pt;
                left: 0;
                right: 0;
                height: 40pt;
                background-color: rgba(0, 255, 0, 0.3);
                border: 2px solid green;
            }
            .content {
                background-color: #eee;
            }
            .page-break {
                page-break-before: always;
            }
        </style>
    </head>
    <body>
        <div class="header">HEADER (top: 0)</div>
        <div class="footer">FOOTER (bottom: 0)</div>
        <div class="content">
            <h1>Page 1 Content</h1>
            <p>This is line 1...</p>
            <div class="page-break"></div>
            <h1>Page 2 Content</h1>
            <p>This is line 1 on page 2...</p>
        </div>
    </body>
    </html>
    `;

    const filePath = path.resolve('scratch/test_fixed_margins.html');
    fs.writeFileSync(filePath, html);

    const browser = await puppeteer.launch({
        headless: 'new',
        args: ['--no-sandbox']
    });
    const page = await browser.newPage();
    await page.goto('file://' + filePath, { waitUntil: 'networkidle0' });
    await page.pdf({
        path: 'scratch/test_fixed_margins.pdf',
        format: 'A4',
        printBackground: true
    });
    await browser.close();
    console.log("PDF generated!");
})();
