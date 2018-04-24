<?php

namespace App\Http\Controllers\Api;

use App\Models\EiCourseEvaluate;
use App\Models\MyCourse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Code;
use App\Models\EiCourses;
use App\Models\EiPlatform;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Validator;

class PersonalController extends Controller
{
    //我的课程
    public function myCourse(Request $request)
    {
        $user_id = $request->user()->id;

        $my_courses = MyCourse::where('user_id',$user_id)->orderBy('time', 'desc')->paginate(3);
        if (count($my_courses)==0){
            return response()->json([
                'result' => 'error',
                'code' => Code::$NoData,
                'msg'=>'没有课程'
            ]);
        }
        foreach ($my_courses as $key=>$value){
            $course = EiCourses::where('id',$value->course_id)->first();
            //获得课程图片、名称
            $value->img = $course->img;
            $value->name = $course->name;

            //获得机构信息
            $ei = EiPlatform::where('id',$course->ei_id)->first();
            if($ei){
                $value->ei_name = $ei->ei_name;
            }else{
                $value->ei_name = null;
            }

            //获取是否评价
            $evaluate = EiCourseEvaluate::where('user_id',$user_id)->where('course_id',$course->id)->first();
            if($evaluate){
                $value->evaluate = true;
            }else{
                $value->evaluate = false;
            }
        }
        return response()->json([
            'result' => 'ok',
            'code' => Code::$OK,
            'msg'=>'成功',
            'data'=>$my_courses
        ]);
    }
}
