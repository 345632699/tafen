<?php

namespace App\Http\Controllers\Employee;

use App\Client;
use App\Model\Order;
use App\Model\OrderDetail;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class EmplyeeController extends Controller
{
    public function all()
    {
        $employee_list = Client::where('agent_type_id', 10)->get();
        dd($employee_list);
    }

    public function spreadList(Request $request)
    {
        $employee_id = $request->get('employee_id', 37);
        $month = $request->get('month', date('m'));
        // 查询所有员工的推广记录
        $client_ids = \DB::table('client_link_treepaths')
            ->select('path_end_client_id as client_id')
            ->where('dist', '>', '0')
            ->where('path_begin_client_id', $employee_id)->get()->pluck('client_id')->toArray();
        $order = Order::select('ol.*', 'clients.nick_name', 'goods.name', 'attr.name as attr_name', 'agm.name as attr_val')
            ->whereIn('client_id', $client_ids)
            ->leftJoin('clients', 'id', '=', 'client_id')
            ->rightJoin('order_lines as ol', 'ol.header_id', '=', 'order_headers.uid')
            ->leftJoin('goods', 'goods.uid', '=', 'ol.good_id')
            ->leftJoin('attr_good_mapping as agm', 'agm.id', '=', 'ol.attr_good_mapping_id')
            ->leftJoin('attributes as attr', 'attr.id', '=', 'agm.attr_id')
            ->whereMonth('ol.created_at', $month)->get();
        dd($order);
    }
}
