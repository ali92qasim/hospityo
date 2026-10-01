// Real-render A4 verification for the lab report print Blade.
// Prints via CDP Page.printToPDF with printBackground:false — Chrome's print-dialog default
// ("Background graphics" off). The --print-to-pdf CLI flag prints backgrounds ON and must not
// be used as proof.
//
// Usage: node scripts/lab-report-print-verify.mjs [--grayscale] [--measure] <file.html>...
import { spawn } from 'node:child_process';
import { mkdtempSync, rmSync, writeFileSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { join, resolve } from 'node:path';
import { pathToFileURL } from 'node:url';

const args = process.argv.slice(2);
const grayscale = args.includes('--grayscale');
const measure = args.includes('--measure');
const files = args.filter((a) => !a.startsWith('--'));
const chrome = process.env.CHROME_PATH ?? 'C:/Program Files/Google/Chrome/Application/chrome.exe';

const profile = mkdtempSync(join(tmpdir(), 'lab-print-'));
const port = 9300 + Math.floor(Math.random() * 500);
const proc = spawn(chrome, ['--headless=new', '--disable-gpu', `--remote-debugging-port=${port}`, `--user-data-dir=${profile}`, 'about:blank']);

let targets;
for (let i = 0; i < 50 && !targets; i++) {
    try { targets = await (await fetch(`http://127.0.0.1:${port}/json`)).json(); }
    catch { await new Promise((r) => setTimeout(r, 200)); }
}
const ws = new WebSocket(targets.find((t) => t.type === 'page').webSocketDebuggerUrl);
await new Promise((r) => ws.addEventListener('open', r));
let seq = 0; const pending = new Map(); const events = [];
ws.addEventListener('message', ({ data }) => {
    const msg = JSON.parse(data);
    if (msg.id && pending.has(msg.id)) { pending.get(msg.id)(msg); pending.delete(msg.id); } else if (msg.method) events.push(msg.method);
});
const send = (method, params = {}) => new Promise((r) => { const id = ++seq; pending.set(id, r); ws.send(JSON.stringify({ id, method, params })); });
const evaluate = async (expression) => (await send('Runtime.evaluate', { expression, returnByValue: true })).result.result.value;

await send('Page.enable');
let failed = false;
for (const file of files) {
    events.length = 0;
    await send('Page.navigate', { url: pathToFileURL(resolve(file)).href });
    for (let i = 0; i < 100 && !events.includes('Page.loadEventFired'); i++) await new Promise((r) => setTimeout(r, 100));
    if (grayscale) await evaluate(`document.documentElement.style.filter = 'grayscale(100%)'`);

    await send('Emulation.setEmulatedMedia', { media: 'print' });
    const logical = await evaluate(`document.querySelectorAll('.report-page').length`);
    const footerPinned = await evaluate(`[...document.querySelectorAll('.report-page')].every((p) => {
        const f = p.querySelector('.report-footer-band'); if (!f) return true;
        return Math.abs(p.getBoundingClientRect().bottom - f.getBoundingClientRect().bottom) < 2;
    })`);
    const metrics = measure ? await evaluate(`(() => {
        const mm = (px) => +(px / 96 * 25.4).toFixed(1);
        const out = {};
        for (const sel of ['.report-reg-line', '.report-band', '.patient-strip', '.running-header', '.test-panel', '.comments-box', '.report-signoff', '.report-footer-band', '.report-page']) {
            out[sel] = [...document.querySelectorAll(sel)].map((e) => mm(e.getBoundingClientRect().height));
        }
        out['td.result-abnormal'] = [...document.querySelectorAll('td.result-abnormal')].map((e) => mm(e.parentElement.getBoundingClientRect().height));
        return out;
    })()`) : undefined;
    await send('Emulation.setEmulatedMedia', { media: '' });

    const pdf = await send('Page.printToPDF', { printBackground: false, preferCSSPageSize: true });
    const buffer = Buffer.from(pdf.result.data, 'base64');
    writeFileSync(file.replace(/\.html$/, grayscale ? '.gray.pdf' : '.pdf'), buffer);
    const physical = (buffer.toString('latin1').match(/\/Type\s*\/Page(?![s\w])/g) ?? []).length;
    const ok = physical === logical && footerPinned;
    failed ||= !ok;
    console.log(JSON.stringify({ file, logical, physical, footerPinned, ok, metrics }));
}
ws.close(); proc.kill();
try { rmSync(profile, { recursive: true, force: true }); } catch {}
process.exit(failed ? 1 : 0);
