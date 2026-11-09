<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\SystemHealth;

class SystemController extends Controller
{
    public function index(SystemHealth $health)
    {
        return view('admin.system', ['checks' => $health->checks()]);
    }
}
