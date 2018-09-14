<?php

namespace App\Api\Controllers\Client;

use App\Api\Controllers\BaseController;
use App\Model\Client;
use App\Model\Delivery;
use App\Model\Order;
use App\Repositories\Client\ClientRepository;
use Illuminate\Http\Request;


class ClientController extends BaseController
{

    private $client;
    public function __construct(ClientRepository $client)
    {
        $this->client = $client;
    }

    /**
     * @api {get} /client 用户详情
     * @apiName 用户详情
     * @apiGroup Client
     *
     * @apiHeader (Authorization) {String} authorization header头需要添加bearer 示例{BEARER eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJzdWIiOjEzLCJpc3MiOiJodHRwczovL2RqLm1xcGhwLmNvbS9hcGkvdXNlci9sb2dpbiIsImlhdCI6MTUzNDI0ODMyMywiZXhwIjoxNTM2ODQwMzIzLCJuYmYiOjE1MzQyNDgzMjMsImp0aSI6Ik1hNjRKTTVFZDBlRTIyTXQifQ.NMNn4BUCVV6xg3s5oIvDAjuwVSdDCxRBLXidoMJAzqw}
     *
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
     *     {
     *          {
     *               "response": {
     *                   "data": {
     *                       "nick_name": "Cyu",
     *                       "phone_num": null,
     *                       "avatar_url": "https://wx.qlogo.cn/mmopen/vi_32/TQRoibX72mRzib5Uf1Kk2uRuPQNsthc6p1JIQibEHQcHWQiaoTkzpJOrf4dnYAeicic4X3k12skIUSJEWEbeINfDGmWg/132",
     *                       "amount": null,
     *                       "freezing_amount": null,
     *                       "default_address_id": {
     *                           "uid": 80,
     *                           "client_id": 13,
     *                           "name": "蔡诗茵",
     *                           "phone_num": 13415398357,
     *                           "province": "广东省",
     *                           "city": "深圳市",
     *                           "area": "盐田区",
     *                           "address": "盐田区有很多盐和田的一个区没有去过盐田区这是一个很长很长的地址长到要换行行行",
     *                           "default_flag": "Y",
     *                           "created_at": "2018-08-06 02:23:39",
     *                           "updated_at": "2018-08-15 04:33:48"
     *                       },
     *                       "wait_pay": 5
     *                   },
     *                   "status": 1,
     *                   "msg": "success"
     *               }
     *           }
     *     }
     *
     */
    public function index() {
        $client_id = $this->client->getUserByOpenId()->id;
        $client = Client::select('nick_name','clients.phone_num','avatar_url','amount','freezing_amount')
                        ->leftJoin('client_amount','client_id','=','clients.id')
                        ->where('id',$client_id)
                        ->get()->first();
        $address_id = \DB::table('client_delivery_contact')
                        ->where('client_id',$client_id)
                        ->Where('default_flag','Y')
                        ->first();
        $client->default_address_id = $address_id;

        //待支付
        $wait_pay = Order::where(['client_id'=>$client_id,'order_status'=>0])->count();
        $client->wait_pay = intval($wait_pay);

        return response_format($client);
    }

    /**
     * 更新父子节点信息
     */
    public function updateTreeNode(Request $request){

    }

    public function checkBind(){
        $client_id = $this->client->getUserByOpenId()->id;
        $result = $this->client->checkBind($client_id);
        if ($result){
            return response_format(['has_bind_robot'=>1]);
        }else{
            return response_format(['has_bind_robot'=>0],0);
        }
    }

    /**
     * @api {get} /client/flow_list 资金变更流水
     * @apiName 资金变更流水
     * @apiGroup Client
     *
     * @apiHeader (Authorization) {String} authorization Authorization value.
     *
     * @apiParam {int} type 提现类型 1 增加冻结金额 2 可提现金额减少 3 减少冻结金额 4 可提现金额增加
     * @apiParam {int} limit 返回条数
     *
     */
    public function getFlowList(Request $request){
        $limit = $request->get('limit',20);
        $client_id = $this->client->getUserByOpenId()->id;
        $type = $request->get('type',0);
        $where['client_id'] = $client_id;
        if ($type) {
            $where['type'] = $type;
        }
        $flow_list = \DB::table('client_amount_flow')
            ->select('clients.nick_name as child_name','client_amount_flow.*')
            ->leftJoin('clients','clients.id','=','child_id')
            ->where($where)
            ->orderBy('uid','desc')
            ->limit($limit)->get();
        return response_format($flow_list);
    }

    /**
     * @api {get} /api/get_spread_list 获取推广列表
     * @apiName get_spread_list 获取推广列表
     * @apiGroup Client
     *
     * @apiHeader (Authorization) {String} authorization header头需要添加bearer 示例{BEARER eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJzdWIiOjEzLCJpc3MiOiJodHRwczovL2RqLm1xcGhwLmNvbS9hcGkvdXNlci9sb2dpbiIsImlhdCI6MTUzNDI0ODMyMywiZXhwIjoxNTM2ODQwMzIzLCJuYmYiOjE1MzQyNDgzMjMsImp0aSI6Ik1hNjRKTTVFZDBlRTIyTXQifQ.NMNn4BUCVV6xg3s5oIvDAjuwVSdDCxRBLXidoMJAzqw}
     *
     * @apiParam {int} [client_id]  用户ID 默认不传获取个人的推广列表  当传了值后 获取该ID值的用户的推广列表
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
     *{
     * "response": {
     * "data": {
     * "one": [
     * {
     * "id": 17,
     * "nick_name": "李国聪",
     * "phone_num": null,
     * "gender": 1,
     * "avatar_url": "https://wx.qlogo.cn/mmopen/vi_32/UohvvvhVOpa7giaRmeC3dfgvHNlOuNwydPflMz5E3qVyaAIwNdjves9xUIxiam3WbYK3qAk96c7Of7qYuxYjgsOQ/132",
     * "updated_at": "2018-09-14 13:37:40",
     * "created_at": "2018-08-15 03:44:19",
     * "parent_id": 16,
     * "agent_type_id": null
     * }
     * ],
     * "two": [
     * {
     * "id": 19,
     * "nick_name": "Mask",
     * "phone_num": null,
     * "gender": 1,
     * "avatar_url": "https://wx.qlogo.cn/mmopen/vi_32/ha3icPy82SgicDsDwxsevGuF44hicNNrCd6We3q71DbuvxOjmVl5ibPbnqP5p24ddBX8QkweQQ8bXVkvibxevicVhJDg/132",
     * "updated_at": "2018-09-14 13:37:38",
     * "created_at": "2018-08-17 12:46:46",
     * "parent_id": 19,
     * "agent_type_id": null
     * }
     * ]
     * },
     * "status": 1,
     * "msg": "success"
     * }
     * }
     */

    public function getChild(Request $request)
    {
        $clientId = $this->client->getUserByOpenId()->id;
        $client_id = $request->get('client_id', $clientId);
        // 获取下一级的推广人员
        $oneIds = \DB::table('client_link_treepaths')->select('path_begin_client_id')->where([
            'path_end_client_id' => $client_id,
            'dist' => 1
        ])->get()->pluck('path_begin_client_id')->toArray();
        $oneChildList = Client::whereIn('id', $oneIds)->get();
        // 获取二级推广
        $twoIds = \DB::table('client_link_treepaths')->select('path_begin_client_id')->where([
            'path_end_client_id' => $client_id,
            'dist' => 2
        ])->get()->pluck('path_begin_client_id')->toArray();
        $twoChildList = Client::whereIn('id', $twoIds)->get();
        $data['one'] = $oneChildList;
        $data['two'] = $twoChildList;
        return response_format($data);
    }

}
