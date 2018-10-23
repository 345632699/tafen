<?php

namespace App\Http\Controllers\Client;

use App\Client;
use App\Model\ClientAmount;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ClientController extends Controller
{
    public function getList() {
        $clients = Client::select('clients.*','client_amount.amount','client_amount.freezing_amount','client_amount.count_all as sum_money')
            ->leftJoin('client_amount','client_amount.client_id','=','clients.id')
            ->get();
        return $clients;
    }

    public function update(Request $request){
      $client =Client::where('id',$request->id);
      if ($client->get()){
        $update['agent_type_id'] = $request->agent_type_id;
        $updateAmount['count_all'] = $request->sum_money * 100;
        $updateAmount['amount'] = $request->amount * 100;
        $updateAmount['freezing_amount'] = $request->freezing_amount * 100;
        $res = $client->update($update);
        $res1 = ClientAmount::where('client_id',$request->id)->update($updateAmount);
        if ($res && $res1){
          return $this->resJson($client->first());
        }
      }else{
        return $this->resJson([],0,'更新失败，用户不存咋');
      }
    }

    public function resJson($data,$status = 1,$msg = 'success') {
      $res = [
        'status' =>  $status,
        'data' =>  $data,
        'msg' =>  $msg,
      ];
      return json_encode($res,JSON_UNESCAPED_UNICODE);
    }
}
