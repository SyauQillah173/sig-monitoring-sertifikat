@props(['text' => null])

@php($footerText = $text ?? app(\App\Services\SystemSettingService::class)->publicLandingSettings()['footer_text'])

<footer {{ $attributes->merge(['class' => 'ui-developer-footer']) }}>
    <span>{{ $footerText }}</span>
    <span class="ui-developer-footer-heart" aria-hidden="true">*</span>
</footer>
