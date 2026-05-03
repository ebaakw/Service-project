<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServiceIntakeFieldResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'service_category_id' => $this->service_category_id,
            'service_problem_id' => $this->service_problem_id,
            'label_ar' => $this->label_ar,
            'label_en' => $this->label_en,
            'display_label' => $this->localized('label'),
            'placeholder_ar' => $this->placeholder_ar,
            'placeholder_en' => $this->placeholder_en,
            'display_placeholder' => $this->localized('placeholder'),
            'field_type' => $this->field_type?->value,
            'options' => $this->displayOptions(),
            'is_required' => (bool) $this->is_required,
            'sort_order' => (int) $this->sort_order,
            'is_active' => (bool) $this->is_active,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function displayOptions(): array
    {
        $opts = $this->options ?? [];
        if (! is_array($opts)) {
            return [];
        }

        $locale = app()->getLocale();
        $out = [];
        foreach ($opts as $opt) {
            if (! is_array($opt)) {
                continue;
            }
            $labelAr = $opt['label_ar'] ?? $opt['label'] ?? '';
            $labelEn = $opt['label_en'] ?? $opt['label'] ?? '';
            $display = $locale === 'en'
                ? (is_string($labelEn) && $labelEn !== '' ? $labelEn : $labelAr)
                : (is_string($labelAr) && $labelAr !== '' ? $labelAr : $labelEn);
            $out[] = array_merge($opt, [
                'display_label' => is_string($display) ? $display : '',
            ]);
        }

        return $out;
    }
}
