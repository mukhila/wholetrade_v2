<?php

namespace App\Services\Printing\Drivers;

/**
 * TSPL2 driver — for TSC label printers only.
 * Most TSC receipt printers (DA200, TUP900) use EscposDriver instead.
 * Use this only if your TSC model is a LABEL printer (TDP/TTP/TX series).
 */
class TsplDriver implements Driver
{
    private const DPI      = 203;
    private const FONT     = '3';
    private const FONT_W   = 12;   // dots per char
    private const FONT_H   = 20;   // dots per row
    private const X_MARGIN = 8;

    protected int $width;

    public function __construct(int $widthChars = 48)
    {
        $this->width = $widthChars;
    }

    public function render(array $ops): string
    {
        $pageWidthMm  = $this->width <= 32 ? 58 : 80;
        $pageWidthDot = (int) round($pageWidthMm / 25.4 * self::DPI);

        // estimate page height from op count
        $lineCount = array_sum(array_map(
            fn($op) => in_array($op[0] ?? '', ['text', 'hr', 'row']) ? 1 : 0,
            $ops
        ));
        $pageHeightDot = max(200, $lineCount * (self::FONT_H + 4) + 80);

        $y     = 10;
        $align = 'LEFT';
        $bold  = false;
        $lines = [];

        foreach ($ops as $op) {
            switch ($op[0] ?? '') {
                case 'align':
                    $align = match($op[1] ?? 'left') {
                        'center' => 'CENTER',
                        'right'  => 'RIGHT',
                        default  => 'LEFT',
                    };
                    break;

                case 'bold':
                    $bold = (bool)($op[1] ?? false);
                    break;

                case 'size':
                    // TSPL fixed font — size ops ignored
                    break;

                case 'text': {
                    $text = $this->clean(rtrim(str_replace(["\r\n", "\n"], ' ', (string)($op[1] ?? ''))));
                    if ($text === '') { $y += self::FONT_H + 4; break; }

                    $x = match($align) {
                        'CENTER' => max(0, (int)($pageWidthDot / 2 - strlen($text) * self::FONT_W / 2)),
                        'RIGHT'  => max(0, $pageWidthDot - strlen($text) * self::FONT_W - self::X_MARGIN),
                        default  => self::X_MARGIN,
                    };
                    $bw = $bold ? 2 : 1;
                    $lines[] = 'TEXT ' . $x . ',' . $y . ',"' . self::FONT . '",0,' . $bw . ',' . $bw . ',"' . addslashes($text) . '"';
                    $y += self::FONT_H + 4;
                    break;
                }

                case 'row': {
                    $left  = $this->clean((string)($op[1] ?? ''));
                    $right = $this->clean((string)($op[2] ?? ''));
                    $combined = str_pad($left, max(0, $this->width - strlen($right) - 1)) . $right;
                    $bw = $bold ? 2 : 1;
                    $lines[] = 'TEXT ' . self::X_MARGIN . ',' . $y . ',"' . self::FONT . '",0,' . $bw . ',' . $bw . ',"' . addslashes($combined) . '"';
                    $y += self::FONT_H + 4;
                    break;
                }

                case 'item_hdr': {
                    $qw  = 4;
                    $col = max(9, (int)(($this->width - $qw) / 3));
                    $iw  = $this->width - $qw - $col - $col;
                    $hdr = str_pad('DESCRIPTION', $iw)
                         . str_pad('QTY',   $qw, ' ', STR_PAD_LEFT)
                         . str_pad('PRICE', $col, ' ', STR_PAD_LEFT)
                         . str_pad('AMT',   $col, ' ', STR_PAD_LEFT);
                    $lines[] = 'TEXT ' . self::X_MARGIN . ',' . $y . ',"' . self::FONT . '",0,2,2,"' . addslashes($hdr) . '"';
                    $y += self::FONT_H + 4;
                    break;
                }

                case 'item': {
                    $desc  = $this->clean((string)($op[1] ?? ''));
                    $qty   = $this->clean((string)($op[2] ?? ''));
                    $price = $this->clean((string)($op[3] ?? ''));
                    $amt   = $this->clean((string)($op[4] ?? ''));
                    $disc  = !empty($op[5]) ? $this->clean((string)$op[5]) : '';

                    $lines[] = 'TEXT ' . self::X_MARGIN . ',' . $y . ',"' . self::FONT . '",0,1,1,"' . addslashes($desc) . '"';
                    $y += self::FONT_H + 4;

                    $qw  = 4;
                    $col = max(9, (int)(($this->width - $qw) / 3));
                    $iw  = $this->width - $qw - $col - $col;
                    $vals = str_pad('', $iw)
                          . str_pad($qty,   $qw,  ' ', STR_PAD_LEFT)
                          . str_pad($price, $col, ' ', STR_PAD_LEFT)
                          . str_pad($amt,   $col, ' ', STR_PAD_LEFT);
                    $lines[] = 'TEXT ' . self::X_MARGIN . ',' . $y . ',"' . self::FONT . '",0,1,1,"' . addslashes($vals) . '"';
                    $y += self::FONT_H + 4;

                    if ($disc !== '') {
                        $discLine = str_pad('  Discount', max(0, $this->width - strlen($disc) - 1)) . '-' . $disc;
                        $lines[] = 'TEXT ' . self::X_MARGIN . ',' . $y . ',"' . self::FONT . '",0,1,1,"' . addslashes($discLine) . '"';
                        $y += self::FONT_H + 4;
                    }
                    break;
                }

                case 'hr':
                    $lines[] = 'BAR ' . self::X_MARGIN . ',' . $y . ',' . ($pageWidthDot - self::X_MARGIN * 2) . ',2';
                    $y += 6;
                    break;

                case 'feed':
                    $y += (self::FONT_H + 4) * max(1, (int)($op[1] ?? 1));
                    break;

                case 'barcode': {
                    $data = $this->clean((string)($op[1] ?? ''));
                    if ($data !== '') {
                        $bh = self::FONT_H * 3;
                        $lines[] = 'BARCODE ' . self::X_MARGIN . ',' . $y . ',"128",' . $bh . ',1,0,2,2,"' . addslashes($data) . '"';
                        $y += $bh + 20;
                    }
                    break;
                }

                case 'image': {
                    // images not natively supported in TSPL2 text mode — skip silently
                    $y += 60;
                    break;
                }
            }
        }

        $out  = "SIZE {$pageWidthMm} mm,{$pageHeightDot} dots\r\n";
        $out .= "GAP 0 mm,0\r\n";
        $out .= "DIRECTION 0\r\n";
        $out .= "CLS\r\n";
        foreach ($lines as $line) {
            $out .= $line . "\r\n";
        }
        $out .= "PRINT 1,1\r\n";
        $out .= "CUT\r\n";

        return $out;
    }

    protected function clean(string $s): string
    {
        $s = str_replace(["\u{2018}", "\u{2019}", "\u{201C}", "\u{201D}", "\u{2013}", "\u{2014}"],
                         ["'",        "'",         '"',        '"',        '-',        '-'], $s);
        return preg_replace('/[^\x20-\x7E]/', '', $s) ?? '';
    }
}
