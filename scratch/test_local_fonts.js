import puppeteer from 'puppeteer';
import fs from 'fs';

(async () => {
    const html = `
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            @font-face {
                font-family: 'Inter';
                src: url('file:///d:/internship/hr-panel/hr-portal-api/storage/fonts/inter_normal_5a66f8ceb794f00a7f54c797a21283b3.ttf') format('truetype');
                font-weight: normal;
                font-style: normal;
            }
            @font-face {
                font-family: 'Inter';
                src: url('file:///d:/internship/hr-panel/hr-portal-api/storage/fonts/inter_bold_02384cdf43e3d24b76c57c66dfd114bf.ttf') format('truetype');
                font-weight: bold;
                font-style: normal;
            }
            body {
                font-family: 'Inter', sans-serif;
                font-size: 11pt;
            }
            .bold {
                font-weight: bold;
            }
        </style>
    </head>
    <body>
        <p>This is regular Inter text.</p>
        <p class="bold">This is bold Inter text.</p>
    </body>
    </html>
    `;

    fs.writeFileSync('scratch/test_font.html', html);

    const browser = await puppeteer.launch({
        headless: 'new',
        args: ['--no-sandbox', '--allow-file-access-from-files']
    });
    const page = await browser.newPage();
    await page.setContent(html, { waitUntil: 'networkidle0' });
    await page.pdf({
        path: 'scratch/test_font.pdf',
        format: 'A4'
    });
    await browser.close();
    console.log("PDF generated!");
})();
