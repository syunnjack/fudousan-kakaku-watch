<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AreaWatch extends Model
{
    protected $fillable = [
        'line_user_id',
        'prefecture_code',
        'prefecture_name',
        'last_avg_price_per_sqm',
        'last_checked_year',
        'last_checked_quarter',
        'last_checked_at',
    ];

    protected $casts = [
        'last_checked_at' => 'datetime',
    ];

    public function lineUser(): BelongsTo
    {
        return $this->belongsTo(LineUser::class);
    }
}
