<?php

namespace App\Http\Controllers\Client;

use App\Client;
use App\Model\ClientAmount;
use App\Model\Order;
use App\Model\ReturnOrder;
use App\Model\WithdrawRecord;
use App\Repositories\Client\ClientRepository;
use App\Repositories\Pay\PayRepository;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ClientController extends Controller
{
    public function __construct(ClientRepository $client, PayRepository $pay)
    {
        $this->client = $client;
        $this->pay = $pay;
    }

    public function getList()
    {
        $clients = Client::select('clients.*', 'client_amount.amount', 'client_amount.freezing_amount', 'client_amount.count_all as sum_money')
            ->leftJoin('client_amount', 'client_amount.client_id', '=', 'clients.id')
            ->get();
        return $clients;
    }

    public function update(Request $request)
    {
        $client = Client::where('id', $request->id);
        if ($client->get()) {
            $update['agent_type_id'] = $request->agent_type_id;
            $updateAmount['count_all'] = $request->sum_money * 100;
            $updateAmount['amount'] = $request->amount * 100;
            $updateAmount['freezing_amount'] = $request->freezing_amount * 100;
            $res = $client->update($update);
            $res1 = ClientAmount::where('client_id', $request->id)->update($updateAmount);
            if ($request->agent_type_id > 0) {
                $count = \DB::table('client_link_treepaths')->where('path_end_client_id')->count();
                if (!$count) {
                    $this->client->insertSelfNode($request->id);
                    \Log::info('============为用户:' . $request->id . '添加树结构记录=======');
                }
            }
            if ($res && $res1) {
                return $this->resJson($client->first());
            }
        } else {
            return $this->resJson([], 0, '更新失败，用户不存在');
        }
    }

    public function resJson($data, $status = 1, $msg = 'success')
    {
        $res = [
            'status' => $status,
            'data' => $data,
            'msg' => $msg,
        ];
        return json_encode($res, JSON_UNESCAPED_UNICODE);
    }

    //提现状态 0 为失败 1 为成功 2 提现处理中
    public function withDrawList()
    {
        $list = WithdrawRecord::leftJoin('clients', 'id', '=', 'client_id')->get();
        return $this->resJson($list);
    }

    public function withdrawOperate(Request $request)
    {
        $id = $request->id;
        $type = $request->type;
        $client_id = $request->client_id;
        $amount = $request->amount;
        if ($type) {
            WithdrawRecord::where('uid', $id)->update(['status' => 1]);
        } else {
            WithdrawRecord::where('uid', $id)->update(['status' => 0]);
            $res = \DB::table('client_amount')->where('client_id', $client_id);
            $client_amount = $res->first();
            $res->update(['amount' => $client_amount->amount + $amount]);
        }
        return resJson([]);
    }

    // 退货列表
    public function returnList()
    {
        $list = ReturnOrder::select('clients.nick_name', 'return_orders.*', 'order_headers.order_number', 'order_headers.address')
            ->leftJoin('order_headers', 'order_headers.uid', '=', 'order_header_id')
            ->leftJoin('clients', 'clients.id', '=', 'return_orders.request_client_id')->get();
        return $this->resJson($list);
    }

    public function confirmReturn(Request $request)
    {
//    0-提交申请，1-审批拒绝，2-审批通过，3-退货中，4-已完成，5-异常
        $operation_type = $request->type;
        $return_order_id = $request->id;
        $order_id = $request->order_id;
        if ($operation_type == 2) {
            ReturnOrder::where('uid', $return_order_id)->update(['return_order_status' => 2]);
            Order::where('uid', $order_id)->update(['order_status' => 7]);
        } elseif ($operation_type == 4) {
            ReturnOrder::where('uid', $return_order_id)->update(['return_order_status' => 4]);
            Order::where('uid', $order_id)->update(['order_status' => 8]);
            $result = $this->pay->refund($return_order_id);
        } elseif ($operation_type == 0) {
            ReturnOrder::where('uid', $return_order_id)->update(['return_order_status' => 0]);
            Order::where('uid', $order_id)->update(['order_status' => 6]);
        } else {
            ReturnOrder::where('uid', $return_order_id)->update(['return_order_status' => 1]);
            if (ReturnOrder::where('uid', $return_order_id)->first()->good_status == 1) {
                $order_status = 3;
            } else {
                $order_status = 2;
            }
            Order::where('uid', $order_id)->update(['order_status' => $order_status]);
        }
        if ($result['return_code'] == 'SUCCESS' && $result['result_code'] == 'FAIL') {
            return resJson([], 0, $result['err_code_des']);
        }
        return resJson([], 1, '退款成功');
    }

    public function refund()
    {
        $this->pay->refund(6);
    }

}
