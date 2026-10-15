#!/usr/bin/env node
/* ============================================================
   WholeTrade POS — Print Bridge Client
   ------------------------------------------------------------
   Runs on ANY computer in the shop (Windows PC, Mac, Linux,
   Raspberry Pi) that can reach the thermal printer.

   It polls your server for queued print jobs and pushes the
   raw bytes to the printer. No dialogs, one-tap printing.

   Requires: Node.js 18+  (no npm packages needed)
   Run:      node print-bridge.js
   ============================================================ */

const fs = require('fs');
const os = require('os');
const path = require('path');
const net = require('net');
const { execFile } = require('child_process');

/* ── load config ── */
const CONFIG_PATH = path.join(__dirname, 'bridge-config.json');
if (!fs.existsSync(CONFIG_PATH)) {
  console.error('ERROR: bridge-config.json not found. Copy bridge-config.example.json and edit it.');
  process.exit(1);
}
const cfg = JSON.parse(fs.readFileSync(CONFIG_PATH, 'utf8'));
const SERVER   = cfg.server.replace(/\/+$/, '');   // e.g. https://sgtrade.techgeeksdigi.com
const TOKEN    = cfg.token;
const STATION  = cfg.station || 'main';
const INTERVAL = cfg.interval_ms || 3000;
const PRINTER  = cfg.printer;                      // {type, ...}

function log(msg) { console.log('[' + new Date().toLocaleTimeString() + '] ' + msg); }

/* ── send raw bytes to the printer ── */
async function printRaw(buffer) {
  switch (PRINTER.type) {

    /* Ethernet/WiFi printer: raw TCP port 9100 */
    case 'network':
      return new Promise((resolve, reject) => {
        const sock = net.createConnection(PRINTER.port || 9100, PRINTER.host, () => {
          sock.write(buffer, () => sock.end());
        });
        sock.on('close', resolve);
        sock.on('error', reject);
        sock.setTimeout(10000, () => { sock.destroy(); reject(new Error('printer TCP timeout')); });
      });

    /* Windows shared printer (USB/Bluetooth printer shared as e.g. TSCPRINTER):
       share it first:  Printer properties -> Sharing -> "TSCPRINTER"        */
    case 'windows-share':
      return new Promise((resolve, reject) => {
        const tmp = path.join(os.tmpdir(), 'wtpos-' + Date.now() + '.bin');
        fs.writeFileSync(tmp, buffer);
        const share = '\\\\' + (PRINTER.computer || 'localhost') + '\\' + PRINTER.share;
        execFile('cmd', ['/c', 'copy', '/b', tmp, share], (err) => {
          fs.unlink(tmp, () => {});
          err ? reject(err) : resolve();
        });
      });

    /* Linux/Mac/Raspberry Pi via CUPS: lp -d <name> -o raw */
    case 'cups':
      return new Promise((resolve, reject) => {
        const tmp = path.join(os.tmpdir(), 'wtpos-' + Date.now() + '.bin');
        fs.writeFileSync(tmp, buffer);
        execFile('lp', ['-d', PRINTER.name, '-o', 'raw', tmp], (err) => {
          fs.unlink(tmp, () => {});
          err ? reject(err) : resolve();
        });
      });

    /* Bluetooth printer paired to THIS pc as a serial COM port (Windows: COM5 etc.,
       Linux: /dev/rfcomm0). Pair the printer first in OS Bluetooth settings.      */
    case 'serial':
      return new Promise((resolve, reject) => {
        const dev = process.platform === 'win32' ? '\\\\.\\' + PRINTER.device : PRINTER.device;
        fs.writeFile(dev, buffer, (err) => (err ? reject(err) : resolve()));
      });

    default:
      throw new Error('Unknown printer.type in bridge-config.json: ' + PRINTER.type);
  }
}

/* ── talk to the server ── */
async function fetchJobs() {
  const res = await fetch(SERVER + '/print-bridge/jobs?station=' + encodeURIComponent(STATION), {
    headers: { 'X-Bridge-Token': TOKEN, 'Accept': 'application/json' },
  });
  if (!res.ok) throw new Error('server HTTP ' + res.status);
  const data = await res.json();
  return data.jobs || [];
}

async function ack(id, success, error) {
  await fetch(SERVER + '/print-bridge/jobs/' + id + '/ack', {
    method: 'POST',
    headers: { 'X-Bridge-Token': TOKEN, 'Content-Type': 'application/json', 'Accept': 'application/json' },
    body: JSON.stringify({ success, error: error || null }),
  }).catch((e) => log('WARN: ack failed: ' + e.message));
}

/* ── main loop ── */
let busy = false;
async function tick() {
  if (busy) return;
  busy = true;
  try {
    const jobs = await fetchJobs();
    for (const job of jobs) {
      log('Job #' + job.id + ' (' + job.format + ') received — printing...');
      try {
        await printRaw(Buffer.from(job.payload, 'base64'));
        await ack(job.id, true);
        log('Job #' + job.id + ' PRINTED ✓');
      } catch (e) {
        await ack(job.id, false, e.message);
        log('Job #' + job.id + ' FAILED ✗ ' + e.message);
      }
    }
  } catch (e) {
    log('Poll error: ' + e.message);
  } finally {
    busy = false;
  }
}

log('WholeTrade Print Bridge started');
log('Server : ' + SERVER);
log('Station: ' + STATION);
log('Printer: ' + JSON.stringify(PRINTER));
setInterval(tick, INTERVAL);
tick();
