<?php

namespace App\Http\Controllers\Api;

use App\Http\Code;
use App\Models\Banner;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class BannerController extends Controller
{
    //获取Banner列表
    public function getBannerList()
    {
        $banner = Banner::where('display',1)->get();

        return response()->json([
            'result' => 'ok',
            'code' => Code::$OK,
            'msg'=> 'Banner列表获取成功',
            'data' => $banner
        ]);
    }
}
