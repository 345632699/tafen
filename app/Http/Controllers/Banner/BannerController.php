<?php

namespace App\Http\Controllers\Banner;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
const BANNER_TYPE_PATH = [
    'home', 'discount', 'lesson'
];
class BannerController extends Controller
{
    public function upload(Request $request)
    {
        // 1 首页广告 2 优惠专区 3 首席芳疗
        $banner_type = $request->get('banner_type', 1);
        $file_path = '/images/banner/' . BANNER_TYPE_PATH[$banner_type - 1] . '/';
        $res = upload($request, $request->file()['file'], $file_path);
        dd($res);
    }
}
