<?php

namespace App\Repositories;

use App\Model\UserAuth;
use App\Model\UserAuthCode;

class UserRepository implements UserRepositoryInterface
{

    public function createUser($data)
    {
        $user = UserAuth::where('user_id', $data['uid'])->first();
        if (!$user) {
            $user = new UserAuth;
            $user->user_id = $data['uid'];
            $user->token = $data['token'];
            $user->save();
        } else {
            UserAuth::where('user_id', $data['uid'])->update(['token' => $data['token']]);
        }

        $user_auth_code = UserAuthCode::where('user_id', $data['uid'])->first();
        if (!$user_auth_code) {
            $user_auth_code = new UserAuthCode;
            $user_auth_code->user_id = $data['uid'];
            $user_auth_code->auth_code = $data['auth_code'];
            $user_auth_code->save();
        } else {
            $user_auth_code->auth_code = $data['auth_code'];
            $user_auth_code->save();
        }

        return true;
        try {
        } catch (\Throwable $th) {
            return  false;
        }
    }
    public function getUserIdByAuthCode($auth_code)
    {
        return UserAuthCode::where('auth_code', $auth_code)->first();
    }
    public function getUserTokenByUserId($user_id)
    {
        return UserAuth::select('token')->where('user_id', $user_id)->first();
    }

    public function createAutcode($data)
    {
        $user_auth_code = UserAuthCode::where('user_id', $data['user_id'])->first();
        if (!$user_auth_code) {
            $user_auth_code = new UserAuthCode;
            $user_auth_code->user_id = $data['user_id'];
            $user_auth_code->auth_code = $data['auth_code'];
            $user_auth_code->save();
        } else {
            $user_auth_code->auth_code = $data['auth_code'];
            $user_auth_code->save();
        }
        return  $user_auth_code;
    }

    public function logout($user_id)
    {
        $user = UserAuth::where('user_id', $user_id)->first();
        $user->token = "";
        return true;
    }
}
