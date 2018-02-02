<?php

use Illuminate\Http\Request;

//需要用户鉴权的路由
Route::group(['middleware'=>'auth:api','namespace' => 'Api'],function (){
    Route::post('/add_evaluate','CourseEvaluateController@addEvaluate'); //添加课程评价
    Route::post('/add_course_like','CourseController@addCourseLike');    //添加/取消  课程点赞

    Route::post('/add_article_like','ArticleController@addArticleLike'); //添加/取消  文章点赞
    Route::post('/add_article_collection','ArticleController@addArticleCollection');  //添加/取消  文章收藏
    Route::post('/add_article_comment','ArticleCommentController@addComment');     //添加文章评价
    Route::post('/add_article_comment_like','ArticleCommentController@addCommentLike');     //添加/取消  文章评价点赞
    Route::post('/follow_author','ArticleController@followAuthor');  //关注/取消关注  作者

    Route::get('/get_user','UsersController@getUser');//获取用户信息
    Route::post('/modify_user_info','UserInfoController@modifyUserInfo');   //修改用户信息

});

//不需要用户鉴权的路由
Route::group(['namespace' => 'Api'],function (){
    Route::post('get_code','GetCodeController@index');//获取手机验证码
    Route::post('register','RegisterController@index');//注册
    Route::post('login','LoginController@login');//用户名登录
    Route::post('wx_login','LoginController@wxLogin');//微信登录
    Route::post('refresh_token','LoginController@refreshToken');//刷新令牌

    Route::get('get_banner_list','BannerController@getBannerList');//获取banner列表
    Route::get('get_course_class','CourseController@getCourseClass');//获取课程分类列表
    Route::post('get_course_list','CourseController@getCourseList');//查询搜索课程列表
    Route::post('reco_course_list','CourseController@recoCourse');//查询推荐课程
    Route::post('get_course_detail','CourseController@courseDetail');//获取课程详情
    Route::post('get_evaluate','CourseEvaluateController@getEvaluate');//查询课程评价
    Route::post('get_teacher_list','CourseTeacherController@getTeacher');//查询课程老师列表
    Route::post('get_teacher_details','CourseTeacherController@getTeacherDetails');//查询课程老师详情

    Route::get('get_article_class','ArticleController@getArticleClass');//获取文章分类
    Route::post('get_article_list','ArticleController@getArticleList'); //获取文章列表
    Route::post('get_article','ArticleController@getArticle');      //获取文章详细信息
    Route::post('search_article','ArticleController@searchArticle');      //搜索文章
    Route::post('get_author_all_article','ArticleController@getAuthorAllArticle');  //获取作者主页及所有文章列表

    Route::post('/upload','UserInfoController@upload');
});

