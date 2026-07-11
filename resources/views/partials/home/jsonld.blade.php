@php($siteUrl = rtrim($studio['site_url'] ?? config('app.url'), '/'))
@php($jsonld = [
    '@context' => 'https://schema.org',
    '@type' => 'HealthAndBeautyBusiness',
    'name' => 'HANDS',
    'legalName' => $studio['legal_name'] ?? '',
    'taxID' => $studio['legal_unp'] ?? '',
    'description' => 'Массажная студия в Могилёве — классический, спортивный, релакс-массаж, массаж спины и лица, коррекция фигуры',
    'url' => $siteUrl,
    'telephone' => $studio['phone'] ?? '',
    'address' => [
        '@type' => 'PostalAddress',
        'streetAddress' => 'переулок Пожарный, 3Б',
        'addressLocality' => 'Могилёв',
        'addressCountry' => 'BY',
    ],
    'priceRange' => 'от 50 р',
    'sameAs' => array_values(array_filter([$studio['instagram_url'] ?? null])),
])
<script type="application/ld+json">
{!! json_encode($jsonld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
</script>
