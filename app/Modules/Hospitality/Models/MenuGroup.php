<?php

namespace App\Modules\Hospitality\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $menu_id
 * @property string $name
 * @property int $min_choices
 * @property int $max_choices
 * @property int $sort_order
 * @property-read Menu $menu
 * @property-read Collection<int, MenuGroupProduct> $products
 */
final class MenuGroup extends Model
{
    protected $table = 'hospitality_menu_groups';

    protected $guarded = [];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'min_choices' => 'integer',
            'max_choices' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    /** @return BelongsTo<Menu, $this> */
    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class, 'menu_id');
    }

    /** @return HasMany<MenuGroupProduct, $this> */
    public function products(): HasMany
    {
        return $this->hasMany(MenuGroupProduct::class, 'menu_group_id')
            ->orderBy('sort_order')
            ->orderBy('id');
    }
}
