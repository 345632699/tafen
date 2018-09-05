<?php

namespace App\Api\Controllers\Goods;

use App\Api\Controllers\BaseController;
use App\Model\Attribute;
use App\Model\Category;
use App\Model\Client;
use App\Model\Good;
use App\Repositories\Client\ClientRepository;
use App\Repositories\Good\GoodRepository;
use Illuminate\Http\Request;
use Mockery\Exception;

class GoodController extends BaseController
{
    public function __construct(GoodRepository $goods,ClientRepository $client)
    {
        $this->goods = $goods;
        $this->client = $client;
    }

    /**
     * @api {get} /good 获取商品详情
     * @apiName 获取商品详情
     * @apiGroup Good
     *
     * @apiHeader (Authorization) {String} authorization header头需要添加bearer 示例{BEARER eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJzdWIiOjEzLCJpc3MiOiJodHRwczovL2RqLm1xcGhwLmNvbS9hcGkvdXNlci9sb2dpbiIsImlhdCI6MTUzNDI0ODMyMywiZXhwIjoxNTM2ODQwMzIzLCJuYmYiOjE1MzQyNDgzMjMsImp0aSI6Ik1hNjRKTTVFZDBlRTIyTXQifQ.NMNn4BUCVV6xg3s5oIvDAjuwVSdDCxRBLXidoMJAzqw}
     *
     * @apiSuccess {float} discount_price 商城价格 折后价格 当is_coupon为1 且这个字段不为null时显示折扣价，该价格与代理折扣价格不会同时存在 (选择规格时，以规格中的属性价格为准)
     * @apiSuccess {float} original_price 原价格 默认返回的价格 未选择规格参数时 默认展示该价格 (选择规格时，以规格中的属性价格为准)
     * @apiSuccess {float} agent_price 一级代理价格 当为null时显示原价 不显示代理折扣价 (选择规格时，以规格中的属性价格为准)
     * @apiSuccess {float} last_price 最终用于支付的价格 (选择规格时，以规格中的属性价格为准)
     * @apiSuccess {int} stock 库存 (选择规格时，以规格中的属性价格为准)
     * @apiSuccess {int} already_sold 已销售数量 (选择规格时，以规格中的属性价格为准)
     * @apiSuccess {int} category_id 分类ID
     * @apiSuccess {int} is_onsale 是否上家
     * @apiSuccess {int} is_new 是否是新品
     * @apiSuccess {int} is_hot 是否热卖
     * @apiSuccess {float} delivery_fee 运费
     * @apiSuccess {int} is_coupon 是否是优惠专区的商品
     * @apiSuccess {int} thumbnail_img 商品缩略图
     * @apiSuccess {Array} attributes 商品属性
     * @apiSuccess {Array} detail_imgs 详情图片
     * @apiSuccess {Array} detail_imgs 详情图片
     * @apiSuccess {banner_imgs} detail_imgs bannerr轮播图片
     *
     * @apiSuccess {Array} data 返回的数据结构体
     * @apiSuccess {Number} status  1 执行成功 0 为执行失败
     * @apiSuccess {string} msg 执行信息提示
     *
     * @apiSuccessExample Success-Response:
     * {
    "response": {
    "data": {
    "uid": 1,
    "name": "她芬美国甜橙精油 100ml",
    "description": "好眠 健脾胃 开心喜悦",
    "discount_price": null,
    "original_price": 131,
    "stock": 280,
    "already_sold": 2143,
    "combos_id": 0,
    "update_time": "2018-09-05 08:22:11",
    "category_id": 2,
    "is_onsale": 1,
    "is_new": 0,
    "is_hot": 0,
    "is_agent_type": 0,
    "agent_type_id": 0,
    "delivery_fee": 0,
    "is_coupon": 0,
    "thumbnail_img": "http://img5.imgtn.bdimg.com/it/u=77511056,783740313&fm=27&gp=0.jpg",
    "attribute_id": "2",
    "agent_price": "104.80",
    "last_price": "26.20",
    "attributes": {
    "name": "规格",
    "list": [
    {
    "title": "规格",
    "id": 1,
    "attr_id": 2,
    "good_id": 1,
    "name": "100ml",
    "original_price": 200,
    "stock": 100,
    "discount_price": null,
    "is_coupon": null,
    "agent_price": "40.00",
    "last_price": "40.00"
    },
    {
    "title": "规格",
    "id": 2,
    "attr_id": 2,
    "good_id": 1,
    "name": "200ml",
    "original_price": 300,
    "stock": 200,
    "discount_price": null,
    "is_coupon": null,
    "agent_price": "60.00",
    "last_price": "60.00"
    },
    {
    "title": "规格",
    "id": 3,
    "attr_id": 2,
    "good_id": 1,
    "name": "300ml",
    "original_price": 400,
    "stock": 300,
    "discount_price": null,
    "is_coupon": null,
    "agent_price": "80.00",
    "last_price": "80.00"
    },
    {
    "title": "规格",
    "id": 4,
    "attr_id": 2,
    "good_id": 1,
    "name": "500ml",
    "original_price": 500,
    "stock": 400,
    "discount_price": null,
    "is_coupon": null,
    "agent_price": "100.00",
    "last_price": "100.00"
    }
    ]
    },
    "detail_imgs": [
    "https://img.alicdn.com/imgextra/i2/125331858/TB2IC6EcW9I.eBjy0FeXXXqwFXa_!!125331858.jpg",
    "https://img.alicdn.com/imgextra/i2/125331858/TB2dqHZcYOJ.eBjy1XaXXbNupXa_!!125331858.jpg",
    "https://img.alicdn.com/imgextra/i3/125331858/TB2QT51dOKO.eBjSZPhXXXqcpXa_!!125331858.jpg",
    "https://img.alicdn.com/imgextra/i2/125331858/TB26A6GcY5K.eBjy0FfXXbApVXa_!!125331858.jpg",
    "https://img.alicdn.com/imgextra/i4/125331858/TB2XPLOcZeK.eBjSszgXXczFpXa_!!125331858.jpg",
    "https://img.alicdn.com/imgextra/i1/125331858/TB2bRbGcY5K.eBjy0FfXXbApVXa_!!125331858.jpg",
    "https://img.alicdn.com/imgextra/i3/125331858/TB2Ss.cc89J.eBjy0FoXXXyvpXa_!!125331858.jpg",
    "https://img.alicdn.com/imgextra/i1/125331858/TB2I1vOc4mJ.eBjy0FhXXbBdFXa_!!125331858.jpg"
    ],
    "banner_imgs": [
    "https://timgsa.baidu.com/timg?image&quality=80&size=b9999_10000&sec=1534817782870&di=55881e397b54808ac7ec51d6426a8d1d&imgtype=0&src=http%3A%2F%2Fimgsrc.baidu.com%2Fimgad%2Fpic%2Fitem%2Fd6ca7bcb0a46f21fe34bb64efc246b600c33aeb4.jpg",
    "https://timgsa.baidu.com/timg?image&quality=80&size=b9999_10000&sec=1534817782868&di=b7954b9a3defcd6e43221f2fbb67b9e1&imgtype=0&src=http%3A%2F%2Fimgsrc.baidu.com%2Fimage%2Fc0%253Dpixel_huitu%252C0%252C0%252C294%252C40%2Fsign%3D6378f8d1b73eb13550cabffbcf66cdbf%2Ffd039245d688d43f64e645cb761ed21b0ef43bb8.jpg",
    "https://timgsa.baidu.com/timg?image&quality=80&size=b9999_10000&sec=1534817782867&di=a195c179dcb3aca3bf2c65fee41c79e1&imgtype=0&src=http%3A%2F%2Fimgsrc.baidu.com%2Fimgad%2Fpic%2Fitem%2F63d0f703918fa0ec1a1a42282c9759ee3d6ddb31.jpg"
    ]
    },
    "status": 1,
    "msg": "success"
    }
    }
     */
    public function index(Request $request) {
        $good_id = $request->get('good_id',1);
        $goods = $this->goods->getGood($good_id);
        return $goods;
    }

    /**
     * @api {get} /cat_goods 获取分类商品列表
     * @apiName 获取分类商品列表
     * @apiGroup Good
     *
     * @apiHeader (Authorization) {String} authorization header头需要添加bearer 示例{BEARER eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJzdWIiOjEzLCJpc3MiOiJodHRwczovL2RqLm1xcGhwLmNvbS9hcGkvdXNlci9sb2dpbiIsImlhdCI6MTUzNDI0ODMyMywiZXhwIjoxNTM2ODQwMzIzLCJuYmYiOjE1MzQyNDgzMjMsImp0aSI6Ik1hNjRKTTVFZDBlRTIyTXQifQ.NMNn4BUCVV6xg3s5oIvDAjuwVSdDCxRBLXidoMJAzqw}
     *
     *
     * @apiParam {int} cat_id 分类ID
     * @apiParam {int} limit 分页条数
     * @apiParam {int} page 页码
     * @apiSuccess {int} index_display 是否首页显示  1显示  0 为不显示
     * @apiSuccess {String} cat_icon_img 预留字段 分类图标
     * @apiSuccess {String} jump_url 分类点解跳转url 备选择端
     * @apiSuccess {Array} category 分类详情
     * @apiSuccess {Array} good_list 商品列表
     * @apiSuccess {int} total 记录总数
     * @apiSuccess {int} last_page 最后的页码
     * @apiSuccess {int} per_page 每页显示条数 默认5 可以通过传limit改变
     * @apiSuccess {float} discount_price 商城价格 折后价格 当is_coupon为1 且这个字段不为null时显示折扣价，该价格与代理折扣价格不会同时存在 (选择规格时，以规格中的属性价格为准) 单位为分
     * @apiSuccess {float} original_price 原价格 默认返回的价格 未选择规格参数时 默认展示该价格 (选择规格时，以规格中的属性价格为准) 单位为分
     * @apiSuccess {int} last_price 最后计算价格 实际用于支付使用的价格 (选择规格时，以规格中的属性价格为准) 单位为分
     * @apiSuccess {int} agent_price 一级代理价格 当为null时显示原价 不显示代理折扣价 (选择规格时，以规格中的属性价格为准) 单位为分
     * @apiSuccess {int} stock 库存 (选择规格时，以规格中的属性价格为准)
     * @apiSuccess {int} already_sold 已销售数量 (选择规格时，以规格中的属性价格为准)
     * @apiSuccess {int} category_id 分类ID
     * @apiSuccess {int} is_onsale 是否上家
     * @apiSuccess {int} is_new 是否是新品
     * @apiSuccess {int} is_hot 是否热卖
     * @apiSuccess {float} delivery_fee 运费
     * @apiSuccess {int} is_coupon 是否是优惠专区的商品
     * @apiSuccess {int} thumbnail_img 商品缩略图
     * @apiSuccess {Array} data 返回机构体
     * @apiSuccess {Number} status  1 执行成功 0 为执行失败
     * @apiSuccess {string} msg 执行信息提示
     *
     * @apiSuccessExample Success-Response:
     *{
    "response": {
    "data": {
    "category": {
    "id": 1,
    "name": "默认分类",
    "cat_banner": "https://timgsa.baidu.com/timg?image&quality=80&size=b9999_10000&sec=1534786474535&di=b694f92d900be6065127c018026c556a&imgtype=0&src=http%3A%2F%2Fimgsrc.baidu.com%2Fimgad%2Fpic%2Fitem%2F9358d109b3de9c82e881a8126681800a18d84342.jpg",
    "index_display": 0,
    "cat_icon_img": null,
    "jump_url": null,
    "created_at": null,
    "updated_at": "2018-08-20 14:47:52"
    },
    "good_list": {
    "current_page": 1,
    "data": [
    {
    "uid": 2,
    "name": "她芬优惠产品",
    "description": "测试用例",
    "discount_price": 80,
    "original_price": 130,
    "stock": 300,
    "already_sold": 12121,
    "combos_id": 0,
    "update_time": "2018-09-05 08:22:12",
    "category_id": 1,
    "is_onsale": 1,
    "is_new": 0,
    "is_hot": 0,
    "is_agent_type": 0,
    "agent_type_id": 0,
    "delivery_fee": 5,
    "is_coupon": 1,
    "thumbnail_img": "http://img5.imgtn.bdimg.com/it/u=77511056,783740313&fm=27&gp=0.jpg",
    "attribute_id": "1",
    "agent_price": null,
    "last_price": 80,
    "attributes": {
    "name": "套餐",
    "list": [
    {
    "title": "套餐",
    "id": 5,
    "attr_id": 1,
    "good_id": 1,
    "name": "test",
    "original_price": 100,
    "stock": 100,
    "discount_price": null,
    "is_coupon": null,
    "agent_price": "20.00",
    "last_price": "20.00"
    }
    ]
    }
    }
    ],
    "from": 1,
    "last_page": 1,
    "next_page_url": null,
    "path": "http://www.tafen.com/api/cat_goods",
    "per_page": 10,
    "prev_page_url": null,
    "to": 1,
    "total": 1
    }
    },
    "status": 1,
    "msg": "success"
    }
    }
     */
    public function categoryGoodsList(Request $request){
        $limit = $request->get('limit',5);
        $cat_id = $request->get('cat_id',1);
        $is_coupon = $request->get('is_coupon',0);
        //是否是优惠产品
        $where = [];
        if ($cat_id) {
            $where['category_id'] = $cat_id;
        }
        // 需要获取优惠产品才生效
        if ($is_coupon) {
            $where['is_coupon'] = $is_coupon;
        }
        $category = Category::find($cat_id);
        $good_list = Good::where($where)->paginate($limit);
        foreach($good_list as $good){
            //代理价格
            $client_id = session('client.id');
            if ($client_id) {
                $res = Client::select('discount_rate')->leftJoin('agent_type','agent_type.id','=','agent_type_id')
                    ->where('clients.id',$client_id)->first();
                $rate = $this->client->getAgentRate($client_id);
                if(isset($res->discount_rate) && $good->is_coupon <= 0){
                    $good->agent_price = ($good->original_price * (100 - $res->discount_rate) / 100);
                }else{
                    $good->agent_price = null;
                }
                if ($good->is_coupon){
                    $good->last_price = $good->discount_price;
                }else{
                    $good->last_price = ($rate * $good->original_price / 100);
                }
                $attributes = Attribute::select('attributes.name as title','agm.*')->where('attributes.id',$good->attribute_id)
                        ->rightJoin('attr_good_mapping as agm','agm.attr_id','=','attributes.id')->get();
                foreach ($attributes as $item){
                    $item->agent_price = $rate == 100 ? null : $item->original_price * $rate / 100;
                    if ($item->is_coupon){
                        $item->last_price = $item->discount_price;
                    }else{
                        $item->last_price = ($rate * $item->original_price / 100);
                    }
                }
                if($attributes->count()){
                    $good->attributes = [
                        'name'=>$attributes[0]->title,
                        'list'=>$attributes
                    ];
                }
            }
        }
        $resData['category'] = $category;
        $resData['good_list'] = $good_list;
        return response_format($resData);
    }
}
