<?php

namespace App\Http\Requests\Api\V1\Customer;

use App\Enums\FeedbackPriority;
use App\Enums\FeedbackTicketType;
use App\Models\Order;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCustomerFeedbackRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isCustomer() === true
            && $this->user()?->customerProfile !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', 'string', Rule::enum(FeedbackTicketType::class)],
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:10000'],
            'company_id' => ['nullable', 'integer', 'exists:companies,id'],
            'order_id' => ['nullable', 'integer', 'exists:orders,id'],
            'priority' => ['nullable', 'string', Rule::enum(FeedbackPriority::class)],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $orderId = $this->input('order_id');
            if ($orderId === null) {
                return;
            }

            $customer = $this->user()?->customerProfile;
            if ($customer === null) {
                return;
            }

            $order = Order::query()->find($orderId);
            if ($order === null) {
                return;
            }

            if ((int) $order->customer_id !== (int) $customer->getKey()) {
                $validator->errors()->add('order_id', 'The order does not belong to your account.');
            }

            $companyId = $this->input('company_id');
            if ($companyId !== null && (int) $order->company_id !== (int) $companyId) {
                $validator->errors()->add('company_id', 'Company must match the selected order.');
            }
        });
    }
}
