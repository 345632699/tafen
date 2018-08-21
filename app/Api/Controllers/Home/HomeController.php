<?php

namespace App\Api\Controllers\Home;

use App\Api\Controllers\BaseController;
use App\Model\Banner;
use App\Model\Category;
use App\Model\Good;
use Illuminate\Http\Request;
use Mockery\Exception;


class HomeController extends BaseController
{
    /**
     * @api {get} /home 首页
     * @apiName  首页
     * @apiGroup Home
     *
     *
     * @apiSuccess {string} index_banner 首页广告图
     * @apiSuccess {string} discount_banner 优惠专区广告视图
     * @apiSuccess {string} chief_banner 首席芳疗师广告图
     *
     * @apiSuccessExample Success-Response:
     *
     * {
    "response": {
    "data": {
    "index_banner": [
    {
    "id": 1,
    "name": "首页广告",
    "description": "首页广告轮播图",
    "banner_type_id": 1,
    "img_url": "https://timgsa.baidu.com/timg?image&quality=80&size=b9999_10000&sec=1534786199920&di=38309ff8735fe6e5cdb477a554d5d600&imgtype=0&src=http%3A%2F%2Fwww.chinairn.com%2FUserFiles%2Fimage%2F20180515%2F20180515174001_6526.jpg",
    "sort": 1,
    "is_display": 1,
    "jump_url": "http://www.baidu.com"
    },
    {
    "id": 2,
    "name": "首页广告",
    "description": "首页广告轮播图",
    "banner_type_id": 1,
    "img_url": "https://timgsa.baidu.com/timg?image&quality=80&size=b9999_10000&sec=1534786199920&di=01c513f05ed1ae1ddd7c7e0a65fb0ae7&imgtype=0&src=http%3A%2F%2Fimg.zcool.cn%2Fcommunity%2F019174562796756ac72548781d76b0.jpg",
    "sort": 2,
    "is_display": 1,
    "jump_url": "http://www.baidu.com"
    },
    {
    "id": 3,
    "name": "首页广告",
    "description": "首页广告轮播图",
    "banner_type_id": 1,
    "img_url": "https://timgsa.baidu.com/timg?image&quality=80&size=b9999_10000&sec=1534786199919&di=fbf8f009447a871685eb89ea7517207b&imgtype=0&src=http%3A%2F%2Fimgsrc.baidu.com%2Fimgad%2Fpic%2Fitem%2Fcdbf6c81800a19d800a8572139fa828ba61e466f.jpg",
    "sort": 3,
    "is_display": 1,
    "jump_url": "http://www.baidu.com"
    },
    {
    "id": 4,
    "name": "首页广告",
    "description": "首页广告轮播图",
    "banner_type_id": 1,
    "img_url": "https://timgsa.baidu.com/timg?image&quality=80&size=b9999_10000&sec=1534786199919&di=f70d2ebb507fa06096ade7e5954c22c8&imgtype=0&src=http%3A%2F%2Fimgsrc.baidu.com%2Fimage%2Fc0%253Dpixel_huitu%252C0%252C0%252C294%252C40%2Fsign%3Df04a80129616fdfacc61ceaeddf7e938%2Fbd3eb13533fa828bf1bbe6daf61f4134970a5a6a.jpg",
    "sort": 4,
    "is_display": 1,
    "jump_url": "http://www.baidu.com"
    }
    ],
    "discount_banner": [
    {
    "id": 5,
    "name": "优惠专区",
    "description": "优惠专区广告图",
    "banner_type_id": 2,
    "img_url": "https://timgsa.baidu.com/timg?image&quality=80&size=b9999_10000&sec=1534786199919&di=fbf8f009447a871685eb89ea7517207b&imgtype=0&src=http%3A%2F%2Fimgsrc.baidu.com%2Fimgad%2Fpic%2Fitem%2Fcdbf6c81800a19d800a8572139fa828ba61e466f.jpg",
    "sort": 1,
    "is_display": 1,
    "jump_url": "http://www.baidu.com"
    },
    {
    "id": 6,
    "name": "优惠专区",
    "description": "优惠专区广告图",
    "banner_type_id": 2,
    "img_url": "https://timgsa.baidu.com/timg?image&quality=80&size=b9999_10000&sec=1534786199919&di=fbf8f009447a871685eb89ea7517207b&imgtype=0&src=http%3A%2F%2Fimgsrc.baidu.com%2Fimgad%2Fpic%2Fitem%2Fcdbf6c81800a19d800a8572139fa828ba61e466f.jpg",
    "sort": 2,
    "is_display": 1,
    "jump_url": "http://www.baidu.com"
    }
    ],
    "chief_banner": [
    {
    "id": 7,
    "name": "首席芳疗师",
    "description": "首席芳疗师广告图",
    "banner_type_id": 3,
    "img_url": "https://timgsa.baidu.com/timg?image&quality=80&size=b9999_10000&sec=1534786199920&di=01c513f05ed1ae1ddd7c7e0a65fb0ae7&imgtype=0&src=http%3A%2F%2Fimg.zcool.cn%2Fcommunity%2F019174562796756ac72548781d76b0.jpg",
    "sort": 1,
    "is_display": 1,
    "jump_url": "http://www.baidu.com"
    }
    ],
    "category_list": [
    {
    "id": 2,
    "name": "明星爆款",
    "cat_banner": "https://timgsa.baidu.com/timg?image&quality=80&size=b9999_10000&sec=1534786474453&di=018d35ee3bb88cdba9922e245ef2b572&imgtype=0&src=http%3A%2F%2Fimgsrc.baidu.com%2Fimgad%2Fpic%2Fitem%2F8326cffc1e178a8220641584fc03738da977e8be.jpg",
    "index_display": 1,
    "cat_icon_img": null,
    "jump_url": null,
    "created_at": null,
    "updated_at": "2018-08-20 14:46:52"
    },
    {
    "id": 3,
    "name": "单方精油",
    "cat_banner": "https://timgsa.baidu.com/timg?image&quality=80&size=b9999_10000&sec=1534786474453&di=75c97bf25c075b4e12d777fe740296c2&imgtype=0&src=http%3A%2F%2Fwww.baicaolu.com%2Fuploads%2F201502%2F1425046964Sb97T13c.jpg",
    "index_display": 1,
    "cat_icon_img": null,
    "jump_url": null,
    "created_at": null,
    "updated_at": "2018-08-20 14:46:53"
    },
    {
    "id": 4,
    "name": "复方精油",
    "cat_banner": "https://timgsa.baidu.com/timg?image&quality=80&size=b9999_10000&sec=1534786474453&di=e5265680a7a1d21122165c4f382de6d4&imgtype=0&src=http%3A%2F%2Fpic.58pic.com%2F58pic%2F13%2F64%2F60%2F34t58PICmj9_1024.jpg",
    "index_display": 1,
    "cat_icon_img": null,
    "jump_url": null,
    "created_at": null,
    "updated_at": "2018-08-20 14:47:01"
    },
    {
    "id": 5,
    "name": "极致纯露",
    "cat_banner": "https://timgsa.baidu.com/timg?image&quality=80&size=b9999_10000&sec=1534786474452&di=0e2db32f59440d301a90071d0bc0a663&imgtype=0&src=http%3A%2F%2Fimgsrc.baidu.com%2Fimgad%2Fpic%2Fitem%2F738b4710b912c8fc8846b7e8f6039245d6882159.jpg",
    "index_display": 1,
    "cat_icon_img": null,
    "jump_url": null,
    "created_at": null,
    "updated_at": "2018-08-20 14:47:12"
    },
    {
    "id": 6,
    "name": "植物精油",
    "cat_banner": "https://timgsa.baidu.com/timg?image&quality=80&size=b9999_10000&sec=1534786474448&di=833c1755f534894d79b0ff1d10c4a1fb&imgtype=0&src=http%3A%2F%2Fimgsrc.baidu.com%2Fimgad%2Fpic%2Fitem%2F7a899e510fb30f24dd6ae726c295d143ad4b0326.jpg",
    "index_display": 1,
    "cat_icon_img": null,
    "jump_url": null,
    "created_at": null,
    "updated_at": "2018-08-20 14:47:42"
    },
    {
    "id": 7,
    "name": "芳疗设备",
    "cat_banner": "https://timgsa.baidu.com/timg?image&quality=80&size=b9999_10000&sec=1534786474535&di=b694f92d900be6065127c018026c556a&imgtype=0&src=http%3A%2F%2Fimgsrc.baidu.com%2Fimgad%2Fpic%2Fitem%2F9358d109b3de9c82e881a8126681800a18d84342.jpg",
    "index_display": 1,
    "cat_icon_img": null,
    "jump_url": null,
    "created_at": null,
    "updated_at": "2018-08-20 14:47:52"
    }
    ]
    },
    "status": 1,
    "msg": "success"
    }
    }
     */
    public function index(Request $request) {
        $resData = [];
        //首页广告图
        $allBanner = \DB::table('banner_type')
                ->leftJoin('banner_images','banner_type_id','=','banner_type.id')
                ->whereIn('banner_type.id',[1,2,3])
                ->where('is_display',1)
                ->get();
        foreach ($allBanner as $banner){
            if ($banner->banner_type_id == 1){
                $resData['index_banner'][] = $banner;
            }elseif ($banner->banner_type_id == 2){
                $resData['discount_banner'][] = $banner;
            }elseif ($banner->banner_type_id == 3){
                $resData['chief_banner'][] = $banner;
            }
        }
        //分类
        $cateList = Category::where('index_display',1)
                            ->get();
        $resData['category_list'] = $cateList;
        return response_format($resData);
    }

}
