<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Repositories\StudentRepositoryInterface;

class StudentController extends Controller
{
    private $student;

    public function __construct(StudentRepositoryInterface $student)
    {
        $this->student = $student;
    }
    public function index()
    {
        $students = $this->student->getAllStudent();
        return response()->json($students, 200);
    }
    public function store(Request $request)
    {
        $data = $request->all();
        $response = $this->student->createStudent($data);
        return response()->json($response, 200);
    }
}
