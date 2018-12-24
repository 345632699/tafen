<?php

namespace App\Http\Controllers\Wechat;

use App\Model\Client;
use App\Repositories\Client\ClientRepository;
use Carbon\Carbon;
use Dingo\Api\Routing\Helpers;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use JWTAuth;

class WechatController extends Controller
{
    use Helpers;

    /**
     * WechatController constructor.
     */
    public function __construct(ClientRepository $client)
    {
        $this->client = $client;
    }

    public function mini(Request $request){
        $code = $request->code;
        $iv = $request->iv;
        $encryptedData = $request->encryptedData;
        $parent_id = $request->get('parent_id', 0);
        if (!isset($code)){
            return response('參數錯誤');
        }
        $app = app('wechat.mini_program');
        $res = $app->auth->session($code);
        $decryptedData = $app->encryptor->decryptData($res['session_key'], $iv, $encryptedData);
        \Log::info("decryptedData解密信息===========================");
        \Log::info(json_encode($decryptedData));
        $openId = $decryptedData['openId'];
        $unionId = $decryptedData['unionId'];

        $client = Client::where('open_id',$openId)->first();
        if (!$client){
            $newUser = [
                'union_id' => '',
                'nick_name' => $decryptedData['nickName'],
                'password' => bcrypt("admin123"),
                'avatar_url' => $decryptedData['avatarUrl'],
                'open_id' => $decryptedData['openId'],
                'union_id' => $decryptedData['unionId'],
                'gender' => $decryptedData['gender'],
                'parent_id' => $parent_id,
            ];
            $client = Client::create($newUser);
            $this->client->updateTreeNode($client->id, $parent_id);
            // 创建资金账户
            $amount = [
                'client_id' => $client->id,
                'amount' => 0,
                'freezing_amount' => 0,
            ];
            \DB::table('client_amount')->insert($amount);
        } else {
            if ($client->union_id == null){
                Client::where('open_id', $openId)->update([
                    'union_id' => $unionId
                ]);
            }
            if ($client->parent_id == 0 && $parent_id > 0) {
                Client::where('open_id', $openId)->update([
                    'parent_id' => $parent_id
                ]);
            }
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

    public function getClientFromOfficial(){
        $res = \DB::table("open_id_list")->orderBy('id','desc')->get()->first();
        $nextOpenId = $res->open_id;
        $app = app('wechat.official_account');
        $users = $app->user->list($nextOpenId);
        if ($users['next_openid'] != null){
            $openIdList = $users['data']["openid"];
            $list = [];
            foreach ($openIdList as $openId){
                $list[] = [
                    'open_id' => $openId,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now()
                ];
            }
            $res = \DB::table("open_id_list")->insert($list);
            if ($res){
                \Log::info("拉取OPENID执行完毕");
            }
        }else{
            dd("暂无需要执行的内容");
        }

    }
}
