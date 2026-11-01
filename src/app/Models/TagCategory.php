<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TagCategory extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'color',
        'description',
    ];

    protected $casts = [
        'color' => 'string',
    ];

    public function tags(): HasMany
    {
        return $this->hasMany(Tag::class, 'category_id');
    }
}
