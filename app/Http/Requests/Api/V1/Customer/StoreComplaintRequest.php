<?php

namespace App\Http\Requests\Api\V1\Customer;

use App\Enums\FeedbackPriority;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreComplaintRequest extends FormRequest
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
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:10000'],
            'order_id' => ['nullable', 'integer', 'exists:orders,id'],
            'company_id' => ['nullable', 'integer', 'exists:companies,id'],
            'priority' => ['nullable', Rule::enum(FeedbackPriority::class)],
        ];
    }
}
