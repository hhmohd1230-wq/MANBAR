// Renders docs/figures/*.svg to PNG (2x) using Playwright/Chromium.  node docs/render_png.js
const { chromium } = require(process.env.PLAYWRIGHT_MODULE || 'playwright');
const fs = require('fs'), path = require('path');
(async () => {
  const dir = path.join(__dirname, 'figures');
  const b = await chromium.launch({ executablePath: process.env.CHROME || undefined, args: ['--no-sandbox'] });
  const p = await b.newPage({ deviceScaleFactor: 2 });
  for (const f of fs.readdirSync(dir).filter(f => f.endsWith('.svg'))) {
    const svg = fs.readFileSync(path.join(dir, f), 'utf8');
    const m = svg.match(/viewBox="0 0 ([\d.]+) ([\d.]+)"/);
    await p.setViewportSize({ width: Math.ceil(+m[1]), height: Math.ceil(+m[2]) });
    await p.setContent(`<body style="margin:0">${svg}</body>`);
    await p.screenshot({ path: path.join(dir, f.replace('.svg', '.png')) });
    console.log('png', f);
  }
  await b.close();
})();
