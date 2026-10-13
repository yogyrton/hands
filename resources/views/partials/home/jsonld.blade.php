@php
    $siteUrl = rtrim($studio['site_url'] ?? config('app.url'), '/');
    $ogImage = ($studio['og_image'] ?? '') ?: asset('images/og-image.png');
    $geo = null;
    if (preg_match('/ll=([\d.]+)(?:%2C|,)([\d.]+)/', $studio['yandex_map_embed'] ?? '', $m)) {
        $geo = ['@type' => 'GeoCoordinates', 'latitude' => $m[2], 'longitude' => $m[1]];
    }
    $jsonld = array_filter([
        '@context' => 'https://schema.org',
        '@type' => 'HealthAndBeautyBusiness',
        'name' => 'HANDS',
        'legalName' => $studio['legal_name'] ?? '',
        'taxID' => $studio['legal_unp'] ?? '',
        'description' => 'Массажная студия в Могилёве — классический, спортивный, релакс-массаж, массаж спины и лица, коррекция фигуры',
        'url' => $siteUrl,
        'logo' => asset('images/hands-logo.svg'),
        'image' => $ogImage,
        'telephone' => $studio['phone'] ?? '',
        'address' => [
            '@type' => 'PostalAddress',
            'streetAddress' => 'переулок Пожарный, 3Б',
            'addressLocality' => 'Могилёв',
            'addressCountry' => 'BY',
        ],
        'geo' => $geo,
        'areaServed' => ['@type' => 'City', 'name' => 'Могилёв'],
        'priceRange' => 'от 50 р',
        // Часы работы для Google (Ежедневно 9:00–21:00). Если режим изменится —
        // поправьте здесь и в «Настройки студии → Режим работы».
        'openingHoursSpecification' => [
            '@type' => 'OpeningHoursSpecification',
            'dayOfWeek' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'],
            'opens' => '09:00',
            'closes' => '21:00',
        ],
        'sameAs' => array_values(array_filter([$studio['instagram_url'] ?? null])),
    ], fn ($v) => $v !== null && $v !== '');
@endphp
<script type="application/ld+json">
{!! json_encode($jsonld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
</script>
