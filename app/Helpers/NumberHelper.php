<?php

if (!function_exists('numberToWords')) {
    /**
     * Convert number to words
     */
    function numberToWords(float $number): string
    {
        $ones = [
            '',
            'One',
            'Two',
            'Three',
            'Four',
            'Five',
            'Six',
            'Seven',
            'Eight',
            'Nine',
            'Ten',
            'Eleven',
            'Twelve',
            'Thirteen',
            'Fourteen',
            'Fifteen',
            'Sixteen',
            'Seventeen',
            'Eighteen',
            'Nineteen'
        ];

        $tens = [
            '',
            '',
            'Twenty',
            'Thirty',
            'Forty',
            'Fifty',
            'Sixty',
            'Seventy',
            'Eighty',
            'Ninety'
        ];

        $hundreds = [
            '',
            'One Hundred',
            'Two Hundred',
            'Three Hundred',
            'Four Hundred',
            'Five Hundred',
            'Six Hundred',
            'Seven Hundred',
            'Eight Hundred',
            'Nine Hundred'
        ];

        if ($number == 0) {
            return 'Zero';
        }

        $number = (int) $number;
        $result = '';

        if ($number >= 100) {
            $result .= $hundreds[(int)($number / 100)] . ' ';
            $number %= 100;
        }

        if ($number >= 20) {
            $result .= $tens[(int)($number / 10)] . ' ';
            $number %= 10;
        }

        if ($number > 0) {
            $result .= $ones[$number] . ' ';
        }

        return trim($result) . ' Rupees Only';
    }
}
