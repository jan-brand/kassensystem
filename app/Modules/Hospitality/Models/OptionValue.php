<?php

namespace App\Modules\Hospitality\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $option_group_id
 * @property string $name
 * @property int $price_delta_cents
 * @property bool $active
 * @property int $sort_order
 * @property-read OptionGroup $group
 */
final class OptionValue extends Model
{
    protected $table = 'hospitality_option_values';

    protected $guarded = [];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'price_delta_cents' => 'integer',
            'active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /** @return BelongsTo<OptionGroup, $this> */
    public function group(): BelongsTo
    {
        return $this->belongsTo(OptionGroup::class, 'option_group_id');
    }
}
