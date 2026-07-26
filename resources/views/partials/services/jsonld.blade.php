{{-- Structured data для страницы услуги. Ожидает $service, $studio --}}
@php
    $siteUrl = rtrim($studio['site_url'] ?? config('app.url'), '/');
    $serviceLd = array_filter([
        '@context' => 'https://schema.org',
        '@type' => 'Service',
        'name' => $service->name . ' в Могилёве',
        'serviceType' => 'Массаж',
        'description' => $service->seo_description ?: $service->lead,
        'url' => route('services.show', $service->slug),
        'areaServed' => ['@type' => 'City', 'name' => 'Могилёв'],
        'provider' => [
            '@type' => 'HealthAndBeautyBusiness',
            'name' => 'HANDS',
            'url' => $siteUrl,
        ],
        'offers' => $service->base_price > 0 ? [
            '@type' => 'Offer',
            'price' => (string) (int) $service->base_price,
            'priceCurrency' => 'BYN',
            'url' => route('services.show', $service->slug),
        ] : null,
    ], fn ($v) => $v !== null);
@endphp
<script type="application/ld+json">
{!! json_encode($serviceLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
</script>
