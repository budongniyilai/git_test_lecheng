<?php
/**
 * 功能：
 * 操作: 唐强
 * 日期: 2/2
 * 时间: 4:37
 */
namespace App\Http;
use Qiniu\Auth;
use Qiniu\Storage\UploadManager;
require_once __DIR__.'/../../vendor/qiniu/php-sdk/autoload.php';

class Qiniu
{
    /**
     * 移动文件至服务器上传目录
     * @param $file  上传的文件
     * @return array  成功时返回文件名和文件路径
     */
    public function moveFile($file)
    {
        $extension = $file->extension();   //获得文件的后缀
        $new_file_name = time() . mt_rand(10000, 99999);     //得到新的文件名
        $result = $file->move(app_path().'/uploads',$new_file_name. '.' . $extension);//移动文件
        if(!$result){
            return ['error' => '移动文件失败'];
        }
        return [
            'fileName' => $new_file_name . '.' . $extension,
            'filePath' => app_path().'/uploads/'.$new_file_name. '.' . $extension
        ];
    }

    //上传文件到七牛云存储
    public function uploadManager($fileName,$filePath)
    {
        $auth = new Auth(env('QINIU_ACCESS_KEY', ''),env('QINIU_SECRET_KEY', ''));  //初始化签权对象
        $token = $auth->uploadToken(env('QINIU_BUCKET', ''));  //生成上传token
        $uploadMgr = new UploadManager();  //构建UploadManager对象

        //上传文件：参数（上传凭证，上传文件名，上传文件的路径）
        $ret = $uploadMgr->putFile($token,$fileName,$filePath);
        return $ret;
    }
}