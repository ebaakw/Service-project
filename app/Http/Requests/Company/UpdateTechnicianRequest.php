<?php

namespace App\Http\Requests\Company;

use App\Enums\TechnicianReviewStatus;
use App\Models\Technician;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateTechnicianRequest extends FormRequest
{
    public function authorize(): bool
    {
        $technician = $this->route('technician');

        return $technician instanceof Technician
            && ($this->user()?->can('update', $technician) ?? false);
    }

    protected function prepareForValidation(): void
    {
        $merge = [];
        foreach (['email', 'phone', 'estimated_job_duration_minutes'] as $key) {
            if ($this->input($key) === '') {
                $merge[$key] = null;
            }
        }
        if ($merge !== []) {
            $this->merge($merge);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Technician $technician */
        $technician = $this->route('technician');
        $userId = $technician->user_id;

        $hasCivilIdDocument = $technician->civilIdMedia() !== null;

        return [
            'first_name_ar' => ['required', 'string', 'max:100'],
            'second_name_ar' => ['required', 'string', 'max:100'],
            'family_name_ar' => ['required', 'string', 'max:100'],
            'first_name_en' => ['nullable', 'string', 'max:100'],
            'second_name_en' => ['nullable', 'string', 'max:100'],
            'family_name_en' => ['nullable', 'string', 'max:100'],
            'civil_id_number' => [
                'required',
                'string',
                'regex:/^\d{12}$/',
                Rule::unique('technicians', 'civil_id_number')->ignore($technician->getKey()),
            ],
            'civil_id_document' => [
                Rule::requiredIf(! $hasCivilIdDocument),
                'nullable',
                'file',
                'mimes:jpg,jpeg,png,webp,pdf',
                'max:5120',
            ],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
            'display_name_ar' => ['nullable', 'string', 'max:255'],
            'display_name_en' => ['nullable', 'string', 'max:255'],
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'phone' => [
                'required',
                'string',
                'regex:/^\d{8}$/',
                Rule::unique('users', 'phone')->ignore($userId),
            ],
            'password' => ['nullable', 'string', 'confirmed', 'min:8'],
            'employee_code' => ['nullable', 'string', 'max:64'],
            'bio_ar' => ['nullable', 'string'],
            'bio_en' => ['nullable', 'string'],
            'job_title_ar' => ['nullable', 'string', 'max:255'],
            'job_title_en' => ['nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
            'is_accepting_orders' => ['sometimes', 'boolean'],
            'estimated_job_duration_minutes' => ['nullable', 'integer', 'min:1', 'max:10080'],
            'service_category_ids' => ['nullable', 'array'],
            'service_category_ids.*' => ['integer', Rule::exists('service_categories', 'id')->where('is_active', true)],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            /** @var Technician|null $technician */
            $technician = $this->route('technician');
            if (! $technician instanceof Technician) {
                return;
            }

            if ($technician->review_status === TechnicianReviewStatus::PendingDeletion) {
                $validator->errors()->add(
                    'review_status',
                    __('tenant.technicians.cannot_edit_while_pending_deletion'),
                );
            }
        });
    }
}
