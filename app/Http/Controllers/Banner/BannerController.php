<?php

namespace App\Http\Controllers\Banner;

use App\Model\Banner;
use App\Model\Good;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Mockery\Exception;
const BANNER_TYPE_PATH = [
    'home', 'discount', 'lesson', 'spread'
];
class BannerController extends Controller
{
    public function upload(Request $request)
    {
        // 1 首页广告 2 优惠专区 3 首席芳疗
        $banner_type = $request->get('banner_type', 1);
        $file_path = '/images/banner/' . BANNER_TYPE_PATH[$banner_type - 1] . '/';
        $res = upload($request, $request->file()['file'], $file_path);
        return $res;
    }

    public function getGoodList()
    {
        $good_list = Good::select('uid', 'name')->get();
        return resJson($good_list);
    }

    public function create(Request $request)
    {
        try {
            $insert = $request->input();
            $res = \DB::table('banner_images')->insert($insert);
            if ($res) {
                return resJson($res, 1, '创建成功');
            } else {
                return resJson([], 0, 'fail');
            }
        } catch (Exception $e) {
            return resJson([], 0, $e->getMessage());
        }
    }

    public function getBannerType()
    {
        $banner_type_list = \DB::table('banner_type')->get();
        return resJson($banner_type_list);
    }

    public function getList()
    {
        $list = \DB::table('banner_images')
            ->select('banner_images.*', 'banner_type.name')
            ->leftJoin('banner_type', 'banner_type.id', '=', 'banner_images.banner_type_id')
            ->orderBy('banner_type_id')
            ->orderBy('sort')
            ->get();
        return resJson($list);
    }

    public function update(Request $request)
    {
        try {
            $banner = \DB::table('banner_images')->where('id', $request->id);
            $update['is_display'] = $request->get('is_display', 0);
            $update['sort'] = $request->get('sort', 1);

            $res = $banner->update($update);
            if ($res) {
                return resJson($banner->first());
            } else {
                return resJson([], 0, '数据没有变动');
            }
        } catch (Exception $e) {
            return resJson([], 0, $e->getMessage());
        }
    }

    public function deleteBanner(Request $request)
    {
        $res = \DB::table('banner_images')->where('id', $request->id)->delete();
        if ($res) {
            return resJson([], 1, '删除成功');
        }
    }
}
