<?php

namespace App\Api\Controllers\Home;

use App\Api\Controllers\BaseController;
use App\Model\Cart;
use Illuminate\Http\Request;

class CartController extends BaseController
{
    public function addToCart(Request $request) {
        $client_id = $this->client->getUserByOpenId()->id;
        $Cart['client_id'] = $client_id;
        $Cart['good_id'] = $request->good_id;
        $Cart['number'] = $request->number;
        $Cart['price'] = $request->price;
        $Cart['attr_good_mapping_id'] = $request->attr_good_mapping_id;
        $Cart['unit_price'] = $request->unit_price;
        $Cart['original_unit_price'] = $request->original_unit_price;
        $Cart['total_price'] = $request->total_price;
        $res = Cart::create($Cart);
        dd($res);
    }
}
