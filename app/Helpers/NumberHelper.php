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

        // Handle thousands
        if ($number >= 1000) {
            $thousands = (int)($number / 1000);
            if ($thousands >= 100) {
                $result .= $hundreds[(int)($thousands / 100)] . ' ';
                $thousands %= 100;
            }
            if ($thousands >= 20) {
                $result .= $tens[(int)($thousands / 10)] . ' ';
                $thousands %= 10;
            }
            if ($thousands > 0) {
                $result .= $ones[$thousands] . ' ';
            }
            $result .= 'Thousand ';
            $number %= 1000;
        }

        // Handle hundreds
        if ($number >= 100) {
            $hundredDigit = (int)($number / 100);
            if ($hundredDigit > 0 && $hundredDigit <= 9) {
                $result .= $hundreds[$hundredDigit] . ' ';
            }
            $number %= 100;
        }

        // Handle tens and ones
        if ($number >= 20) {
            $tenDigit = (int)($number / 10);
            if ($tenDigit > 0 && $tenDigit <= 9) {
                $result .= $tens[$tenDigit] . ' ';
            }
            $number %= 10;
        }

        if ($number > 0 && $number <= 19) {
            $result .= $ones[$number] . ' ';
        }

        return trim($result);
    }
}
