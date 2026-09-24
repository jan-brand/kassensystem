<?php

namespace App\Modules\Hospitality\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $year
 * @property int $last_number
 */
final class HospitalityOrderNumberSequence extends Model
{
    protected $table = 'hospitality_order_number_sequences';

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
