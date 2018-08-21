<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $table = "good_category";

    protected $fillable = [
        'id',
        'name',
        'cat_banner', // banner 图
        'index_display', //首页展示
        'cat_icon_img',
        'jump_url'
    ];

}
