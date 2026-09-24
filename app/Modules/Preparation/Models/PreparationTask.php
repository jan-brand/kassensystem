<?php

namespace App\Modules\Preparation\Models;

use App\Modules\Catalog\Models\Product;
use App\Modules\Hospitality\Models\HospitalityOrder;
use App\Modules\Hospitality\Models\HospitalityOrderItem;
use App\Modules\Hospitality\Models\HospitalityOrderItemComponent;
use App\Modules\Identity\Models\User;
use App\Modules\Preparation\Enums\PreparationTaskStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use LogicException;

/**
 * @property int $id
 * @property int $station_id
 * @property int $hospitality_order_id
 * @property int $hospitality_order_item_id
 * @property int|null $hospitality_order_item_component_id
 * @property int|null $product_id
 * @property string $source_key
 * @property string $label_snapshot
 * @property string|null $component_label_snapshot
 * @property string|null $note_snapshot
 * @property int $quantity
 * @property PreparationTaskStatus $status
 * @property int|null $started_by_user_id
 * @property int|null $ready_by_user_id
 * @property Carbon|null $started_at
 * @property Carbon|null $ready_at
 */
final class PreparationTask extends Model
{
    protected $table = 'preparation_tasks';

    protected $guarded = [];

    protected static function booted(): void
    {
        self::updating(static function (PreparationTask $task): void {
            $immutable = [
                'station_id',
                'hospitality_order_id',
                'hospitality_order_item_id',
                'hospitality_order_item_component_id',
                'product_id',
                'source_key',
            ];

            foreach ($immutable as $attribute) {
                if ($task->isDirty($attribute)) {
                    throw new LogicException('Zubereitungsarbeit darf historisch nicht umgeschrieben werden.');
                }
            }

            $original = PreparationTaskStatus::from((string) $task->getRawOriginal('status'));

            if ($original === PreparationTaskStatus::Ready) {
                throw new LogicException('Fertige Zubereitungsarbeit ist unveränderlich.');
            }

            if ($original === PreparationTaskStatus::InPreparation) {
                foreach ([
                    'label_snapshot',
                    'component_label_snapshot',
                    'note_snapshot',
                    'quantity',
                    'started_by_user_id',
                    'started_at',
                ] as $attribute) {
                    if ($task->isDirty($attribute)) {
                        throw new LogicException('Laufende Zubereitungsarbeit darf historisch nicht umgeschrieben werden.');
                    }
                }
            }

            if ($original === PreparationTaskStatus::New) {
                if ($task->status === PreparationTaskStatus::New) {
                    $allowedSnapshotChanges = [
                        'label_snapshot',
                        'component_label_snapshot',
                        'note_snapshot',
                        'quantity',
                        'updated_at',
                    ];

                    foreach (array_keys($task->getDirty()) as $attribute) {
                        if (! in_array($attribute, $allowedSnapshotChanges, true)) {
                            throw new LogicException('Neue Zubereitungsarbeit darf nur mit der offenen Position synchronisiert werden.');
                        }
                    }

                    return;
                }

                if ($task->status !== PreparationTaskStatus::InPreparation) {
                    throw new LogicException('Zubereitung muss mit „In Zubereitung“ beginnen.');
                }

                if ($task->started_by_user_id === null || $task->started_at === null) {
                    throw new LogicException('Start der Zubereitung benötigt Benutzer und Zeitpunkt.');
                }

                return;
            }

            if ($task->status !== PreparationTaskStatus::Ready) {
                throw new LogicException('Laufende Zubereitung kann nur als fertig markiert werden.');
            }

            if ($task->ready_by_user_id === null || $task->ready_at === null) {
                throw new LogicException('Fertigmeldung benötigt Benutzer und Zeitpunkt.');
            }
        });

        self::deleting(static function (): void {
            throw new LogicException('Zubereitungsarbeit kann nicht manuell gelöscht werden.');
        });
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'status' => PreparationTaskStatus::class,
            'started_at' => 'datetime',
            'ready_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<PreparationStation, $this> */
    public function station(): BelongsTo
    {
        return $this->belongsTo(PreparationStation::class, 'station_id');
    }

    /** @return BelongsTo<HospitalityOrder, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(HospitalityOrder::class, 'hospitality_order_id');
    }

    /** @return BelongsTo<HospitalityOrderItem, $this> */
    public function item(): BelongsTo
    {
        return $this->belongsTo(HospitalityOrderItem::class, 'hospitality_order_item_id');
    }

    /** @return BelongsTo<HospitalityOrderItemComponent, $this> */
    public function component(): BelongsTo
    {
        return $this->belongsTo(HospitalityOrderItemComponent::class, 'hospitality_order_item_component_id');
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** @return BelongsTo<User, $this> */
    public function startedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'started_by_user_id');
    }

    /** @return BelongsTo<User, $this> */
    public function readyBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ready_by_user_id');
    }
}
