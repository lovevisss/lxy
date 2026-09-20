<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RetreatRouteImport extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['errors' => 'array'];
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function routes(): HasMany
    {
        return $this->hasMany(RetreatRoute::class);
    }
}
