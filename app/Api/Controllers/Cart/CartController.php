<?php

namespace App\Api\Controllers\Cart;

use App\Api\Controllers\BaseController;
use App\Client;
use App\Model\Attribute;
use App\Model\Cart;
use App\Model\Good;
use App\Repositories\Client\ClientRepository;
use Illuminate\Http\Request;
use Mockery\Exception;

class CartController extends BaseController
{
    public function __construct(ClientRepository $client)
    {
        $this->client = $client;
    }
    /**
     * @api {post} /cart/create 添加商品到购物车
     * @apiName CartCreate
     * @apiGroup Cart
     *
     * @apiHeader (Authorization) {String} authorization Authorization value.
     *
     * @apiParam {int} good_id 商品id
     * @apiParam {int} number 商品数量
     * @apiParam {int} shipping_fee 运费
     * @apiParam {int} attr_good_mapping_id 选中属性ID
     *
     * @apiSuccess {Array} data 返回的数据结构体
     * @apiSuccess {Number} status  1 执行成功 0 为执行失败
     * @apiSuccess {string} msg 执行信息提示
     * @apiSuccessExample Success-Response:
     *{
    "response": {
    "data": {
    "client_id": 16,
    "good_id": "1",
    "number": 1,
    "shipping_fee": "5",
    "attr_good_mapping_id": "2",
    "attribute_name": "200ml",
    "original_price": 39900,
    "discount_price": null,
    "agent_price": 7980,
    "last_price": 7980,
    "total_price": 7980,
    "updated_at": "2018-09-05 13:07:52",
    "created_at": "2018-09-05 13:07:52",
    "id": 10
    },
    "status": 1,
    "msg": "success"
    }
    }
     */
    public function addToCart(Request $request) {
        $client_id = $this->client->getUserByOpenId()->id;
        $rate = $this->client->getAgentRate($client_id);
        if (!$request->good_id){
            return response_format([],0,'至少选择一个商品添加到购物车');
        }
        if (!$request->attr_good_mapping_id){
            return response_format([],0,'未选择商品属性');
        }
        $good = Good::find($request->good_id);
        if (!$good){
            return response_format([],0,'商品不存在');
        }
        $Cart['client_id'] = $client_id;
        $Cart['good_id'] = $request->good_id;
        $Cart['number'] = $request->get('number',1);
        $Cart['shipping_fee'] = $request->get('shipping_fee',0);
        $Cart['attr_good_mapping_id'] = $request->attr_good_mapping_id;
        $attr_good_mapping_id = $request->get('attr_good_mapping_id', '');
        if ($attr_good_mapping_id) {
            $attr_mapping = DB::table('attr_good_mapping')->where('id', $attr_good_mapping_id)->first();
            $Cart['attribute_name'] = $attr_mapping->name;
            $Cart['original_price'] = $attr_mapping->original_price;
            $Cart['discount_price'] = $attr_mapping->discount_price;
        } else {
            $Cart['original_price'] = $good->original_price;
            $Cart['discount_price'] = $good->discount_price;
        }
        $Cart['agent_price'] = $Cart['original_price'] * $rate / 100;
        $Cart['last_price'] = $good->is_coupon ? $Cart['discount_price'] : $Cart['agent_price'];
        $Cart['total_price'] = $Cart['last_price'] * $Cart['number'];
        $cart = Cart::where(['client_id'=>$client_id,'good_id'=>$request->good_id])->get()->first();
        if ($cart){
            if ($Cart['number'] >= 5 || ($cart->number + $Cart['number']) >= 5) {
                $number = 5;
            } else {
                $number = $cart->number + $Cart['number'];
            }
            $cart->update([
                'number' => $number,
                'total_price' => $Cart['last_price'] * $number
            ]);
            return response_format($cart);
        }else{
            $res = Cart::create($Cart);
            if ($res){
                return response_format($res);
            }
        }
    }
    /**
     * @api {post} /cart/update 更新购物车某行的数量
     * @apiName CartUpdate
     * @apiGroup Cart
     *
     * @apiHeader (Authorization) {String} authorization Authorization value.
     *
     * @apiParam {int} good_id 商品id
     * @apiParam {int} number 商品数量
     * @apiParam {int} attr_good_mapping_id 选中属性ID
     *
     * @apiSuccess {Array} data 返回的数据结构体
     * @apiSuccess {Number} status  1 执行成功 0 为执行失败
     * @apiSuccess {string} msg 执行信息提示
     * @apiSuccessExample Success-Response:
     *{{
    "response": {
    "data": true,
    "status": 1,
    "msg": "success"
    }
    }
     */
    public function updateCart(Request $request){
        $number = $request->number;
        $cart_id = $request->cart_id;
        $attr_good_mapping_id = $request->attr_good_mapping_id;
        $cart = Cart::find($cart_id);
        if (!$cart){
            return response_format([],0,'购物车不存在');
        }
        if (isset($request->number)){
            if ($number >= 5) {
                $number = 5;
            }
            $update['number'] = $number;
            $update['total_price'] = $cart->last_price * $number;
        }
        if (isset($request->attr_good_mapping_id)){
            $update['attr_good_mapping_id'] = $attr_good_mapping_id;
        }
        if (count($update)){
            $res = $cart->update($update);
            if ($res){
                return response_format($res);
            }
        }else{
            return response_format([],0,'购物车无更新');
        }
    }

    /**
     * @api {post} /cart/delete 删除购物车的某一项
     * @apiName CartDelete
     * @apiGroup Cart
     *
     * @apiHeader (Authorization) {String} authorization Authorization value.
     *
     * @apiParam {int} cart_id 商品id
     * @apiParam {int} [all] 默认不传 值为 1 时删除全部
     *
     * @apiSuccess {Array} data 返回的数据结构体
     * @apiSuccess {Number} status  1 执行成功 0 为执行失败
     * @apiSuccess {string} msg 执行信息提示
     * @apiSuccessExample Success-Response:
     *{{
    "response": {
    "data": true,
    "status": 1,
    "msg": "success"
    }
    }
     */
    public function deleteCart(Request $request){
        try{
            if (isset($request->all)){
                $res = Cart::where('id','>',0)->delete();
                if ($res){
                    return response_format($res);
                }
            }
            $cart_id = $request->cart_id;
            $cart = Cart::find($cart_id);
            if (!$cart){
                return response_format([],0,'购物车不存在');
            }
            $res = Cart::destroy($cart_id);
            if ($res){
                return response_format($res);
            }
        }catch (Exception $e){
            return response_format([],0,'删除失败');
        }

    }

    /**
     * @api {post} /cart/cart_list  获取购物车列表
     * @apiName cart_list
     * @apiGroup Cart
     *
     * @apiHeader (Authorization) {String} authorization Authorization value.
     *
     * @apiParam {int} [limit] 每页显示数量
     * @apiParam {int} [page] 页码
     *
     * @apiSuccess {Array} data 返回的数据结构体
     * @apiSuccess {Number} status  1 执行成功 0 为执行失败
     * @apiSuccess {string} msg 执行信息提示
     * @apiSuccessExample Success-Response:
     *{
     * "response": {
     * "data": {
     * "current_page": 1,
     * "data": [
     * {
     * "id": 3,
     * "client_id": 16,
     * "good_id": 3,
     * "number": 1,
     * "attr_good_mapping_id": 2,
     * "original_price": 10000,
     * "discount_price": null,
     * "agent_price": 8000,
     * "last_price": 8000,
     * "total_price": 8000,
     * "attribute_name": "200ml",
     * "memo": null,
     * "shipping_fee": 0,
     * "updated_at": null,
     * "created_at": null,
     * "good_name": "单方精油",
     * "description": "测试用例",
     * "thumbnail_img": "https://dj.mqphp.com/images/good3.jpg"
     * }
     * ],
     * "from": 1,
     * "last_page": 1,
     * "next_page_url": null,
     * "path": "http://www.tafen.com/api/cart/cart_list",
     * "per_page": 10,
     * "prev_page_url": null,
     * "to": 1,
     * "total": 1
     * },
     * "status": 1,
     * "msg": "success"
     * }
     * }
     */
    public function cart_list(Request $request)
    {
        $limit = $request->get('limit', 10);
        $client_id = $this->client->getUserByOpenId()->id;
        $cart_list = Cart::select('carts.*', 'goods.name as good_name', 'goods.description', 'goods.thumbnail_img', 'goods.is_coupon')->leftJoin('goods', 'good_id', '=', 'uid')->where('client_id', $client_id)->paginate($limit);
        return response_format($cart_list);
    }

    /**
     * @api {post} /guessLike  猜你喜欢
     * @apiName guessLike
     * @apiGroup Cart
     *
     * @apiHeader (Authorization) {String} authorization Authorization value.
     *
     *
     * @apiSuccess {Array} data 返回的数据结构体
     * @apiSuccess {Number} status  1 执行成功 0 为执行失败
     * @apiSuccess {string} msg 执行信息提示
     * @apiSuccessExample Success-Response:
     *{
     * "response": {
     * "data": [
     * {
     * "uid": 2,
     * "name": "她芬优惠产品",
     * "description": "测试用例",
     * "discount_price": 80,
     * "original_price": 13100,
     * "stock": 300,
     * "already_sold": 12121,
     * "combos_id": 0,
     * "update_time": "2018-09-14 18:01:27",
     * "category_id": 2,
     * "is_onsale": 1,
     * "is_new": 0,
     * "is_hot": 0,
     * "is_agent_type": 0,
     * "agent_type_id": 0,
     * "delivery_fee": 5,
     * "is_coupon": 1,
     * "thumbnail_img": "https://dj.mqphp.com/images/good2.jpg",
     * "attribute_id": "2",
     * "agent_price": null,
     * "last_price": 80,
     * "attributes": {
     * "name": "规格",
     * "list": [
     * {
     * "title": "规格",
     * "id": 6,
     * "attr_id": 2,
     * "good_id": 2,
     * "name": "100",
     * "original_price": null,
     * "stock": null,
     * "discount_price": null,
     * "is_coupon": null,
     * "agent_price": 0,
     * "last_price": 0
     * },
     * {
     * "title": "规格",
     * "id": 7,
     * "attr_id": 2,
     * "good_id": 2,
     * "name": "100",
     * "original_price": null,
     * "stock": null,
     * "discount_price": null,
     * "is_coupon": null,
     * "agent_price": 0,
     * "last_price": 0
     * }
     * ]
     * }
     * },
     * {
     * "uid": 4,
     * "name": "单方精油",
     * "description": "测试用例",
     * "discount_price": null,
     * "original_price": 13800,
     * "stock": 48,
     * "already_sold": 332,
     * "combos_id": 0,
     * "update_time": "2018-09-14 18:01:30",
     * "category_id": 3,
     * "is_onsale": 1,
     * "is_new": 0,
     * "is_hot": 0,
     * "is_agent_type": 0,
     * "agent_type_id": 0,
     * "delivery_fee": 0,
     * "is_coupon": 0,
     * "thumbnail_img": "https://dj.mqphp.com/images/good4.jpg",
     * "attribute_id": "2",
     * "agent_price": 11040,
     * "last_price": 2760
     * },
     * {
     * "uid": 5,
     * "name": "她芬优惠产品1",
     * "description": "测试用例",
     * "discount_price": null,
     * "original_price": 13800,
     * "stock": 48,
     * "already_sold": 332,
     * "combos_id": 0,
     * "update_time": "2018-09-14 18:01:30",
     * "category_id": 2,
     * "is_onsale": 1,
     * "is_new": 0,
     * "is_hot": 0,
     * "is_agent_type": 0,
     * "agent_type_id": 0,
     * "delivery_fee": 0,
     * "is_coupon": 0,
     * "thumbnail_img": "https://dj.mqphp.com/images/good2.jpg",
     * "attribute_id": "2",
     * "agent_price": 11040,
     * "last_price": 2760
     * },
     * {
     * "uid": 13,
     * "name": "芳香辅材",
     * "description": "测试用例",
     * "discount_price": null,
     * "original_price": 13800,
     * "stock": 48,
     * "already_sold": 332,
     * "combos_id": 0,
     * "update_time": "2018-09-14 18:01:30",
     * "category_id": 6,
     * "is_onsale": 1,
     * "is_new": 0,
     * "is_hot": 0,
     * "is_agent_type": 0,
     * "agent_type_id": 0,
     * "delivery_fee": 0,
     * "is_coupon": 0,
     * "thumbnail_img": "https://dj.mqphp.com/images/good2.jpg",
     * "attribute_id": "2",
     * "agent_price": 11040,
     * "last_price": 2760
     * }
     * ],
     * "status": 1,
     * "msg": "success"
     * }
     * }
     */
    public function guessLike()
    {
        $all = Good::all()->toArray();
        $rand = array_rand($all, 4);
        $good_list = Good::whereIn('uid', $rand)->get();
        foreach ($good_list as $good) {
            //代理价格
            $client_id = session('client.id');
            if ($client_id) {
                $res = Client::select('discount_rate')->leftJoin('agent_type', 'agent_type.id', '=', 'agent_type_id')
                    ->where('clients.id', $client_id)->first();
                $rate = $this->client->getAgentRate($client_id);
                if (isset($res->discount_rate) && $good->is_coupon <= 0) {
                    $good->agent_price = ($good->original_price * (100 - $res->discount_rate) / 100);
                } else {
                    $good->agent_price = $good->original_price * 0.9;
                }
                if ($good->is_coupon) {
                    $good->last_price = $good->discount_price;
                } else {
                    $good->last_price = ($rate * $good->original_price / 100);
                }
                $attributes = Attribute::select('attributes.name as title', 'agm.*')->where('attributes.id', $good->attribute_id)
                    ->rightJoin('attr_good_mapping as agm', 'agm.attr_id', '=', 'attributes.id')
                    ->where('agm.good_id', $good->uid)
                    ->get();
                foreach ($attributes as $item) {
                    $item->agent_price = $rate == 100 ? $item->original_price * 0.9 : $item->original_price * $rate / 100;
                    if ($item->is_coupon) {
                        $item->last_price = $item->discount_price;
                    } else {
                        $item->last_price = ($rate * $item->original_price / 100);
                    }
                }
                if ($attributes->count()) {
                    $good->attributes = [
                        'name' => $attributes[0]->title,
                        'list' => $attributes
                    ];
                }
            }
        }
        return response_format($good_list);

    }
}
