<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Adldap\Laravel\Facades\Adldap;
use App\Repositories\UserRepositoryInterface;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use JWTAuth;
use JWTFactory;
use Tymon\JWTAuth\Exceptions\JWTException;

class AuthController extends Controller
{

    protected $user;

    public function __construct(UserRepositoryInterface $user)
    {
        $this->user = $user;
    }
    public function getTokenByAuthCode(Request $request)
    {
        $data = $request->all();
        $validator =  Validator::make($request->all(), [
            'client_id' => 'required',
            'client_secret' => 'required',
            'grant_type' => 'required',
            'code' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => 'query parm structure incoreect please check your param'], 400);
        }
        //dont forget config secret
        $client_id = Arr::get($data, 'client_id');
        $client_secret = Arr::get($data, 'client_secret');
        $code = Arr::get($data, 'code');

        $user_auth = $this->user->getUserIdByAuthCode($code);
        if ($user_auth) {
            $now = Carbon::now();
            $create_date = Carbon::parse($user_auth->updated_at);

            $age = $now->diffInMinutes($create_date);
            if ($age > 5) {
                return response()->json(['message' => 'auth code has benn expired'], 401);
            }
            $token = $this->user->getUserTokenByUserId($user_auth->user_id);
            $URL = env('SSO_MANAGE_URL') . "/users/$user_auth->user_id";
            $client = new Client(['base_uri' => $URL]);
            $response = $client->request('GET', $URL);

            if ($response->getStatusCode() == 200) {
                $response = json_decode($response->getBody(), true);
                $response['token'] = $token;
                return response()->json($response, 200);
            }
        } else {
            return response()->json(['message' => 'User auth code not found'], 404);
        }
    }
}
