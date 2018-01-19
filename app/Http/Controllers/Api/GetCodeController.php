<?php
/**
 * 获取手机验证码
 */
namespace App\Http\Controllers\Api;

use App\Http\Code;
use App\Models\SmsCode;
use App\Models\User;
use Common\Functions;
use Common\Sms;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Validator;

class GetCodeController extends Controller
{
    //获取手机验证码
    public function index(Request $request)
    {
        $msg = [
            'mobile.required' => '你没有提供手机号码',
            'mobile.regex' => '手机号码格式有误',
            'code_type.required' => '你没有提供验证码类型'
        ];

        $validator = Validator::make(Input::all(),[
            'mobile' => 'bail|required|regex:[^[1][0-9]{10}$]',
            'code_type' => 'required'
        ],$msg);

        if($validator->fails()){
            return response()->json([
                'result' => 'error',
                'code' => Code::$ParameterErr,
                'msg'=>$validator->errors()
            ]);
        }

        $mobile = $request->mobile;      //得到手机号码
        $code_type = $request->code_type;   //得到验证码类型

        //判断验证码类型是否存在,及获取验证码权限处理
        $code_types = [
            '注册验证',
            '登录验证',
            '修改密码验证',
            '找回密码验证'
        ];

        if (!in_array($code_type,$code_types)){
            return response()->json([
                'result' => 'error',
                'code' => Code::$NoMatching,
                'msg'=> '你提供的验证码类型不存在'
            ]);
        }

        if ($code_type == '注册验证'){
            $result = User::where('mobile',$mobile)->first();
            if($result){   //该手机号已经被注册
                return response()->json([
                    'result' => 'error',
                    'code' => Code::$ExistData,
                    'msg'=> '手机号已经被注册'
                ]);
            }
        }

        //获得一个不重复的6位随机数作为短信验证码
        $functions = new Functions();
        $code = $functions->get_Verfycode($mobile);

        //把验证码写入数据库
        $add = [
            'mobile' => $mobile,
            'code' => $code,
            'code_type' => $code_type,
            'created_at' => time()
        ];
        $result = SmsCode::insert($add);

        if(!$result){
            return response()->json([
                'result' => 'error',
                'code' => Code::$SystemErr,
                'msg'=>'系统异常，请稍后重试'
            ]);
        }

        //发送短信验证码
        $message = $code_type.":您的手机验证码为".$code.",有效时间30分钟。【创慧乐成】";  //短信签名必须经过校验
        $sms = new Sms(array('api_key' => env('LUOSIMAO_API_KEY', ''), 'use_ssl' => FALSE ));
        $res = $sms->send($mobile,$message);    //发送
        if($res['error']!=0){     //发送验证码失败了
            return response()->json([
                'result' => 'error',
                'code' => Code::$SystemErr,
                'msg'=>'短信验证码获取失败，请稍后重试'
            ]);
        }

        return response()->json([
            'result' => 'ok',
            'code' => Code::$OK,
            'msg'=>'验证码已发送至手机'
        ]);
    }
}
