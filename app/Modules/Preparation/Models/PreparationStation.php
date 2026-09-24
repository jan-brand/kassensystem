<?php

namespace App\Modules\Preparation\Models;

use App\Modules\Catalog\Models\Product;
use App\Modules\Preparation\Enums\PreparationNotificationSound;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

/**
 * @property int $id
 * @property string $name
 * @property string $code
 * @property bool $active
 * @property int $sort_order
 * @property PreparationNotificationSound $notification_sound
 * @property-read Collection<int, Product> $products
 * @property-read Collection<int, PreparationTask> $tasks
 */
final class PreparationStation extends Model
{
    protected $table = 'preparation_stations';

    protected $guarded = [];

    protected static function booted(): void
    {
        self::deleting(static function (): void {
            throw new LogicException('Zubereitungsstationen werden nicht gelöscht, sondern deaktiviert.');
        });
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'sort_order' => 'integer',
            'notification_sound' => PreparationNotificationSound::class,
        ];
    }

    /** @return BelongsToMany<Product, $this> */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(
            Product::class,
            'preparation_station_products',
            'station_id',
            'product_id',
        )->withTimestamps();
    }

    /** @return HasMany<PreparationTask, $this> */
    public function tasks(): HasMany
    {
        return $this->hasMany(PreparationTask::class, 'station_id');
    }
}
