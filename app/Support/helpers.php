<?php

if (! function_exists('rupiah')) {
    /**
     * Format a number as Indonesian rupiah.
     */
    function rupiah(int|float|string|null $amount, bool $withDecimals = false): string
    {
        $value = (float) ($amount ?? 0);
        $decimals = $withDecimals ? 2 : 0;

        return 'Rp '.number_format($value, $decimals, ',', '.');
    }
}

if (! function_exists('rupiah_short')) {
    /**
     * Compact rupiah label for chart axes, e.g. 1,2 jt / 250 rb.
     */
    function rupiah_short(int|float|string|null $amount): string
    {
        $value = (float) ($amount ?? 0);
        $absolute = abs($value);

        if ($absolute >= 1_000_000_000) {
            return rtrim(rtrim(number_format($value / 1_000_000_000, 1, ',', '.'), '0'), ',').' M';
        }

        if ($absolute >= 1_000_000) {
            return rtrim(rtrim(number_format($value / 1_000_000, 1, ',', '.'), '0'), ',').' jt';
        }

        if ($absolute >= 1_000) {
            return number_format($value / 1_000, 0, ',', '.').' rb';
        }

        return number_format($value, 0, ',', '.');
    }
}

if (! function_exists('initials')) {
    function initials(?string $name): string
    {
        $words = preg_split('/\s+/', trim((string) $name)) ?: [];
        $words = array_values(array_filter($words));

        if ($words === []) {
            return '?';
        }

        $first = mb_substr($words[0], 0, 1);
        $last = count($words) > 1 ? mb_substr(end($words), 0, 1) : '';

        return mb_strtoupper($first.$last);
    }
}
