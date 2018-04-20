<?php

namespace App\Http\Controllers\Push;

use App\Http\Code;
use App\Models\PrivateLetter;
use Common\GatewayClient;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Validator;

class GatewayClientController extends Controller
{
    public function sendToALL(Request $request,GatewayClient $Gateway)
    {
        //一个发送推送消息的例子
        $Gateway->sendToAll('123456');
        $Gateway->sendToUid(101,'123456789');
    }

    //将client_id与uid绑定
    public function bindUid(Request $request,GatewayClient $Gateway)
    {
        //参数校验
        $msg = [
            'client_id.required' => '你没有提供客服端id'
        ];

        $validator = Validator::make(Input::all(),[
            'client_id' => 'required',
        ],$msg);

        if($validator->fails()){
            return response()->json([
                'result' => 'error',
                'code' => Code::$ParameterErr,
                'msg'=>$validator->errors()
            ]);
        }

        $client_id = $request->client_id;
        $user_id = $request->user()->id;

        //判断该客户端id是否在线
        $result = $Gateway->isOnline($client_id);
        if($result==0){
            return response()->json([
                'result' => 'error',
                'code' => Code::$NotOnLine,
                'msg'=>'你提供的客户端id未在线'
            ]);
        }

        //将client_id与uid绑定
        $Gateway->bindUid($client_id,$user_id);

        //查看该连接用户是否有未接收的推送消息，有就直接推送给该用户
        $query = PrivateLetter::where('receive_user_id',$user_id)->where('status',0)->get();
        if(count($query)>0){     //有未读消息
            foreach ($query as $key => $value){
                $data = [
                    'type' => 'private_letters',        //表示私信
                    'send_user_id'=>$value->send_user_id,
                    'message_type'=>$value->message_type,
                    'message'=>$value->message
                ];
                $data = json_encode($data);
                $Gateway->sendToUid($user_id,$data);      //发送未读消息

                $id = $value->id;
                PrivateLetter::where('id',$id)->update(['status'=>1]);    //更新私信状态
            }
        }

        return response()->json([
            'result' => 'ok',
            'code' => Code::$OK,
            'msg'=>'绑定成功'
        ]);
    }

    //判断某用户id是否在线
    public function isUidOnline(Request $request,GatewayClient $Gateway)
    {
        //参数校验
        $msg = [
            'query_user_id.required' => '你没有提供要查询的用户id',
        ];

        $validator = Validator::make(Input::all(),[
            'query_user_id' => 'required',
        ],$msg);

        if($validator->fails()){
            return response()->json([
                'result' => 'error',
                'code' => Code::$ParameterErr,
                'msg'=>$validator->errors()
            ]);
        }

        $query_user_id = $request->query_user_id;
        $result = $Gateway->isUidOnline($query_user_id);
        if($result==0){
            $data = false;
        }else{
            $data = true;
        }

        return response()->json([
            'result' => 'ok',
            'code' => Code::$OK,
            'msg'=>[
                'on_line'=>$data
            ]
        ]);
    }

    //向某个用户id发送消息
    public function sendToUid(Request $request,GatewayClient $Gateway)
    {
        //参数校验
        $msg = [
            'receive_user_id.required' => '你没有提供接收用户id',
            'message_type.required' => '你没有提供消息类别',
            'message.required' => '你没有提供消息类型'
        ];

        $validator = Validator::make(Input::all(),[
            'receive_user_id' => 'required',
            'message_type' => 'required',
            'message' => 'required',
        ],$msg);

        if($validator->fails()){
            return response()->json([
                'result' => 'error',
                'code' => Code::$ParameterErr,
                'msg'=>$validator->errors()
            ]);
        }

        $receive_user_id = $request->receive_user_id;
        $message_type = $request->message_type;
        $message = $request->message;
        $send_user_id = $request->user()->id;

        //判断接收信息的用户是否在线
        $result = $Gateway->isUidOnline($receive_user_id);
        if($result==0){
            //消息状态改成未读
            $status = 0;
        }else{
            $status = 1;
        }
        //存储消息
        $arr = [
            'send_user_id'=>$send_user_id,
            'receive_user_id'=>$receive_user_id,
            'message_type'=>$message_type,
            'message'=>$message,
            'time' => time(),
            'status' => $status
        ];
        PrivateLetter::insert($arr);

        if($result==0){
            //反馈
            return response()->json([
                'result' => 'error',
                'code' => Code::$NotOnLine,
                'msg'=>'对方未在线，系统将在他上线后重发消息'
            ]);
        }

        //用户在线，发送消息
        $data = [
            'type' => 'private_letters',        //表示私信
            'send_user_id'=>$send_user_id,
            'message_type'=>$message_type,
            'message'=>$message
        ];
        $data = json_encode($data);
        $Gateway->sendToUid($receive_user_id,$data);

        return response()->json([
            'result' => 'ok',
            'code' => Code::$OK,
            'msg'=>'对方已收到消息'
        ]);
    }
}
