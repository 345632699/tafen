<?php
/**
 * Created by PhpStorm.
 * User: xu
 * Date: 2018/7/2
 * Time: 16:49
 */

namespace App\Repositories\Good;


use App\Client;
use App\Model\Attribute;
use App\Model\Good;
use App\Repositories\Client\ClientRepository;

class GoodRepository implements GoodRepositoryInterface
{
    public function __construct(ClientRepository $client)
    {
        $this->client = $client;
    }

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
//            $attrList = \DB::table('attr_good_mapping as agm')
//                ->select('agm.*','attr.name','attr.description')
//                ->leftJoin('attributes as attr','attr.id','=','agm.attr_id')
//                ->where('good_id',$good_id)
//                ->get();
//            $attrRes = [];
            //代理价格
            $client_id = session('client.id');
            if ($client_id) {
                $res = Client::select('discount_rate')->leftJoin('agent_type','agent_type.id','=','agent_type_id')
                                    ->where('clients.id',$client_id)->first();
                if(isset($res->discount_rate) && $goods->is_coupon <= 0){
                    $goods->agent_price = $goods->original_price * (100 - $res->discount_rate) / 100;
                }else{
                    $goods->agent_price = $goods->original_price * 0.9;
                }
            }
//            foreach ($attrList as $attr) {
//                $attrRes[$attr->attr_id]['attr_list'][] = $attr;
//                $attrRes[$attr->attr_id]['name'] = $attrRes[$attr->attr_id]['attr_list'][0]->name;
//            }
//            $goods->attr = array_values($attrRes);
            $rate = $this->client->getAgentRate($client_id);
            if ($goods->is_coupon){
                $goods->last_price = $goods->discount_price;
            }else{
                $goods->last_price = $rate * $goods->original_price / 100;
            }
            $attributes = Attribute::select('attributes.name as title', 'agm.*')->where('attributes.id', $goods->attribute_id)
                ->rightJoin('attr_good_mapping as agm', 'agm.attr_id', '=', 'attributes.id')
                ->where('agm.good_id', $goods->uid)
                ->get();
            foreach ($attributes as $item){
                $item->agent_price = $rate == 100 ? $item->original_price * 0.9 : $item->original_price * $rate / 100;
                if ($item->is_coupon){
                    $item->last_price = $item->discount_price;
                }else{
                    $item->last_price = $rate * $item->original_price / 100;
                }
            }
            $goods->attributes = [];
            if (isset($attributes[0])) {
                $goods->attributes = [
                    'name' => $attributes[0]->title,
                    'list' => $attributes
                ];
            }
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