<?php

namespace App\Http\Controllers\Api;

use App\Http\Code;
use App\Http\Qiniu;
use App\Models\User;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class UserInfoController extends Controller
{
    //图片上传测试
    public function upload(Request $request)
    {
        if(!$request->hasFile('value')){
            return response()->json([
                'result' => 'error',
                'code' => Code::$ParameterErr,
                'msg'=>'请上传图片文件'
            ]);
        }

        $move = Qiniu::moveFile(file('value'));//移动
        return $move;


        //unlink(app_path().'/uploads/'.$new_file_name.'.'.$extension);//删除文件
    }

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

        if($validator->fails()){
            return response()->json([
                'result' => 'error',
                'code' => Code::$ParameterErr,
                'msg'=>$validator->errors()
            ]);
        }

        $key = $request->key;
        $value = $request->value;
        $user_id = $request->user()->id;

        if ($key == 'name'){  //修改用户名（乐成号）
            $exist_user = User::where('name',$value)->first();
            if ($exist_user){  //如果用户名存在
                if ($exist_user->id == $user_id){  //如果用户名存在，且为当前访问用户
                    return response()->json([
                        'result' => 'error',
                        'code' => Code::$ExistData,
                        'msg'=> '你未做任何修改',
                    ]);
                }
                return response()->json([
                    'result' => 'error',
                    'code' => Code::$ExistData,
                    'msg'=> '用户名已存在',
                ]);
            }

            $result = User::where('id',$user_id)->update(['name'=>$value]);

            if ($result){
                return response()->json([
                    'result' => 'ok',
                    'code' => Code::$OK,
                    'msg'=> '用户名修改成功',
                ]);
            }

            return response()->json([
                'result' => 'error',
                'code' => Code::$SystemErr,
                'msg'=> '系统错误，请稍后重试！',
            ]);


        }elseif ($key == 'nickname'){  //修改昵称
            $result = User::where('id',$user_id)->update(['nickname'=>$value]);
            if($result){
                return response()->json([
                    'result' => 'ok',
                    'code' => Code::$OK,
                    'msg'=> '昵称修改成功',
                ]);
            }

            return response()->json([
                'result' => 'error',
                'code' => Code::$SystemErr,
                'msg'=> '系统错误，请稍后重试！',
            ]);

        }elseif ($key == 'gender'){   //修改性别
            if($value!=0&&$value!=1&&$value!=2){    //0:保密  1:男  2:女
                return response()->json([
                    'result' => 'error',
                    'code' => Code::$NoMatching,
                    'msg'=> '性别参数错误',
                ]);
            }

            $result = User::where('id',$user_id)->update(['gender'=>$value]);
            if($result){
                return response()->json([
                    'result' => 'ok',
                    'code' => Code::$OK,
                    'msg'=> '性别修改成功',
                ]);
            }

            return response()->json([
                'result' => 'error',
                'code' => Code::$SystemErr,
                'msg'=> '系统错误，请稍后重试！',
            ]);

        }elseif ($key == 'head'){   //修改头像
            if(!$request->hasFile('value')){
                return response()->json([
                    'result' => 'error',
                    'code' => Code::$ParameterErr,
                    'msg'=> '请提供头像文件',
                ]);
            }

//            return 123;

        }else{
            return response()->json([
                'result' => 'error',
                'code' => Code::$NoMatching,
                'msg'=> 'key错误',
            ]);
        }
    }
}
