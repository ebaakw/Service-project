<?php

namespace App\Http\Resources\Api\V1;

use App\Enums\OrderStatus;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderStatusHistoryResource extends JsonResource
{
    /**
     * Known system note keys (and legacy English strings stored in older rows).
     *
     * @var array<string, string>
     */
    private const NOTE_KEYS = [
        'inspection_fee_paid' => 'inspection_fee_paid',
        'technician_pre_selected' => 'technician_pre_selected',
        'awaiting_technician_assignment' => 'awaiting_technician_assignment',
        'final_invoice_paid' => 'final_invoice_paid',
        'technician_assigned_by_company' => 'technician_assigned_by_company',
        'force_completed' => 'force_completed',
        'final_invoice_sent' => 'final_invoice_sent',
        'Inspection fee paid' => 'inspection_fee_paid',
        'Technician pre-selected' => 'technician_pre_selected',
        'Awaiting technician assignment' => 'awaiting_technician_assignment',
        'Final invoice paid — work may begin' => 'final_invoice_paid',
        'Technician assigned by company' => 'technician_assigned_by_company',
        'Force completed' => 'force_completed',
        'Final invoice sent' => 'final_invoice_sent',
    ];

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $oldStatus = $this->old_status !== null
            ? OrderStatus::tryFrom((string) $this->old_status)
            : null;
        $newStatus = OrderStatus::tryFrom((string) $this->new_status);

        $oldLabel = $oldStatus?->label();
        $newLabel = $newStatus?->label() ?? (string) $this->new_status;

        return [
            'id' => $this->id,
            'old_status' => $this->old_status,
            'old_status_label' => $oldLabel,
            'new_status' => $this->new_status,
            'new_status_label' => $newLabel,
            'new_status_group' => $newStatus?->statusGroup(),
            'new_status_group_label' => $newStatus?->statusGroupLabel(),
            'title' => $this->title($oldLabel, $newLabel),
            'note' => $this->displayNote(),
            'changed_by_user_id' => $this->changed_by_user_id,
            'changed_by' => $this->changedByPayload(),
            'created_at' => $this->created_at?->toIso8601String(),
            'created_at_label' => $this->createdAtLabel(),
        ];
    }

    private function title(?string $oldLabel, string $newLabel): string
    {
        if ($this->old_status === null || $oldLabel === null) {
            return __('orders.history.title_created', ['status' => $newLabel]);
        }

        return __('orders.history.title_changed', [
            'from' => $oldLabel,
            'to' => $newLabel,
        ]);
    }

    private function displayNote(): ?string
    {
        $note = $this->note;
        if (! is_string($note) || trim($note) === '') {
            return null;
        }

        $key = self::NOTE_KEYS[$note] ?? null;
        if ($key !== null) {
            return __('orders.history.notes.'.$key);
        }

        return $note;
    }

    /**
     * @return array{id: int|null, name: string, role: string, role_label: string}
     */
    private function changedByPayload(): array
    {
        $user = $this->relationLoaded('changedBy') ? $this->changedBy : null;

        if (! $user instanceof User) {
            return [
                'id' => null,
                'name' => __('orders.history.system_actor'),
                'role' => 'system',
                'role_label' => __('orders.history.role.system'),
            ];
        }

        $role = 'company';
        if ($user->isTechnician()) {
            $role = 'technician';
        } elseif ($user->isCustomer()) {
            $role = 'customer';
        }

        return [
            'id' => (int) $user->getKey(),
            'name' => $user->localized('name') ?: (string) $user->name,
            'role' => $role,
            'role_label' => __('orders.history.role.'.$role),
        ];
    }

    private function createdAtLabel(): ?string
    {
        if ($this->created_at === null) {
            return null;
        }

        return $this->created_at
            ->timezone(config('app.timezone'))
            ->locale(app()->getLocale())
            ->translatedFormat('d M Y · h:i A');
    }
}
