<?php

namespace App\Http\Controllers\Wechat;

use App\Model\Client;
use Dingo\Api\Routing\Helpers;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use JWTAuth;

class WechatController extends Controller
{
    use Helpers;
    public function mini(Request $request){
        $code = $request->code;
        $iv = $request->iv;
        $encryptedData = $request->encryptedData;
        if (!isset($code)){
            return response('參數錯誤');
        }
        $app = app('wechat.mini_program');
        $res = $app->auth->session($code);
        $decryptedData = $app->encryptor->decryptData($res['session_key'], $iv, $encryptedData);

        $openId = $decryptedData['openId'];

        $client = Client::where('open_id',$openId)->first();
        if (!$client){
            $newUser = [
                'union_id' => '',
                'nick_name' => $decryptedData['nickName'],
                'password' => bcrypt("admin123"),
                'avatar_url' => $decryptedData['avatarUrl'],
                'open_id' => $decryptedData['openId'],
                'gender' => $decryptedData['gender'],
            ];
            $client = Client::create($newUser);
            // 创建资金账户
            $amount = [
                'client_id' => $client->id,
                'amount' => 0,
                'freezing_amount' => 0,
            ];
            \DB::table('client_amount')->insert($amount);
        }
        if (isset($client->id)){
            $token = JWTAuth::fromUser($client);
            return response_format(['token'=>$token,'client_id'=>$client->id]);
        }else{
            return response_format([],0,'授权出错',401);
        }

    }

    public function getQrcode()
    {
        $app = app('wechat.mini_program');
        $response = $app->app_code->get('page/main/main?client_id=1');
        if ($response instanceof \EasyWeChat\Kernel\Http\StreamResponse) {
            $filename = $response->saveAs(public_path('qrcode'), 'appcode.png');
            dd($filename);
        }
    }
}
