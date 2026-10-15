<?php

namespace App\Services\Printing\Drivers;

class EscposDriver implements Driver
{
    protected int    $width;
    protected string $align = 'left';
    protected string $size  = 'normal';

    public function __construct(int $widthChars = 48)
    {
        $this->width = $widthChars;
    }

    protected function effWidth(): int
    {
        return $this->size === 'normal' ? $this->width : max(8, intdiv($this->width, 2));
    }

    public function render(array $ops): string
    {
        \Log::debug('EscposDriver render', ['width' => $this->width]);
        $out = '';
        foreach ($ops as $op) {
            switch ($op[0]) {

                case 'init':
                    // Avoid sending ESC @ (\x1b\x40) because many Bluetooth/ESC-POS clone
                    // chipsets undergo a slow hardware reset and clear/drop the serial buffer.
                    // When printed in rapid succession, this drops the initial bytes of the
                    // job (the header). Instead, we perform a manual "soft reset" of all styles.
                    $out .= "\x1b\x45\x00"; // Bold off
                    $out .= "\x1b\x61\x00"; // Align left
                    $out .= "\x1d\x21\x00"; // Size normal
                    $out .= "\x1b\x2d\x00"; // Underline off
                    $out .= "\x1b\x32";     // Default line spacing (1/6 inch)
                    $this->align = 'left';
                    $this->size  = 'normal';
                    break;

                case 'align':
                    $this->align = $op[1];
                    $out .= "\x1b\x61" . chr(['left' => 0, 'center' => 1, 'right' => 2][$op[1]] ?? 0);
                    break;

                case 'bold':
                    $out .= "\x1b\x45" . chr($op[1] ? 1 : 0);
                    break;

                case 'size':
                    $this->size = $op[1];
                    $map = ['normal' => 0x00, 'wide' => 0x10, 'big' => 0x11];
                    $out .= "\x1d\x21" . chr($map[$op[1]] ?? 0x00);
                    break;

                case 'text':
                    $out .= $this->emitText($this->clean($op[1]));
                    break;

                case 'row':
                    $out .= $this->row($this->clean($op[1]), $this->clean($op[2]));
                    break;

                case 'item_hdr':
                    $out .= $this->itemHeader();
                    break;

                case 'item':
                    // ['item', desc, qty, price, amt, ?discount]
                    $out .= $this->itemRow(
                        $this->clean($op[1]),
                        $this->clean($op[2]),
                        $this->clean($op[3]),
                        $this->clean($op[4])
                    );
                    if (!empty($op[5])) {
                        $out .= $this->row('  Discount', '-' . $this->clean($op[5]));
                    }
                    break;

                case 'hr':
                    $out .= str_repeat($op[1], $this->effWidth()) . "\n";
                    break;

                case 'feed':
                    $out .= str_repeat("\n", max(1, (int) $op[1]));
                    break;

                case 'qr_image':
                    $out .= $this->rasterImage($op[1]);
                    break;

                case 'cut':
                    // No hardware cut — some BT printers mis-handle GS V and
                    // corrupt the next job's buffer. Feed only.
                    // NOTE: do NOT send ESC @ here. A reset arriving while the
                    // printer is still printing clears its buffer on many BT
                    // clones and swallows the START of the next job (second
                    // receipt prints with the header missing). The next job's
                    // 'init' op already resets state cleanly.
                    $out .= "\x1b\x45\x00";         // bold off
                    $out .= "\x1b\x61\x00";         // align left
                    $out .= str_repeat("\n", 8);    // feed to clear cutter slot
                    break;
            }
        }
        \Log::debug('EscposDriver rendered', ['bytes' => strlen($out)]);
        return $out;
    }

    /* ── 4-column item layout ─────────────────────────────────────────────
       Column widths are calculated dynamically from $this->width so the
       same ops look correct on 80mm (48 or 42 cols) and 58mm (32 cols).

       Formula:  price_col = amt_col = max(9, floor((width - qty_col) / 3))
                 indent_col = width - qty_col - price_col - amt_col
    ── */
    protected function itemCols(): array
    {
        $qw  = 4;
        $col = max(9, (int)(($this->width - $qw) / 3));
        $iw  = $this->width - $qw - $col - $col;
        return [$iw, $qw, $col, $col];   // [indent, qty, price, amt]
    }

    protected function itemHeader(): string
    {
        [$iw, $qw, $pw, $aw] = $this->itemCols();
        return str_pad('DESCRIPTION', $iw)
            . str_pad('QTY',   $qw, ' ', STR_PAD_LEFT)
            . str_pad('PRICE', $pw, ' ', STR_PAD_LEFT)
            . str_pad('AMT',   $aw, ' ', STR_PAD_LEFT)
            . "\n";
    }

    protected function itemRow(string $desc, string $qty, string $price, string $amt): string
    {
        [$iw, $qw, $pw, $aw] = $this->itemCols();

        // Description word-wraps across the full line width
        $out = implode("\n", $this->wrap($desc, $this->effWidth())) . "\n";

        // Values: indented then right-aligned qty / price / amt
        $out .= str_pad('', $iw)
            . str_pad($qty,   $qw, ' ', STR_PAD_LEFT)
            . str_pad($price, $pw, ' ', STR_PAD_LEFT)
            . str_pad($amt,   $aw, ' ', STR_PAD_LEFT)
            . "\n";

        return $out;
    }

    /* ── text with word wrap ── */
    protected function emitText(string $s): string
    {
        $w = $this->effWidth();

        if ($this->size !== 'normal' && strlen($s) > $w) {
            $reset   = "\x1d\x21\x00";
            $restore = "\x1d\x21" . chr($this->size === 'big' ? 0x11 : 0x10);
            return $reset . implode("\n", $this->wrap($s, $this->width)) . "\n" . $restore;
        }

        return implode("\n", $this->wrap($s, $w)) . "\n";
    }

    /* ── label ..... value, right-aligned ── */
    protected function row(string $left, string $right): string
    {
        $w     = $this->effWidth();
        $space = $w - strlen($left) - strlen($right);

        if ($space >= 1) {
            return $left . str_repeat(' ', $space) . $right . "\n";
        }

        $out = implode("\n", $this->wrap($left, $w)) . "\n";
        if ($right !== '') {
            $out .= str_pad($right, $w, ' ', STR_PAD_LEFT) . "\n";
        }
        return $out;
    }

    /**
     * Convert a JPEG/PNG image file to ESC/POS GS v 0 raster bytes,
     * centered on the paper and emitted in short horizontal bands.
     *
     * Bands matter: many Bluetooth ESC/POS clones have a small receive
     * buffer and silently drop (or garble) a single tall raster block.
     * Sending ~64 rows at a time prints reliably on the same hardware.
     */
    protected function rasterImage(string $path): string
    {
        if (!file_exists($path)) {
            \Log::warning('EscposDriver rasterImage: file not found', ['path' => $path]);
            return '';
        }
        if (!function_exists('imagecreatetruecolor')) {
            \Log::warning('EscposDriver rasterImage: GD extension not available - QR skipped');
            return '';
        }

        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $src = match($ext) {
            'png'          => @imagecreatefrompng($path),
            'jpeg', 'jpg'  => @imagecreatefromjpeg($path),
            'gif'          => @imagecreatefromgif($path),
            'webp'         => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : false,
            default        => false,
        };
        if (!$src) {
            \Log::warning('EscposDriver rasterImage: could not decode image', ['path' => $path, 'ext' => $ext]);
            return '';
        }

        // Row width in dots: 80mm paper ~ 576 dots, 58mm ~ 384 dots
        $rowDots  = $this->width >= 42 ? 576 : 384;
        $rowBytes = (int) ceil($rowDots / 8);

        // Target QR size in dots. Bigger = more dots per QR module = easier
        // to scan. Keep it a multiple of 8 so it packs into whole bytes.
        $qrSize = $this->width >= 42 ? 256 : 192;

        $dst = imagecreatetruecolor($qrSize, $qrSize);
        imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255));
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $qrSize, $qrSize, imagesx($src), imagesy($src));
        imagedestroy($src);
        imagefilter($dst, IMG_FILTER_GRAYSCALE);

        $qrBytes  = (int) ceil($qrSize / 8);
        $padLeft  = (int) floor(($rowBytes - $qrBytes) / 2);
        $padRight = $rowBytes - $qrBytes - $padLeft;

        // Pack every row into 1-bit-per-dot bytes first.
        $rows = [];
        for ($y = 0; $y < $qrSize; $y++) {
            $line = str_repeat("\x00", $padLeft);
            for ($bx = 0; $bx < $qrBytes; $bx++) {
                $byte = 0;
                for ($bit = 0; $bit < 8; $bit++) {
                    $x = $bx * 8 + $bit;
                    if ($x < $qrSize) {
                        $c = imagecolorat($dst, $x, $y);
                        if ((($c >> 16) & 0xFF) < 128) { // dark pixel -> printed dot
                            $byte |= (0x80 >> $bit);
                        }
                    }
                }
                $line .= chr($byte);
            }
            if ($padRight > 0) $line .= str_repeat("\x00", $padRight);
            $rows[] = $line;
        }
        imagedestroy($dst);

        // Zero line spacing so consecutive bands butt together with no gap.
        $out  = "\x1b\x33\x00";

        // 16 rows per band keeps each GS v 0 payload under 1200 bytes, safe for
        // clone BT printers whose raster buffer is often only 2 KB.
        $band = 16;
        for ($y = 0; $y < $qrSize; $y += $band) {
            $h     = min($band, $qrSize - $y);
            $chunk = implode('', array_slice($rows, $y, $h));

            // GS v 0  m=0 (normal)  xL xH = bytes per row  yL yH = rows
            $out .= "\x1d\x76\x30\x00"
                  . chr($rowBytes & 0xFF) . chr(($rowBytes >> 8) & 0xFF)
                  . chr($h & 0xFF)        . chr(($h >> 8) & 0xFF)
                  . $chunk;
        }

        // Restore default line spacing (1/6 inch) and end the image line.
        $out .= "\x1b\x32" . "\n";

        \Log::debug('EscposDriver rasterImage ok', [
            'path'     => basename($path),
            'qrSize'   => $qrSize,
            'rowBytes' => $rowBytes,
            'bytes'    => strlen($out),
        ]);

        return $out;
    }

    protected function wrap(string $s, int $w): array
    {
        if (strlen($s) <= $w) return [$s];
        return explode("\n", wordwrap($s, $w, "\n", true));
    }

    protected function clean(string $s): string
    {
        $s = str_replace(['\u{2018}', '\u{2019}', '\u{201C}', '\u{201D}', '\u{2013}', '\u{2014}'],
                         ["'",        "'",          '"',        '"',        '-',        '-'], $s);
        $s = str_replace(["\u{2018}", "\u{2019}", "\u{201C}", "\u{201D}", "\u{2013}", "\u{2014}"],
                         ["'",        "'",         '"',        '"',        '-',        '-'], $s);
        return preg_replace('/[^\x20-\x7E]/', '', $s) ?? '';
    }
}
