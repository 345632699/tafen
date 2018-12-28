<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Openid extends Model
{
    protected $table = "open_id_list";

    protected $fillable = [
        'id',
        'open_id'
    ];

}
