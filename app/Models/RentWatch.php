<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RentWatch extends Model
{
    protected $fillable = [
        'line_user_id',
        'prefecture_code',
        'prefecture_name',
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
