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
        //图片检测
        if(!$request->hasFile('value')){
            return response()->json([
                'result' => 'error',
                'code' => Code::$ParameterErr,
                'msg'=>'请上传图片文件'
            ]);
        }

        //图片移动
        $qiniu = new Qiniu();
        $move = $qiniu->moveFile($request->file('value'));//移动文件到服务器
        if(array_key_exists('error',$move)){  //移动文件出错
            return response()->json([
                'result' => 'error',
                'code' => Code::$SystemErr,
                'msg'=>$move['error']
            ]);
        }

        list($ret, $err) = $qiniu->uploadManager($move['fileName'],$move['filePath']);  //图片上传七牛云
        unlink($move['filePath']);  //删除上传到服务器上的图片
        if ($err!=null) {
            return response()->json([
                'result' => 'error',
                'code' => Code::$SystemErr,
                'msg'=>'图片上传失败'
            ]);
        }
        $head_pic = env('QINIU_DOMAIN_NAME','').$ret['key'];//上传到七牛云的文件地址
        return response()->json([
            'result' => 'ok',
            'code' => Code::$OK,
            'msg'=>'图片上传成功',
            'data' => $head_pic
        ]);
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
            //图片检测
            if(!$request->hasFile('value')){
                return response()->json([
                    'result' => 'error',
                    'code' => Code::$ParameterErr,
                    'msg'=> [
                        'value'=>'请上传正确的图片文件'
                    ]
                ]);
            }

            //图片移动
            $qiniu = new Qiniu();
            $move = $qiniu->moveFile($request->file('value'));//移动文件到服务器
            if(array_key_exists('error',$move)){  //移动文件出错
                return response()->json([
                    'result' => 'error',
                    'code' => Code::$SystemErr,
                    'msg'=>'系统错误，请稍后重试！'
                ]);
            }

            list($ret, $err) = $qiniu->uploadManager($move['fileName'],$move['filePath']);  //图片上传七牛云
            unlink($move['filePath']);  //删除上传到服务器上的图片
            if ($err!=null) {
                return response()->json([
                    'result' => 'error',
                    'code' => Code::$SystemErr,
                    'msg'=>'系统错误，请稍后重试！'
                ]);
            }
            $head_pic = env('QINIU_DOMAIN_NAME','').$ret['key'];//上传到七牛云的文件地址
            //修改头像
            $result = User::where('id',$user_id)->update(['head'=>$head_pic]);
            if($result){
                return response()->json([
                    'result' => 'ok',
                    'code' => Code::$OK,
                    'msg'=>'头像修改成功',
                    'data' => [
                        'head_pic' => $head_pic
                    ]
                ]);
            }
            return response()->json([
                'result' => 'error',
                'code' => Code::$SystemErr,
                'msg'=>'系统错误，请稍后重试'
            ]);

        }else{
            return response()->json([
                'result' => 'error',
                'code' => Code::$NoMatching,
                'msg'=> 'key错误',
            ]);
        }
    }
}
