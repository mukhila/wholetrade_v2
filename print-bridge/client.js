/**
 * WholeTrade Print Bridge Client
 * ================================
 * Runs on any shop PC / Raspberry Pi with Node.js (v14+).
 * Polls the Laravel server for pending print jobs and
 * sends raw bytes to the local printer.
 *
 * Supported printer connections:
 *   "network"  — TCP socket to printer IP:port (most common for WiFi/LAN printers)
 *   "usb"      — Windows: named printer via PowerShell; Linux: /dev/usb/lp0 etc.
 *   "cups"     — Linux: lp command (CUPS)
 *   "serial"   — COM1 / /dev/ttyUSB0 etc.
 *
 * Zero npm dependencies — only Node.js built-ins used.
 *
 * Usage:
 *   node client.js [path/to/config.json]
 *
 * Config keys: see config.example.json
 */

'use strict';

const fs    = require('fs');
const http  = require('http');
const https = require('https');
const net   = require('net');
const { execSync, spawnSync } = require('child_process');

// ── Load config ────────────────────────────────────────────────
const configPath = process.argv[2] || __dirname + '/config.json';
if (!fs.existsSync(configPath)) {
  console.error('[BRIDGE] Config file not found: ' + configPath);
  console.error('[BRIDGE] Copy config.example.json → config.json and fill in values.');
  process.exit(1);
}
const cfg = JSON.parse(fs.readFileSync(configPath, 'utf8'));

const SERVER    = (cfg.server_url || '').replace(/\/$/, '');  // e.g. https://yourapp.com
const TOKEN     = cfg.token       || '';
const STATION   = cfg.station     || 'main';
const FORMAT    = cfg.format      || 'escpos';               // escpos | tspl | text
const INTERVAL  = cfg.poll_ms     || 2000;                   // poll every 2 s

// Printer connection settings
const CON_TYPE  = cfg.connection  || 'network';              // network | usb | cups | serial
const NET_HOST  = cfg.printer_host || '';
const NET_PORT  = cfg.printer_port || 9100;
const USB_PATH  = cfg.printer_path || (process.platform === 'win32' ? 'POS-80C' : '/dev/usb/lp0');
const CUPS_NAME = cfg.cups_name   || '';
const COM_PORT  = cfg.com_port    || (process.platform === 'win32' ? 'COM1' : '/dev/ttyUSB0');
const COM_BAUD  = cfg.com_baud    || 9600;

console.log('[BRIDGE] Starting — server=' + SERVER + ' station=' + STATION + ' format=' + FORMAT + ' connection=' + CON_TYPE);
if (!SERVER || !TOKEN) {
  console.error('[BRIDGE] server_url and token are required in config.json');
  process.exit(1);
}

// ── HTTP helper ────────────────────────────────────────────────
function request(method, path, body, cb) {
  const url   = new URL(SERVER + path);
  const isSSL = url.protocol === 'https:';
  const mod   = isSSL ? https : http;

  const bodyBuf = body ? Buffer.from(JSON.stringify(body)) : null;

  const options = {
    hostname: url.hostname,
    port:     url.port || (isSSL ? 443 : 80),
    path:     url.pathname + url.search,
    method:   method,
    headers: {
      'X-Bridge-Token': TOKEN,
      'Accept':         'application/json',
      ...(bodyBuf ? { 'Content-Type': 'application/json', 'Content-Length': bodyBuf.length } : {}),
    },
  };

  const req = mod.request(options, (res) => {
    let data = '';
    res.on('data', c => data += c);
    res.on('end', () => {
      try { cb(null, JSON.parse(data), res.statusCode); }
      catch (e) { cb(e); }
    });
  });
  req.on('error', cb);
  if (bodyBuf) req.write(bodyBuf);
  req.end();
}

// ── Send bytes to printer ──────────────────────────────────────
function sendToPrinter(bytesBuffer, cb) {
  if (CON_TYPE === 'network') {
    if (!NET_HOST) { return cb(new Error('printer_host not set in config.json')); }
    const sock = net.createConnection(NET_PORT, NET_HOST, () => {
      sock.write(bytesBuffer, () => {
        sock.end();
        console.log('[BRIDGE] Sent ' + bytesBuffer.length + ' bytes to ' + NET_HOST + ':' + NET_PORT);
        cb(null);
      });
    });
    sock.on('error', cb);
    sock.setTimeout(10000, () => { sock.destroy(); cb(new Error('Network print timeout')); });
    return;
  }

  if (CON_TYPE === 'usb') {
    if (process.platform === 'win32') {
      // Write raw bytes via PowerShell's .NET PrintQueue (Windows)
      const b64   = bytesBuffer.toString('base64');
      const psCmd = `
        Add-Type -AssemblyName System.Drawing;
        $bytes = [Convert]::FromBase64String('${b64}');
        $pq    = New-Object System.Drawing.Printing.PrintDocument;
        $pq.PrinterSettings.PrinterName = '${USB_PATH}';
        $pq.PrintPage += { $args[1].Graphics };
        # Raw bytes approach via RawPrinterHelper
        $r = [System.Runtime.InteropServices.RuntimeEnvironment]::GetRuntimeDirectory();
        Add-Type -Path "$r\\System.Drawing.dll" -ErrorAction SilentlyContinue;
        $handle = [System.Drawing.Printing.PrintDocument]::new();
        # Simple fallback: write file then use copy /b
        $tmp = [System.IO.Path]::GetTempFileName();
        [System.IO.File]::WriteAllBytes($tmp, $bytes);
        & cmd /c "copy /b $tmp \`"\\\\.\\${USB_PATH}\`"" 2>$null;
        Remove-Item $tmp -Force;
      `;
      try {
        const tmp = require('os').tmpdir() + '\\rawprint_' + Date.now() + '.bin';
        fs.writeFileSync(tmp, bytesBuffer);
        spawnSync('cmd', ['/c', `copy /b "${tmp}" "\\\\.\\${USB_PATH}"`], { shell: false });
        fs.unlinkSync(tmp);
        console.log('[BRIDGE] Windows USB print: ' + bytesBuffer.length + ' bytes → ' + USB_PATH);
        cb(null);
      } catch (e) { cb(e); }
      return;
    }

    // Linux/Mac: write directly to device file
    try {
      fs.writeFileSync(USB_PATH, bytesBuffer);
      console.log('[BRIDGE] USB/device print: ' + bytesBuffer.length + ' bytes → ' + USB_PATH);
      cb(null);
    } catch (e) { cb(e); }
    return;
  }

  if (CON_TYPE === 'cups') {
    if (!CUPS_NAME) { return cb(new Error('cups_name not set in config.json')); }
    try {
      const tmp = '/tmp/printjob_' + Date.now() + '.bin';
      fs.writeFileSync(tmp, bytesBuffer);
      spawnSync('lp', ['-d', CUPS_NAME, '-o', 'raw', tmp]);
      fs.unlinkSync(tmp);
      console.log('[BRIDGE] CUPS print: ' + bytesBuffer.length + ' bytes → ' + CUPS_NAME);
      cb(null);
    } catch (e) { cb(e); }
    return;
  }

  if (CON_TYPE === 'serial') {
    // Serial port using Node.js built-in net or fs (basic COM/tty)
    try {
      const fd = fs.openSync(COM_PORT, 'w');
      fs.writeSync(fd, bytesBuffer);
      fs.closeSync(fd);
      console.log('[BRIDGE] Serial print: ' + bytesBuffer.length + ' bytes → ' + COM_PORT);
      cb(null);
    } catch (e) { cb(e); }
    return;
  }

  cb(new Error('Unknown connection type: ' + CON_TYPE));
}

// ── Main poll loop ─────────────────────────────────────────────
let busy = false;

function poll() {
  if (busy) return;
  busy = true;

  const qs = '?station=' + encodeURIComponent(STATION) + '&format=' + encodeURIComponent(FORMAT);
  request('GET', '/print-bridge/pending' + qs, null, (err, data, status) => {
    if (err || status !== 200) {
      if (err) console.error('[BRIDGE] Poll error:', err.message);
      busy = false;
      return;
    }

    const job = data && data.job;
    if (!job) { busy = false; return; }

    console.log('[BRIDGE] Got job #' + job.id + ' (' + job.format + ', ' + job.copies + ' copies)');

    let bytes;
    try {
      bytes = Buffer.from(job.payload, 'base64');
    } catch (e) {
      ack(job.id, 'failed', 'base64 decode error: ' + e.message);
      return;
    }

    sendToPrinter(bytes, (printErr) => {
      if (printErr) {
        console.error('[BRIDGE] Print error:', printErr.message);
        ack(job.id, 'failed', printErr.message);
      } else {
        console.log('[BRIDGE] Job #' + job.id + ' printed OK');
        ack(job.id, 'done', null);
      }
    });
  });
}

function ack(jobId, status, error) {
  request('POST', '/print-bridge/ack/' + jobId, { status, error }, (err) => {
    if (err) console.error('[BRIDGE] Ack error:', err.message);
    busy = false;
  });
}

// Start polling
setInterval(poll, INTERVAL);
poll();
console.log('[BRIDGE] Polling every ' + INTERVAL + 'ms. Press Ctrl+C to stop.');
