package com.techgeeks.wholetradeprint

import android.app.Notification
import android.app.NotificationChannel
import android.app.NotificationManager
import android.app.PendingIntent
import android.app.Service
import android.content.Intent
import android.content.pm.ServiceInfo
import android.os.Build
import android.os.IBinder
import android.os.PowerManager
import android.util.Base64
import android.util.Log

/**
 * Foreground service: polls the Laravel print-bridge queue and prints
 * pending ESC/POS jobs to the paired Bluetooth Classic printer.
 *
 * Web app flow: cashier taps "⚡ Direct Print" → job queued on server →
 * this service picks it up within [Prefs.pollSeconds] → receipt prints.
 */
class PrintPollingService : Service() {

    companion object {
        const val ACTION_STOP = "com.techgeeks.wholetradeprint.STOP"
        const val CHANNEL_ID = "wt_print_service"
        const val NOTIF_ID = 1
        private const val TAG = "WTPrintService"

        @Volatile var isRunning = false
            private set
    }

    private var worker: Thread? = null
    private var wakeLock: PowerManager.WakeLock? = null

    override fun onBind(intent: Intent?): IBinder? = null

    override fun onStartCommand(intent: Intent?, flags: Int, startId: Int): Int {
        if (intent?.action == ACTION_STOP) {
            stopSelf()
            return START_NOT_STICKY
        }
        if (isRunning) return START_STICKY

        val prefs = Prefs(this)
        if (!prefs.isConfigured) {
            Log.w(TAG, "Service started but app not configured — stopping")
            stopSelf()
            return START_NOT_STICKY
        }

        startInForeground("Waiting for print jobs…")
        try { acquireWakeLock() } catch (e: Exception) { Log.w(TAG, "WakeLock unavailable: ${e.message}") }

        isRunning = true

        worker = Thread { pollLoop(prefs) }.apply {
            name = "wt-print-poll"
            start()
        }
        return START_STICKY
    }

    private fun pollLoop(prefs: Prefs) {
        var consecutiveErrors = 0

        while (isRunning) {
            var jobId = -1
            try {
                val requestedJobId = prefs.requestedJobId
                val job = ApiClient.pending(prefs.serverUrl, prefs.token, prefs.station, requestedJobId)

                if (job != null) {
                    jobId = job.getInt("id")
                    if (requestedJobId == jobId) prefs.requestedJobId = -1
                    updateNotification("New job #$jobId received")
                    Log.i(TAG, "Job #$jobId picked up")

                    val payload = Base64.decode(job.getString("payload"), Base64.DEFAULT)
                    val cols = job.optInt("cols", 42)

                    // Create lock object to wait for user action
                    val lock = Object()
                    var userChoice: String? = null // "print", "save", "cancel"

                    PreviewHolder.show(this, jobId, payload, cols, "Job #$jobId — tap Print or Save",
                        onPrint = {
                            synchronized(lock) {
                                userChoice = "print"
                                lock.notifyAll()
                            }
                        },
                        onSave = {
                            synchronized(lock) {
                                userChoice = "save"
                                lock.notifyAll()
                            }
                        },
                        onCancel = {
                            synchronized(lock) {
                                userChoice = "cancel"
                                lock.notifyAll()
                            }
                        }
                    )

                    // Block thread until user responds (max 120 s, so one
                    // unattended preview can never freeze the whole queue)
                    var waitedSec = 0
                    synchronized(lock) {
                        while (userChoice == null && isRunning && waitedSec < 120) {
                            try { lock.wait(1000) } catch (ignored: InterruptedException) { break }
                            waitedSec++
                        }
                    }
                    if (userChoice == null) {
                        userChoice = "timeout"
                        Log.w(TAG, "Job #$jobId preview timed out with no user action")
                        try {
                            ApiClient.ack(prefs.serverUrl, prefs.token, jobId, false,
                                "Preview shown but no action taken on the phone")
                        } catch (ignored: Exception) {}
                        updateNotification("⚠ Job #$jobId skipped — no action taken")
                    }

                    // Handle choice
                    if (userChoice == "print") {
                        Log.i(TAG, "Job #$jobId printed successfully by activity")
                    } else if (userChoice == "save") {
                        Log.i(TAG, "Job #$jobId saved to gallery by activity")
                    } else {
                        Log.i(TAG, "Job #$jobId cancelled or stopped")
                    }

                    consecutiveErrors = 0
                    continue // check immediately for the next queued job
                }
                consecutiveErrors = 0
            } catch (e: InterruptedException) {
                break
            } catch (e: Exception) {
                consecutiveErrors++
                Log.e(TAG, "Poll/print error: ${e.message}")
                updateNotification("⚠ ${e.message?.take(60)}")

                // Report failure so the web UI shows "Print failed" instead of timing out
                if (jobId > 0) {
                    try {
                        ApiClient.ack(prefs.serverUrl, prefs.token, jobId, false, e.message)
                    } catch (ignored: Exception) {}
                }
            }

            // Back off up to 30s when the server/printer is unreachable
            val delayMs = if (consecutiveErrors > 3) 30_000L else prefs.pollSeconds * 1000L
            try { Thread.sleep(delayMs) } catch (e: InterruptedException) { break }
        }
    }

    /* ── lifecycle ── */

    override fun onDestroy() {
        isRunning = false
        worker?.interrupt()
        wakeLock?.let { if (it.isHeld) it.release() }
        super.onDestroy()
    }

    /* ── notification / wakelock plumbing ── */

    private fun startInForeground(text: String) {
        val nm = getSystemService(NotificationManager::class.java)
        if (Build.VERSION.SDK_INT >= 26) {
            nm.createNotificationChannel(
                NotificationChannel(CHANNEL_ID, "Print service", NotificationManager.IMPORTANCE_LOW)
            )
        }
        val notification = buildNotification(text)
        try {
            if (Build.VERSION.SDK_INT >= 34) {
                startForeground(NOTIF_ID, notification, ServiceInfo.FOREGROUND_SERVICE_TYPE_DATA_SYNC)
            } else {
                startForeground(NOTIF_ID, notification)
            }
        } catch (e: Exception) {
            Log.e(TAG, "startForeground failed: ${e.message}")
            isRunning = false
            stopSelf()
        }
    }

    private fun buildNotification(text: String): Notification {
        val openApp = PendingIntent.getActivity(
            this, 0,
            Intent(this, MainActivity::class.java),
            PendingIntent.FLAG_UPDATE_CURRENT or PendingIntent.FLAG_IMMUTABLE
        )
        val builder = if (Build.VERSION.SDK_INT >= 26) {
            Notification.Builder(this, CHANNEL_ID)
        } else {
            @Suppress("DEPRECATION") Notification.Builder(this)
        }
        return builder
            .setContentTitle("Whole Trade Print Service")
            .setContentText(text)
            .setSmallIcon(android.R.drawable.ic_dialog_info)
            .setContentIntent(openApp)
            .setOngoing(true)
            .build()
    }

    private fun updateNotification(text: String) {
        getSystemService(NotificationManager::class.java)
            .notify(NOTIF_ID, buildNotification(text))
    }

    private fun acquireWakeLock() {
        val pm = getSystemService(PowerManager::class.java)
        wakeLock = pm.newWakeLock(PowerManager.PARTIAL_WAKE_LOCK, "WTPrint::poll").apply {
            setReferenceCounted(false)
            acquire(10 * 60 * 1000L) // 10-min safety timeout; service renews via START_STICKY
        }
    }
}
