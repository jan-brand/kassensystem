<?php

namespace App\Modules\Sales\Models;

use Illuminate\Database\Eloquent\Model;

final class SaleNumberSequence extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected $primaryKey = 'year';

    public $incrementing = false;

    protected $keyType = 'int';

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'last_number' => 'integer',
        ];
    }
}
