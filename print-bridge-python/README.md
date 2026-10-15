# Whole Trade Print Bridge (Python)

Single-file bridge that prints queued receipts from the WholeTrade server to a
thermal printer. Replaces the Android app — no browser Bluetooth, no pairing
popups, no RawBT.

## Requirements

- Any always-on machine near the printer: Windows PC, laptop, or Raspberry Pi
- Python 3.10+
- For Bluetooth/USB-serial printers: `pip install pyserial`

## Setup (Windows + Bluetooth printer)

1. Windows Settings → Bluetooth → pair the printer (one time, permanent).
2. Control Panel → Devices and Printers → right-click Bluetooth settings →
   **COM Ports** tab → note the **Outgoing** port (e.g. `COM5`).
3. Edit the CONFIG block at the top of `wholetrade_print_bridge.py`:
   - `SERVER_URL`   = your server
   - `BRIDGE_TOKEN` = the `PRINT_BRIDGE_TOKEN` value from the server `.env`
   - `PRINTER_MODE` = `"serial"`, `PRINTER_ADDR` = `"COM5"`
4. Run it:
   ```
   pip install pyserial
   python wholetrade_print_bridge.py
   ```
5. Tap **⚡ Print / Thermal Print** in the web app → receipt prints.

## Auto-start on Windows boot

Create `start_bridge.bat` next to the script:

```bat
@echo off
cd /d %~dp0
pythonw wholetrade_print_bridge.py
```

Put a shortcut to it in `shell:startup` (Win+R → `shell:startup`).

## Raspberry Pi / Linux USB printer

`PRINTER_MODE = "file"`, `PRINTER_ADDR = "/dev/usb/lp0"`, then add a systemd
service or `@reboot` cron entry.

## Network (LAN/WiFi) printer

`PRINTER_MODE = "network"`, `PRINTER_ADDR = "192.168.1.50"` (printer IP, port 9100).

## Notes

- The web page's ⚡ Print button and Thermal Print button already queue jobs to
  this bridge — no web changes needed.
- Copies are baked into the payload server-side; the bridge prints it as-is.
- If printing fails, the bridge acks `failed` and the web page shows the error.
