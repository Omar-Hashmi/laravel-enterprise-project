<?php

namespace App\Models;

use Database\Factories\TaskFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Task extends Model
{
    /** @use HasFactory<TaskFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'title',
        'description',
        'workflow_instance_id',
        'form_submission_id',
        'user_id',
        'creator_id',
        'delegated_by_id',
        'delegated_to_id',
        'delegated_at',
        'status',
        'priority',
        'due_at',
        'sla_breached',
        'completed_at',
        'action_notes',
        'metadata',
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'pending',
        'priority' => 'medium',
        'sla_breached' => false,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'due_at' => 'datetime',
            'delegated_at' => 'datetime',
            'completed_at' => 'datetime',
            'sla_breached' => 'boolean',
            'metadata' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Task $task): void {
            if (empty($task->uuid)) {
                $task->uuid = (string) Str::uuid();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->user();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function delegatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'delegated_by_id');
    }

    public function delegatedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'delegated_to_id');
    }

    public function workflowInstance(): BelongsTo
    {
        return $this->belongsTo(WorkflowInstance::class, 'workflow_instance_id');
    }

    public function formSubmission(): BelongsTo
    {
        return $this->belongsTo(FormSubmission::class, 'form_submission_id');
    }

    public function scopeAssignedTo(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId)->where('status', '!=', 'completed');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->whereIn('status', ['pending', 'in_progress']);
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', 'completed');
    }

    public function scopeDelegated(Builder $query): Builder
    {
        return $query->where(function (Builder $subQuery): void {
            $subQuery->whereNotNull('delegated_to_id')
                ->orWhere('status', 'delegated');
        });
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->where('status', '!=', 'completed')
            ->where(function (Builder $subQuery): void {
                $subQuery->where('sla_breached', true)
                    ->orWhere(function (Builder $nested): void {
                        $nested->whereNotNull('due_at')->where('due_at', '<', now());
                    });
            });
    }

    public function markComplete(?string $notes = null): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
            'action_notes' => $notes ?? $this->action_notes,
        ]);
    }

    public function delegateTo(int $newUserId, int $delegatedByUserId, ?string $notes = null): void
    {
        $this->update([
            'user_id' => $newUserId,
            'delegated_to_id' => $newUserId,
            'delegated_by_id' => $delegatedByUserId,
            'delegated_at' => now(),
            'status' => 'delegated',
            'action_notes' => $notes ?? $this->action_notes,
        ]);
    }

    public function checkSlaBreach(): bool
    {
        if ($this->status !== 'completed' && $this->due_at !== null && $this->due_at->isPast()) {
            if (! $this->sla_breached) {
                $this->update(['sla_breached' => true]);
            }

            return true;
        }

        return (bool) $this->sla_breached;
    }
}
