<?php

namespace App\Http\Controllers\Good;

use App\Model\Attribute;
use App\Model\Category;
use App\Model\Good;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Mockery\Exception;

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
}
