<?php

namespace App\Api\Controllers\Pay;

use App\Api\Controllers\BaseController;
use App\Model\Client;
use App\Model\Good;
use App\Model\Order;
use App\Model\WithdrawRecord;
use App\Model\ClientAmount;
use App\Repositories\Client\ClientRepository;
use App\Repositories\Pay\PayRepository;
use Carbon\Carbon;
use ClassesWithParents\D;
use EasyWeChat\Payment\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;


class PayController extends BaseController
{

    public function __construct(ClientRepository $client,PayRepository $pay)
    {
        $this->client = $client;
        $this->pay = $pay;
    }

    public function index() {

    }

    public function create(){

    }

    public function payNotify() {
        $app = app('wechat.payment');
        $response = $app->handlePaidNotify(function ($message, $fail) {
            // 你的逻辑
            Log::info("wechat-notify:".$message['out_trade_no']);
            file_put_contents(storage_path('logs/pay.log'),"支付单号：".$message['out_trade_no']."支付结果：".$message['return_code'].PHP_EOL,FILE_APPEND);
            $out_trade_no = $message['out_trade_no'];
            $pay_bills = \DB::table("pay_bills")->where('pay_order_number',$out_trade_no);
//            传递parent_id
            if (!$pay_bills) { // 如果订单不存在
                Log::info("========微信支付=========");
                Log::error('Order not exist.'."订单号：".$out_trade_no);
                Log::info("========微信支付=========");
                return 'Order not exist.'; // 告诉微信，我已经处理完了，订单没找到，别再通知我了
            }
            if ($pay_bills->first()->pay_date) { // 假设订单字段“支付时间”不为空代表已经支付
                Log::info("========微信支付=========");
                Log::info('单字段“支付时间”不为空代表已经支付.'."订单号：".$out_trade_no);
                Log::info("========微信支付=========");
                return true; // 已经支付成功了就不再更新了
            }

            if ($message['return_code'] === 'SUCCESS') { // return_code 表示通信状态，不代表支付状态
                $parent_id = $pay_bills->first()->parent_id;
                // 用户是否支付成功
                if (array_get($message, 'result_code') === 'SUCCESS') {
                    $order = Order::find($pay_bills->first()->order_header_id);
                    Log::info($order->toJson());
                    if (!$order){
                        Log::info("========微信支付=========");
                        Log::error('订单不存在.'."订单号：".$out_trade_no);
                        Log::info("========微信支付=========");
                        return $fail('订单不存在');
                    }
                    $update = [
                        'pay_date' => Carbon::now(),
                        'pay_status' => 1,
                        'parent_id' => $parent_id
                    ];
                    $res = $pay_bills->update($update);
                    if ($res){
                        // $parent_id = $pay_bills->first()->parent_id;
                        $client_id = $pay_bills->first()->client_id;
                        Log::info("更新payBill成功,pid:".$parent_id."cid:".$client_id);
                        $this->client->updateTreeNode($client_id,$parent_id);
                    }

                    // 判断商品 是否代理商品
                    $order_lines = Order::select('ol.good_id', 'ol.last_price', 'ol.quantity')
                        ->rightJoin('order_lines as ol', 'ol.header_id', '=', 'order_headers.uid')
                        ->where('order_headers.uid', $order->uid)->get();
                    if ($order_lines->count() == 1) {
                        $good_agent_type = Good::find($order_lines[0]->good_id)->agent_type_id;
                    }

                    // 确定代理等级 根据代理等级进行计算
                    // 1为一级芬赚达人 2 为芬赚高手 3 芬赚大师  10 为代理员工
                    $client = Client::find($client_id);
                    $client_agent_type = $client->agent_type_id;
                    if ($good_agent_type > 0 && $good_agent_type > $client_agent_type) {
                        //更新用户的代理等级
                        $client->update(['agent_type_id' => $good_agent_type]);
                    }
                    $levelOne = Client::find($client->parent_id);
                    if ($levelOne) {
                        //更新上一级用户资金  正常更新上一级的资金流水
                        if ($levelOne->agent_type_id > 3) {
                            $this->updateEmployeeAmount($order_lines, $client_id, $parent_id);
                        } else {
                            $this->updateAmount($order_lines, $client_id, $parent_id);
                            //如果上级用户不是销售员 则查询上一级的parent_id
                            // 查找上二级用户 存在且为销售员
                            if ($levelOne->parent_id > 0) {
                                $levelTow = Client::find($levelOne->parent_id);
                                if ($levelTow->agent_type_id > 3) {
                                    $levelTowId = $levelTow->id;
                                    $this->updateSecondAmount($order_lines, $client_id, $levelTowId);
                                }
                            }
                        }
                    }

                    $orderUpdate['pay_date'] = Carbon::now();
                    $orderUpdate['order_status'] = 1;
                    $orderUpdateres = $order->update($orderUpdate);
                    Log::info("========微信支付=========");
                    Log::info('订单支付成功，更新状态:' . $orderUpdateres . "订单号：" . $out_trade_no);
                    Log::info("========微信支付=========");
                    // 用户支付失败
                } elseif (array_get($message, 'result_code') === 'FAIL') {
                    $update = [
                        'pay_status' => 2
                    ];
                    $res = $pay_bills->update($update);
                    Log::info("========微信支付=========");
                    Log::error('订单支付失败.'.$res."订单号：".$out_trade_no);
                    Log::info("========微信支付=========");
                }
            } else {
                Log::info("========微信支付=========");
                Log::error('通信失败，请稍后再通知我.'."订单号：".$out_trade_no);
                Log::info("========微信支付=========");
                return $fail('通信失败，请稍后再通知我');
            }
            file_put_contents(storage_path('logs/pay.log'),"支付单号：".$message['out_trade_no']."支付结果：".$message['return_code'].PHP_EOL,FILE_APPEND);
            return true;
        });
        return $response;
    }

    /**
     * @api {post} /pay/withdraw 提现
     * @apiName withdraw
     * @apiGroup Pay
     *
     * @apiHeader (Authorization) {String} authorization Authorization value.
     *
     * @apiParam {int} amount 提现金额
     *
     * @apiSuccess {Array} data 返回的数据结构体
     * @apiSuccess {Number} status  1 执行成功 0 为执行失败
     * @apiSuccess {string} msg 执行信息提示
     *
     *
     */
    public function withdraw(Request $request){
        $withdraw_amount = $request->amount;
        if ($withdraw_amount <= 30){
            return response_format([],0,'提现金额必须大于30元');
        }
        $client = $this->client->getUserByOpenId();
        $client_id = $client->id;
//        $amount = \DB::table('client_amount')->where('client_id',$client_id)->first();
        $amount = ClientAmount::where('client_id',$client_id)->first();
        if ($amount){
            $can_withdraw_amount = $amount->amount - $amount->freezing_amount;
            if ( $can_withdraw_amount >= $withdraw_amount){
                //record
                $withdraw_record['client_id'] = $client_id;
                $withdraw_record['partner_trade_no'] = 'W'.time();
                $withdraw_record['amount'] = $withdraw_amount;
                $withdraw_record['created_at'] = Carbon::now();
                $withdraw_record['updated_at'] = Carbon::now();

//                $res = \DB::table('withdraw_record')->create($withdraw_record);
                $res = WithdrawRecord::create($withdraw_record);
                if ($res->uid) {
                    $update['amount'] = $amount->amount - $withdraw_amount;
                    $amount->update($update);
                    $this->pay->withDraw($res->uid,$client,$amount);
                }
                $this->addFlowLog($client_id,null,$withdraw_amount,2);
                return response_format($res);

            }else{
                return response_format([],0,'可提余额不足');
            }
        }else{
            return response_format([],0,'个人信息获取失败');
        }
    }

    /**
     * @param $client_id
     * @param null $child_id
     * @param $amount
     * @param $type 1 增加冻结金额 2 可提现金额减少 3 减少冻结金额 4 可提现金额增加
     */
    public function addFlowLog($client_id,$child_id = null,$amount,$type){
        $record['client_id'] = $client_id;
        $record['child_id'] = $child_id;
        $record['amount'] = $amount;
        $record['type'] = 2;
        $client = Client::find($client_id);
        if ($type == 1){
            $record['memo'] = $client->nick_name."增加冻结金额".$record['amount']."元";
        }else if ($type == 2){
            $record['memo'] = $client->nick_name."可提现金额减少".$record['amount']."元";
        }else if ($type == 3){
            $record['memo'] = $client->nick_name."减少冻结金额".$record['amount']."元";
        }else if ($type == 3){
            $record['memo'] = $client->nick_name."可提现金额增加".$record['amount']."元";
        }
        $record['updated_at'] = Carbon::now();
        $record['created_at'] = Carbon::now();
        $id = \DB::table('client_amount_flow')->insertGetId($record);
        if ($id > 0 ){
            \Log::info($client_id."冻结金额增加成功，金额为：".$record['amount']);
        }
    }

    /**
     * @api {post} /pay/withdraw_list 提现记录
     * @apiName PayWithdraw
     * @apiGroup Pay
     *
     * @apiHeader (Authorization) {String} authorization Authorization value.
     *
     * @apiParam {int} type 1 获取个人的  2 获取所有的
     * @apiParam {int} status 0 提现失败 1 提现成功 2 提现中
     *
     * @apiSuccess {Array} data 返回的数据结构体
     * @apiSuccess {Number} status  1 执行成功 0 为执行失败
     * @apiSuccess {string} msg 执行信息提示
     *
     *
     */
    public function getWithDrawRecordList(Request $request){
        //type - 1 获取个人的  2 获取所有的
        $type = $request->get('type',1);
        $client_id = session('client.id');

        //status - 0 提现失败 1 提现成功 2 提现中
        $status = $request->status;

        $limit = $request->get('limit',5);

        if ($type == 1) {
            if ($status){
                $where['status'] = $status;
                $where['client_id'] = $client_id;
            }else{
                $where['client_id'] = $client_id;
            }
            $list = \DB::table('withdraw_record')
                ->select('withdraw_record.*','clients.nick_name','clients.phone_num')
                ->leftJoin('clients','clients.id','=','withdraw_record.client_id')
                ->where($where)->paginate($limit);
            return response_format($list);
        }else{
            $list = \DB::table('withdraw_record')
                ->select('withdraw_record.*','clients.nick_name','clients.phone_num')
                ->leftJoin('clients','clients.id','=','withdraw_record.client_id')
                ->where('status',$status)
                ->orderBy('uid','desc')
                ->limit(8)
                ->get()->toArray();
            return response_format($list);
        }
    }

    // 更新上级的余额 非员工
    public function updateAmount($order_lines, $client_id, $parent_id)
    {
        // 员工非员工 正常进行金钱统计 只有一条记录的时候才可能为 代理商品
        if ($order_lines->count() == 1) {
            $good_id = $order_lines[0]->good_id;
            $last_price = $order_lines[0]->last_price;
            $good_agent_type = Good::find($good_id)->agent_type_id;
            switch ($good_agent_type) {
                case 1:
                    $spread_amount = $last_price * 0.1 + 5000;
                    $this->addFlowRecord($client_id, $parent_id, $spread_amount, $good_id);
                    break;
                case 2:
                    $spread_amount = $last_price * 0.1 + 10000;
                    $this->addFlowRecord($client_id, $parent_id, $spread_amount, $good_id);
                    break;
                case 3:
                    $spread_amount = $last_price * 0.1 + 12000;
                    $this->addFlowRecord($client_id, $parent_id, $spread_amount, $good_id);
                    break;
                default:
                    $rate = 0.1;
                    $spread_amount = $last_price * $rate;
                    $this->addFlowRecord($client_id, $parent_id, $spread_amount, $good_id);
                    break;
            }
        } else {
            foreach ($order_lines as $order_line) {
                $spread_amount = $order_line->total_price * 0.1;
                $this->addFlowRecord($client_id, $parent_id, $spread_amount, $order_line->good_id);
            }
        }
    }

    // 更新上一级的余额 员工 agent_type_id > 3
    public function updateEmployeeAmount($order_lines, $client_id, $parent_id)
    {
        // 员工非员工 正常进行金钱统计 只有一条记录的时候才可能为 代理商品
        if ($order_lines->count() == 1) {
            $good_id = $order_lines[0]->good_id;
            $last_price = $order_lines[0]->last_price;
            $quantity = $order_lines[0]->quantity;
            $good_agent_type = Good::find($good_id)->agent_type_id;
            if ($this->isConmplete($parent_id)) {
                $rate = 0.02;
            } else {
                $rate = 0;
            }
            switch ($good_agent_type) {
                case 1:
                    $spread_amount = 5000 * $quantity + $last_price * $quantity * $rate;
                    $this->addFlowRecord($client_id, $parent_id, $spread_amount, $good_id);
                    break;
                case 2:
                    $spread_amount = 10000 * $quantity + $last_price * $quantity * $rate;
                    $this->addFlowRecord($client_id, $parent_id, $spread_amount, $good_id);
                    break;
                case 3:
                    $spread_amount = 12000 * $quantity + $last_price * $quantity * $rate;
                    $this->addFlowRecord($client_id, $parent_id, $spread_amount, $good_id);
                    break;
                default:
                    // 如果为销售员 根据业绩情况 统计回报率
                    $spread_amount = $last_price * $quantity * 0.1;
                    $this->addFlowRecord($client_id, $parent_id, $spread_amount, $good_id);
                    break;
            }
        } else {
            foreach ($order_lines as $order_line) {
                $spread_amount = $order_line->total_price * 0.1;
                $this->addFlowRecord($client_id, $parent_id, $spread_amount, $order_line->good_id);
            }
        }

    }

    //更新上两级用户为员工的账户
    public function updateSecondAmount($order_lines, $client_id, $parent_id)
    {
        $rate = 0.08;
        foreach ($order_lines as $order_line) {
            $good_agent_type = Good::find($order_line->good_id)->agent_type_id;
            if ($good_agent_type == 0) {
                $spread_amount = $order_line->last_price * $rate;
                $this->addFlowRecord($client_id, $parent_id, $spread_amount, $order_line->good_id);
            }
        }
    }

    private function addFlowRecord($client_id, $parent_id, $spread_amount, $good_id)
    {
//        $spread_amount = \SystemConfig::$spread_amount;
        $record['client_id'] = $parent_id;
        $record['child_id'] = $client_id;
        $record['amount'] = $spread_amount;
        $record['good_id'] = $good_id;
        $record['type'] = 3;
        $record['memo'] = "提成" . $record['amount'] . "元";
        $record['updated_at'] = Carbon::now();
        $record['created_at'] = Carbon::now();
        $id = \DB::table('client_amount_flow')->insertGetId($record);
        if ($id > 0) {
            \Log::info($parent_id . "冻结金额增加成功，金额为：" . $record['amount']);
        }
        $amount = \DB::table('client_amount')->where('client_id', $parent_id);
        $amount->update(['amount', $amount->first()->amount + $spread_amount]);
    }

    // 是否达标
    public function isConmplete($client_id)
    {
        $oneSql = 'select * from xm_client_link_treepaths where dist=1 and path_end_client_id=' . $client_id . ' and month(created_at)=month(now())';
        $twoSql = 'select * from xm_client_link_treepaths where dist=2 and path_end_client_id=' . $client_id . ' and month(created_at)=month(now())';
        $oneCount = count(\DB::select($oneSql));
        $twoCount = count(\DB::select($twoSql));
        if ($oneCount >= 30 && $twoCount >= 60) {
            return true;
        } else {
            return false;
        }
    }

}
