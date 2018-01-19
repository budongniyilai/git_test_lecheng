<?php

namespace App\Models;

use App\Repositories\UserRepository;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Passport\HasApiTokens;
use Prettus\Repository\Contracts\Transformable;
use Prettus\Repository\Traits\TransformableTrait;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements Transformable
{
    use TransformableTrait,Notifiable,HasApiTokens;

    protected $table='users';    //定义一个数据表

    /**
     * The attributes that are mass assignable.
     * 可填充的数据
     * @var array
     */
    protected $fillable = [
        'name',
        'nickname',
        'mobile',
        'weixin_open_id',
        'email',
        'gender',
        'password',
        'head',
    ];

    /*
     *  定义强制类型转换
     */
    public $casts=[
        //格式   '字段名'=>'数据类型'，
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /*
     *  定义关联关系，跟users表的关联关系
     *  详情查阅laravel文档Eloquent ORM->关联关系
     */

    /*
     * 多类型账号自动判断
     */
    public function findForPassport($username){
        return app(UserRepository::class)->findByUserName($username);
    }
}
