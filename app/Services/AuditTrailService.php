<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AuditTrailService
{
    /** @param array<string, mixed>|null $oldValues @param array<string, mixed>|null $newValues */
    public function record(string $action, Model $auditable, ?array $oldValues = null, ?array $newValues = null, ?int $userId = null): AuditLog
    {
        return AuditLog::create([
            'user_id' => $userId ?? Auth::id(), 'action' => $action,
            'auditable_type' => $auditable->getMorphClass(), 'auditable_id' => $auditable->getKey(),
            'old_values' => $oldValues, 'new_values' => $newValues, 'ip_address' => Request::ip(),
        ]);
    }
}
