package com.techgeeks.wholetradeprint

import android.app.Application
import java.io.PrintWriter
import java.io.StringWriter
import java.text.SimpleDateFormat
import java.util.Date
import java.util.Locale

/**
 * Captures any uncaught exception (i.e. every crash) and stores the full
 * stack trace in SharedPreferences. MainActivity shows it on next launch,
 * so crashes can be diagnosed straight from the phone — no ADB/logcat.
 */
class WTApp : Application() {

    override fun onCreate() {
        super.onCreate()
        val systemHandler = Thread.getDefaultUncaughtExceptionHandler()

        Thread.setDefaultUncaughtExceptionHandler { thread, throwable ->
            try {
                val sw = StringWriter()
                throwable.printStackTrace(PrintWriter(sw))
                val time = SimpleDateFormat("yyyy-MM-dd HH:mm:ss", Locale.US).format(Date())
                Prefs(this).lastCrash =
                    "[$time] CRASH on thread '${thread.name}'\n$sw"
            } catch (ignored: Exception) {}

            // let Android finish the normal crash flow
            systemHandler?.uncaughtException(thread, throwable)
        }
    }
}
