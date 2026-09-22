<?php

namespace App\Modules\Sales\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $year
 * @property int $last_number
 */
final class SaleNumberSequence extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected $primaryKey = 'year';

    public $incrementing = false;

    protected $keyType = 'int';

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'last_number' => 'integer',
        ];
    }
}
