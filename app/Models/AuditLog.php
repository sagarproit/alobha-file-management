<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AuditLog extends Model
{
    use HasFactory;

    protected $fillable = ['repository_id', 'user_id', 'action', 'details'];

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function repository() {
        return $this->belongsTo(Repository::class);
    }
}

