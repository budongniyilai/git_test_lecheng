<?php

namespace App\Http\Controllers\Api;

use App\Http\Code;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Curl\Curl;
use Illuminate\Support\Facades\Input;

class LoginController extends Controller
{
    //用户名登录
    public function login(Request $request)
    {
        $msg = [
            'username.required' => '你没有提供手机号码',
            'password.required' => '您没有提供密码',
        ];

        $validator = Validator::make(Input::all(),[
            'username' => 'required',
            'password' => 'required',
        ],$msg);

        if($validator->fails()){
            return response()->json([
                'result' => 'error',
                'code' => Code::$ParameterErr,
                'msg'=>$validator->errors()
            ]);
        }

        $username = $request->username;
        $password = $request->password;

        //用户验证及生成token
        $http = new Curl();
        $response = $http->post('http://lecheng.viiwen.cn/api/oauth/token',
            [
                'grant_type' => 'password',
                'client_id' => 2,
                'client_secret' => 'uZzSct0HzVpfAltpYhbfdEqhiDc8uSo3XEop8Tg9',
                'username' => $username,
                'password' => $password,
                'scope' => '',
            ]
        );

        if (isset($response->error)){      //如果接口返回错误
            return response()->json([
                'result' => 'error',
                'code' => Code::$NoMatching,
                'msg'=> '账户名和密码不匹配'
            ]);
        }

        return response()->json([
            'result' => 'ok',
            'code' => Code::$OK,
            'msg'=> '登录成功',
            'data' => $response
        ]);
    }

    //微信登录
    public function wxLogin(Request $request)
    {
        $msg = [
            'code.required' => '你没有提供用户登录凭证code',
            'ed.required' => '你没有提供加密数据ed',
            'iv.required' => '你没有提供加密算法初始向量iv'
        ];

        $validator = Validator::make(Input::all(),[
            'code' => 'required',
            'ed' => 'required',
            'iv' => 'required',
        ],$msg);

        if($validator->fails()){
            return response()->json([
                'result' => 'error',
                'code' => Code::$ParameterErr,
                'msg'=>$validator->errors()
            ]);
        }

        $code = $request->code;
        $ed = $request->ed;
        $iv = $request ->iv;

        //用户验证及生成token
        $http = new Curl();
        $response = $http->post('http://lecheng.viiwen.cn/api/oauth/token',
            [
                'grant_type' => 'wechat_openid',
                'client_id' => 2,
                'client_secret' => 'uZzSct0HzVpfAltpYhbfdEqhiDc8uSo3XEop8Tg9',
                'code' => $code,
                'ed' => $ed,
                'iv' => $iv,
                'scope' => '',
            ]
        );

        if (isset($response->error)){      //如果接口返回错误
            return response()->json([
                'result' => 'error',
                'code' => Code::$NoMatching,
                'msg'=> '微信登录失败'
            ]);
        }

        return response()->json([
            'result' => 'ok',
            'code' => Code::$OK,
            'msg'=> '微信登录成功',
            'data' => $response
        ]);
    }

    //刷新token
    public function refreshToken(Request $request)
    {
        $msg = ['refresh_token.required' => '你没有提供用刷新令牌',];

        $validator = Validator::make(Input::all(),['refresh_token' => 'required',],$msg);

        if($validator->fails()){
            return response()->json([
                'result' => 'error',
                'code' => Code::$ParameterErr,
                'msg'=>$validator->errors()
            ]);
        }

        $refresh_token = $request->refresh_token;

        //刷新token
        $http = new Curl();
        $response = $http->post('http://lecheng.viiwen.cn/api/oauth/token',
            [
                'grant_type' => 'refresh_token',
                'client_id' => 2,
                'client_secret' => 'uZzSct0HzVpfAltpYhbfdEqhiDc8uSo3XEop8Tg9',
                'refresh_token' => $refresh_token,
                'scope' => '',
            ]
        );

        if (isset($response->error)){   //如果接口返回错误
            return response()->json([
                'result' => 'error',
                'code' => Code::$NoMatching,
                'msg'=> '刷新令牌失败'
            ]);
        }

        return response()->json([
            'result' => 'ok',
            'code' => Code::$OK,
            'msg'=> '刷新令牌成功',
            'data' => $response
        ]);
    }
}
