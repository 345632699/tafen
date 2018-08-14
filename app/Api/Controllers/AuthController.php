<?php

namespace App\Api\Controllers;

use App\Client;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use JWTAuth;

class AuthController extends BaseController
{
    /**
     * The authentication guard that should be used.
     *
     * @var string
     */
    public function __construct()
    {
        parent::__construct();

    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function authenticate(Request $request)
    {
        $payload = [
            'open_id' => $request->get('open_id'),
            'password' => "admin123"
        ];
        try {
            if (!$token = JWTAuth::attempt($payload)) {
                return response()->json(['error' => 'token_not_provided'], 401);
            }
        } catch (JWTException $e) {
            return response()->json(['error' => '不能创建token'], 500);
        }
        return response()->json(compact('token'));
    }

    /**
     * @param Request $request
     */
    public function register(Request $request)
    {
        $newUser = [
            'open_id' => $request->get('open_id'),
            'name' => $request->get('name'),
            'password' => bcrypt("admin123"),
            'avatar_url' => bcrypt("admin123")
        ];
        $user = Client::create($newUser);
        $token = JWTAuth::fromUser($user);
        return $token;
    }

    /****
     * 获取用户的信息
     * @return \Illuminate\Http\JsonResponse
     */
    public function AuthenticatedUser()
    {
        try {
            if (!$user = JWTAuth::parseToken()->authenticate()) {
                return response()->json(['user_not_found'], 404);
            }
        } catch (TokenExpiredException $e) {
            return response()->json(['token_expired'], $e->getStatusCode());
        } catch (TokenInvalidException $e) {
            return response()->json(['token_invalid'], $e->getStatusCode());
        } catch (JWTException $e) {
            return response()->json(['token_absent'], $e->getStatusCode());
        }
        // the token is valid and we have found the user via the sub claim
        return response()->json(compact('user'));
    }

    /**
     * @api {post} /getUnionId 获取Token
     * @apiName Wechat 获取Token及用户Id
     * @apiGroup Wechat
     *
     *
     * @apiParam {string} code wx.login接口获取到的CODE值
     * @apiParam {string} encryptedData wx.getUserInfo 获取
     * @apiParam {string} iv wx.getUserInfo 获取
     *
     * @apiSuccess {Array} data 返回的数据结构体
     * @apiSuccess {Number} client_id 用于做二级分销URL后传递的parent_id 需要本地全局缓存
     * @apiSuccess {string} token 请求接口 在header头中传递 或者在url的teken= ？ 中传递
     *
     * * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
     *     {
     *          "response": {
     *               "data": [
     *                token: "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJzdWIiOjE1LCJpc3MiOiJodHRwczovL2RqLm1xcGhwLmNvbS9nZXRVbmlvbklkIiwiaWF0IjoxNTM0MjU2MTEzLCJleHAiOjE1MzY4NDgxMTMsIm5iZiI6MTUzNDI1NjExMywianRpIjoiS1pKM1kxOTRBbjRvc0NVcSJ9.K4z-FqXcZWTGkcf8MFSvWYiAv0gRmOsDAb6jk7XGOYA",
     *                client_id: 15
     * ],
     *               "status": 1,
     *               "msg": "success"
     *          }
     *     }
     *
     *
     */
}
