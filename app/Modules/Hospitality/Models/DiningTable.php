<?php

namespace App\Modules\Hospitality\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property int $area_id
 * @property string $name
 * @property bool $active
 * @property int $sort_order
 * @property-read DiningArea $area
 * @property-read HospitalityOrder|null $openOrder
 */
final class DiningTable extends Model
{
    protected $table = 'hospitality_tables';

    protected $guarded = [];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /** @return BelongsTo<DiningArea, $this> */
    public function area(): BelongsTo
    {
        return $this->belongsTo(DiningArea::class, 'area_id');
    }

    /** @return HasMany<HospitalityOrder, $this> */
    public function orders(): HasMany
    {
        return $this->hasMany(HospitalityOrder::class, 'table_id');
    }

    /** @return HasOne<HospitalityOrder, $this> */
    public function openOrder(): HasOne
    {
        return $this->hasOne(HospitalityOrder::class, 'open_table_id');
    }
}
