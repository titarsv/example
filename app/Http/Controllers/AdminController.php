<?php

namespace App\Http\Controllers;

class AdminController extends Controller
{
    public function dashAction()
    {
        return view('admin.dashboard');
    }
}