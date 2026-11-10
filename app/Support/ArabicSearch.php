<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;

final class ArabicSearch
{
    /**
     * Characters removed from both the PHP needle and SQL column expression
     * (tashkeel / diacritics + tatweel).
     *
     * @var list<string>
     */
    private const STRIP_CHARS = [
        'ـ', // tatweel
        'ً', 'ٌ', 'ٍ', 'َ', 'ُ', 'ِ', 'ّ', 'ْ',
        'ٓ', 'ٔ', 'ٕ', 'ٖ', 'ٗ', '٘', 'ٙ', 'ٚ', 'ٛ', 'ٜ', 'ٝ', 'ٞ', 'ٟ',
        'ٰ',
    ];

    /**
     * Letter unifications applied after stripping diacritics.
     *
     * @var array<string, string>
     */
    private const LETTER_MAP = [
        'أ' => 'ا',
        'إ' => 'ا',
        'آ' => 'ا',
        'ٱ' => 'ا',
        'ٲ' => 'ا',
        'ٳ' => 'ا',
        'ة' => 'ه',
        'ى' => 'ي',
        'ؤ' => 'و',
        'ئ' => 'ي',
    ];

    public static function normalize(string $text): string
    {
        $text = trim($text);
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

        // Drop remaining Arabic combining marks not listed explicitly.
        $text = preg_replace('/\p{Mn}/u', '', $text) ?? $text;

        foreach (self::STRIP_CHARS as $char) {
            $text = str_replace($char, '', $text);
        }

        foreach (self::LETTER_MAP as $from => $to) {
            $text = str_replace($from, $to, $text);
        }

        return mb_strtolower($text, 'UTF-8');
    }

    /**
     * Apply a normalized substring (contains) filter across columns, OR'd together.
     * Empty / whitespace-only / diacritics-only terms are a no-op.
     *
     * @param  EloquentBuilder<*>|QueryBuilder  $builder
     * @param  list<string>  $columns
     */
    public static function applyContains(EloquentBuilder|QueryBuilder $builder, array $columns, string $term): void
    {
        $needle = self::normalize($term);
        if ($needle === '' || $columns === []) {
            return;
        }

        $like = '%'.$needle.'%';

        $builder->where(function (EloquentBuilder|QueryBuilder $query) use ($columns, $like): void {
            foreach (array_values($columns) as $index => $column) {
                $sql = self::columnExpression($column).' LIKE ?';
                if ($index === 0) {
                    $query->whereRaw($sql, [$like]);
                } else {
                    $query->orWhereRaw($sql, [$like]);
                }
            }
        });
    }

    /**
     * SQL expression that mirrors {@see normalize()} for a trusted column identifier.
     *
     * MySQL strips diacritics with one REGEXP_REPLACE, then applies a shallow REPLACE
     * chain for letter unification. SQLite skips the deep strip list (parser depth)
     * and only applies the letter map — enough for search variants under test.
     */
    public static function columnExpression(string $column): string
    {
        if (! preg_match('/^[A-Za-z_][A-Za-z0-9_]*(\.[A-Za-z_][A-Za-z0-9_]*)?$/', $column)) {
            throw new \InvalidArgumentException('Invalid search column: '.$column);
        }

        $expr = 'TRIM('.$column.')';
        $driver = DB::connection()->getDriverName();

        if ($driver === 'mysql') {
            // One REGEXP_REPLACE for diacritics/tatweel keeps nesting shallow.
            $expr = 'REGEXP_REPLACE('.$expr.', '.self::sqlString(self::stripPattern()).', \'\')';
        }
        // SQLite: letter-map REPLACE only (parser depth). Needle is already stripped in PHP.

        foreach (self::LETTER_MAP as $from => $to) {
            $expr = 'REPLACE('.$expr.', '.self::sqlString($from).', '.self::sqlString($to).')';
        }

        return 'LOWER('.$expr.')';
    }

    private static function stripPattern(): string
    {
        return '['.implode('', self::STRIP_CHARS).']';
    }

    private static function sqlString(string $value): string
    {
        return "'".str_replace("'", "''", $value)."'";
    }
}
