<?php

namespace App\Http\Controllers\Api;

use App\Http\Code;
use App\Models\EiCourseTeacher;
use App\Models\EiTeacher;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Validator;

class CourseTeacherController extends Controller
{
    //查询课程老师
    public function getTeacher(Request $request)
    {
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

        $request = EiCourseTeacher::where('course_id',$course_id)->get();
        foreach ($request as $key=>$value){
            $teacher_id = $value->teacher_id;
            $teacher = EiTeacher::where('id',$teacher_id)->first();
            $value->name = $teacher->name;
            $value->head = $teacher->head;
        }

        return response()->json([
            'result' => 'ok',
            'code' => Code::$OK,
            'msg'=>'成功',
            'data'=> $request
        ]);
    }
    
    //查询老师详情
    public function getTeacherDetails(Request $request)
    {
        //请求参数校验
        $msg = [
            'teacher_id.required' => '你没有提供老师ID',
        ];

        $validator = Validator::make(Input::all(),[
            'teacher_id' => 'required',
        ],$msg);

        if($validator->fails()){
            return response()->json([
                'result' => 'error',
                'code' => Code::$ParameterErr,
                'msg'=>$validator->errors()
            ]);
        }

        $teacher_id = $request->teacher_id;

        //查询老师信息
        $teacher = EiTeacher::where('id',$teacher_id)->first();
        if (!$teacher){
            return response()->json([
                'result' => 'error',
                'code' => Code::$NoData,
                'msg'=>'该老师不存在！'
            ]);
        }

        return response()->json([
            'result' => 'ok',
            'code' => Code::$OK,
            'msg'=>'成功',
            'data' => $teacher
        ]);
    }
}
