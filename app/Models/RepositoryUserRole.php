<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RepositoryUserRole extends Model
{
    protected $fillable = ['repository_id', 'user_id', 'role'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function repository()
    {
        return $this->belongsTo(Repository::class);
    }
}
