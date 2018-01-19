<?php
/**
 * 功能：
 * 操作: 唐强
 * 日期: 12/20
 * 时间: 7:28
 */

namespace App\Repositories;


use Prettus\Repository\Eloquent\BaseRepository;

abstract class BaseRepositoryEloquent extends BaseRepository
{
    public function paginate($limit = null, $columns = ['*'], $method = "paginate")
    {
        $pageSize=request('page_size',$limit);   //从request里提取page_size，如果page_size不存在，直接传入$limit
        return parent::paginate($pageSize, $columns, $method);
    }
}