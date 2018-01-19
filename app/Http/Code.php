<?php
/**
 * 功能：
 * 操作: 唐强
 * 日期: 12/27
 * 时间: 5:59
 */

namespace App\Http;

class Code
{
    public static $OK = 200;           //成功
    public static $ParameterErr = 400; //参数格式错误
    public static $NoMatching = 401;   //参数不匹配
    public static $Overdue = 402;      //参数已经过期
    public static $NoData = 403;       //没有数据
    public static $ExistData = 404;    //数据已存在
    public static $SystemErr = 500;    //系统错误
}