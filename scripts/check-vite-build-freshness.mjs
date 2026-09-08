import { spawnSync } from 'node:child_process';
import { existsSync, readFileSync } from 'node:fs';

const MANIFEST = 'public/build/manifest.json';

function entryFiles(raw) {
    const manifest = JSON.parse(raw);
    const files = {};

    for (const [key, value] of Object.entries(manifest)) {
        if (value && typeof value === 'object' && value.isEntry && typeof value.file === 'string') {
            files[key] = value.file;
        }
    }

    return files;
}

if (! existsSync(MANIFEST)) {
    console.error(`${MANIFEST} is missing. Run npm run build and commit the production assets (git add -f public/build).`);
    process.exit(1);
}

const committed = readFileSync(MANIFEST, 'utf8');
const build = spawnSync('npx', ['vite', 'build'], {
    stdio: 'inherit',
    shell: true,
});

if (build.status !== 0) {
    process.exit(build.status ?? 1);
}

const rebuilt = readFileSync(MANIFEST, 'utf8');
const committedEntries = entryFiles(committed);
const rebuiltEntries = entryFiles(rebuilt);
const keys = new Set([...Object.keys(committedEntries), ...Object.keys(rebuiltEntries)]);
const stale = [];

for (const key of [...keys].sort()) {
    if (committedEntries[key] !== rebuiltEntries[key]) {
        stale.push(
            `${key}: committed ${committedEntries[key] ?? '(missing)'} vs rebuilt ${rebuiltEntries[key] ?? '(missing)'}`,
        );
    }
}

if (stale.length > 0) {
    console.error('Vite production manifest is stale. Run npm run build and commit public/build (git add -f).');
    console.error(stale.join('\n'));
    process.exit(1);
}

const untracked = [];

for (const file of Object.values(rebuiltEntries).sort()) {
    const path = `public/build/${file}`;
    const listed = spawnSync('git', ['ls-files', '--error-unmatch', '--', path], {
        encoding: 'utf8',
    });

    if (listed.status !== 0) {
        untracked.push(path);
    }
}

if (untracked.length > 0) {
    console.error('Fresh Vite assets are not tracked. Commit them with git add -f public/build.');
    console.error(untracked.join('\n'));
    process.exit(1);
}

console.log('Vite production manifest matches a fresh build.');
