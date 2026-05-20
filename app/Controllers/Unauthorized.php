<?php

namespace App\Controllers;

class Unauthorized extends BaseController
{
    public function index()
    {
        return view('errors/unauthorized', [
            'title' => 'Access Denied'
        ]);
    }
}