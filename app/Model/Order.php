<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $table = "order_headers";

    protected $primaryKey = 'uid';

    protected $fillable = [
        'uid',
        'order_number',
        'order_type',
        'order_status',
        'client_id',
        'order_date',
        'pay_date',
        'pay_name',
        'pay_type',
        'completion_date',
        'return_date',
        'contract_id',
        'request_close_date',
        'open_invoice_flag',
        'shipping_fee',
        'expired_time',
        'updated_at',
        'created_at',
    ];

}
