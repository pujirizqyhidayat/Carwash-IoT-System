<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = 'audit_logs';

    protected $fillable = [
        'user_id',
        'action',
        'module',
        'description',
        'ip_address',
        'user_agent',
        'status',
        'metadata',
        'created_at',
    ];

    protected $appends = [
        'actor_username',
        'actor_role',
    ];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::creating(function (AuditLog $auditLog) {
            if (!$auditLog->user_id) {
                return;
            }

            $metadata = $auditLog->metadata ?? [];
            if (!empty($metadata['actor_username']) && !empty($metadata['actor_role'])) {
                return;
            }

            $user = User::find($auditLog->user_id);
            if (!$user) {
                return;
            }

            $auditLog->metadata = array_merge($metadata, [
                'actor_username' => $metadata['actor_username'] ?? $user->username,
                'actor_role' => $metadata['actor_role'] ?? $user->role,
            ]);
        });
    }

    public function getActorUsernameAttribute()
    {
        return $this->metadata['actor_username'] ?? $this->user?->username;
    }

    public function getActorRoleAttribute()
    {
        return $this->metadata['actor_role'] ?? $this->user?->role;
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
