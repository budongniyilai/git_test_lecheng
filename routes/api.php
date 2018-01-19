<?php

use Illuminate\Http\Request;

//需要用户鉴权的路由
Route::group(['middleware'=>'auth:api','namespace' => 'Api'],function (){
    Route::get('/user',function(Request $request){return $request->user();});//获取用户信息
    Route::post('/add_evaluate','CourseEvaluateController@addEvaluate');//添加课程评价
    Route::post('/add_course_like','CourseController@addCourseLike');//添加课程点赞
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
});

