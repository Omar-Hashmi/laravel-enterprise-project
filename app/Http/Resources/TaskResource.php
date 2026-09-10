<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status,
            'priority' => $this->priority,
            'due_at' => $this->due_at?->toISOString(),
            'sla_breached' => (bool) $this->sla_breached,
            'completed_at' => $this->completed_at?->toISOString(),
            'action_notes' => $this->action_notes,
            'metadata' => $this->metadata,
            'assignee' => $this->whenLoaded('user', fn () => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
            ]),
            'creator' => $this->whenLoaded('creator', fn () => [
                'id' => $this->creator->id,
                'name' => $this->creator->name,
                'email' => $this->creator->email,
            ]),
            'delegated_by' => $this->whenLoaded('delegatedBy', fn () => [
                'id' => $this->delegatedBy->id,
                'name' => $this->delegatedBy->name,
                'email' => $this->delegatedBy->email,
            ]),
            'delegated_to' => $this->whenLoaded('delegatedTo', fn () => [
                'id' => $this->delegatedTo->id,
                'name' => $this->delegatedTo->name,
                'email' => $this->delegatedTo->email,
            ]),
            'delegated_at' => $this->delegated_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
