<?php

namespace App\Http\Controllers;

use App\Models\IpAddress;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function update(Request $request)
    {
        $validated = $request->validate([
            'sato_ip' => ['required', 'string', 'ip', 'max:15'],
            'h_offset' => ['required', 'integer', 'between:-9999,9999'],
            'v_offset' => ['required', 'integer', 'between:-9999,9999'],
        ]);

        $ipAddress = $request->ip();

        IpAddress::where('ip_address', $ipAddress)
            ->update([
                'SATO_ip_address'   => $validated['sato_ip'],
                'horizontal_offset' => $validated['h_offset'],
                'vertical_offset'   => $validated['v_offset'],
            ]);

        return back()->with('settingsSaved', true);
    }
}
