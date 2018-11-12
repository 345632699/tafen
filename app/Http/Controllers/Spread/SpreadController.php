<?php

namespace App\Http\Controllers\Spread;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class SpreadController extends Controller
{
    public function getList(Request $request)
    {
        $limit = $request->get('limit', 100);
        $flow_list = \DB::table('client_amount_flow')
            ->select('clients.nick_name', 'client_amount_flow.*')
            ->leftJoin('clients', 'clients.id', '=', 'client_amount_flow.client_id')
            ->whereIn('type', [0, 2, 3])
            ->paginate($limit);
        return resJson($flow_list);
    }

    /**
     * @param Request $request
     * 操作类型type - 1 通过审核 2 拒绝通过
     */
    public function updateRecord(Request $request)
    {
        $id = $request->id;
        $operate_type = $request->type;
        if ($operate_type == 1) {
            $update['type'] = 3;
            $msg = '审核通过';
        } else {
            $update['type'] = 0;
            $msg = '审核拒绝';
        }
        $res = \DB::table('client_amount_flow')->where('uid', $id)
            ->update($update);
        if ($res) {
            return resJson([], 1, $msg);
        }
    }
}
