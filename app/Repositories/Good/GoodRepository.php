<?php
/**
 * Created by PhpStorm.
 * User: xu
 * Date: 2018/7/2
 * Time: 16:49
 */

namespace App\Repositories\Good;


use App\Model\Good;

class GoodRepository implements GoodRepositoryInterface
{

    /**
     * @param $good_id
     * @return mixed
     * 获取商品详情
     */
    public function getGood($good_id)
    {
        try{
            $goods = Good::select("goods.*")
                ->where('goods.uid',$good_id)
                ->first();
            $attrList = \DB::table('attr_good_mapping as agm')
                ->select('agm.*','attr.name','attr.description')
                ->leftJoin('attributes as attr','attr.id','=','agm.attr_id')
                ->where('good_id',$good_id)
                ->get();
            $attrRes = [];
            foreach ($attrList as $attr) {
                $attrRes[$attr->attr_id]['attr_list'][] = $attr;
                $attrRes[$attr->attr_id]['name'] = $attrRes[$attr->attr_id]['attr_list'][0]->name;
            }
            $goods->attr = array_values($attrRes);
            $goods->detail_imgs = \DB::table('good_details')
                ->select("url")
                ->where('good_id',$good_id)
                ->orderBy('order_by')
                ->pluck('url');
            $goods->banner_imgs = \DB::table('good_banners')
                ->select("url")
                ->where('good_id',$good_id)
                ->orderBy('order_by')
                ->pluck('url');

            return response_format($goods);
        }catch (Exception $e){
            return response_format([],0,$e->getMessage());
        }
    }

    public function find($good_id)
    {
        return Good::findOrFail($good_id)->first();
    }
}