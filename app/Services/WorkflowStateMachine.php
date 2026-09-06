<?php

namespace App\Services;

use App\Models\WorkflowInstance;
use DomainException;

class WorkflowStateMachine
{
    public const STATE_PENDING = 'pending';

    public const STATE_IN_PROGRESS = 'in_progress';

    public const STATE_APPROVED = 'approved';

    public const STATE_REJECTED = 'rejected';

    public const STATE_CANCELLED = 'cancelled';

    public function transition(WorkflowInstance $instance, string $toState): void
    {
        $allowedTransitions = [
            self::STATE_PENDING => [self::STATE_IN_PROGRESS, self::STATE_CANCELLED],
            self::STATE_IN_PROGRESS => [self::STATE_APPROVED, self::STATE_REJECTED, self::STATE_CANCELLED],
        ];

        if (! isset($allowedTransitions[$instance->status]) || ! in_array($toState, $allowedTransitions[$instance->status], true)) {
            throw new DomainException("Invalid state transition from {$instance->status} to {$toState}.");
        }

        $instance->forceFill([
            'status' => $toState,
            'completed_at' => in_array($toState, [self::STATE_APPROVED, self::STATE_REJECTED, self::STATE_CANCELLED], true) ? now() : null,
        ])->save();
    }
}
