<?php

namespace App\Http\Controllers\Api;

use App\Http\Code;
use App\Models\Article;
use App\Models\ArticleClass;
use App\Models\ArticleCollection;
use App\Models\ArticleComment;
use App\Models\ArticleCommentLike;
use App\Models\ArticleLike;
use App\Models\EiPlatform;
use App\Models\FollowAuthor;
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
                               'comment_num','like_num','author_type','author_id')
                               ->where('class_id',$article_class_id)   //分类筛选条件
                               ->where('status',2)   //文章状态需为可读
                              //文章排序，暂时按照最新文章来排序，后面写一个综合热度排序
                               ->orderBy('id', 'desc')   //文章倒序排列，最新的在前
                               ->paginate(10);  //文章分页，每页返回5条数据

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
        }

        return response()->json([
            'result' => 'ok',
            'code' => Code::$OK,
            'msg'=>'文章列表获取成功',
            'data' => $article_list
        ]);

    }

    //查询文章详情
    public function getArticle(Request $request)
    {
        //用户id参数校验
        $msg = [
            'user_id.required' => '你没有提供用户id',
            'atricle_id.required' => '你没有提供文章id',
        ];

        $validator = Validator::make(Input::all(),[
            'user_id' => 'required',
            'atricle_id' => 'required',
        ],$msg);

        if($validator->fails()){
            return response()->json([
                'result' => 'error',
                'code' => Code::$ParameterErr,
                'msg'=>$validator->errors()
            ]);
        }

        $user_id = $request->user_id;
        $atricle_id = $request->atricle_id;
        //查询文章内容
        $article = Article::where('id',$atricle_id)->where('status',2)->first();
        if (!$article){
            return response()->json([
                'result' => 'error',
                'code' => Code::$NoData,
                'msg'=>'该文章不存在，或被限制显示'
            ]);
        }
        //查询作者名称
        if ($article->author_type =='机构'){
            $author_name = EiPlatform::select('ei_name','ei_logo')
                ->where('id',$article->author_id)->first();
            if ($author_name){
                $article->author_name = $author_name->ei_name;
                $article->author_head = $author_name->ei_logo;
            }else{
                $article->author_name = null;
                $article->author_head = null;
            }
        }
        if ($article->author_type =='个人'){
            $author_name = User::select('nickname','head')
                ->where('id',$article->author_id)->first();
            if ($author_name){
                $article->author_name = $author_name->nickname;
                $article->author_head = $author_name->head;
            }else{
                $article->author_name = null;
                $article->author_head = null;
            }
        }

        //查询是否给文章点过赞
        $re = ArticleLike::where('user_id',$user_id)->where('article_id',$atricle_id)->first();
        if($re){
            $article->like = true;     //该用户给该文章点过赞
        }else{
            $article->like = false;      //该用户没有给该文章点过赞
        }

        //查询是否收藏过该文章
        $re = ArticleCollection::where('user_id',$user_id)->where('article_id',$atricle_id)->first();
        if($re){
            $article->collection = true;     //该用户收藏了该文章
        }else{
            $article->collection = false;      //该用户没有收藏该文章
        }
        $data['article']=$article;

        //查询文章评价,查询是否给每个评价点过赞
        $comments = ArticleComment::where('article_id',$atricle_id)->get();
        foreach ($comments as $key=>$value){
            $comment_id = $value->id;
            $re = ArticleCommentLike::where('user_id',$user_id)->where('article_comment_id',$comment_id)->first();
            if($re){
                $value->like = true;     //该用户给该评论点过赞
            }else{
                $value->like = false;      //该用户没有给该评论点过赞
            }
        }
        $data['comments']=$comments;

        return response()->json([
            'result' => 'ok',
            'code' => Code::$OK,
            'msg'=>'文章查询成功',
            'data' =>$data
        ]);
    }

    //添加/取消 文章点赞
    public function addArticleLike(Request $request)
    {
        $msg = [
            'article_id.required' => '你没有提供文章ID',
            'type.required' => '你没有提供操作指令'
        ];

        $validator = Validator::make(Input::all(),[
            'article_id' => 'required',
            'type' => 'required',
        ],$msg);

        if($validator->fails()){
            return response()->json([
                'result' => 'error',
                'code' => Code::$ParameterErr,
                'msg'=>$validator->errors()
            ]);
        }

        $article_id = $request->article_id;
        $type = $request->type;
        $user_id =$request->user()->id;

        //点赞判断
        $result = ArticleLike::where('user_id',$user_id)->where('article_id',$article_id)->first();

        $article = Article::where('id',$article_id)->first();
        $like_num = $article->like_num;

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
                $result1 = ArticleLike::where('user_id',$user_id)->where('article_id',$article_id)->delete();
                //点赞总数减一
                if ($like_num>0){
                    $result2 = Article::where('id',$article_id)->update(
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
                    'article_id' => $article_id
                ];
                $result3 = ArticleLike::Insert($add);  //写入点赞记录
                $result4 = Article::where('id',$article_id)->update(
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

    //添加/取消 文章收藏
    public function addArticleCollection(Request $request)
    {
        $msg = [
            'article_id.required' => '你没有提供文章ID',
            'type.required' => '你没有提供操作指令'
        ];

        $validator = Validator::make(Input::all(),[
            'article_id' => 'required',
            'type' => 'required',
        ],$msg);

        if($validator->fails()){
            return response()->json([
                'result' => 'error',
                'code' => Code::$ParameterErr,
                'msg'=>$validator->errors()
            ]);
        }

        $article_id = $request->article_id;
        $type = $request->type;
        $user_id =$request->user()->id;

        //收藏判断
        $result = ArticleCollection::where('article_id',$article_id)
            ->where('user_id',$user_id)->first();

        if($result){   //存在收藏数据
            if ($type == 1){
                return response()->json([
                    'result' => 'error',
                    'code' => Code::$ExistData,
                    'msg'=>'你已经收藏过该文章'
                ]);
            }
            if ($type == -1){
                //删除收藏记录
                $result1 = ArticleCollection::where('article_id',$article_id)->where('user_id',$user_id)->delete();

                return response()->json([
                    'result' => 'ok',
                    'code' => Code::$OK,
                    'msg'=>'取消收藏成功'
                ]);
            }
        }else{  //点赞记录不存在
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
                    'article_id' => $article_id,
                    'time' => time()
                ];
                $result3 = ArticleCollection::Insert($add);  //写入收藏

                return response()->json([
                    'result' => 'ok',
                    'code' => Code::$OK,
                    'msg'=>'收藏成功！'
                ]);
            }
        }
    }
    
    //查看收藏文章
    public function queryArticleCollection(Request $request)
    {
        //获得用户id
        $user_id =$request->user()->id;
        $result = ArticleCollection::where('user_id',$user_id)->orderBy('time', 'desc')->paginate(10);
        if (count($result)==0){
            return response()->json([
                'result' => 'error',
                'code' => Code::$NoData,
                'msg'=>'没有收藏文章'
            ]);
        }
        foreach ($result as $key=>$value){
            $value->article_id;
            $articles = Article::where('id',$value->article_id)->first();
            $value->title = $articles->title;
            $value->img = $articles->img;
        }
        return response()->json([
            'result' => 'ok',
            'code' => Code::$OK,
            'msg'=>'成功',
            'data'=>$result
        ]);

    }

    //关注作者
    public function followAuthor(Request $request)
    {
        $msg = [
            'author_type.required' => '你没有提供作者类型',
            'author_id.required' => '你没有提供作者id',
            'type.required' => '你没有提供操作类型'
        ];

        $validator = Validator::make(Input::all(),[
            'author_type' => 'required',
            'author_id' => 'required',
            'type' => 'required',
        ],$msg);

        if($validator->fails()){
            return response()->json([
                'result' => 'error',
                'code' => Code::$ParameterErr,
                'msg'=>$validator->errors()
            ]);
        }

        $author_type = $request->author_type;
        $author_id = $request->author_id;
        $type = $request->type;
        $user_id = $request->user()->id;

        //查询是否关注了作者
        $result = FollowAuthor::where('author_type',$author_type)->where('author_id',$author_id)->where('user_id',$user_id)->first();

        if($result){   //存在关注数据
            if ($type == 1){
                return response()->json([
                    'result' => 'error',
                    'code' => Code::$ExistData,
                    'msg'=>'你已经关注过该作者'
                ]);
            }
            if ($type == -1){
                //删除关注记录
                $result1 = FollowAuthor::where('author_type',$author_type)->where('author_id',$author_id)->where('user_id',$user_id)->delete();

                return response()->json([
                    'result' => 'ok',
                    'code' => Code::$OK,
                    'msg'=>'取消关注成功'
                ]);
            }
        }else{  //关注记录不存在
            if($type == -1){
                return response()->json([
                    'result' => 'error',
                    'code' => Code::$NoData,
                    'msg'=>'你还未关注该作者，无法取消'
                ]);
            }
            if ($type == 1){
                $add = [
                    'user_id' => $user_id,
                    'author_type' => $author_type,
                    'author_id' => $author_id,
                    'time' => time()
                ];
                $result3 = FollowAuthor::Insert($add);  //写入关注

                return response()->json([
                    'result' => 'ok',
                    'code' => Code::$OK,
                    'msg'=>'关注成功！'
                ]);
            }
        }

    }

    //搜索文章
    public function searchArticle(Request $request)
    {
        $msg = [
            'search.required' => '你没有提供搜索关键词',
        ];

        $validator = Validator::make(Input::all(), [
            'search' => 'required',
        ], $msg);

        if ($validator->fails()) {
            return response()->json([
                'result' => 'error',
                'code' => Code::$ParameterErr,
                'msg' => $validator->errors()
            ]);
        }

        $search = '%' . $request->search . '%';//这里的搜索还应该加上分词拆分
        $article = Article::select('id','title','img_url','time','read_num','comment_num',
                                   'like_num','author_type','author_id')
                          ->where('title','like',$search)->where('status',2)->get();

        if (count($article)==0){
            return response()->json([
                'result' => 'error',
                'code' => Code::$NoData,
                'msg' => '没有搜索结果，请更换关键词'
            ]);
        }

        foreach ($article as $key=>$value){
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
        }

        return response()->json([
            'result' => 'ok',
            'code' => Code::$OK,
            'msg' => '成功',
            'data' => $article
        ]);
    }
    
    //查看作者主页及他的全部文章
    public function getAuthorAllArticle(Request $request)
    {
        $msg = [
            'author_type.required' => '你没有提供作者类型',
            'author_id.required' => '你没有提供作者id',
            'user_id.required' => '你没有用户id',
        ];

        $validator = Validator::make(Input::all(), [
            'author_type' => 'required',
            'author_id' => 'required',
            'user_id' => 'required'
        ], $msg);

        $author_type = $request->author_type;
        $author_id = $request->author_id;
        $user_id = $request->id;

        $data = [];

        if ($validator->fails()) {
            return response()->json([
                'result' => 'error',
                'code' => Code::$ParameterErr,
                'msg' => $validator->errors()
            ]);
        }
        //查询作者的头像、名称
        if ($author_type =='机构'){
            $author = EiPlatform::select('ei_logo','ei_name')
                ->where('id',$author_id)->first();
            if ($author){
                $data['author_name'] = $author->ei_name;
                $data['author_head'] = $author->ei_logo;
            }else{
                $data['author_name'] = null;
                $data['author_head'] = null;
            }
        }
        if ($author_type =='个人'){
            $author = User::select('nickname','head')
                ->where('id',$author_id)->first();
            if ($author){
                $data['author_name'] = $author->nickname;
                $data['author_head'] = $author->head;
            }else{
                $data['author_name'] = null;
                $data['author_head'] = null;
            }
        }
        //查询是否关注了该作者
        $follow = FollowAuthor::where('user_id',$user_id)->where('author_type',$author_type)->where('author_id',$author_id)->first();
        if ($follow){
            $data['follow'] = true;
        }else{
            $data['follow'] = false;
        }
        //查询作者的粉丝数
        $follow_num = FollowAuthor::where('author_type',$author_type)->where('author_id',$author_id)->count();
        $data['follow_num'] = $follow_num;

        //查询作者所有文章
        $articles = Article::select('id','title','img_url','time','read_num','comment_num',
                                    'like_num','author_type','author_id')
                           ->where('author_type',$author_type)->where('author_id',$author_id)->where('status',2)->get();
        $data['articles'] = $articles;

        return response()->json([
            'result' => 'ok',
            'code' => Code::$OK,
            'msg' => '成功',
            'data' => $data
        ]);
    }
}
