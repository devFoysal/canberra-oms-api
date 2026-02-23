<?php

use Illuminate\Support\Str;

if (!function_exists('slugify')) {
    /**
     * Generate URL-friendly slug with 8-character random suffix
     *
     * @param string $text
     * @return string
     */
    function slugify(string $text): string
    {
        // Generate 8-char alphanumeric lowercase suffix
        $random = Str::lower(Str::random(8));

        // Remove accents/diacritics
        $baseSlug = Str::slug($text, '-', null); // Laravel slug helper

        return "{$baseSlug}-{$random}";
    }
}

if (!function_exists('generate_sku')) {
    /**
     * Generate SKU with pattern: [PREFIX]-[YYYYMMDD]-[RANDOM]
     *
     * @param string $prefix Category code or custom string (e.g. "ELEC")
     * @return string
     */
    function generate_sku(string $prefix): string
    {
        $date = date('Ymd');
        $random = Str::upper(Str::random(4)); // 4 uppercase letters/numbers

        return "{$prefix}-{$date}-{$random}";
    }
}


if (!function_exists('generate_employee_code')) {
    /**
     * Generate Employee Code with pattern: [PREFIX]-[YYYYMMDD]-[RANDOM]
     *
     * @param string $prefix Department or company code (e.g. "HR", "ENG")
     * @return string
     */
    function generate_employee_code(string $prefix = 'EMP'): string
    {
        $date = date('Ymd');               // Current date YYYYMMDD
        $random = Str::upper(Str::random(4)); // 4-char random uppercase alphanumeric

        return "{$prefix}-{$date}-{$random}";
    }
}

if (!function_exists('number_to_words')) {
    function number_to_words($number): string
    {
        // Return 'zero' if null or not numeric
        if (!is_numeric($number)) {
            return 'zero';
        }

        try {
            $formatter = new \NumberFormatter("en", \NumberFormatter::SPELLOUT);

            // Handle decimals
            if (strpos((string)$number, '.') !== false) {
                $parts = explode('.', (string)$number);
                $integerPart = $formatter->format($parts[0]);
                $decimalPart = implode(' ', str_split($parts[1])); // spell out each digit
                return $integerPart . ' point ' . $decimalPart;
            }

            return $formatter->format($number);

        } catch (\Exception $e) {
            // fallback if any error occurs
            return (string)$number;
        }
    }

    /**
     * Check if file exists in storage
     *
     * @param string $path Relative path (e.g., 'storage/uploads/filename.webp')
     * @return string
     */
    if (!function_exists('status_label')) {
        function status_label($value)
        {
            return str_replace('_', ' ', $value);
        }
    }
}

if (!function_exists('money_format_bd')) {
    function money_format_bd($amount, $decimals = 2)
    {
        $amount = number_format((float) $amount, $decimals, '.', '');
        [$int, $dec] = explode('.', $amount);

        $last3 = substr($int, -3);
        $rest = substr($int, 0, -3);

        if ($rest !== '') {
            $rest = preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', $rest);
            $int = $rest . ',' . $last3;
        }

        return $decimals ? $int . '.' . $dec : $int;
    }
}
