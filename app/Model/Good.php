<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Good extends Model
{
    protected $fillable = ['uid',
        'name',
        'description',
        'discount_price',
        'original_price',
        'stock',
        'already_sold',
        'combos_id',
        'update_time',
        'category_id',
        'is_onsale',
        'is_new',
        'is_hot',
        'is_agent_type',
        'agent_type_id',
        'delivery_fee',
        'is_coupon',
        'thumbnail_img',
        'attribute_id',
        'sort',
        'banner_img'
    ];

    protected $primaryKey = 'uid';


}
