<?php

namespace App\Modules\Hospitality\Models;

use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Product;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property int $min_choices
 * @property int $max_choices
 * @property bool $active
 * @property int $sort_order
 * @property-read Collection<int, OptionValue> $values
 */
final class OptionGroup extends Model
{
    protected $table = 'hospitality_option_groups';

    protected $guarded = [];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'min_choices' => 'integer',
            'max_choices' => 'integer',
            'active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /** @return HasMany<OptionValue, $this> */
    public function values(): HasMany
    {
        return $this->hasMany(OptionValue::class, 'option_group_id')
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    /** @return BelongsToMany<Product, $this> */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(
            Product::class,
            'hospitality_option_group_products',
            'option_group_id',
            'product_id',
        );
    }

    /** @return BelongsToMany<Category, $this> */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(
            Category::class,
            'hospitality_option_group_categories',
            'option_group_id',
            'category_id',
        );
    }
}
