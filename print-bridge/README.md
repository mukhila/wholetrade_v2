# WholeTrade Print Bridge

One-tap thermal printing from ANY device (phone / tablet / laptop) with **no
print dialog**. A tiny client runs on one shop computer and prints every job
queued by the server.

```
Cashier phone/laptop --tap--> Laravel server (queue) <--poll-- Shop PC (this client) --> Printer
```

## Server setup (one time)

1. Add to `.env` on the server (any long random string):

   ```
   PRINT_BRIDGE_TOKEN=change-me-to-a-long-random-string
   ```

2. Run the migration (visit `/run-migrations` on your site) and clear caches
   (visit `/clear-cache`).

## Shop computer setup (one time)

1. Install Node.js 18+ (nodejs.org). Windows/Mac/Linux/Raspberry Pi all work.
2. Copy this `print-bridge` folder to the computer.
3. Copy `bridge-config.example.json` to `bridge-config.json` and edit:
   - `server` — your site URL
   - `token`  — same value as `PRINT_BRIDGE_TOKEN` in `.env`
   - `printer` — how the printer is connected:

   | Printer connection                          | type            | settings                       |
   |---------------------------------------------|-----------------|--------------------------------|
   | Ethernet / Wi-Fi printer                    | `network`       | `host`, `port` (9100)          |
   | USB/Bluetooth printer on a Windows PC       | `windows-share` | share the printer, set `share` |
   | Printer on Linux/Mac/Raspberry Pi (CUPS)    | `cups`          | `name` (lpstat -p)             |
   | Bluetooth paired as COM port                | `serial`        | `device` (e.g. `COM5`)         |

4. Run it:

   ```
   node print-bridge.js
   ```

   To auto-start on Windows boot: create a shortcut to
   `node C:\path\to\print-bridge.js` in `shell:startup`.

## Printer language

Jobs default to **ESC/POS** (works with nearly all receipt printers).
If your TSC printer is a LABEL printer that only speaks TSPL, change the
Direct Print button body in `thermal-print.blade.php` to `format: 'tspl'`.
Plain-text printers: use `format: 'text'`.

## Debugging

- Bridge console shows every job and error.
- Server side: search `PRINT-BRIDGE` in `storage/logs/laravel.log`.
- Receipt page: add `?debug=1` to the URL for the on-screen step log.
