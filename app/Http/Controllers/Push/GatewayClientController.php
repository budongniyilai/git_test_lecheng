<?php

namespace App\Http\Controllers\Push;

use Common\GatewayClient;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class GatewayClientController extends Controller
{
    public function sendToALL(Request $request,GatewayClient $Gateway)
    {
        //一个发送推送消息的例子
        $Gateway->sendToAll('123456');
    }
}
