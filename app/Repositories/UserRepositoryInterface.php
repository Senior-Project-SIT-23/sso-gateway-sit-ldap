<?php

namespace App\Repositories;


interface UserRepositoryInterface
{
    public function createUser($data);
    public function getUserIdByAuthCode($data);
    public function getUserTokenByUserId($data);
}
