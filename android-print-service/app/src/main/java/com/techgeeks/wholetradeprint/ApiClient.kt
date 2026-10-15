package com.techgeeks.wholetradeprint

import org.json.JSONObject
import java.io.BufferedReader
import java.net.HttpURLConnection
import java.net.URL

/**
 * Minimal HTTP client for the Laravel print-bridge API.
 * All methods are blocking — call them from a worker thread only.
 */
object ApiClient {

    private const val CONNECT_TIMEOUT = 10_000
    private const val READ_TIMEOUT = 15_000

    /** POST print-bridge/register  { company_code } → { token, station, company_name } */
    fun register(serverUrl: String, companyCode: String, station: String): JSONObject {
        return post(
            "$serverUrl/print-bridge/register",
            token = null,
            body = JSONObject().put("company_code", companyCode).put("station", station)
        )
    }

    /** GET print-bridge/pending → job JSONObject or null when queue is empty. */
    fun pending(serverUrl: String, token: String, station: String, jobId: Int = -1): JSONObject? {
        val jobQuery = if (jobId > 0) "&job_id=$jobId" else ""
        val res = get("$serverUrl/print-bridge/pending?station=$station&format=escpos$jobQuery", token)
        return if (res.isNull("job")) null else res.getJSONObject("job")
    }

    /** POST print-bridge/ack/{id}  { status, error } */
    fun ack(serverUrl: String, token: String, jobId: Int, ok: Boolean, error: String?) {
        post(
            "$serverUrl/print-bridge/ack/$jobId",
            token,
            JSONObject()
                .put("status", if (ok) "done" else "failed")
                .put("error", error ?: JSONObject.NULL)
        )
    }

    /* ── internals ── */

    private fun get(url: String, token: String?): JSONObject =
        exchange(url, "GET", token, null)

    private fun post(url: String, token: String?, body: JSONObject): JSONObject =
        exchange(url, "POST", token, body)

    private fun exchange(url: String, method: String, token: String?, body: JSONObject?): JSONObject {
        val conn = URL(url).openConnection() as HttpURLConnection
        try {
            conn.requestMethod = method
            conn.connectTimeout = CONNECT_TIMEOUT
            conn.readTimeout = READ_TIMEOUT
            conn.setRequestProperty("Accept", "application/json")
            token?.let { conn.setRequestProperty("X-Bridge-Token", it) }

            if (body != null) {
                conn.doOutput = true
                conn.setRequestProperty("Content-Type", "application/json")
                conn.outputStream.use { it.write(body.toString().toByteArray(Charsets.UTF_8)) }
            }

            val code = conn.responseCode
            val stream = if (code in 200..299) conn.inputStream else conn.errorStream
            val text = stream?.bufferedReader()?.use(BufferedReader::readText) ?: "{}"

            val json = try { JSONObject(text) } catch (e: Exception) {
                throw ApiException("HTTP $code — non-JSON response from server")
            }

            if (code !in 200..299) {
                throw ApiException(json.optString("error", "HTTP $code"))
            }
            return json
        } finally {
            conn.disconnect()
        }
    }

    class ApiException(message: String) : Exception(message)
}
