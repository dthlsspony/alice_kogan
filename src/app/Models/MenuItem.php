<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class MenuItem extends Model
{
    protected $fillable = [
        'label',
        'url',
        'parent_id',
        'sort_order',
        'is_visible',
        'target',
    ];

    protected function casts(): array
    {
        return [
            'is_visible' => 'boolean',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }

    /**
     * Видимые корневые пункты с вложенными видимыми детьми.
     */
    public static function visibleTree(): Collection
    {
        return static::query()
            ->whereNull('parent_id')
            ->where('is_visible', true)
            ->with(['children' => fn ($q) => $q->where('is_visible', true)])
            ->orderBy('sort_order')
            ->get();
    }
}
