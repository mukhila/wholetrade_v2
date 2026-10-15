#!/usr/bin/env python3
"""
Whole Trade Print Bridge (Python)
=================================
Polls the Laravel server for queued receipt jobs and prints them to a
thermal printer. Pairs with PrintBridgeController (print-bridge/pending,
print-bridge/ack). Runs on any always-on PC / Raspberry Pi near the printer.

Flow:
  Cashier taps "⚡ Print" in the web app
    -> Laravel queues ESC/POS job
    -> this script picks it up within POLL_SECONDS
    -> writes raw bytes to the printer (COM port / USB / network)
    -> acks the job -> web page shows "✓ Printed"

Supported printer connections (set PRINTER_MODE below):
  "serial"  - Windows Bluetooth SPP or USB-serial printer (COM port).
              Pair printer in Windows Settings -> Bluetooth; check
              "More Bluetooth options" -> COM Ports for the OUTGOING port
              (e.g. COM5). Requires:  pip install pyserial
  "network" - LAN/WiFi printer with raw port 9100.
  "file"    - Linux/Raspberry Pi USB printer (e.g. /dev/usb/lp0).

Run:  python wholetrade_print_bridge.py
"""

import base64
import json
import sys
import time
import urllib.error
import urllib.parse
import urllib.request

# ══════════════════════════════════════════════════════════════════
# CONFIG — edit these five lines
# ══════════════════════════════════════════════════════════════════
SERVER_URL   = "https://wholetrade.techgeeksdigi.com"
BRIDGE_TOKEN = "PASTE_PRINT_BRIDGE_TOKEN_HERE"   # same as PRINT_BRIDGE_TOKEN in .env
STATION      = "main"

PRINTER_MODE = "serial"          # "serial" | "network" | "file"
PRINTER_ADDR = "COM5"            # serial: COM port | network: "192.168.1.50" | file: "/dev/usb/lp0"
# ══════════════════════════════════════════════════════════════════

POLL_SECONDS   = 2
CHUNK_SIZE     = 512             # small chunks + pauses keep clone printers reliable
CHUNK_DELAY_S  = 0.02
NETWORK_PORT   = 9100
SERIAL_BAUD    = 9600            # typical for BT SPP; try 115200 for USB-serial


def log(msg: str) -> None:
    print(time.strftime("[%Y-%m-%d %H:%M:%S] ") + msg, flush=True)


# ── HTTP helpers ────────────────────────────────────────────────────
def api(path: str, payload: dict | None = None) -> dict:
    url = SERVER_URL.rstrip("/") + path
    data = json.dumps(payload).encode() if payload is not None else None
    req = urllib.request.Request(
        url,
        data=data,
        method="POST" if data else "GET",
        headers={
            "Accept": "application/json",
            "Content-Type": "application/json",
            "X-Bridge-Token": BRIDGE_TOKEN,
        },
    )
    with urllib.request.urlopen(req, timeout=15) as res:
        return json.loads(res.read().decode())


def fetch_pending() -> dict | None:
    qs = urllib.parse.urlencode({"station": STATION, "format": "escpos"})
    return api(f"/print-bridge/pending?{qs}").get("job")


def ack(job_id: int, ok: bool, error: str | None = None) -> None:
    api(f"/print-bridge/ack/{job_id}",
        {"status": "done" if ok else "failed", "error": error})


# ── printer transports ─────────────────────────────────────────────
def print_bytes(data: bytes) -> None:
    if PRINTER_MODE == "serial":
        import serial                                  # pip install pyserial
        with serial.Serial(PRINTER_ADDR, SERIAL_BAUD, timeout=5, write_timeout=15) as p:
            _write_chunked(p.write, data, flush=p.flush)
    elif PRINTER_MODE == "network":
        import socket
        with socket.create_connection((PRINTER_ADDR, NETWORK_PORT), timeout=10) as s:
            _write_chunked(s.sendall, data)
    elif PRINTER_MODE == "file":
        with open(PRINTER_ADDR, "wb") as f:
            _write_chunked(f.write, data, flush=f.flush)
    else:
        raise ValueError(f"Unknown PRINTER_MODE: {PRINTER_MODE}")


def _write_chunked(write, data: bytes, flush=None) -> None:
    for i in range(0, len(data), CHUNK_SIZE):
        write(data[i:i + CHUNK_SIZE])
        if flush:
            flush()
        time.sleep(CHUNK_DELAY_S)
    time.sleep(0.1)  # let the printer buffer drain before closing


# ── main loop ──────────────────────────────────────────────────────
def main() -> None:
    if "PASTE_" in BRIDGE_TOKEN:
        sys.exit("Edit the CONFIG block first (BRIDGE_TOKEN is not set).")

    log(f"Whole Trade Print Bridge started — {SERVER_URL} station={STATION} "
        f"printer={PRINTER_MODE}:{PRINTER_ADDR}")

    errors = 0
    while True:
        job = None
        try:
            job = fetch_pending()
            if job:
                jid = job["id"]
                payload = base64.b64decode(job["payload"])
                log(f"Job #{jid} picked up ({len(payload)} bytes) — printing…")
                print_bytes(payload)
                ack(jid, True)
                log(f"Job #{jid} printed ✓")
                errors = 0
                continue  # immediately check for the next queued job
            errors = 0
        except KeyboardInterrupt:
            log("Stopped.")
            return
        except Exception as e:
            errors += 1
            log(f"ERROR: {e}")
            if job:  # report print failures so the web UI shows them
                try:
                    ack(job["id"], False, str(e)[:200])
                except Exception:
                    pass

        # back off when the server/printer is repeatedly unreachable
        time.sleep(30 if errors > 3 else POLL_SECONDS)


if __name__ == "__main__":
    main()
