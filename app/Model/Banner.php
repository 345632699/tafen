<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
    protected $table = "banner_type";

    protected $fillable = [
        'id',
        'name',
        'description'
    ];

}
