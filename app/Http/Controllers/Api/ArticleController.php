<?php

namespace App\Http\Controllers\Api;

use App\Http\Code;
use App\Models\Article;
use App\Models\ArticleClass;
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
        /**
         * 参数：文章筛选条件
         * 文章分类
         * 该分类上次获取到的最新文章的id，没有就传null
         */

        $msg = [
            'article_class_id.required' => '你没有提供文章分类id',
            'max_article_id.required' => '你没有提供该文章分类下的最后浏览文章id',
        ];

        $validator = Validator::make(Input::all(),[
            'article_class_id' => 'required',
            'max_article_id' => 'required',
        ],$msg);

        if($validator->fails()){
            return response()->json([
                'result' => 'error',
                'code' => Code::$ParameterErr,
                'msg'=>$validator->errors()
            ]);
        }

        $article_class_id = $request->article_class_id;
        $max_article_id = $request->max_article_id;

        $article_list = Article::where('class_id',$article_class_id)
                               ->where('id','>',$max_article_id)
                               ->orderBy('id', 'asc')->limit(5)->get();

        return response()->json([
            'result' => 'ok',
            'code' => Code::$OK,
            'msg'=>'文章列表获取成功',
            'data' => $article_list
        ]);

    }

}
