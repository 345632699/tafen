<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Lesson extends Model
{
    protected $table = "lessons";

    protected $fillable = ['id', 'name', 'start_time', 'is_free', 'url', 'banner_bg', 'author', 'type', 'description'];

    protected $primaryKey = 'id';

}
