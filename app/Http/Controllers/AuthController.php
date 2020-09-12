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
            'code' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => 'query param structure incoreect please check your param'], 400);
        }
        $client_id = Arr::get($data, 'client_id');
        $client_secret = Arr::get($data, 'client_secret');
        $code = Arr::get($data, 'code');
        try {
            $URL = env('SSO_MANAGE_URL') . "/applications/client/${client_id}/check-secret";
            $client = new Client(['base_uri' => $URL]);
            $response = $client->request('POST', $URL, ['json' => ['client_secret' => $client_secret]]);
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
        } catch (\Throwable $th) {
            $responseBody = $th->getResponse();
            if ($responseBody->getStatusCode() == 404) {
                return response()->json(['message' => 'client_id and client_secret not match'], 404);
            } else {
                return response()->json(['message' => 'something went wrong pls contact developer'], 500);
            }
        }
    }

    public function getClient(Request $request, $client_id)
    {

        try {
            $URL = env('SSO_MANAGE_URL') . "/applications/client/${client_id}";
            $client = new Client(['base_uri' => $URL]);
            $response = $client->request('GET', $URL);

            if ($response->getStatusCode() == 200) {
                $response = json_decode($response->getBody(), true);
                return response()->json($response, 200);
            }
        } catch (\Throwable $th) {
            $responseBody = $th->getResponse();
            if ($responseBody->getStatusCode() == 404) {
                return response()->json(['message' => 'client_id and client_secret not match'], 404);
            } else {
                return response()->json(['message' => 'something went wrong pls contact developer'], 500);
            }
        }
    }

    public function getUserByToken(Request $request)
    {
        $data = $request->all();
        $user_id = $data["user_id"];
        $URL = env('SSO_MANAGE_URL') . "/users/$user_id";
        $client = new Client(['base_uri' => $URL]);
        $response = $client->request('GET', $URL);

        if ($response->getStatusCode() == 200) {
            $response = json_decode($response->getBody(), true);
            return response()->json($response, 200);
        }
    }

    public function getAuthCodeByUserId(Request $request)
    {
        $data = $request->all();
        $auth_code = substr(str_shuffle(str_repeat($x = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil(10 / strlen($x)))), 1, 10);
        $data["auth_code"] = $auth_code;
        $response = $this->user->createAutcode($data);
        return response()->json($response, 200);
    }

    public function logout(Request $request)
    {
        $data = $request->all();
        $response = $this->user->logout($data["user_id"]);
        if ($response) {
            return response()->json(["message" => "logout"], 200);
        }
        return response()->json(['message' => 'something went wrong pls contact developer'], 500);
    }
}
