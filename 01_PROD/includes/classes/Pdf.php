<?php
/**
 * Générateur PDF minimaliste (sans dépendance) : texte Helvetica, traits,
 * rectangles, images JPEG. Format A4, unités en points, origine en haut à gauche.
 */
class Pdf
{
    private const W = 595.28;
    private const H = 841.89;

    private array $pages = [];
    private int $current = -1;
    private array $images = [];
    private string $font = 'F1';
    private float $size = 10;

    private const WIDTHS_REGULAR = [278,278,355,556,556,889,667,191,333,333,389,584,278,333,278,278,556,556,556,556,556,556,556,556,556,556,278,278,584,584,584,556,1015,667,667,722,722,667,611,778,722,278,500,667,556,833,722,778,667,778,722,667,611,722,667,944,667,667,611,278,278,278,469,556,333,556,556,500,556,556,278,556,556,222,222,500,222,833,556,556,556,556,333,500,278,556,500,722,500,500,500,334,260,334,584];
    private const WIDTHS_BOLD = [278,333,474,556,556,889,722,238,333,333,389,584,278,333,278,278,556,556,556,556,556,556,556,556,556,556,333,333,584,584,584,611,975,722,722,722,722,667,611,778,722,278,556,722,611,833,722,778,667,778,722,667,611,722,667,944,667,667,611,333,278,333,584,556,333,556,611,556,611,556,333,611,611,278,278,556,278,889,611,611,611,611,389,556,333,611,556,778,556,556,500,389,280,389,584];

    public function addPage(): void
    {
        $this->pages[] = '';
        $this->current = count($this->pages) - 1;
    }

    public function pageWidth(): float { return self::W; }
    public function pageHeight(): float { return self::H; }

    private function out(string $s): void
    {
        $this->pages[$this->current] .= $s . "\n";
    }

    private static function enc(string $text): string
    {
        $t = @iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', str_replace(["\u{202F}", "\u{00A0}"], ' ', $text));
        return $t === false ? $text : $t;
    }

    private static function esc(string $s): string
    {
        return str_replace(['\\', '(', ')', "\r", "\n"], ['\\\\', '\\(', '\\)', '', ' '], $s);
    }

    private static function rgb(string $hex): string
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        $r = hexdec(substr($hex, 0, 2)) / 255;
        $g = hexdec(substr($hex, 2, 2)) / 255;
        $b = hexdec(substr($hex, 4, 2)) / 255;
        return sprintf('%.3F %.3F %.3F', $r, $g, $b);
    }

    public function setFont(bool $bold = false, float $size = 10): void
    {
        $this->font = $bold ? 'F2' : 'F1';
        $this->size = $size;
    }

    public function textWidth(string $text): float
    {
        $w = 0;
        $table = $this->font === 'F2' ? self::WIDTHS_BOLD : self::WIDTHS_REGULAR;
        $enc = self::enc($text);
        for ($i = 0, $n = strlen($enc); $i < $n; $i++) {
            $c = ord($enc[$i]);
            $w += ($c >= 32 && $c <= 126) ? $table[$c - 32] : 556;
        }
        return $w * $this->size / 1000;
    }

    /** Texte ; $align : L, R, C (x = bord gauche, droit ou centre). */
    public function text(float $x, float $y, string $text, string $color = '#000000', string $align = 'L'): void
    {
        if ($align === 'R') {
            $x -= $this->textWidth($text);
        } elseif ($align === 'C') {
            $x -= $this->textWidth($text) / 2;
        }
        $this->out(sprintf('BT %s rg /%s %.2F Tf %.2F %.2F Td (%s) Tj ET', self::rgb($color), $this->font, $this->size, $x, self::H - $y, self::esc(self::enc($text))));
    }

    /** Découpe un texte en lignes tenant dans $width. */
    public function wrap(string $text, float $width): array
    {
        $lines = [];
        foreach (preg_split('/\r?\n/', $text) as $para) {
            $line = '';
            foreach (preg_split('/\s+/', trim($para)) as $word) {
                $try = $line === '' ? $word : $line . ' ' . $word;
                if ($this->textWidth($try) > $width && $line !== '') {
                    $lines[] = $line;
                    $line = $word;
                } else {
                    $line = $try;
                }
            }
            $lines[] = $line;
        }
        return $lines;
    }

    /** Écrit un paragraphe ; retourne la position y après le texte. */
    public function paragraph(float $x, float $y, float $width, string $text, float $lineHeight = 13, string $color = '#000000'): float
    {
        foreach ($this->wrap($text, $width) as $line) {
            $this->text($x, $y, $line, $color);
            $y += $lineHeight;
        }
        return $y;
    }

    public function rect(float $x, float $y, float $w, float $h, ?string $fill = null, ?string $stroke = null): void
    {
        $ops = '';
        if ($fill) $ops .= self::rgb($fill) . ' rg ';
        if ($stroke) $ops .= self::rgb($stroke) . ' RG 0.5 w ';
        $mode = $fill && $stroke ? 'B' : ($fill ? 'f' : 'S');
        $this->out(sprintf('%s%.2F %.2F %.2F %.2F re %s', $ops, $x, self::H - $y - $h, $w, $h, $mode));
    }

    public function line(float $x1, float $y1, float $x2, float $y2, string $color = '#cccccc', float $width = 0.5): void
    {
        $this->out(sprintf('%s RG %.2F w %.2F %.2F m %.2F %.2F l S', self::rgb($color), $width, $x1, self::H - $y1, $x2, self::H - $y2));
    }

    /** Image JPEG (les autres formats sont convertis via GD si disponible). */
    public function image(string $file, float $x, float $y, float $w, ?float $h = null): bool
    {
        if (!is_file($file)) return false;
        $info = @getimagesize($file);
        if (!$info) return false;
        if ($info[2] === IMAGETYPE_JPEG) {
            $data = file_get_contents($file);
        } elseif (function_exists('imagecreatefromstring')) {
            $src = @imagecreatefromstring(file_get_contents($file));
            if (!$src) return false;
            $bg = imagecreatetruecolor($info[0], $info[1]);
            imagefill($bg, 0, 0, imagecolorallocate($bg, 255, 255, 255));
            imagecopy($bg, $src, 0, 0, 0, 0, $info[0], $info[1]);
            ob_start();
            imagejpeg($bg, null, 90);
            $data = ob_get_clean();
        } else {
            return false;
        }
        $h = $h ?? $w * $info[1] / $info[0];
        $name = 'I' . (count($this->images) + 1);
        $this->images[$name] = ['data' => $data, 'w' => $info[0], 'h' => $info[1], 'cs' => (($info['channels'] ?? 3) === 4 ? 'DeviceCMYK' : 'DeviceRGB')];
        $this->out(sprintf('q %.2F 0 0 %.2F %.2F %.2F cm /%s Do Q', $w, $h, $x, self::H - $y - $h, $name));
        return true;
    }

    public function output(): string
    {
        $objs = [];
        $objs[1] = '<< /Type /Catalog /Pages 2 0 R >>';
        $objs[3] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>';
        $objs[4] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>';
        $n = 5;
        $xobj = '';
        foreach ($this->images as $name => $img) {
            $objs[$n] = "<< /Type /XObject /Subtype /Image /Width {$img['w']} /Height {$img['h']} /ColorSpace /{$img['cs']} /BitsPerComponent 8 /Filter /DCTDecode /Length " . strlen($img['data']) . " >>\nstream\n" . $img['data'] . "\nendstream";
            $xobj .= "/$name $n 0 R ";
            $n++;
        }
        $resources = '<< /Font << /F1 3 0 R /F2 4 0 R >>' . ($xobj ? " /XObject << $xobj>>" : '') . ' >>';
        $kids = [];
        foreach ($this->pages as $content) {
            $stream = gzcompress($content);
            $objs[$n] = '<< /Length ' . strlen($stream) . " /Filter /FlateDecode >>\nstream\n" . $stream . "\nendstream";
            $objs[$n + 1] = sprintf('<< /Type /Page /Parent 2 0 R /MediaBox [0 0 %.2F %.2F] /Resources %s /Contents %d 0 R >>', self::W, self::H, $resources, $n);
            $kids[] = ($n + 1) . ' 0 R';
            $n += 2;
        }
        $objs[2] = '<< /Type /Pages /Kids [' . implode(' ', $kids) . '] /Count ' . count($kids) . ' >>';
        ksort($objs);

        $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $offsets = [];
        foreach ($objs as $i => $o) {
            $offsets[$i] = strlen($pdf);
            $pdf .= "$i 0 obj\n$o\nendobj\n";
        }
        $xref = strlen($pdf);
        $max = max(array_keys($objs));
        $pdf .= "xref\n0 " . ($max + 1) . "\n0000000000 65535 f \n";
        for ($i = 1; $i <= $max; $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i] ?? 0);
        }
        $pdf .= "trailer\n<< /Size " . ($max + 1) . " /Root 1 0 R >>\nstartxref\n$xref\n%%EOF";
        return $pdf;
    }
}
