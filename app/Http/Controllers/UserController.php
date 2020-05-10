<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Repositories\UserRepositoryInterface;

class UserController extends Controller
{
    private $user;

    public function __construct(UserRepositoryInterface $user)
    {
        $this->user = $user;
    }

    public function store(Request $request)
    {
        $data = $request->all();
        $response = $this->user->createUser($data);
        return response()->json($response, 200);
    }
}
