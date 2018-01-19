<?php
/**
 * 课程控制器
 */
namespace App\Http\Controllers\Api;

use App\Http\Code;
use App\Models\EiCourseClass;
use App\Models\EiCourseLike;
use App\Models\EiCourses;
use App\Models\EiLocation;
use App\Models\EiPlatform;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Validator;

class CourseController extends Controller
{
    //查询课程分类列表
    public function getCourseClass()
    {
        $course_class = EiCourseClass::where('grade', 1)->get();
        foreach ($course_class as $key => $value){
            $second_level = EiCourseClass::where('grade',2)->where('upper_level',$value->id)->get();
            foreach ($second_level as $k => $v){
                $v->three_level = EiCourseClass::where('grade',3)->where('upper_level',$v->id)->get();
            }
            $value->second_level = $second_level;
        }

        return response()->json([
            'result' => 'ok',
            'code' => Code::$OK,
            'msg'=> '课程分类获取成功',
            'data' => $course_class
        ]);
    }

    //获取推荐课程列表
    public function recoCourse(Request $request)
    {
        /**
         * 逻辑：推荐热度较高的同城线下课程
         *       推荐热度较高的线上课程
         * 升级1：获取user_id来获取云端上的偏好，以更好的推荐内容
         * 升级2：获取用户本地传递过来的关键词数组，以推荐课程
         */

        //以下是接口完整性测试代码
        $reco_course_list = EiCourses::limit(10)         //推荐10个课程到前端
            ->select('id','ei_id','class_id','mode','img','name','price','signup_num','like_num')
            ->get();

        return response()->json([
            'result' => 'ok',
            'code' => Code::$OK,
            'msg'=> '推荐课程列表获取成功',
            'data' => $reco_course_list
        ]);
    }

    //获取筛选、排序后的课程列表
    public function getCourseList(Request $request)
    {
        /**
         * 字段
         * $search:搜索关键词
         * $course_class_id:课程类别id（all：所有课程 or id编号）
         * $course_mode:课程模式编号(all：所有课程 1、线下课程  2、线上课程)
         * $price_hi:最高价(默认为all，无限制 )
         * $price_mi:最低价（默认为0，无限制）
         * $order:排序(1、默认排序 2、距离最近  3、价格最低  4、价格最高  5、报名最多  6、评分最高)
         * $city:城市
         * $lat:纬度
         * $lng:经度
         */
        $msg = [
            'course_class_id.required' => '你没有提供课程类别编号',
            'course_mode.required' => '你没有提供课程模式',
            'price_hi.required' => '你没有提供最高价格',
            'price_mi.required' => '你没有提供最低价格',
            'order.required' => '你没有提供排序方法',
            'lat.required' => '你没有提供纬度',
            'lng.required' => '你没有提供经度'
        ];

        $validator = Validator::make(Input::all(),[
            'course_class_id' => 'required',
            'course_mode' => 'required',
            'price_hi' => 'required',
            'price_mi' => 'required',
            'order' => 'required',
            'lat' => 'required',
            'lng' => 'required',
        ],$msg);

        if($validator->fails()){
            return response()->json([
                'result' => 'error',
                'code' => Code::$ParameterErr,
                'msg'=>$validator->errors()
            ]);
        }

        //获得参数
        $course_class_id = $request->course_class_id;   //获得课程分类
        $course_mode = $request->course_mode;    //获得课程模式
        $price_hi = $request->price_hi;    //获得课程最高价
        $price_mi = $request->price_mi;    //获得课程最低价
        $order = $request->order;          //获得排序规则
        $lat = $request->lat;    //获得纬度
        $lng = $request->lng;    //获得经度

        //处理搜索关键词
        if(!isset($request->search)){    //不存在或为空
            $search = '%';  //表示任意长度的字符
        }else{
            $search = '%'.$request->search.'%';//这里的搜索还应该加上分词拆分
        }
        $course_list = EiCourses::where('name','like',$search);   //搜索包含这个词的课程

        //处理某城市机构查询
        if(isset($request->city)){    //如果存在城市，那么加入筛选条件
            //通过城市查询机构id
            $city = '%'.$request->city.'%';  //这里对城市名做一些处理，避免出现“成都”无法查询“成都市”的情况
            $ei_locations = EiLocation::where('city','like',$city)->select(['ei_id'])->get();
            $ei_id_arr =[];   //机构id数组
            foreach ($ei_locations as $key => $ei_location){
                $ei_id_arr[] = $ei_location->ei_id;
            }

            //通过机构id查询课程
            $course_list = $course_list->whereIn('ei_id',$ei_id_arr);
        }

        //处理课程分类
        $class_id_arr = [];
        if ($course_class_id!='all'){//如果不是查询所有分类课程，加入查询条件
            $course_class_id = intval($course_class_id);
            $course_class = EiCourseClass::where('id',$course_class_id)->first();
            if ($course_class != null){
                $grade = $course_class->grade;         //查询课程分类级别
                if ($grade==1){
                    $grade2 = EiCourseClass::where('upper_level',$course_class_id)->get();
                    foreach ($grade2 as $k => $v){
                        $grade2_id = $v->id;
                        $grade3 = EiCourseClass::where('upper_level',$grade2_id)->get();
                        foreach ($grade3 as $k2 => $v2){
                            $class_id_arr[] = $v2->id;
                        }
                    }
                }elseif ($grade==2){
                    $grade3 = EiCourseClass::where('upper_level',$course_class_id)->get();
                    foreach ($grade3 as $k2 => $v2){
                        $class_id_arr[] = $v2->id;
                    }
                }else{
                    $class_id_arr[] = $course_class_id;
                }
            }
            $course_list = $course_list->whereIn('class_id',$class_id_arr);
        }

        //处理课程模式
        if ($course_mode!='all'){
            $course_mode = intval($course_mode);
            $course_list = $course_list->where('mode',$course_mode);
        }

        //处理价格区间
        if($price_mi!=0||$price_hi!='all'){        //如果价格不是0到无穷，加入查询
            $price_hi = intval($price_hi);
            $price_mi = intval($price_mi);
            if ($price_mi>$price_hi){   //保证最大值比最小值大
                $middle = $price_mi;
                $price_mi = $price_hi;
                $price_hi = $middle;
            }
            $course_list = $course_list->where('price','>=',$price_mi)->where('price','<=',$price_hi);
        }

        //排序处理(1、默认排序 2、距离最近  3、价格最低  4、价格最高  5、报名最多  6、评分最高)
        if($order == 2){    //按距离最近排序
            $sql_str = 'getDistance('.$lat.','.$lng.',ei_courses.lat,ei_courses.lng) AS distance';
            $course_list = $course_list
                ->select('id','ei_id','class_id','mode','img','name','price','time','signup_num','like_num','lat','lng','address',
                         DB::raw($sql_str))
                ->orderBy('distance','asc')
                ->paginate(10);      //分页返回
        }else{    //按其它模式排序
            if($order == 1){
                //默认排序
                $course_list = $course_list->orderBy('signup_num','desc');  //暂时按报名最多的排序
            }elseif ($order == 3){
                //价格最低
                $course_list = $course_list->orderBy('price','asc');
            }elseif ($order == 4){
                //价格最高
                $course_list = $course_list->orderBy('price','desc');
            }elseif ($order == 5){
                //报名最多
                $course_list = $course_list->orderBy('signup_num','desc');
            }elseif ($order == 6){
                //评分最高
            }
            //获得筛选、排序后的查询结果，并选择字段分页输出
            $course_list = $course_list
                ->select('id','ei_id','class_id','mode','img','name','price','time','signup_num','like_num','lat','lng','address')
                ->paginate(10);      //分页返回

            //计算距离
            foreach ($course_list as $key1 => $value1){
                $value1 -> distance = intval(6378.138 * acos(
                        sin($lat * pi() /180)
                        *
                        sin($value1->lat * pi() /180)
                        +
                        cos($lat * pi() /180)
                        *
                        cos($value1->lat * pi() /180)
                        *cos($lng * pi() /180 - $value1->lng * pi() / 180)
                    )*1000);
            }
        }

        foreach ($course_list as $key=>$value){
            //处理距离
            if ($value->distance >= 1000){
                $value->distance = round($value->distance / 1000,1);
                $value->distance = $value->distance.'km';
            }else{
                $value->distance = $value->distance.'m';
            }

            $ei_id = $value->ei_id;
            //获取机构名称
            $value->ei_name = EiPlatform::where('id',$ei_id)->select(['ei_name'])->first()->ei_name;
        }

        if(count($course_list)==0){    //返回的结果没有数据
            return response()->json([
                'result' => 'ok',
                'code' => Code::$OK,
                'msg'=> '成功',
                'data'=> []
            ]);
        }

        return response()->json([
            'result' => 'ok',
            'code' => Code::$OK,
            'msg'=> '成功',
            'data' => $course_list
        ]);
    }

    //查询课程详情
    public function courseDetail (Request $request)
    {
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

        $course = EiCourses::where('id',$request->course_id)->first();
        if(!$course){       //如果该课程不存在
            return response()->json([
                'result' => 'error',
                'code' => Code::$NoData,
                'msg'=>'课程id不存在'
            ]);
        }

        //查询课程分类名
        $course_class_id = $course->class_id;
        $course->class_name = EiCourseClass::where('id',$course_class_id)->first()->name;

        //查询机构名称
        //查询课程地址
        //计算课程距离
        //查询上课时间
        //查询是否点赞
        //查询咨询入口账号
        //查询评价数量
        //查询好评度

        return response()->json([
            'result' => 'ok',
            'code' => Code::$OK,
            'msg'=> '成功',
            'data' => $course
        ]);
    }

    //给课程点赞
    public function addCourseLike(Request $request)
    {
        //参数校验
        $msg = [
            'course_id.required' => '你没有提供课程ID',
            'type.required' => '你没有提供操作指令'
        ];

        $validator = Validator::make(Input::all(),[
            'course_id' => 'required',
            'type' => 'required',
        ],$msg);

        if($validator->fails()){
            return response()->json([
                'result' => 'error',
                'code' => Code::$ParameterErr,
                'msg'=>$validator->errors()
            ]);
        }

        $course_id = $request->course_id;
        $type = $request->type;
        $user_id =$request->user()->id;

        //点赞判断
        $result = EiCourseLike::where('course_id',$course_id)
            ->where('user_id',$user_id)->first();

        $course = EiCourses::where('id',$course_id)->first();
        $like_num = $course->like_num;

        if($result){   //存在点赞记录
            if ($type == 1){
                return response()->json([
                    'result' => 'error',
                    'code' => Code::$ExistData,
                    'msg'=>'你已经点过赞了'
                ]);
            }
            if ($type == -1){
                //删除点赞记录
                $result1 = EiCourseLike::where('course_id',$course_id)->where('user_id',$user_id)->delete();
                //点赞总数减一
                if ($like_num>0){
                    $result2 = EiCourses::where('id',$course_id)->update(
                        [
                            'like_num'=>$like_num-1
                        ]
                    );
                }
                return response()->json([
                    'result' => 'ok',
                    'code' => Code::$OK,
                    'msg'=>'取消点赞成功'
                ]);
            }
        }else{  //点赞记录不存在
            if($type == -1){
                return response()->json([
                    'result' => 'error',
                    'code' => Code::$NoData,
                    'msg'=>'你未点赞，无法取消点赞'
                ]);
            }
            if ($type == 1){
                $add = [
                    'user_id' => $user_id,
                    'course_id' => $course_id
                ];
                $result3 = EiCourseLike::Insert($add);  //写入点赞记录
                $result4 = EiCourses::where('id',$course_id)->update(
                    [
                        'like_num'=>$like_num+1
                    ]
                );

                return response()->json([
                    'result' => 'ok',
                    'code' => Code::$OK,
                    'msg'=>'点赞成功！'
                ]);
            }
        }
    }
}
