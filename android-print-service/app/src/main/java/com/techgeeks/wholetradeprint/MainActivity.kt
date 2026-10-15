package com.techgeeks.wholetradeprint

import android.Manifest
import android.annotation.SuppressLint
import android.app.Activity
import android.app.AlertDialog
import android.bluetooth.BluetoothAdapter
import android.content.Intent
import android.content.pm.PackageManager
import android.os.Build
import android.os.Bundle
import android.provider.MediaStore
import android.util.Base64
import android.util.Log
import android.widget.Button
import android.widget.CheckBox
import android.widget.EditText
import android.widget.TextView
import android.widget.Toast

class MainActivity : Activity() {

    private lateinit var prefs: Prefs

    private lateinit var etServer: EditText
    private lateinit var etCompanyCode: EditText
    private lateinit var tvLoginStatus: TextView
    private lateinit var tvPrinter: TextView
    private lateinit var tvServiceStatus: TextView
    private lateinit var tvLog: TextView
    private lateinit var cbAutoCut: CheckBox
    private lateinit var btnService: Button

    private lateinit var layoutLogin: android.view.ViewGroup
    private lateinit var layoutDashboard: android.view.ViewGroup
    private lateinit var tvDashboardCompanyName: TextView

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(R.layout.activity_main)
        prefs = Prefs(this)

        etServer = findViewById(R.id.etServer)
        etCompanyCode = findViewById(R.id.etCompanyCode)
        tvLoginStatus = findViewById(R.id.tvLoginStatus)
        tvPrinter = findViewById(R.id.tvPrinter)
        tvServiceStatus = findViewById(R.id.tvServiceStatus)
        tvLog = findViewById(R.id.tvLog)
        cbAutoCut = findViewById(R.id.cbAutoCut)
        btnService = findViewById(R.id.btnService)

        layoutLogin = findViewById(R.id.layoutLogin)
        layoutDashboard = findViewById(R.id.layoutDashboard)
        tvDashboardCompanyName = findViewById(R.id.tvDashboardCompanyName)

        etServer.setText(prefs.serverUrl)
        cbAutoCut.isChecked = prefs.autoCut
        cbAutoCut.setOnCheckedChangeListener { _, checked -> prefs.autoCut = checked }

        findViewById<Button>(R.id.btnLogin).setOnClickListener { doLogin() }
        findViewById<Button>(R.id.btnPickPrinter).setOnClickListener { pickPrinter() }
        findViewById<Button>(R.id.btnTestPrint).setOnClickListener { testPrint() }
        findViewById<Button>(R.id.btnLogout).setOnClickListener { doLogout() }
        findViewById<Button>(R.id.btnOpenGallery).setOnClickListener { openGallery() }
        btnService.setOnClickListener { toggleService() }

        requestRuntimePermissions()
        refreshUi()
        showLastCrashIfAny()
        handleLaunchIntent(intent)
    }

    /** If the app crashed last time, show the full stack trace with a Copy
     *  button — lets us diagnose crashes without ADB/logcat on the phone. */
    private fun showLastCrashIfAny() {
        val crash = prefs.lastCrash
        if (crash.isEmpty()) return
        prefs.lastCrash = ""
        log("=== APP CRASHED LAST RUN — tap 'Copy' in dialog ===")
        AlertDialog.Builder(this)
            .setTitle("App crashed last time")
            .setMessage(crash.take(3500))
            .setPositiveButton("Copy") { _, _ ->
                val cm = getSystemService(android.content.ClipboardManager::class.java)
                cm.setPrimaryClip(android.content.ClipData.newPlainText("crash", crash))
                toast("Crash log copied — paste it to the developer")
            }
            .setNegativeButton("Dismiss", null)
            .show()
    }

    override fun onNewIntent(intent: Intent) {
        super.onNewIntent(intent)
        setIntent(intent)
        handleLaunchIntent(intent)
    }

    override fun onResume() {
        super.onResume()
        refreshUi()
    }

    private fun handleLaunchIntent(intent: Intent?) {
        val data = intent?.data ?: return
        if (data.scheme != "wholetradeprint" || data.host != "preview") return

        val serverFromWeb = data.getQueryParameter("server_url")?.trim()?.trimEnd('/') ?: ""
        if (serverFromWeb.isNotEmpty() && prefs.serverUrl.isEmpty()) {
            prefs.serverUrl = serverFromWeb
            etServer.setText(serverFromWeb)
        }

        val jobId = data.getQueryParameter("job_id")?.toIntOrNull() ?: -1
        log(if (jobId > 0) "Job #$jobId requested from web" else "Print request from web")

        if (!prefs.isConfigured) {
            toast("Login to Whole Trade Print App first, then tap Thermal Print again.")
            refreshUi()
            return
        }

        // Start background service for future automatic jobs
        prefs.serviceEnabled = true
        val si = Intent(this, PrintPollingService::class.java)
        try {
            if (Build.VERSION.SDK_INT >= 26) startForegroundService(si) else startService(si)
        } catch (e: Exception) {
            try { startService(si) } catch (ignored: Exception) {}
        }
        btnService.postDelayed({ refreshUi() }, 600)

        if (jobId <= 0) return

        // Fetch the job in the background, then ask the user Print or Save
        toast("Loading receipt…")
        Thread {
            try {
                val job = ApiClient.pending(prefs.serverUrl, prefs.token, prefs.station, jobId)
                if (job == null) {
                    runOnUiThread { toast("Job #$jobId not found — already processed?") }
                    return@Thread
                }
                val id      = job.getInt("id")
                val payload = Base64.decode(job.getString("payload"), Base64.DEFAULT)
                val cols    = job.optInt("cols", 42)
                runOnUiThread { showPrintOrSaveDialog(id, payload, cols) }
            } catch (e: Exception) {
                Log.e("WTPrint", "fetch job #$jobId failed: ${e.message}")
                runOnUiThread { toast("Failed to load receipt: ${e.message}") }
            }
        }.start()
    }

    private fun showPrintOrSaveDialog(jobId: Int, payload: ByteArray, cols: Int) {
        AlertDialog.Builder(this)
            .setTitle("Receipt Ready — Job #$jobId")
            .setMessage(if (prefs.printerName.isNotEmpty()) "Printer: ${prefs.printerName}" else "No printer selected")
            .setPositiveButton("Print") { _, _ -> doPrint(jobId, payload, cols) }
            .setNegativeButton("Save to Gallery") { _, _ -> doSave(jobId, payload, cols) }
            .setCancelable(false)
            .show()
    }

    private fun doPrint(jobId: Int, payload: ByteArray, cols: Int) {
        if (prefs.printerMac.isEmpty()) {
            toast("No printer selected — saving to gallery instead.")
            doSave(jobId, payload, cols)
            return
        }
        log("Printing job #$jobId…")
        Thread {
            val printer = BluetoothPrinter(prefs.printerMac)
            try {
                printer.connect()
                printer.print(payload, prefs.autoCut)
                printer.close()
                try { ApiClient.ack(prefs.serverUrl, prefs.token, jobId, true, null) } catch (ignored: Exception) {}
                runOnUiThread { log("✓ Printed job #$jobId"); toast("Printed successfully!") }
            } catch (e: Exception) {
                try { printer.close() } catch (ignored: Exception) {}
                Log.e("WTPrint", "Print failed: ${e.message}")
                runOnUiThread {
                    log("Print failed — saving to gallery")
                    toast("Printer not connected — saving to gallery.")
                    doSave(jobId, payload, cols)
                }
            }
        }.start()
    }

    private fun doSave(jobId: Int, payload: ByteArray, cols: Int) {
        Thread {
            val lines = try { ReceiptRenderer.parse(payload) } catch (e: Exception) { emptyList() }
            val bmp   = try { ReceiptRenderer.render(lines, cols) } catch (e: Exception) { null }
            val saved = if (bmp != null) ReceiptRenderer.saveToGallery(this, bmp, "receipt_job_$jobId") else false
            try {
                ApiClient.ack(prefs.serverUrl, prefs.token, jobId, saved,
                    if (saved) null else "Gallery save failed")
            } catch (ignored: Exception) {}
            runOnUiThread {
                if (saved) { log("✓ Saved job #$jobId to gallery"); toast("Saved to Gallery (Pictures/WholeTrade)") }
                else        { log("✗ Save failed for job #$jobId"); toast("Failed to save receipt to gallery.") }
            }
        }.start()
    }

    private fun doLogout() {
        prefs.token = ""
        prefs.companyName = ""
        prefs.serviceEnabled = false
        if (PrintPollingService.isRunning) {
            startService(Intent(this, PrintPollingService::class.java).setAction(PrintPollingService.ACTION_STOP))
        }
        log("Logged out")
        refreshUi()
    }

    private fun openGallery() {
        try {
            // setDataAndType() sets both URI and MIME type atomically.
            // Using type = "..." alone calls setType() which clears mData,
            // leaving the intent with no URI and causing "unsupported file".
            val intent = Intent(Intent.ACTION_VIEW).apply {
                setDataAndType(MediaStore.Images.Media.EXTERNAL_CONTENT_URI, "image/*")
            }
            startActivity(Intent.createChooser(intent, "Open saved receipts"))
        } catch (e: Exception) {
            toast("Open Gallery app and check Pictures/WholeTrade.")
        }
    }

    /* ── UI state ── */

    private fun refreshUi() {
        val loggedIn = prefs.token.isNotEmpty()
        if (loggedIn) {
            layoutLogin.visibility = android.view.View.GONE
            layoutDashboard.visibility = android.view.View.VISIBLE
            tvDashboardCompanyName.text = prefs.companyName
        } else {
            layoutLogin.visibility = android.view.View.VISIBLE
            layoutDashboard.visibility = android.view.View.GONE
            tvLoginStatus.text = ""
        }

        tvPrinter.text = if (prefs.printerMac.isNotEmpty())
            "✓ ${prefs.printerName}  [${prefs.printerMac}]"
        else "No printer selected (Gallery fallback active)"

        val running = PrintPollingService.isRunning
        btnService.text = if (running) "Stop Print Service" else "Start Print Service"
        tvServiceStatus.text = if (running)
            "● Service running — polling every ${prefs.pollSeconds}s"
        else "Service stopped"

        if (running) {
            tvServiceStatus.setTextColor(android.graphics.Color.parseColor("#0d9488"))
        } else {
            tvServiceStatus.setTextColor(android.graphics.Color.parseColor("#e11d48"))
        }
    }

    private fun log(msg: String) {
        runOnUiThread {
            tvLog.text = "$msg\n${tvLog.text}".lines().take(15).joinToString("\n")
        }
    }

    /* ── 1. login ── */

    private fun doLogin() {
        val server = etServer.text.toString().trim().trimEnd('/')
        val code = etCompanyCode.text.toString().trim()
        if (server.isEmpty() || code.isEmpty()) {
            toast("Enter server URL and company code"); return
        }

        tvLoginStatus.text = "Logging in…"
        Thread {
            try {
                val res = ApiClient.register(server, code, prefs.station)
                prefs.serverUrl = server
                prefs.token = res.getString("token")
                prefs.station = res.optString("station", "main")
                prefs.companyName = res.optString("company_name", "")
                prefs.pollSeconds = res.optInt("poll_seconds", 2)
                log("Login OK — ${prefs.companyName}")
                runOnUiThread { etCompanyCode.setText(""); refreshUi() }
            } catch (e: Exception) {
                log("Login failed: ${e.message}")
                runOnUiThread { tvLoginStatus.text = "✗ ${e.message}" }
            }
        }.start()
    }

    /* ── 2. printer ── */

    @SuppressLint("MissingPermission")
    private fun pickPrinter() {
        if (!hasBtPermission()) { requestRuntimePermissions(); return }

        val adapter = BluetoothAdapter.getDefaultAdapter()
        if (adapter == null || !adapter.isEnabled) {
            toast("Turn on Bluetooth first"); return
        }
        val bonded = adapter.bondedDevices.toList()
        if (bonded.isEmpty()) {
            toast("No paired devices. Pair the printer in Android Settings → Bluetooth first.")
            return
        }
        val names = bonded.map { "${it.name ?: "Unknown"}\n${it.address}" }.toTypedArray()
        AlertDialog.Builder(this)
            .setTitle("Select printer")
            .setItems(names) { _, which ->
                prefs.printerMac = bonded[which].address
                prefs.printerName = bonded[which].name ?: "Unknown"
                log("Printer saved: ${prefs.printerName}")
                refreshUi()
            }
            .show()
    }

    private fun testPrint() {
        if (prefs.printerMac.isEmpty()) { toast("Choose a printer first"); return }
        log("Test print → ${prefs.printerName}…")
        Thread {
            val p = BluetoothPrinter(prefs.printerMac)
            try {
                val body = "\u001bE\u0000" +           // soft reset: bold off
                    "\u001ba\u0000" +                   // align left
                    "\u001d!\u0000" +                   // size normal
                    "\u001b2" +                          // default line spacing
                    "*** WHOLE TRADE PRINT ***\n" +
                    "Test print OK\n" +
                    "Printer: ${prefs.printerName}\n" +
                    "Auto cut: ${if (prefs.autoCut) "ON" else "OFF"}\n" +
                    "**************************\n\n\n\n"
                p.connect()
                p.print(body.toByteArray(Charsets.ISO_8859_1), prefs.autoCut)
                log("Test print sent ✓")
            } catch (e: Exception) {
                log("Test print failed: ${e.message}")
            } finally {
                p.close()
            }
        }.start()
    }

    /* ── 3. service ── */

    private fun toggleService() {
        if (PrintPollingService.isRunning) {
            prefs.serviceEnabled = false
            startService(Intent(this, PrintPollingService::class.java).setAction(PrintPollingService.ACTION_STOP))
            log("Service stopped")
        } else {
            if (!prefs.isConfigured) { toast("Please login first"); return }

            prefs.serviceEnabled = true
            val i = Intent(this, PrintPollingService::class.java)
            try {
                if (Build.VERSION.SDK_INT >= 26) startForegroundService(i) else startService(i)
            } catch (e: Exception) {
                Log.e("WTPrint", "Failed to start service: ${e.message}")
                try { startService(i) } catch (ignored: Exception) {}
            }
            log("Service started — polling ${prefs.serverUrl}")
        }
        // isRunning flips asynchronously; refresh shortly after
        btnService.postDelayed({ refreshUi() }, 600)
    }

    /* ── permissions ── */

    private fun hasBtPermission(): Boolean =
        Build.VERSION.SDK_INT < 31 ||
            checkSelfPermission(Manifest.permission.BLUETOOTH_CONNECT) == PackageManager.PERMISSION_GRANTED

    private fun requestRuntimePermissions() {
        val wanted = mutableListOf<String>()
        if (Build.VERSION.SDK_INT >= 31 &&
            checkSelfPermission(Manifest.permission.BLUETOOTH_CONNECT) != PackageManager.PERMISSION_GRANTED
        ) wanted += Manifest.permission.BLUETOOTH_CONNECT
        if (Build.VERSION.SDK_INT >= 31 &&
            checkSelfPermission(Manifest.permission.BLUETOOTH_SCAN) != PackageManager.PERMISSION_GRANTED
        ) wanted += Manifest.permission.BLUETOOTH_SCAN
        if (Build.VERSION.SDK_INT >= 33 &&
            checkSelfPermission(Manifest.permission.POST_NOTIFICATIONS) != PackageManager.PERMISSION_GRANTED
        ) wanted += Manifest.permission.POST_NOTIFICATIONS

        if (wanted.isNotEmpty()) requestPermissions(wanted.toTypedArray(), 1)
    }

    private fun toast(msg: String) = Toast.makeText(this, msg, Toast.LENGTH_LONG).show()
}
