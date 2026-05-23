<?php

namespace App\Http\Controllers;

use App\Services\Cement\CementDashboardService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(
        private readonly CementDashboardService $cementDashboardService,
    ) {}

    public function index(Request $request): RedirectResponse
    {
        return redirect()->route($request->user()->dashboardRouteName());
    }

    public function admin(Request $request): View
    {
        return $this->cementProductsView($request);
    }

    public function petugas(Request $request): View
    {
        return $this->cementProductsView($request);
    }

    private function cementProductsView(Request $request): View
    {
        return view('cement.products', [
            'dashboard' => $this->cementDashboardService->build($request->query()),
        ]);
    }
}
