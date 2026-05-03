<?php

namespace App\View\Components;

use Closure;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Lang;
use Illuminate\View\Component;
use Illuminate\View\View;

class Translations extends Component
{
    /**
     * Flatten lang files into keys prefixed for window._translations (tenant.*, dashboard.*).
     *
     * @return array<string, string>
     */
    public function flattenedStrings(): array
    {
        return array_merge(
            $this->flattenLangFile('tenant', 'tenant'),
            $this->flattenLangFile('dashboard', 'dashboard'),
        );
    }

    /**
     * @return array<string, string>
     */
    private function flattenLangFile(string $file, string $keyPrefix): array
    {
        $tree = Lang::get($file);
        if (! is_array($tree)) {
            return [];
        }

        $out = [];
        foreach (Arr::dot($tree) as $key => $value) {
            if (is_string($value)) {
                $out[$keyPrefix.'.'.$key] = $value;
            }
        }

        return $out;
    }

    public function render(): View|Closure|string
    {
        return view('components.translations', [
            'translations' => $this->flattenedStrings(),
        ]);
    }
}
