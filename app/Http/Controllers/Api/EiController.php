<?php
/**
 * 机构控制器
 */
namespace App\Http\Controllers\Api;

use App\Http\Code;
use App\Models\CourseCollection;
use App\Models\EiApplicant;
use App\Models\EiCollection;
use App\Models\EiCourses;
use App\Models\EiOrganization;
use App\Models\EiPlatform;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Validator;

class EiController extends Controller
{
    //查询用户申请机构入驻状态
    public function eiIntoStatus(Request $request)
    {
        $user_id = $request->user()->id;
        $ei_platforms = EiPlatform::where('user_id',$user_id)->first();
        if(!$ei_platforms){
            $status = 0;
            $note = '未提交审核信息';
        }else{
            $status = $ei_platforms->status;
            $note = $ei_platforms->note;
        }
        return response()->json([
            'result' => 'ok',
            'code' => Code::$OK,
            'msg'=>'查询成功',
            'data'=>[
                'status'=>$status,   //0:未提交审核信息  1:审核中  2：待修改  3：审核通过
                'msg'=>$note
            ]
        ]);
    }

    //机构申请入驻提交数据处理
    public function eiIntoData(Request $request)
    {
        //获取表单提交过来的数据
        $ei_logo_url = $request->ei_logo_url;
        $ei_name = $request->ei_name;
        $ei_trademark_url = $request->ei_trademark_url;
        $ei_brief = $request->ei_brief;
        $ei_remark = $request->ei_remark;
        $ei_phone = $request->ei_phone;
        $ei_qq = $request->ei_qq;
        $ei_wechat = $request->ei_wechat;
        $ei_website = $request->ei_website;
        $co_name = $request->co_name;
        $org_code_url = $request->org_code_url;
        $org_code = $request->org_code;
        $business_license_url = $request->business_license_url;
        $business_license = $request->business_license;
        $edu_qualification_url = $request->edu_qualification_url;
        $edu_qualification = $request->edu_qualification;
        $proposer_name = $request->proposer_name;
        $id_card_url = $request->id_card_url;
        $id_card = $request->id_card;
        $phone_number = $request->phone_number;
        $e_mail = $request->e_mail;

        //要写入的3个数组
        $ei_platform=[
            'ei_logo'=>$ei_logo_url,
            'ei_name'=>$ei_name,
            'ei_trademark'=>$ei_trademark_url,
            'ei_brief'=>$ei_brief,
            'ei_remark'=>$ei_remark,
            'ei_phone'=>$ei_phone,
            'ei_qq'=>$ei_qq,
            'ei_wechat'=>$ei_wechat,
            'ei_website'=>$ei_website,
            'submission_time'=>time(),
            'status'=>1,
            'note'=>'已提交，请等待审核结果',
        ];
        $ei_organization = [
            'name' => $co_name,
            'org_code_img' => $org_code_url,
            'org_code' => $org_code,
            'business_license_img' => $business_license_url,
            'business_license_num' => $business_license,
            'edu_qualification_img' => $edu_qualification_url,
            'edu_qualification_num' => $edu_qualification,
        ];
        $ei_applicant = [
            'name' => $proposer_name,
            'id_img' => $id_card_url,
            'id_num' => $id_card,
            'mobile' => $phone_number,
            'e_mail' => $e_mail,
        ];

        $user_id = $request->user()->id;
        $ei_platforms = EiPlatform::where('user_id',$user_id)->first();
        if(!$ei_platforms){    //不存在，插入一条数据
            $ei_id = EiPlatform::insertGetld($ei_platform);   //写入平台数据
            $ei_organization['ei_id']=$ei_id;
            EiOrganization::insert($ei_organization);    //写入工商数据
            $ei_applicant['ei_id']=$ei_id;
            EiApplicant::insert($ei_applicant);    //写入申请人数据
        }else{      //已经存在，修改数据
            $ei_id = $ei_platforms->id;
            EiPlatform::where('id',$ei_id)->update($ei_platform);   //更新机构平台数据
            EiOrganization::where('ei_id',$ei_id)->update($ei_organization);   //更新工商数据
            EiApplicant::where('ei_id',$ei_id)->update($ei_applicant);       //更新申请人数据
        }
        return response()->json([
            'result' => 'ok',
            'code' => Code::$OK,
            'msg'=>'提交成功',
        ]);
    }
    
    
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
