<?php

namespace App\Http\Controllers\Data;

use App\Http\Controllers\Controller;
use App\Ldap\User;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function index() {
        return view('content.data.admin.index');
    }

    public function users() {
        return view('content.data.admin.users');
    }
}
