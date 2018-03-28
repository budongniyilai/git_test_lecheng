<?php
/**
 * 机构控制器
 */
namespace App\Http\Controllers\Api;

use App\Http\Code;
use App\Models\CourseCollection;
use App\Models\EiCollection;
use App\Models\EiCourses;
use App\Models\EiPlatform;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Validator;

class EiController extends Controller
{
    //查询机构详情
    public function queryEiDetail(Request $request)
    {
        $msg = [
            'ei_id.required' => '你没有提供机构ID',
        ];

        $validator = Validator::make(Input::all(),[
            'ei_id' => 'required',
        ],$msg);

        if($validator->fails()){
            return response()->json([
                'result' => 'error',
                'code' => Code::$ParameterErr,
                'msg'=>$validator->errors()
            ]);
        }

        $ei_id = $request->ei_id;

        //查询机构详情
        $ei_detail = EiPlatform::where('id',$ei_id)->first();
        if(!$ei_detail){
            return response()->json([
                'result' => 'error',
                'code' => Code::$NoData,
                'msg'=>'该机构不存在'
            ]);
        }

        //查询是否提供了user_id,并判断是否收藏了该机构
        if(!isset($request->user_id)){    //用户id不存在或为空
            $ei_detail -> collection = false;
        }else{
            $query = EiCollection::where('ei_id',$ei_id)->where('user_id',$request->user_id)->first();
            if($query){
                $ei_detail -> collection = true;
            }else{
                $ei_detail -> collection = false;
            }
        }

        return response()->json([
            'result' => 'ok',
            'code' => Code::$OK,
            'msg'=>'成功',
            'data' => $ei_detail
        ]);
    }
    
    //收藏机构
    public function addEiCollection(Request $request)
    {
        $msg = [
            'ei_id.required' => '你没有提供机构ID',
            'type.required' => '你没有提供操作指令'
        ];

        $validator = Validator::make(Input::all(),[
            'ei_id' => 'required',
            'type' => 'required',
        ],$msg);

        if($validator->fails()){
            return response()->json([
                'result' => 'error',
                'code' => Code::$ParameterErr,
                'msg'=>$validator->errors()
            ]);
        }

        $ei_id = $request->ei_id;
        $type = $request->type;
        $user_id =$request->user()->id;

        //收藏判断
        $result = EiCollection::where('ei_id',$ei_id)
            ->where('user_id',$user_id)->first();

        if($result){   //存在收藏数据
            if ($type == 1){
                return response()->json([
                    'result' => 'error',
                    'code' => Code::$ExistData,
                    'msg'=>'你已经收藏过该机构'
                ]);
            }
            if ($type == -1){
                //删除收藏记录
                $result1 = EiCollection::where('ei_id',$ei_id)->where('user_id',$user_id)->delete();

                return response()->json([
                    'result' => 'ok',
                    'code' => Code::$OK,
                    'msg'=>'取消收藏成功'
                ]);
            }
        }else{  //收藏记录不存在
            if($type == -1){
                return response()->json([
                    'result' => 'error',
                    'code' => Code::$NoData,
                    'msg'=>'你还未收藏，无法取消'
                ]);
            }
            if ($type == 1){
                $add = [
                    'user_id' => $user_id,
                    'ei_id' => $ei_id,
                    'time' => time()
                ];
                $result3 = EiCollection::Insert($add);  //写入收藏

                return response()->json([
                    'result' => 'ok',
                    'code' => Code::$OK,
                    'msg'=>'收藏成功！'
                ]);
            }
        }
    }

    //查看收藏机构
    public function queryEiCollection(Request $request)
    {
        //获得用户id
        $user_id =$request->user()->id;
        $result = EiCollection::where('user_id',$user_id)->orderBy('time', 'desc')->paginate(10);
        if (count($result)==0){
            return response()->json([
                'result' => 'error',
                'code' => Code::$NoData,
                'msg'=>'没有收藏机构'
            ]);
        }
        foreach ($result as $key=>$value){
            $value->article_id;
            $articles = EiPlatform::where('id',$value->ei_id)->first();
            $value->ei_name = $articles->ei_name;
            $value->ei_logo = $articles->ei_logo;
        }
        return response()->json([
            'result' => 'ok',
            'code' => Code::$OK,
            'msg'=>'成功',
            'data'=>$result
        ]);

    }

}
