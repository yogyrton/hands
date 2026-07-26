{{-- Structured data для страницы мастера. Ожидает $master, $studio --}}
@php
    $siteUrl = rtrim($studio['site_url'] ?? config('app.url'), '/');
    $mstImg = $master->mainUrl();
    $personLd = array_filter([
        '@context' => 'https://schema.org',
        '@type' => 'Person',
        'name' => $master->name,
        'jobTitle' => $master->role,
        'url' => route('masters.show', $master->slug),
        'image' => $mstImg ?: null,
        'worksFor' => [
            '@type' => 'HealthAndBeautyBusiness',
            'name' => 'HANDS',
            'url' => $siteUrl,
        ],
    ], fn ($v) => $v !== null);
@endphp
<script type="application/ld+json">
{!! json_encode($personLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
</script>
