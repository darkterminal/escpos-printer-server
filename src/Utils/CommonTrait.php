<?php
declare(strict_types=1);

namespace Darkterminal\EscposPrinterServer\Utils;

trait CommonTrait
{
    /**
     * Format currency
     */
    public function toIDR(
        int|float $number, 
        bool $prefix = false, 
        int $decimal_number = 0, 
        string $decimal_point = ',', 
        string $thousand_point = '.'
    ): string
    {
        $formatted = number_format($number, $decimal_number, $decimal_point, $thousand_point);
        return ($prefix == false) ? $formatted : "Rp. $formatted";
    }

    /**
     * Create aligned text for two columns
     */
    public function makeAlignText(string $kolom1, string $kolom2, int $max_width): string
    {
        $full_width = $max_width;
        $half_width = (int) floor($full_width / 2);

        $rawText = str_pad($kolom1, $half_width);
        $rawText .= str_pad($kolom2, $half_width, " ", STR_PAD_LEFT);
        return "$rawText\n";
    }

    /**
     * Create content with word wrapping for two columns
     */
    public function makeWrapText(string $kolom1, string $kolom2, string $template = 'epson'): string
    {
        $lebar_kolom_1 = ($template === 'epson') ? 18 : 22;
        $lebar_kolom_2 = ($template === 'epson') ? 21 : 25;

        $kolom1 = wordwrap($kolom1, $lebar_kolom_1, "\n", true);
        $kolom2 = wordwrap($kolom2, $lebar_kolom_2, "\n", true);

        $kolom1Array = explode("\n", $kolom1);
        $kolom2Array = explode("\n", $kolom2);

        $jmlBarisTerbanyak = max(count($kolom1Array), count($kolom2Array));
        $hasilBaris = [];

        for ($i = 0; $i < $jmlBarisTerbanyak; $i++) {
            $hasilKolom1 = str_pad($kolom1Array[$i] ?? "", $lebar_kolom_1);
            $hasilKolom2 = str_pad($kolom2Array[$i] ?? "", $lebar_kolom_2, " ", STR_PAD_LEFT);
            $hasilBaris[] = "{$hasilKolom1} {$hasilKolom2}";
        }

        return implode("\n", $hasilBaris) . "\n";
    }
}
