// Node script: use Puppeteer to fetch rendered page and output HTML to stdout
// Usage: node fetcher.js <url>
const url = process.argv[2];
if (!url) {
  console.error('Usage: node fetcher.js <url>');
  process.exit(2);
}
(async () => {
  try {
    const puppeteer = require('puppeteer-core');
    const browser = await puppeteer.launch({executablePath: process.env.PUPPETEER_EXECUTABLE_PATH || '/usr/bin/chromium', args: ['--no-sandbox','--disable-setuid-sandbox']});
    const page = await browser.newPage();
    await page.setUserAgent('Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 Chrome/115.0 Safari/537.36');
    await page.goto(url, {waitUntil: 'networkidle2', timeout: 30000});
    // Wait for main flight list to render; adjust selector or timeout if site structure differs
    try { await page.waitForTimeout(1500); } catch(e) {}
    const html = await page.content();
    console.log(html);
    await browser.close();
    process.exit(0);
  } catch (err) {
    console.error('fetch error:', err && err.message ? err.message : err);
    process.exit(3);
  }
})();
