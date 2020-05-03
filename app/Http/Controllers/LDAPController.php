<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Adldap\Laravel\Facades\Adldap;

use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use JWTAuth;
use JWTFactory;
use Tymon\JWTAuth\Exceptions\JWTException;

class LDAPController extends Controller
{

    protected $ldap;

    public function __construct()
    {
    }


    protected function attemptLogin(Request $request)
    {
        // $credentials = $request->only($this->username(), 'password');
        $username = $request->all()['username'];
        $password = $request->all()['password'];;
        $user_format = env('LDAP_USER_FORMAT', 'cn=%s,' . env('LDAP_BASE_DN', ''));
        $userdn = sprintf($user_format, $username);
        // you might need this, as reported in
        // [#14](https://github.com/jotaelesalinas/laravel-simple-ldap-auth/issues/14):
        // Adldap::auth()->bind($userdn, $password);
        if (Adldap::auth()->attempt($userdn, $password, $bindAsUser = true)) {
            // the user exists in the LDAP server, with the provided password
            $sync_attrs = $this->retrieveSyncAttributes($username);
            $token = $this->encode($sync_attrs['uid']);
            $sync_attrs['token'] = $token;
            return response()->json($sync_attrs, 200);
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

    public function encode($student_id)
    {
        $factory = JWTFactory::customClaims([
            'sub'   => 'JWT',
            'student_id' => $student_id,
        ]);

        $payload = $factory->make();
        $token = JWTAuth::encode($payload)->get();
        return $token;
    }
}
