<?php

namespace App\Http\Controllers\Data;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class QueryController extends Controller
{
    public function index() {
        return view('content.data.queries.index');
    }
}
