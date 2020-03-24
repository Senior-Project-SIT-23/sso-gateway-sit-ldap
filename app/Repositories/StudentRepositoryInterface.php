<?php

namespace App\Repositories;


interface StudentRepositoryInterface
{
    public function getAllStudent();
    public function createStudent($data);
}
