<?php

namespace App\Http\Resources\Api\V1;

use App\Enums\DiscountType;
use App\Enums\TechnicianAvailabilityStatus;
use App\Enums\TechnicianReviewStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompanyResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $offerText = null;
        $activeDiscountType = null;
        $activeDiscountValue = null;
        if ($this->relationLoaded('discounts')) {
            $discount = $this->discounts->first();
            if ($discount !== null) {
                $activeDiscountType = $discount->discount_type?->value;
                $activeDiscountValue = $discount->discount_value !== null
                    ? (string) $discount->discount_value
                    : null;
                $offerText = $discount->localized('name')
                    ?: $discount->localized('description')
                    ?: $this->fallbackOfferText($activeDiscountType, $activeDiscountValue);
            }
        }

        return [
            'id' => $this->id,
            'ulid' => $this->ulid,
            'name' => $this->name,
            'name_ar' => $this->name_ar,
            'name_en' => $this->name_en,
            'display_name' => $this->localized('name'),
            'slug' => $this->slug,
            'short_description_ar' => $this->short_description_ar,
            'short_description_en' => $this->short_description_en,
            'display_short_description' => $this->localized('short_description'),
            'description_ar' => $this->description_ar,
            'description_en' => $this->description_en,
            'display_description' => $this->localized('description'),
            'warranty_note_ar' => $this->warranty_note_ar,
            'warranty_note_en' => $this->warranty_note_en,
            'display_warranty_note' => $this->localized('warranty_note'),
            'status' => $this->status?->value,
            'status_label' => $this->status?->label(),
            'subscription_status' => $this->subscription_status?->value,
            'subscription_status_label' => $this->subscription_status?->label(),
            'is_featured' => (bool) $this->is_featured,
            'logo_path' => $this->logo_path,
            'logo_url' => $this->resolveLogoUrl(),
            'offer_text' => $offerText,
            'active_discount_type' => $activeDiscountType,
            'active_discount_value' => $activeDiscountValue,
            'rating_average' => $this->rating_average !== null ? (float) $this->rating_average : null,
            'rating_count' => isset($this->rating_count) ? (int) $this->rating_count : null,
            'availability_status' => $this->resolveAvailabilityStatus(),
            'default_inspection_fee' => $this->default_inspection_fee !== null
                ? (string) $this->default_inspection_fee
                : null,
        ];
    }

    private function resolveLogoUrl(): ?string
    {
        $logoPath = trim((string) $this->logo_path);

        if ($logoPath === '') {
            return null;
        }

        if (str_starts_with($logoPath, 'http://') || str_starts_with($logoPath, 'https://')) {
            return $logoPath;
        }

        $logoPath = ltrim($logoPath, '/');
        if (str_starts_with($logoPath, 'storage/')) {
            $logoPath = substr($logoPath, strlen('storage/'));
        }

        return asset('storage/'.$logoPath);
    }

    private function resolveAvailabilityStatus(): ?string
    {
        if (array_key_exists('has_available_technician', $this->resource->getAttributes())) {
            return (bool) $this->resource->getAttribute('has_available_technician')
                ? 'available'
                : 'unavailable';
        }

        if ($this->relationLoaded('technicians')) {
            $hasAvailable = $this->technicians->contains(function ($technician): bool {
                return (bool) $technician->is_active
                    && $technician->review_status === TechnicianReviewStatus::Approved
                    && $technician->availability_status === TechnicianAvailabilityStatus::Available;
            });

            return $hasAvailable ? 'available' : 'unavailable';
        }

        return null;
    }

    private function fallbackOfferText(?string $type, ?string $value): ?string
    {
        if ($type === null || $value === null || $value === '') {
            return null;
        }

        $numeric = rtrim(rtrim($value, '0'), '.');

        if ($type === DiscountType::Percentage->value) {
            return 'يوجد خصم '.$numeric.'%';
        }

        if ($type === DiscountType::FixedAmount->value) {
            return 'يوجد خصم '.$numeric.' د.ك';
        }

        return null;
    }
}
