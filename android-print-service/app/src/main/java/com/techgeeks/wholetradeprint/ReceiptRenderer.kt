package com.techgeeks.wholetradeprint

import android.content.ContentValues
import android.content.Context
import android.graphics.Bitmap
import android.graphics.Canvas
import android.graphics.Color
import android.graphics.Paint
import android.graphics.Typeface
import android.os.Build
import android.provider.MediaStore

/**
 * Renders an ESC/POS payload into a receipt image:
 *  - parse():        strips ESC/POS commands, keeps text + bold/center state
 *  - render():       draws the lines onto a white bitmap (receipt look)
 *  - saveToGallery(): stores the bitmap as PNG in Pictures/WholeTrade
 */
object ReceiptRenderer {

    data class Line(val text: String, val bold: Boolean, val center: Boolean, val bitmap: Bitmap? = null)

    fun parse(payload: ByteArray): List<Line> {
        val lines = ArrayList<Line>()
        val sb = StringBuilder()
        var bold = false
        var center = false
        var i = 0

        fun flush() {
            // Skip blank lines immediately after a bitmap band — the server emits a LF
            // between every GS v 0 raster band, which would otherwise create a white
            // stripe gap between QR code bands in the rendered image.
            if (sb.isEmpty() && lines.isNotEmpty() && lines.last().bitmap != null) return
            lines.add(Line(sb.toString(), bold, center))
            sb.setLength(0)
        }

        while (i < payload.size) {
            val b = payload[i].toInt() and 0xFF
            when {
                b == 0x0A -> { flush(); i++ }                       // LF
                b == 0x1B -> {                                      // ESC …
                    val cmd = if (i + 1 < payload.size) payload[i + 1].toInt() and 0xFF else -1
                    when (cmd) {
                        0x40 -> { bold = false; center = false; i += 2 }                                        // ESC @
                        0x61 -> { center = i + 2 < payload.size && payload[i + 2].toInt() == 1; i += 3 }        // ESC a n
                        0x45 -> { bold   = i + 2 < payload.size && payload[i + 2].toInt() == 1; i += 3 }        // ESC E n
                        0x21, 0x64, 0x4A -> i += 3                                                              // ESC ! / d / J
                        else -> i += 2
                    }
                }
                b == 0x1D -> {                                      // GS …
                    val cmd = if (i + 1 < payload.size) payload[i + 1].toInt() and 0xFF else -1
                    when (cmd) {
                        0x56 -> i += if (i + 2 < payload.size && (payload[i + 2].toInt() and 0xFF) >= 0x41) 4 else 3 // GS V m [n]
                        0x76 -> {                                                                               // GS v 0: raster image — decode to Bitmap
                            if (i + 7 < payload.size) {
                                val xL       = payload[i + 4].toInt() and 0xFF
                                val xH       = payload[i + 5].toInt() and 0xFF
                                val yL       = payload[i + 6].toInt() and 0xFF
                                val yH       = payload[i + 7].toInt() and 0xFF
                                val rowBytes = xL + xH * 256
                                val rowCount = yL + yH * 256
                                val dotWidth = rowBytes * 8
                                val dataStart = i + 8
                                val pixels   = IntArray(dotWidth * rowCount) { Color.WHITE }
                                for (y in 0 until rowCount) {
                                    for (bx in 0 until rowBytes) {
                                        val idx = dataStart + y * rowBytes + bx
                                        if (idx >= payload.size) break
                                        val b2 = payload[idx].toInt() and 0xFF
                                        for (bit in 0..7) {
                                            if ((b2 and (0x80 shr bit)) != 0)
                                                pixels[y * dotWidth + bx * 8 + bit] = Color.BLACK
                                        }
                                    }
                                }
                                if (sb.isNotEmpty()) flush() // only flush pending text, never create a blank line before a bitmap band
                                val bmp = Bitmap.createBitmap(dotWidth, rowCount, Bitmap.Config.ARGB_8888)
                                bmp.setPixels(pixels, 0, dotWidth, 0, 0, dotWidth, rowCount)
                                lines.add(Line("", bold = false, center = true, bitmap = bmp))
                                i += 8 + rowBytes * rowCount
                            } else i += 3
                        }
                        else -> i += 3                                                                         // GS ! n etc.
                    }
                }
                b in 0x20..0xFF -> { sb.append(b.toChar()); i++ }   // printable ASCII + extended (code-page chars)
                else -> i++                                         // NUL preamble & other control bytes
            }
        }
        if (sb.isNotEmpty()) flush()

        // Trim trailing blank feed lines for a tidy preview
        while (lines.isNotEmpty() && lines.last().text.isBlank()) lines.removeAt(lines.size - 1)
        return lines
    }

    fun render(lines: List<Line>, cols: Int): Bitmap {
        val textSize = 26f
        val paint = Paint(Paint.ANTI_ALIAS_FLAG).apply {
            typeface = Typeface.MONOSPACE
            this.textSize = textSize
            color = Color.BLACK
        }
        val charW = paint.measureText("W")
        val pad = 24f
        val lineH = textSize * 1.35f
        val postImageGap = 50f  // gap between last QR band and the next text line
        val width = (charW * cols + pad * 2).toInt()
        val innerW = (width - pad * 2).coerceAtLeast(1f)

        // Pre-calculate canvas height using the same logic as the draw loop
        var estimatedH = pad + textSize
        var prevWasBitmap = false
        for (line in lines) {
            if (line.bitmap != null && line.bitmap.width > 0) {
                val scale = innerW / line.bitmap.width.toFloat()
                estimatedH += line.bitmap.height * scale
                prevWasBitmap = true
            } else {
                if (prevWasBitmap) estimatedH += postImageGap
                estimatedH += lineH
                prevWasBitmap = false
            }
        }
        estimatedH += pad * 3  // generous bottom padding so nothing is clipped
        val height = estimatedH.toInt().coerceAtLeast(120)

        val bmp = Bitmap.createBitmap(width, height, Bitmap.Config.ARGB_8888)
        val canvas = Canvas(bmp)
        canvas.drawColor(Color.WHITE)

        var y = pad + textSize
        prevWasBitmap = false
        for (line in lines) {
            if (line.bitmap != null && line.bitmap.width > 0) {
                val scale = innerW / line.bitmap.width.toFloat()
                val bmpW = (line.bitmap.width * scale).toInt()
                val bmpH = (line.bitmap.height * scale).toInt()
                val x = ((width - bmpW) / 2f).coerceAtLeast(0f)
                canvas.drawBitmap(
                    line.bitmap, null,
                    android.graphics.Rect(x.toInt(), y.toInt(), (x + bmpW).toInt(), (y + bmpH).toInt()),
                    null
                )
                // Consecutive raster bands butt together — no gap between them
                y += bmpH
                prevWasBitmap = true
            } else {
                // First text line after a bitmap block gets a 50px breathing gap
                if (prevWasBitmap) y += postImageGap
                paint.isFakeBoldText = line.bold
                val w = paint.measureText(line.text)
                val x = if (line.center) ((width - w) / 2f).coerceAtLeast(pad) else pad
                canvas.drawText(line.text, x, y, paint)
                y += lineH
                prevWasBitmap = false
            }
        }
        return bmp
    }

    /** Save as PNG in the gallery. Returns true on success. */
    fun saveToGallery(context: Context, bmp: Bitmap, name: String): Boolean {
        return try {
            val values = ContentValues().apply {
                put(MediaStore.Images.Media.DISPLAY_NAME, "$name.png")
                put(MediaStore.Images.Media.MIME_TYPE, "image/png")
                if (Build.VERSION.SDK_INT >= 29) {
                    put(MediaStore.Images.Media.RELATIVE_PATH, "Pictures/WholeTrade")
                }
            }
            val uri = context.contentResolver
                .insert(MediaStore.Images.Media.EXTERNAL_CONTENT_URI, values) ?: return false
            context.contentResolver.openOutputStream(uri)?.use {
                bmp.compress(Bitmap.CompressFormat.PNG, 100, it)
            } ?: return false
            true
        } catch (e: Exception) {
            false
        }
    }
}
