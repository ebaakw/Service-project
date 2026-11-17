<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderMessageResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_id' => $this->order_id,
            'type' => $this->type?->value,
            'type_label' => $this->type?->label(),
            'sender_role' => $this->sender_role?->value,
            'sender_role_label' => $this->sender_role?->label(),
            'sender' => $this->whenLoaded('sender', function () {
                if ($this->sender === null) {
                    return null;
                }

                return [
                    'id' => $this->sender->id,
                    'name' => $this->sender->name,
                    'display_name' => $this->sender->localized('name'),
                ];
            }),
            'actor_context' => $this->actor_context?->value,
            'actor_context_label' => $this->actor_context?->label(),
            'on_behalf_label' => $this->resource->onBehalfLabel(),
            'actor' => $this->whenLoaded('actor', function () {
                if ($this->actor === null) {
                    return null;
                }

                return [
                    'id' => $this->actor->id,
                    'name' => $this->actor->name,
                    'display_name' => $this->actor->localized('name'),
                ];
            }),
            'body' => $this->body,
            'proposed_scheduled_at' => $this->proposed_scheduled_at?->toIso8601String(),
            'proposed_duration_minutes' => $this->proposed_duration_minutes !== null
                ? (int) $this->proposed_duration_minutes
                : null,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
