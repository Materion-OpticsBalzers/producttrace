<?php

namespace App\Http\Controllers\Data;

use App\Http\Controllers\Controller;

class AdminController extends Controller
{
    public function index() {
        return view('content.data.admin.index');
    }
}
