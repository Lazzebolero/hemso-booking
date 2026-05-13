/**
 * Keeps public/js/offline-queue.js in sync with resources/js/pwa-offline-queue.js
 * so production can load the queue without depending on the Vite bundle being cached.
 */

const fs = require('fs');
const path = require('path');

const root = path.join(__dirname, '..');
const src = path.join(root, 'resources', 'js', 'pwa-offline-queue.js');
const dest = path.join(root, 'public', 'js', 'offline-queue.js');

fs.mkdirSync(path.dirname(dest), { recursive: true });
fs.copyFileSync(src, dest);

process.stdout.write(`Synced offline queue script:\n  ${src}\n  -> ${dest}\n`);
