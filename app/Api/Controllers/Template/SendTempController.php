<?php

namespace App\Api\Controllers\Template;

use App\Api\Controllers\BaseController;
use App\Model\Client;
use App\Model\Good;
use EasyWeChat\Payment\Application;
use Illuminate\Http\Request;


class SendTempController extends BaseController
{
    public function newOrder($order_lines,$client_id,$parent_id){
        $official_app = app('wechat.official_account');
        $official_parent_oepnid = findOfficialOpenid($parent_id);
        $official_client_oepnid = findOfficialOpenid($client_id);
        $client = Client::find($client_id);
        $good_name = ''; //产品名称
        $good_num = ''; //产品数量
        $order_price = ''; //产品数量
        $time = ''; //产品数量
        foreach ($order_lines as $order_line) {
            $good_name .= Good::find($order_line->good_id)->first()->name.",";
            $good_num .= Good::find($order_line->good_id)->first()->name."x".$order_line->quantity.",";
            $order_price .= Good::find($order_line->good_id)->first()->name.":".($order_line->total_price / 100)."元,";
            $time = $order_line->created_at;
        }
        if ($official_parent_oepnid != null){
            $sendData = [
                'touser' => $official_parent_oepnid,
                'template_id' => '8QBuwuBrqHVJ935lVo3dJ5egO31i_m1XItEww7BCGns',
                'path' => 'index',
                'data' => [
                    'first' => '恭喜您的下线'.$client->nick_name.'下单成功。',
                    'keyword1' => $good_name,
                    'keyword2' => $good_num,
                    'keyword3' => $order_price,
                    'keyword4' => $time,
                    'keyword5' =>"包邮",
                    'remark' =>"更多优惠项目，详情咨询客服"
                ],
            ];
            \Log::info('===发送模板信息===sendData', $sendData);
            $res = $official_app->template_message->send($sendData);
            \Log::info('===发送模板信息===resData' . json_encode($res));
        }
        if ($official_client_oepnid != null) {
            $sendData['data']['first'] = '恭喜'.$client->nick_name.'下单成功。';
            \Log::info('===发送模板信息===sendData', $sendData);
            $res = $official_app->template_message->send($sendData);
            \Log::info('===发送模板信息===resData' . json_encode($res));
        }
    }
}
