<?php
/**
 * Created by PhpStorm.
 * User: xu
 * Date: 2018/7/2
 * Time: 16:49
 */

namespace App\Repositories\Order;


use App\Model\Contact;
use App\Model\Delivery;
use App\Model\Good;
use App\Model\Invoice;
use App\Model\Order;
use App\Model\OrderDetail;
use App\Repositories\Client\ClientRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Mockery\Exception;

class OrderRepository implements OrderRepositoryInterface
{
    
    public function __construct(ClientRepository $client)
    {
        $this->client = $client;
    }

    public function createOrderHeader($request,$client_id)
    {
        $order_header_data['client_id'] = $client_id;
        $order_header_data['order_number'] = config('wechat.payment.default.mch_id').time();
        $order_header_data['order_date'] = Carbon::now();
        $order_header_data['pay_name'] = $request->get('pay_name','微信支付');
        $order_header_data['pay_type'] = $request->get('pay_type','1');
        $order_header_data['expired_time'] = Carbon::now()->addMinutes(30);
        $order_header_data['shipping_fee'] = $request->get('shipping_fee','0');
        $order_header_data['open_invoice_flag'] = $request->get('open_invoice_flag','N');
        $contract = Contact::find($request->get('address_id'));
        $order_header_data['address'] = '';
        if ($contract) {
            $order_header_data['address'] = $contract->name . " " . $contract->phone_num . '-' . $contract->province . $contract->city . $contract->area . $contract->address;
        }
        $order_header = Order::create($order_header_data);
        return $order_header;
    }

    public function createOrderLine($order_header_id,$request,$parent_id)
    {
        $client_id = session('client.id');
        $order_line_data['header_id'] = $order_header_id;
        $order_line_data['good_id'] = $request->get('good_id',1);
        $good = DB::table('goods')->where('uid',$order_line_data['good_id'])->first();
        $order_line_data['buyer_msg'] = $request->get('buyer_msg',"");
        $order_line_data['quantity'] = $request->get('quantity',1);
        $order_line_data['attr_good_mapping_id'] = $request->get('attr_good_mapping_id','');
        $attr_good_mapping_id = $request->get('attr_good_mapping_id', '');
        if ($attr_good_mapping_id) {
            $attr_mapping = DB::table('attr_good_mapping')->where('id', $attr_good_mapping_id)->first();
            $order_line_data['original_price'] = $attr_mapping->original_price;
            $order_line_data['discount_price'] = $attr_mapping->discount_price;
        } else {
            $order_line_data['original_price'] = $good->original_price;
            $order_line_data['discount_price'] = $good->discount_price;
        }
        $agentRate = $this->client->getAgentRate($client_id);
        $order_line_data['agent_price'] = $good->original_price * $agentRate / 100;
        if ($good->is_coupon) {
            $order_line_data['last_price'] =  $order_line_data['discount_price'];
        }else{
            $order_line_data['last_price'] =  $order_line_data['agent_price'];
        }
        $order_line_data['total_price'] = $order_line_data['last_price'] * $order_line_data['quantity'];
        $contract = Contact::find($request->get('address_id'));
        $order_line_data['address'] = '';
        if ($contract){
            $order_line_data['address'] = $contract->name . " " . $contract->phone_num . '-' . $contract->province . $contract->city . $contract->area . $contract->address;
        }
        $order_line = OrderDetail::create($order_line_data);
        return $order_line;
    }

    public function createOrderLineFromCart($order_header_id,$cart,$address_id){
        $order_line_data['header_id'] = $order_header_id;
        $order_line_data['good_id'] = $cart->good_id;
        $order_line_data['buyer_msg'] = '';
        $order_line_data['quantity'] = $cart->number;
        $order_line_data['attr_good_mapping_id'] = $cart->attr_good_mapping_id;
        $attr_good_mapping_id = $cart->attr_good_mapping_id;
        if ($attr_good_mapping_id) {
            $attr_mapping = DB::table('attr_good_mapping')->where('id', $attr_good_mapping_id)->first();
            $order_line_data['original_price'] = $attr_mapping->original_price;
            $order_line_data['discount_price'] = $attr_mapping->discount_price;
        } else {
            $order_line_data['original_price'] = $cart->original_price;
            $order_line_data['discount_price'] = $cart->discount_price;
        }
        $order_line_data['agent_price'] = $cart->agent_price;
        $order_line_data['last_price'] =  $cart->last_price;
        $order_line_data['total_price'] = $cart->total_price;
        $contract = Contact::find($address_id);
        $order_line_data['address'] = '';
        if ($contract){
            $order_line_data['address'] = $contract->name . ' ' . $contract->province.$contract->city.$contract->area.$contract->address . " " .$contract->phone_num;
        }
        $order_line = OrderDetail::create($order_line_data);
        return $order_line;
    }

    public function createDelivery($order_header_id,$address_id)
    {
        $delivery_data['order_header_id'] = $order_header_id;
        $delivery_data['delivery_contact_id'] = $address_id;
        $contract = Contact::find($address_id);
        $delivery_data['address'] = '';
        if ($contract){
            $delivery_data['address'] = $contract->name . ' ' . $contract->province.$contract->city.$contract->area.$contract->address . " " .$contract->phone_num;
        }
        $delivery = Delivery::create($delivery_data);
        return $delivery;
    }

    public function getOrderList($order_status, $keywords, $limit = 5)
    {
        if ($order_status >= 0 ){
            $where['order_status'] = $order_status;
            $where['client_id'] = session('client.id');
        }else{
            $where['client_id'] = session('client.id');
        }
        if ($keywords) {
            $limit = $limit ? $limit : 5;
            $order_list = \DB::table('order_headers')
                ->select(
                    'order_headers.*',
                    'order_headers.uid as order_id',
                    'order_type',
                    'order_status',
                    'order_date',
                    'completion_date',
                    'return_date',
                    'request_close_date',
                    'expired_time',
                    'pay_type',
                    'pay_name',
                    'pay_date',
                    'shipping_fee'
                )
                ->where($where)
                ->where('order_number', 'like', '%' . $keywords . '%')
                ->orderBy('order_date', 'desc')
                ->paginate($limit)->toArray();
        } else {
            $limit = $limit ? $limit : 5;
            $order_list = \DB::table('order_headers')
                ->select(
                    'order_headers.*',
                    'order_headers.uid as order_id',
                    'order_type',
                    'order_status',
                    'order_date',
                    'completion_date',
                    'return_date',
                    'request_close_date',
                    'expired_time',
                    'pay_type',
                    'pay_name',
                    'pay_date',
                    'shipping_fee'
                )
                ->where($where)
                ->orderBy('order_date', 'desc')
                ->paginate($limit)->toArray();
        }


        foreach ($order_list['data'] as $order){
            if ($order->expired_time < Carbon::now()) {
            }
            $list = Order::select('ol.*','goods.name as good_name','goods.thumbnail_img','goods.description','attr.name as attr_name','agm.name as attr_value')
                ->rightJoin('order_lines as ol','ol.header_id','=','order_headers.uid')
                ->leftJoin('attr_good_mapping as agm','agm.id','=','attr_good_mapping_id')
                ->leftJoin('attributes as attr','attr.id','=','agm.attr_id')
                ->leftJoin('goods','goods.uid','=','ol.good_id')
                ->where('order_headers.uid',$order->uid)->get();
            $order->good_list = $list;
        }

        return $order_list;
    }

    /**
     * @param $client_id 用户ID
     * @param $order_id  订单ID
     * @param $request   请求参数
     * @return mixed
     *
     * 创建发票信息
     */
    public function createInvoice($client_id, $order_id, $total_price, $request)
    {
        //invoice_type 0-个人，1-公司
        $invoice['invoice_type'] = $request->get('invoice_type','0');
        $invoice['detail'] = $request->get('detail','');
        $invoice['phone_num'] = $request->get('phone_num',null);
        $invoice['amount'] = $total_price;
        $invoice['email'] = $request->get('email','');
        $invoice['title'] = $request->get('title','');
        $invoice['tax_code'] = $request->get('tax_code','');
        $invoice['client_id'] = $client_id;
        $invoice['order_id'] = $order_id;
        $invoice['invoice_date'] = Carbon::now();
        $res = Invoice::create($invoice);
        Log::info("invoice 创建成功");
    }

    public function getOrderDetail($order_id)
    {
        try{
            $order = \DB::table('order_headers')
                ->select(
                    'order_headers.*',
                    'order_headers.uid as order_id',
                    'order_type',
                    'order_status',
                    'order_date',
                    'completion_date',
                    'return_date',
                    'request_close_date',
                    'expired_time',
                    'pay_type',
                    'pay_name',
                    'pay_date',
                    'shipping_fee',
                    'nick_name'
                )
                ->leftJoin('clients', 'id', '=', 'order_headers.client_id')
                ->where('uid',$order_id)->get()->first();


            $list = Order::select('ol.*','goods.name as good_name','goods.thumbnail_img','goods.description','attr.name as attr_name','agm.name as attr_value')
                ->rightJoin('order_lines as ol','ol.header_id','=','order_headers.uid')
                ->leftJoin('attr_good_mapping as agm','agm.id','=','attr_good_mapping_id')
                ->leftJoin('attributes as attr','attr.id','=','agm.attr_id')
                ->leftJoin('goods','goods.uid','=','ol.good_id')
                ->where('order_headers.uid', $order_id)->get()->toArray();
            $order->good_list = $list;

            //订单状态，见xm_lookup_values表ORDER_STATUS：0-已下单，1-已支付，2-待发货，3-已发货，4-已完成，5-异常，6-申请退货，7-确认退货，8-已退货
            $order_status = $order->order_status;
            switch ($order_status){
                case 0:
                    $order->order_status_name = "未支付";
                    break;
                case 1:
                    $order->order_status_name = "已支付";
                    break;
                case 2:
                    $order->order_status_name = "待发货";
                    break;
                case 3:
                    $order->order_status_name = "已发货";
                    break;
                case 4:
                    $order->order_status_name = "已完成";
                    break;
                case 5:
                    $order->order_status_name = "异常";
                    break;
                case 6:
                    $order->order_status_name = "申请退货";
                    break;
                case 7:
                    $order->order_status_name = "确认退货";
                    break;
                case 8:
                    $order->order_status_name = "已退货";
                    break;
                case 9:
                    $order->order_status_name = "已取消";
                    break;
            }
            // 0-预付款，1-货到付款
            if (!$order->order_type){
                $order->order_type_name = '微信支付';
            }else{
                $order->order_type_name = '货到付款';
            }


//            $good = Good::find($order->good_id);
//            $address = Contact::where('uid',$order->contract_id)->first();
//            $delivery = Delivery::select('delivery_products.product_id','delivery.*')
//                                ->leftJoin('delivery_products','delivery_id','=','delivery.uid')
//                                ->where('order_header_id',$order->uid)->first();
//
//            $invoice = \DB::table('invoice_record')->where('order_id',$order->uid)->first();
//            if (!$invoice)
//                $invoice = [];
//            $data['order'] = $order;
//            $data['good'] = $good;
//            $data['address'] = $address;
//            $data['delivery'] = $delivery;
//            $data['invoice'] = $invoice;
            return $order;
        }catch (Exception $e){
            return $e->getMessage();
        }

    }

    /**
     * @param $order_id
     * @param $client_id
     * @return mixed
     * 确认收货
     */
    public function confirm($order_id, $client_id)
    {
        if (is_null($order_id)){
            return response_format([],0,'缺少order_id参数',400);
        }
        try{
            $orderRes = $deliveryRes = false;
            DB::beginTransaction();
            //确保订单是 本人在操作
            $order = DB::table('order_headers')->where(['uid'=>$order_id,'client_id'=>$client_id,'order_status'=>3]);
            if ($order){
                $orderRes = $order->update(['order_status'=>4]);
            }
            //已发货 切用户id对上了才可以进行操作
            $delivery = DB::table('delivery')->where(['order_header_id'=>$order_id,'delivery_status'=>1]);
            if ($delivery)
                $deliveryRes = $delivery->update(['delivery_status'=>2]);
            if ($orderRes && $deliveryRes){
                DB::commit();
                return ['status'=>1,'statusCode'=>200,'msg'=>'success'];
            }else{
                DB::rollback();
                return ['status'=>0,'statusCode'=>400,'msg'=>'订单不存在'];
            }
        }catch (Exception $e){
            return ['status'=>0,'statusCode'=>$e->getCode(),'msg'=>$e->getMessage()];
        }
    }

    /**
     * @param $order_id
     * @param $client_id
     * @return mixed
     * 取消订单
     */
    public function cancel($order_id, $client_id)
    {
        if (is_null($order_id)){
            return response_format([],0,'缺少order_id参数',400);
        }
        try{
            $orderRes = $deliveryRes = false;
            DB::beginTransaction();
            //确保订单是 本人在操作
            $order = DB::table('order_headers')->where(['uid'=>$order_id,'client_id'=>$client_id,'order_status'=>0]);
            if ($order){
                $orderRes = $order->update(['order_status'=>9]);
            }

            if ($orderRes){
                DB::commit();
                return ['status'=>1,'statusCode'=>200,'msg'=>'success'];
            }else{
                DB::rollback();
                return ['status'=>0,'statusCode'=>400,'msg'=>'订单不存在'];
            }
        }catch (Exception $e){
            return ['status'=>0,'statusCode'=>$e->getCode(),'msg'=>$e->getMessage()];
        }
    }
}