# Whole Trade Print Service (Android)

Native Kotlin app that turns any Android phone/tablet into a print bridge for the
Whole Trade web app. It polls the Laravel server for queued receipts and prints them
to a Bluetooth Classic thermal printer — **no browser popups, no pairing dialogs, no RawBT**.

```
Cashier taps "⚡ Direct Print" in web app
        → Laravel queues ESC/POS job   (PrintBridgeController@enqueue)
        → this app polls /print-bridge/pending every 2 s
        → prints via Bluetooth Classic SPP
        → acks /print-bridge/ack/{id}  → web UI shows "✓ Printed"
```

## Server setup (one time)

1. Add to the production `.env`:
   ```
   PRINT_BRIDGE_TOKEN=<long random string, e.g. openssl rand -hex 32>
   PRINT_BRIDGE_COMPANY_CODE=<short code staff type into the app, e.g. SGTRADE2026>
   ```
2. `php artisan config:clear`
3. In `thermal-print.blade.php`, re-enable the **⚡ Direct Print** button
   (it is currently commented out).

New endpoint added: `POST /print-bridge/register` — exchanges the company code
for the bridge token so it never has to be typed on the phone.

## Building the APK

1. Open this folder (`android-print-service/`) in Android Studio.
2. Let Gradle sync (project has **zero external dependencies**).
3. Build → Build APK. Install on the shop's Android device.

## Client device setup (one time, ~3 minutes)

1. Android Settings → Bluetooth → **pair the thermal printer** (OS-level, permanent).
2. Open the app:
   - **1 · Server** — enter server URL + company code → Login
   - **2 · Printer** — Choose paired printer → select it; optionally enable Auto paper cut; Test print
   - **3 · Service** — Start print service
3. Recommended: Android Settings → Apps → Whole Trade Print Service → Battery →
   **Unrestricted** (prevents Android from killing the background service).

The service survives reboots (starts automatically), reconnects after printer
sleep/power-off, and shows its status in a persistent notification.

## Notes

- Payloads are generated server-side by `ReceiptBuilder` + `EscposDriver`; copies
  are already baked into the payload, the app prints it as-is.
- Auto paper cut sends `GS V 66 0` (partial cut). Leave it off for printers
  without a cutter.
- If a print fails, the app acks `failed` with the error message so the web UI
  shows it instead of timing out.
- Poll interval comes from the server response (`poll_seconds`, default 2 s).
