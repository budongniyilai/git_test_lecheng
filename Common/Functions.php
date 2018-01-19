<?php
/**
 * 功能：
 * 操作: 唐强
 * 日期: 12/29
 * 时间: 6:23
 */

namespace Common;


use App\Models\SmsCode;

class Functions
{
    //同一手机号生成不重复的短信验证码
    public function get_Verfycode($mobile)
    {
        $verfy_code = mt_rand(100000, 999999);
        $result = SmsCode::where('mobile',$mobile)->where('code',$verfy_code)->first();

        if($result){
            $this->get_Verfycode($mobile);
        }else{
            return $verfy_code;
        }
    }
}