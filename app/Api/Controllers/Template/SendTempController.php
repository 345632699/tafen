<?php

namespace App\Api\Controllers\Template;

use App\Api\Controllers\BaseController;
use App\Model\Client;
use App\Model\Good;
use Carbon\Carbon;

class SendTempController extends BaseController
{
    public function newOrder($order_lines, $client_id, $parent_id,$type = 0)
    {
        $official_app = app('wechat.official_account');
        $official_parent_oepnid = findOfficialOpenid($parent_id);
        $official_client_oepnid = findOfficialOpenid($client_id);
        $client = Client::find($client_id);
        $good_name = ''; //产品名称
        $good_num = ''; //产品数量
        $order_price = ''; //产品数量
        foreach ($order_lines as $order_line) {
            \Log::info("order_line: =========================",json_encode($order_line));
            $name = Good::find($order_line->good_id)->first()->name;
            $good_name .= $name . ",";
            $good_num .= $name . "x" . $order_line->quantity . ",";
            $order_price .= $name . ":" . ($order_line->total_price / 100) . "元,";
        }
        if ($official_parent_oepnid != null) {
            if ($type > 0){
                $level = "二级";
            }else{
                $level = "一级";
            }
            $sendData = [
                'touser' => $official_parent_oepnid,
                'template_id' => '8QBuwuBrqHVJ935lVo3dJ5egO31i_m1XItEww7BCGns',
                'miniprogram' => [
                    'appid'=> "wx309384160dc144df",
                    "path" => "main/main"
                ],
                'data' => [
                    'first' => '恭喜您的'.$level.'下线' . $client->nick_name . '下单成功。',
                    'keyword1' => "她芬精油",
                    'keyword2' => rtrim($good_name, ",") ,
                    'keyword3' => Carbon::now(),
                    'keyword4' =>  rtrim($order_price,','),
                    'keyword5' => "已付款",
                    'remark' => "更多优惠项目，详情咨询客服"
                ],
            ];
            \Log::info('===发送模板信息===sendData', $sendData);
            $res = $official_app->template_message->send($sendData);
            \Log::info('===发送模板信息===resData' . json_encode($res));
        }
        if ($official_client_oepnid != null && $type == 0) {
            $sendData = [
                'touser' => $official_client_oepnid,
                'template_id' => '8QBuwuBrqHVJ935lVo3dJ5egO31i_m1XItEww7BCGns',
                'miniprogram' => [
                    'appid'=> "wx309384160dc144df",
                    "path" => "main/main"
                ],
                'data' => [
                    'first' => '恭喜' . $client->nick_name . '下单成功。',
                    'keyword1' => "她芬精油",
                    'keyword2' => rtrim($good_name, ",") ,
                    'keyword3' => Carbon::now(),
                    'keyword4' => rtrim($order_price,','),
                    'keyword5' => "已付款",
                    'remark' => "更多优惠项目，详情咨询客服"
                ]
            ];
            \Log::info('===发送模板信息===sendData', $sendData);
            $res = $official_app->template_message->send($sendData);
            \Log::info('===发送模板信息===resData' . json_encode($res));
        }
    }
}
