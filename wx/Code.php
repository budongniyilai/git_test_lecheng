<?php

namespace Wx;

class Code
{
    public static $OK = 0; //成功
    public static $IllegalSessionKey = -41001; //非法session_key
    public static $IllegalIv = -41002; //非法Iv
    public static $AESDecryptionFails = -41003; //AES解密失败
    public static $AppIdMismatch = -41004; //AppId不匹配
    public static $GetSessionKeyFailure = -41005; //code换取session_key失败
}