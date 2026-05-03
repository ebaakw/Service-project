<?php

namespace App\Http\Requests\Api\V1\Customer;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'technician_id' => ['required', 'integer', 'exists:technicians,id'],
            'customer_address_id' => ['required', 'integer', 'exists:customer_addresses,id'],
            'service_category_id' => ['required', 'integer', 'exists:service_categories,id'],
            'service_problem_id' => ['nullable', 'integer', 'exists:service_problems,id'],
            'custom_problem_text' => ['nullable', 'string', 'max:5000'],
            'customer_notes' => ['nullable', 'string', 'max:5000'],
            'intake_answers' => ['nullable', 'array'],
            'intake_answers.*.service_intake_field_id' => ['required_with:intake_answers', 'integer', 'exists:service_intake_fields,id'],
            'intake_answers.*.value' => ['nullable'],
        ];
    }
}
