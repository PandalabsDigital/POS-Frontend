<?php

namespace App\Services;

use App\Models\TaxAuditLog;
use Illuminate\Support\Facades\Auth;

class TaxAuditLogger
{
    /**
     * @param  array<string, mixed>|null  $before
     * @param  array<string, mixed>|null  $after
     */
    public function record(
        string $action,
        ?array $before = null,
        ?array $after = null,
        ?string $reason = null,
        ?string $entityType = null,
        ?int $entityId = null,
        ?string $countryCode = null,
    ): void {
        TaxAuditLog::query()->create([
            'user_id' => Auth::id(),
            'country_code' => $countryCode,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'before' => $before,
            'after' => $after,
            'reason' => $reason,
        ]);
    }
}
