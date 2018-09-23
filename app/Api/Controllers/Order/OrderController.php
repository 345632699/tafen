<?php

namespace App\Api\Controllers\Order;
use App\Api\Controllers\BaseController;
use App\Model\Cart;
use App\Repositories\Client\ClientRepository;
use App\Repositories\Order\OrderRepository;
use App\Repositories\Pay\PayRepository;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Mockery\Exception;


class OrderController extends BaseController
{

    private $client;
    private $pay;
    private $order;

    public function __construct(
        ClientRepository $client,
        PayRepository $pay,
        OrderRepository $order
    ){
        $this->client = $client;
        $this->pay = $pay;
        $this->order = $order;
    }

    public function index() {

    }

    /**
     * @api {get} /order/get 获取订单详情
     * @apiName OrderDetail
     * @apiGroup Order
     *
     * @apiHeader (Authorization) {String} authorization Authorization value.
     *
     * @apiParam {int} order_id 订单ID
     *
     * @apiSuccess {Array} data 返回的数据结构体
     * @apiSuccess {Number} status  1 执行成功 0 为执行失败
     * @apiSuccess {string} msg 执行信息提示
     * @apiSuccess {string} tips 具体参考获取订单列表参数接口文档
     *
     * @apiSuccessExample Success-Response:
     * {
    "response": {
    "data": {
    "uid": 7,
    "order_number": "15096990811536147816",
    "order_type": 0,
    "order_status": 0,
    "client_id": 16,
    "order_date": "2018-09-05 11:43:36",
    "pay_name": "微信支付",
    "pay_type": 1,
    "pay_date": null,
    "completion_date": null,
    "return_date": null,
    "request_close_date": null,
    "open_invoice_flag": "N",
    "shipping_fee": 5,
    "expired_time": "2018-09-05 12:13:36",
    "updated_at": "2018-09-05 11:43:36",
    "created_at": "2018-09-05 11:43:36",
    "order_id": 7,
    "good_list": [
    {
    "uid": 9,
    "header_id": 7,
    "good_id": 1,
    "attr_good_mapping_id": 2,
    "quantity": 2,
    "buyer_msg": "",
    "discount_price": null,
    "total_price": 32,
    "shipping_status": 0,
    "shipping_code": null,
    "shipping_time": null,
    "taking_time": null,
    "shipping_name": null,
    "address": "蔡诗茵 广东省深圳市盐田区盐田区有很多盐和田的一个区没有去过盐田区这是一个很长很长的地址长到要换行行行 13415398357",
    "updated_at": "2018-09-05 11:43:36",
    "created_at": "2018-09-05 11:43:36",
    "last_price": 16,
    "agent_price": 16,
    "original_price": 80,
    "good_name": "她芬精油",
    "thumbnail_img": "http://img5.imgtn.bdimg.com/it/u=77511056,783740313&fm=27&gp=0.jpg",
    "description": "测试用例",
    "attr_name": "规格",
    "attr_value": "200ml"
    },
    {
    "uid": 10,
    "header_id": 7,
    "good_id": 2,
    "attr_good_mapping_id": 3,
    "quantity": 3,
    "buyer_msg": "",
    "discount_price": null,
    "total_price": 240,
    "shipping_status": 0,
    "shipping_code": null,
    "shipping_time": null,
    "taking_time": null,
    "shipping_name": null,
    "address": "蔡诗茵 广东省深圳市盐田区盐田区有很多盐和田的一个区没有去过盐田区这是一个很长很长的地址长到要换行行行 13415398357",
    "updated_at": "2018-09-05 11:43:36",
    "created_at": "2018-09-05 11:43:36",
    "last_price": 80,
    "agent_price": 80,
    "original_price": 400,
    "good_name": "她芬优惠产品",
    "thumbnail_img": "http://img5.imgtn.bdimg.com/it/u=77511056,783740313&fm=27&gp=0.jpg",
    "description": "测试用例",
    "attr_name": "规格",
    "attr_value": "300ml"
    }
    ],
    "order_status_name": "未支付",
    "order_type_name": "微信支付"
    },
    "status": 1,
    "msg": "success"
    }
    }
     *
     */
    public function get(Request $request){
        $order_id = $request->order_id;
        if (!isset($order_id)){
            return response_format(['err_msg'=>"订单ID不能为空"]);
        }
        $data = $this->order->getOrderDetail($order_id);
        return response_format($data);
    }

    /**
     * @param $order_status
     * @return array
     * 订单状态，ORDER_STATUS：0-已下单，1-已支付，2-待发货，3-已发货，4-已完成，5-异常，6-申请退货，7-确认退货，8-已退货
     */
    /**
     * @api {get} /order/list 根据订单状态获取订单列表
     * @apiName OrderList
     * @apiGroup Order
     *
     * @apiHeader (Authorization) {String} authorization Authorization value.
     *
     * @apiParam {int} order_status 0-未支付，1-已支付，2-待发货，3-已发货，4-已完成，5-异常，6-申请退货，7-确认退货，8-已退货 9-已取消 -1 全部
     * @apiParam {int} limit 每页显示条数
     * @apiParam {int} page 页码
     *
     * @apiSuccess {int} order_id 订单ID
     * @apiSuccess {string} order_number 商品订单
     * @apiSuccess {int} order_type 0-预付款，1-货到付款
     * @apiSuccess {int} order_status ORDER_STATUS：0-已下单，1-已支付，2-待发货，3-已发货，4-已完成，5-异常，6-申请退货，7-确认退货，8-已退货 9-已取消
     * @apiSuccess {datetime} order_date 下单时间
     * @apiSuccess {int} pay_type 支付方式ID
     * @apiSuccess {string} pay_name 支付方式名称
     * @apiSuccess {datetime} pay_date 支付时间
     * @apiSuccess {datetime} completion_date 订单完成时间
     * @apiSuccess {datetime} return_date 退货时间
     * @apiSuccess {datetime} request_close_date 订单关闭日期
     * @apiSuccess {string} open_invoice_flag 是否开发票
     * @apiSuccess {int} shipping_fee 运费 单位为分
     * @apiSuccess {datetime} expired_time 订单失效时间
     * @apiSuccess {Array} good_list 订单包含的商品
     * @apiSuccess {int} good_id 商品id
     * @apiSuccess {string} good_name 商品名称
     * @apiSuccess {string} thumbnail_img 商品缩略图
     * @apiSuccess {string} description 商品描述
     * @apiSuccess {string} attr_name 规格属性名称
     * @apiSuccess {string} attr_value 选中的规格 属性值
     * @apiSuccess {int} total_price 商品总金额 单位为 分
     * @apiSuccess {int} discount_price 优惠商品价格 仅当为优惠产品时 字段不为空 单位为 分
     * @apiSuccess {int} last_price 最终结算 使用的价格 单位为 分
     * @apiSuccess {int} agent_price 一级代理的价格 单位为 分
     * @apiSuccess {int} original_price 原价 单位为 分
     * @apiSuccess {string} address 收件人地址信息 联系方式
     * @apiSuccess {int} quantity 数量
     * @apiSuccess {int} shipping_status 快递状态 0 为未发货 1 已发货
     * @apiSuccess {string} shipping_name 快递名称
     * @apiSuccess {string} shipping_code 快递单号
     * @apiSuccess {string} shipping_time 发货时间
     * @apiSuccess {string} taking_time 收货时间
     *
     * @apiSuccessExample Success-Response:
     * {
    "response": {
    "data": {
    "current_page": 1,
    "data": [
    {
    "uid": 9,
    "order_number": "15096990811536148127",
    "order_type": 0,
    "order_status": 0,
    "client_id": 16,
    "order_date": "2018-09-05 11:48:47",
    "pay_name": "微信支付",
    "pay_type": 1,
    "pay_date": null,
    "completion_date": null,
    "return_date": null,
    "request_close_date": null,
    "open_invoice_flag": "N",
    "shipping_fee": 5,
    "expired_time": "2018-09-05 12:18:47",
    "updated_at": "2018-09-05 11:48:47",
    "created_at": "2018-09-05 11:48:47",
    "order_id": 9,
    "good_list": [
    {
    "uid": 11,
    "header_id": 9,
    "good_id": 1,
    "attr_good_mapping_id": 2,
    "quantity": 1,
    "buyer_msg": "test",
    "discount_price": null,
    "total_price": 26,
    "shipping_status": 0,
    "shipping_code": null,
    "shipping_time": null,
    "taking_time": null,
    "shipping_name": null,
    "address": "蔡诗茵 广东省深圳市盐田区盐田区有很多盐和田的一个区没有去过盐田区这是一个很长很长的地址长到要换行行行 13415398357",
    "updated_at": "2018-09-05 11:48:47",
    "created_at": "2018-09-05 11:48:47",
    "last_price": 26,
    "agent_price": 26,
    "original_price": 131,
    "good_name": "她芬精油",
    "thumbnail_img": "http://img5.imgtn.bdimg.com/it/u=77511056,783740313&fm=27&gp=0.jpg",
    "description": "测试用例",
    "attr_name": "规格",
    "attr_value": "200ml"
    }
    ]
    },
    {
    "uid": 8,
    "order_number": "15096990811536148099",
    "order_type": 0,
    "order_status": 0,
    "client_id": 16,
    "order_date": "2018-09-05 11:48:19",
    "pay_name": "微信支付",
    "pay_type": 1,
    "pay_date": null,
    "completion_date": null,
    "return_date": null,
    "request_close_date": null,
    "open_invoice_flag": "N",
    "shipping_fee": 5,
    "expired_time": "2018-09-05 12:18:19",
    "updated_at": "2018-09-05 11:48:19",
    "created_at": "2018-09-05 11:48:19",
    "order_id": 8,
    "good_list": []
    }
    ],
    "from": 1,
    "last_page": 5,
    "next_page_url": "http://www.tafen.com/api/order/list?page=2",
    "path": "http://www.tafen.com/api/order/list",
    "per_page": "2",
    "prev_page_url": null,
    "to": 2,
    "total": 9
    },
    "status": 1,
    "msg": "success"
    }
    }
     */
    /**
     * @api {get} /order/search 根据订单号查询订单
     * @apiName OrderSearch 订单查询
     * @apiGroup Order
     *
     * @apiHeader (Authorization) {String} authorization Authorization value.
     *
     * @apiParam {int} keyword 订单号
     * @apiParam {int} limit 每页显示条数
     * @apiParam {int} page 页码
     *
     * @apiSuccess {int} order_id 订单ID
     * @apiSuccess {string} order_number 商品订单
     * @apiSuccess {int} order_type 0-预付款，1-货到付款
     * @apiSuccess {int} order_status ORDER_STATUS：0-已下单，1-已支付，2-待发货，3-已发货，4-已完成，5-异常，6-申请退货，7-确认退货，8-已退货 9-已取消
     * @apiSuccess {datetime} order_date 下单时间
     * @apiSuccess {int} pay_type 支付方式ID
     * @apiSuccess {string} pay_name 支付方式名称
     * @apiSuccess {datetime} pay_date 支付时间
     * @apiSuccess {datetime} completion_date 订单完成时间
     * @apiSuccess {datetime} return_date 退货时间
     * @apiSuccess {datetime} request_close_date 订单关闭日期
     * @apiSuccess {string} open_invoice_flag 是否开发票
     * @apiSuccess {int} shipping_fee 运费 单位为分
     * @apiSuccess {datetime} expired_time 订单失效时间
     * @apiSuccess {Array} good_list 订单包含的商品
     * @apiSuccess {int} good_id 商品id
     * @apiSuccess {string} good_name 商品名称
     * @apiSuccess {string} thumbnail_img 商品缩略图
     * @apiSuccess {string} description 商品描述
     * @apiSuccess {string} attr_name 规格属性名称
     * @apiSuccess {string} attr_value 选中的规格 属性值
     * @apiSuccess {int} total_price 商品总金额 单位为 分
     * @apiSuccess {int} discount_price 优惠商品价格 仅当为优惠产品时 字段不为空 单位为 分
     * @apiSuccess {int} last_price 最终结算 使用的价格 单位为 分
     * @apiSuccess {int} agent_price 一级代理的价格 单位为 分
     * @apiSuccess {int} original_price 原价 单位为 分
     * @apiSuccess {string} address 收件人地址信息 联系方式
     * @apiSuccess {int} quantity 数量
     * @apiSuccess {int} shipping_status 快递状态 0 为未发货 1 已发货
     * @apiSuccess {string} shipping_name 快递名称
     * @apiSuccess {string} shipping_code 快递单号
     * @apiSuccess {string} shipping_time 发货时间
     * @apiSuccess {string} taking_time 收货时间
     *
     * @apiSuccessExample Success-Response:
     * {
     * "response": {
     * "data": {
     * "current_page": 1,
     * "data": [
     * {
     * "uid": 9,
     * "order_number": "15096990811536148127",
     * "order_type": 0,
     * "order_status": 0,
     * "client_id": 16,
     * "order_date": "2018-09-05 11:48:47",
     * "pay_name": "微信支付",
     * "pay_type": 1,
     * "pay_date": null,
     * "completion_date": null,
     * "return_date": null,
     * "request_close_date": null,
     * "open_invoice_flag": "N",
     * "shipping_fee": 5,
     * "expired_time": "2018-09-05 12:18:47",
     * "updated_at": "2018-09-05 11:48:47",
     * "created_at": "2018-09-05 11:48:47",
     * "order_id": 9,
     * "good_list": [
     * {
     * "uid": 11,
     * "header_id": 9,
     * "good_id": 1,
     * "attr_good_mapping_id": 2,
     * "quantity": 1,
     * "buyer_msg": "test",
     * "discount_price": null,
     * "total_price": 26,
     * "shipping_status": 0,
     * "shipping_code": null,
     * "shipping_time": null,
     * "taking_time": null,
     * "shipping_name": null,
     * "address": "蔡诗茵 广东省深圳市盐田区盐田区有很多盐和田的一个区没有去过盐田区这是一个很长很长的地址长到要换行行行 13415398357",
     * "updated_at": "2018-09-05 11:48:47",
     * "created_at": "2018-09-05 11:48:47",
     * "last_price": 26,
     * "agent_price": 26,
     * "original_price": 131,
     * "good_name": "她芬精油",
     * "thumbnail_img": "http://img5.imgtn.bdimg.com/it/u=77511056,783740313&fm=27&gp=0.jpg",
     * "description": "测试用例",
     * "attr_name": "规格",
     * "attr_value": "200ml"
     * }
     * ]
     * },
     * {
     * "uid": 8,
     * "order_number": "15096990811536148099",
     * "order_type": 0,
     * "order_status": 0,
     * "client_id": 16,
     * "order_date": "2018-09-05 11:48:19",
     * "pay_name": "微信支付",
     * "pay_type": 1,
     * "pay_date": null,
     * "completion_date": null,
     * "return_date": null,
     * "request_close_date": null,
     * "open_invoice_flag": "N",
     * "shipping_fee": 5,
     * "expired_time": "2018-09-05 12:18:19",
     * "updated_at": "2018-09-05 11:48:19",
     * "created_at": "2018-09-05 11:48:19",
     * "order_id": 8,
     * "good_list": []
     * }
     * ],
     * "from": 1,
     * "last_page": 5,
     * "next_page_url": "http://www.tafen.com/api/order/list?page=2",
     * "path": "http://www.tafen.com/api/order/list",
     * "per_page": "2",
     * "prev_page_url": null,
     * "to": 2,
     * "total": 9
     * },
     * "status": 1,
     * "msg": "success"
     * }
     * }
     */
    public function getOrderList(Request $request)
    {
        $order_status = $request->get('order_status',-1);
        $limit = $request->limit;
        $keyword = $request->get('keyword','');
        $order_list = $this->order->getOrderList($order_status,$keyword,$limit);
        return response_format($order_list);
    }

    /**
     * @api {post} /order/create 生成商品购买订单
     * @apiName OrderCreate
     * @apiGroup Order
     *
     * @apiHeader (Authorization) {String} authorization Authorization value.
     *
     * @apiParam {int} address_id 地址ID
     * @apiParam {string} [open_invoice_flag] 是否开具发票 Y 开 N否 (预留字段)
     * @apiParam {int} good_id 商品id
     * @apiParam {int} attr_good_mapping 商品属性ID 规格选项中 子选项的ID
     * @apiParam {int} quantity 商品数量
     * @apiParam {string} buyer_msg 买家留言
     * @apiParam {float} shipping_fee 运费
     *
     * @apiParam {int} invoice_type 发票类型 0-个人，1-公司 open_invoice_flag为Y时必填 (预留字段)
     * @apiParam {string} detail 发票明细（选填）(预留字段)
     * @apiParam {string} email 收票人邮箱（选填）(预留字段)
     * @apiParam {int} phone_num 收票人电话（必填）(预留字段)
     * @apiParam {string} title 发票抬头 open_invoice_flag为Y时必填 (预留字段)
     * @apiParam {string} tax_code 发票税号 invoice_type为1时必填 (预留字段)
     *
     * @apiParam {int} parent_id 推广人
     *
     * @apiSuccess {Array} data 返回的数据结构体
     * @apiSuccess {Number} status  1 执行成功 0 为执行失败
     * @apiSuccess {string} msg 执行信息提示
     * @apiSuccessExample Success-Response:
     *{
    "response": {
    "data": {
    "client_id": 16,
    "order_number": "15096990811536148127",
    "order_date": {
    "date": "2018-09-05 11:48:47.096844",
    "timezone_type": 3,
    "timezone": "UTC"
    },
    "pay_name": "微信支付",
    "pay_type": "1",
    "expired_time": {
    "date": "2018-09-05 12:18:47.097000",
    "timezone_type": 3,
    "timezone": "UTC"
    },
    "shipping_fee": "5",
    "open_invoice_flag": "N",
    "updated_at": "2018-09-05 11:48:47",
    "created_at": "2018-09-05 11:48:47",
    "uid": 9
    },
    "status": 1,
    "msg": "success"
    }
    }
 */
    public function create(Request $request){
        $client = $this->client->getUserByOpenId();
        $client_id = $client->id;
        $parent_id = $request->parent_id;
        //判断是否存在订单 存在则不重新新建
        if ($request->order_header_id > 0){
            $payJssdk = $this->getPayJssdk($request->order_header_id,$client,$parent_id);
            return response_format($payJssdk);
        }

        if (is_null($request->address_id)){
            return response_format([],0,'请选择地址');
        }
        //k可以加事務
        try{
            //添加order头
            $order_header = $this->order->createOrderHeader($request,$client_id);
            $order_header_id = $order_header->uid;
            if ($order_header_id) {
                //添加order详情
                $order_line = $this->order->createOrderLine($order_header->uid,$request,$parent_id);

                //添加发货记录
//                $delivery = $this->order->createDelivery($order_header->uid,$request->get('address_id'));

                //生成发票信息 如果有
//                $has_invoice = $request->get('open_invoice_flag','N');
//                if ($has_invoice == 'Y'){
//                    $this->order->createInvoice($client_id,$order_header_id,$order_line->total_price,$request);
//                }
                //生成微信支付订单 并 返回支付相关的JS配置
//                if ($order_line && $delivery){
//                    $payJssdk = $this->getPayJssdk($order_header_id,$client,$parent_id);
//                    return response_format($payJssdk);
//                }
//                $payJssdk = $this->getPayJssdk($order_header_id,$client,$parent_id);
//                return response_format($payJssdk);
                if ($order_line->uid){
                    return response_format($order_header);
                }
            }
        }catch (Exception $e){
            return response_format([],0,$e->getMessage());
        }
    }

    /**
     * @api {post} /order/cart 购物车商品下单支付
     * @apiName OrderCreateFromCart
     * @apiGroup Order
     *
     * @apiHeader (Authorization) {String} authorization Authorization value.
     *
     * @apiParam {int} address_id 地址ID
     * @apiParam {int} quantity 商品数量
     * @apiParam {float} shipping_fee 运费
     * @apiParam {string} cart_ids 购物车列表选中ID 用英文,号隔开 示例: (1,2,3)
     *
     * @apiParam {int} parent_id 推广人
     *
     * @apiSuccess {Array} data 返回的数据结构体
     * @apiSuccess {Number} status  1 执行成功 0 为执行失败
     * @apiSuccess {string} msg 执行信息提示
     * @apiSuccessExample Success-Response:
     *{
    "response": {
    "data": {
    "client_id": 16,
    "order_number": "15096990811536148127",
    "order_date": {
    "date": "2018-09-05 11:48:47.096844",
    "timezone_type": 3,
    "timezone": "UTC"
    },
    "pay_name": "微信支付",
    "pay_type": "1",
    "expired_time": {
    "date": "2018-09-05 12:18:47.097000",
    "timezone_type": 3,
    "timezone": "UTC"
    },
    "shipping_fee": "5",
    "open_invoice_flag": "N",
    "updated_at": "2018-09-05 11:48:47",
    "created_at": "2018-09-05 11:48:47",
    "uid": 9
    },
    "status": 1,
    "msg": "success"
    }
    }
     */
    public function createFromCart(Request $request){
        try{
            $client_id = session('client.id');
            $cart_ids = explode(',',$request->cart_ids);
            $cart_list = Cart::whereIn('id',$cart_ids)->get();
            if (!$request->address_id){
                return response_format([],0,'至少选择一个收获地址');
            }
            if (count($cart_list) == 0){
                return response_format([],0,'购物车为空或没有选择商品');
            }
            $order_header = $this->order->createOrderHeader($request,$client_id);
            $order_header_id = $order_header->uid;
            if ($order_header_id) {
                $address_id = $request->address_id;
                $success = 0;
                foreach ($cart_list as $cart){
                    $res = $this->order->createOrderLineFromCart($order_header_id,$cart,$address_id);
                    if ($res->uid){
                        $success += 1;
                    }
                }
                if ($success == count($cart_list)){
                    Cart::destroy($cart_ids);
                }
                return response_format($order_header);
            }else{
                return response_format([],0,'订单生成出错');
            }
        }catch (Exception $e){
            return response_format($e,0,'订单生成出错');
        }
    }

    /**
     * @api {post} /order/wxpaysdk 获取微信支付相应的JSSDK配置信息
     * @apiName getWxPayConfig
     * @apiGroup Order
     *
     * @apiHeader (Authorization) {String} authorization Authorization value.
     *
     * @apiParam {int} order_id 订单ID
     * @apiParam {int} parent_id 推广人
     *
     * @apiSuccess {Array} data 返回的数据结构体
     * @apiSuccess {Number} status  1 执行成功 0 为执行失败
     * @apiSuccess {string} msg 执行信息提示
     * @apiSuccessExample Success-Response:
     *{
    "response": {
    "data": {
    "client_id": 16,
    "order_number": "15096990811536148127",
    "order_date": {
    "date": "2018-09-05 11:48:47.096844",
    "timezone_type": 3,
    "timezone": "UTC"
    },
    "pay_name": "微信支付",
    "pay_type": "1",
    "expired_time": {
    "date": "2018-09-05 12:18:47.097000",
    "timezone_type": 3,
    "timezone": "UTC"
    },
    "shipping_fee": "5",
    "open_invoice_flag": "N",
    "updated_at": "2018-09-05 11:48:47",
    "created_at": "2018-09-05 11:48:47",
    "uid": 9
    },
    "status": 1,
    "msg": "success"
    }
    }
     */
    public function getWxPayConfig(Request $request){
        $client = $this->client->getUserByOpenId();
        $order_header_id = $request->order_id;
        $parent_id = $request->parent_id;
        $payJssdk = $this->getPayJssdk($order_header_id,$client,$parent_id);
        return response_format($payJssdk);
    }

    /**
     * @api {post} /order/confirm 确认收货
     * @apiName OrderConfirm
     * @apiGroup Order
     *
     * @apiHeader (Authorization) {String} authorization Authorization value.
     *
     * @apiParam {int} order_id 订单ID
     *
     * @apiSuccess {Array} data 返回的数据结构体
     * @apiSuccess {Number} status  1 执行成功 0 为执行失败
     * @apiSuccess {string} msg 执行信息提示
     *
     *
     */
    public function confirmReceipt(Request $request){
        $order_id = intval($request->order_id);
        $client_id = session('client.id');
        $res = $this->order->confirm($order_id,$client_id);
        return response_format([],$res['status'],$res['msg'],$res['statusCode']);
    }

    /**
     * @api {post} /order/cancel 取消订单
     * @apiName OrderCancel
     * @apiGroup Order
     *
     * @apiHeader (Authorization) {String} authorization Authorization value.
     *
     * @apiParam {int} order_id 订单ID
     *
     * @apiSuccess {Array} data 返回的数据结构体
     * @apiSuccess {Number} status  1 执行成功 0 为执行失败
     * @apiSuccess {string} msg 执行信息提示
     *
     * @apiSuccessExample Success-Response:
     * {
    "response": {
    "data": [],
    "status": 1,
    "msg": "success"
    }
    }
     */
    public function cancelOrder(Request $request){
        $order_id = intval($request->order_id);
        $client_id = session('client.id');
        $res = $this->order->cancel($order_id,$client_id);
        return response_format([],$res['status'],$res['msg'],$res['statusCode']);
    }

    public function getPayJssdk($order_header_id,$client,$parent_id){
        $resultArr = [];
        $pay = $this->pay->createPayBillByOrder($order_header_id,$client,$parent_id);
        if ($pay){
            $payJssdk = $this->pay->getPayJssdk($pay,$client->open_id);
            $resultArr['payJssdk'] = $payJssdk;
            $resultArr['payBill'] = $pay;//将订单信息也返回
            return $resultArr;
        }else{
            return response_format([],0,"订单生成失败");
        }
    }

    /**
     * @api {post} /order/return 申请退款
     * @apiName OrderReturn
     * @apiGroup Order
     *
     * @apiHeader (Authorization) {String} authorization Authorization value.
     *
     * @apiParam {int} good_status 退货商品状态  退货状态 0 已到货 1 未到货
     * @apiParam {int} order_id 订单的id 非订单号 是订单的uid
     * @apiParam {int} return_order_status 退货单状态订单状态，0-提交申请，1-审批拒绝，2-审批通过，3-退货中，4-已完成，5-异常
     * @apiParam {int} return_reason_type 退货理由类型，0-无理由，1-功能异常，2-商品损坏
     * @apiParam {string} return_reason 退货具体原因
     * @apiParam {string} evidence_pic1_url 退货凭证图片1url
     * @apiParam {string} evidence_pic2_url 退货凭证图片2url
     * @apiParam {string} evidence_pic3_url 退货凭证图片3url
     * @apiParam {int} [return_request_type] 退货发起类型，，0-用户发起，我方发起 默认为0
     * @apiParam {int} [request_client_id] 退货申请人，取自两方面值，当退货发起类型为用户，则为xm_client中的uid，当退货发起类型为我方，则为xm_employees中的uid
     *
     * @apiSuccess {Array} data 返回的数据结构体
     * @apiSuccess {Number} status  1 执行成功 0 为执行失败
     * @apiSuccess {string} msg 执行信息提示
     * @apiSuccess {string} return_date 退货到货日期
     *
     * @apiSuccessExample Success-Response:
     * {
     * "response": {
     * "data": {
     * "uid": 4,
     * "return_order_number": "R_1537695508",
     * "order_header_id": 10,
     * "return_request_type": 0,
     * "return_order_status": 0,
     * "request_client_id": 22,
     * "request_date": "2018-09-23 09:38:28",
     * "return_date": null,
     * "return_reason_type": 0,
     * "return_reason": "0",
     * "evidence_pic1_url": null,
     * "evidence_pic2_url": null,
     * "evidence_pic3_url": null,
     * "good_status": 1,
     * "update_time": null
     * },
     * "status": 1,
     * "msg": "申请退货成功,等待商家确认"
     * }
     * }
     */
    public function returnMoney(Request $request)
    {
        try {
            $client_id = session('client.id');
            $data['good_status'] = $request->good_status;
            $data['order_header_id'] = $request->order_id;
            $data['return_order_status'] = $request->return_order_status; // 退货单状态订单状态，见xm_lookup_values表RETURN_ORDER_STATUS：0-提交申请，1-审批拒绝，2-审批通过，3-退货中，4-已完成，5-异常
            $data['return_request_type'] = $request->get('return_request_type', 0); //退货发起类型，，见xm_lookup_values表RETURN_REQUEST_TYPE：0-用户发起，我方发起
            $data['return_order_number'] = 'R_' . time();
            $data['return_reason_type'] = $request->return_reason_type; // 退货理由类型，见xm_lookup_values表RETURN_REASON_TYPE：0-无理由，1-功能异常，2-硬件损坏
            $data['return_reason'] = $request->return_reason; // 退货理由类型，见xm_lookup_values表RETURN_REASON_TYPE：0-无理由，1-功能异常，2-硬件损坏
            $data['request_client_id'] = $client_id;
            $data['request_date'] = Carbon::now();
            $res = \DB::table('return_orders')->insertGetId($data);
            $return_order = \DB::table('return_orders')->where('uid', $res)->first();
            return response_format($return_order, 1, '申请退货成功,等待商家确认', 200);
        } catch (Exception $e) {
            return response_format([], 0, $e->getMessage(), 501);
        }
    }

    /**
     * @api {post} /order/uploadImg 图片上传接口
     * @apiName uploadImg
     * @apiGroup Order
     *
     * @apiHeader (Authorization) {String} authorization Authorization value.
     *
     * @apiParam {file} img 文件字段名 form表单提交 二进制文件上传
     *
     * @apiSuccess {Array} data 返回的数据结构体
     * @apiSuccess {Number} status  1 执行成功 0 为执行失败
     * @apiSuccess {string} msg 执行信息提示
     *
     * @apiSuccessExample Success-Response:
     * {
    * "response": {
    * "data": {
    * "path": "/order_return/jsxHtKB2_1537697894card_01.png",
    * "size": "0.27",
    * "file_display": "card_01.png"
    * },
    * "status": 1,
    * "msg": "success"
    * }
    * }
     */
    public function uploadImg(Request $request)
    {
        $res = upload($request, $request->file()['img']);
        return response_format($res);
    }

}
