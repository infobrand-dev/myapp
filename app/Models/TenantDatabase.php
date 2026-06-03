<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Crypt;

class TenantDatabase extends Model
{
    protected $connection = 'central';

    protected $fillable = [
        'server_id',
        'key',
        'database_name',
        'connection_name',
        'username',
        'password',
        'status',
        'sslmode',
        'max_schemas',
        'current_schemas',
        'schema_prefix',
        'options',
    ];

    protected $casts = [
        'max_schemas' => 'integer',
        'current_schemas' => 'integer',
        'options' => 'array',
    ];

    public function server(): BelongsTo
    {
        return $this->belongsTo(TenantServer::class, 'server_id');
    }

    public function tenants(): HasMany
    {
        return $this->hasMany(Tenant::class, 'database_id');
    }

    public function topologies(): HasMany
    {
        return $this->hasMany(TenantTopology::class, 'tenant_database_id');
    }

    public function setPasswordAttribute(?string $value): void
    {
        $this->attributes['password'] = $value === null || $value === ''
            ? null
            : Crypt::encryptString($value);
    }

    public function decryptedPassword(): ?string
    {
        $password = $this->attributes['password'] ?? null;

        if ($password === null || $password === '') {
            return null;
        }

        return Crypt::decryptString($password);
    }
}
