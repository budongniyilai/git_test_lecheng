<?php

namespace App\Http\Controllers\Api;

use App\Http\Code;
use App\Models\Article;
use App\Models\ArticleClass;
use App\Models\EiPlatform;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Validator;

class ArticleController extends Controller
{
    //获取文章分类
    public function getArticleClass()
    {
        $article_list = ArticleClass::get();
        if (count($article_list)!=0){
            return response()->json([
                'result' => 'ok',
                'code' => Code::$OK,
                'msg'=> '文章分类获取成功',
                'data' => $article_list
            ]);
        }

        return response()->json([
            'result' => 'error',
            'code' => Code::$NoData,
            'msg'=> '没有文章分类数据',
        ]);

    }

    //获取文章列表
    public function getArticleList(Request $request)
    {
        $msg = [
            'article_class_id.required' => '你没有提供文章分类id',
        ];

        $validator = Validator::make(Input::all(),[
            'article_class_id' => 'required',
        ],$msg);

        if($validator->fails()){
            return response()->json([
                'result' => 'error',
                'code' => Code::$ParameterErr,
                'msg'=>$validator->errors()
            ]);
        }

        $article_class_id = $request->article_class_id;

        $article_list = Article::select('id','title','img_url','time','read_num',
                               'comment_num','content','like_num','author_type','author_id')
                               ->where('class_id',$article_class_id)   //分类筛选条件
                               ->where('status',2)   //文章状态需为可读
                              //文章排序，暂时按照最新文章来排序，后面写一个综合热度排序
                               ->orderBy('id', 'desc')   //文章倒序排列，最新的在前
                               ->paginate(5);  //文章分页，每页返回5条数据

        foreach ($article_list as $key => $value){
            //查询作者名称
            if ($value->author_type =='机构'){
                $author_name = EiPlatform::select('ei_name')
                          ->where('id',$value->author_id)->first();
                if ($author_name){
                    $value->author_name = $author_name->ei_name;
                }else{
                    $value->author_name = null;
                }
            }
            if ($value->author_type =='个人'){
                $author_name = User::select('nickname')
                                     ->where('id',$value->author_id)->first();
                if ($author_name){
                    $value->author_name = $author_name->nickname;
                }else{
                    $value->author_name = null;
                }
            }

//            //去除html中的\r\n
//            $value->content = str_replace("\r\n","",$value->content);
//            //把html字符串中的\"转换成"
//            $value->content = str_replace("","",$value->content);

        }


        return response()->json([
            'result' => 'ok',
            'code' => Code::$OK,
            'msg'=>'文章列表获取成功',
            'data' => $article_list
        ]);

    }

}
