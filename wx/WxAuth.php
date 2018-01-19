<?php

namespace Wx;
use GuzzleHttp\Client;

class WxAuth
{
    /**
     * @param $code
     * @param $ed
     * @param $iv
     * @param $return
     * @return int
     */
    public static function getUserInfo($code, $ed, $iv, &$return)
    {
        $appId=\Config::get('wx.app_id','');
        $appSecret=\Config::get('wx.app_secret','');
        //请求微信服务器,获得openid和session_key
        $url = 'https://api.weixin.qq.com/sns/jscode2session?appid='.$appId.'&secret='.$appSecret.'&js_code='.$code.'&grant_type=authorization_code';
        $client=new Client();
        $response = $client->request('GET',$url);
        $body=$response->getBody()->getContents();
        $result = json_decode($body);

        //判断是否存在失败码字段
        if(array_key_exists('errcode', $result)){
            return Code::$GetSessionKeyFailure;
        }

        $session_key = $result->session_key;        //获得会话密钥
        $openid = $result->openid;                  //获得用户唯一标识

        //用户信息解密
        if (strlen($session_key)!=24){
            return Code::$IllegalSessionKey;
        }
        $aesKey = base64_decode($session_key);      //对称解密秘钥

        if (strlen($iv)!=24){
            return Code::$IllegalIv;
        }
        $aesIV = base64_decode($iv);                //对称解密算法初始向量

        $aesCipher = base64_decode($ed);            //对称解密的目标密文

        $data = openssl_decrypt($aesCipher,'AES-128-CBC',$aesKey,OPENSSL_RAW_DATA,$aesIV);      //对称解密

        $dataDecode = json_decode($data);

        if($dataDecode == null){
            return Code::$AESDecryptionFails;
        }

        if($dataDecode->watermark->appid != $appId){
            return Code::$AppIdMismatch;
        }

        $return = $dataDecode;    //以数组对象的形式返回用户信息
        return Code::$OK;
    }
}