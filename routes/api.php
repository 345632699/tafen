<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});
Route::group(['middleware' => ['auth:api']], function () {
    Route::get('/clientList', 'Client\ClientController@getList');
    Route::post('/client/update', 'Client\ClientController@update');
    Route::get('/banner/goods', 'Banner\BannerController@getGoodList');
    Route::post('/banner/create', 'Banner\BannerController@create');
    Route::post('/banner/update', 'Banner\BannerController@update');
    Route::post('/banner/delete', 'Banner\BannerController@deleteBanner');
    Route::get('/banner/list', 'Banner\BannerController@getList');
});
Route::get('/good/getAttr', 'Good\GoodController@goodAttr');
Route::post('/good/create', 'Good\GoodController@create');
Route::get('/withdraw/list', 'Client\ClientController@withDrawList');
Route::post('/withdraw/operate', 'Client\ClientController@withdrawOperate');
Route::get('/return/list', 'Client\ClientController@returnList');
Route::post('/return/operate', 'Client\ClientController@confirmReturn');
//Route::get('/refund', 'Client\ClientController@refund');
Route::get('/spread/list', 'Spread\SpreadController@getList');
Route::post('/spread/update', 'Spread\SpreadController@updateRecord');

$api = app('Dingo\Api\Routing\Router');
$api->version('v1', function ($api) {
    $api->group(['namespace' => 'App\Api\Controllers', 'middleware' => ['client.change']], function ($api) {
        $api->post('user/login', 'AuthController@authenticate');  //登录授权
        $api->post('user/register', 'AuthController@register');
        $api->group(['middleware' => 'jwt.auth'], function ($api) {

            //路径为 /api/tests
            $api->get('tests', 'TestsController@index');
            //请求方式：
            //http://localhost:8000/api/tests?token=xxxxxx  (从登陆或注册那里获取,目前只能用get)
            $api->get('tests/{id}', 'TestsController@show');
            $api->post('user/me', 'AuthController@AuthenticatedUser'); //根据
        });


        //首页路由
        $api->get('home', 'Home\HomeController@index');
        //支付回调
        $api->any('pay/notify', 'Pay\PayController@payNotify');
        // 课程
        $api->get('lesson/list', 'Lesson\LessonController@getLessonList');

        //收货地址
        $api->group(['middleware' => ['jwt.auth', 'scope']], function ($api) {
            $api->get('address/list', 'Address\AddressController@index');
            $api->get('address/get', 'Address\AddressController@get');
            $api->post('address/create', 'Address\AddressController@create');
            $api->post('address/edit', 'Address\AddressController@edit');
            $api->post('address/delete', 'Address\AddressController@delete');

            $api->get('order/list', 'Order\OrderController@getOrderList');
            $api->get('order/get', 'Order\OrderController@get');
            $api->post('order/create', 'Order\OrderController@create');
            $api->post('order/cart', 'Order\OrderController@createFromCart'); //购物车选中下单
            $api->post('order/wxpaysdk', 'Order\OrderController@getWxPayConfig'); // 获取微信支付SDK
            $api->post('order/confirm', 'Order\OrderController@confirmReceipt');
            $api->post('order/cancel', 'Order\OrderController@cancelOrder');
            $api->get('order/search', 'Order\OrderController@getOrderList');
            $api->post('order/return', 'Order\OrderController@returnMoney');
            $api->post('order/uploadImg', 'Order\OrderController@uploadImg');
            $api->post('order/delete', 'Order\OrderController@delete');

            $api->post('cart/create', 'Cart\CartController@addToCart');
            $api->post('cart/update', 'Cart\CartController@updateCart');
            $api->post('cart/delete', 'Cart\CartController@deleteCart');
            $api->get('cart/cart_list', 'Cart\CartController@cart_list');
            $api->get('/guessLike', 'Cart\CartController@guessLike');

        });

        //用户中心
        $api->group(['middleware' => ['jwt.auth', 'scope']], function ($api) {
            $api->get('client', 'Client\ClientController@index');
            $api->get('client/check', 'Client\ClientController@checkBind');
            $api->get('client/flow_list', 'Client\ClientController@getFlowList');
            $api->get('client/amount', 'Client\ClientController@getAmount');
            // 获取推广用户列表
            $api->get('get_spread_list', 'Client\ClientController@getChild');

            //提现记录
            $api->post('pay/withdraw_list', 'Pay\PayController@getWithDrawRecordList');
            //提现
            $api->post('pay/withdraw', 'Pay\PayController@withdraw');

            //商品路由
            $api->get('good', 'Goods\GoodController@index');
            //商品搜索
            $api->get('good/search', 'Goods\GoodController@search');
            //商品分类列表
            $api->get('cat_goods', 'Goods\GoodController@categoryGoodsList');
            //获取二维码
            $api->get('getQrcode', 'Client\ClientController@getQrcode');
            //用户留言
            $api->post('client/comment', 'Client\ClientController@levaeComment');
        });
    });
});