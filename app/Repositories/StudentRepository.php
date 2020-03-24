<?php

namespace App\Repositories;

use App\Model\Student;

class StudentRepository implements StudentRepositoryInterface
{
    public function getAllStudent()
    {
        $students = Student::all();
        return $students;
    }
    public function createStudent($data)
    {
        $students = new Student;
        $students->name = $data['name'];
        $students->grade = $data['grade'];
        $students->save();

        return $students;
    }
}
