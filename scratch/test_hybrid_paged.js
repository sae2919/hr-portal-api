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
                margin: 0 37pt 60pt 37pt;
            }
            body {
                font-family: sans-serif;
                margin: 0;
                padding: 0;
            }
            /* Fixed background graphics */
            .header-bg {
                position: fixed;
                top: 0;
                left: -37pt;
                right: -37pt;
                height: 30px;
                background-color: blue;
                z-index: -100;
            }
            .footer-bg {
                position: fixed;
                top: 802pt;
                left: -37pt;
                right: -37pt;
                height: 40pt;
                background-color: darkblue;
                z-index: -100;
            }
            
            /* Print container table */
            .print-table {
                width: 100%;
                border-collapse: collapse;
            }
            .header-space {
                height: 115pt;
            }
            
            /* Actual header/footer positioned elements */
            .header-content {
                position: fixed;
                top: 20pt;
                left: 0;
                right: 0;
                height: 80pt;
                background-color: rgba(255, 0, 0, 0.2);
                border-bottom: 2px solid red;
            }
            .footer-content {
                position: fixed;
                top: 785pt;
                left: 0;
                right: 0;
                height: 40pt;
                background-color: rgba(0, 255, 0, 0.2);
            }
            
            .content-cell {
                padding: 0;
            }
            .page-break {
                page-break-before: always;
            }
        </style>
    </head>
    <body>
        <div class="header-bg"></div>
        <div class="footer-bg"></div>
        <div class="header-content">ACTUAL HEADER CONTENT</div>
        <div class="footer-content">ACTUAL FOOTER CONTENT</div>
        
        <table class="print-table">
            <thead>
                <tr>
                    <td><div class="header-space"></div></td>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="content-cell">
                        <h1>Page 1 content</h1>
                        <p>Line 1 of content...</p>
                        <p>Line 2 of content...</p>
                        
                        <h1 class="page-break">Page 2 content</h1>
                        <p>Line 1 of page 2...</p>
                    </td>
                </tr>
            </tbody>
        </table>
    </body>
    </html>
    `;

    const filePath = path.resolve('scratch/test_hybrid.html');
    fs.writeFileSync(filePath, html);

    const browser = await puppeteer.launch({
        headless: 'new',
        args: ['--no-sandbox']
    });
    const page = await browser.newPage();
    await page.goto('file://' + filePath, { waitUntil: 'networkidle0' });
    await page.pdf({
        path: 'scratch/test_hybrid.pdf',
        format: 'A4',
        printBackground: true
    });
    await browser.close();
    console.log("Hybrid PDF generated!");
})();
