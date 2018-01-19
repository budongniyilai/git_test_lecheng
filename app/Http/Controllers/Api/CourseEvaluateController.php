<?php

namespace App\Http\Controllers\Api;

use App\Http\Code;
use App\Models\EiCourseEvaluate;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Validator;

class CourseEvaluateController extends Controller
{
    //添加课程评价
    public function addEvaluate(Request $request)
    {
        //请求参数校验
        $msg = [
            'course_id.required' => '你没有提供课程ID',
            'score.required' => '你没有提供评分',
            'comment.required' => '你没有提供评语',
        ];

        $validator = Validator::make(Input::all(),[
            'course_id' => 'required',
            'score' => 'required',
            'comment' => 'required',
        ],$msg);

        if($validator->fails()){
            return response()->json([
                'result' => 'error',
                'code' => Code::$ParameterErr,
                'msg'=>$validator->errors()
            ]);
        }

        $course_id = $request->course_id;
        $user_id = $request->user()->id;
        $score = $request->score;
        $comment = $request->comment;
        $time = time();

        //评价权限校验（报名了该课程的学员才能评价）

        //判断课程是否开始

        //判断是否已经写入过评价
        $evaluate = EiCourseEvaluate::where('user_id',$user_id)
            ->where('course_id',$course_id)->first();
        if($evaluate){
            return response()->json([
                'result' => 'error',
                'code' => Code::$ExistData,
                'msg'=> '你已经评价过该课程'
            ]);
        }


        //评价写入
        $add = [
            'user_id' => $user_id,
            'course_id' => $course_id,
            'score' => $score,
            'comment' => $comment,
            'time' => $time
        ];

        $res = EiCourseEvaluate::Insert($add);
        if(!$res){
            return response()->json([
                'result' => 'error',
                'code' => Code::$SystemErr,
                'msg'=> '评价失败，请稍后重试'
            ]);
        }

        return response()->json([
            'result' => 'ok',
            'code' => Code::$OK,
            'msg'=> '成功'
        ]);
    }


    //获得课程评价
    public function getEvaluate(Request $request){
        //请求参数校验
        $msg = [
            'course_id.required' => '你没有提供课程ID',
        ];

        $validator = Validator::make(Input::all(),[
            'course_id' => 'required',
        ],$msg);

        if($validator->fails()){
            return response()->json([
                'result' => 'error',
                'code' => Code::$ParameterErr,
                'msg'=>$validator->errors()
            ]);
        }

        $course_id = $request->course_id;

        //查询评价
        $request = EiCourseEvaluate::where('course_id',$course_id)->get();

        if(!$request){
            return response()->json([
                'result' => 'ok',
                'code' => Code::$OK,
                'msg'=>'成功',
                'data' => []
            ]);
        }
        foreach ($request as $key => $value){
            $user_id = $value->user_id;
            $user = User::where('id',$user_id)->first();
            $value->nick_name = $user->nickname;
            $value->head = $user->head;
        }

        return response()->json([
            'result' => 'ok',
            'code' => Code::$OK,
            'msg'=>'成功',
            'data' => $request
        ]);
    }
}
