<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;

class DatabaseHelper
{
    /**
     * Get the appropriate unaccent function based on database driver
     */
    public static function getUnaccentFunction($column, $value)
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'pgsql') {
            // PostgreSQL uses unaccent extension
            return [
                "LOWER(unaccent({$column})) LIKE LOWER(unaccent(?))",
                $value
            ];
        } else {
            // MySQL: Remove accents manually using REPLACE functions
            $column_clean = "LOWER(
                REPLACE(
                    REPLACE(
                        REPLACE(
                            REPLACE(
                                REPLACE(
                                    REPLACE(
                                        REPLACE(
                                            REPLACE(
                                                REPLACE(
                                                    REPLACE(
                                                        REPLACE(
                                                            REPLACE(
                                                                REPLACE(
                                                                    REPLACE(
                                                                        REPLACE(
                                                                            REPLACE(
                                                                                REPLACE(
                                                                                    REPLACE(
                                                                                        REPLACE(
                                                                                            REPLACE(
                                                                                                REPLACE({$column}, 'á', 'a'),
                                                                                            'à', 'a'),
                                                                                        'ä', 'a'),
                                                                                    'â', 'a'),
                                                                                'é', 'e'),
                                                                            'è', 'e'),
                                                                        'ë', 'e'),
                                                                    'ê', 'e'),
                                                                'í', 'i'),
                                                            'ì', 'i'),
                                                        'ï', 'i'),
                                                    'î', 'i'),
                                                'ó', 'o'),
                                            'ò', 'o'),
                                        'ö', 'o'),
                                    'ô', 'o'),
                                'ú', 'u'),
                            'ù', 'u'),
                        'ü', 'u'),
                    'û', 'u'),
                'ñ', 'n')
            )";

            $value_clean = str_replace(
                [
                    'á',
                    'à',
                    'ä',
                    'â',
                    'é',
                    'è',
                    'ë',
                    'ê',
                    'í',
                    'ì',
                    'ï',
                    'î',
                    'ó',
                    'ò',
                    'ö',
                    'ô',
                    'ú',
                    'ù',
                    'ü',
                    'û',
                    'ñ',
                    'Á',
                    'À',
                    'Ä',
                    'Â',
                    'É',
                    'È',
                    'Ë',
                    'Ê',
                    'Í',
                    'Ì',
                    'Ï',
                    'Î',
                    'Ó',
                    'Ò',
                    'Ö',
                    'Ô',
                    'Ú',
                    'Ù',
                    'Ü',
                    'Û',
                    'Ñ'
                ],
                [
                    'a',
                    'a',
                    'a',
                    'a',
                    'e',
                    'e',
                    'e',
                    'e',
                    'i',
                    'i',
                    'i',
                    'i',
                    'o',
                    'o',
                    'o',
                    'o',
                    'u',
                    'u',
                    'u',
                    'u',
                    'n',
                    'a',
                    'a',
                    'a',
                    'a',
                    'e',
                    'e',
                    'e',
                    'e',
                    'i',
                    'i',
                    'i',
                    'i',
                    'o',
                    'o',
                    'o',
                    'o',
                    'u',
                    'u',
                    'u',
                    'u',
                    'n'
                ],
                strtolower($value)
            );

            return [
                "{$column_clean} LIKE ?",
                $value_clean
            ];
        }
    }
}
