<?php

namespace App\Http\Controllers\Api;

use App\Http\Code;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class UserInfoController extends Controller
{
    //修改个人信息
    public function modifyUserInfo(Request $request)
    {
        $msg = [
            'key.required' => '你没有提供要修改的属性',
            'value.required' => '你没有提供要修改的属性内容',
        ];

        $validator = Validator::make(Input::all(), [
            'key' => 'required',
            'value' => 'required',
        ], $msg);

        $key = $request->key;
        $value = $request->value;
        $user_id = $request->user()->id;

        return response()->json([
            'result' => 'ok',
            'code' => Code::$OK,
            'msg'=> '修改成功',
        ]);
    }
}
