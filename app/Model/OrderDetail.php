<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class OrderDetail extends Model
{
    protected $table = "order_lines";

    protected $primaryKey = 'uid';

    protected $fillable = [
        'uid',
        'header_id',
        'good_id',
        'color',
        'combo_id',
        'robot_id',
        'quantity',
        'discount_price',
        'buyer_msg',
        'original_price',
        'attr_good_mapping_id',
        'last_price',
        'agent_price',
        'total_price',
        'shipping_status',
        'shipping_code',
        'shipping_time',
        'shipping_name',
        'address',
        'taking_time',
        'updated_at',
        'created_at'
    ];

}
