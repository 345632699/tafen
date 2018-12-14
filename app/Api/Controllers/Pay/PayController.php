<?php

namespace App\Api\Controllers\Pay;

use App\Api\Controllers\BaseController;
use App\Model\Client;
use App\Model\Good;
use App\Model\Order;
use App\Model\OrderDetail;
use App\Model\WithdrawRecord;
use App\Model\ClientAmount;
use App\Repositories\Client\ClientRepository;
use App\Repositories\Pay\PayRepository;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Mockery\Exception;


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
            file_put_contents(storage_path('logs/pay.log'),"支付单号：".$message['out_trade_no']."支付结果：".$message['return_code'].PHP_EOL,FILE_APPEND);
            $out_trade_no = $message['out_trade_no'];
            $pay_bills = \DB::table("pay_bills")->where('pay_order_number',$out_trade_no);
//            传递parent_id
            if (!$pay_bills) { // 如果订单不存在
                return 'Order not exist.'; // 告诉微信，我已经处理完了，订单没找到，别再通知我了
            }
            if ($pay_bills->first()->pay_date) { // 假设订单字段“支付时间”不为空代表已经支付
                return true; // 已经支付成功了就不再更新了
            }

            if ($message['return_code'] === 'SUCCESS') { // return_code 表示通信状态，不代表支付状态
                $parent_id = $pay_bills->first()->parent_id;
                // 用户是否支付成功
                if (array_get($message, 'result_code') === 'SUCCESS') {
                    $order = Order::find($pay_bills->first()->order_header_id);
                    if (!$order){
                        return $fail('订单不存在');
                    }
                    $update = [
                        'pay_date' => Carbon::now(),
                        'pay_status' => 1,
                        'parent_id' => $parent_id,
                        'transaction_id' => $message['transaction_id'],
                        'total_fee' => $message['total_fee']
                    ];
                    $res = $pay_bills->update($update);
                    if ($res){
                        // $parent_id = $pay_bills->first()->parent_id;
                        $client_id = $pay_bills->first()->client_id;
                        // 发送模板消息
                        $this->sendTempMsg($client_id, $order, $pay_bills->first());
                        Log::info("更新payBill成功,pid:".$parent_id."cid:".$client_id);
                        $this->client->updateTreeNode($client_id,$parent_id);
                    }
                    $client = Client::find($client_id);
                    // 判断商品 是否代理商品
                    $order_lines = Order::select('ol.good_id', 'ol.last_price', 'ol.total_price', 'ol.quantity', 'ol.uid', 'ol.header_id')
                        ->rightJoin('order_lines as ol', 'ol.header_id', '=', 'order_headers.uid')
                        ->where('order_headers.uid', $order->uid)->get();
                    foreach ($order_lines as $line) {
                        $good = Good::find($line->good_id);
                        $good->update([
                            "stock" => $good->stock - $order->quantity,
                            "already_sold" => $good->already_sold + $order->quantity
                        ]);
                        $this->updateAchievement($client,$line->total_price);
                    }
                    if ($order_lines->count() == 1) {
                        $good_agent_type = Good::find($order_lines[0]->good_id)->agent_type_id;
                        // 确定代理等级 根据代理等级进行计算
                        // 1为一级芬赚达人 2 为芬赚高手 3 芬赚大师 4 领袖  10 为代理员工
                        $client_agent_type = $client->agent_type_id;
                        if ($good_agent_type > 0 && $good_agent_type > $client_agent_type) {
                          //更新用户的代理等级
                          $res = $client->update(['agent_type_id' => $good_agent_type]);
                        }
                    }

                    //判断逻辑 不应该写在这里  应与方法一同提取出去
                    $levelOne = Client::find($client->parent_id);
                    if ($levelOne) {
                        //更新上一级用户资金  正常更新上一级的资金流水
                        if ($levelOne->agent_type_id == 10) {
//                            $this->updateEmployeeAmount($order_lines, $client_id, $parent_id);
                        } else {
                            $this->updateAmount($order_lines, $client_id, $parent_id);
                            Log::info("一级用户 更新完毕");
                            //如果上级用户不是销售员 则查询上一级的parent_id
                            // 查找上二级用户 存在且为销售员
                            if ($levelOne->parent_id > 0) {
                                $levelTow = Client::find($levelOne->parent_id);
                                if ($levelTow->agent_type_id == 10) {
                                    $levelTowId = $levelTow->id;
                                    // 员工
//                                    $this->updateSecondAmount($order_lines, $client_id, $levelTowId);
                                } else {
                                    // 非员工
                                    $this->updateSecondPercentage($order_lines, $client_id, $parent_id, $levelTow);
                                    Log::info("二级用户 更新完毕");
                                }
                            }
                        }
                    }

                    $orderUpdate['pay_date'] = Carbon::now();
                    $orderUpdate['order_status'] = 1;
                    $orderUpdateres = $order->update($orderUpdate);
                    // 用户支付失败
                } elseif (array_get($message, 'result_code') === 'FAIL') {
                    $update = [
                        'pay_status' => 2
                    ];
                    $res = $pay_bills->update($update);
                }
            } else {
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
                //提现
                $this->addFlowLog($client_id, null, $withdraw_amount, 4);
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
    public function addFlowLog($client_id, $child_id = null, $amount, $type = 2)
    {
        $record['client_id'] = $client_id;
        $record['child_id'] = $child_id;
        $record['amount'] = $amount;
        $record['type'] = $type;
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
        $parent = Client::find($parent_id);
        $client = Client::find($client_id);
        // 员工非员工 正常进行金钱统计 只有一条记录的时候才可能为 代理商品
        if ($order_lines->count() == 1) {
            $good_id = $order_lines[0]->good_id;
            $total_price = $order_lines[0]->total_price;
            $good_agent_type = Good::find($good_id)->agent_type_id;
            Log::info('$order_lines:' . $order_lines[0]->total_price);
            switch ($good_agent_type) {
                case 1:
                    if ($parent->agent_type_id == 1) {
                        $spread_amount = 5000;
                        $this->addFlowRecord($client_id, $parent_id, $spread_amount, $order_lines[0]);
                    } elseif ($parent->agent_type_id == 2) {
                        $spread_amount = 5500;
                        // 更新冻结金额
                        $this->addFlowRecord($client_id, $parent_id, $spread_amount, $order_lines[0]);
                        // 更新业绩
                        $amount = $order_lines[0]->total_price;
                        $this->updateAchievement($parent, $amount);
                    } elseif ($parent->agent_type_id == 3) {
                        $spread_amount = 6000;
                        // 更新冻结金额
                        $this->addFlowRecord($client_id, $parent_id, $spread_amount, $order_lines[0]);
                        // 更新业绩
                        $amount = $order_lines[0]->total_price;
                        $this->updateAchievement($parent, $amount);
                    }
                    break;
                case 2:
                    if ($parent->agent_type_id == 2) {
                        $spread_amount = 10000;
                        // 更新冻结金额
                        $this->addFlowRecord($client_id, $parent_id, $spread_amount, $order_lines[0]);
                        $parent = Client::find($parent_id);
                        if ($parent->parent_id) {
                            $p_parent = Client::find($parent->parent_id);
                            if ($p_parent->agent_type_id == 3) {
                                $spread_amount = 6000;
                                $this->addFlowRecord($client_id, $parent->parent_id, $spread_amount, $order_lines[0]);
                            }
                        }
                        // 更新业绩
                        $amount = $order_lines[0]->total_price;
                        $this->updateAchievement($parent, $amount);
                    } elseif ($parent->agent_type_id == 3) {
                        $spread_amount = 16000;
                        // 更新冻结金额
                        $this->addFlowRecord($client_id, $parent_id, $spread_amount, $order_lines[0]);
                        // 更新业绩
                        $amount = $order_lines[0]->total_price;
                        $this->updateAchievement($parent, $amount);
                    }
                    break;
                case 3:
                    // 更新业绩
                    $amount = $order_lines[0]->total_price;
                    $this->updateAchievement($parent, $amount);
                    break;
                default:
                    $p_agent_type = $parent->agent_type_id;
                    $c_agent_type = $client->agent_type_id;
                    $rate = 0;
                    $c_rake_back_rate = 0;
                    $p_rake_back_rate = 0;
                    if ($p_agent_type > 0) {
                        $p_rake_back_rate = \DB::table('agent_type')->where('id', $p_agent_type)->first()->rake_back_rate;
                        if ($c_agent_type) {
                            $c_rake_back_rate = \DB::table('agent_type')->where('id', $c_agent_type)->first()->rake_back_rate;
                        }
                        $rate = ($p_rake_back_rate - $c_rake_back_rate) / 100;
                        if ($p_agent_type == 1 && $c_agent_type == 1) {
                            $rate = 0.1;
                        }
                    }

                    if ($rate > 0) {
                        $spread_amount = $total_price * $rate;
                        $this->addFlowRecord($client_id, $parent_id, $spread_amount, $order_lines[0]);
                    }
                    // 上级存在 更新上级业绩
                    $this->updateAchievement($parent, $total_price);
                    break;
            }
        } else {
            $p_agent_type = $parent->agent_type_id;
            $c_agent_type = $client->agent_type_id;
            $rate = 0;
            if ($p_agent_type > 0) {
                $p_rake_back_rate = \DB::table('agent_type')->where('id', $p_agent_type)->first()->rake_back_rate;
                if ($c_agent_type) {
                    $c_rake_back_rate = \DB::table('agent_type')->where('id', $c_agent_type)->first()->rake_back_rate;
                }
                $rate = ($p_rake_back_rate - $c_rake_back_rate) / 100;
                if ($p_agent_type == 1 && $c_agent_type == 1) {
                    $rate = 0.1;
                }
            }
            if ($rate > 0) {
                foreach ($order_lines as $order_line) {
                    $spread_amount = $order_line->total_price * $rate;
                    $this->addFlowRecord($client_id, $parent_id, $spread_amount, $order_line);
                    // 上级存在 更新上级业绩
                    $this->updateAchievement($parent, $order_line->total_price);
                }
            }else{
              foreach ($order_lines as $order_line) {
                // 上级存在 更新上级业绩
                $this->updateAchievement($parent, $order_line->total_price);
              }
            }
        }
    }

    // 更新上一级的余额 员工 agent_type_id > 3
    public function updateEmployeeAmount($order_lines, $client_id, $parent_id)
    {
        if ($this->isConmplete($parent_id)) {
            $rate = 0.08;
        } else {
            $rate = 0.05;
        }
        // 员工非员工 正常进行金钱统计 只有一条记录的时候才可能为 代理商品
        if ($order_lines->count() == 1) {
            $good_id = $order_lines[0]->good_id;
            $last_price = $order_lines[0]->last_price;
            $quantity = $order_lines[0]->quantity;
            $good_agent_type = Good::find($good_id)->agent_type_id;
            switch ($good_agent_type) {
                case 1:
                    $spread_amount = 5000 * $quantity;
                    $this->addFlowRecord($client_id, $parent_id, $spread_amount, $order_lines[0]);
                    break;
                case 2:
                    $spread_amount = 10000 * $quantity;
                    $this->addFlowRecord($client_id, $parent_id, $spread_amount, $order_lines[0]);
                    break;
                case 3:
                    $spread_amount = 16000 * $quantity;
                    $this->addFlowRecord($client_id, $parent_id, $spread_amount, $order_lines[0]);
                    break;
                default:
                    // 如果为销售员 根据业绩情况 统计回报率
                    $spread_amount = $last_price * $quantity * $rate;
                    $this->addFlowRecord($client_id, $parent_id, $spread_amount, $order_lines[0]);
                    break;
            }
        } else {
            foreach ($order_lines as $order_line) {
                $spread_amount = $order_line->total_price * $rate;
                $this->addFlowRecord($client_id, $parent_id, $spread_amount, $order_line);
            }
        }

    }

    // 更新用户等级为高手及其之上的提成 二级用户提成
    public function updateSecondPercentage($order_lines, $client_id, $parent_id, $levelTow)
    {
        try {
            $parent = Client::find($parent_id);
            $client = Client::find($client_id);
            $p_agent_type = $parent->agent_type_id;
            $c_agent_type = $client->agent_type_id;
            $p_p_agent_type = $levelTow->agent_type_id;
            $rate = 0;
            if ($p_p_agent_type > 1) {
                $c_rake_back_rate = 0;
                $p_p_rake_back_rate = 0;
                $p_rake_back_rate = \DB::table('agent_type')->where('id', $p_agent_type)->first()->rake_back_rate;
                if ($c_agent_type) {
                    $c_rake_back_rate = \DB::table('agent_type')->where('id', $c_agent_type)->first()->rake_back_rate;
                    $p_p_rake_back_rate = \DB::table('agent_type')->where('id', $levelTow->agent_type_id)->first()->rake_back_rate;
                }
                $rate = ($p_rake_back_rate - $c_rake_back_rate - $p_p_rake_back_rate) / 100;
                if ($rate > 0) {
                    foreach ($order_lines as $order_line) {
                        $spread_amount = $order_line->total_price * $rate;
                        $this->addFlowRecord($client_id, $levelTow->id, $spread_amount, $order_line);
                        // 更新用户
                        Log::info("用户ID" . $client_id . "用户金额" . $spread_amount);
                    }
                }
            }
        } catch (Exception $e) {
            Log::error('更新二级提成出错' . $e->getMessage());
        }
    }

    //更新上两级用户为员工的账户
    public function updateSecondAmount($order_lines, $client_id, $parent_id)
    {
        if ($this->isConmplete($parent_id)) {
            $rate = 0.08;
        } else {
            $rate = 0.05;
        }
        foreach ($order_lines as $order_line) {
            $good_agent_type = Good::find($order_line->good_id)->agent_type_id;
            if ($good_agent_type == 0) {
                $spread_amount = $order_line->last_price * $rate;
                $level = 2;
                $this->addFlowRecord($client_id, $parent_id, $spread_amount, $order_line, $level);
            }
        }
    }

    //更新业绩
    private function updateAchievement($parent, $amount)
    {
        try {
            $parent->update(['achievement' => $parent->achievement + $amount]);
            if ($parent->achievement >= 76600 && $parent->achievement < 300000){
              $parent->update(['agent_type_id' => 2]);
            }elseif($parent->achievement >= 300000 && $parent->achievement < 500000) {
                $parent->update(['agent_type_id' => 3]);
            } elseif ($parent->achievement >= 500000 && $parent->agent_type_id < 4) {
                $parent->update(['agent_type_id' => 4]);
            }
            //添加更新记录
            $insert['client_id'] = $parent->id;
            $insert['amount'] = $amount;
            $insert['created_at'] = Carbon::now();
            $insert['updated_at'] = Carbon::now();
            $insert['memo'] = '用户业绩增加' . $amount / 100 . '元';
            DB::table('achievement')->insert($insert);
        } catch (Exception $e) {
            Log::error("更新用户等级出错" . $e->getMessage());
        }
    }

    // 更新冻结金额  待审核
    private function addFlowRecord($client_id, $parent_id, $spread_amount, $order_line, $level = 1)
    {
//        $spread_amount = \SystemConfig::$spread_amount;
        $record['client_id'] = $parent_id;
        $record['child_id'] = $client_id;
        $record['amount'] = $spread_amount;
        $record['good_id'] = $order_line->good_id;
        $record['order_line_id'] = $order_line->uid;
        $record['order_header_id'] = $order_line->header_id;
        $record['quantity'] = $order_line->quantity;
        $record['type'] = 2;
        $record['level'] = $level;
        $record['memo'] = "待审核" . $record['amount'] / 100 . "元";
        $record['updated_at'] = Carbon::now();
        $record['created_at'] = Carbon::now();
        $id = \DB::table('client_amount_flow')->insertGetId($record);
        if ($id > 0) {
            \Log::info($parent_id . "冻结金额增加成功，金额为：" . $record['amount']);
        }
        $amount = \DB::table('client_amount')->where('client_id', $parent_id);
        $amount->update([
            'freezing_amount' => $amount->first()->freezing_amount + $spread_amount,
            'count_all' => $amount->first()->count_all + $spread_amount
        ]);
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

    public function sendTempMsg($client_id, $order, $pay_bills)
    {
        $oepn_id = Client::find($client_id)->open_id;
        $minapp = app('wechat.mini_program');
        $sendData = [
            'touser' => $oepn_id,
            'template_id' => 'IylQgK3QkoX2Jn5VrDGcrTC-jprXO6wRNpci9alVfls',
            'page' => 'index',
            'form_id' => $pay_bills->prepay_id,
            'data' => [
                'keyword1' => $pay_bills->transaction_id,
                'keyword2' => $pay_bills->total_fee / 100 . "元",
                'keyword3' => $order->created_at,
                'keyword4' => $pay_bills->name,
                'keyword5' => $pay_bills->uid,
                'keyword6' => $pay_bills->pay_date,
                'keyword7' => $pay_bills->total_price / 100 . "元",
                'keyword8' => '已受理',
                'keyword9' => $order->order_number,
                'keyword10' => $pay_bills->name,
            ],
        ];
        Log::info('===发送模板信息===sendData', $sendData);
        $res = $minapp->template_message->send($sendData);
        Log::info('===发送模板信息===resData' . json_encode($res));
    }
}
