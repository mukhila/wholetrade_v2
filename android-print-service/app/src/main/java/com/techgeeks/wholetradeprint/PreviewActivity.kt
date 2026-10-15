package com.techgeeks.wholetradeprint

import android.app.Activity
import android.app.AlertDialog
import android.content.Context
import android.content.Intent
import android.graphics.Color
import android.graphics.Typeface
import android.os.Bundle
import android.os.Handler
import android.os.Looper
import android.view.Gravity
import android.view.ViewGroup.LayoutParams.MATCH_PARENT
import android.view.ViewGroup.LayoutParams.WRAP_CONTENT
import android.widget.Button
import android.widget.ImageView
import android.widget.LinearLayout
import android.widget.ScrollView
import android.widget.TextView
import android.widget.Toast

/**
 * Shared state between the polling service and the preview screen.
 */
object PreviewHolder {
    @Volatile var status: String = ""
    @Volatile var jobId: Int = -1
    @Volatile var payload: ByteArray? = null
    @Volatile var cols: Int = 42
    @Volatile var listener: (() -> Unit)? = null
    @Volatile var onPrintClicked: (() -> Unit)? = null
    @Volatile var onSaveClicked: (() -> Unit)? = null
    @Volatile var onCancelClicked: (() -> Unit)? = null
    private val main = Handler(Looper.getMainLooper())

    fun show(
        context: Context,
        id: Int,
        pay: ByteArray,
        columns: Int,
        statusText: String,
        onPrint: () -> Unit,
        onSave: () -> Unit,
        onCancel: () -> Unit
    ) {
        jobId = id
        payload = pay
        cols = columns
        status = statusText
        onPrintClicked = onPrint
        onSaveClicked = onSave
        onCancelClicked = onCancel
        notifyUi()
        try {
            context.startActivity(
                Intent(context, PreviewActivity::class.java).addFlags(
                    Intent.FLAG_ACTIVITY_NEW_TASK or
                    Intent.FLAG_ACTIVITY_SINGLE_TOP or
                    Intent.FLAG_ACTIVITY_REORDER_TO_FRONT
                )
            )
        } catch (ignored: Exception) {}
    }

    fun updateStatus(s: String) { status = s; notifyUi() }

    fun fire(choice: String) {
        val cb = when (choice) {
            "print" -> onPrintClicked
            "save"  -> onSaveClicked
            else    -> onCancelClicked
        }
        onPrintClicked = null
        onSaveClicked  = null
        onCancelClicked = null
        cb?.invoke()
    }

    private fun notifyUi() { main.post { listener?.invoke() } }
}

/**
 * Receipt preview screen.
 * Parses the ESC/POS payload into text lines and displays them as a
 * styled receipt — no bitmap conversion, no WebView, no OOM risk.
 */
class PreviewActivity : Activity() {

    private lateinit var linesContainer: LinearLayout
    private lateinit var tvStatus: TextView

    /** Guards against double-fire: executeCancel already fires; onDestroy must not repeat it. */
    private var cancelDispatched = false

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)

        val root = LinearLayout(this).apply {
            orientation = LinearLayout.VERTICAL
            setBackgroundColor(Color.parseColor("#111827"))
        }

        // Status bar at the top
        tvStatus = TextView(this).apply {
            setTextColor(Color.WHITE)
            textSize = 14f
            gravity = Gravity.CENTER
            setPadding(24, 32, 24, 16)
        }
        root.addView(tvStatus, MATCH_PARENT, WRAP_CONTENT)

        // White receipt "paper" card
        linesContainer = LinearLayout(this).apply {
            orientation = LinearLayout.VERTICAL
            setBackgroundColor(Color.WHITE)
            setPadding(20, 20, 20, 20)
        }

        val scroll = ScrollView(this).apply {
            setPadding(16, 0, 16, 16)
        }
        scroll.addView(linesContainer, MATCH_PARENT, WRAP_CONTENT)
        root.addView(scroll, LinearLayout.LayoutParams(MATCH_PARENT, 0, 1f))

        // Button row
        val buttonBar = LinearLayout(this).apply {
            orientation = LinearLayout.HORIZONTAL
            setPadding(16, 12, 16, 16)
            gravity = Gravity.CENTER
        }

        val btnPrint = Button(this).apply {
            text = "Print"
            setBackgroundColor(Color.parseColor("#059669"))
            setTextColor(Color.WHITE)
            setOnClickListener { tryPrint() }
        }
        buttonBar.addView(btnPrint, LinearLayout.LayoutParams(0, WRAP_CONTENT, 1f).apply { marginEnd = 8 })

        val btnSave = Button(this).apply {
            text = "Save"
            setBackgroundColor(Color.parseColor("#4b5563"))
            setTextColor(Color.WHITE)
            setOnClickListener { executeSaveToGallery() }
        }
        buttonBar.addView(btnSave, LinearLayout.LayoutParams(0, WRAP_CONTENT, 1f).apply { marginEnd = 8 })

        val btnCancel = Button(this).apply {
            text = "Cancel"
            setBackgroundColor(Color.parseColor("#dc2626"))
            setTextColor(Color.WHITE)
            setOnClickListener { executeCancel() }
        }
        buttonBar.addView(btnCancel, LinearLayout.LayoutParams(0, WRAP_CONTENT, 1f))

        root.addView(buttonBar, MATCH_PARENT, WRAP_CONTENT)
        setContentView(root)
    }

    // ── receipt rendering ──────────────────────────────────────────

    private fun showReceiptLines(payload: ByteArray?) {
        linesContainer.removeAllViews()

        if (payload == null || payload.isEmpty()) {
            linesContainer.addView(makeLabel("Receipt preview not available.", Color.GRAY, false, true))
            return
        }

        val lines = try { ReceiptRenderer.parse(payload) } catch (e: Exception) { emptyList() }

        if (lines.isEmpty()) {
            linesContainer.addView(makeLabel("(empty receipt)", Color.GRAY, false, true))
            return
        }

        for (line in lines) {
            if (line.bitmap != null) {
                val iv = ImageView(this).apply {
                    setImageBitmap(line.bitmap)
                    scaleType = ImageView.ScaleType.FIT_CENTER
                    adjustViewBounds = true
                    setPadding(0, 4, 0, 4)
                }
                linesContainer.addView(iv, LinearLayout.LayoutParams(MATCH_PARENT, WRAP_CONTENT))
            } else {
                val tv = TextView(this).apply {
                    text = line.text.ifEmpty { " " } // keep blank lines visible
                    typeface = if (line.bold) Typeface.MONOSPACE else Typeface.MONOSPACE
                    setTypeface(typeface, if (line.bold) Typeface.BOLD else Typeface.NORMAL)
                    textSize = 11f
                    setTextColor(Color.BLACK)
                    gravity = if (line.center) Gravity.CENTER_HORIZONTAL else Gravity.START
                    setPadding(0, 1, 0, 1)
                    // Dashes / separator lines get a slightly lighter colour
                    if (line.text.all { it == '-' || it == '=' || it == '*' || it == ' ' }) {
                        setTextColor(Color.parseColor("#555555"))
                    }
                }
                linesContainer.addView(tv, MATCH_PARENT, WRAP_CONTENT)
            }
        }
    }

    private fun makeLabel(text: String, color: Int, bold: Boolean, center: Boolean) =
        TextView(this).apply {
            this.text = text
            setTextColor(color)
            textSize = 12f
            gravity = if (center) Gravity.CENTER else Gravity.START
            setPadding(0, 8, 0, 8)
            if (bold) setTypeface(null, Typeface.BOLD)
        }

    // ── actions ───────────────────────────────────────────────────

    private fun tryPrint() {
        val payload = PreviewHolder.payload ?: return
        val prefs   = Prefs(this)

        runOnUiThread { tvStatus.text = "🖨️ Connecting to printer…" }

        Thread {
            try {
                if (prefs.printerMac.isEmpty())
                    throw Exception("No printer selected. Pair the printer in Android Bluetooth settings first.")

                val printer = BluetoothPrinter(prefs.printerMac)
                printer.connect()
                printer.print(payload, prefs.autoCut)
                printer.close()

                val jobId = PreviewHolder.jobId
                if (jobId > 0)
                    try { ApiClient.ack(prefs.serverUrl, prefs.token, jobId, true, null) } catch (ignored: Exception) {}

                runOnUiThread {
                    Toast.makeText(this, "✓ Printed successfully", Toast.LENGTH_SHORT).show()
                    PreviewHolder.fire("print")
                    closeAndReturn()
                }
            } catch (e: Exception) {
                runOnUiThread { showPrinterErrorDialog(e.message ?: "Unknown error") }
            }
        }.start()
    }

    private fun showPrinterErrorDialog(errorMsg: String) {
        tvStatus.text = "⚠ Connection failed"
        AlertDialog.Builder(this)
            .setTitle("Printer Offline")
            .setMessage("Could not connect to printer.\n\nError: $errorMsg\n\nPair the printer in Android Bluetooth settings, then select it in the app.")
            .setCancelable(false)
            .setPositiveButton("Retry")         { _, _ -> tryPrint() }
            .setNeutralButton("Pair Printer")   { _, _ -> startActivity(Intent(this, MainActivity::class.java)) }
            .setNegativeButton("Save to Gallery") { _, _ -> executeSaveToGallery() }
            .show()
    }

    private fun executeSaveToGallery() {
        val payload = PreviewHolder.payload
        val jobId   = PreviewHolder.jobId
        val prefs   = Prefs(this)

        runOnUiThread { tvStatus.text = "Saving to Gallery…" }

        Thread {
            val bmp = try {
                ReceiptRenderer.render(
                    ReceiptRenderer.parse(payload ?: ByteArray(0)),
                    PreviewHolder.cols
                )
            } catch (e: Exception) { null }

            if (bmp == null) {
                runOnUiThread { Toast.makeText(this, "✗ Failed to create receipt image", Toast.LENGTH_LONG).show() }
                return@Thread
            }

            val saved = ReceiptRenderer.saveToGallery(this, bmp, "receipt_job_$jobId")
            runOnUiThread {
                if (saved) {
                    Toast.makeText(this, "✓ Saved to Gallery (Pictures/WholeTrade)", Toast.LENGTH_LONG).show()
                    Thread {
                        try { ApiClient.ack(prefs.serverUrl, prefs.token, jobId, true, null) } catch (ignored: Exception) {}
                    }.start()
                    PreviewHolder.fire("save")
                    closeAndReturn()
                } else {
                    Toast.makeText(this, "✗ Failed to save to Gallery", Toast.LENGTH_LONG).show()
                    tvStatus.text = "✗ Save failed"
                }
            }
        }.start()
    }

    private fun dispatchCancel(reason: String) {
        if (cancelDispatched) return
        cancelDispatched = true
        val jobId = PreviewHolder.jobId
        val prefs = Prefs(this)
        Thread {
            try { ApiClient.ack(prefs.serverUrl, prefs.token, jobId, false, reason) } catch (ignored: Exception) {}
        }.start()
        PreviewHolder.fire("cancel")
    }

    private fun executeCancel() {
        dispatchCancel("Cancelled by user")
        closeAndReturn()
    }

    /** Close the preview AND send the whole app task to the back, so the
     *  cashier lands back in the browser (invoice page) instead of on the
     *  app dashboard. The polling service keeps running in the background. */
    private fun closeAndReturn() {
        try { moveTaskToBack(true) } catch (ignored: Exception) {}
        finish()
    }

    // ── lifecycle ────────────────────────────────────────────────

    override fun onResume() {
        super.onResume()
        PreviewHolder.listener = { runOnUiThread { refresh() } }
        refresh()
    }

    override fun onPause() {
        super.onPause()
        PreviewHolder.listener = null
    }

    override fun onDestroy() {
        super.onDestroy()
        // Back button / swipe-dismiss path — executeCancel() hasn't run, so fire now.
        // If executeCancel() already ran, cancelDispatched is true and dispatchCancel is a no-op.
        if (isFinishing) dispatchCancel("Preview dismissed")
    }

    private fun refresh() {
        tvStatus.text = PreviewHolder.status
        showReceiptLines(PreviewHolder.payload)
    }
}
