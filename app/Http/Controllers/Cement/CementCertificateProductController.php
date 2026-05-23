<?php

namespace App\Http\Controllers\Cement;

use App\Http\Controllers\Controller;
use App\Services\Cement\CementDashboardService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class CementCertificateProductController extends Controller
{
    public function __construct(
        private readonly CementDashboardService $dashboardService,
    ) {}

    public function index(Request $request): View
    {
        return view('cement.products', [
            'dashboard' => $this->dashboardService->build($request->query()),
        ]);
    }
}
