<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class RunHelper extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'run:helper';        //命令代码

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "一站式生成或更新所有代码辅助文件";     //命令介绍

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()    //命令执行脚本
    {
        //在本地运行RunHelper会运行下面三条代码,一站式生成所有代码辅助文件
        if (\App::environment()==='local'){
            $this->call('ide-helper:generate');
            $this->call('ide-helper:meta');
            $this->call('ide-helper:models');
        }
    }
}
