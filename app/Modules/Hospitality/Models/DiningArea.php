<?php

namespace App\Modules\Hospitality\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property bool $active
 * @property int $sort_order
 * @property-read Collection<int, DiningTable> $tables
 */
final class DiningArea extends Model
{
    protected $table = 'hospitality_areas';

    protected $guarded = [];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /** @return HasMany<DiningTable, $this> */
    public function tables(): HasMany
    {
        return $this->hasMany(DiningTable::class, 'area_id')
            ->orderBy('sort_order')
            ->orderBy('id');
    }
}
