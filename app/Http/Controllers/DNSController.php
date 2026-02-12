<?php

namespace App\Http\Controllers;
use Inertia\Inertia;
use Illuminate\Http\Request;

class DNSController extends Controller
{
    public function index()
    {
        return Inertia::render('DNS', [
            'stats' => [
                'users' => 1000,
                'projects' => 250,
            ],
        ]);
    }
}
