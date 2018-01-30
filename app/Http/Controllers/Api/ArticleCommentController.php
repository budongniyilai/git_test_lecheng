<?php

namespace App\Http\Controllers\Api;

use App\Models\ArticleComment;
use App\Models\ArticleCommentLike;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Validator;
use App\Http\Code;

class ArticleCommentController extends Controller
{
    //添加文章评价
    public function addComment(Request $request)
    {
        $msg = [
            'article_id.required' => '你没有提供文章id',
            'comment.required' => '你没有提供评论内容',
        ];

        $validator = Validator::make(Input::all(),[
            'article_id' => 'required',
            'comment' => 'required',
        ],$msg);


        if($validator->fails()){
            return response()->json([
                'result' => 'error',
                'code' => Code::$ParameterErr,
                'msg'=>$validator->errors()
            ]);
        }

        $article_id = $request->article_id;
        $comment = $request->comment;
        $user_id = $request->user()->id;

        $add = [
            'user_id' => $user_id,
            'article_id' => $article_id,
            'comment' => $comment,
            'time' => time()
        ];

        $result = ArticleComment::Insert($add);  //写入评论
        if (!$result){    //评价写入失败
            return response()->json([
                'result' => 'error',
                'code' => Code::$SystemErr,
                'msg'=>'系统错误，请稍后重试'
            ]);
        }

        return response()->json([
            'result' => 'ok',
            'code' => Code::$OK,
            'msg'=>'评论成功！'
        ]);

    }

    //给文章评价点赞
    public function addCommentLike(Request $request)
    {
        $msg = [
            'article_comment_id.required' => '你没有提供文章评价ID',
            'type.required' => '你没有提供操作指令'
        ];

        $validator = Validator::make(Input::all(),[
            'article_comment_id' => 'required',
            'type' => 'required',
        ],$msg);

        if($validator->fails()){
            return response()->json([
                'result' => 'error',
                'code' => Code::$ParameterErr,
                'msg'=>$validator->errors()
            ]);
        }

        $article_comment_id = $request->article_comment_id;
        $type = $request->type;
        $user_id =$request->user()->id;

        //点赞判断
        $result = ArticleCommentLike::where('user_id',$user_id)->where('article_comment_id',$article_comment_id)->first();

        $article_comment = ArticleComment::where('id',$article_comment_id)->first();
        $like_num = $article_comment->like_num;

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
                $result1 = ArticleCommentLike::where('user_id',$user_id)->where('article_comment_id',$article_comment_id)->delete();
                //点赞总数减一
                if ($like_num>0){
                    $result2 = ArticleComment::where('id',$article_comment_id)->update(
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
                    'article_comment_id' => $article_comment_id
                ];
                $result3 = ArticleCommentLike::Insert($add);  //写入点赞记录
                $result4 = ArticleComment::where('id',$article_comment_id)->update(
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
