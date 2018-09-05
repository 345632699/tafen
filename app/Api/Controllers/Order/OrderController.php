<?php

namespace App\Api\Controllers\Order;
use App\Api\Controllers\BaseController;
use App\Model\Cart;
use App\Repositories\Client\ClientRepository;
use App\Repositories\Order\OrderRepository;
use App\Repositories\Pay\PayRepository;
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
     *
     * @apiSuccess {int} order_id 订单ID
     * @apiSuccess {string} order_number 商品订单
     * @apiSuccess {int} order_type 0-预付款，1-货到付款
     * @apiSuccess {int} order_status ORDER_STATUS：0-已下单，1-已支付，2-待发货，3-已发货，4-已完成，5-异常，6-申请退货，7-确认退货，8-已退货 9-已取消
     * @apiSuccess {datetime} order_date 下单时间
     * @apiSuccess {datetime} pay_date 支付时间
     * @apiSuccess {int} contract_id 收货地址ID
     * @apiSuccess {datetime} completion_date 订单完成时间
     * @apiSuccess {datetime} return_date 退货时间
     * @apiSuccess {datetime} request_close_date 订单关闭日期
     * @apiSuccess {string} open_invoice_flag 是否开发票
     * @apiSuccess {int} good_id 商品id
     * @apiSuccess {string} good_name 商品名称
     * @apiSuccess {float} total_price 商品总金额
     * @apiSuccess {float} discount_price 单价
     * @apiSuccess {int} quantity 数量
     * @apiSuccess {string} robot_id 关联机器人ID
     *
     */
    public function getOrderList(Request $request){
        $order_status = $request->get('order_status',-1);
        $limit = $request->limit;
        $order_list = $this->order->getOrderList($order_status,$limit);
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
     *
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

}
