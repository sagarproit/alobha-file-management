<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class File extends Model
{
    protected $fillable = ['repository_id', 'name', 'latest_version_id'];

    /**
     * All versions of this file
     */
    public function versions(): HasMany
    {
        return $this->hasMany(FileVersion::class);
    }

    /**
     * Latest version of the file (manual link via latest_version_id)
     */
    public function latestVersion(): BelongsTo
    {
        return $this->belongsTo(FileVersion::class, 'latest_version_id');
    }

    /**
     * Related repository
     */
    public function repository()
    {
        return $this->belongsTo(Repository::class);
    }

}
