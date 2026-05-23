<?php

namespace App\Http\Controllers;

use App\Services\LandingPageSummaryService;
use App\Services\SystemSettingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\View\View;

class LandingPageController extends Controller
{
    public function __construct(
        private readonly LandingPageSummaryService $landingPageSummaryService,
        private readonly SystemSettingService $systemSettingService,
    ) {}

    public function index(): View|RedirectResponse
    {
        if (auth()->check()) {
            return redirect()->route('dashboard');
        }

        return view('welcome', [
            'landingSummary' => $this->landingPageSummaryService->build(),
            'publicSettings' => $this->systemSettingService->publicLandingSettings(),
        ]);
    }

    public function summary(): Response
    {
        return response()
            ->view('landing.partials.summary-panel', [
                'landingSummary' => $this->landingPageSummaryService->build(),
                'publicSettings' => $this->systemSettingService->publicLandingSettings(),
            ])
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache');
    }
}
