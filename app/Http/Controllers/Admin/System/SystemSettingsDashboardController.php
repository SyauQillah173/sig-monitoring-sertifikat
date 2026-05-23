<?php

namespace App\Http\Controllers\Admin\System;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class SystemSettingsDashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('admin.system-settings.index');
    }
}
