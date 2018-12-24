<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('index');
});

Auth::routes();

Route::get('/adminHome', 'HomeController@index')->name('home');

//...
Route::group(['middleware' => ['web']], function () {
    //订单
    Route::get('/user',"HomeController@getToken");
    Route::resource('order','Order\OrderController');
    Route::post('orders/update_status','Order\OrderController@updateStatus')->name('order.update-status');

    //发货
    Route::get('orders/delivery/{order_id}','Order\OrderController@editDelivery')->name('order.eidt-delivery');
    Route::post('orders/update_delivery','Order\OrderController@updateDelivery')->name('order.update-delivery');

});

Route::any('getUnionId','Wechat\WechatController@mini');
Route::any('getUsers','Wechat\WechatController@getClientFromOfficial');
Route::any('pullUserList','Wechat\WechatController@getUnionIdByOpenId');
Route::any('getQrcode', 'Wechat\WechatController@getQrcode');
Route::any('home', '\App\Api\Controllers\Home\HomeController@index');
Route::any('category_gppds','\App\Api\Controllers\Goods\GoodController@categoryGoodsList');
Route::any('good_detail','\App\Api\Controllers\Goods\GoodController@index');
Route::get('spread_list', 'Employee\EmplyeeController@spreadList');
Route::get('employee_list', 'Employee\EmplyeeController@all');

Route::post('banner/upload', 'Banner\BannerController@upload');
Route::post('good/upload', 'Good\GoodController@uploadImg');

// 个人中心



