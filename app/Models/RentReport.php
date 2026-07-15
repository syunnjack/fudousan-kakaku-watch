<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RentReport extends Model
{
    protected $fillable = [
        'prefecture_code',
        'prefecture_name',
        'city_name',
        'layout',
        'area_sqm',
        'rent_yen',
        'nickname',
        'comment',
        'ip_hash',
    ];
}
