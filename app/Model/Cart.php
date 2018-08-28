<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    protected $fillable = [
        'id',
        'client_id',
        'good_id',
        'number',
        'price',
        'attr_good_mapping_id',
        'original_price',
        'unit_price',
        'total_price',
        'memo'
    ];

    protected $primaryKey = 'id';


}
