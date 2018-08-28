<?php
/**
 * Created by PhpStorm.
 * User: xu
 * Date: 2018/7/2
 * Time: 16:49
 */

namespace App\Repositories\Good;


use App\Client;
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
            //代理价格
            $client_id = session('client.id');
            if ($client_id) {
                $res = Client::select('discount_rate')->leftJoin('agent_type','agent_type.id','=','agent_type_id')
                                    ->where('clients.id',$client_id)->first();
                if(isset($res->discount_rate) && $goods->is_coupon <= 0){
                    $goods->agent_price = number_format($goods->original_unit_price * (100 - $res->discount_rate) / 100,2);
                }else{
                    $goods->agent_price = null;
                }
            }
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