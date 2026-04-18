<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdminPermission extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'modules', 'is_super_admin'];

    protected $casts = [
        'modules'        => 'array',
        'is_super_admin' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function can(string $module): bool
    {
        if ($this->is_super_admin) return true;
        return (bool) ($this->modules[$module] ?? false);
    }
}
