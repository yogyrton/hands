{{-- Хлебные крошки для поисковиков. Ожидает $items = [['name' => ..., 'url' => ...], ...] --}}
@php
    $breadcrumbLd = [
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => collect($items)->values()->map(fn ($item, $i) => array_filter([
            '@type' => 'ListItem',
            'position' => $i + 1,
            'name' => $item['name'],
            'item' => $item['url'] ?? null,
        ], fn ($v) => $v !== null))->all(),
    ];
@endphp
<script type="application/ld+json">
{!! json_encode($breadcrumbLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
</script>
