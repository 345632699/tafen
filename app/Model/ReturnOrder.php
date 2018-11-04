<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class ReturnOrder extends Model
{
  protected $table = "return_orders";

  protected $fillable = [
      'uid',
      'return_order_number',
      'order_header_id',
      'return_request_type',
      'return_order_status',
      'request_client_id',
      'request_date',
      'return_date',
      'return_reason_type',
      'return_reason',
      'evidence_pic1_url',
      'evidence_pic2_url',
      'evidence_pic3_url',
      'good_status',
      'update_time',
      'return_sum',
      'return_phone'
  ];

}
