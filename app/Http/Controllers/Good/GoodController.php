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
        $attributeList = Attribute::select('id', 'name', 'description')->get();
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
        $limit = $request->get('limit', 10);
        $good_list = Good::orderBy('uid', 'desc')->paginate($limit);
        return resJson($good_list);
    }

    public function detail(Request $request)
    {
        $good_id = $request->good_id;
        $good = Good::find($good_id);
        return resJson($good);
    }

    public function bannerImgList(Request $request)
    {
        $good_banner_list = \DB::table("good_banners")
            ->select('good_banners.*', 'goods.name as good_name')
            ->leftJoin("goods", 'goods.uid', '=', 'good_banners.good_id')
            ->where("good_id", $request->good_id)
            ->orderBy('order_by')
            ->get();
        return resJson($good_banner_list);
    }

    public function detailImgList(Request $request)
    {
        $detail_img_list = \DB::table("good_details")
            ->select('good_details.*', 'goods.name as good_name')
            ->leftJoin("goods", 'goods.uid', '=', 'good_details.good_id')
            ->orderBy('order_by')
            ->where("good_id", $request->good_id)->get();
        return resJson($detail_img_list);
    }

    public function attributeList(Request $request)
    {
        $good_attr_list = \DB::table("attr_good_mapping")
            ->select('attr_good_mapping.*', 'attributes.name as attr_name', 'attributes.description', 'goods.name as good_name')
            ->leftJoin('attributes', 'attr_good_mapping.attr_id', '=', 'attributes.id')
            ->leftJoin('goods', 'goods.uid', '=', 'attr_good_mapping.good_id')
            ->where("attr_good_mapping.good_id", $request->good_id)
            ->get();
        $attr_list = Attribute::select('id', 'name', 'description')->get();
        $result['good_attr_list'] = $good_attr_list;
        $result['attr_list'] = $attr_list;
        return resJson($result);
    }

    public function attrUpdate(Request $request)
    {
        $update = $this->array_remove($request->input(), 'id');
        $res = \DB::table('attr_good_mapping')
            ->where('id', $request->id)
            ->update($update);
        if ($res) {
            return resJson([], 1, '更新成功');
        } else {
            return resJson([], 0, '暂无更新');
        }
    }

    public function addAttr(Request $request)
    {
        $insert = $request->input();
        $insert['stock'] = 10000;
        $res = \DB::table('attr_good_mapping')
            ->insert($insert);
        if ($res) {
            return resJson([], 1, '创建成功');
        } else {
            return resJson([], 0, '创建失败');
        }
    }

    public function delAttr(Request $request)
    {
        $res = \DB::table('attr_good_mapping')
            ->where('id', $request->id)->delete();
        if ($res) {
            return resJson([], 1, '创建成功');
        } else {
            return resJson([], 0, '创建失败');
        }
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
        if ($request->good_type > 1) {
            $tabel_name = "good_details";
        } else {
            $tabel_name = "good_banners";
        }
        $id = $request->id;
        $update['url'] = $request->url;
        $update['order_by'] = $request->order_by;
        $update['description'] = $request->description;
        $res = \DB::table($tabel_name)->where('uid', $id)->update($update);

        if ($res) {
            return resJson([], 1, '更新成功');
        } else {
            return resJson([], 0, '暂无更新');
        }
    }

    public function update(Request $request)
    {
      $update = $this->array_remove($request->input(),'uid');
      $res = Good::find($request->uid)->update($update);
      if ($res) {
        return resJson([], 1, '更新成功');
      } else {
        return resJson([], 0, '暂无更新');
      }
    }

    public function deleteImg(Request $request){
      $id = $request->id;
      if ($request->good_type > 1) {
        $tabel_name = "good_details";
      } else {
        $tabel_name = "good_banners";
      }
      $res = \DB::table($tabel_name)->where('uid', $id)->delete();
      if ($res) {
        return resJson([], 1, '删除成功');
      } else {
        return resJson([], 0, '删除失败');
      }
    }

    public function addImg(Request $request){
      if ($request->good_type > 1) {
        $tabel_name = "good_details";
      } else {
        $tabel_name = "good_banners";
      }
      $input = $this->array_remove( $request->input(),"good_type");
      $res = \DB::table($tabel_name)->insert($input);
        $good = Good::find($request->good_id);
        if ($request->good_type <= 1 && $good->thumbnail_img == '') {
            $good->update([
                'thumbnail_img' => $request->url
            ]);
        }
      if ($res) {
        return resJson([], 1, '添加成功');
      } else {
        return resJson([], 0, '添加失败');
      }
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
