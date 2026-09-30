<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductActivityLog extends Model
{
    protected $fillable = [
        'admin_id',
        'admin_name',
        'entity_type',
        'entity_id',
        'entity_name',
        'action',
        'details',
    ];

    public static function log(string $action, string $entityType, ?int $entityId, string $entityName, ?string $details = null): self
    {
        $admin = auth()->user();
        $adminName = $admin ? ($admin->name ?? $admin->email ?? 'Admin') : 'System Admin';

        return self::create([
            'admin_id'    => auth()->id(),
            'admin_name'  => $adminName,
            'action'      => $action,
            'entity_type' => $entityType,
            'entity_id'   => $entityId,
            'entity_name' => $entityName,
            'details'     => $details,
        ]);
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class, 'admin_id');
    }
}
