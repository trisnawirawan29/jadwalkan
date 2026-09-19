<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\View\View;

class UserManualController extends Controller
{
    public function __invoke(): View
    {
        return view('user-manual', [
            'appName' => Setting::value('app_name', config('app.name')),
        ]);
    }
}
