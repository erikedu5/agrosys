<?php

namespace App\Services\Pos;

use Illuminate\Validation\ValidationException;

class Decimal
{
    public static function units(mixed $value, string $field, bool $signed = false): int
    {
        // Legacy PWA/database values may be floats. Reject NaN/INF and avoid exponent notation.
        if (is_float($value)) {
            if (! is_finite($value)) {
                self::invalid($field);
            }
            $value = rtrim(rtrim(sprintf('%.8F', $value), '0'), '.');
        }
        if (! is_string($value) && ! is_int($value)) {
            self::invalid($field);
        }
        $value = (string) $value;
        $pattern = $signed ? '/^(-?)(\d{1,8})(?:\.(\d{1,2}))?$/' : '/^()(\d{1,8})(?:\.(\d{1,2}))?$/';
        if (! preg_match($pattern, $value, $match)) {
            self::invalid($field);
        }
        $units = (int) $match[2] * 100 + (int) str_pad($match[3] ?? '', 2, '0');

        return ($match[1] ?? '') === '-' ? -$units : $units;
    }

    public static function format(int $units): string
    {
        return ($units < 0 ? '-' : '').intdiv(abs($units), 100).'.'.str_pad((string) (abs($units) % 100), 2, '0', STR_PAD_LEFT);
    }

    public static function roundRatio(int $numerator, int $denominator): int
    {
        return intdiv($numerator + intdiv($denominator, 2), $denominator);
    }

    public static function invalid(string $field): never
    {
        throw ValidationException::withMessages([$field => 'Importe o cantidad decimal inválida (máximo dos decimales).']);
    }
}
