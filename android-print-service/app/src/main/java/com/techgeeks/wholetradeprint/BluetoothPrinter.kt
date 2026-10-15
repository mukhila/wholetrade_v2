package com.techgeeks.wholetradeprint

import android.annotation.SuppressLint
import android.bluetooth.BluetoothAdapter
import android.bluetooth.BluetoothSocket
import java.io.IOException
import java.io.OutputStream
import java.util.UUID

/**
 * Bluetooth Classic (SPP/RFCOMM) printer connection with auto-reconnect.
 *
 * Uses the OS-level pairing (done once in Android Settings), so there is
 * never a chooser dialog. Keeps the socket open between jobs; if the printer
 * slept or dropped the link, the next print reconnects transparently.
 */
@SuppressLint("MissingPermission") // BLUETOOTH_CONNECT is checked in MainActivity before service start
class BluetoothPrinter(private val mac: String) {

    companion object {
        private val SPP_UUID: UUID = UUID.fromString("00001101-0000-1000-8000-00805F9B34FB")

        /** GS V 66 0 — partial cut with feed. */
        val CUT_COMMAND = byteArrayOf(0x1d, 0x56, 0x42, 0x00)

        /** ESC d 6 — feed 6 lines before cutting so content clears the cutter gap (~30 mm). */
        private val FEED_BEFORE_CUT = byteArrayOf(0x1B, 0x64, 0x06)

        /** Bytes per write; small chunks + pauses keep clone printers reliable. */
        private const val CHUNK_SIZE = 512
        private const val CHUNK_DELAY_MS = 25L
        /** Pause after each GS v 0 raster band so the printer can drain its buffer. */
        private const val RASTER_BAND_DELAY_MS = 80L
        /** Time after the last byte is sent for the printer to finish printing before
         *  the socket closes. A full receipt (store header + ~10 items + QR image +
         *  signature + feeds) totals ~320 mm of paper. At 50 mm/s that is 6400 ms to
         *  print; writing the payload takes ~2600 ms, so 5000 ms drain leaves a ~1 s
         *  margin for printers down to ~43 mm/s. Cheap BT clones retain their internal
         *  print buffer across RFCOMM disconnects, so if the socket closes too early
         *  the unprinted tail (bottom of QR + signature) appears at the start of the
         *  next job. */
        private const val FINAL_DRAIN_MS = 5000L
    }

    private var socket: BluetoothSocket? = null
    private var output: OutputStream? = null

    val isConnected: Boolean
        get() = socket?.isConnected == true

    @Synchronized
    @Throws(IOException::class)
    fun connect() {
        close()
        val adapter = BluetoothAdapter.getDefaultAdapter()
            ?: throw IOException("Bluetooth not available on this device")
        if (!adapter.isEnabled) throw IOException("Bluetooth is turned off")

        val device = adapter.getRemoteDevice(mac)
        // cancelDiscovery needs BLUETOOTH_SCAN on Android 12+; it's only an
        // optimization, so never let it block the connection.
        try { adapter.cancelDiscovery() } catch (ignored: SecurityException) {}

        val s = device.createRfcommSocketToServiceRecord(SPP_UUID)
        try {
            s.connect()
        } catch (e: IOException) {
            // Fallback for some clone printers that only accept channel 1
            try {
                val m = device.javaClass.getMethod("createRfcommSocket", Int::class.javaPrimitiveType)
                val s2 = m.invoke(device, 1) as BluetoothSocket
                s2.connect()
                socket = s2
                output = s2.outputStream
                return
            } catch (e2: Exception) {
                try { s.close() } catch (ignored: IOException) {}
                throw IOException("Cannot connect to printer ($mac): ${e.message}")
            }
        }
        socket = s
        output = s.outputStream
    }

    /**
     * Send one print job. Reconnects automatically if the link dropped
     * (printer sleep, power cycle, out of range and back).
     */
    @Synchronized
    @Throws(IOException::class)
    fun print(payload: ByteArray, autoCut: Boolean) {
        // Always feed paper before cutting so the last lines clear the cutter gap.
        // Without this, content near the bottom (QR code tail, signature) stays
        // hidden inside the printer between the print head and the cutter blade.
        val data = if (autoCut) payload + FEED_BEFORE_CUT + CUT_COMMAND
                   else         payload + FEED_BEFORE_CUT

        try {
            writeChunked(data)
        } catch (e: IOException) {
            // Stale socket — reconnect once and retry the whole job
            connect()
            writeChunked(data)
        }
    }

    @Throws(IOException::class)
    private fun writeChunked(data: ByteArray) {
        val out = output ?: run { connect(); output!! }
        var offset = 0
        while (offset < data.size) {
            // Detect GS v 0 raster band (\x1d\x76\x30 + mode + xL xH yL yH).
            // Send the full band in sub-chunks, then pause so the printer can
            // print and drain its buffer before the next band or text arrives.
            if (offset + 8 <= data.size &&
                data[offset]   == 0x1D.toByte() &&
                data[offset+1] == 0x76.toByte() &&
                data[offset+2] == 0x30.toByte()) {
                val xL = data[offset + 4].toInt() and 0xFF
                val xH = data[offset + 5].toInt() and 0xFF
                val yL = data[offset + 6].toInt() and 0xFF
                val yH = data[offset + 7].toInt() and 0xFF
                val bandBytes = 8 + (xL + xH * 256) * (yL + yH * 256)
                val bandEnd = minOf(offset + bandBytes, data.size)
                var pos = offset
                while (pos < bandEnd) {
                    val chunkEnd = minOf(pos + CHUNK_SIZE, bandEnd)
                    out.write(data, pos, chunkEnd - pos)
                    out.flush()
                    pos = chunkEnd
                    if (pos < bandEnd) Thread.sleep(CHUNK_DELAY_MS)
                }
                Thread.sleep(RASTER_BAND_DELAY_MS)
                offset = bandEnd
                continue
            }
            // Scan ahead within this chunk for a GS v 0 header so the regular
            // chunk path never straddles one. Without this, the first raster band
            // (which is preceded by 3 bytes of ESC \x33 \x00) gets swallowed into
            // a regular chunk and sent without the RASTER_BAND_DELAY_MS pause,
            // causing the printer's buffer to overflow on the leading bands.
            var end = minOf(offset + CHUNK_SIZE, data.size)
            for (i in (offset + 1)..(end - 3)) {
                if (data[i] == 0x1D.toByte() &&
                    data[i + 1] == 0x76.toByte() &&
                    data[i + 2] == 0x30.toByte()) {
                    end = i
                    break
                }
            }
            out.write(data, offset, end - offset)
            out.flush()
            offset = end
            if (offset < data.size) Thread.sleep(CHUNK_DELAY_MS)
        }
        // Hold the socket open while the printer finishes printing all buffered data.
        Thread.sleep(FINAL_DRAIN_MS)
    }

    @Synchronized
    fun close() {
        try { output?.close() } catch (ignored: IOException) {}
        try { socket?.close() } catch (ignored: IOException) {}
        output = null
        socket = null
    }
}
