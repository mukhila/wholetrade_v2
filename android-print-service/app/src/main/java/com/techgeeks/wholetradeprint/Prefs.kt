package com.techgeeks.wholetradeprint

import android.content.Context
import android.content.SharedPreferences

/** Tiny SharedPreferences wrapper — single source of truth for app settings. */
class Prefs(context: Context) {

    private val sp: SharedPreferences =
        context.getSharedPreferences("wt_print", Context.MODE_PRIVATE)

    var serverUrl: String
        get() = sp.getString("server_url", "https://sgtrade.techgeeksdigi.com") ?: "https://sgtrade.techgeeksdigi.com"
        set(v) = sp.edit().putString("server_url", v.trim().trimEnd('/')).apply()

    var token: String
        get() = sp.getString("token", "") ?: ""
        set(v) = sp.edit().putString("token", v).apply()

    var station: String
        get() = sp.getString("station", "main") ?: "main"
        set(v) = sp.edit().putString("station", v).apply()

    var companyName: String
        get() = sp.getString("company_name", "") ?: ""
        set(v) = sp.edit().putString("company_name", v).apply()

    var printerMac: String
        get() = sp.getString("printer_mac", "") ?: ""
        set(v) = sp.edit().putString("printer_mac", v).apply()

    var printerName: String
        get() = sp.getString("printer_name", "") ?: ""
        set(v) = sp.edit().putString("printer_name", v).apply()

    var autoCut: Boolean
        get() = sp.getBoolean("auto_cut", false)
        set(v) = sp.edit().putBoolean("auto_cut", v).apply()

    var pollSeconds: Int
        get() = sp.getInt("poll_seconds", 2)
        set(v) = sp.edit().putInt("poll_seconds", v.coerceIn(1, 30)).apply()

    /** Remembered so BootReceiver can restart the service after reboot. */
    var serviceEnabled: Boolean
        get() = sp.getBoolean("service_enabled", false)
        set(v) = sp.edit().putBoolean("service_enabled", v).apply()

    /** Job requested by the web deep link; consumed by the polling service. */
    var requestedJobId: Int
        get() = sp.getInt("requested_job_id", -1)
        set(v) = sp.edit().putInt("requested_job_id", v).apply()

    /** Full stack trace of the last crash, captured by WTApp. */
    var lastCrash: String
        get() = sp.getString("last_crash", "") ?: ""
        set(v) = sp.edit().putString("last_crash", v).apply()

    val isConfigured: Boolean
        get() = serverUrl.isNotEmpty() && token.isNotEmpty()
}
