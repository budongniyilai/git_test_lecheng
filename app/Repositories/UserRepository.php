<?php

namespace App\Repositories;

use Prettus\Repository\Contracts\RepositoryCriteriaInterface;
use Prettus\Repository\Contracts\RepositoryInterface;

/**
 * Interface UserRepository
 * @package namespace App\Repositories;
 */
interface UserRepository extends RepositoryInterface,RepositoryCriteriaInterface
{
    /*
     * 根据Username自动查找用户
     */
    public function findByUserName($username);
}
