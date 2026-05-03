<?php

namespace App\Models\Concerns;

use Illuminate\Support\Facades\App;

trait HasLocalizedFields
{
    /**
     * Resolve a localized value for a logical field base (e.g. "name" -> name_ar, name_en, name).
     */
    public function localized(string $baseField, ?string $locale = null): ?string
    {
        $locale = $locale ?? App::getLocale();
        $ar = $this->getAttribute("{$baseField}_ar");
        $en = $this->getAttribute("{$baseField}_en");
        $legacy = $this->getAttribute($baseField);

        if ($locale === 'en') {
            return $this->firstNonEmptyString($en, $ar, $legacy);
        }

        return $this->firstNonEmptyString($ar, $en, $legacy);
    }

    /**
     * @param  string|int|float|bool|null  ...$candidates
     */
    private function firstNonEmptyString(mixed ...$candidates): ?string
    {
        foreach ($candidates as $c) {
            if ($c === null) {
                continue;
            }
            if (is_string($c) && $c !== '') {
                return $c;
            }
        }

        return null;
    }
}
