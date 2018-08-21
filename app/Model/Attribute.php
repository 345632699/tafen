<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Attribute extends Model
{
    protected $table = "attributes";

    protected $fillable = [
        'id',
        'name',
        'cat_id',
        'description'
    ];

}
