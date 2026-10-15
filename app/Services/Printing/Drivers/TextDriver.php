<?php

namespace App\Services\Printing\Drivers;

class TextDriver implements Driver
{
    protected int $width;

    public function __construct(int $widthChars = 48)
    {
        $this->width = $widthChars;
    }

    public function render(array $ops): string
    {
        $out = '';

        foreach ($ops as $op) {
            switch ($op[0]) {

                case 'text':
                    foreach ($this->wrap($this->clean((string)($op[1] ?? '')), $this->width) as $line) {
                        $out .= $line . "\n";
                    }
                    break;

                case 'hr':
                    $ch  = (string)($op[1] ?? '-');
                    $out .= str_repeat($ch[0], $this->width) . "\n";
                    break;

                case 'row':
                    $out .= $this->row(
                        $this->clean((string)($op[1] ?? '')),
                        $this->clean((string)($op[2] ?? ''))
                    );
                    break;

                case 'item_hdr':
                    $out .= $this->itemHeader();
                    break;

                case 'item':
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

                case 'feed':
                    $out .= str_repeat("\n", max(1, (int)($op[1] ?? 1)));
                    break;

                case 'cut':
                    $out .= "\x0C";
                    break;

                // init, align, bold, size — ignored in plain text
            }
        }

        return $out;
    }

    protected function itemCols(): array
    {
        $qw  = 4;
        $col = max(9, (int)(($this->width - $qw) / 3));
        $iw  = $this->width - $qw - $col - $col;
        return [$iw, $qw, $col, $col];
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
        $out = implode("\n", $this->wrap($desc, $this->width)) . "\n";
        $out .= str_pad('', $iw)
            . str_pad($qty,   $qw, ' ', STR_PAD_LEFT)
            . str_pad($price, $pw, ' ', STR_PAD_LEFT)
            . str_pad($amt,   $aw, ' ', STR_PAD_LEFT)
            . "\n";
        return $out;
    }

    protected function row(string $left, string $right): string
    {
        $space = $this->width - strlen($left) - strlen($right);
        if ($space >= 1) {
            return $left . str_repeat(' ', $space) . $right . "\n";
        }
        $out = implode("\n", $this->wrap($left, $this->width)) . "\n";
        if ($right !== '') {
            $out .= str_pad($right, $this->width, ' ', STR_PAD_LEFT) . "\n";
        }
        return $out;
    }

    protected function wrap(string $s, int $w): array
    {
        if (strlen($s) <= $w) return [$s];
        return explode("\n", wordwrap($s, $w, "\n", true));
    }

    protected function clean(string $s): string
    {
        $s = str_replace(["\u{2018}", "\u{2019}", "\u{201C}", "\u{201D}", "\u{2013}", "\u{2014}"],
                         ["'",        "'",         '"',        '"',        '-',        '-'], $s);
        return preg_replace('/[^\x20-\x7E]/', '', $s) ?? '';
    }
}
