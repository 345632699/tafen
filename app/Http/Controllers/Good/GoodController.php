<?php

namespace App\Http\Controllers\Good;

use App\Model\Attribute;
use App\Model\Category;
use App\Model\Good;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Mockery\Exception;
const GOOD_IMG_PATH = [
    'banner', 'detail'
];
class GoodController extends Controller
{
    public function goodAttr()
    {
        $attributeList = Attribute::select('id', 'name')->get();
        $catList = Category::select('id', 'name')->get();
        $agentList = \DB::table('agent_type')->get();

        $attr['attr_list'] = $attributeList;
        $attr['cat_list'] = $catList;
        $attr['agent_list'] = $agentList;
        return resJson($attr);
    }

    public function create(Request $request)
    {
        try {
            $res = Good::create($request->input());
            if ($res) {
                return resJson($res->toArray(), 1, '创建成功');
            } else {
                return resJson([], 0, '创建失败');
            }
        } catch (Exception $e) {
            return resJson([], 0, '创建失败：' . $e->getMessage());
        }

    }

    public function goodList(Request $request)
    {
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 100);
        $good_list = Good::paginate(10);
        return resJson($good_list);
    }

    public function detail(Request $request)
    {
        $good_id = $request->good_id;
    }

    public function bannerImgList(Request $request)
    {
        $good_banner_list = \DB::table("good_banners")
            ->select('good_banners.*', 'goods.name')
            ->leftJoin("goods", 'goods.uid', '=', 'good_banners.good_id')
            ->where("good_id", $request->good_id)->get();
        return resJson($good_banner_list);
    }

    public function detailImgList(Request $request)
    {
        $detail_img_list = \DB::table("good_details")->where("good_id", $request->good_id)->get();
        return resJson($detail_img_list);
    }

    public function attributeList(Request $request)
    {
        $good_attr_list = \DB::table("attr_good_mapping")
            ->select('attr_good_mapping.*', 'attributes.name as attr_name', 'attributes.description')
            ->leftJoin('attributes', 'attr_good_mapping.attr_id', '=', 'attributes.id')
            ->where("attr_good_mapping.good_id", $request->good_id)
            ->get();
        $attr_list = Attribute::all();
        $result['good_attr_list'] = $good_attr_list;
        $result['attr_list'] = $attr_list;
        return resJson($result);
    }

    public function uploadImg(Request $request)
    {
        // 1 banner 2 detail
        $banner_type = $request->get('good_type', 1);
        $file_path = '/images/goods/' . GOOD_IMG_PATH[$banner_type - 1] . '/';
        $res = upload($request, $request->file()['file'], $file_path);
        return $res;
    }

    public function doUpdateImg(Request $request)
    {
        $good_id = $request->good_id;

    }

    public function update(Request $request)
    {
        $good_id = $request->good_id;

    }

    public function array_remove($arr, $key)
    {
        if (!array_key_exists($key, $arr)) {
            return $arr;
        }
        $keys = array_keys($arr);
        $index = array_search($key, $keys);
        if ($index !== FALSE) {
            array_splice($arr, $index, 1);
        }
        return $arr;
    }

}
