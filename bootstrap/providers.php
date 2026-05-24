<?php

use App\Providers\AppServiceProvider;
use App\Providers\FortifyServiceProvider;
use Illuminate\View\ViewServiceProvider;

return [
    AppServiceProvider::class,
    FortifyServiceProvider::class,
    ViewServiceProvider::class,
];
