<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class LDAPController extends Controller
{

    protected $ldap;

    public function __construct()
    {
    }

    public function show(Request $request)
    {
        $username = $request->only('username');
        $user = \Adldap::search()->where(env('LDAP_USER_ATTRIBUTE'), '=', $username . "");

        dd($user);
    }
}
