<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Adldap\Laravel\Facades\Adldap;
use App\Repositories\UserRepositoryInterface;
use GuzzleHttp\Client;

use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use JWTAuth;
use JWTFactory;
use Tymon\JWTAuth\Exceptions\JWTException;

class LDAPController extends Controller
{

    protected $ldap;
    protected $user;

    public function __construct(UserRepositoryInterface $user)
    {
        $this->user = $user;
    }

    protected function attemptLogin(Request $request)
    {
        // $credentials = $request->only($this->username(), 'password');
        $username = $request->all()['username'];
        $password = $request->all()['password'];;
        $user_format = env('LDAP_USER_FORMAT', 'cn=%s,' . env('LDAP_BASE_STUDENT', ''));
        $userdn = sprintf($user_format, $username);
        // you might need this, as reported in
        // [#14](https://github.com/jotaelesalinas/laravel-simple-ldap-auth/issues/14):
        // Adldap::auth()->bind($userdn, $password);
        if (Adldap::auth()->attempt($userdn, $password, $bindAsUser = true)) {
            // the user exists in the LDAP server, with the provided password
            $sync_attrs = $this->retrieveSyncAttributes($username);

            $URL = env('SSO_MANAGE_URL') . '/users';
            $client = new Client(['base_uri' => $URL]);
            $response = $client->request('POST', $URL, ['json' => ['sync_attrs' => $sync_attrs]]);

            if ($response->getStatusCode() == 200) {
                $auth_code = $this->generateRandomString(10);
                $user_id = $sync_attrs['uid'];
                $token = $this->encode($sync_attrs['uid'], 'ssoserviceforsit');

                $sync_attrs['token'] = $token;
                $sync_attrs['auth_code'] = $auth_code;
                $is_created_user = $this->user->createUser($sync_attrs);
                if ($is_created_user) {
                    return response()->json($sync_attrs, 200);
                } else {
                    return response()->json("Error Create Sessions user", 500);
                }
            } elseif ($response->getStatusCode() == 500) {
                return response()->json($response->getBody(), 500);
            }
        }

        $user_format = env('LDAP_STAFF_FORMAT', 'cn=%s,' . env('LDAP_BASE_STAFF', ''));
        $userdn = sprintf($user_format, $username);
        if (Adldap::auth()->attempt($userdn, $password, $bindAsUser = true)) {
            // the user exists in the LDAP server, with the provided password
            $sync_attrs = $this->retrieveSyncAttributes($username);

            $URL = env('SSO_MANAGE_URL') . '/users';
            $client = new Client(['base_uri' => $URL]);
            $response = $client->request('POST', $URL, ['json' => ['sync_attrs' => $sync_attrs]]);

            if ($response->getStatusCode() == 200) {
                $auth_code = $this->generateRandomString(10);
                $user_id = $sync_attrs['uid'];
                $token = $this->encode($sync_attrs['uid'], 'ssoserviceforsit');

                $sync_attrs['token'] = $token;
                $sync_attrs['auth_code'] = $auth_code;
                $is_created_user = $this->user->createUser($sync_attrs);
                if ($is_created_user) {
                    return response()->json($sync_attrs, 200);
                } else {
                    return response()->json("Error Create Sessions user", 500);
                }
            } elseif ($response->getStatusCode() == 500) {
                return response()->json($response->getBody(), 500);
            }
        }
        // the user doesn't exist in the LDAP server or the password is wrong
        // log error
        return response()->json(['message' => 'username or password incorrect'], 401);
    }

    protected function retrieveSyncAttributes($username)
    {
        $ldapuser = Adldap::search()->where(env('LDAP_USER_ATTRIBUTE'), '=', $username)->first();
        if (!$ldapuser) {
            return false;
        }
        // if you want to see the list of available attributes in your specific LDAP server:
        // var_dump($ldapuser->attributes); exit;
        // needed if any attribute is not directly accessible via a method call.
        // attributes in \Adldap\Models\User are protected, so we will need
        // to retrieve them using reflection.
        $ldapuser_attrs = null;

        $attrs = [];
        foreach (config('ldap_auth.sync_attributes') as $local_attr => $ldap_attr) {
            if ($local_attr == 'username') {
                continue;
            }

            $method = 'get' . $ldap_attr;
            if (method_exists($ldapuser, $method)) {
                $attrs[$local_attr] = $ldapuser->$method();
                continue;
            }

            if ($ldapuser_attrs === null) {
                $ldapuser_attrs = self::accessProtected($ldapuser, 'attributes');
            }

            if (!isset($ldapuser_attrs[$ldap_attr])) {
                // an exception could be thrown
                $attrs[$local_attr] = null;
                continue;
            }

            if (!is_array($ldapuser_attrs[$ldap_attr])) {
                $attrs[$local_attr] = $ldapuser_attrs[$ldap_attr];
            }

            if (count($ldapuser_attrs[$ldap_attr]) == 0) {
                // an exception could be thrown
                $attrs[$local_attr] = null;
                continue;
            }
            // now it returns the first item, but it could return
            // a comma-separated string or any other thing that suits you better
            $attrs[$local_attr] = $ldapuser_attrs[$ldap_attr][0];
            //$attrs[$local_attr] = implode(',', $ldapuser_attrs[$ldap_attr]);
        }

        return $attrs;
    }

    protected static function accessProtected($obj, $prop)
    {
        $reflection = new \ReflectionClass($obj);
        $property = $reflection->getProperty($prop);
        $property->setAccessible(true);
        return $property->getValue($obj);
    }

    public function username()
    {
        // we could return config('ldap_auth...') directly
        // but it seems to change a lot and with this check we make sure
        // that it will fail in future versions, if changed.
        // you can return the config(...) directly if you want.
        $column_name = config('ldap_auth.identifiers.database.username_column', null);
        if (!$column_name) {
            die('Error in LoginController::username(): could not find username column.');
        }
        return $column_name;
    }

    public function encode($student_id, $sub_type)
    {
        $factory = JWTFactory::customClaims([
            'sub'   => $sub_type,
            'student_id' => $student_id,
        ]);

        $payload = $factory->make();
        $token = JWTAuth::encode($payload)->get();
        return $token;
    }
    function generateRandomString($length)
    {
        return substr(str_shuffle(str_repeat($x = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil($length / strlen($x)))), 1, $length);
    }
}
