<?php

namespace App\Http\Controllers\Client;

use App\Client;
use App\Model\ClientAmount;
use App\Model\Order;
use App\Model\ReturnOrder;
use App\Model\WithdrawRecord;
use App\Repositories\Client\ClientRepository;
use App\Repositories\Pay\PayRepository;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use function Sodium\add;

class ClientController extends Controller
{
    public function __construct(ClientRepository $client, PayRepository $pay)
    {
        $this->client = $client;
        $this->pay = $pay;
    }

    public function getList()
    {
        $clients = Client::select('clients.*', 'client_amount.amount', 'parent.nick_name as parent_name', 'parent.id as parent_id', 'client_amount.freezing_amount', 'client_amount.count_all as sum_money')
            ->leftJoin('client_amount', 'client_amount.client_id', '=', 'clients.id')
            ->leftJoin('clients as parent', 'parent.id', '=', 'clients.parent_id')
            ->get();
        $parent_list = Client::select('id', 'nick_name')->get();
        $res['clients'] = $clients;
        $res['parent_list'] = $parent_list;
        return $this->resJson($res);;
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
            //更新推荐人
            if ($client->first()->parent_id != $request->parent_id) {
                $this->changeParent($request->id, $request->parent_id);
            }

//            if ($request->agent_type_id > 0) {
//                $count = \DB::table('client_link_treepaths')->where('path_end_client_id')->count();
//                if (!$count) {
//                    $this->client->insertSelfNode($request->id);
//                    \Log::info('============为用户:' . $request->id . '添加树结构记录=======');
//                }
//            }
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
            return resJson([]);
        } elseif ($operation_type == 4) {
            ReturnOrder::where('uid', $return_order_id)->update(['return_order_status' => 4]);
            Order::where('uid', $order_id)->update(['order_status' => 8]);
            $result = $this->pay->refund($return_order_id);
        } elseif ($operation_type == 0) {
            ReturnOrder::where('uid', $return_order_id)->update(['return_order_status' => 0]);
            Order::where('uid', $order_id)->update(['order_status' => 6]);
            return resJson([]);
        } else {
            ReturnOrder::where('uid', $return_order_id)->update(['return_order_status' => 1]);
            if (ReturnOrder::where('uid', $return_order_id)->first()->good_status == 1) {
                $order_status = 3;
            } else {
                $order_status = 2;
            }
            Order::where('uid', $order_id)->update(['order_status' => $order_status]);
            return resJson([], 1, '重置成功');
        }
        if ($result['return_code'] == 'SUCCESS' && $result['result_code'] == 'FAIL') {
            return resJson([], 0, $result['err_code_des']);
        }
        return resJson([], 1, '退款成功');
    }

    public function changeParent($client_id, $parent_id)
    {
//        $client = Client::where("id",$client_id);
//        $client->update(['parent_id'=>$parent_id]);
        $row = \DB::table('client_link_treepaths')->where([
            'path_end_client_id' => $client_id,
            'dist' => 1
        ])->first();
        if ($row) {
            // 更改上级关系
            $childs = \DB::table('client_link_treepaths')->where([
                'path_begin_client_id' => $row->path_begin_client_id,
                'dist' => 1
            ]);
            if ($childs->count() < 2) {
                \DB::table('client_link_treepaths')->where([
                    'path_begin_client_id' => $row->path_begin_client_id,
                    'path_end_client_id' => $row->path_begin_client_id
                ])->update(['is_leaf' => 1]);
            }
            $c_node = \DB::table('client_link_treepaths')->select("path_end_client_id")->where([
                'path_begin_client_id' => $client_id,
            ])->where('dist', '>', 0)->pluck("path_end_client_id")->toArray();
            array_push($c_node, $client_id);
            $p_node = \DB::table('client_link_treepaths')->select("path_begin_client_id")->where([
                'path_end_client_id' => $client_id,
            ])->where('dist', '>', 0)->pluck("path_begin_client_id")->toArray();
            \DB::table('client_link_treepaths')->whereIn(
                'path_end_client_id', $c_node
            )->whereIn('path_begin_client_id', $p_node)->where('dist', '>', 0)->where('dist', '>', 0)->delete();
        }

        $my_childs = \DB::table('client_link_treepaths')->select('path_end_client_id')->where([
            'path_begin_client_id' => $client_id,
        ])->where('dist', '>', 0)->pluck('path_end_client_id')->toArray();

        $this->updateParent($client_id, $parent_id, count($my_childs));
        $this->updateChildNode($client_id, $parent_id);
//        $this->updateChids($client_id,$my_childs);
    }

    public function updateChildNode($client_id, $parent_id)
    {
        $res = \DB::table('client_link_treepaths')->where([
            'path_begin_client_id' => $client_id,
        ])->where('dist', '>', 0);
        if ($res->count()) {
            foreach ($res->get() as $key => $ine) {
                $insert[$key]['path_begin_client_id'] = $parent_id;
                $insert[$key]['dist'] = $ine->dist + 1;
                $insert[$key]['is_leaf'] = $ine->is_leaf;
                $insert[$key]['path_end_client_id'] = $ine->path_end_client_id;
                $insert[$key]['created_at'] = Carbon::now();
                $insert[$key]['updated_at'] = Carbon::now();
            }
            \DB::table('client_link_treepaths')->insert($insert);
        }
    }

    //更新父级节点
    public function updateParent($client_id, $parent_id, $count_child)
    {
        if ($parent_id > 0) {
            //更新父节点的 叶子节点改为0
            $parent_nodes = \DB::table('client_link_treepaths')->where([
                'path_end_client_id' => $parent_id,
                'path_begin_client_id' => $parent_id
            ]);
//                    $parent_nodes_id = $parent_nodes->pluck('uid')->toArray();
            if ($parent_nodes->count() == 0) {
                $this->client->insertSelfNode($parent_id);
                //更新父节点的 叶子节点改为0
                $parent_nodes = \DB::table('client_link_treepaths')->where('path_end_client_id', $parent_id);
//                    $parent_nodes_id = $parent_nodes->pluck('uid')->toArray();
            }
            \DB::table('client_link_treepaths')->where(
                [
                    'path_begin_client_id' => $parent_id,
                    'path_end_client_id' => $parent_id
                ]
            )->update(['is_leaf' => 0]);

            //插入
            $insert = [];
            foreach ($parent_nodes->get() as $key => $node) {
                $insert[$key]['path_begin_client_id'] = $node->path_begin_client_id;
                $insert[$key]['dist'] = $node->dist + 1;
                $insert[$key]['is_leaf'] = ($count_child > 0) ? 0 : 1;
                $insert[$key]['path_end_client_id'] = $client_id;
                $insert[$key]['created_at'] = Carbon::now();
                $insert[$key]['updated_at'] = Carbon::now();
            }
            \Log::info('==============添加用户树形结构j记录' . $client_id . "++++++++" . json_encode($insert, JSON_UNESCAPED_UNICODE) . '==========');
            \DB::table('client_link_treepaths')->insert($insert);
            //插入自身的一条记录
            $client_record = \DB::table('client_link_treepaths')->where([
                'path_end_client_id' => $client_id,
                'path_begin_client_id' => $client_id
            ]);
            if ($client_record->count() == 0) {
                $this->client->insertSelfNode($client_id);
            }
            // 更新parent_id
            $res = Client::where("id", $client_id)->update(['parent_id' => $parent_id]);

            if ($res) {
                \Log::info($client_id . '的pareent_id更新成功为' . $parent_id);
            }
            //添加 推广人的冻结资金
//                    $this->updateFrozenAmount($client_id,$parent_id);
        } else {
            $this->client->insertSelfNode($client_id);
        }
    }

    //递归更新
    public function updateChids($client_id, $my_childs)
    {
        // 更改下级关系
        if (count($my_childs) > 0) {
            array_push($my_childs, $client_id);
            \DB::table('client_link_treepaths')->whereIn(
                'path_end_client_id', $my_childs
            )->delete();

            foreach ($my_childs as $id) {
                $my_childs = \DB::table('client_link_treepaths')->select('path_end_client_id')->where([
                    'path_begin_client_id' => $id,
                ])->where('dist', '>', 0)->pluck('path_end_client_id')->toArray();
                \Log::info("========WOde ID" . \GuzzleHttp\json_encode($id));
                \Log::info("========wwewwew" . \GuzzleHttp\json_encode($my_childs));
                $this->updateParent($id, $client_id, count($my_childs));
                $this->updateChids($id, $my_childs);
            }
        }
    }

    public function refund()
    {
        $this->pay->refund(6);
    }

}
