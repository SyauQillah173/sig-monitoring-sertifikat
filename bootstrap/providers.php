<?php

use App\Providers\AppServiceProvider;
use App\Providers\FortifyServiceProvider;
use Illuminate\View\ViewServiceProvider;

return [
    ViewServiceProvider::class,
    AppServiceProvider::class,
    FortifyServiceProvider::class,
];
