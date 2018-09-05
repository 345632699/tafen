<?php

namespace App\Api\Controllers\Cart;

use App\Api\Controllers\BaseController;
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
        $Cart['attribute_name'] = \DB::table('attr_good_mapping')->where('id',$request->attr_good_mapping_id)->first()->name;
        $Cart['original_price'] = $good->original_price;
        $Cart['discount_price'] = $good->discount_price;
        $Cart['agent_price'] = $good->original_price * $rate / 100;
        $Cart['last_price'] = $good->is_coupon ? $Cart['discount_price'] : $Cart['agent_price'];
        $Cart['total_price'] = $Cart['last_price'] * $Cart['number'];
        $cart = Cart::where(['client_id'=>$client_id,'good_id'=>$request->good_id])->get()->first();
        if ($cart){
            $cart->update(['number'=>$cart->number+1]);
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
}
