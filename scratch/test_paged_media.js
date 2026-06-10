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
                margin: 0;
            }
            body {
                font-family: sans-serif;
                margin: 0;
                padding: 0;
            }
            .header-bg {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                height: 20px;
                background-color: blue;
            }
            .header {
                position: fixed;
                top: 0;
                left: 50px;
                right: 50px;
                height: 100px;
                background-color: lightblue;
                border-bottom: 2px solid darkblue;
            }
            .footer {
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
                height: 50px;
                background-color: gray;
            }
            .content {
                margin-top: 130px;
                margin-bottom: 70px;
                margin-left: 50px;
                margin-right: 50px;
            }
            .page-break {
                page-break-before: always;
            }
        </style>
    </head>
    <body>
        <div class="header-bg"></div>
        <div class="header">HEADER TEXT</div>
        <div class="footer">FOOTER TEXT</div>
        
        <div class="content">
            <h1>Page 1 Content</h1>
            <p>Lorem ipsum dolor sit amet...</p>
            
            <h1 class="page-break">Page 2 Content</h1>
            <p>Consectetur adipiscing elit...</p>
        </div>
    </body>
    </html>
    `;

    const filePath = path.resolve('scratch/test_paged.html');
    fs.writeFileSync(filePath, html);

    const browser = await puppeteer.launch({
        headless: 'new',
        args: ['--no-sandbox']
    });
    const page = await browser.newPage();
    await page.goto('file://' + filePath, { waitUntil: 'networkidle0' });
    await page.pdf({
        path: 'scratch/test_paged.pdf',
        format: 'A4',
        printBackground: true
    });
    await browser.close();
    console.log("Paged PDF generated!");
})();
