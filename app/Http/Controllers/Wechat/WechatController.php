<?php

namespace App\Http\Controllers\Wechat;

use App\Model\Client;
use App\Repositories\Client\ClientRepository;
use Carbon\Carbon;
use Dingo\Api\Routing\Helpers;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use JWTAuth;
use function Sodium\increment;

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
            // 发送发展下线的模版消息
            $this->sendTemp($client,$parent_id);
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
        $lastOpenid = null;
        if ($res != null){
            $lastOpenid = $res->open_id;
        }
        $app = app('wechat.official_account');
        $users = $app->user->list($nextOpenId = $lastOpenid);
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

    public function getUnionIdByOpenId(){
        $lastItem = \DB::table('official_account')->orderBy('id','desc')->first();
        $id = 0;
        if ($lastItem != null){
            $open_id = $lastItem->open_id;
            $id = \DB::table("open_id_list")->where('open_id',$open_id)->first()->id;
        }
        $openIdArray = \DB::table('open_id_list')
            ->select('open_id')
            ->where('id','>',$id)
            ->limit(100)
            ->pluck('open_id')
            ->toArray();
        return $openIdArray;
    }

    public function insertAccount(){
        while (1){
            $openIds = $this->getUnionIdByOpenId();
            \Log::info(json_encode($openIds));
            if (count($openIds) > 0){
                $app = app('wechat.official_account');
                $users = $app->user->select($openIds);
                $list = [];
                foreach ($users["user_info_list"] as $user){
                    $list[] = [
                        'open_id' => $user['openid'],
                        'union_id' => $user['unionid'],
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now()
                    ];
                }
                \DB::table('official_account')->insert($list);
            }else{
                break;
            }
        }
        echo "执行完毕";
        return;
    }

    public function sendTemp($client = null,$parent_id = null){
        $official_app = app('wechat.official_account');
        $official_parent_oepnid = findOfficialOpenid($parent_id);
        if ($official_parent_oepnid != null){
            $sendData = [
                'touser' => $official_parent_oepnid,
                'template_id' => '2NiEIVPTxnuw-8EGXxAKpwomavdF5QSF67yT6xvVGQA',
                'miniprogram' => [
                    'appid'=> "wx309384160dc144df",
                    "path" => "main/main"
                ],
                'data' => [
                    'first' => '您好，'.$client->nick_name.'已经成为你的会员。',
                    'type' => "商户",
                    'address' => "她芬商城",
                    'cardNumber' => $client->id,
                    'VIPName' => $client->nick_name,
                    'VIPPhone' => "无",
                    'expDate' => "长期有效",
                    'remark' =>"感谢您的努力，点击下方查看详情"
                ],
            ];
            \Log::info('===发送模板信息===sendData', $sendData);
            $res = $official_app->template_message->send($sendData);
            \Log::info('===发送模板信息===resData' . json_encode($res));
        }

    }
    public function getNeWId(){
        $app = app('wechat.official_account');
        $next_openid = null;
        while (1){
            $users = $app->user->list($nextOpenId = $next_openid);
            if ($users['next_openid'] != null){
                $openIdList = $users['data']["openid"];
                $exist_openIdList = \DB::table('open_id_list')->pluck("open_id")->toArray();
                $arr = array_diff($openIdList,$exist_openIdList);
                $list = [];
                foreach ($arr as $openId){
                    $list[] = [
                        'open_id' => $openId,
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now()
                    ];
                }
                \DB::table("open_id_list")->insert($list);
                $next_openid = $users['next_openid'];
            }else{
                \Log::info("拉取OPENID执行完毕");
                break;
            }
        }
    }

    //全表更新
    public function updateAllOfficialInfo() {
        try{
            \DB::table('open_id_list')->truncate();
            \DB::table('official_account')->truncate();
            $this->getClientFromOfficial();
            $this->insertAccount();
            return resJson([],1,'成功');
        }catch (\Exception $e){
            return resJson([],0,$e->getMessage());
        }
    }

    //部分更新
    public function updatelOfficialInfo() {
        try{
            $this->getNeWId();
            $this->insertAccount();
            return resJson([],1,'成功');
        }catch (\Exception $e){
            return resJson([],0,$e->getMessage());
        }
    }
}
