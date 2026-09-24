<?php

namespace App\Modules\Hospitality\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property int $price_cents
 * @property bool $active
 * @property int $sort_order
 * @property-read Collection<int, MenuGroup> $groups
 */
final class Menu extends Model
{
    protected $table = 'hospitality_menus';

    protected $guarded = [];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'price_cents' => 'integer',
            'active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /** @return HasMany<MenuGroup, $this> */
    public function groups(): HasMany
    {
        return $this->hasMany(MenuGroup::class, 'menu_id')
            ->orderBy('sort_order')
            ->orderBy('id');
    }
}
