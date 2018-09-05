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
        'attr_good_mapping_id',
        'original_price',
        'discount_price',
        'last_price',
        'agent_price',
        'total_price',
        'attribute_name',
        'shipping_fee',
        'memo'
    ];

    protected $primaryKey = 'id';


}
