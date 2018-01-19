<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::defaultStringLength(191);
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id')->comment('编号');
            $table->string('name')->unique()->comment('用户名');
            $table->string('nickname')->nullable()->comment('昵称');    //注册时不需要填写，可以为空
            $table->string('mobile')->nullable()->unique()->comment('手机号');         //用微信登录时可以为空
            $table->string('weixin_open_id')->nullable()->unique()->comment('微信OPENID');   //注册用户可以为空
            $table->string('email')->nullable()->unique()->comment('邮箱');      //邮箱后面绑定，可以为空
            $table->unsignedTinyInteger('gender')->default(0)->comment('性别');    //默认为0，保密
            $table->string('password')->nullable()->comment('密码');
            $table->string('head')->nullable()->comment('头像');      //注册时不需要设置，可以为空
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
}
