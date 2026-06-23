// Regenerate resources/css/critical-home.css after CSS changes:
//   npm i -D penthouse puppeteer && node gen-critical.cjs public/build/assets/app-*.css

const penthouse = require('penthouse');
const fs = require('fs');
penthouse({
  url: 'https://rasoolallah.com/',
  cssString: fs.readFileSync(process.argv[2], 'utf8'),
  width: 412, height: 915, timeout: 90000,
  forceInclude: [/\.dark/, 'html', 'body', '.fixed', '.sticky'],   // ensure dark + shell rules survive
  puppeteerLaunchArgs: ['--no-sandbox', '--disable-setuid-sandbox'],
}).then(c => { fs.writeFileSync('resources/css/critical-home.css', c); console.log('BYTES='+c.length); })
  .catch(e => { console.error('ERR', e.message); process.exit(1); });
