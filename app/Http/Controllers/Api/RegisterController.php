<?php
/**
 * 注册
 */
namespace App\Http\Controllers\Api;

use App\Http\Code;
use App\Models\SmsCode;
use App\Models\User;
use Curl\Curl;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Validator;

class RegisterController extends Controller
{
    //手机号注册
    public function index(Request $request)
    {
        $msg = [
            'mobile.required' => '你没有提供手机号码',
            'mobile.regex' => '手机号码格式有误',
            'mobile.unique' => '手机号已被注册',
            'password.required' => '你没有提供密码',
            'password.regex' => '密码必须为6至16位数字、字母、下划线的任意组合',
            'code.required' => '你没有提供短信验证码',
            'code.regex' => '短信验证码格式有误',
        ];

        $validator = Validator::make(Input::all(),[
            'mobile' => 'bail|required|regex:[^[1][0-9]{10}$]|unique:users',
            'password' => 'bail|required|regex:[^[0-9a-zA-Z_]{6,16}$]',
            'code' => 'bail|required|regex:[^[1-9][0-9]{5}$]',
        ],$msg);

        if($validator->fails()){
            return response()->json([
                'result' => 'error',
                'code' => Code::$ParameterErr,
                'msg'=>$validator->errors()
            ]);
        }

        $mobile = $request->mobile;
        $password = bcrypt($request->password);   //获取密码并加密
        $code = $request->code;

        //短信验证码校验
        $check_code = SmsCode::where('code',$code)
            ->where('mobile',$mobile)
            ->where('code_type','注册验证')
            ->first();

        if(!$check_code){
            return response()->json([
                'result' => 'error',
                'code' => Code::$NoMatching,
                'msg'=> '短信验证码错误'
            ]);
        }

        if($check_code->created_at + env('VERFYCODE_EFFECTIVE_TIME','') < time()){    //短信验证码有效期30分钟
            SmsCode::where('mobile',$mobile)->where('code_type','注册验证')->delete();   //删除所有注册验证码
            return response()->json([
                'result' => 'error',
                'code' => Code::$Overdue,
                'msg'=> '短信验证码已过期'
            ]);
        }
        SmsCode::where('mobile',$mobile)->where('code_type','注册验证')->delete();   //删除所有注册验证码

        //验证通过，注册
        $add = [
            'mobile'=>$mobile,
            'password'=>$password,
        ];

        $insert_user = User::insertGetId($add);
        if (!$insert_user){
            return response()->json([
                'result' => 'error',
                'code' => Code::$SystemErr,
                'msg'=> '注册失败，请稍后重试'
            ]);
        }

        //生成并返回token
        $http = new Curl();
        $response = $http->post('http://lecheng.viiwen.cn/api/oauth/token',
            [
                'grant_type' => 'password',
                'client_id' => 2,
                'client_secret' => 'uZzSct0HzVpfAltpYhbfdEqhiDc8uSo3XEop8Tg9',
                'username' => $mobile,
                'password' => $request->password,
                'scope' => '',
            ]
        );

        return response()->json([
            'result' => 'ok',
            'code' => Code::$OK,
            'msg'=> '注册成功',
            'data' => $response
        ]);
    }
}
