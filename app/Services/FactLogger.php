<?php

namespace App\Services;

use App\Models\FactLog;
use Illuminate\Support\Facades\Auth;

class FactLogger
{
    /**
     * Canonical FactLog writer.
     *
     * @param string      $type     e.g. "event.created", "volunteer_import.previewed"
     * @param string|null $action   short label for UI filters e.g. "Create", "Edit", "Deleted"
     * @param mixed       $entity   Eloquent model OR string entity type
     * @param int|null    $entityId explicit entity id (optional)
     * @param mixed       $details  string OR array/object (stored in payload->summary/data)
     * @param int|null    $adminId  optional override
     */
    public function log(
        string $type,
        ?string $action = null,
        $entity = null,
        ?int $entityId = null,
        $details = null,
        ?int $adminId = null
    ): FactLog {
        $admin = Auth::guard('admin')->user();

        $resolvedAdminId = is_numeric($adminId)
            ? (int) $adminId
            : ($admin->admin_id ?? null);

        // Resolve entity type + id
        $entityType = 'Unknown';

        if (is_object($entity)) {
            $entityType = class_basename($entity);
            if ($entityId === null && method_exists($entity, 'getKey')) {
                $entityId = $entity->getKey();
            }
        } elseif (is_string($entity) && $entity !== '') {
            $entityType = $entity;
        }

        // Normalize details into summary + data
        $summary = null;
        $data    = null;

        if (is_array($details) || is_object($details)) {
            $arr = (array) $details;
            $summary = isset($arr['summary']) ? (string) $arr['summary'] : null;

            // If you pass ['summary'=>..., 'data'=>...], store only data in payload->data
            // Otherwise store the whole array/object
            $data = (is_array($details) && array_key_exists('data', $details))
                ? $details['data']
                : $details;
        } else {
            $summary = $details !== null ? (string) $details : null;
        }

        $payload = [
            'version' => 1,
            'type'    => $type,
            'summary' => $summary,
            'actor'   => [
                'admin_id'   => $resolvedAdminId,
                'username'   => $admin->username ?? null,
                'full_name'  => $admin->full_name ?? null, // ✅ FIX: AdminAccount uses full_name
            ],
            'entity'  => [
                'type' => $entityType,
                'id'   => $entityId,
            ],
            'action' => $action,
            'data'   => $data,
            'meta'   => [
                'ip' => request()->ip(),
                'ua' => substr((string) request()->userAgent(), 0, 255),
            ],
            'at' => now()->toIso8601String(),
        ];

        return FactLog::create([
            'admin_id'    => $resolvedAdminId,
            'entity_type' => $entityType,
            'entity_id'   => $entityId,
            'action'      => $action ?? $type,
            'details'     => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'timestamp'   => now(),
        ]);
    }
}
