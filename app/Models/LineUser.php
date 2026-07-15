<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LineUser extends Model
{
    protected $fillable = ['line_user_id', 'display_name'];

    public function areaWatches(): HasMany
    {
        return $this->hasMany(AreaWatch::class);
    }
}
