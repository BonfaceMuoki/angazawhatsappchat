<?php

use App\Helpers\AuditLogger;
use Illuminate\Support\Facades\Auth;

function super_admin_audit(
    string $action,
    ?string $entityType = null,
    ?int $entityId = null,
    ?array $oldValues = null,
    ?array $newValues = null
): void {
    $user = Auth::guard('api')->user();
    AuditLogger::log(
        $user?->id,
        $action,
        $entityType,
        $entityId,
        $oldValues,
        $newValues
    );
}
