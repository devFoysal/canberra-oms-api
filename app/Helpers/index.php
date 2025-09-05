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
