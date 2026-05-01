<?php

namespace App\Http\Controllers;
use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Models\IpAddress;

class DNSController extends Controller
{
    public function index(Request $request)
    {
        $ipAddress = $request->ip();
        $printSettings = IpAddress::where('ip_address', $ipAddress)->first();

        return Inertia::render('DNS', [
            'stats' => [
                'users' => 1000,
                'projects' => 250,
            ],
            'printSettings' => $printSettings ? [
                'SATO_ip_address'   => $printSettings->SATO_ip_address,
                'horizontal_offset' => $printSettings->horizontal_offset,
                'vertical_offset'   => $printSettings->vertical_offset,
            ] : null,
        ]);
    }
}
