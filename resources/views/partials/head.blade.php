<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

<title>
    {{ filled($title ?? null) ? $title.' - '.config('app.name', 'SIG Monitoring Sertifikat') : config('app.name', 'SIG Monitoring Sertifikat') }}
</title>

<link rel="icon" type="image/png" href="{{ asset('images/logo/semen_indonesia_group.png') }}">
<link rel="shortcut icon" type="image/png" href="{{ asset('images/logo/semen_indonesia_group.png') }}">
<link rel="apple-touch-icon" href="{{ asset('images/logo/semen_indonesia_group.png') }}">
<meta name="theme-color" content="#091120">

<link rel="preconnect" href="https://fonts.bunny.net">
<link
    href="https://fonts.bunny.net/css?family=manrope:400,500,600,700,800&display=swap"
    rel="stylesheet"
    media="print"
    onload="this.media='all'"
>
<noscript><link href="https://fonts.bunny.net/css?family=manrope:400,500,600,700,800&display=swap" rel="stylesheet"></noscript>

@vite(['resources/css/app.css', 'resources/js/app.js'])
@fluxAppearance
